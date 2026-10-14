<?php

namespace App\Http\Middleware;

use App\Models\Post;
use App\Support\ApiCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Caches successful GET responses on the public API.
 *
 * Applied as middleware rather than inside each controller for two reasons.
 * It covers all eight controllers without touching any of them, so there is no
 * chance of a hand-rewritten action quietly changing its JSON shape. And it
 * caches the rendered response body, so what a cache hit returns is byte-for-byte
 * what a miss returned — no resource object gets serialised and rehydrated.
 *
 * Only GET is cached, and only 2xx. POST /api/contact writes an enquiry and must
 * always reach the controller; a 404 or 500 must never be stored, or one blip
 * would persist for the whole TTL.
 */
class CacheApiResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        $key = 'response:' . sha1($request->path() . '?' . $this->normalisedQuery($request));
        $cached = ApiCache::get($key);

        if (is_array($cached)) {
            return response($cached['content'], $cached['status'])
                ->header('Content-Type', $cached['content_type'])
                ->header('X-Api-Cache', 'hit');
        }

        $response = $next($request);
        $status = $response->getStatusCode();

        // Only store success. Caching a 404 or a 500 would let one bad moment
        // persist for the whole TTL — exactly the failure this host produces
        // under load.
        if ($status >= 200 && $status < 300) {
            ApiCache::put($key, [
                'content' => $response->getContent(),
                'status' => $status,
                'content_type' => $response->headers->get('Content-Type', 'application/json'),
            ], $this->secondsUntilNextScheduledPost());
        }

        $response->headers->set('X-Api-Cache', 'miss');

        return $response;
    }

    /**
     * How long until the next scheduled post goes live, or null when none is
     * waiting.
     *
     * A scheduled post publishes itself: Post::published() counts it the moment
     * its time passes, with nothing saved and no job run. That makes it the one
     * change this cache cannot hear about, and the symptom was a post staying
     * invisible long after its publish time — for up to the full day-long TTL,
     * until an unrelated edit happened to flush everything.
     *
     * Capping the TTL at that moment means the entry expires exactly when the
     * answer changes. One cheap query, and only when writing an entry.
     */
    private function secondsUntilNextScheduledPost(): ?int
    {
        $next = Post::query()
            ->where('status', 'scheduled')
            ->where('published_at', '>', now())
            ->min('published_at');

        if (! $next) {
            return null;
        }

        // At least a second: a zero TTL means "forget immediately" on some
        // stores and "never expire" on others, and neither is what we want.
        return max(1, now()->diffInSeconds($next, false));
    }

    /**
     * Sorts query parameters so ?a=1&b=2 and ?b=2&a=1 share one cache entry
     * instead of two, and so key generation is stable across requests.
     */
    private function normalisedQuery(Request $request): string
    {
        $query = $request->query();
        ksort($query);

        return http_build_query($query);
    }
}
