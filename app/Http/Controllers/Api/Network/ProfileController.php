<?php

namespace App\Http\Controllers\Api\Network;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Network\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends BaseApiController
{
    protected string $cacheTag = 'network_profiles';

    public function __construct()
    {
        parent::__construct();
    }

    public function me(Request $request): JsonResponse
    {
        $profile = UserProfile::with('interests')
            ->where('user_id', $request->user()->id)
            ->first();

        return $this->respond([
            'success' => true,
            'data' => $profile,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $existing = UserProfile::where('user_id', $request->user()->id)->first();
        if ($existing) {
            return $this->respondError('Profile already exists. Use PUT/PATCH to update.', 422);
        }

        $error = $this->validate($request->all(), [
            'headline' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'company' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:500',
            'location' => 'nullable|string|max:255',
        ]);

        if ($error) {
            return $error;
        }

        $profile = UserProfile::create(array_merge($request->only([
            'headline', 'bio', 'company', 'job_title', 'website', 'location',
        ]), [
            'user_id' => $request->user()->id,
        ]));

        $this->cacheFlush();

        return $this->respondCreated($profile);
    }

    public function update(Request $request): JsonResponse
    {
        $profile = UserProfile::where('user_id', $request->user()->id)->first();

        if (!$profile) {
            return $this->respondNotFound('Profile not found');
        }

        $error = $this->validate($request->all(), [
            'headline' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'company' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:500',
            'location' => 'nullable|string|max:255',
            'open_to' => 'nullable|array',
            'avatar_path' => 'nullable|string|max:500',
        ]);

        if ($error) {
            return $error;
        }

        $profile->update($request->only([
            'headline', 'bio', 'company', 'job_title', 'website', 'location', 'open_to', 'avatar_path',
        ]));

        $this->cacheFlush();

        return $this->respondSuccess('Profile updated successfully', $profile);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $profile = UserProfile::with('interests')->find($id);

        if (!$profile) {
            return $this->respondNotFound('Profile not found');
        }

        // Only show if discoverable or own profile
        if (!$profile->is_discoverable && $profile->user_id !== $request->user()->id) {
            return $this->respondNotFound('Profile not found');
        }

        return $this->respond(['success' => true, 'data' => $profile]);
    }

    public function optIn(Request $request): JsonResponse
    {
        $profile = UserProfile::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['is_discoverable' => false]
        );

        $profile->update([
            'is_discoverable' => true,
            'opted_in_at' => now(),
        ]);

        $this->cacheFlush();

        return $this->respondSuccess('You are now discoverable in the network', $profile);
    }

    public function optOut(Request $request): JsonResponse
    {
        $profile = UserProfile::where('user_id', $request->user()->id)->first();

        if (!$profile) {
            return $this->respondNotFound('Profile not found');
        }

        $profile->update([
            'is_discoverable' => false,
            'opted_out_at' => now(),
        ]);

        $this->cacheFlush();

        return $this->respondSuccess('You have opted out of network discovery');
    }
}
