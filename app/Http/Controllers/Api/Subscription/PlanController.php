<?php

namespace App\Http\Controllers\Api\Subscription;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Subscription\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends BaseApiController
{
    protected string $cacheTag = 'subscription_plans';

    protected array $validationRules = [
        'code' => 'required|string|max:50|unique:subscription_plans,code',
        'name' => 'required|string|max:200',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'currency' => 'nullable|string|max:3',
        'billing_interval' => 'required|in:monthly,quarterly,annually',
        'trial_days' => 'nullable|integer|min:0',
        'max_users' => 'nullable|integer|min:1',
        'included_usage' => 'nullable|numeric|min:0',
        'usage_overage_rate' => 'nullable|numeric|min:0',
        'features' => 'nullable|array',
        'is_active' => 'nullable|boolean',
        'sort_order' => 'nullable|integer|min:0',
    ];

    protected array $validationMessages = [
        'code.required' => 'Plan code is required.',
        'code.unique' => 'This plan code already exists.',
        'name.required' => 'Plan name is required.',
        'price.required' => 'Plan price is required.',
        'billing_interval.required' => 'Billing interval is required.',
    ];

    public function __construct()
    {
        parent::__construct(new Plan());
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all());
        if ($error) return $error;

        $data = $request->all();
        $data['is_active'] = $data['is_active'] ?? true;

        $plan = Plan::create($data);
        return $this->respondCreated($plan, 'Plan created');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $plan = Plan::find($id);
        if (!$plan) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'code' => 'required|string|max:50|unique:subscription_plans,code,' . $id,
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'billing_interval' => 'required|in:monthly,quarterly,annually',
            'trial_days' => 'nullable|integer|min:0',
            'max_users' => 'nullable|integer|min:1',
            'included_usage' => 'nullable|numeric|min:0',
            'usage_overage_rate' => 'nullable|numeric|min:0',
            'features' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        if ($error) return $error;

        $plan->update($request->all());
        return $this->respondSuccess('Plan updated', $plan->fresh());
    }

    public function index(Request $request): JsonResponse
    {
        $query = Plan::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $items = $query->orderBy('sort_order')->orderBy('price')->get();

        return $this->respond([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $plan = Plan::withCount('subscriptions')->find($id);
        if (!$plan) return $this->respondNotFound();

        return $this->respond(['success' => true, 'data' => $plan]);
    }

    public function destroy(int $id): JsonResponse
    {
        $plan = Plan::find($id);
        if (!$plan) return $this->respondNotFound();

        if ($plan->subscriptions()->where('status', 'active')->count() > 0) {
            return $this->respondError('Cannot delete plan with active subscriptions', 422);
        }

        $plan->delete();
        return $this->respondSuccess('Plan deleted');
    }
}
