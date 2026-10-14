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
                'title' => 'Design promo banners & thumbnail',
                'description_template' => 'Design promo banners and video/livestream thumbnail for webinar announcements.',
                'offset_days' => -14,
                'assigned_role' => 'designer',
            ],
            [
                'event_type' => 'webinar',
                'title' => 'Draft LinkedIn & Twitter copy',
                'description_template' => 'Draft promotional copy for LinkedIn and Twitter social campaigns.',
                'offset_days' => -10,
                'assigned_role' => 'copywriter',
            ],
            [
                'event_type' => 'webinar',
                'title' => 'Send email invitation blast',
                'description_template' => 'Send primary email invitation blast to targeted subscriber list.',
                'offset_days' => -5,
                'assigned_role' => 'marketing_lead',
            ],
            [
                'event_type' => 'webinar',
                'title' => 'Send final 24h reminder notification',
                'description_template' => 'Send final 24-hour reminder notice with access link to registered attendees.',
                'offset_days' => -1,
                'assigned_role' => 'marketing_lead',
            ],
            [
                'event_type' => 'webinar',
                'title' => 'Upload session recording & slides to Drive/YouTube',
                'description_template' => 'Upload processed session recording and presentation slides to Drive and YouTube channel.',
                'offset_days' => 1,
                'assigned_role' => 'operations',
            ],
            [
                'event_type' => 'webinar',
                'title' => 'Dispatch attendee follow-up & feedback survey',
                'description_template' => 'Dispatch post-webinar thank-you email and feedback survey to attendees.',
                'offset_days' => 2,
                'assigned_role' => 'marketing_lead',
            ],
            // Conference templates
            [
                'event_type' => 'conference',
                'title' => 'Prepare speaker decks & visual assets',
                'description_template' => 'Prepare branded keynote slide decks and on-stage digital assets.',
                'offset_days' => -21,
                'assigned_role' => 'designer',
            ],
            [
                'event_type' => 'conference',
                'title' => 'Publish agenda & press release',
                'description_template' => 'Publish full conference agenda and distribute official press release.',
                'offset_days' => -14,
                'assigned_role' => 'copywriter',
            ],
            [
                'event_type' => 'conference',
                'title' => 'Venue tech check & run-of-show review',
                'description_template' => 'Perform venue A/V technical check and review the run-of-show with speakers and staff.',
                'offset_days' => -7,
                'assigned_role' => 'operations',
            ],
            [
                'event_type' => 'conference',
                'title' => 'Publish post-event photo gallery & executive recap',
                'description_template' => 'Curate and publish post-event photo gallery and executive summary recap across channels.',
                'offset_days' => 3,
                'assigned_role' => 'copywriter',
            ],
            [
                'event_type' => 'conference',
                'title' => 'Finalize sponsor metrics & lead attribution report',
                'description_template' => 'Compile and finalize sponsor ROI metrics, attendance statistics, and lead attribution report.',
                'offset_days' => 7,
                'assigned_role' => 'marketing_lead',
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
