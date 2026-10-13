<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Task;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

test('reminders command with --dry-run operates strictly read-only without sending webhooks', function (): void {
    $event = Event::factory()->create([
        'title' => 'Quarterly Product Launch',
        'event_date' => now()->addDays(5),
    ]);

    Task::factory()->create([
        'event_id' => $event->id,
        'title' => 'Prepare Press Materials',
        'due_date' => now()->addDays(2),
        'status' => 'pending',
    ]);

    config([
        'services.google_chat.webhook_url' => 'https://chat.googleapis.com/v1/spaces/SPACE/messages',
        'services.google_chat.enabled' => true,
    ]);

    Http::fake();

    $this->artisan('events:send-daily-reminders', ['--dry-run' => true])
        ->expectsOutputToContain('[DRY-RUN] Daily briefing simulation')
        ->expectsOutputToContain('Quarterly Product Launch')
        ->assertSuccessful();

    Http::assertNothingSent();
});

test('reminders command without --dry-run delivers briefing card payload to Google Chat', function (): void {
    $webhookUrl = 'https://chat.googleapis.com/v1/spaces/SPACE/messages';
    config([
        'services.google_chat.webhook_url' => $webhookUrl,
        'services.google_chat.enabled' => true,
    ]);

    Http::fake([
        '*' => Http::response(['name' => 'spaces/SPACE/messages/digest123'], 200),
    ]);

    $event = Event::factory()->create([
        'title' => 'Annual Shareholder Meeting',
        'event_date' => now()->addDays(7),
    ]);

    Task::factory()->create([
        'event_id' => $event->id,
        'title' => 'Audit Financial Slides',
        'due_date' => now()->subDay(),
        'status' => 'pending',
    ]);

    $this->artisan('events:send-daily-reminders')
        ->expectsOutputToContain('Daily briefing dispatched')
        ->assertSuccessful();

    Http::assertSent(function (Request $request) use ($webhookUrl): bool {
        if ($request->url() !== $webhookUrl) {
            return false;
        }

        $data = $request->data();
        $card = $data['cardsV2'][0]['card'] ?? [];

        return isset($card['header']['title'])
            && $card['header']['title'] === 'Daily Operations Briefing';
    });
});

test('scheduled daily reminder task is registered and listed in schedule', function (): void {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('events:send-daily-reminders')
        ->assertSuccessful();
});
