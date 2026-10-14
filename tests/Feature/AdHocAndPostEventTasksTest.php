<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Task;
use App\Models\TaskTemplate;
use App\Models\User;
use Database\Seeders\TaskTemplateSeeder;

test('task template seeder populates templates with positive offset days and generates tasks after the event date', function (): void {
    $this->seed(TaskTemplateSeeder::class);

    // Verify positive offset templates exist for webinar and conference
    $postWebinarTemplates = TaskTemplate::where('event_type', 'webinar')
        ->where('offset_days', '>', 0)
        ->get();

    expect($postWebinarTemplates->count())->toBeGreaterThanOrEqual(2)
        ->and($postWebinarTemplates->pluck('title')->all())->toContain(
            'Upload session recording & slides to Drive/YouTube',
            'Dispatch attendee follow-up & feedback survey',
        );

    $postConfTemplates = TaskTemplate::where('event_type', 'conference')
        ->where('offset_days', '>', 0)
        ->get();

    expect($postConfTemplates->count())->toBeGreaterThanOrEqual(2)
        ->and($postConfTemplates->pluck('title')->all())->toContain(
            'Publish post-event photo gallery & executive recap',
            'Finalize sponsor metrics & lead attribution report',
        );

    // Verify event creation generates post-event tasks with due dates in future relative to event_date
    $event = Event::create([
        'title' => 'Future of AI Webinar',
        'event_date' => '2026-11-20 15:00:00',
        'type' => 'webinar',
    ]);

    $uploadTask = $event->tasks()->where('title', 'Upload session recording & slides to Drive/YouTube')->firstOrFail();
    $surveyTask = $event->tasks()->where('title', 'Dispatch attendee follow-up & feedback survey')->firstOrFail();

    expect($uploadTask->due_date->format('Y-m-d H:i:s'))->toBe('2026-11-21 15:00:00')
        ->and($uploadTask->assigned_role)->toBe('operations')
        ->and($uploadTask->status)->toBe('pending');

    expect($surveyTask->due_date->format('Y-m-d H:i:s'))->toBe('2026-11-22 15:00:00')
        ->and($surveyTask->assigned_role)->toBe('marketing_lead')
        ->and($surveyTask->status)->toBe('pending');
});

test('post tasks validates input and persists an ad-hoc task linked to the specified event', function (): void {
    $user = User::factory()->create();
    $event = Event::factory()->create([
        'event_date' => now()->addDays(10),
    ]);

    $payload = [
        'event_id' => $event->id,
        'title' => 'Emergency Security Briefing Slide',
        'description' => 'Prepare 2-page briefing on zero-day vulnerability for attendees.',
        'due_date' => now()->addDays(8)->format('Y-m-d H:i:s'),
        'assigned_role' => 'operations',
        'assigned_to' => 'security@eventify.test',
        'asset_url' => 'https://drive.google.com/file/d/ad-hoc-briefing',
    ];

    $response = $this->actingAs($user)->post(route('tasks.store'), $payload);

    $response->assertRedirect()
        ->assertSessionHas('success', 'Custom task added successfully.');

    $task = Task::where('title', 'Emergency Security Briefing Slide')->firstOrFail();

    expect($task->event_id)->toBe($event->id)
        ->and($task->status)->toBe('pending')
        ->and($task->assigned_role)->toBe('operations')
        ->and($task->assigned_to)->toBe('security@eventify.test')
        ->and($task->asset_url)->toBe('https://drive.google.com/file/d/ad-hoc-briefing')
        ->and($task->description)->toBe('Prepare 2-page briefing on zero-day vulnerability for attendees.');
});

test('unauthorized or missing event_id fails validation', function (): void {
    $user = User::factory()->create();

    // Guest request should redirect to login
    $this->post(route('tasks.store'), [
        'event_id' => 1,
        'title' => 'Test Task',
        'due_date' => now()->format('Y-m-d H:i:s'),
        'assigned_role' => 'operations',
    ])->assertRedirect(route('login'));

    // Missing event_id
    $responseMissing = $this->actingAs($user)->post(route('tasks.store'), [
        'title' => 'Orphan Task',
        'due_date' => now()->format('Y-m-d H:i:s'),
        'assigned_role' => 'operations',
    ]);
    $responseMissing->assertSessionHasErrors(['event_id']);

    // Non-existent event_id
    $responseNonExistent = $this->actingAs($user)->post(route('tasks.store'), [
        'event_id' => 999999,
        'title' => 'Non Existent Task',
        'due_date' => now()->format('Y-m-d H:i:s'),
        'assigned_role' => 'operations',
    ]);
    $responseNonExistent->assertSessionHasErrors(['event_id']);

    // Missing required fields
    $responseEmpty = $this->actingAs($user)->post(route('tasks.store'), []);
    $responseEmpty->assertSessionHasErrors(['event_id', 'title', 'due_date', 'assigned_role']);
});
