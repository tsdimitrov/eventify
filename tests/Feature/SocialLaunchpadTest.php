<?php

declare(strict_types=1);

use App\Actions\GenerateSocialCopyAction;
use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('event show page passes structured socialDrafts containing LinkedIn, Twitter, and Email fields', function (): void {
    $user = User::factory()->create();

    $event = Event::factory()->create([
        'title' => 'AI Breakthroughs Summit',
        'subtitle' => 'Exploring Modern LLMs',
        'description' => 'A deep dive into frontier models and agentic workflows.',
        'type' => 'conference',
        'event_date' => now()->addDays(20),
        'metadata' => [
            'registration_url' => 'https://example.com/register/ai-summit',
        ],
    ]);

    $response = $this->actingAs($user)->get(route('events.show', $event));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('event.id', $event->id)
            ->has('socialDrafts.linkedin', fn (Assert $item) => $item
                ->where('platform', 'LinkedIn')
                ->where('character_limit', 3000)
                ->whereContains('hashtags', '#Webinar')
                ->where('text', fn (string $text) => str_contains($text, 'AI Breakthroughs Summit')
                    && str_contains($text, 'Exploring Modern LLMs')
                    && str_contains($text, 'https://example.com/register/ai-summit')
                )
                ->etc()
            )
            ->has('socialDrafts.twitter', fn (Assert $item) => $item
                ->where('platform', 'X / Twitter')
                ->where('character_limit', 280)
                ->whereContains('hashtags', '#Tech')
                ->where('text', fn (string $text) => str_contains($text, 'AI Breakthroughs Summit')
                    && str_contains($text, 'https://example.com/register/ai-summit')
                )
                ->etc()
            )
            ->has('socialDrafts.email', fn (Assert $item) => $item
                ->where('platform', 'Newsletter Blurb')
                ->where('subject', "You're Invited: AI Breakthroughs Summit")
                ->where('text', fn (string $text) => str_contains($text, 'AI Breakthroughs Summit')
                    && str_contains($text, 'Hi {{first_name}}')
                    && str_contains($text, 'https://example.com/register/ai-summit')
                )
                ->etc()
            )
        );
});

test('event show page correctly pulls bannerAssetUrl from the designer task when available', function (): void {
    $user = User::factory()->create();

    $event = Event::factory()->create([
        'title' => 'Design Sprint 2026',
        'event_date' => now()->addDays(15),
    ]);

    $bannerUrl = 'https://assets.example.com/campaigns/design-sprint-banner.png';

    Task::factory()->create([
        'event_id' => $event->id,
        'assigned_role' => 'designer',
        'title' => 'Design social graphics and promo banners',
        'asset_url' => $bannerUrl,
    ]);

    Task::factory()->create([
        'event_id' => $event->id,
        'assigned_role' => 'copywriter',
        'title' => 'Draft email sequences',
        'asset_url' => 'https://docs.google.com/document/d/copy-doc',
    ]);

    $response = $this->actingAs($user)->get(route('events.show', $event));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('event.id', $event->id)
            ->where('bannerAssetUrl', $bannerUrl)
        );
});

test('bannerAssetUrl is null when no designer task exists or designer task has no asset_url', function (): void {
    $user = User::factory()->create();

    $event = Event::factory()->create([
        'title' => 'Internal Retrospective',
        'event_date' => now()->addDays(5),
    ]);

    Task::factory()->create([
        'event_id' => $event->id,
        'assigned_role' => 'designer',
        'title' => 'Design presentation slides',
        'asset_url' => null,
    ]);

    $response = $this->actingAs($user)->get(route('events.show', $event));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('event.id', $event->id)
            ->where('bannerAssetUrl', null)
        );
});

test('GenerateSocialCopyAction falls back to event show url when registration_url metadata is absent', function (): void {
    $event = Event::factory()->create([
        'title' => 'Community Q&A Session',
        'event_date' => now()->addDays(8),
        'metadata' => null,
    ]);

    $action = new GenerateSocialCopyAction;
    $drafts = $action->execute($event);

    $expectedFallbackUrl = url("/events/{$event->id}");

    expect($drafts['linkedin']['text'])->toContain($expectedFallbackUrl);
    expect($drafts['twitter']['text'])->toContain($expectedFallbackUrl);
    expect($drafts['email']['text'])->toContain($expectedFallbackUrl);
});
