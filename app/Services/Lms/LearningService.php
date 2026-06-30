<?php

namespace App\Services\Lms;

use App\Models\Lms\Course;
use App\Models\Lms\Enrollment;

class LearningService
{
    public function createCourse(array $data): Course
    {
        return Course::create($data);
    }

    public function enrollEmployee(Course $course, int $employeeId): Enrollment
    {
        $exists = Enrollment::where('course_id', $course->id)
            ->where('employee_id', $employeeId)
            ->exists();

        if ($exists) {
            throw new \RuntimeException('Employee is already enrolled in this course');
        }

        return Enrollment::create([
            'course_id' => $course->id,
            'employee_id' => $employeeId,
            'enrollment_date' => now()->toDateString(),
            'status' => 'enrolled',
        ]);
    }

    public function completeEnrollment(Enrollment $enrollment, ?float $score = null): Enrollment
    {
        $enrollment->update([
            'status' => 'completed',
            'completion_date' => now()->toDateString(),
            'score' => $score,
        ]);

        return $enrollment->fresh();
    }

    public function dropEnrollment(Enrollment $enrollment): Enrollment
    {
        $enrollment->update(['status' => 'dropped']);

        return $enrollment->fresh();
    }
}
