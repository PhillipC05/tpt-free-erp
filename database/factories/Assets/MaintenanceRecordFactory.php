<?php

namespace Database\Factories\Assets;

use App\Models\Assets\Asset;
use App\Models\Assets\MaintenanceRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaintenanceRecordFactory extends Factory
{
    protected $model = MaintenanceRecord::class;

    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['preventive', 'corrective', 'emergency']),
            'scheduled_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'completed_date' => null,
            'cost' => fake()->optional(0.6)->randomFloat(2, 50, 5000),
            'status' => 'scheduled',
            'notes' => fake()->optional(0.4)->sentence(),
        ];
    }
}
