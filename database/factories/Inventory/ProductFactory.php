<?php

namespace Database\Factories\Inventory;

use App\Models\Inventory\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'sku' => fake()->unique()->bothify('SKU-####-??'),
            'barcode' => null,
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'category_id' => null,
            'unit' => fake()->randomElement(['pcs', 'kg', 'litre', 'box', 'm']),
            'unit_price' => fake()->randomFloat(2, 1, 1000),
            'cost_price' => fake()->randomFloat(2, 1, 500),
            'weight' => fake()->optional(0.5)->randomFloat(2, 0.1, 50),
            'image_url' => null,
            'is_active' => true,
            'valuation_method' => fake()->randomElement(['fifo', 'lifo', 'average']),
            'min_stock_level' => fake()->randomFloat(2, 0, 10),
            'max_stock_level' => fake()->randomFloat(2, 50, 500),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
