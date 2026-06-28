<?php

namespace Database\Factories\Notification;

use App\Models\Notification\NotificationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationTemplateFactory extends Factory
{
    protected $model = NotificationTemplate::class;

    public function definition(): array
    {
        $code = fake()->unique()->bothify('??_????_??');

        return [
            'code' => $code,
            'name' => fake()->sentence(3),
            'subject' => fake()->sentence(5),
            'body' => fake()->paragraph(),
            'html_body' => null,
            'default_channels' => ['in_app'],
            'variables' => ['name', 'item'],
            'category' => fake()->randomElement(['general', 'finance', 'fleet', 'subscription', 'system']),
            'is_active' => true,
        ];
    }
}
