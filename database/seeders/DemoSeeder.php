<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds for the demo showcase.
     */
    public function run(): void
    {
        // Ensure standard task templates are seeded
        $this->call(TaskTemplateSeeder::class);

        // Ensure a default demo user exists for immediate login
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Demo User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Clean reset of tasks and events to prevent duplicate showcase data
        Schema::disableForeignKeyConstraints();
        Task::truncate();
        Event::truncate();
        Schema::enableForeignKeyConstraints();

        // Create showcase events without firing automatic observers (custom tasks seeded below)
        Event::withoutEvents(function (): void {
            // Event 1: The Flagship Demo Event (Webinar)
            $event1 = Event::create([
                'title' => 'Building Resilient Microservices with Go & Kotlin',
                'subtitle' => 'Architecture patterns, zero-downtime deploys, and incident post-mortems',
                'description' => 'Join our principal engineers as they break down how we modernized our backend core, slashed latency by 40%, and eliminated deployment bottlenecks.',
                'type' => 'webinar',
                'status' => 'scheduled',
                'event_date' => now()->addDays(12)->setTime(18, 0, 0),
                'metadata' => [
                    'registration_url' => 'https://workspace.google.com/events/microservices-2026',
                    'target_audience' => 'Backend Engineers, Tech Leads, DevOps',
                    'speaker' => 'Alex Dimitrov, Principal Architect',
                    'tags' => ['Go', 'Kotlin', 'Distributed Systems', 'Architecture'],
                ],
            ]);

            Task::create([
                'event_id' => $event1->id,
                'title' => 'Banner & Visual Social Assets',
                'description' => 'Design promotional banners and visual social assets for webinar launch across channels.',
                'assigned_role' => 'designer',
                'status' => 'completed',
                'due_date' => now()->subDays(2)->setTime(12, 0, 0),
                'assigned_to' => 'Elena (Lead Designer)',
                'asset_url' => 'https://images.unsplash.com/photo-1542744094-3a31727560fa?auto=format&fit=crop&w=1200&q=80',
            ]);

            Task::create([
                'event_id' => $event1->id,
                'title' => 'Draft Technical Article & LinkedIn Announcement',
                'description' => 'Write comprehensive technical blog post and LinkedIn announcement copy highlighting architectural takeaways.',
                'assigned_role' => 'copywriter',
                'status' => 'in_progress',
                'due_date' => now()->addDay()->setTime(17, 0, 0),
                'assigned_to' => 'Martin (Marketing)',
                'asset_url' => 'https://docs.google.com/document/d/1demo-google-doc-brief/edit',
            ]);

            Task::create([
                'event_id' => $event1->id,
                'title' => 'Send First Email Invitation Blast',
                'description' => 'Dispatch primary email invitation campaign to newsletter subscribers and engineering contacts.',
                'assigned_role' => 'marketing_lead',
                'status' => 'pending',
                'due_date' => now()->addDays(7)->setTime(10, 0, 0),
                'assigned_to' => 'Sarah (Growth Lead)',
                'asset_url' => null,
            ]);

            Task::create([
                'event_id' => $event1->id,
                'title' => 'Send 24h Final Reminder Notification',
                'description' => 'Send final 24-hour reminder email with livestream access link and calendar event file.',
                'assigned_role' => 'marketing_lead',
                'status' => 'pending',
                'due_date' => now()->addDays(11)->setTime(10, 0, 0),
                'assigned_to' => 'Sarah (Growth Lead)',
                'asset_url' => null,
            ]);

            // Event 2: Upcoming High-Stakes Conference
            $event2 = Event::create([
                'title' => 'Enterprise Cloud & AI Summit 2026',
                'subtitle' => 'Annual customer & partner keynote',
                'description' => 'Full-day hybrid conference featuring 12 keynote talks, partner booths, and hands-on developer workshops.',
                'type' => 'conference',
                'status' => 'scheduled',
                'event_date' => now()->addDays(5)->setTime(9, 30, 0),
                'metadata' => [
                    'registration_url' => 'https://workspace.google.com/events/cloud-ai-summit-2026',
                    'target_audience' => 'Enterprise Architects, VPs of Engineering, Cloud Leaders',
                    'venue' => 'Grand Convention Center, Hall A',
                ],
            ]);

            Task::create([
                'event_id' => $event2->id,
                'title' => 'Finalize Speaker Presentation Decks',
                'description' => 'Review keynote slide decks, partner presentations, and stage branding visuals.',
                'assigned_role' => 'designer',
                'status' => 'pending',
                'due_date' => now()->subDay()->setTime(18, 0, 0),
                'assigned_to' => 'Elena (Lead Designer)',
                'asset_url' => null,
            ]);

            Task::create([
                'event_id' => $event2->id,
                'title' => 'Venue Tech Check & Stage Audio Setup',
                'description' => 'Conduct comprehensive audio/visual check, high-speed networking test, and stage livestream rehearsal.',
                'assigned_role' => 'operations',
                'status' => 'in_progress',
                'due_date' => now()->addDays(2)->setTime(14, 0, 0),
                'assigned_to' => 'Krasimir (Ops Lead)',
                'asset_url' => 'https://drive.google.com/drive/folders/demo-venue-checklist',
            ]);

            Task::create([
                'event_id' => $event2->id,
                'title' => 'Publish Agenda & Partner Press Release',
                'description' => 'Publish official summit schedule on the event portal and distribute wire press release to media partners.',
                'assigned_role' => 'copywriter',
                'status' => 'completed',
                'due_date' => now()->subDays(4)->setTime(12, 0, 0),
                'assigned_to' => 'Martin (Marketing)',
                'asset_url' => 'https://docs.google.com/document/d/demo-press-release/edit',
            ]);

            // Event 3: Draft Internal Meeting (Showcases empty/draft state)
            $event3 = Event::create([
                'title' => 'Q4 Engineering Hackathon & Demo Day',
                'subtitle' => 'Internal product innovation showcases',
                'description' => 'Two-day internal hackathon encouraging cross-functional teams to experiment with generative AI tools and platform APIs.',
                'type' => 'internal_meeting',
                'status' => 'draft',
                'event_date' => now()->addDays(25)->setTime(13, 0, 0),
                'metadata' => [
                    'internal_channel' => '#hackathon-q4',
                    'teams_count' => 8,
                ],
            ]);

            Task::create([
                'event_id' => $event3->id,
                'title' => 'Book All-Hands Meeting Room & AV Equipment',
                'description' => 'Reserve primary collaboration area and configure internal video broadcast.',
                'assigned_role' => 'operations',
                'status' => 'pending',
                'due_date' => now()->addDays(15)->setTime(10, 0, 0),
                'assigned_to' => 'Krasimir (Ops Lead)',
                'asset_url' => null,
            ]);

            Task::create([
                'event_id' => $event3->id,
                'title' => 'Draft Internal Agenda & Team Hackathon Rules',
                'description' => 'Prepare submission guidelines, scoring rubric, and schedule of team presentations.',
                'assigned_role' => 'operations',
                'status' => 'pending',
                'due_date' => now()->addDays(20)->setTime(12, 0, 0),
                'assigned_to' => 'Alex (Lead Architect)',
                'asset_url' => null,
            ]);
        });
    }
}
