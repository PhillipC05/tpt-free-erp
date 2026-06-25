<?php

namespace Database\Factories\Fleet;

use App\Models\Fleet\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        return [
            'trip_number' => 'TRIP-'.date('Ymd').'-'.str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'vehicle_id' => null,
            'driver_id' => null,
            'start_location' => fake()->city(),
            'end_location' => fake()->optional(0.8)->city(),
            'start_odometer' => fake()->randomFloat(1, 10000, 100000),
            'end_odometer' => null,
            'distance' => null,
            'start_time' => fake()->dateTimeBetween('-7 days', 'now'),
            'end_time' => null,
            'status' => 'scheduled',
            'purpose' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $distance = fake()->randomFloat(1, 10, 500);
            $endOdometer = $attributes['start_odometer'] + $distance;

            return [
                'status' => 'completed',
                'end_location' => fake()->city(),
                'end_odometer' => $endOdometer,
                'distance' => $distance,
                'end_time' => now(),
            ];
        });
    }

    public function inProgress(): static
    {
        return $this->state(['status' => 'in_progress']);
    }
}
