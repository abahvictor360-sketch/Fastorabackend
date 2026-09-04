<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Corrects an over-eager fix: 2026_09_04_090000 cleared the "How Fastora
 * began" block's broken image by reverting it all the way to the original
 * stock studio photo, undoing 2026_08_07_170000's intentional swap to a real
 * photo of the team — the actual most recent, correct content for that
 * block. Migrations don't re-run once applied, so a fresh migration is
 * needed to put the team photo back rather than re-running that one.
 *
 * Self-contained like the migration it corrects, rather than assuming the
 * file already made it into storage.
 */
return new class extends Migration
{
    private const HEADING = 'How Fastora began';

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

        $photo = $this->importImage('team-group-photo.jpg', 'The Fastora team');
        $changed = false;

        foreach ($layout as $index => $block) {
            if (($block['type'] ?? null) !== 'content') {
                continue;
            }

            $richText = $block['data']['richText'] ?? '';

            if (! str_starts_with($richText, '<h2>' . self::HEADING . '</h2>')) {
                continue;
            }

            if (($block['data']['image'] ?? null) === $photo->id) {
                continue;
            }

            $layout[$index]['data']['image'] = $photo->id;
            $changed = true;
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
        // Not restorable to the exact prior image — matches the pattern of
        // the other one-way content-tweak migrations in this batch.
    }

    private function importImage(string $filename, string $alt): object
    {
        $source = database_path('seeders/images/' . $filename);
        $path = 'seed/' . $filename;

        if (is_file($source) && ! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, file_get_contents($source));
        }

        $existing = DB::table('media')->where('path', $path)->where('disk', 'public')->first();

        if ($existing !== null) {
            return $existing;
        }

        $dimensions = Storage::disk('public')->exists($path)
            ? @getimagesize(Storage::disk('public')->path($path))
            : false;

        $id = DB::table('media')->insertGetId([
            'disk' => 'public',
            'path' => $path,
            'filename' => $filename,
            'mime_type' => str_ends_with(strtolower($filename), '.png') ? 'image/png' : 'image/jpeg',
            'size' => Storage::disk('public')->exists($path) ? Storage::disk('public')->size($path) : 0,
            'alt' => $alt,
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('media')->where('id', $id)->first();
    }
};
