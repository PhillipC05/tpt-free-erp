<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_cost_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_profile_id')->constrained('agent_profiles')->cascadeOnDelete();
            $table->string('skill_slug', 100);
            $table->string('model_used', 100);
            $table->integer('tokens_input')->default(0);
            $table->integer('tokens_output')->default(0);
            $table->decimal('estimated_cost', 10, 6)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('recorded_at');
            $table->date('date_bucket');
            $table->timestamp('created_at')->nullable();

            $table->index(['agent_profile_id', 'date_bucket']);
            $table->index(['skill_slug', 'date_bucket']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_cost_records');
    }
};
