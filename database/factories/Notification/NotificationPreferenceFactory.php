<?php

namespace Database\Factories\Notification;

use App\Models\Notification\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'template_code' => null,
            'channels' => ['in_app'],
            'email_enabled' => true,
            'in_app_enabled' => true,
            'webhook_enabled' => false,
            'email_address' => null,
        ];
    }
}
