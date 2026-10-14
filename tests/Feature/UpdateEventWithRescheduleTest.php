<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from event edit, update, and destroy to login', function (): void {
    $event = Event::factory()->create();

    $this->get(route('events.edit', $event))->assertRedirect(route('login'));
    $this->put(route('events.update', $event), ['title' => 'Updated'])->assertRedirect(route('login'));
    $this->delete(route('events.destroy', $event))->assertRedirect(route('login'));
});

test('authenticated users can view event edit page', function (): void {
    $user = User::factory()->create();
    $event = Event::factory()->create([
        'title' => 'Design Sprint 2026',
        'type' => 'workshop',
        'status' => 'scheduled',
    ]);

    $response = $this->actingAs($user)->get(route('events.edit', $event));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Edit')
            ->where('event.id', $event->id)
            ->where('event.title', 'Design Sprint 2026')
            ->where('event.type', 'workshop')
            ->where('event.status', 'scheduled')
        );
});

test('updating an event validates required inputs', function (): void {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $response = $this->actingAs($user)->put(route('events.update', $event), [
        'title' => '',
        'event_date' => 'not-a-date',
        'type' => 'invalid-type',
        'status' => 'invalid-status',
    ]);

    $response->assertSessionHasErrors(['title', 'event_date', 'type', 'status']);
});

test('moving event forward by 5 days shifts all pending and in_progress tasks forward by 5 days when reschedule_tasks is true', function (): void {
    $user = User::factory()->create();

    $initialEventDate = CarbonImmutable::parse('2026-11-10 10:00:00');
    $newEventDate = CarbonImmutable::parse('2026-11-15 10:00:00');

    $event = Event::factory()->create([
        'event_date' => $initialEventDate,
    ]);

    $pendingTask = Task::factory()->create([
        'event_id' => $event->id,
        'status' => 'pending',
        'due_date' => CarbonImmutable::parse('2026-11-03 10:00:00'),
    ]);

    $inProgressTask = Task::factory()->create([
        'event_id' => $event->id,
        'status' => 'in_progress',
        'due_date' => CarbonImmutable::parse('2026-11-07 10:00:00'),
    ]);

    $completedTask = Task::factory()->create([
        'event_id' => $event->id,
        'status' => 'completed',
        'due_date' => CarbonImmutable::parse('2026-11-01 10:00:00'),
    ]);

    $response = $this->actingAs($user)->put(route('events.update', $event), [
        'title' => 'Rescheduled Event Title',
        'subtitle' => 'New Subtitle',
        'description' => 'Updated event description',
        'event_date' => $newEventDate->format('Y-m-d H:i:s'),
        'type' => $event->type,
        'status' => 'scheduled',
        'reschedule_tasks' => true,
    ]);

    $response->assertRedirect(route('events.show', $event))
        ->assertSessionHas('success', 'Event updated successfully. 2 tasks rescheduled.');

    $event->refresh();
    expect($event->title)->toBe('Rescheduled Event Title')
        ->and($event->event_date->format('Y-m-d H:i:s'))->toBe('2026-11-15 10:00:00');

    $pendingTask->refresh();
    $inProgressTask->refresh();
    $completedTask->refresh();

    expect($pendingTask->due_date->format('Y-m-d H:i:s'))->toBe('2026-11-08 10:00:00')
        ->and($inProgressTask->due_date->format('Y-m-d H:i:s'))->toBe('2026-11-12 10:00:00')
        ->and($completedTask->due_date->format('Y-m-d H:i:s'))->toBe('2026-11-01 10:00:00');
});

test('completed tasks retain their original due_date untouched after an event date shift', function (): void {
    $user = User::factory()->create();

    $initialEventDate = CarbonImmutable::parse('2026-12-01 14:00:00');
    $newEventDate = CarbonImmutable::parse('2026-12-10 14:00:00');

    $event = Event::factory()->create([
        'event_date' => $initialEventDate,
    ]);

    $completedTask1 = Task::factory()->create([
        'event_id' => $event->id,
        'status' => 'completed',
        'due_date' => CarbonImmutable::parse('2026-11-20 14:00:00'),
    ]);

    $completedTask2 = Task::factory()->create([
        'event_id' => $event->id,
        'status' => 'completed',
        'due_date' => CarbonImmutable::parse('2026-11-25 14:00:00'),
    ]);

    $this->actingAs($user)->put(route('events.update', $event), [
        'title' => $event->title,
        'event_date' => $newEventDate->format('Y-m-d H:i:s'),
        'type' => $event->type,
        'status' => $event->status,
        'reschedule_tasks' => true,
    ])->assertRedirect(route('events.show', $event));

    $completedTask1->refresh();
    $completedTask2->refresh();

    expect($completedTask1->due_date->format('Y-m-d H:i:s'))->toBe('2026-11-20 14:00:00')
        ->and($completedTask2->due_date->format('Y-m-d H:i:s'))->toBe('2026-11-25 14:00:00');
});

test('when reschedule_tasks is false task due dates remain unchanged even when event date changes', function (): void {
    $user = User::factory()->create();

    $initialEventDate = CarbonImmutable::parse('2026-11-10 10:00:00');
    $newEventDate = CarbonImmutable::parse('2026-11-25 10:00:00');

    $event = Event::factory()->create([
        'event_date' => $initialEventDate,
    ]);

    $pendingTask = Task::factory()->create([
        'event_id' => $event->id,
        'status' => 'pending',
        'due_date' => CarbonImmutable::parse('2026-11-03 10:00:00'),
    ]);

    $this->actingAs($user)->put(route('events.update', $event), [
        'title' => $event->title,
        'event_date' => $newEventDate->format('Y-m-d H:i:s'),
        'type' => $event->type,
        'status' => $event->status,
        'reschedule_tasks' => false,
    ])->assertRedirect(route('events.show', $event))
        ->assertSessionHas('success', 'Event updated successfully.');

    $pendingTask->refresh();
    expect($pendingTask->due_date->format('Y-m-d H:i:s'))->toBe('2026-11-03 10:00:00');
});

test('soft deleting an event removes it from standard queries without foreign key constraint errors', function (): void {
    $user = User::factory()->create();

    $event = Event::factory()->create();
    $task = Task::factory()->create([
        'event_id' => $event->id,
    ]);

    $response = $this->actingAs($user)->delete(route('events.destroy', $event));

    $response->assertRedirect(route('events.index'))
        ->assertSessionHas('success', 'Event moved to archive.');

    expect(Event::find($event->id))->toBeNull()
        ->and(Event::withTrashed()->find($event->id))->not->toBeNull()
        ->and(Event::withTrashed()->find($event->id)?->trashed())->toBeTrue();

    // Verify task still exists and references the soft-deleted event without error
    $task->refresh();
    expect($task->event_id)->toBe($event->id);
});
