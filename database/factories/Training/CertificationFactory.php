<?php

namespace Database\Factories\Training;

use App\Models\Training\Certification;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificationFactory extends Factory
{
    protected $model = Certification::class;

    public function definition(): array
    {
        return [
            'employee_id' => null,
            'program_id' => null,
            'certification_name' => fake()->randomElement(['OSHA 30', 'PMP', 'First Aid', 'CPR', 'Forklift Operator']),
            'issuing_body' => fake()->company(),
            'certificate_number' => fake()->unique()->bothify('CERT-#####'),
            'issued_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'expiry_date' => fake()->optional(0.7)->dateTimeBetween('+3 months', '+3 years'),
            'status' => 'active',
        ];
    }

    public function expiringSoon(): static
    {
        return $this->state(['expiry_date' => now()->addDays(15)]);
    }

    public function expired(): static
    {
        return $this->state(['expiry_date' => now()->subDays(5), 'status' => 'active']);
    }
}
