<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\DemoSeeder;

test('DemoSeeder populates the database with showcase events and tasks', function (): void {
    $this->seed(DemoSeeder::class);

    // Verify test demo user
    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();

    // Verify exactly 3 showcase events
    $events = Event::withCount([
        'tasks',
        'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'completed'),
    ])->orderBy('event_date', 'asc')->get();

    expect($events)->toHaveCount(3);

    // Event 1: Conference (Cloud AI Summit) - earlier date (T+5)
    $conference = $events->firstWhere('type', 'conference');
    expect($conference)->not->toBeNull();
    expect($conference->title)->toBe('Enterprise Cloud & AI Summit 2026');
    expect($conference->tasks_count)->toBe(3);
    expect($conference->completed_tasks_count)->toBe(1);

    // Overdue task for Conference
    $overdueTask = $conference->tasks()->where('assigned_role', 'designer')->first();
    expect($overdueTask)->not->toBeNull();
    expect($overdueTask->status)->toBe('pending');
    expect($overdueTask->due_date->isPast())->toBeTrue();

    // Event 2: Webinar (Microservices) - T+12
    $webinar = $events->firstWhere('type', 'webinar');
    expect($webinar)->not->toBeNull();
    expect($webinar->title)->toBe('Building Resilient Microservices with Go & Kotlin');
    expect($webinar->tasks_count)->toBe(4);
    expect($webinar->completed_tasks_count)->toBe(1);

    // Completed designer task with Unsplash creative banner
    $bannerTask = $webinar->tasks()->where('assigned_role', 'designer')->first();
    expect($bannerTask)->not->toBeNull();
    expect($bannerTask->status)->toBe('completed');
    expect($bannerTask->asset_url)->toContain('images.unsplash.com');

    // In-progress copywriter task due tomorrow
    $copyTask = $webinar->tasks()->where('assigned_role', 'copywriter')->first();
    expect($copyTask)->not->toBeNull();
    expect($copyTask->status)->toBe('in_progress');
    expect($copyTask->due_date->isFuture())->toBeTrue();
    expect($copyTask->asset_url)->toContain('docs.google.com');

    // Event 3: Draft Internal Meeting (Hackathon) - T+25
    $internalMeeting = $events->firstWhere('type', 'internal_meeting');
    expect($internalMeeting)->not->toBeNull();
    expect($internalMeeting->status)->toBe('draft');
    expect($internalMeeting->tasks_count)->toBe(2);
    expect($internalMeeting->completed_tasks_count)->toBe(0);
});

test('DemoSeeder can be run multiple times cleanly without duplicate records or errors', function (): void {
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(Event::count())->toBe(3);
    expect(Task::count())->toBe(9);
});
