<?php

namespace App\Http\Controllers\Api\Network;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Network\NetworkPost;
use App\Models\Network\UserConnection;
use App\Models\Network\UserFollow;
use App\Models\Network\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Get IDs of users the auth user follows
        $followingIds = UserFollow::where('follower_id', $userId)
            ->pluck('following_id')
            ->toArray();

        // Include auth user's own posts
        $followingIds[] = $userId;

        // Accepted connections can see each other's posts even if not discoverable
        $connectionIds = UserConnection::where('status', 'accepted')
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)->orWhere('addressee_id', $userId);
            })
            ->get()
            ->flatMap(fn ($c) => [$c->requester_id, $c->addressee_id])
            ->filter(fn ($id) => $id !== $userId)
            ->unique()
            ->values()
            ->toArray();

        // Discoverable user IDs among the people being followed
        $discoverableFollowingIds = UserProfile::whereIn('user_id', $followingIds)
            ->where('is_discoverable', true)
            ->pluck('user_id')
            ->toArray();

        // Visible authors = own posts + connections + discoverable following
        $visibleIds = array_unique(array_merge([$userId], $connectionIds, $discoverableFollowingIds));

        // Intersect with followingIds to keep the feed limited to followed users + own
        $feedUserIds = array_intersect($followingIds, $visibleIds);
        $feedUserIds[] = $userId; // always include self

        $posts = NetworkPost::with([
            'author:id,name',
            'author.profile:user_id,headline',
        ])
            ->whereIn('user_id', array_unique($feedUserIds))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->respond([
            'success' => true,
            'data' => $posts->items(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }
}
