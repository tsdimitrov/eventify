<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TaskTemplate;
use Illuminate\Database\Seeder;

class TaskTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Webinar templates
            [
                'event_type' => 'webinar',
                'title' => 'Banner & Social Visuals',
                'description_template' => 'Design banner graphics and promotional visuals for webinar announcements.',
                'offset_days' => -14,
                'assigned_role' => 'designer',
            ],
            [
                'event_type' => 'webinar',
                'title' => 'Draft Promo Article & Social Copy',
                'description_template' => 'Write promotional blog article and drafted social media posts.',
                'offset_days' => -10,
                'assigned_role' => 'copywriter',
            ],
            [
                'event_type' => 'webinar',
                'title' => 'Email Invitation Blast',
                'description_template' => 'Send out primary email invitation blast to targeted subscriber list.',
                'offset_days' => -5,
                'assigned_role' => 'marketing_lead',
            ],
            [
                'event_type' => 'webinar',
                'title' => 'Final Reminder Notification',
                'description_template' => 'Send 24-hour reminder notice with access link to registered attendees.',
                'offset_days' => -1,
                'assigned_role' => 'marketing_lead',
            ],
            // Conference templates
            [
                'event_type' => 'conference',
                'title' => 'Keynote Assets & Slide Deck Template',
                'description_template' => 'Prepare branded keynote slide decks and digital conference assets.',
                'offset_days' => -21,
                'assigned_role' => 'designer',
            ],
            [
                'event_type' => 'conference',
                'title' => 'Agenda Publication & PR Post',
                'description_template' => 'Publish confirmed speaker agenda and distribute press release.',
                'offset_days' => -14,
                'assigned_role' => 'copywriter',
            ],
            [
                'event_type' => 'conference',
                'title' => 'Logistics & Venue Confirmation Check',
                'description_template' => 'Verify venue arrangements, A/V equipment, catering, and on-site logistics.',
                'offset_days' => -7,
                'assigned_role' => 'operations',
            ],
        ];

        foreach ($templates as $template) {
            TaskTemplate::firstOrCreate(
                [
                    'event_type' => $template['event_type'],
                    'title' => $template['title'],
                ],
                $template
            );
        }
    }
}
