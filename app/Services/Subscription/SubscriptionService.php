<?php

namespace App\Services\Subscription;

use App\Models\Subscription\Plan;
use App\Models\Subscription\Subscription;
use App\Models\Subscription\Invoice;
use App\Models\Subscription\PlanChange;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function createSubscription(array $data, int $userId): Subscription
    {
        return DB::transaction(function () use ($data, $userId) {
            $plan = Plan::findOrFail($data['plan_id']);
            $number = Subscription::generateNumber();

            $subscription = Subscription::create([
                'subscription_number' => $number,
                'customer_id' => $data['customer_id'],
                'plan_id' => $plan->id,
                'status' => $plan->trial_days ? 'trialing' : 'active',
                'trial_ends_at' => $plan->trial_days ? now()->addDays($plan->trial_days) : null,
                'current_period_start' => now()->toDateString(),
                'current_period_end' => $this->calculatePeriodEnd(now(), $plan->billing_interval),
                'billing_anchor_day' => now()->day,
                'quantity' => $data['quantity'] ?? 1,
                'discount_percent' => $data['discount_percent'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            PlanChange::create([
                'subscription_id' => $subscription->id,
                'from_plan_id' => null,
                'to_plan_id' => $plan->id,
                'change_type' => 'initial',
                'effective_date' => now()->toDateString(),
                'created_by' => $userId,
            ]);

            return $subscription;
        });
    }

    public function changePlan(Subscription $subscription, int $newPlanId, string $reason, int $userId): Subscription
    {
        return DB::transaction(function () use ($subscription, $newPlanId, $reason, $userId) {
            $newPlan = Plan::findOrFail($newPlanId);
            $oldPlan = $subscription->plan;

            $priceDiff = $newPlan->price - $oldPlan->price;
            $daysRemaining = now()->diffInDays($subscription->current_period_end);
            $daysInPeriod = $oldPlan->billing_interval === 'monthly' ? 30
                : ($oldPlan->billing_interval === 'quarterly' ? 90 : 365);
            $proration = $daysInPeriod > 0
                ? round(($priceDiff / $daysInPeriod) * $daysRemaining * $subscription->quantity, 2)
                : 0;

            $changeType = $newPlan->price > $oldPlan->price ? 'upgrade' : 'downgrade';

            $subscription->update([
                'plan_id' => $newPlan->id,
                'discount_percent' => 0,
            ]);

            PlanChange::create([
                'subscription_id' => $subscription->id,
                'from_plan_id' => $oldPlan->id,
                'to_plan_id' => $newPlan->id,
                'change_type' => $changeType,
                'effective_date' => now()->toDateString(),
                'proration_amount' => $proration,
                'reason' => $reason,
                'created_by' => $userId,
            ]);

            return $subscription->fresh(['plan', 'customer']);
        });
    }

    public function cancelSubscription(Subscription $subscription, string $reason): Subscription
    {
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $subscription->fresh();
    }

    public function generateInvoice(Subscription $subscription): Invoice
    {
        $plan = $subscription->plan;
        $amount = $plan->price * $subscription->quantity;
        $discount = $amount * ($subscription->discount_percent / 100);
        $total = $amount - $discount;

        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateNumber(),
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'discount_amount' => $discount,
            'tax_amount' => 0,
            'total_amount' => $total,
            'status' => 'sent',
            'period_start' => $subscription->current_period_start,
            'period_end' => $subscription->current_period_end,
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $subscription->update([
            'current_period_start' => $subscription->current_period_end,
            'current_period_end' => $this->calculatePeriodEnd(
                $subscription->current_period_end,
                $plan->billing_interval
            ),
        ]);

        return $invoice;
    }

    private function calculatePeriodEnd($startDate, string $interval): string
    {
        $date = \Carbon\Carbon::parse($startDate);

        return match ($interval) {
            'monthly' => $date->copy()->addMonthNoOverflow()->toDateString(),
            'quarterly' => $date->copy()->addMonthsNoOverflow(3)->toDateString(),
            'annually' => $date->copy()->addYearsNoOverflow(1)->toDateString(),
            default => $date->copy()->addMonthNoOverflow()->toDateString(),
        };
    }
}
