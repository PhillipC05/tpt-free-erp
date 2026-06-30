<?php

namespace Database\Factories\Procurement;

use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\Vendor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 50000);

        return [
            'po_number' => fake()->unique()->bothify('PO-####-??'),
            'vendor_id' => Vendor::factory(),
            'order_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'expected_delivery_date' => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'status' => fake()->randomElement(['draft', 'sent', 'confirmed', 'received', 'cancelled']),
            'subtotal' => $subtotal,
            'tax_amount' => round($subtotal * 0.1, 2),
            'total_amount' => round($subtotal * 1.1, 2),
            'notes' => fake()->optional(0.3)->sentence(),
            'created_by' => User::factory(),
            'approved_by' => null,
            'approved_at' => null,
        ];
    }
}
