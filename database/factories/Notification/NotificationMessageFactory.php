<?php

namespace Database\Factories\Notification;

use App\Models\Notification\NotificationMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationMessageFactory extends Factory
{
    protected $model = NotificationMessage::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'template_id' => null,
            'channel' => 'in_app',
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'data' => null,
            'status' => 'sent',
            'attempts' => 1,
            'sent_at' => now(),
            'read_at' => null,
        ];
    }

    public function unread(): static
    {
        return $this->state(['read_at' => null]);
    }

    public function read(): static
    {
        return $this->state(['read_at' => now()]);
    }
}
