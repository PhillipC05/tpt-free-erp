<?php

namespace Database\Factories\Fleet;

use App\Models\Fleet\Driver;
use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'license_number' => fake()->unique()->bothify('DL-########'),
            'license_class' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'license_expiry' => fake()->dateTimeBetween('+6 months', '+5 years'),
            'license_fee' => fake()->optional(0.6)->randomFloat(2, 20, 200),
            'certifications' => fake()->optional()->sentence(),
            'status' => 'active',
        ];
    }
}
