<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from tasks index to login', function (): void {
    $response = $this->get(route('tasks.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can view tasks index with associated events', function (): void {
    $user = User::factory()->create();
    $event = Event::factory()->create(['title' => 'Master Event']);
    $task = Task::factory()->create([
        'event_id' => $event->id,
        'title' => 'Key Task',
        'assigned_role' => 'designer',
    ]);

    $response = $this->actingAs($user)->get(route('tasks.index'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tasks/Index')
            ->has('tasks', 1)
            ->where('tasks.0.id', $task->id)
            ->where('tasks.0.event.title', 'Master Event')
            ->has('availableRoles')
        );
});

test('tasks index correctly filters by assigned role', function (): void {
    $user = User::factory()->create();

    $designerTask = Task::factory()->create(['assigned_role' => 'designer']);
    $writerTask = Task::factory()->create(['assigned_role' => 'copywriter']);

    $response = $this->actingAs($user)->get(route('tasks.index', ['role' => 'designer']));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tasks/Index')
            ->has('tasks', 1)
            ->where('tasks.0.id', $designerTask->id)
            ->where('filters.role', 'designer')
        );
});

test('tasks index correctly filters by status', function (): void {
    $user = User::factory()->create();

    $pendingTask = Task::factory()->create(['status' => 'pending']);
    $completedTask = Task::factory()->completed()->create();

    $response = $this->actingAs($user)->get(route('tasks.index', ['status' => 'completed']));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tasks/Index')
            ->has('tasks', 1)
            ->where('tasks.0.id', $completedTask->id)
            ->where('filters.status', 'completed')
        );
});

test('tasks index treats all as no filter', function (): void {
    $user = User::factory()->create();

    Task::factory()->create(['assigned_role' => 'designer']);
    Task::factory()->create(['assigned_role' => 'copywriter']);

    $response = $this->actingAs($user)->get(route('tasks.index', ['role' => 'all', 'status' => 'all']));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tasks/Index')
            ->has('tasks', 2)
        );
});

test('guests cannot update tasks', function (): void {
    $task = Task::factory()->create();

    $response = $this->patch(route('tasks.update', $task), [
        'status' => 'completed',
    ]);

    $response->assertRedirect(route('login'));
});

test('updating task status changes status in database and redirects back with flash message', function (): void {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)
        ->from(route('events.show', $task->event_id))
        ->patch(route('tasks.update', $task), [
            'status' => 'in_progress',
        ]);

    $response->assertRedirect(route('events.show', $task->event_id))
        ->assertSessionHas('success', 'Task updated.');

    expect($task->fresh()->status)->toBe('in_progress');
});

test('updating task asset_url saves url in database', function (): void {
    $user = User::factory()->create();
    $task = Task::factory()->create();

    $response = $this->actingAs($user)
        ->patch(route('tasks.update', $task), [
            'asset_url' => 'https://drive.google.com/file/d/12345/view',
        ]);

    $response->assertSessionHas('success', 'Task updated.');

    expect($task->fresh()->asset_url)->toBe('https://drive.google.com/file/d/12345/view');
});

test('updating task validates status in permitted enum values', function (): void {
    $user = User::factory()->create();
    $task = Task::factory()->create();

    $response = $this->actingAs($user)
        ->patch(route('tasks.update', $task), [
            'status' => 'invalid_status_xyz',
        ]);

    $response->assertSessionHasErrors(['status']);
});

test('updating task validates asset_url format', function (): void {
    $user = User::factory()->create();
    $task = Task::factory()->create();

    $response = $this->actingAs($user)
        ->patch(route('tasks.update', $task), [
            'asset_url' => 'not-a-valid-url',
        ]);

    $response->assertSessionHasErrors(['asset_url']);
});
