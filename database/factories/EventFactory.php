<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'subtitle' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'event_date' => fake()->dateTimeBetween('+1 week', '+3 months'),
            'type' => fake()->randomElement(['webinar', 'conference', 'internal_meeting', 'workshop']),
            'status' => 'draft',
            'metadata' => [
                'tags' => fake()->words(3),
                'target_audience' => fake()->words(2, true),
            ],
        ];
    }

    /**
     * Indicate that the event is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'scheduled',
        ]);
    }

    /**
     * Indicate that the event is a webinar.
     */
    public function webinar(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'webinar',
        ]);
    }

    /**
     * Indicate that the event is a conference.
     */
    public function conference(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'conference',
        ]);
    }
}
