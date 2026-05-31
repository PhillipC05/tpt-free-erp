<?php

namespace Database\Factories\Assets;

use App\Models\Assets\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 500, 50000);

        return [
            'asset_code' => fake()->unique()->bothify('AST-####'),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(['equipment', 'vehicle', 'furniture', 'it', 'building', 'other']),
            'serial_number' => fake()->unique()->optional(0.8)->bothify('SN-########'),
            'purchase_date' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'purchase_cost' => $cost,
            'current_value' => $cost,
            'salvage_value' => round($cost * 0.1, 2),
            'useful_life_years' => fake()->randomElement([3, 5, 7, 10, 15]),
            'depreciation_method' => 'straight_line',
            'status' => 'active',
            'assigned_to' => null,
            'location_id' => null,
        ];
    }
}
