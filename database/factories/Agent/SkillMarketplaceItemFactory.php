<?php

namespace Database\Factories\Agent;

use App\Models\SkillMarketplaceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class SkillMarketplaceItemFactory extends Factory
{
    protected $model = SkillMarketplaceItem::class;

    public function definition(): array
    {
        $category = fake()->randomElement(['data-processing', 'reporting', 'automation', 'integration', 'analysis']);
        $name = fake()->words(3, true);

        return [
            'slug' => $category.'.'.str_replace(' ', '_', strtolower($name)),
            'name' => ucfirst($name),
            'description' => fake()->paragraph(),
            'category' => $category,
            'author' => fake()->name(),
            'github_url' => 'https://github.com/'.fake()->userName().'/'.fake()->slug(),
            'version' => fake()->semver(),
            'downloads_count' => fake()->numberBetween(0, 10000),
            'is_featured' => fake()->boolean(20),
            'rating' => fake()->randomFloat(2, 1, 5),
            'tags' => fake()->words(3),
        ];
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
