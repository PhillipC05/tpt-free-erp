<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('agent_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('agent_teams')->cascadeOnDelete();
            $table->foreignId('agent_profile_id')->constrained('agent_profiles')->cascadeOnDelete();
            $table->unsignedInteger('execution_order')->default(0);
            $table->string('skill_slug', 100);
            $table->json('input_mapping')->nullable();
            $table->unique(['team_id', 'agent_profile_id']);
            $table->timestamps();
        });

        Schema::create('agent_team_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('agent_teams')->cascadeOnDelete();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('queued');
            $table->json('output')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_team_step_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_execution_id')->constrained('agent_team_executions')->cascadeOnDelete();
            $table->foreignId('agent_profile_id')->constrained('agent_profiles');
            $table->string('skill_slug', 100);
            $table->foreignId('execution_id')->nullable()->constrained('agent_executions')->nullOnDelete();
            $table->json('input');
            $table->json('output')->nullable();
            $table->string('status', 20);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('step_order');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_team_step_results');
        Schema::dropIfExists('agent_team_executions');
        Schema::dropIfExists('agent_team_members');
        Schema::dropIfExists('agent_teams');
    }
};
