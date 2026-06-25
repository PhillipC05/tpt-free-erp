<?php

namespace Database\Factories\Fleet;

use App\Models\Fleet\MaintenanceRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaintenanceRecordFactory extends Factory
{
    protected $model = MaintenanceRecord::class;

    public function definition(): array
    {
        return [
            'vehicle_id' => null,
            'type' => fake()->randomElement(['preventive', 'corrective', 'emergency', 'inspection']),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'scheduled_date' => fake()->dateTimeBetween('-30 days', '+30 days'),
            'completed_date' => null,
            'cost' => fake()->optional(0.7)->randomFloat(2, 50, 2000),
            'service_provider' => fake()->optional()->company(),
            'odometer_at_service' => fake()->optional()->randomFloat(1, 10000, 100000),
            'status' => 'scheduled',
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'completed_date' => fake()->dateTimeBetween('-14 days', 'now'),
        ]);
    }
}
