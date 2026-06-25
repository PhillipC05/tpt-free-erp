<?php

namespace App\Http\Controllers\Api\Subscription;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Subscription\UsageRecord;
use App\Models\Subscription\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsageController extends BaseApiController
{
    protected string $cacheTag = 'subscription_usage';

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'subscription_id' => 'required|exists:subscriptions,id',
            'usage_type' => 'required|string|max:100',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'nullable|numeric|min:0',
            'recorded_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        if ($error) return $error;

        $subscription = Subscription::find($request->input('subscription_id'));
        if (!$subscription->isActive()) {
            return $this->respondError('Cannot record usage for inactive subscription', 422);
        }

        $data = $request->all();
        $data['recorded_at'] = $data['recorded_at'] ?? now();
        $data['period_start'] = $subscription->current_period_start;
        $data['period_end'] = $subscription->current_period_end;
        $data['unit_price'] = $data['unit_price'] ?? 0;
        $data['total_cost'] = $data['quantity'] * $data['unit_price'];

        $record = UsageRecord::create($data);

        return $this->respondCreated($record->fresh(['subscription']), 'Usage recorded');
    }

    public function batch(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'records' => 'required|array|min:1',
            'records.*.subscription_id' => 'required|exists:subscriptions,id',
            'records.*.usage_type' => 'required|string|max:100',
            'records.*.quantity' => 'required|numeric|min:0.01',
            'records.*.unit_price' => 'nullable|numeric|min:0',
            'records.*.recorded_at' => 'nullable|date',
        ]);
        if ($error) return $error;

        $created = DB::transaction(function () use ($request) {
            $results = [];
            foreach ($request->input('records') as $recordData) {
                $subscription = Subscription::find($recordData['subscription_id']);
                if (!$subscription || !$subscription->isActive()) {
                    continue;
                }

                $recordData['recorded_at'] = $recordData['recorded_at'] ?? now();
                $recordData['period_start'] = $subscription->current_period_start;
                $recordData['period_end'] = $subscription->current_period_end;
                $recordData['unit_price'] = $recordData['unit_price'] ?? 0;
                $recordData['total_cost'] = $recordData['quantity'] * $recordData['unit_price'];

                $results[] = UsageRecord::create($recordData);
            }
            return $results;
        });

        return $this->respondCreated($created, count($created) . ' usage records created');
    }

    public function index(Request $request): JsonResponse
    {
        $query = UsageRecord::query()->with(['subscription.plan', 'subscription.customer']);

        if ($request->has('subscription_id')) {
            $query->where('subscription_id', $request->query('subscription_id'));
        }

        if ($request->has('usage_type')) {
            $query->where('usage_type', $request->query('usage_type'));
        }

        if ($request->has('start_date')) {
            $query->where('recorded_at', '>=', $request->query('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('recorded_at', '<=', $request->query('end_date') . ' 23:59:59');
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderByDesc('recorded_at')->paginate(min($perPage, 100));

        return $this->respond([
            'success' => true,
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }
}
