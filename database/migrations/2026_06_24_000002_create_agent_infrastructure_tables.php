<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('agent_type', 30)->default('local');
            // agent_type: local|openrouter|api|human_subcontractor
            $table->json('provider_config')->nullable(); // model, base_url, max_tokens (no raw keys)
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('agent_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_profile_id')->constrained('agent_profiles')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token_hash', 64);
            $table->string('name', 200);
            $table->json('abilities')->nullable();             // Sanctum-style scopes
            $table->json('allowed_skill_slugs')->nullable();   // restrict to specific skills
            $table->integer('rate_limit_per_minute')->default(60);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique('token_hash');
            $table->index(['agent_profile_id', 'expires_at']);
        });

        Schema::create('agent_skill_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_profile_id')->constrained('agent_profiles')->cascadeOnDelete();
            $table->string('skill_slug', 100);
            $table->boolean('is_enabled')->default(true);
            $table->json('config_overrides')->nullable(); // per-agent model/token overrides
            $table->timestamps();

            $table->unique(['agent_profile_id', 'skill_slug']);
            $table->index('skill_slug');
        });

        Schema::create('agent_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_profile_id')->constrained('agent_profiles')->cascadeOnDelete();
            $table->string('skill_slug', 100);
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('trigger_type', 20)->default('manual');
            // trigger_type: manual|scheduled|webhook|api
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->string('status', 20)->default('queued');
            // status: queued|running|completed|failed
            $table->integer('tokens_used')->nullable();
            $table->string('model_used', 100)->nullable();
            $table->integer('duration_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps(); // immutable audit log — no soft deletes

            $table->index(['agent_profile_id', 'status']);
            $table->index(['skill_slug', 'created_at']);
            $table->index('triggered_by');
        });

        Schema::create('agent_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_profile_id')->constrained('agent_profiles')->cascadeOnDelete();
            $table->string('skill_slug', 100);
            $table->string('cron_expression', 100)->default('0 * * * *'); // hourly default
            $table->json('input_template')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->foreignId('last_execution_id')->nullable()->constrained('agent_executions')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_schedules');
        Schema::dropIfExists('agent_executions');
        Schema::dropIfExists('agent_skill_assignments');
        Schema::dropIfExists('agent_tokens');
        Schema::dropIfExists('agent_profiles');
    }
};
