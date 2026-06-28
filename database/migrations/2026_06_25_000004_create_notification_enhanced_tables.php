<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name', 200);
            $table->text('subject')->nullable();
            $table->text('body');
            $table->text('html_body')->nullable();
            $table->json('default_channels')->default('["in_app"]');
            $table->json('variables')->nullable();
            $table->string('category', 100)->default('general');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('template_code', 100)->nullable();
            $table->json('channels')->default('["in_app"]');
            $table->boolean('email_enabled')->default(true);
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('webhook_enabled')->default(false);
            $table->string('email_address', 200)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'template_code']);
        });

        Schema::create('notification_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('template_id')->nullable()->constrained('notification_templates');
            $table->string('channel', 50);
            $table->text('subject')->nullable();
            $table->text('body');
            $table->json('data')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['channel', 'status']);
        });

        Schema::create('notification_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('url', 500);
            $table->json('events')->default('["*"]');
            $table->string('secret', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('failure_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notification_queue');
            $table->string('channel', 50);
            $table->string('status', 20);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_webhooks');
        Schema::dropIfExists('notification_queue');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_templates');
    }
};
