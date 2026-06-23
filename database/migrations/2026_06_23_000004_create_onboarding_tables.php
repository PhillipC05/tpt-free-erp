<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_presets', function (Blueprint $table) {
            $table->id();
            $table->string('industry_key')->unique();
            $table->string('industry_name');
            $table->string('icon_emoji', 10);
            $table->text('description');
            $table->json('recommended_modules');
            $table->json('chart_of_accounts_template');
            $table->json('departments_template');
            $table->string('color_theme', 7)->default('#6366f1');
            $table->timestamps();
        });

        Schema::create('onboarding_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('industry_key')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_completions');
        Schema::dropIfExists('onboarding_presets');
    }
};
