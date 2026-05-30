<?php

namespace Database\Factories\HR;

use App\Models\HR\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('DEPT-##'),
            'name' => fake()->randomElement(['Finance', 'Human Resources', 'Operations', 'Sales', 'IT', 'Procurement', 'Marketing', 'Legal']),
            'description' => fake()->sentence(),
            'manager_id' => null,
            'parent_id' => null,
            'is_active' => true,
        ];
    }
}
