<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\DemoSeeder;

test('DemoSeeder populates the database with showcase events and tasks across all lifecycle stages', function (): void {
    $this->seed(DemoSeeder::class);

    // Verify test demo user
    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();

    // Verify exactly 5 showcase events
    $events = Event::withCount([
        'tasks',
        'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'completed'),
    ])->get();

    expect($events)->toHaveCount(5);

    // 1. Upcoming Flagship Webinar
    $webinar = $events->firstWhere('title', 'Building Resilient Microservices with Go & Kotlin');
    expect($webinar)->not->toBeNull()
        ->and($webinar->status)->toBe('scheduled')
        ->and($webinar->type)->toBe('webinar')
        ->and($webinar->tasks_count)->toBe(6)
        ->and($webinar->completed_tasks_count)->toBe(1);

    // Ad-hoc task in Webinar
    $adHocTask = $webinar->tasks()->where('title', 'Live studio tech run with guest speakers')->first();
    expect($adHocTask)->not->toBeNull()
        ->and($adHocTask->assigned_role)->toBe('operations')
        ->and($adHocTask->status)->toBe('pending');

    // T-plus task in Webinar
    $postEventTask = $webinar->tasks()->where('title', 'Upload session recording & slides to Drive/YouTube')->first();
    expect($postEventTask)->not->toBeNull()
        ->and($postEventTask->due_date->greaterThan($webinar->event_date))->toBeTrue();

    // Completed designer banner asset
    $bannerTask = $webinar->tasks()->where('assigned_role', 'designer')->first();
    expect($bannerTask)->not->toBeNull()
        ->and($bannerTask->status)->toBe('completed')
        ->and($bannerTask->asset_url)->toContain('images.unsplash.com');

    // 2. High-Urgency Conference (Red alert overdue task)
    $conference = $events->firstWhere('title', 'Enterprise Cloud & AI Summit 2026');
    expect($conference)->not->toBeNull()
        ->and($conference->status)->toBe('scheduled')
        ->and($conference->tasks_count)->toBe(3);

    $overdueTask = $conference->tasks()->where('assigned_role', 'designer')->first();
    expect($overdueTask)->not->toBeNull()
        ->and($overdueTask->status)->toBe('pending')
        ->and($overdueTask->due_date->isPast())->toBeTrue();

    $dueSoonTask = $conference->tasks()->where('assigned_role', 'operations')->first();
    expect($dueSoonTask)->not->toBeNull()
        ->and($dueSoonTask->status)->toBe('in_progress')
        ->and($dueSoonTask->due_date->isFuture())->toBeTrue();

    // 3. Past / Archived Event (All tasks completed)
    $pastEvent = $events->firstWhere('title', 'Q3 Backend Observability & Distributed Tracing Deep Dive');
    expect($pastEvent)->not->toBeNull()
        ->and($pastEvent->status)->toBe('completed')
        ->and($pastEvent->event_date->isPast())->toBeTrue()
        ->and($pastEvent->tasks_count)->toBe(4)
        ->and($pastEvent->completed_tasks_count)->toBe(4);

    // 4. Draft Planning Event
    $draftEvent = $events->firstWhere('title', '2027 Strategic Tech Roadmap & Hackathon Planning');
    expect($draftEvent)->not->toBeNull()
        ->and($draftEvent->status)->toBe('draft')
        ->and($draftEvent->tasks_count)->toBe(1);

    // 5. Cancelled Event
    $cancelledEvent = $events->firstWhere('title', 'Legacy Monolith Decomposition Workshop');
    expect($cancelledEvent)->not->toBeNull()
        ->and($cancelledEvent->status)->toBe('cancelled')
        ->and($cancelledEvent->metadata)->toHaveKey('cancellation_reason');

    // Tab counts verification
    expect(Event::upcoming()->count())->toBe(2)
        ->and(Event::past()->count())->toBe(1)
        ->and(Event::drafts()->count())->toBe(1)
        ->and(Event::cancelled()->count())->toBe(1);
});

test('DemoSeeder can be run multiple times cleanly without duplicate records or errors', function (): void {
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(Event::count())->toBe(5)
        ->and(Task::count())->toBe(15);
});
