<?php

namespace App\Http\Controllers\Api\Network;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Marketing\Lead;
use App\Models\Network\UserProfile;
use App\Models\Sales\CrmPipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscoveryController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $query = UserProfile::with(['user:id,name,email', 'interests'])
            ->where('is_discoverable', true);

        // Filter by keyword (search headline/bio/company/job_title)
        if ($request->filled('keyword')) {
            $keyword = $request->query('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('headline', 'like', "%{$keyword}%")
                    ->orWhere('bio', 'like', "%{$keyword}%")
                    ->orWhere('company', 'like', "%{$keyword}%")
                    ->orWhere('job_title', 'like', "%{$keyword}%");
            });
        }

        // Filter by industry (via interests)
        if ($request->filled('industry')) {
            $industry = $request->query('industry');
            $query->whereHas('interests', function ($q) use ($industry) {
                $q->where('type', 'industry')->where('value', 'like', "%{$industry}%");
            });
        }

        // Filter by open_to
        if ($request->filled('open_to')) {
            $openTo = $request->query('open_to');
            $query->whereJsonContains('open_to', $openTo);
        }

        $profiles = $query->paginate(20);

        return $this->respond([
            'success' => true,
            'data' => $profiles->items(),
            'meta' => [
                'current_page' => $profiles->currentPage(),
                'last_page' => $profiles->lastPage(),
                'per_page' => $profiles->perPage(),
                'total' => $profiles->total(),
            ],
        ]);
    }

    public function addToCrm(int $profileId): JsonResponse
    {
        $profile = UserProfile::with('user')->find($profileId);

        if (! $profile) {
            return $this->respondNotFound('Profile not found');
        }

        $pipeline = CrmPipeline::create([
            'name' => $profile->user->name ?? 'Unknown',
            'contact_name' => $profile->user->name ?? 'Unknown',
            'contact_email' => $profile->user->email ?? null,
            'company' => $profile->company,
            'stage' => 'lead',
            'status' => 'open',
        ]);

        return $this->respondSuccess('Profile added to CRM pipeline', $pipeline);
    }

    public function addToLead(int $profileId): JsonResponse
    {
        $profile = UserProfile::with('user')->find($profileId);

        if (! $profile) {
            return $this->respondNotFound('Profile not found');
        }

        $nameParts = explode(' ', $profile->user->name ?? '', 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';

        $lead = Lead::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $profile->user->email ?? null,
            'company' => $profile->company,
            'source' => 'network',
            'status' => 'new',
        ]);

        return $this->respondSuccess('Profile added as a lead', $lead);
    }
}
