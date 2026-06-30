<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_ab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->foreignId('agent_profile_id')->constrained('agent_profiles')->cascadeOnDelete();
            $table->string('skill_slug_a', 100);
            $table->string('skill_slug_b', 100);
            $table->json('input_data')->nullable();
            $table->string('status', 20)->default('draft');
            // status: draft|running|completed
            $table->string('winner_skill', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['agent_profile_id', 'status']);
        });

        Schema::create('agent_ab_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ab_test_id')->constrained('agent_ab_tests')->cascadeOnDelete();
            $table->string('skill_slug', 100);
            $table->foreignId('execution_id')->nullable()->constrained('agent_executions')->nullOnDelete();
            $table->json('output')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->decimal('quality_score', 5, 2)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['ab_test_id', 'skill_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_ab_test_results');
        Schema::dropIfExists('agent_ab_tests');
    }
};
