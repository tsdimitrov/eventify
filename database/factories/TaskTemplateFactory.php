<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TaskTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskTemplate>
 */
class TaskTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_type' => fake()->randomElement(['webinar', 'conference', 'internal_meeting', 'workshop']),
            'title' => fake()->sentence(3),
            'description_template' => fake()->paragraph(),
            'offset_days' => fake()->numberBetween(-30, 7),
            'assigned_role' => fake()->randomElement(['designer', 'copywriter', 'marketing_lead', 'operations']),
        ];
    }

    /**
     * Set the event type for the task template.
     */
    public function forEventType(string $eventType): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_type' => $eventType,
        ]);
    }

    /**
     * Set the assigned role for the task template.
     */
    public function forRole(string $role): static
    {
        return $this->state(fn (array $attributes): array => [
            'assigned_role' => $role,
        ]);
    }
}
