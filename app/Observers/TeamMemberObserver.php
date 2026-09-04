<?php

namespace App\Observers;

use App\Models\TeamMember;
use App\Support\RevalidatesFrontend;

class TeamMemberObserver
{
    public function saved(TeamMember $member): void
    {
        // /about too: the team grid there links to whichever profiles exist, so
        // publishing or unpublishing someone changes that page as well.
        $paths = ['/about', '/' . $member->slug];

        // A renamed profile leaves its old address cached and still rendering.
        if ($member->wasChanged('slug') && $member->getOriginal('slug')) {
            $paths[] = '/' . $member->getOriginal('slug');
        }

        RevalidatesFrontend::revalidate($paths);
    }

    public function deleted(TeamMember $member): void
    {
        RevalidatesFrontend::revalidate(['/about', '/' . $member->slug]);
    }
}
