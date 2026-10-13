<?php

declare(strict_types=1);

use App\Actions\GenerateTasksForEventAction;
use App\Models\Event;
use App\Models\Task;
use App\Models\TaskTemplate;
use Database\Seeders\TaskTemplateSeeder;

test('creating an event with type webinar automatically triggers task creation', function (): void {
    $this->seed(TaskTemplateSeeder::class);

    $event = Event::create([
        'title' => 'Scaling Laravel Applications',
        'subtitle' => 'Performance & Architecture',
        'description' => 'A deep dive into high-throughput Laravel architecture.',
        'event_date' => '2026-11-15 14:00:00',
        'type' => 'webinar',
    ]);

    expect($event->tasks()->count())->toBe(4);

    $tasks = $event->tasks()->get();
    expect($tasks->pluck('title')->all())->toContain(
        'Design promo banners & thumbnail',
        'Draft LinkedIn & Twitter copy',
        'Send email invitation blast',
        'Send final 24h reminder notification',
    );

    expect($tasks->every(fn (Task $task): bool => $task->status === 'pending'))->toBeTrue();
});

test('due_date math accurately handles negative offsets relative to event_date', function (): void {
    TaskTemplate::create([
        'event_type' => 'workshop',
        'title' => 'Early Bird Promo Asset',
        'description_template' => 'Early promo for {{title}} on {{date}}',
        'offset_days' => -14,
        'assigned_role' => 'designer',
    ]);

    TaskTemplate::create([
        'event_type' => 'workshop',
        'title' => 'Post-Event Survey',
        'description_template' => 'Feedback for {{title}}',
        'offset_days' => 2,
        'assigned_role' => 'operations',
    ]);

    $event = Event::create([
        'title' => 'Advanced Vue & Inertia Workshop',
        'event_date' => '2026-11-20 10:00:00',
        'type' => 'workshop',
    ]);

    $earlyTask = $event->tasks()->where('title', 'Early Bird Promo Asset')->firstOrFail();
    $postTask = $event->tasks()->where('title', 'Post-Event Survey')->firstOrFail();

    expect($earlyTask->due_date->format('Y-m-d H:i:s'))->toBe('2026-11-06 10:00:00')
        ->and($earlyTask->description)->toBe('Early promo for Advanced Vue & Inertia Workshop on 2026-11-20');

    expect($postTask->due_date->format('Y-m-d H:i:s'))->toBe('2026-11-22 10:00:00')
        ->and($postTask->description)->toBe('Feedback for Advanced Vue & Inertia Workshop');
});

test('idempotency ensures invoking action twice does not produce duplicate tasks unless force is true', function (): void {
    $this->seed(TaskTemplateSeeder::class);

    $event = Event::create([
        'title' => 'Annual Developer Conference',
        'event_date' => '2026-12-10 09:00:00',
        'type' => 'conference',
    ]);

    $action = app(GenerateTasksForEventAction::class);

    // Initial event creation auto-generated 3 tasks
    expect($event->tasks()->count())->toBe(3);

    // Second execution with force = false should not create duplicates
    $existingTasks = $action->execute($event, force: false);
    expect($existingTasks)->toHaveCount(3)
        ->and($event->tasks()->count())->toBe(3);

    // Third execution with force = true should create a fresh batch
    $forcedTasks = $action->execute($event, force: true);
    expect($forcedTasks)->toHaveCount(3)
        ->and($event->tasks()->count())->toBe(6);
});

test('event with unknown or template-less type creates zero tasks without throwing exceptions', function (): void {
    $this->seed(TaskTemplateSeeder::class);

    $event = Event::create([
        'title' => 'Ad-hoc Internal Chat',
        'event_date' => '2026-10-30 15:00:00',
        'type' => 'non_existent_event_type',
    ]);

    expect($event->tasks()->count())->toBe(0);

    $action = app(GenerateTasksForEventAction::class);
    $result = $action->execute($event);

    expect($result)->toBeEmpty()
        ->and($event->tasks()->count())->toBe(0);
});
