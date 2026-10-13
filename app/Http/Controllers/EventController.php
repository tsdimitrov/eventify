<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateSocialCopyAction;
use App\Http\Requests\StoreEventRequest;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * Display a listing of the events.
     */
    public function index(): Response
    {
        $events = Event::query()
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn (Builder $query): Builder => $query->where('status', 'completed'),
            ])
            ->orderBy('event_date', 'asc')
            ->get();

        return Inertia::render('Events/Index', [
            'events' => $events,
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
}
