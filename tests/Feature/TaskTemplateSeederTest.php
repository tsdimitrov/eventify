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
        'title' => 'Design promo banners & thumbnail',
        'offset_days' => -14,
        'assigned_role' => 'designer',
    ])->exists())->toBeTrue();

    expect(TaskTemplate::where([
        'event_type' => 'webinar',
        'title' => 'Draft LinkedIn & Twitter copy',
        'offset_days' => -10,
        'assigned_role' => 'copywriter',
    ])->exists())->toBeTrue();

    expect(TaskTemplate::where([
        'event_type' => 'webinar',
        'title' => 'Send email invitation blast',
        'offset_days' => -5,
        'assigned_role' => 'marketing_lead',
    ])->exists())->toBeTrue();

    expect(TaskTemplate::where([
        'event_type' => 'webinar',
        'title' => 'Send final 24h reminder notification',
        'offset_days' => -1,
        'assigned_role' => 'marketing_lead',
    ])->exists())->toBeTrue();

    $conferenceTemplates = TaskTemplate::where('event_type', 'conference')->get();
    expect($conferenceTemplates->count())->toBeGreaterThanOrEqual(3);

    expect(TaskTemplate::where([
        'event_type' => 'conference',
        'title' => 'Prepare speaker decks & visual assets',
        'offset_days' => -21,
        'assigned_role' => 'designer',
    ])->exists())->toBeTrue();

    expect(TaskTemplate::where([
        'event_type' => 'conference',
        'title' => 'Publish agenda & press release',
        'offset_days' => -14,
        'assigned_role' => 'copywriter',
    ])->exists())->toBeTrue();

    expect(TaskTemplate::where([
        'event_type' => 'conference',
        'title' => 'Venue tech check & run-of-show review',
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
