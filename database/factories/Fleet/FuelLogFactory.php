<?php

namespace Database\Factories\Fleet;

use App\Models\Fleet\FuelLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FuelLogFactory extends Factory
{
    protected $model = FuelLog::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 5, 60);
        $unitCost = fake()->randomFloat(4, 2.5, 6.0);

        return [
            'vehicle_id' => null,
            'trip_id' => null,
            'date' => fake()->dateTimeBetween('-30 days', 'now'),
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => round($quantity * $unitCost, 2),
            'fuel_type' => fake()->randomElement(['gasoline', 'diesel']),
            'odometer' => fake()->randomFloat(1, 10000, 100000),
            'station' => fake()->optional()->company(),
            'receipt_number' => fake()->optional()->bothify('RCP-#####'),
            'logged_by' => User::factory(),
        ];
    }
}
