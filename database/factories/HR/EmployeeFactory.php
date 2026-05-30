<?php

namespace Database\Factories\HR;

use App\Models\HR\Department;
use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'employee_code' => fake()->unique()->bothify('EMP-####'),
            'user_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'position' => fake()->jobTitle(),
            'department_id' => null,
            'manager_id' => null,
            'hire_date' => fake()->dateTimeBetween('-5 years', '-1 month')->format('Y-m-d'),
            'termination_date' => null,
            'employment_type' => 'full_time',
            'status' => 'active',
            'salary' => fake()->randomFloat(2, 30000, 120000),
            'currency' => 'USD',
            'address' => fake()->address(),
            'emergency_contact' => fake()->name(),
            'emergency_phone' => fake()->phoneNumber(),
        ];
    }

    public function withDepartment(): static
    {
        return $this->state(fn () => [
            'department_id' => Department::factory(),
        ]);
    }

    public function terminated(): static
    {
        return $this->state([
            'status' => 'terminated',
            'termination_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ]);
    }
}
