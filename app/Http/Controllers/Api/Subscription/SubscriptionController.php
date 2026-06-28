<?php

namespace App\Http\Controllers\Api\Subscription;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Subscription\Subscription;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends BaseApiController
{
    protected string $cacheTag = 'subscriptions';

    public function __construct()
    {
        parent::__construct(new Subscription);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'customer_id' => 'required|exists:sales_customers,id',
            'plan_id' => 'required|exists:subscription_plans,id',
            'quantity' => 'nullable|integer|min:1',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);
        if ($error) {
            return $error;
        }

        $service = new SubscriptionService;
        $subscription = $service->createSubscription($request->all(), Auth::id());

        return $this->respondCreated($subscription->fresh(['plan', 'customer']), 'Subscription created');
    }

    public function changePlan(Request $request, int $id): JsonResponse
    {
        $subscription = Subscription::find($id);
        if (! $subscription) {
            return $this->respondNotFound();
        }

        if (! $subscription->isActive()) {
            return $this->respondError('Only active subscriptions can change plans', 422);
        }

        $error = $this->validate($request->all(), [
            'plan_id' => 'required|exists:subscription_plans,id|different:'.$subscription->plan_id,
            'reason' => 'required|string|max:500',
        ]);
        if ($error) {
            return $error;
        }

        $service = new SubscriptionService;
        $subscription = $service->changePlan(
            $subscription,
            $request->input('plan_id'),
            $request->input('reason'),
            Auth::id()
        );

        return $this->respondSuccess('Plan changed', $subscription);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $subscription = Subscription::find($id);
        if (! $subscription) {
            return $this->respondNotFound();
        }

        if (! $subscription->isActive()) {
            return $this->respondError('Subscription is not active', 422);
        }

        $error = $this->validate($request->all(), [
            'reason' => 'required|string|max:500',
        ]);
        if ($error) {
            return $error;
        }

        $service = new SubscriptionService;
        $subscription = $service->cancelSubscription($subscription, $request->input('reason'));

        return $this->respondSuccess('Subscription cancelled', $subscription->fresh());
    }

    public function reactivate(int $id): JsonResponse
    {
        $subscription = Subscription::find($id);
        if (! $subscription) {
            return $this->respondNotFound();
        }

        if ($subscription->status !== 'cancelled') {
            return $this->respondError('Only cancelled subscriptions can be reactivated', 422);
        }

        $subscription->update([
            'status' => 'active',
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'current_period_start' => now()->toDateString(),
        ]);

        return $this->respondSuccess('Subscription reactivated', $subscription->fresh(['plan']));
    }

    public function index(Request $request): JsonResponse
    {
        $query = Subscription::query()->with(['plan', 'customer']);

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->query('customer_id'));
        }

        if ($request->has('plan_id')) {
            $query->where('plan_id', $request->query('plan_id'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('subscription_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $perPage = $request->query('per_page', 15);
        $items = $query->orderBy('created_at', 'desc')->paginate(min($perPage, 100));

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

    public function show(int $id): JsonResponse
    {
        $subscription = Subscription::with(['plan', 'customer', 'invoices', 'usageRecords', 'planChanges.fromPlan', 'planChanges.toPlan'])->find($id);
        if (! $subscription) {
            return $this->respondNotFound();
        }

        return $this->respond(['success' => true, 'data' => $subscription]);
    }

    public function usage(Request $request, int $id): JsonResponse
    {
        $subscription = Subscription::find($id);
        if (! $subscription) {
            return $this->respondNotFound();
        }

        $usageType = $request->query('usage_type');
        $query = $subscription->usageRecords()->orderByDesc('recorded_at');

        if ($usageType) {
            $query->where('usage_type', $usageType);
        }

        $items = $query->paginate(min($request->query('per_page', 15), 100));

        $totalQuantity = $subscription->usageRecords()->sum('quantity');
        $totalCost = $subscription->usageRecords()->sum('total_cost');
        $currentPeriodUsage = $subscription->currentUsage($usageType ?? 'api_calls');

        return $this->respond([
            'success' => true,
            'data' => [
                'records' => $items->items(),
                'summary' => [
                    'total_quantity' => $totalQuantity,
                    'total_cost' => $totalCost,
                    'current_period_usage' => $currentPeriodUsage,
                ],
            ],
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function dashboard(): JsonResponse
    {
        $activeCount = Subscription::where('status', 'active')->count();
        $trialingCount = Subscription::where('status', 'trialing')->count();
        $cancelledCount = Subscription::where('status', 'cancelled')
            ->where('cancelled_at', '>=', now()->subMonths(3))
            ->count();
        $mrr = Subscription::where('status', 'active')
            ->join('subscription_plans', 'subscriptions.plan_id', '=', 'subscription_plans.id')
            ->selectRaw('sum(subscription_plans.price * subscriptions.quantity * (1 - subscriptions.discount_percent / 100)) as mrr')
            ->value('mrr') ?? 0;

        $arr = Subscription::where('status', 'cancelled')
            ->where('cancelled_at', '>=', now()->subMonths(12))
            ->count();
        $totalActive = Subscription::where('status', 'active')->count();
        $churnRate = $totalActive > 0 ? round(($arr / ($totalActive + $arr)) * 100, 1) : 0;

        $planDistribution = Subscription::where('status', 'active')
            ->select('plan_id', \DB::raw('count(*) as count'))
            ->groupBy('plan_id')
            ->with('plan:id,name,price')
            ->get();

        $recentChanges = Subscription::with(['customer', 'plan', 'planChanges.fromPlan', 'planChanges.toPlan'])
            ->has('planChanges')
            ->with('planChanges')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        $expiringTrial = Subscription::where('status', 'trialing')
            ->where('trial_ends_at', '<=', now()->addDays(7))
            ->where('trial_ends_at', '>=', now())
            ->with(['customer', 'plan'])
            ->get();

        return $this->respond([
            'success' => true,
            'data' => [
                'mrr' => round($mrr, 2),
                'active_count' => $activeCount,
                'trialing_count' => $trialingCount,
                'cancelled_recent' => $cancelledCount,
                'churn_rate_percent' => $churnRate,
                'plan_distribution' => $planDistribution,
                'recent_changes' => $recentChanges,
                'expiring_trials' => $expiringTrial,
            ],
        ]);
    }
}
