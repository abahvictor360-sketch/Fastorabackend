<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Points Emmanuel's card in the About page team grid at /genesis.
 *
 * The grid works out where each card links by matching its name against the
 * Team profiles, which is enough for everyone whose page carries the same name
 * they are introduced by. Emmanuel is introduced as Emmanuel Akaluese and his
 * page is /genesis, so there is nothing for the match to find and his card was
 * the only one without a "View profile" link.
 *
 * The block now takes an explicit profile link per member for exactly this
 * case; this fills it in so nobody has to open the page editor to get the link
 * back.
 */
return new class extends Migration
{
    private const PATH = '/genesis';

    public function up(): void
    {
        $this->eachTeamMember(function (array $member): array {
            if ($this->isEmmanuel($member) && blank($member['profilePath'] ?? null)) {
                $member['profilePath'] = self::PATH;
            }

            return $member;
        });
    }

    public function down(): void
    {
        $this->eachTeamMember(function (array $member): array {
            if (($member['profilePath'] ?? null) === self::PATH) {
                unset($member['profilePath']);
            }

            return $member;
        });
    }

    /** Matched on the first name alone, so a later edit to the surname still finds him. */
    private function isEmmanuel(array $member): bool
    {
        return str_contains(mb_strtolower($member['name'] ?? ''), 'emmanuel');
    }

    /** Rewrites every team-block member on every page through the callback. */
    private function eachTeamMember(callable $callback): void
    {
        foreach (DB::table('pages')->select('id', 'layout')->get() as $page) {
            $layout = json_decode($page->layout ?? '[]', true);

            if (! is_array($layout)) {
                continue;
            }

            $changed = false;

            foreach ($layout as $blockIndex => $block) {
                if (($block['type'] ?? null) !== 'team') {
                    continue;
                }

                foreach ($block['data']['members'] ?? [] as $memberIndex => $member) {
                    $updated = $callback($member);

                    if ($updated !== $member) {
                        $layout[$blockIndex]['data']['members'][$memberIndex] = $updated;
                        $changed = true;
                    }
                }
            }

            if ($changed) {
                DB::table('pages')->where('id', $page->id)->update([
                    'layout' => json_encode($layout),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
