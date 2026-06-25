<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->enum('billing_interval', ['monthly', 'quarterly', 'annually'])->default('monthly');
            $table->integer('trial_days')->nullable();
            $table->integer('max_users')->nullable();
            $table->decimal('included_usage', 12, 2)->nullable();
            $table->decimal('usage_overage_rate', 10, 4)->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('sales_customers');
            $table->foreignId('plan_id')->constrained('subscription_plans');
            $table->enum('status', ['trialing', 'active', 'past_due', 'cancelled', 'suspended'])->default('active');
            $table->timestamp('trial_ends_at')->nullable();
            $table->date('current_period_start');
            $table->date('current_period_end');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->integer('billing_anchor_day')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('subscription_id')->constrained('subscriptions');
            $table->decimal('amount', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'void'])->default('draft');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('subscription_usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions');
            $table->string('usage_type', 100);
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 10, 4)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->timestamp('recorded_at');
            $table->date('period_start');
            $table->date('period_end');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_plan_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions');
            $table->foreignId('from_plan_id')->nullable()->constrained('subscription_plans');
            $table->foreignId('to_plan_id')->constrained('subscription_plans');
            $table->enum('change_type', ['upgrade', 'downgrade', 'initial']);
            $table->date('effective_date');
            $table->decimal('proration_amount', 12, 2)->default(0);
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index('customer_id');
            $table->index('status');
            $table->index('current_period_end');
        });

        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->index('subscription_id');
            $table->index('status');
        });

        Schema::table('subscription_usage_records', function (Blueprint $table) {
            $table->index(['subscription_id', 'usage_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plan_changes');
        Schema::dropIfExists('subscription_usage_records');
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
