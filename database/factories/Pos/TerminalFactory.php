<?php

namespace Database\Factories\Pos;

use App\Models\Pos\Terminal;
use Illuminate\Database\Eloquent\Factories\Factory;

class TerminalFactory extends Factory
{
    protected $model = Terminal::class;

    public function definition(): array
    {
        return [
            'terminal_code' => fake()->unique()->bothify('POS-####'),
            'name' => 'Terminal ' . fake()->randomLetter(),
            'warehouse_id' => null,
            'assigned_to' => null,
            'status' => 'active',
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
