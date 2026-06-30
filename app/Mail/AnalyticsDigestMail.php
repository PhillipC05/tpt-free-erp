<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnalyticsDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array $kpis,
        public readonly array $revenue,
        public readonly array $leads,
        public readonly array $projects,
        public readonly array $tasks,
        public readonly string $periodLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Weekly Analytics Digest — {$this->periodLabel}",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
            textString: $this->buildText(),
        );
    }

    private function buildHtml(): string
    {
        $revenueCurrent = number_format((float) ($this->revenue['current'] ?? 0), 2);
        $revenueTrend = (float) ($this->revenue['trend'] ?? 0);
        $trendColor = $revenueTrend >= 0 ? '#16a34a' : '#dc2626';
        $trendArrow = $revenueTrend >= 0 ? '&#9650;' : '&#9660;';
        $orders = $this->kpis['orders'] ?? 0;
        $activeSubscriptions = $this->kpis['active_subscriptions'] ?? 0;
        $mrr = number_format((float) ($this->kpis['mrr'] ?? 0), 2);
        $newCustomers = $this->revenue['new_customers'] ?? 0;
        $pendingOrders = $this->revenue['pending_orders'] ?? 0;
        $totalLeads = $this->leads['total'] ?? 0;
        $newLeads = $this->leads['new'] ?? 0;
        $convertedLeads = $this->leads['converted'] ?? 0;
        $activeProjects = $this->projects['active'] ?? 0;
        $completedProjects = $this->projects['completed'] ?? 0;
        $pendingTasks = $this->tasks['pending'] ?? 0;
        $overdueTasks = $this->tasks['overdue'] ?? 0;
        $appName = config('app.name', 'TPT ERP');

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333;">
    <h2 style="color: #1e40af; border-bottom: 2px solid #1e40af; padding-bottom: 10px;">Weekly Analytics Digest</h2>
    <p style="color: #666;">Period: {$this->periodLabel}</p>

    <h3 style="color: #374151; margin-top: 24px;">Key Performance Indicators</h3>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
        <tr>
            <td style="padding: 8px; border: 1px solid #e5e7eb; background: #f9fafb;">Revenue</td>
            <td style="padding: 8px; border: 1px solid #e5e7eb; font-weight: bold;">\$$revenueCurrent</td>
            <td style="padding: 8px; border: 1px solid #e5e7eb; color: {$trendColor};">{$trendArrow} {$revenueTrend}%</td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #e5e7eb; background: #f9fafb;">Orders</td>
            <td style="padding: 8px; border: 1px solid #e5e7eb; font-weight: bold;">{$orders}</td>
            <td style="padding: 8px; border: 1px solid #e5e7eb;"></td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #e5e7eb; background: #f9fafb;">Active Subscriptions</td>
            <td style="padding: 8px; border: 1px solid #e5e7eb; font-weight: bold;">{$activeSubscriptions}</td>
            <td style="padding: 8px; border: 1px solid #e5e7eb;"></td>
        </tr>
        <tr>
            <td style="padding: 8px; border: 1px solid #e5e7eb; background: #f9fafb;">MRR</td>
            <td style="padding: 8px; border: 1px solid #e5e7eb; font-weight: bold;">\$$mrr</td>
            <td style="padding: 8px; border: 1px solid #e5e7eb;"></td>
        </tr>
    </table>

    <h3 style="color: #374151; margin-top: 24px;">Sales &amp; Revenue</h3>
    <ul style="line-height: 1.8;">
        <li>New Customers: <strong>{$newCustomers}</strong></li>
        <li>Pending Orders: <strong>{$pendingOrders}</strong></li>
    </ul>

    <h3 style="color: #374151; margin-top: 24px;">Marketing &amp; Leads</h3>
    <ul style="line-height: 1.8;">
        <li>Total Leads: <strong>{$totalLeads}</strong></li>
        <li>New Leads (this week): <strong>{$newLeads}</strong></li>
        <li>Converted Leads: <strong>{$convertedLeads}</strong></li>
    </ul>

    <h3 style="color: #374151; margin-top: 24px;">Projects &amp; Tasks</h3>
    <ul style="line-height: 1.8;">
        <li>Active Projects: <strong>{$activeProjects}</strong></li>
        <li>Completed Projects: <strong>{$completedProjects}</strong></li>
        <li>Pending Tasks: <strong>{$pendingTasks}</strong></li>
        <li>Overdue Tasks: <strong>{$overdueTasks}</strong></li>
    </ul>

    <hr style="border: none; border-top: 1px solid #eee; margin: 24px 0;">
    <p style="color: #999; font-size: 12px;">Sent from {$appName}</p>
</body>
</html>
HTML;
    }

    private function buildText(): string
    {
        $revenueCurrent = number_format((float) ($this->revenue['current'] ?? 0), 2);
        $revenueTrend = (float) ($this->revenue['trend'] ?? 0);
        $trendArrow = $revenueTrend >= 0 ? '↑' : '↓';
        $orders = $this->kpis['orders'] ?? 0;
        $activeSubscriptions = $this->kpis['active_subscriptions'] ?? 0;
        $mrr = number_format((float) ($this->kpis['mrr'] ?? 0), 2);
        $newCustomers = $this->revenue['new_customers'] ?? 0;
        $pendingOrders = $this->revenue['pending_orders'] ?? 0;
        $totalLeads = $this->leads['total'] ?? 0;
        $newLeads = $this->leads['new'] ?? 0;
        $convertedLeads = $this->leads['converted'] ?? 0;
        $activeProjects = $this->projects['active'] ?? 0;
        $completedProjects = $this->projects['completed'] ?? 0;
        $pendingTasks = $this->tasks['pending'] ?? 0;
        $overdueTasks = $this->tasks['overdue'] ?? 0;
        $appName = config('app.name', 'TPT ERP');

        return <<<TEXT
Weekly Analytics Digest — {$this->periodLabel}

KEY PERFORMANCE INDICATORS
  Revenue: \$$revenueCurrent ({$trendArrow} {$revenueTrend}%)
  Orders: {$orders}
  Active Subscriptions: {$activeSubscriptions}
  MRR: \$$mrr

SALES & REVENUE
  New Customers: {$newCustomers}
  Pending Orders: {$pendingOrders}

MARKETING & LEADS
  Total Leads: {$totalLeads}
  New Leads: {$newLeads}
  Converted Leads: {$convertedLeads}

PROJECTS & TASKS
  Active Projects: {$activeProjects}
  Completed Projects: {$completedProjects}
  Pending Tasks: {$pendingTasks}
  Overdue Tasks: {$overdueTasks}

---
Sent from {$appName}
TEXT;
    }
}
