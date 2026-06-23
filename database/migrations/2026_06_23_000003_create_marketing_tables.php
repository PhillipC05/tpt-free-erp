<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type'); // email|social|event|paid_ads|content|referral|sms
            $table->string('status')->default('draft'); // draft|active|paused|completed|archived
            $table->decimal('budget', 15, 2)->nullable();
            $table->decimal('actual_spend', 15, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('target_audience')->nullable();
            $table->json('goals')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'start_date']);
        });

        Schema::create('marketing_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('job_title')->nullable();
            $table->string('source'); // organic|referral|campaign|network|event|manual|import
            $table->string('status')->default('new'); // new|contacted|qualified|nurturing|converted|dead
            $table->unsignedTinyInteger('interest_score')->default(0);
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('converted_to_customer_id')->nullable()->constrained('sales_customers')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'source']);
            $table->index('assigned_to');
        });

        Schema::create('campaign_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('marketing_campaigns')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->decimal('cost', 12, 2)->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['campaign_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_analytics');
        Schema::dropIfExists('marketing_leads');
        Schema::dropIfExists('marketing_campaigns');
    }
};
