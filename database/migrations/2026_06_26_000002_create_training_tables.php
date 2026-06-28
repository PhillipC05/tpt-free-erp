<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_programs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->foreignId('course_id')->nullable()->constrained('lms_courses');
            $table->enum('type', ['onboarding', 'compliance', 'skill', 'safety', 'leadership', 'other'])->default('skill');
            $table->integer('duration_hours')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('training_programs');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('location', 200)->nullable();
            $table->foreignId('instructor_id')->nullable()->constrained('hr_employees');
            $table->integer('max_participants')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
        });

        Schema::create('training_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('training_sessions');
            $table->foreignId('employee_id')->constrained('hr_employees');
            $table->enum('status', ['enrolled', 'attended', 'completed', 'no_show'])->default('enrolled');
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['session_id', 'employee_id']);
        });

        Schema::create('training_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees');
            $table->foreignId('program_id')->nullable()->constrained('training_programs');
            $table->string('certification_name', 200);
            $table->string('issuing_body', 200)->nullable();
            $table->string('certificate_number', 100)->nullable();
            $table->date('issued_date');
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['active', 'expired', 'revoked'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('training_enrollments', function (Blueprint $table) {
            $table->index(['employee_id', 'status']);
        });

        Schema::table('training_certifications', function (Blueprint $table) {
            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_certifications');
        Schema::dropIfExists('training_enrollments');
        Schema::dropIfExists('training_sessions');
        Schema::dropIfExists('training_programs');
    }
};
