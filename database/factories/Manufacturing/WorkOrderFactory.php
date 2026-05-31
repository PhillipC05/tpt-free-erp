<?php

namespace Database\Factories\Manufacturing;

use App\Models\Manufacturing\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkOrderFactory extends Factory
{
    protected $model = WorkOrder::class;

    public function definition(): array
    {
        return [
            'wo_number' => fake()->unique()->bothify('WO-####-??'),
            'product_id' => \App\Models\Inventory\Product::factory(),
            'bom_id' => null,
            'planned_quantity' => fake()->randomFloat(2, 1, 500),
            'produced_quantity' => 0,
            'start_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+3 months')->format('Y-m-d'),
            'status' => fake()->randomElement(['planned', 'in_progress', 'completed', 'cancelled']),
            'notes' => fake()->optional(0.3)->sentence(),
            'assigned_to' => null,
        ];
    }
}
