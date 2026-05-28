<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 200);
            $table->decimal('rate', 7, 4);
            $table->enum('type', ['percentage', 'fixed'])->default('percentage');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 200);
            $table->foreignId('account_id')->nullable()->constrained('finance_accounts');
            $table->foreignId('department_id')->nullable()->constrained('hr_departments');
            $table->year('fiscal_year');
            $table->enum('period', ['monthly', 'quarterly', 'annual'])->default('annual');
            $table->tinyInteger('period_number')->nullable();
            $table->decimal('budgeted_amount', 15, 2)->default(0);
            $table->decimal('actual_amount', 15, 2)->default(0);
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 200);
            $table->string('contact_person', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->decimal('minimum_order_value', 15, 2)->nullable();
            $table->enum('status', ['active', 'inactive', 'blacklisted'])->default('active');
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hr_payroll', function (Blueprint $table) {
            $table->id();
            $table->string('payroll_number', 50)->unique();
            $table->foreignId('employee_id')->constrained('hr_employees');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('overtime', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->date('payment_date')->nullable();
            $table->enum('payment_method', ['bank_transfer', 'cash', 'cheque'])->default('bank_transfer');
            $table->enum('status', ['draft', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_crm_pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 200);
            $table->foreignId('customer_id')->nullable()->constrained('sales_customers');
            $table->string('contact_name', 200)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->enum('stage', ['lead', 'prospect', 'proposal', 'negotiation', 'won', 'lost'])->default('lead');
            $table->decimal('value', 15, 2)->nullable();
            $table->integer('probability')->default(0);
            $table->date('expected_close_date')->nullable();
            $table->date('actual_close_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->enum('status', ['active', 'closed_won', 'closed_lost'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('procurement_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('requisition_number', 50)->unique();
            $table->foreignId('requested_by')->constrained('hr_employees');
            $table->foreignId('department_id')->nullable()->constrained('hr_departments');
            $table->date('required_date');
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'ordered'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('hr_employees');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('procurement_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('procurement_requisitions')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('inventory_products');
            $table->string('description', 500);
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 20)->default('pcs');
            $table->decimal('estimated_unit_price', 15, 2)->nullable();
            $table->decimal('estimated_total', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('manufacturing_production_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('schedule_number', 50)->unique();
            $table->foreignId('work_order_id')->constrained('manufacturing_work_orders');
            $table->string('resource_name', 200)->nullable();
            $table->timestamp('planned_start');
            $table->timestamp('planned_end');
            $table->timestamp('actual_start')->nullable();
            $table->timestamp('actual_end')->nullable();
            $table->decimal('planned_quantity', 12, 2)->nullable();
            $table->decimal('actual_quantity', 12, 2)->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_production_schedules');
        Schema::dropIfExists('procurement_requisition_items');
        Schema::dropIfExists('procurement_requisitions');
        Schema::dropIfExists('sales_crm_pipelines');
        Schema::dropIfExists('hr_payroll');
        Schema::dropIfExists('inventory_suppliers');
        Schema::dropIfExists('finance_budgets');
        Schema::dropIfExists('finance_tax_rates');
    }
};
