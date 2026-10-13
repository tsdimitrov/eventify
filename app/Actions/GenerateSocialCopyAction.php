<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Event;

class GenerateSocialCopyAction
{
    /**
     * Generate structured social media drafts from the event data and its metadata.
     *
     * @return array<string, array{
     *     platform: string,
     *     character_limit?: int,
     *     subject?: string,
     *     text: string,
     *     hashtags?: list<string>
     * }>
     */
    public function execute(Event $event): array
    {
        $registrationUrl = (is_array($event->metadata) && ! empty($event->metadata['registration_url']) && is_string($event->metadata['registration_url']))
            ? $event->metadata['registration_url']
            : url("/events/{$event->id}");

        return [
            'linkedin' => [
                'platform' => 'LinkedIn',
                'character_limit' => 3000,
                'text' => "🚀 Announcing: {$event->title}\n\n{$event->subtitle}\n\n📅 Date: ".$event->event_date->format('F j, Y - H:i')."\n\n{$event->description}\n\n👉 Register now: {$registrationUrl}",
                'hashtags' => ['#Webinar', '#TechEvents', '#Innovation'],
            ],
            'twitter' => [
                'platform' => 'X / Twitter',
                'character_limit' => 280,
                'text' => "Mark your calendar! 📅 {$event->title} is happening on ".$event->event_date->format('M j').".\n\nSave your spot 👇 {$registrationUrl}",
                'hashtags' => ['#Tech', '#Event'],
            ],
            'email' => [
                'platform' => 'Newsletter Blurb',
                'subject' => "You're Invited: {$event->title}",
                'text' => "Hi {{first_name}},\n\nWe're thrilled to invite you to our upcoming {$event->type}: {$event->title}.\n\nWhen: ".$event->event_date->format('l, F j, Y \a\t H:i')."\n\n{$event->description}\n\nClick here to RSVP: {$registrationUrl}",
            ],
        ];
    }
}
