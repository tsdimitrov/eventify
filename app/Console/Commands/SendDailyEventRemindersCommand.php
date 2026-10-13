<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Task;
use App\Services\GoogleChatService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('events:send-daily-reminders {--dry-run : Run in dry-run mode without sending external notifications}')]
#[Description('Send daily briefing and event deadline reminders to Google Chat')]
class SendDailyEventRemindersCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(GoogleChatService $service): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $upcomingEvents = Event::query()
            ->whereBetween('event_date', [now(), now()->addDays(14)])
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'completed'),
                'tasks as overdue_tasks_count' => fn ($q) => $q->whereIn('status', ['pending', 'in_progress'])->where('due_date', '<', now()),
            ])
            ->orderBy('event_date', 'asc')
            ->get();

        $overdueTasksCount = Task::query()
            ->where('due_date', '<', now())
            ->where('status', '!=', 'completed')
            ->count();

        $pendingTasksCount = Task::query()
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        if ($isDryRun) {
            $this->info("[DRY-RUN] Daily briefing simulation: {$upcomingEvents->count()} upcoming events found.");
            $this->line("• Total pending tasks: {$pendingTasksCount}");
            $this->line("• Total overdue tasks: {$overdueTasksCount}");

            $rows = [];
            foreach ($upcomingEvents as $event) {
                $rows[] = [
                    $event->id,
                    $event->title,
                    $event->event_date->format('Y-m-d H:i'),
                    ($event->completed_tasks_count ?? 0).' / '.($event->tasks_count ?? 0),
                    $event->overdue_tasks_count ?? 0,
                ];
            }

            if (! empty($rows)) {
                $this->table(['ID', 'Event Title', 'Date', 'Tasks Done', 'Overdue'], $rows);
            }

            $this->info('[DRY-RUN] Completed without dispatching external notifications.');

            return self::SUCCESS;
        }

        $digestPayload = [
            'cardsV2' => [
                [
                    'cardId' => 'daily-reminders-'.now()->format('Y-m-d'),
                    'card' => [
                        'header' => [
                            'title' => 'Daily Operations Briefing',
                            'subtitle' => '📅 '.now()->format('l, F j, Y'),
                            'imageUrl' => 'https://fonts.gstatic.com/s/i/short-term/release/googlesymbols/notifications_active/default/48px.png',
                            'imageType' => 'CIRCLE',
                        ],
                        'sections' => [
                            [
                                'header' => 'Pipeline Overview',
                                'widgets' => [
                                    [
                                        'decoratedText' => [
                                            'topLabel' => 'UPCOMING EVENTS (14D)',
                                            'text' => "{$upcomingEvents->count()} active events scheduled",
                                            'startIcon' => [
                                                'knownIcon' => 'BOOKMARK',
                                            ],
                                        ],
                                    ],
                                    [
                                        'decoratedText' => [
                                            'topLabel' => 'ACTION ITEMS',
                                            'text' => "{$pendingTasksCount} pending deliverables ({$overdueTasksCount} overdue)",
                                            'startIcon' => [
                                                'knownIcon' => 'CLOCK',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'widgets' => [
                                    [
                                        'buttonList' => [
                                            'buttons' => [
                                                [
                                                    'text' => 'Open Operations Cockpit',
                                                    'onClick' => [
                                                        'openLink' => [
                                                            'url' => route('dashboard'),
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $service->sendRaw($digestPayload);

        $this->info("Daily briefing dispatched for {$upcomingEvents->count()} events.");

        return self::SUCCESS;
    }
}
