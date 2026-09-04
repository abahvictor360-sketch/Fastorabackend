<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The About page's "How Fastora began" content block was pointed at a Media
 * row (a fresh admin upload, path media/01KZF31GHDG6K4KYTXSXMG5XXA.jpg) whose
 * file was never actually written to storage — the database record exists,
 * the upload itself didn't persist, so the image 404s. Not the seed-image
 * storage-wipe this app already has a fallback for: this file was never part
 * of the bundled seed set, so app:sync-media and the /storage/{path}
 * fallback route have nothing to restore it from.
 *
 * Reverts to the original photo (seed/121758.jpg, still present and
 * serving), so the page stops showing a broken image. An editor can upload
 * the intended replacement again from Pages -> About -> Content ->
 * "How Fastora began" -> Image whenever they have the file.
 */
return new class extends Migration
{
    private const BROKEN_PATH = 'media/01KZF31GHDG6K4KYTXSXMG5XXA.jpg';
    private const FALLBACK_ALT = 'A communications professional at work in a studio';

    public function up(): void
    {
        $page = DB::table('pages')->where('slug', 'about')->first();

        if ($page === null) {
            return;
        }

        $layout = json_decode($page->layout ?? '[]', true);

        if (! is_array($layout)) {
            return;
        }

        $fallbackId = DB::table('media')->where('alt', self::FALLBACK_ALT)->value('id');

        if ($fallbackId === null) {
            return;
        }

        $changed = false;

        foreach ($layout as $i => $block) {
            if (($block['type'] ?? null) !== 'content') {
                continue;
            }

            $imageId = $block['data']['image'] ?? null;

            if ($imageId === null) {
                continue;
            }

            $path = DB::table('media')->where('id', $imageId)->value('path');

            if ($path === self::BROKEN_PATH) {
                $layout[$i]['data']['image'] = $fallbackId;
                $changed = true;
            }
        }

        if (! $changed) {
            return;
        }

        DB::table('pages')->where('id', $page->id)->update([
            'layout' => json_encode($layout),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Not restorable: reverting back to the broken image would just
        // reintroduce the 404, and the original replacement file was never
        // actually captured anywhere this migration can reach.
    }
};
