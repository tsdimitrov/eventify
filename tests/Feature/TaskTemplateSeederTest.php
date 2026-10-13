<?php

declare(strict_types=1);

use App\Models\TaskTemplate;
use Database\Seeders\TaskTemplateSeeder;

test('task template seeder populates webinar and conference templates', function (): void {
    $this->seed(TaskTemplateSeeder::class);

    $webinarTemplates = TaskTemplate::where('event_type', 'webinar')->get();
    expect($webinarTemplates->count())->toBeGreaterThanOrEqual(4);

    expect(TaskTemplate::where([
        'event_type' => 'webinar',
        'title' => 'Banner & Social Visuals',
        'offset_days' => -14,
        'assigned_role' => 'designer',
    ])->exists())->toBeTrue();

    expect(TaskTemplate::where([
        'event_type' => 'webinar',
        'title' => 'Draft Promo Article & Social Copy',
        'offset_days' => -10,
        'assigned_role' => 'copywriter',
    ])->exists())->toBeTrue();

    expect(TaskTemplate::where([
        'event_type' => 'webinar',
        'title' => 'Email Invitation Blast',
        'offset_days' => -5,
        'assigned_role' => 'marketing_lead',
    ])->exists())->toBeTrue();

    expect(TaskTemplate::where([
        'event_type' => 'webinar',
        'title' => 'Final Reminder Notification',
        'offset_days' => -1,
        'assigned_role' => 'marketing_lead',
    ])->exists())->toBeTrue();

    $conferenceTemplates = TaskTemplate::where('event_type', 'conference')->get();
    expect($conferenceTemplates->count())->toBeGreaterThanOrEqual(3);

    expect(TaskTemplate::where([
        'event_type' => 'conference',
        'title' => 'Keynote Assets & Slide Deck Template',
        'offset_days' => -21,
        'assigned_role' => 'designer',
    ])->exists())->toBeTrue();

    expect(TaskTemplate::where([
        'event_type' => 'conference',
        'title' => 'Agenda Publication & PR Post',
        'offset_days' => -14,
        'assigned_role' => 'copywriter',
    ])->exists())->toBeTrue();

    expect(TaskTemplate::where([
        'event_type' => 'conference',
        'title' => 'Logistics & Venue Confirmation Check',
        'offset_days' => -7,
        'assigned_role' => 'operations',
    ])->exists())->toBeTrue();
});

test('task template seeder is idempotent', function (): void {
    $this->seed(TaskTemplateSeeder::class);
    $initialCount = TaskTemplate::count();

    $this->seed(TaskTemplateSeeder::class);
    expect(TaskTemplate::count())->toBe($initialCount);
});
