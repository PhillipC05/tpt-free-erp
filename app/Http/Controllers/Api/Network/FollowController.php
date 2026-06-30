<?php

namespace App\Http\Controllers\Api\Network;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Network\UserFollow;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FollowController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function following(Request $request): JsonResponse
    {
        $following = UserFollow::with('following:id,name,email')
            ->where('follower_id', $request->user()->id)
            ->paginate(20);

        return $this->respond([
            'success' => true,
            'data' => $following->items(),
            'meta' => [
                'current_page' => $following->currentPage(),
                'last_page' => $following->lastPage(),
                'per_page' => $following->perPage(),
                'total' => $following->total(),
            ],
        ]);
    }

    public function followers(Request $request): JsonResponse
    {
        $followers = UserFollow::with('follower:id,name,email')
            ->where('following_id', $request->user()->id)
            ->paginate(20);

        return $this->respond([
            'success' => true,
            'data' => $followers->items(),
            'meta' => [
                'current_page' => $followers->currentPage(),
                'last_page' => $followers->lastPage(),
                'per_page' => $followers->perPage(),
                'total' => $followers->total(),
            ],
        ]);
    }

    public function follow(Request $request, int $userId): JsonResponse
    {
        if ($userId === $request->user()->id) {
            return $this->respondError('You cannot follow yourself', 422);
        }

        $existing = UserFollow::where('follower_id', $request->user()->id)
            ->where('following_id', $userId)
            ->first();

        if ($existing) {
            return $this->respondError('You are already following this user', 422);
        }

        $follow = UserFollow::create([
            'follower_id' => $request->user()->id,
            'following_id' => $userId,
        ]);

        // Notify the followed user
        DB::table('notifications')->insert([
            'user_id' => $userId,
            'title' => 'New Follower',
            'message' => $request->user()->name.' started following you',
            'type' => 'new_follower',
            'data' => json_encode([
                'type' => 'new_follower',
                'follower_id' => $request->user()->id,
                'follower_name' => $request->user()->name,
            ]),
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->respondSuccess('User followed successfully', $follow);
    }

    public function unfollow(Request $request, int $userId): JsonResponse
    {
        $follow = UserFollow::where('follower_id', $request->user()->id)
            ->where('following_id', $userId)
            ->first();

        if (! $follow) {
            return $this->respondNotFound('Follow relationship not found');
        }

        $follow->delete();

        return $this->respondSuccess('User unfollowed successfully');
    }
}
