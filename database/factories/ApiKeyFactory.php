<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        $key = ApiKey::generateKey();

        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'key_hash' => ApiKey::hashKey($key),
            'key_prefix' => substr($key, 0, 8),
            'abilities' => null,
            'rate_limit_per_minute' => 60,
            'is_active' => true,
            'last_used_at' => null,
            'expires_at' => null,
        ];
    }
}
