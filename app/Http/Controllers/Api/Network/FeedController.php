<?php

namespace App\Http\Controllers\Api\Network;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Network\NetworkPost;
use App\Models\Network\UserFollow;
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

        $posts = NetworkPost::with([
                'author:id,name',
                'author.profile:user_id,headline',
            ])
            ->whereIn('user_id', $followingIds)
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
