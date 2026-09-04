<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeamMemberResource;
use App\Models\TeamMember;
use Illuminate\Http\Request;

class TeamMemberController extends Controller
{
    public function index(Request $request)
    {
        $query = TeamMember::published()
            ->with(['photo', 'metaImage'])
            ->orderBy('order')
            ->orderBy('name');

        if ($limit = $request->integer('limit')) {
            $query->limit($limit);
        }

        return TeamMemberResource::collection($query->get());
    }

    public function show(string $slug)
    {
        $member = TeamMember::published()
            ->where('slug', $slug)
            ->with(['photo', 'metaImage'])
            ->firstOrFail();

        return new TeamMemberResource($member);
    }
}
