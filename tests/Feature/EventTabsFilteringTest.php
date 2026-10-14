<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('get events defaults to upcoming events returning events where event_date is in future and excluding drafts and cancelled', function (): void {
    $user = User::factory()->create();

    // Upcoming scheduled events
    $upcomingEvent1 = Event::factory()->scheduled()->create([
        'title' => 'Upcoming Event Early',
        'event_date' => now()->addDays(2),
    ]);

    $upcomingEvent2 = Event::factory()->scheduled()->create([
        'title' => 'Upcoming Event Later',
        'event_date' => now()->addDays(10),
    ]);

    // Past event
    Event::factory()->scheduled()->create([
        'title' => 'Past Event',
        'event_date' => now()->subDays(5),
    ]);

    // Draft event (in future)
    Event::factory()->create([
        'title' => 'Draft Event',
        'status' => 'draft',
        'event_date' => now()->addDays(5),
    ]);

    // Cancelled event (in future)
    Event::factory()->create([
        'title' => 'Cancelled Event',
        'status' => 'cancelled',
        'event_date' => now()->addDays(7),
    ]);

    $response = $this->actingAs($user)->get(route('events.index'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Index')
            ->where('currentTab', 'upcoming')
            ->has('events.data', 2)
            ->where('events.data.0.id', $upcomingEvent1->id)
            ->where('events.data.1.id', $upcomingEvent2->id)
        );
});

test('get events with tab=past returns only past events ordered descending by date', function (): void {
    $user = User::factory()->create();

    $olderPastEvent = Event::factory()->scheduled()->create([
        'title' => 'Older Past Event',
        'event_date' => now()->subDays(10),
    ]);

    $recentPastEvent = Event::factory()->scheduled()->create([
        'title' => 'Recent Past Event',
        'event_date' => now()->subDays(2),
    ]);

    // Upcoming event
    Event::factory()->scheduled()->create([
        'title' => 'Future Event',
        'event_date' => now()->addDays(5),
    ]);

    // Past cancelled event
    Event::factory()->create([
        'title' => 'Past Cancelled Event',
        'status' => 'cancelled',
        'event_date' => now()->subDays(3),
    ]);

    // Past draft event
    Event::factory()->create([
        'title' => 'Past Draft Event',
        'status' => 'draft',
        'event_date' => now()->subDays(4),
    ]);

    $response = $this->actingAs($user)->get(route('events.index', ['tab' => 'past']));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Index')
            ->where('currentTab', 'past')
            ->has('events.data', 2)
            ->where('events.data.0.id', $recentPastEvent->id)
            ->where('events.data.1.id', $olderPastEvent->id)
        );
});

test('get events with tab=drafts returns events with status draft', function (): void {
    $user = User::factory()->create();

    $draft1 = Event::factory()->create([
        'title' => 'First Draft',
        'status' => 'draft',
        'created_at' => now()->subHours(2),
    ]);

    $draft2 = Event::factory()->create([
        'title' => 'Second Draft',
        'status' => 'draft',
        'created_at' => now()->subHour(),
    ]);

    // Scheduled event
    Event::factory()->scheduled()->create([
        'title' => 'Active Event',
        'event_date' => now()->addDays(3),
    ]);

    $response = $this->actingAs($user)->get(route('events.index', ['tab' => 'drafts']));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Index')
            ->where('currentTab', 'drafts')
            ->has('events.data', 2)
            ->where('events.data.0.id', $draft2->id)
            ->where('events.data.1.id', $draft1->id)
        );
});

test('get events with tab=cancelled returns events with status cancelled', function (): void {
    $user = User::factory()->create();

    $cancelled1 = Event::factory()->create([
        'title' => 'Cancelled Event 1',
        'status' => 'cancelled',
        'event_date' => now()->addDays(2),
    ]);

    $cancelled2 = Event::factory()->create([
        'title' => 'Cancelled Event 2',
        'status' => 'cancelled',
        'event_date' => now()->addDays(15),
    ]);

    // Scheduled event
    Event::factory()->scheduled()->create([
        'title' => 'Active Event',
        'event_date' => now()->addDays(5),
    ]);

    $response = $this->actingAs($user)->get(route('events.index', ['tab' => 'cancelled']));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Index')
            ->where('currentTab', 'cancelled')
            ->has('events.data', 2)
            ->where('events.data.0.id', $cancelled2->id)
            ->where('events.data.1.id', $cancelled1->id)
        );
});

test('tabCounts prop accurately calculates totals across all four categories regardless of the active tab', function (): void {
    $user = User::factory()->create();

    // 3 upcoming events
    Event::factory()->scheduled()->count(3)->create([
        'event_date' => now()->addDays(5),
    ]);

    // 2 past events
    Event::factory()->scheduled()->count(2)->create([
        'event_date' => now()->subDays(5),
    ]);

    // 4 draft events
    Event::factory()->count(4)->create([
        'status' => 'draft',
    ]);

    // 1 cancelled event
    Event::factory()->count(1)->create([
        'status' => 'cancelled',
    ]);

    // Check with default tab (upcoming)
    $responseUpcoming = $this->actingAs($user)->get(route('events.index'));

    $responseUpcoming->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('tabCounts.upcoming', 3)
            ->where('tabCounts.past', 2)
            ->where('tabCounts.drafts', 4)
            ->where('tabCounts.cancelled', 1)
        );

    // Check with tab=past
    $responsePast = $this->actingAs($user)->get(route('events.index', ['tab' => 'past']));

    $responsePast->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('tabCounts.upcoming', 3)
            ->where('tabCounts.past', 2)
            ->where('tabCounts.drafts', 4)
            ->where('tabCounts.cancelled', 1)
        );
});
