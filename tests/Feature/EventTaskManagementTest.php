<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Task;
use App\Models\TaskTemplate;
use Carbon\CarbonInterface;

test('event can be created with fillable attributes and casts event_date and metadata', function (): void {
    $event = Event::create([
        'title' => 'Q4 Tech Summit',
        'subtitle' => 'Innovations in AI & Cloud',
        'description' => 'Annual conference discussing latest trends.',
        'event_date' => '2026-11-20 09:00:00',
        'type' => 'conference',
        'status' => 'draft',
        'metadata' => [
            'target_audience' => 'Engineers & Architects',
            'drive_link' => 'https://drive.google.com/tech-summit',
            'tags' => ['tech', 'cloud', 'ai'],
        ],
    ]);

    expect($event->id)->not->toBeNull()
        ->and($event->title)->toBe('Q4 Tech Summit')
        ->and($event->subtitle)->toBe('Innovations in AI & Cloud')
        ->and($event->description)->toBe('Annual conference discussing latest trends.')
        ->and($event->event_date)->toBeInstanceOf(CarbonInterface::class)
        ->and($event->event_date->format('Y-m-d H:i:s'))->toBe('2026-11-20 09:00:00')
        ->and($event->type)->toBe('conference')
        ->and($event->status)->toBe('draft')
        ->and($event->metadata)->toBeArray()
        ->and($event->metadata['tags'])->toBe(['tech', 'cloud', 'ai'])
        ->and($event->metadata['target_audience'])->toBe('Engineers & Architects');
});

test('event status defaults to draft', function (): void {
    $event = Event::create([
        'title' => 'Weekly Sync',
        'event_date' => '2026-10-15 14:00:00',
        'type' => 'internal_meeting',
    ]);

    expect($event->status)->toBe('draft');
});

test('task template can be created and casts offset_days to integer', function (): void {
    $template = TaskTemplate::create([
        'event_type' => 'webinar',
        'title' => 'Banner & Social Visuals',
        'description_template' => 'Create banner assets for social promo.',
        'offset_days' => '-14',
        'assigned_role' => 'designer',
    ]);

    expect($template->id)->not->toBeNull()
        ->and($template->event_type)->toBe('webinar')
        ->and($template->title)->toBe('Banner & Social Visuals')
        ->and($template->offset_days)->toBeInt()
        ->and($template->offset_days)->toBe(-14)
        ->and($template->assigned_role)->toBe('designer');
});

test('task can be created, casts due_date, and belongs to an event', function (): void {
    $event = Event::factory()->create([
        'title' => 'Spring Webinar',
        'event_date' => '2026-04-10 16:00:00',
        'type' => 'webinar',
    ]);

    $task = Task::create([
        'event_id' => $event->id,
        'title' => 'Prepare Slide Deck',
        'description' => 'Draft the introduction and key talking points.',
        'due_date' => '2026-04-03 12:00:00',
        'status' => 'pending',
        'assigned_role' => 'copywriter',
        'assigned_to' => 'writer@example.com',
        'asset_url' => 'https://docs.google.com/presentation/d/12345',
    ]);

    expect($task->id)->not->toBeNull()
        ->and($task->event_id)->toBe($event->id)
        ->and($task->title)->toBe('Prepare Slide Deck')
        ->and($task->due_date)->toBeInstanceOf(CarbonInterface::class)
        ->and($task->due_date->format('Y-m-d H:i:s'))->toBe('2026-04-03 12:00:00')
        ->and($task->status)->toBe('pending')
        ->and($task->assigned_role)->toBe('copywriter')
        ->and($task->assigned_to)->toBe('writer@example.com')
        ->and($task->asset_url)->toBe('https://docs.google.com/presentation/d/12345')
        ->and($task->event->id)->toBe($event->id);

    expect($event->tasks)->toHaveCount(1)
        ->and($event->tasks->first()->id)->toBe($task->id);
});

test('task status defaults to pending', function (): void {
    $event = Event::factory()->create();

    $task = Task::create([
        'event_id' => $event->id,
        'title' => 'Coordinate Catering',
        'due_date' => '2026-05-01 10:00:00',
    ]);

    expect($task->status)->toBe('pending');
});

test('deleting an event cascades and deletes associated tasks', function (): void {
    $event = Event::factory()->create();
    $task1 = Task::factory()->create(['event_id' => $event->id]);
    $task2 = Task::factory()->create(['event_id' => $event->id]);

    expect(Task::where('event_id', $event->id)->count())->toBe(2);

    $event->delete();

    expect(Task::find($task1->id))->toBeNull()
        ->and(Task::find($task2->id))->toBeNull();
});

test('factories generate valid models with states', function (): void {
    $event = Event::factory()->scheduled()->webinar()->create();
    expect($event->status)->toBe('scheduled')
        ->and($event->type)->toBe('webinar');

    $template = TaskTemplate::factory()->forEventType('conference')->forRole('operations')->create();
    expect($template->event_type)->toBe('conference')
        ->and($template->assigned_role)->toBe('operations');

    $task = Task::factory()->completed()->create(['event_id' => $event->id]);
    expect($task->status)->toBe('completed')
        ->and($task->event_id)->toBe($event->id);
});
