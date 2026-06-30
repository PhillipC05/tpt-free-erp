<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\ApiKeyUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiKeyUsage>
 */
class ApiKeyUsageFactory extends Factory
{
    protected $model = ApiKeyUsage::class;

    public function definition(): array
    {
        return [
            'api_key_id' => ApiKey::factory(),
            'endpoint' => fake()->randomElement(['v1/finance/accounts', 'v1/inventory/products', 'v1/hr/employees']),
            'method' => fake()->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'status_code' => fake()->randomElement([200, 201, 204, 400, 404, 500]),
            'response_time_ms' => fake()->numberBetween(10, 500),
            'ip_address' => fake()->ipv4(),
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
