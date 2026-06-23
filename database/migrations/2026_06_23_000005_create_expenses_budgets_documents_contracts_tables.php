<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ===== EXPENSES =====
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->boolean('requires_receipt')->default(false);
            $table->timestamps();
        });

        Schema::create('expense_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('status')->default('draft'); // draft|submitted|approved|rejected|paid
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });

        Schema::create('expense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->string('description');
            $table->date('expense_date');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('NZD');
            $table->string('receipt_path')->nullable();
            $table->timestamps();
        });

        // ===== BUDGETS =====
        Schema::create('finance_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('period_type'); // annual|quarterly|monthly
            $table->integer('year');
            $table->unsignedTinyInteger('period')->nullable(); // 1-4 (quarter) or 1-12 (month)
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->nullOnDelete();
            $table->string('status')->default('draft'); // draft|approved|active|closed
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['year', 'period_type', 'status']);
        });

        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('finance_budgets')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('finance_accounts');
            $table->decimal('budgeted_amount', 15, 2);
            $table->decimal('actual_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['budget_id', 'account_id']);
        });

        // ===== DOCUMENTS =====
        Schema::create('document_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('document_folders')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('original_filename');
            $table->string('storage_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->foreignId('folder_id')->nullable()->constrained('document_folders')->nullOnDelete();
            $table->morphs('documentable'); // polymorphic: link to any entity
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['documentable_type', 'documentable_id']);
        });

        // ===== CONTRACTS =====
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('contract_number')->unique();
            $table->string('type'); // sale|purchase|employment|service|lease|nda|other
            $table->string('status')->default('draft'); // draft|review|signed|active|expired|terminated
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('value', 15, 2)->nullable();
            $table->string('currency', 3)->default('NZD');
            $table->text('description')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('sales_customers')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('procurement_vendors')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'end_date']);
        });

        Schema::create('contract_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->decimal('payment_amount', 12, 2)->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_milestones');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_folders');
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('finance_budgets');
        Schema::dropIfExists('expense_items');
        Schema::dropIfExists('expense_reports');
        Schema::dropIfExists('expense_categories');
    }
};
