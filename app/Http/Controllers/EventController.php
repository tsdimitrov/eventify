<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateSocialCopyAction;
use App\Actions\RescheduleEventTasksAction;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * Display a listing of the events.
     */
    public function index(Request $request): Response
    {
        $tabCounts = [
            'upcoming' => Event::upcoming()->count(),
            'past' => Event::past()->count(),
            'drafts' => Event::drafts()->count(),
            'cancelled' => Event::cancelled()->count(),
        ];

        $tab = (string) $request->query('tab', 'upcoming');

        $eventsQuery = match ($tab) {
            'past' => Event::past(),
            'drafts' => Event::drafts(),
            'cancelled' => Event::cancelled(),
            default => Event::upcoming(),
        };

        $events = $eventsQuery
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn (Builder $query): Builder => $query->where('status', 'completed'),
            ])
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Events/Index', [
            'events' => $events,
            'currentTab' => $tab,
            'tabCounts' => $tabCounts,
        ]);
    }

    /**
     * Show the form for creating a new event.
     */
    public function create(): Response
    {
        return Inertia::render('Events/Create');
    }

    /**
     * Store a newly created event in storage.
     */
    public function store(StoreEventRequest $request): RedirectResponse
    {
        $event = Event::create($request->validated());

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Event created successfully.');
    }

    /**
     * Display the specified event.
     */
    public function show(Event $event, GenerateSocialCopyAction $generateSocialCopyAction): Response
    {
        $event->load([
            'tasks' => fn ($query) => $query->orderBy('due_date', 'asc'),
        ]);

        $bannerAssetUrl = $event->tasks->firstWhere('assigned_role', 'designer')?->asset_url;
        $socialDrafts = $generateSocialCopyAction->execute($event);

        return Inertia::render('Events/Show', [
            'event' => $event,
            'socialDrafts' => $socialDrafts,
            'bannerAssetUrl' => $bannerAssetUrl,
        ]);
    }

    /**
     * Show the form for editing the specified event.
     */
    public function edit(Event $event): Response
    {
        return Inertia::render('Events/Edit', [
            'event' => $event,
        ]);
    }

    /**
     * Update the specified event in storage.
     */
    public function update(
        UpdateEventRequest $request,
        Event $event,
        RescheduleEventTasksAction $rescheduleAction,
    ): RedirectResponse {
        $originalDate = $event->event_date->copy();
        $validated = $request->validated();
        unset($validated['reschedule_tasks']);

        $event->update($validated);

        $message = 'Event updated successfully.';

        if ($request->boolean('reschedule_tasks') && $event->event_date->ne($originalDate)) {
            $count = $rescheduleAction->execute($event, $originalDate, $event->event_date);
            $message = "Event updated successfully. {$count} tasks rescheduled.";
        }

        return redirect()
            ->route('events.show', $event)
            ->with('success', $message);
    }

    /**
     * Remove the specified event from storage.
     */
    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return redirect()
            ->route('events.index')
            ->with('success', 'Event moved to archive.');
    }
}
