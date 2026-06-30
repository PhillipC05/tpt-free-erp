<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Finance ───────────────────────────────────────────────────────
        Schema::table('finance_accounts', function (Blueprint $table) {
            $table->index('type');
            $table->index('is_active');
            $table->index('parent_id');
        });

        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->index('account_id');
            $table->index('type');
            $table->index('transaction_date');
            $table->index(['account_id', 'type']);
        });

        Schema::table('finance_journal_entries', function (Blueprint $table) {
            $table->index('status');
            $table->index('entry_date');
        });

        // ── Inventory ─────────────────────────────────────────────────────
        Schema::table('inventory_products', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('category_id');
        });

        Schema::table('inventory_stock_movements', function (Blueprint $table) {
            $table->index('product_id');
            $table->index('warehouse_id');
            $table->index('type');
            $table->index('movement_date');
            $table->index(['product_id', 'warehouse_id']);
        });

        // ── HR ────────────────────────────────────────────────────────────
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->index('department_id');
            $table->index('status');
            $table->index('employment_type');
            $table->index('manager_id');
        });

        Schema::table('hr_leave_requests', function (Blueprint $table) {
            $table->index('employee_id');
            $table->index('status');
            $table->index(['employee_id', 'status']);
            $table->index('start_date');
        });

        Schema::table('hr_attendance', function (Blueprint $table) {
            $table->index('employee_id');
            $table->index('date');
            $table->index(['employee_id', 'date']);
        });

        // ── Sales ─────────────────────────────────────────────────────────
        Schema::table('sales_customers', function (Blueprint $table) {
            $table->index('status');
            $table->index('assigned_to');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->index('customer_id');
            $table->index('status');
            $table->index('order_date');
            $table->index(['customer_id', 'status']);
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->index('customer_id');
            $table->index('order_id');
            $table->index('status');
            $table->index('due_date');
            $table->index(['customer_id', 'status']);
        });

        // ── Procurement ───────────────────────────────────────────────────
        Schema::table('procurement_vendors', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('procurement_purchase_orders', function (Blueprint $table) {
            $table->index('vendor_id');
            $table->index('status');
            $table->index('order_date');
            $table->index(['vendor_id', 'status']);
        });

        // ── Manufacturing ─────────────────────────────────────────────────
        Schema::table('manufacturing_boms', function (Blueprint $table) {
            $table->index('product_id');
            $table->index('is_active');
        });

        Schema::table('manufacturing_work_orders', function (Blueprint $table) {
            $table->index('product_id');
            $table->index('bom_id');
            $table->index('status');
            $table->index('start_date');
            $table->index('assigned_to');
        });

        // ── Projects ──────────────────────────────────────────────────────
        Schema::table('projects', function (Blueprint $table) {
            $table->index('status');
            $table->index('priority');
            $table->index('project_manager_id');
        });

        Schema::table('project_tasks', function (Blueprint $table) {
            $table->index('project_id');
            $table->index('assigned_to');
            $table->index('status');
            $table->index('priority');
            $table->index('parent_id');
            $table->index(['project_id', 'status']);
        });

        Schema::table('project_time_entries', function (Blueprint $table) {
            $table->index('task_id');
            $table->index('employee_id');
            $table->index('date');
        });

        // ── Quality ───────────────────────────────────────────────────────
        Schema::table('quality_checks', function (Blueprint $table) {
            $table->index('product_id');
            $table->index('type');
            $table->index('result');
            $table->index('inspected_by');
        });

        Schema::table('quality_non_conformances', function (Blueprint $table) {
            $table->index('check_id');
            $table->index('severity');
            $table->index('status');
            $table->index('assigned_to');
        });

        // ── Assets ────────────────────────────────────────────────────────
        Schema::table('assets', function (Blueprint $table) {
            $table->index('type');
            $table->index('status');
            $table->index('assigned_to');
            $table->index('location_id');
        });

        Schema::table('asset_maintenance', function (Blueprint $table) {
            $table->index('asset_id');
            $table->index('type');
            $table->index('status');
            $table->index('scheduled_date');
        });

        // ── Field Service ─────────────────────────────────────────────────
        Schema::table('field_service_tickets', function (Blueprint $table) {
            $table->index('customer_id');
            $table->index('status');
            $table->index('priority');
            $table->index('assigned_to');
        });

        // ── LMS ───────────────────────────────────────────────────────────
        Schema::table('lms_courses', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('type');
        });

        Schema::table('lms_enrollments', function (Blueprint $table) {
            $table->index('course_id');
            $table->index('employee_id');
            $table->index('status');
            $table->index(['course_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        // Drop indexes by name (Laravel auto-names them as table_column_index)
        Schema::table('finance_accounts', fn (Blueprint $t) => $t->dropIndex(['type', 'is_active', 'parent_id']));
        Schema::table('finance_transactions', fn (Blueprint $t) => $t->dropIndex(['account_id', 'type', 'transaction_date']));
        Schema::table('finance_journal_entries', fn (Blueprint $t) => $t->dropIndex(['status', 'entry_date']));
        Schema::table('inventory_products', fn (Blueprint $t) => $t->dropIndex(['is_active', 'category_id']));
        Schema::table('inventory_stock_movements', fn (Blueprint $t) => $t->dropIndex(['product_id', 'warehouse_id', 'type', 'movement_date']));
        Schema::table('hr_employees', fn (Blueprint $t) => $t->dropIndex(['department_id', 'status', 'employment_type', 'manager_id']));
        Schema::table('hr_leave_requests', fn (Blueprint $t) => $t->dropIndex(['employee_id', 'status', 'start_date']));
        Schema::table('hr_attendance', fn (Blueprint $t) => $t->dropIndex(['employee_id', 'date']));
        Schema::table('sales_customers', fn (Blueprint $t) => $t->dropIndex(['status', 'assigned_to']));
        Schema::table('sales_orders', fn (Blueprint $t) => $t->dropIndex(['customer_id', 'status', 'order_date']));
        Schema::table('sales_invoices', fn (Blueprint $t) => $t->dropIndex(['customer_id', 'order_id', 'status', 'due_date']));
        Schema::table('procurement_vendors', fn (Blueprint $t) => $t->dropIndex(['status']));
        Schema::table('procurement_purchase_orders', fn (Blueprint $t) => $t->dropIndex(['vendor_id', 'status', 'order_date']));
        Schema::table('manufacturing_boms', fn (Blueprint $t) => $t->dropIndex(['product_id', 'is_active']));
        Schema::table('manufacturing_work_orders', fn (Blueprint $t) => $t->dropIndex(['product_id', 'bom_id', 'status', 'start_date', 'assigned_to']));
        Schema::table('projects', fn (Blueprint $t) => $t->dropIndex(['status', 'priority', 'project_manager_id']));
        Schema::table('project_tasks', fn (Blueprint $t) => $t->dropIndex(['project_id', 'assigned_to', 'status', 'priority', 'parent_id']));
        Schema::table('project_time_entries', fn (Blueprint $t) => $t->dropIndex(['task_id', 'employee_id', 'date']));
        Schema::table('quality_checks', fn (Blueprint $t) => $t->dropIndex(['product_id', 'type', 'result', 'inspected_by']));
        Schema::table('quality_non_conformances', fn (Blueprint $t) => $t->dropIndex(['check_id', 'severity', 'status', 'assigned_to']));
        Schema::table('assets', fn (Blueprint $t) => $t->dropIndex(['type', 'status', 'assigned_to', 'location_id']));
        Schema::table('asset_maintenance', fn (Blueprint $t) => $t->dropIndex(['asset_id', 'type', 'status', 'scheduled_date']));
        Schema::table('field_service_tickets', fn (Blueprint $t) => $t->dropIndex(['customer_id', 'status', 'priority', 'assigned_to']));
        Schema::table('lms_courses', fn (Blueprint $t) => $t->dropIndex(['is_active', 'type']));
        Schema::table('lms_enrollments', fn (Blueprint $t) => $t->dropIndex(['course_id', 'employee_id', 'status']));
    }
};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('erp_tables', function (Blueprint $table) {
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_tables', function (Blueprint $table) {
            //
        });
    }
};
