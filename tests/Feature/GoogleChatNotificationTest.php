<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Task;
use App\Services\GoogleChatService;
use Database\Seeders\TaskTemplateSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

test('event creation triggers Google Chat notification and dispatches Card v2 payload', function (): void {
    $webhookUrl = 'https://chat.googleapis.com/v1/spaces/SPACE_ID/messages?key=KEY&token=TOKEN';
    config([
        'services.google_chat.webhook_url' => $webhookUrl,
        'services.google_chat.enabled' => true,
    ]);

    Http::fake([
        'https://chat.googleapis.com/*' => Http::response(['name' => 'spaces/SPACE_ID/messages/MSG_ID'], 200),
    ]);

    $this->seed(TaskTemplateSeeder::class);

    $event = Event::factory()->create([
        'title' => 'Global Developer Summit',
        'type' => 'conference',
        'event_date' => now()->addDays(30),
    ]);

    Http::assertSent(function (Request $request) use ($webhookUrl, $event): bool {
        if ($request->url() !== $webhookUrl) {
            return false;
        }

        $body = $request->data();

        if (! isset($body['cardsV2'][0]['card'])) {
            return false;
        }

        $card = $body['cardsV2'][0]['card'];

        return $card['header']['title'] === $event->title
            && str_contains($card['header']['subtitle'], $event->event_date->format('M j, Y'))
            && $body['cardsV2'][0]['cardId'] === 'event-created-'.$event->id;
    });
});

test('card payload contains event details, formatted task titles, and action url', function (): void {
    $event = Event::factory()->create([
        'title' => 'Kubernetes Workshop',
        'type' => 'workshop',
        'event_date' => now()->addDays(14),
        'description' => 'Hands-on practical training for container orchestration.',
    ]);

    Task::factory()->create([
        'event_id' => $event->id,
        'title' => 'Prepare slide deck',
        'assigned_role' => 'presenter',
        'due_date' => now()->addDays(7),
    ]);

    Task::factory()->create([
        'event_id' => $event->id,
        'title' => 'Configure demo cluster',
        'assigned_role' => 'devops_engineer',
        'due_date' => now()->addDays(10),
    ]);

    $webhookUrl = 'https://chat.googleapis.com/v1/spaces/SPACE_ID/messages';
    config([
        'services.google_chat.webhook_url' => $webhookUrl,
        'services.google_chat.enabled' => true,
    ]);

    Http::fake([
        '*' => Http::response(['status' => 'ok'], 200),
    ]);

    $service = app(GoogleChatService::class);
    $result = $service->sendEventCreatedCard($event);

    expect($result)->toBeTrue();

    Http::assertSent(function (Request $request) use ($event): bool {
        $body = $request->data();
        $card = $body['cardsV2'][0]['card'];

        // Section 1: Summary
        $summarySection = $card['sections'][0];
        expect($summarySection['header'])->toBe('Event Summary');

        // Section 2: Tasks
        $taskSection = $card['sections'][1];
        expect($taskSection['header'])->toBe('Generated Action Items');
        $widgets = $taskSection['widgets'];
        expect($widgets)->toHaveCount(2);
        expect($widgets[0]['decoratedText']['text'])->toBe('Prepare slide deck');
        expect($widgets[0]['decoratedText']['topLabel'])->toBe('PRESENTER');
        expect($widgets[1]['decoratedText']['text'])->toBe('Configure demo cluster');
        expect($widgets[1]['decoratedText']['topLabel'])->toBe('DEVOPS ENGINEER');

        // Section 3: Action Button
        $actionSection = $card['sections'][2];
        $button = $actionSection['widgets'][0]['buttonList']['buttons'][0];
        expect($button['text'])->toBe('View Event & Tasks');
        expect($button['onClick']['openLink']['url'])->toBe(route('events.show', $event));

        return true;
    });
});

test('service gracefully handles missing webhook URL without throwing or making requests', function (): void {
    config([
        'services.google_chat.webhook_url' => '',
        'services.google_chat.enabled' => true,
    ]);

    Http::fake();
    Log::spy();

    $event = Event::factory()->create([
        'title' => 'Internal Standup',
        'type' => 'internal_meeting',
    ]);

    $service = app(GoogleChatService::class);
    $result = $service->sendEventCreatedCard($event);

    expect($result)->toBeTrue();
    Http::assertNothingSent();

    Log::shouldHaveReceived('info')
        ->with('Google Chat notifications are disabled or webhook URL is not configured.');
});

test('service gracefully handles disabled notifications', function (): void {
    config([
        'services.google_chat.webhook_url' => 'https://chat.googleapis.com/test',
        'services.google_chat.enabled' => false,
    ]);

    Http::fake();
    Log::spy();

    $event = Event::factory()->create([
        'title' => 'Disabled Notification Event',
    ]);

    $service = app(GoogleChatService::class);
    $result = $service->sendEventCreatedCard($event);

    expect($result)->toBeTrue();
    Http::assertNothingSent();

    Log::shouldHaveReceived('info')
        ->with('Google Chat notifications are disabled or webhook URL is not configured.');
});

test('service logs warning and returns false when Google Chat webhook returns non-200 response', function (): void {
    config([
        'services.google_chat.webhook_url' => 'https://chat.googleapis.com/test',
        'services.google_chat.enabled' => true,
    ]);

    Http::fake([
        '*' => Http::response(['error' => 'Invalid space webhook'], 403),
    ]);

    Log::spy();

    $service = app(GoogleChatService::class);
    $result = $service->sendRaw(['test' => 'data']);

    expect($result)->toBeFalse();

    Log::shouldHaveReceived('warning')
        ->with('Google Chat webhook request returned an unsuccessful response.', Mockery::on(function (array $context): bool {
            return $context['status'] === 403;
        }));
});

test('google-chat:test command warns when webhook URL is not configured', function (): void {
    config([
        'services.google_chat.webhook_url' => '',
        'services.google_chat.enabled' => true,
    ]);

    $this->artisan('google-chat:test')
        ->expectsOutputToContain('Google Chat webhook URL is not configured')
        ->assertFailed();
});

test('google-chat:test command warns when notifications are disabled', function (): void {
    config([
        'services.google_chat.webhook_url' => 'https://chat.googleapis.com/test',
        'services.google_chat.enabled' => false,
    ]);

    $this->artisan('google-chat:test')
        ->expectsOutputToContain('Google Chat notifications are disabled')
        ->assertFailed();
});

test('google-chat:test command successfully dispatches test card when configured', function (): void {
    $webhookUrl = 'https://chat.googleapis.com/v1/spaces/SPACE/messages';
    config([
        'services.google_chat.webhook_url' => $webhookUrl,
        'services.google_chat.enabled' => true,
    ]);

    Http::fake([
        '*' => Http::response(['name' => 'spaces/SPACE/messages/123'], 200),
    ]);

    $this->artisan('google-chat:test')
        ->expectsOutputToContain('Test notification sent successfully to Google Chat!')
        ->assertSuccessful();

    Http::assertSent(function (Request $request) use ($webhookUrl): bool {
        return $request->url() === $webhookUrl
            && isset($request->data()['cardsV2'][0]['card']['header']['title'])
            && $request->data()['cardsV2'][0]['card']['header']['title'] === 'Eventify Webhook Test';
    });
});
