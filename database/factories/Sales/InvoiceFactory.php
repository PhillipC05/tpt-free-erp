<?php

namespace Database\Factories\Sales;

use App\Models\Sales\Customer;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 10000);
        $total = round($subtotal * 1.1, 2);

        return [
            'invoice_number' => fake()->unique()->bothify('INV-####-??'),
            'order_id' => Order::factory(),
            'customer_id' => Customer::factory(),
            'invoice_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'due_date' => fake()->dateTimeBetween('now', '+90 days')->format('Y-m-d'),
            'subtotal' => $subtotal,
            'tax_amount' => round($subtotal * 0.1, 2),
            'total_amount' => $total,
            'paid_amount' => 0,
            'balance_due' => $total,
            'status' => fake()->randomElement(['draft', 'sent', 'paid', 'overdue', 'cancelled']),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => 'paid',
            'paid_amount' => $attrs['total_amount'],
            'balance_due' => 0,
        ]);
    }
}
