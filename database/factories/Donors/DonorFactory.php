<?php

namespace Database\Factories\Donors;

use App\Models\Donors\Donor;
use Illuminate\Database\Eloquent\Factories\Factory;

class DonorFactory extends Factory
{
    protected $model = Donor::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => fake()->randomElement(['individual', 'corporate', 'foundation', 'government']),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'contact_person' => fake()->name(),
            'total_contributed' => fake()->randomFloat(2, 0, 100000),
            'status' => 'active',
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function individual(): static
    {
        return $this->state(['type' => 'individual']);
    }

    public function corporate(): static
    {
        return $this->state(['type' => 'corporate']);
    }

    public function foundation(): static
    {
        return $this->state(['type' => 'foundation']);
    }

    public function government(): static
    {
        return $this->state(['type' => 'government']);
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
