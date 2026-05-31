<?php

namespace Database\Factories\Quality;

use App\Models\Quality\QualityCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

class QualityCheckFactory extends Factory
{
    protected $model = QualityCheck::class;

    public function definition(): array
    {
        return [
            'check_code' => fake()->unique()->bothify('QC-####'),
            'product_id' => \App\Models\Inventory\Product::factory(),
            'reference_type' => fake()->randomElement(['work_order', 'purchase_order', 'incoming', null]),
            'reference_id' => null,
            'type' => fake()->randomElement(['incoming', 'in_process', 'final', 'audit']),
            'result' => fake()->optional(0.7)->randomElement(['pass', 'fail', 'conditional']),
            'notes' => fake()->optional(0.5)->sentence(),
            'inspected_by' => null,
            'inspected_at' => fake()->optional(0.7)->dateTimeThisMonth(),
        ];
    }

    public function passed(): static
    {
        return $this->state(['result' => 'pass']);
    }

    public function failed(): static
    {
        return $this->state(['result' => 'fail']);
    }
}
