<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rewrites the search snippets for the main pages.
 *
 * Google was ignoring the stored descriptions on a brand search and composing
 * its own from page text, which pulled in footer fragments like the phone
 * number and left the result reading as a broken sentence. The likeliest cause
 * is length and vagueness: the home page description was 69 characters against
 * the roughly 155 a result can show, and said what the company believes rather
 * than what it does, so almost any paragraph on the page looked more
 * informative than the tag.
 *
 * These are written to fill the available width, lead with the concrete offer
 * rather than a slogan, and carry the words someone would actually search for.
 * A description is not a ranking signal, so this is aimed at the click, not the
 * position, and Google may still choose to write its own.
 *
 * Services and Case Studies also get titles. They had none, so both fell back
 * to a bare "Services | Fastora" in the code. Unlike descriptions, the title is
 * a ranking signal, which makes those two the most useful change here.
 *
 * Guarded per field: each is rewritten only where it is still empty or still
 * holds exactly the value being replaced, so anything an editor has since
 * written by hand survives.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->copy() as $slug => $fields) {
            $page = DB::table('pages')->where('slug', $slug)->first();

            if ($page === null) {
                continue;
            }

            $update = [];

            foreach ($fields as $column => [$expected, $replacement]) {
                $current = $page->{$column};

                if ($current === null || $current === '' || $current === $expected) {
                    $update[$column] = $replacement;
                }
            }

            if ($update === []) {
                continue;
            }

            DB::table('pages')->where('id', $page->id)->update($update + ['updated_at' => now()]);
        }
    }

    /**
     * slug => column => [value being replaced, new value].
     *
     * @return array<string, array<string, array{0: ?string, 1: string}>>
     */
    private function copy(): array
    {
        return [
            'home' => [
                'meta_description' => [
                    'Helping businesses become easier to understand, remember, and choose.',
                    'Fastora is a communications and digital strategy company helping businesses, founders, and organisations across Africa be understood, remembered, and chosen.',
                ],
            ],

            'about' => [
                'meta_description' => [
                    'Fastora is a communications and digital strategy company helping businesses communicate with clarity, credibility, and confidence.',
                    'The people behind Fastora, an African communications and digital strategy company, and how we help businesses communicate with clarity and credibility.',
                ],
            ],

            'services' => [
                'meta_title' => [
                    null,
                    'Communications & Digital Strategy Services',
                ],
                'meta_description' => [
                    null,
                    'Communications strategy, brand positioning, content and storytelling, and digital marketing, built around how people actually experience your business.',
                ],
            ],

            'case-studies' => [
                'meta_title' => [
                    null,
                    'Case Studies & Client Results',
                ],
                'meta_description' => [
                    null,
                    'Real client work across media, energy, real estate, and beauty, including a media platform grown to 60,000 followers and 718,000 monthly reach.',
                ],
            ],

            'insights' => [
                'meta_description' => [
                    'Practical thinking on communications, branding, and digital strategy from the Fastora team.',
                    'Practical thinking on communications, branding, and digital strategy, drawn from our work with businesses across Africa and beyond.',
                ],
            ],

            'contact' => [
                'meta_description' => [
                    "Tell us about your business and where you'd like to go. We respond to every enquiry within one business day.",
                    'Talk to Fastora about communications, branding, or digital strategy. Tell us about your business and we reply to every enquiry within one business day.',
                ],
            ],

            'consultation' => [
                'meta_description' => [
                    'A free 45-minute strategic session on your business, your audience, and the communication problem in front of you.',
                    'Book a free 45-minute conversation with Fastora about your business, your audience, and the communication problem in front of you.',
                ],
            ],
        ];
    }

    public function down(): void
    {
        // One-way content edit, like the other copy migrations in this project:
        // reinstating the previous text would overwrite whatever is live by then.
    }
};
