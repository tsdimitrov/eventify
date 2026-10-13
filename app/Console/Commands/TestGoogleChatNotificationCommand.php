<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\GoogleChatService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('google-chat:test')]
#[Description('Send a test notification card to the configured Google Chat webhook')]
class TestGoogleChatNotificationCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(GoogleChatService $service): int
    {
        $webhookUrl = (string) config('services.google_chat.webhook_url');
        $enabled = (bool) config('services.google_chat.enabled', true);

        if ($webhookUrl === '') {
            $this->warn('Google Chat webhook URL is not configured. Set GOOGLE_CHAT_WEBHOOK_URL in your .env file.');

            return self::FAILURE;
        }

        if (! $enabled) {
            $this->warn('Google Chat notifications are disabled. Set GOOGLE_CHAT_NOTIFICATIONS_ENABLED=true in your .env file.');

            return self::FAILURE;
        }

        $this->info('Sending test notification to Google Chat...');

        $testPayload = [
            'cardsV2' => [
                [
                    'cardId' => 'test-notification-'.time(),
                    'card' => [
                        'header' => [
                            'title' => 'Eventify Webhook Test',
                            'subtitle' => 'Testing Google Chat integration',
                            'imageUrl' => 'https://fonts.gstatic.com/s/i/short-term/release/googlesymbols/check_circle/default/48px.png',
                            'imageType' => 'CIRCLE',
                        ],
                        'sections' => [
                            [
                                'header' => 'Connectivity Check',
                                'widgets' => [
                                    [
                                        'textParagraph' => [
                                            'text' => 'This is a test notification from Eventify to verify webhook connectivity.',
                                        ],
                                    ],
                                    [
                                        'decoratedText' => [
                                            'topLabel' => 'STATUS',
                                            'text' => 'Webhook is reachable and operational.',
                                            'startIcon' => [
                                                'knownIcon' => 'STAR',
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

        $success = $service->sendRaw($testPayload);

        if ($success) {
            $this->info('Test notification sent successfully to Google Chat!');

            return self::SUCCESS;
        }

        $this->error('Failed to send test notification to Google Chat. Check application logs for details.');

        return self::FAILURE;
    }
}
