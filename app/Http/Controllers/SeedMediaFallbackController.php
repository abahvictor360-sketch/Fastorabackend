<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Last resort for a /storage image that is not on the public disk.
 *
 * storage/app/public carries a `*` .gitignore, so its contents never arrive by
 * git. The only thing that puts the bundled photography there is
 * `app:sync-media`, which runs inside `app:deploy`. Deploy by hand, pull
 * without running it, or lose the directory to a host-side reset, and every
 * image on the live site 404s at once — the logo included. That has now
 * happened more than once.
 *
 * The bundled originals are in database/seeders/images, which is tracked, so
 * they are always present after a deploy even when the public disk is bare.
 * This restores the file from there on the first request for it and serves it,
 * which means the site repairs itself instead of waiting for someone to notice
 * and run a command.
 *
 * Only reached when the file is genuinely missing: the .htaccess rule in front
 * of this serves the real file directly whenever it exists, so a healthy site
 * never touches this code, and PHP is not put in the path of normal image
 * traffic.
 *
 * Uploads are deliberately not covered. An editor's upload exists only on the
 * server, so there is no copy here to restore it from — that is a backup
 * problem, not something a fallback can solve.
 */
class SeedMediaFallbackController extends Controller
{
    public function __invoke(Request $request, string $path): Response
    {
        $disk = Storage::disk('public');

        // Reject anything that tries to climb out of the media directories
        // before it is used to build a filesystem path. basename() alone is not
        // enough, since legitimate paths contain a directory segment.
        if (! $this->isSafe($path)) {
            abort(404);
        }

        if ($disk->exists($path)) {
            return $this->serve($disk->path($path));
        }

        // Only files the seeder put under seed/ have a bundled original.
        if (! str_starts_with($path, 'seed/')) {
            abort(404);
        }

        $source = database_path('seeders/images/' . basename($path));

        if (! is_file($source)) {
            abort(404);
        }

        // Put it back, so this request is the only one that pays for the repair.
        // A failure here (read-only disk, no space) must not take the image
        // down with it, so the response is served from the source either way.
        try {
            $disk->put($path, file_get_contents($source));
        } catch (\Throwable $e) {
            report($e);
        }

        return $this->serve($disk->exists($path) ? $disk->path($path) : $source);
    }

    private function isSafe(string $path): bool
    {
        return ! str_contains($path, '..')
            && ! str_contains($path, "\0")
            && preg_match('#^[A-Za-z0-9._/-]+$#', $path) === 1;
    }

    private function serve(string $absolutePath): BinaryFileResponse
    {
        // Long-lived and immutable: these filenames are stable and their
        // contents never change in place, and the frontend's image optimiser
        // fetches each source once and caches what it derives from it.
        return response()->file($absolutePath, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
