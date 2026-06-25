<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ===== POS MODULE =====
        Schema::create('pos_terminals', function (Blueprint $table) {
            $table->id();
            $table->string('terminal_code', 20)->unique();
            $table->string('name', 200);
            $table->foreignId('warehouse_id')->nullable()->constrained('inventory_warehouses');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->foreignId('terminal_id')->constrained('pos_terminals');
            $table->foreignId('customer_id')->nullable()->constrained('sales_customers');
            $table->foreignId('employee_id')->nullable()->constrained('hr_employees');
            $table->enum('status', ['open', 'completed', 'voided', 'refunded'])->default('open');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pos_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('pos_transactions')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('inventory_products');
            $table->string('description', 500);
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
        });

        Schema::create('pos_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('pos_transactions');
            $table->enum('method', ['cash', 'card', 'bank_transfer', 'digital_wallet', 'other']);
            $table->decimal('amount', 15, 2);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // Performance indexes
        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->index('status');
            $table->index('terminal_id');
            $table->index('customer_id');
            $table->index('created_by');
            $table->index('completed_at');
        });

        Schema::table('pos_payments', function (Blueprint $table) {
            $table->index('transaction_id');
            $table->index('method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_payments');
        Schema::dropIfExists('pos_transaction_items');
        Schema::dropIfExists('pos_transactions');
        Schema::dropIfExists('pos_terminals');
    }
};
