<?php

namespace Database\Factories\Manufacturing;

use App\Models\Manufacturing\Bom;
use Illuminate\Database\Eloquent\Factories\Factory;

class BomFactory extends Factory
{
    protected $model = Bom::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('BOM-####'),
            'name' => fake()->words(3, true),
            'product_id' => \App\Models\Inventory\Product::factory(),
            'quantity' => fake()->randomFloat(2, 1, 100),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
