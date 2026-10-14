<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Caches the read-only API responses the Next.js frontend depends on, so a
 * page view costs one cache read instead of a fistful of queries and eager
 * loads. Every endpoint under /api is public, identical for all callers and
 * changes only when an editor saves something, which makes it about the most
 * cacheable thing in the app.
 *
 * Deliberately driver-agnostic. It uses only get/put/increment, never
 * Cache::tags(), because tags are unsupported on the database and file stores
 * and this app has to keep working on shared hosting where Redis may not exist.
 * Point CACHE_STORE at redis and the same code gets faster with no edit.
 *
 * Invalidation is by version counter rather than key deletion. Cached keys are
 * namespaced with a version number; bumping it orphans every old key at once,
 * which is the only approach that can clear a whole group without tag support
 * and without enumerating keys. Orphans expire on their own TTL.
 *
 * The counter is global on purpose. Per-model counters would mean mapping every
 * model to the endpoints it appears in, and a single missed mapping serves
 * stale content indefinitely — a far worse failure than the handful of queries
 * it costs to repopulate everything after any edit.
 */
class ApiCache
{
    private const VERSION_KEY = 'api:cache-version';

    /**
     * Long by design: entries are invalidated by editor action, not by expiry.
     * The TTL is only a backstop against orphaned keys accumulating.
     */
    private const TTL_SECONDS = 86400;

    /**
     * Runs the callback and caches its result, or returns the cached copy.
     *
     * Cache failures never surface to the caller. A misconfigured CACHE_STORE
     * or an unreachable Redis should make the API slow, not broken, so any
     * problem falls through to querying the database directly.
     */
    public static function remember(string $key, Closure $callback): mixed
    {
        if (! config('api-cache.enabled', true)) {
            return $callback();
        }

        try {
            return Cache::remember(self::versionedKey($key), self::TTL_SECONDS, $callback);
        } catch (Throwable $e) {
            report($e);

            return $callback();
        }
    }

    /**
     * Reads a cached value, or null when absent or when caching is off.
     *
     * Paired with put() for callers that must not run their work twice — the
     * response middleware needs to decide whether a result is cacheable only
     * after producing it, which remember() cannot express.
     */
    public static function get(string $key): mixed
    {
        if (! config('api-cache.enabled', true)) {
            return null;
        }

        try {
            return Cache::get(self::versionedKey($key));
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * $ttlSeconds shortens this one entry's life. Invalidation here is by
     * editor action, which covers everything an editor does — but not content
     * that becomes visible on its own, with nobody saving anything. A caller
     * that knows when that happens next passes it in, so the entry expires then
     * instead of outliving the event by up to a day.
     */
    public static function put(string $key, mixed $value, ?int $ttlSeconds = null): void
    {
        if (! config('api-cache.enabled', true)) {
            return;
        }

        try {
            Cache::put(self::versionedKey($key), $value, min($ttlSeconds ?? self::TTL_SECONDS, self::TTL_SECONDS));
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Invalidates every cached API response by moving the namespace forward.
     * Called from the model observers that already trigger frontend
     * revalidation, so a save clears both caches in one place.
     */
    public static function flush(): void
    {
        try {
            // increment() returns false when the key is absent on some stores,
            // so seed it rather than silently failing to invalidate.
            if (Cache::increment(self::VERSION_KEY) === false) {
                Cache::forever(self::VERSION_KEY, self::currentVersion() + 1);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Builds a cache key that carries the current version, so old entries
     * become unreachable the moment the version moves.
     */
    private static function versionedKey(string $key): string
    {
        return 'api:v' . self::currentVersion() . ':' . $key;
    }

    private static function currentVersion(): int
    {
        try {
            return (int) Cache::get(self::VERSION_KEY, 1);
        } catch (Throwable) {
            return 1;
        }
    }
}
