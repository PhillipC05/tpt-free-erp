<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Magic link and TOTP auth columns on users
        Schema::table('users', function (Blueprint $table) {
            $table->string('magic_link_token', 80)->nullable()->unique()->after('remember_token');
            $table->timestamp('magic_link_expires_at')->nullable()->after('magic_link_token');
            $table->string('totp_secret', 100)->nullable()->after('magic_link_expires_at');
            $table->boolean('totp_enabled')->default(false)->after('totp_secret');
        });

        // Missing columns on lms_courses
        Schema::table('lms_courses', function (Blueprint $table) {
            $table->string('instructor', 200)->nullable()->after('description');
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('beginner')->after('instructor');
            $table->boolean('is_published')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['magic_link_token', 'magic_link_expires_at', 'totp_secret', 'totp_enabled']);
        });

        Schema::table('lms_courses', function (Blueprint $table) {
            $table->dropColumn(['instructor', 'difficulty', 'is_published']);
        });
    }
};
