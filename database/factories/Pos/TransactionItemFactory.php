<?php

namespace Database\Factories\Pos;

use App\Models\Pos\TransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionItemFactory extends Factory
{
    protected $model = TransactionItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $unitPrice = fake()->randomFloat(2, 5, 100);

        return [
            'transaction_id' => null,
            'product_id' => null,
            'description' => fake()->words(3, true),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_percent' => 0,
            'tax_percent' => 10,
            'line_total' => $quantity * $unitPrice * 1.1,
        ];
    }
}
