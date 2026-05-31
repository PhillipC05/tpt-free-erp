<?php

namespace Database\Factories\Procurement;

use App\Models\Procurement\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('VEN-####'),
            'name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'tax_number' => fake()->optional(0.7)->numerify('TAX-########'),
            'payment_terms' => fake()->randomElement([30, 60, 90]),
            'status' => 'active',
            'current_balance' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
