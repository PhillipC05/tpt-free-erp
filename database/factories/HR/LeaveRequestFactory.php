<?php

namespace Database\Factories\HR;

use App\Models\HR\Employee;
use App\Models\HR\LeaveRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+3 months');
        $end = clone $start;
        $end->modify('+'.fake()->numberBetween(1, 14).' days');

        return [
            'employee_id' => Employee::factory(),
            'leave_type' => fake()->randomElement(['annual', 'sick', 'personal', 'maternity', 'paternity', 'other']),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'total_days' => fake()->randomFloat(1, 1, 14),
            'reason' => fake()->sentence(),
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved']);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => 'rejected',
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
