<?php

namespace Database\Factories\Fleet;

use App\Models\Fleet\PartUsage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartUsageFactory extends Factory
{
    protected $model = PartUsage::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitCost = fake()->randomFloat(2, 10, 200);

        return [
            'part_id' => null,
            'vehicle_id' => null,
            'maintenance_id' => null,
            'trip_id' => null,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => round($quantity * $unitCost, 2),
            'used_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'used_by' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
