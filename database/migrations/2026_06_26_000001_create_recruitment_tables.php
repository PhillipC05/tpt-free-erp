<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_code', 20)->unique();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('hr_departments');
            $table->string('location', 200)->nullable();
            $table->string('employment_type', 50)->default('full_time');
            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->integer('positions')->default(1);
            $table->enum('status', ['draft', 'open', 'on_hold', 'closed', 'filled'])->default('draft');
            $table->date('posted_date')->nullable();
            $table->date('closing_date')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('recruitment_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number', 20)->unique();
            $table->foreignId('job_id')->constrained('recruitment_jobs');
            $table->string('candidate_name', 200);
            $table->string('candidate_email', 200);
            $table->string('candidate_phone', 30)->nullable();
            $table->text('resume_path')->nullable();
            $table->text('cover_letter')->nullable();
            $table->decimal('expected_salary', 12, 2)->nullable();
            $table->enum('status', ['new', 'screening', 'interview', 'offer', 'hired', 'rejected'])->default('new');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('recruitment_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('recruitment_applications');
            $table->string('interview_type', 50)->default('phone');
            $table->timestamp('scheduled_at');
            $table->integer('duration_minutes')->default(30);
            $table->string('location', 200)->nullable();
            $table->foreignId('interviewer_id')->nullable()->constrained('hr_employees');
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('recruitment_applications', function (Blueprint $table) {
            $table->index(['job_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_interviews');
        Schema::dropIfExists('recruitment_applications');
        Schema::dropIfExists('recruitment_jobs');
    }
};
