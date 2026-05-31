<?php

namespace Database\Factories\FieldService;

use App\Models\FieldService\ServiceTicket;
use App\Models\Sales\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceTicketFactory extends Factory
{
    protected $model = ServiceTicket::class;

    public function definition(): array
    {
        return [
            'ticket_number' => fake()->unique()->bothify('TKT-####-??'),
            'customer_id' => Customer::factory(),
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => fake()->randomElement(['open', 'assigned', 'in_progress', 'resolved', 'closed']),
            'assigned_to' => null,
            'scheduled_date' => fake()->optional(0.6)->dateTimeBetween('now', '+2 weeks')?->format('Y-m-d'),
            'resolved_at' => null,
            'resolution_notes' => null,
        ];
    }
}
