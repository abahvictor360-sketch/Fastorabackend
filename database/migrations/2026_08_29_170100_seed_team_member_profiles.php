<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The three people who already have their bio on the About page, given the
 * profile pages that were requested: /kator, /genesis and /ndidiamaka.
 *
 * Copy is lifted from database/data/reference-about-page.php so the two say the
 * same thing on the day this ships; they are separate records afterwards, and
 * editing one no longer changes the other.
 *
 * Emmanuel's page is /genesis because that is what he goes by, while the page
 * itself carries the name clients see in writing.
 */
return new class extends Migration
{
    private const MEMBERS = [
        [
            'slug' => 'kator',
            'name' => 'Kator Tarkaa',
            'role' => 'Founder & Digital Communications Strategist',
            'bio' => "Kator leads Fastora's strategy, helping businesses communicate more effectively through brand positioning, communications, content, and digital strategy. His work focuses on helping businesses present themselves with confidence and build stronger connections with the people they serve.",
            'order' => 1,
        ],
        [
            'slug' => 'genesis',
            'name' => 'Emmanuel Akaluese',
            'role' => 'Operations Associate',
            'bio' => 'Emmanuel helps keep projects moving from idea to delivery. He supports internal operations, coordinates workflows, and ensures client projects stay organised, efficient, and on schedule.',
            'order' => 2,
        ],
        [
            'slug' => 'ndidiamaka',
            'name' => 'Ndidiamaka Eya',
            'role' => 'Digital Communications Associate',
            'bio' => 'Ndidiamaka supports the planning, coordination, and delivery of digital communications across client accounts. She helps ensure content is published consistently and that day-to-day communication reflects the quality and direction of each brand.',
            'order' => 3,
        ],
    ];

    public function up(): void
    {
        foreach (self::MEMBERS as $member) {
            // Skip anyone already added by hand in the admin, so re-running this
            // never overwrites an edit.
            if (DB::table('team_members')->where('slug', $member['slug'])->exists()) {
                continue;
            }

            DB::table('team_members')->insert([
                ...$member,
                'socials' => json_encode([]),
                'status' => 'published',
                'meta_noindex' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('team_members')
            ->whereIn('slug', array_column(self::MEMBERS, 'slug'))
            ->delete();
    }
};
