<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_marketplace_items', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('category');
            $table->string('author');
            $table->string('github_url');
            $table->string('version', 30)->default('1.0.0');
            $table->unsignedInteger('downloads_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->decimal('rating', 3, 2)->nullable();
            $table->json('tags')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('skill_marketplace_installs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_item_id')->constrained('skill_marketplace_items')->cascadeOnDelete();
            $table->foreignId('installed_by')->constrained('users');
            $table->timestamp('installed_at');
            $table->timestamp('uninstalled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_marketplace_installs');
        Schema::dropIfExists('skill_marketplace_items');
    }
};
