<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magic_link_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('email');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_fingerprint', 64);
            $table->string('device_name', 200)->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('os', 100)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_trusted')->default(false);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();
            $table->unique(['user_id', 'device_fingerprint']);
        });

        Schema::create('legal_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 500);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamp('held_at');
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('user_disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('dispute_type', 100);
            $table->text('description');
            $table->enum('status', ['open', 'under_review', 'resolved', 'rejected'])->default('open');
            $table->text('resolution')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_objections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('processing_purpose', 200);
            $table->text('objection_reason');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('response')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('data_processing_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('processing_activity', 200);
            $table->string('legal_basis', 100);
            $table->string('data_categories', 500)->nullable();
            $table->string('processor', 200)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('team_behavioral_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->string('setting_key', 100);
            $table->text('setting_value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->unique(['team_id', 'setting_key']);
        });

        Schema::create('company_behavioral_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value')->nullable();
            $table->string('description', 500)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_behavioral_settings');
        Schema::dropIfExists('team_behavioral_settings');
        Schema::dropIfExists('data_processing_log');
        Schema::dropIfExists('user_objections');
        Schema::dropIfExists('user_disputes');
        Schema::dropIfExists('legal_holds');
        Schema::dropIfExists('user_devices');
        Schema::dropIfExists('magic_link_tokens');
    }
};
