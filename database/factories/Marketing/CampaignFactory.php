<?php

namespace Database\Factories\Marketing;

use App\Models\Marketing\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'code' => fake()->unique()->bothify('CAMP-####'),
            'type' => fake()->randomElement(['email', 'social', 'event', 'paid_ads', 'content']),
            'status' => 'draft',
            'budget' => fake()->randomFloat(2, 1000, 50000),
            'created_by' => User::factory(),
        ];
    }
}
