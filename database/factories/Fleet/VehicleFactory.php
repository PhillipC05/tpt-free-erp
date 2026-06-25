<?php

namespace Database\Factories\Fleet;

use App\Models\Fleet\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'vehicle_code' => fake()->unique()->bothify('VH-####'),
            'make' => fake()->randomElement(['Toyota', 'Ford', 'Honda', 'Chevrolet', 'Mercedes-Benz', 'BMW', 'Nissan', 'Hyundai']),
            'model' => fake()->randomElement(['Corolla', 'F-150', 'Civic', 'Silverado', 'Sprinter', 'X5', 'Altima', 'Tucson']),
            'year' => fake()->numberBetween(2018, 2026),
            'vin' => fake()->unique()->bothify('?????????????????'),
            'license_plate' => fake()->unique()->bothify('??-####'),
            'color' => fake()->safeColorName(),
            'type' => fake()->randomElement(['car', 'truck', 'van', 'motorcycle', 'bus', 'trailer', 'other']),
            'fuel_type' => fake()->randomElement(['gasoline', 'diesel', 'electric', 'hybrid']),
            'current_odometer' => fake()->randomFloat(1, 1000, 100000),
            'fuel_capacity' => fake()->randomFloat(2, 30, 100),
            'fuel_level' => fake()->randomFloat(1, 10, 100),
            'status' => 'active',
            'assigned_driver_id' => null,
            'warehouse_id' => null,
            'registration_expiry' => fake()->dateTimeBetween('+1 month', '+2 years'),
            'insurance_expiry' => fake()->dateTimeBetween('+1 month', '+2 years'),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }

    public function maintenance(): static
    {
        return $this->state(['status' => 'maintenance']);
    }
}
