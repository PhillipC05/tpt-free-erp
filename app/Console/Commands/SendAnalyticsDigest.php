<?php

namespace App\Console\Commands;

use App\Mail\AnalyticsDigestMail;
use App\Models\Marketing\Lead;
use App\Models\Projects\Project;
use App\Models\Projects\Task;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendAnalyticsDigest extends Command
{
    protected $signature = 'analytics:send-digest';

    protected $description = 'Send weekly analytics digest email to users with the preference enabled';

    public function handle(): int
    {
        $users = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->get();

        $eligibleUsers = $users->filter(function (User $user) {
            $pref = DB::table('notification_preferences')
                ->where('user_id', $user->id)
                ->where('template_code', 'analytics.digest')
                ->first();

            return ! $pref || $pref->email_enabled;
        });

        if ($eligibleUsers->isEmpty()) {
            $this->info('No eligible users for analytics digest.');

            return self::SUCCESS;
        }

        $kpis = $this->gatherKPIs();
        $revenue = $this->gatherRevenue();
        $leads = $this->gatherLeads();
        $projects = $this->gatherProjects();
        $tasks = $this->gatherTasks();

        $periodLabel = now()->subWeek()->format('M j').' – '.now()->format('M j, Y');

        foreach ($eligibleUsers as $user) {
            Mail::to($user->email)->queue(new AnalyticsDigestMail(
                kpis: $kpis,
                revenue: $revenue,
                leads: $leads,
                projects: $projects,
                tasks: $tasks,
                periodLabel: $periodLabel,
            ));

            $this->info("Queued digest for {$user->email}");
        }

        $this->info("Analytics digest sent to {$eligibleUsers->count()} user(s).");

        return self::SUCCESS;
    }

    private function gatherKPIs(): array
    {
        $revenue = DB::table('sales_orders')->where('status', 'delivered')
            ->where('created_at', '>=', now()->subWeek())->sum('total_amount');
        $orders = DB::table('sales_orders')
            ->where('created_at', '>=', now()->subWeek())->count();
        $activeSubscriptions = DB::table('subscriptions')->where('status', 'active')->count();
        $mrr = DB::table('subscriptions')
            ->join('subscription_plans', 'subscriptions.plan_id', '=', 'subscription_plans.id')
            ->where('subscriptions.status', 'active')
            ->selectRaw('COALESCE(sum(subscription_plans.price * subscriptions.quantity * (1 - subscriptions.discount_percent / 100)), 0) as mrr')
            ->value('mrr') ?? 0;

        return [
            'orders' => $orders,
            'active_subscriptions' => $activeSubscriptions,
            'mrr' => round((float) $mrr, 2),
        ];
    }

    private function gatherRevenue(): array
    {
        $current = DB::table('sales_orders')->where('status', 'delivered')
            ->where('created_at', '>=', now()->subWeek())->sum('total_amount');
        $previous = DB::table('sales_orders')->where('status', 'delivered')
            ->where('created_at', '>=', now()->subWeeks(2))
            ->where('created_at', '<', now()->subWeek())->sum('total_amount');
        $trend = $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : 0;

        $newCustomers = DB::table('sales_customers')
            ->where('created_at', '>=', now()->subWeek())->count();
        $pendingOrders = DB::table('sales_orders')
            ->whereNotIn('status', ['delivered', 'cancelled'])->count();

        return [
            'current' => round((float) $current, 2),
            'trend' => $trend,
            'new_customers' => $newCustomers,
            'pending_orders' => $pendingOrders,
        ];
    }

    private function gatherLeads(): array
    {
        return [
            'total' => Lead::count(),
            'new' => Lead::where('created_at', '>=', now()->subWeek())->count(),
            'converted' => Lead::whereNotNull('converted_at')
                ->where('converted_at', '>=', now()->subWeek())->count(),
        ];
    }

    private function gatherProjects(): array
    {
        return [
            'active' => Project::where('status', 'active')->count(),
            'completed' => Project::where('status', 'completed')
                ->where('updated_at', '>=', now()->subWeek())->count(),
        ];
    }

    private function gatherTasks(): array
    {
        return [
            'pending' => Task::whereIn('status', ['todo', 'in_progress', 'review'])->count(),
            'overdue' => Task::whereIn('status', ['todo', 'in_progress'])
                ->where('due_date', '<', now()->toDateString())->count(),
        ];
    }
}
