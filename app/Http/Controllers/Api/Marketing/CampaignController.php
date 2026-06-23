<?php

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\CampaignAnalytic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends BaseApiController
{
    protected string $cacheTag = 'marketing_campaigns';

    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $query = Campaign::query();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        $perPage = (int) $request->query('per_page', 15);
        $campaigns = $query->orderBy('created_at', 'desc')->paginate(min($perPage, 100));

        return $this->respond([
            'success' => true,
            'data' => $campaigns->items(),
            'meta' => [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:marketing_campaigns,code',
            'type' => 'required|string|in:email,social,event,paid_ads,content',
            'status' => 'nullable|string|in:draft,active,paused,completed,cancelled',
            'budget' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($error) {
            return $error;
        }

        $campaign = Campaign::create(array_merge($request->all(), [
            'created_by' => $request->user()->id,
        ]));

        $this->cacheFlush();

        return $this->respondCreated($campaign);
    }

    public function show(int $id): JsonResponse
    {
        $campaign = Campaign::find($id);

        if (!$campaign) {
            return $this->respondNotFound('Campaign not found');
        }

        return $this->respond(['success' => true, 'data' => $campaign]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $campaign = Campaign::find($id);

        if (!$campaign) {
            return $this->respondNotFound('Campaign not found');
        }

        $error = $this->validate($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:100|unique:marketing_campaigns,code,' . $id,
            'type' => 'sometimes|required|string|in:email,social,event,paid_ads,content',
            'status' => 'nullable|string|in:draft,active,paused,completed,cancelled',
            'budget' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($error) {
            return $error;
        }

        $campaign->update($request->all());
        $this->cacheFlush();

        return $this->respondSuccess('Campaign updated successfully', $campaign);
    }

    public function destroy(int $id): JsonResponse
    {
        $campaign = Campaign::find($id);

        if (!$campaign) {
            return $this->respondNotFound('Campaign not found');
        }

        $campaign->delete();
        $this->cacheFlush();

        return $this->respondSuccess('Campaign deleted successfully');
    }

    public function analytics(int $id): JsonResponse
    {
        $campaign = Campaign::find($id);

        if (!$campaign) {
            return $this->respondNotFound('Campaign not found');
        }

        $analytics = $campaign->analytics()->orderBy('date', 'asc')->get();

        $summary = [
            'impressions' => $analytics->sum('impressions'),
            'clicks' => $analytics->sum('clicks'),
            'conversions' => $analytics->sum('conversions'),
            'cost' => (float) $analytics->sum('cost'),
            'revenue' => (float) $analytics->sum('revenue'),
            'ctr' => $analytics->sum('impressions') > 0
                ? round($analytics->sum('clicks') / $analytics->sum('impressions') * 100, 2)
                : 0,
            'conversion_rate' => $analytics->sum('clicks') > 0
                ? round($analytics->sum('conversions') / $analytics->sum('clicks') * 100, 2)
                : 0,
            'roi' => $analytics->sum('cost') > 0
                ? round(($analytics->sum('revenue') - $analytics->sum('cost')) / $analytics->sum('cost') * 100, 2)
                : 0,
        ];

        $daily = $analytics->map(fn ($a) => [
            'date' => $a->date,
            'impressions' => $a->impressions,
            'clicks' => $a->clicks,
            'conversions' => $a->conversions,
            'cost' => (float) $a->cost,
            'revenue' => (float) $a->revenue,
        ])->values();

        return $this->respond([
            'success' => true,
            'data' => [
                'campaign' => $campaign,
                'summary' => $summary,
                'daily' => $daily,
            ],
        ]);
    }
}
