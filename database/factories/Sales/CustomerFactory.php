<?php

namespace Database\Factories\Sales;

use App\Models\Sales\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('CUST-####'),
            'name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'country' => fake()->countryCode(),
            'tax_number' => fake()->optional(0.7)->numerify('TAX-########'),
            'payment_terms' => fake()->randomElement([30, 60, 90]),
            'credit_limit' => fake()->randomFloat(2, 1000, 100000),
            'current_balance' => 0,
            'status' => 'active',
            'assigned_to' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
