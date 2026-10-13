<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Journal note the frontend prints under every article — "This article is
 * part of Fastora's Journal on branding, communication and the way people make
 * decisions" — with a switch for the pieces it does not belong on.
 *
 * On by default, because the Journal is what Insights is: an author writing a
 * new piece should get the note without remembering anything, and turn it off
 * for the occasional article that is not part of the Journal. That matches how
 * the frontend already reads the field — it prints the note unless the value is
 * explicitly false.
 */
return new class extends Migration
{
    /**
     * The note as it was typed by hand into articles before the frontend
     * started printing it, tags stripped. Matched loosely on whitespace and on
     * either apostrophe, since editors paste from everywhere.
     */
    private const HAND_WRITTEN_NOTE = "/This\s+article\s+is\s+part\s+of\s+Fastora[''’]s\s+Journal\s+on\s+branding,?\s+communication\s+and\s+the\s+way\s+people\s+make\s+decisions\.?/iu";

    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('show_journal_note')->default(true)->after('featured');
        });

        // Articles that already carry the sentence in their body would now show
        // it twice — once as their own paragraph, once as the panel. Turn the
        // panel off for those rather than editing anybody's copy: the author can
        // delete the line and switch it back on whenever they get to it.
        foreach (DB::table('posts')->select('id', 'content')->get() as $post) {
            // Entities are decoded first: rich-text editors write the apostrophe
            // as &#39; or &rsquo; as often as the character itself, and a match
            // on the raw markup misses every one of those.
            $plain = html_entity_decode(strip_tags($post->content ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if (filled($post->content) && preg_match(self::HAND_WRITTEN_NOTE, $plain)) {
                DB::table('posts')->where('id', $post->id)->update(['show_journal_note' => false]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('show_journal_note');
        });
    }
};
