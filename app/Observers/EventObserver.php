<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\GenerateTasksForEventAction;
use App\Jobs\SendEventCreatedGoogleChatNotification;
use App\Models\Event;

class EventObserver
{
    /**
     * Create a new observer instance.
     */
    public function __construct(
        protected GenerateTasksForEventAction $generateTasksAction,
    ) {}

    /**
     * Handle the Event "created" event.
     */
    public function created(Event $event): void
    {
        $this->generateTasksAction->execute($event);

        SendEventCreatedGoogleChatNotification::dispatch($event);
    }
}
