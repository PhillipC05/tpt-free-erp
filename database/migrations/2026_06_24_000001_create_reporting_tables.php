<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('report_type', 100);
            $table->json('parameters')->nullable();
            $table->string('format', 10)->default('json'); // json|csv|pdf
            $table->string('status', 20)->default('queued'); // queued|running|completed|failed
            $table->longText('result_data')->nullable();     // JSON result for small reports
            $table->string('result_path', 500)->nullable();  // file path for large/PDF reports
            $table->text('error_message')->nullable();
            $table->integer('row_count')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });

        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('report_type', 100);
            $table->json('parameters')->nullable();
            $table->string('format', 10)->default('json');
            $table->string('frequency', 20)->default('daily'); // hourly|daily|weekly|monthly
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->string('delivery_email', 200)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('generated_reports');
    }
};
