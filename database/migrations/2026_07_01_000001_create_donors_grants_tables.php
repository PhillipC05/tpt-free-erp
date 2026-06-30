<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->enum('type', ['individual', 'corporate', 'foundation', 'government'])->default('individual');
            $table->string('email', 200)->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('contact_person', 200)->nullable();
            $table->decimal('total_contributed', 12, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('type');
        });

        Schema::create('grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_id')->nullable()->constrained('donors')->nullOnDelete();
            $table->string('title', 200);
            $table->string('grant_number', 100)->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['draft', 'submitted', 'approved', 'active', 'closed', 'rejected'])->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('purpose')->nullable();
            $table->text('requirements')->nullable();
            $table->decimal('funded_amount', 12, 2)->default(0);
            $table->decimal('spent_amount', 12, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('donor_id');
            $table->index(['status', 'start_date']);
        });

        Schema::create('grant_disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grant_id')->constrained('grants')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('description', 500)->nullable();
            $table->date('disbursement_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('grant_id');
            $table->index('disbursement_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grant_disbursements');
        Schema::dropIfExists('grants');
        Schema::dropIfExists('donors');
    }
};
