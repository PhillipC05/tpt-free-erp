<?php

namespace Database\Factories\Fleet;

use App\Models\Fleet\Part;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartFactory extends Factory
{
    protected $model = Part::class;

    public function definition(): array
    {
        return [
            'part_number' => fake()->unique()->bothify('PT-#####'),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'category_id' => null,
            'manufacturer' => fake()->optional()->company(),
            'supplier' => fake()->optional()->company(),
            'unit' => fake()->randomElement(['pcs', 'set', 'ltr', 'kg', 'box']),
            'unit_cost' => fake()->randomFloat(2, 5, 500),
            'sell_price' => fake()->optional(0.7)->randomFloat(2, 10, 750),
            'quantity_on_hand' => fake()->numberBetween(0, 100),
            'reorder_level' => fake()->numberBetween(2, 20),
            'reorder_quantity' => fake()->numberBetween(10, 50),
            'bin_location' => fake()->optional(0.5)->bothify('Bin-??-##'),
            'compatible_vehicles' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(function () {
            return [
                'quantity_on_hand' => 0,
                'reorder_level' => 10,
            ];
        });
    }
}
