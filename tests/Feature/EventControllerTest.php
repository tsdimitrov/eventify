<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\TaskTemplateSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from events index to login', function (): void {
    $response = $this->get(route('events.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can view events index with task progress counts', function (): void {
    $user = User::factory()->create();

    $event1 = Event::factory()->create([
        'title' => 'First Event',
        'event_date' => now()->addDays(5),
    ]);

    $event2 = Event::factory()->create([
        'title' => 'Second Event',
        'event_date' => now()->addDays(10),
    ]);

    Task::factory()->completed()->create(['event_id' => $event1->id]);
    Task::factory()->create(['event_id' => $event1->id, 'status' => 'pending']);

    $response = $this->actingAs($user)->get(route('events.index'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Index')
            ->has('events', 2)
            ->where('events.0.id', $event1->id)
            ->where('events.0.tasks_count', 2)
            ->where('events.0.completed_tasks_count', 1)
            ->where('events.1.id', $event2->id)
        );
});

test('authenticated users can view event creation page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('events.create'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Create')
        );
});

test('storing an event validates required inputs', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('events.store'), []);

    $response->assertSessionHasErrors(['title', 'event_date', 'type']);
});

test('storing an event requires event_date to be after today', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('events.store'), [
        'title' => 'Past Event',
        'event_date' => now()->subDay()->format('Y-m-d H:i:s'),
        'type' => 'webinar',
    ]);

    $response->assertSessionHasErrors(['event_date']);
});

test('storing an event creates record, seeds tasks, and redirects to show', function (): void {
    $this->seed(TaskTemplateSeeder::class);
    $user = User::factory()->create();

    $payload = [
        'title' => 'Cloud Native Webinar',
        'subtitle' => 'Kubernetes & Microservices',
        'description' => 'A comprehensive overview of cloud native architectures.',
        'event_date' => now()->addDays(20)->format('Y-m-d H:i:s'),
        'type' => 'webinar',
    ];

    $response = $this->actingAs($user)->post(route('events.store'), $payload);

    $event = Event::where('title', 'Cloud Native Webinar')->firstOrFail();

    $response->assertRedirect(route('events.show', $event))
        ->assertSessionHas('success', 'Event created successfully.');

    expect($event->tasks()->count())->toBe(4);
});

test('authenticated users can view event show page with ordered tasks', function (): void {
    $user = User::factory()->create();

    $event = Event::factory()->create([
        'event_date' => now()->addDays(15),
    ]);

    $laterTask = Task::factory()->create([
        'event_id' => $event->id,
        'title' => 'Later Task',
        'due_date' => now()->addDays(10),
    ]);

    $earlierTask = Task::factory()->create([
        'event_id' => $event->id,
        'title' => 'Earlier Task',
        'due_date' => now()->addDays(2),
    ]);

    $response = $this->actingAs($user)->get(route('events.show', $event));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('event.id', $event->id)
            ->has('event.tasks', 2)
            ->where('event.tasks.0.id', $earlierTask->id)
            ->where('event.tasks.1.id', $laterTask->id)
        );
});
