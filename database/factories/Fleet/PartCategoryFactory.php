<?php

namespace Database\Factories\Fleet;

use App\Models\Fleet\PartCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PartCategoryFactory extends Factory
{
    protected $model = PartCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Brakes', 'Engine', 'Filters', 'Tires', 'Electrical',
            'Suspension', 'Exhaust', 'Fluids', 'Belts & Hoses',
            'Lights', 'Body Parts', 'Tools',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->optional()->sentence(),
            'parent_id' => null,
            'is_active' => true,
        ];
    }
}
