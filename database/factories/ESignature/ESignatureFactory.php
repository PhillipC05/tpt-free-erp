<?php

namespace Database\Factories\ESignature;

use App\Models\ESignature\ESignature;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ESignatureFactory extends Factory
{
    protected $model = ESignature::class;

    public function definition(): array
    {
        return [
            'signable_type' => 'App\\Models\\Contracts\\Contract',
            'signable_id' => 1,
            'token' => ESignature::generateToken(),
            'status' => 'pending',
            'signer_name' => $this->faker->name(),
            'signer_email' => $this->faker->safeEmail(),
            'document_hash' => hash('sha256', $this->faker->sentence()),
            'message' => $this->faker->optional()->sentence(),
            'expires_at' => $this->faker->optional()->dateTimeBetween('+1 day', '+30 days'),
            'requested_by' => User::factory(),
            'audit_log' => [['event' => 'created', 'at' => now()->toIso8601String()]],
        ];
    }

    public function signed(): static
    {
        return $this->state(fn () => [
            'status' => 'signed',
            'signature_type' => 'typed',
            'signature_data' => $this->faker->name(),
            'signer_ip' => $this->faker->ipv4(),
            'signed_hash' => hash('sha256', $this->faker->sentence()),
            'signed_at' => now(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn () => ['status' => 'declined']);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => 'expired',
            'expires_at' => now()->subDay(),
        ]);
    }
}
