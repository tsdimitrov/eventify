<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GoogleChatService
{
    /**
     * Send a Card v2 notification for a newly scheduled event with its tasks.
     */
    public function sendEventCreatedCard(Event $event): bool
    {
        $event->loadMissing(['tasks' => fn ($query) => $query->orderBy('due_date', 'asc')]);

        $formattedDate = $event->event_date->format('M j, Y');
        $diffDays = (int) now()->startOfDay()->diffInDays($event->event_date->startOfDay(), false);

        if ($diffDays === 0) {
            $countdownText = 'Event starts today!';
        } elseif ($diffDays > 0) {
            $countdownText = "Event starts in {$diffDays} ".($diffDays === 1 ? 'day' : 'days');
        } else {
            $pastDays = abs($diffDays);
            $countdownText = "Event occurred {$pastDays} ".($pastDays === 1 ? 'day' : 'days').' ago';
        }

        $formattedType = Str::headline($event->type);

        // Section 1: Event Summary
        $summaryWidgets = [
            [
                'decoratedText' => [
                    'topLabel' => 'TYPE',
                    'text' => $formattedType,
                    'startIcon' => [
                        'knownIcon' => 'BOOKMARK',
                    ],
                ],
            ],
            [
                'decoratedText' => [
                    'topLabel' => 'TIMELINE',
                    'text' => $countdownText,
                    'startIcon' => [
                        'knownIcon' => 'CLOCK',
                    ],
                ],
            ],
        ];

        if ($event->description) {
            $summaryWidgets[] = [
                'textParagraph' => [
                    'text' => Str::limit($event->description, 200),
                ],
            ];
        }

        // Section 2: Generated Action Items (Tasks Breakdown - up to 6 items)
        $taskWidgets = [];
        $tasksToDisplay = $event->tasks->take(6);

        if ($tasksToDisplay->isEmpty()) {
            $taskWidgets[] = [
                'textParagraph' => [
                    'text' => 'No action items generated for this event.',
                ],
            ];
        } else {
            foreach ($tasksToDisplay as $task) {
                $roleLabel = strtoupper($task->assigned_role ? str_replace('_', ' ', $task->assigned_role) : 'UNASSIGNED');
                $taskWidgets[] = [
                    'decoratedText' => [
                        'topLabel' => $roleLabel,
                        'text' => $task->title,
                        'bottomLabel' => 'Due: '.$task->due_date->format('M j, Y'),
                    ],
                ];
            }
        }

        // Section 3: Actions
        $actionWidgets = [
            [
                'buttonList' => [
                    'buttons' => [
                        [
                            'text' => 'View Event & Tasks',
                            'onClick' => [
                                'openLink' => [
                                    'url' => route('events.show', $event),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $payload = [
            'cardsV2' => [
                [
                    'cardId' => 'event-created-'.$event->id,
                    'card' => [
                        'header' => [
                            'title' => $event->title,
                            'subtitle' => '📅 Event Scheduled: '.$formattedDate,
                            'imageUrl' => 'https://fonts.gstatic.com/s/i/short-term/release/googlesymbols/event/default/48px.png',
                            'imageType' => 'CIRCLE',
                        ],
                        'sections' => [
                            [
                                'header' => 'Event Summary',
                                'widgets' => $summaryWidgets,
                            ],
                            [
                                'header' => 'Generated Action Items',
                                'widgets' => $taskWidgets,
                            ],
                            [
                                'widgets' => $actionWidgets,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this->sendRaw($payload);
    }

    /**
     * Send a raw payload to the configured Google Chat webhook URL.
     *
     * @param  array<string, mixed>  $payload
     */
    public function sendRaw(array $payload): bool
    {
        $webhookUrl = (string) config('services.google_chat.webhook_url');
        $enabled = (bool) config('services.google_chat.enabled', true);

        if ($webhookUrl === '' || ! $enabled) {
            Log::info('Google Chat notifications are disabled or webhook URL is not configured.');

            return true;
        }

        try {
            $response = Http::timeout(5)->post($webhookUrl, $payload);

            if (! $response->successful()) {
                Log::warning('Google Chat webhook request returned an unsuccessful response.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('Google Chat webhook request failed with an exception.', [
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
