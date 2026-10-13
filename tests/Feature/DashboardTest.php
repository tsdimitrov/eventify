<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from dashboard to the login page', function (): void {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard with all required executive props', function (): void {
    $user = User::factory()->create();

    $event = Event::factory()->create([
        'title' => 'Executive Keynote',
        'event_date' => now()->addDays(10),
    ]);

    Task::factory()->create([
        'event_id' => $event->id,
        'assigned_role' => 'designer',
        'status' => 'pending',
        'due_date' => now()->addDays(5),
    ]);

    Task::factory()->completed()->create([
        'event_id' => $event->id,
        'assigned_role' => 'copywriter',
        'asset_url' => 'https://docs.google.com/document/d/123/edit',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('kpi', fn (Assert $kpi) => $kpi
                ->has('active_events_count')
                ->has('pending_tasks_count')
                ->has('overdue_tasks_count')
                ->has('completed_this_week_count')
            )
            ->has('redFlags', fn (Assert $flags) => $flags
                ->has('overdue_tasks')
                ->has('at_risk_events')
            )
            ->has('upcomingEvents')
            ->has('workloadByRole')
            ->has('recentAssets')
        );
});

test('overdue tasks are correctly flagged in redFlags and non-overdue or completed tasks are excluded', function (): void {
    $user = User::factory()->create();

    $event = Event::factory()->create([
        'title' => 'Near Term Event',
        'event_date' => now()->addDays(4),
    ]);

    // Overdue pending task
    $overdueTask = Task::factory()->create([
        'event_id' => $event->id,
        'title' => 'Overdue Critical Deck',
        'assigned_role' => 'designer',
        'status' => 'pending',
        'due_date' => now()->subDays(2),
    ]);

    // Future pending task (should not be in overdue_tasks)
    Task::factory()->create([
        'event_id' => $event->id,
        'title' => 'Future Task',
        'assigned_role' => 'marketing_lead',
        'status' => 'pending',
        'due_date' => now()->addDays(2),
    ]);

    // Past completed task (should not be in overdue_tasks)
    Task::factory()->completed()->create([
        'event_id' => $event->id,
        'title' => 'Completed Past Task',
        'assigned_role' => 'operations',
        'due_date' => now()->subDays(3),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('redFlags.overdue_tasks', 1)
            ->where('redFlags.overdue_tasks.0.id', $overdueTask->id)
            ->where('redFlags.overdue_tasks.0.title', 'Overdue Critical Deck')
            ->where('kpi.overdue_tasks_count', 1)
        );
});

test('events further than 30 days out do not appear in the 30-day roadmap', function (): void {
    $user = User::factory()->create();

    // Event within 30 days
    $nearEvent = Event::factory()->create([
        'title' => 'Upcoming Event',
        'event_date' => now()->addDays(15),
    ]);

    // Event outside 30 days
    $farEvent = Event::factory()->create([
        'title' => 'Far Future Event',
        'event_date' => now()->addDays(45),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('upcomingEvents', 1)
            ->where('upcomingEvents.0.id', $nearEvent->id)
            ->where('kpi.active_events_count', 1)
        );
});
