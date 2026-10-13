<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Event;
use App\Services\GoogleChatService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendEventCreatedGoogleChatNotification implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Event $event,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GoogleChatService $service): void
    {
        $service->sendEventCreatedCard($this->event);
    }
}
