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

        // Create showcase events without firing automatic observers
        Event::withoutEvents(function (): void {
            $this->seedUpcomingWebinar();
            $this->seedAtRiskConference();
            $this->seedPastArchivedEvent();
            $this->seedDraftEvent();
            $this->seedCancelledEvent();
        });
    }

    /**
     * Event 1: The Upcoming Flagship Webinar (Upcoming Tab, T-Minus, T-Plus, & Social Hub).
     */
    protected function seedUpcomingWebinar(): void
    {
        $event = Event::create([
            'title' => 'Building Resilient Microservices with Go & Kotlin',
            'subtitle' => 'Architecture patterns, zero-downtime deploys, and incident post-mortems',
            'description' => 'Join our principal engineers as they break down how we modernized our backend core, slashed latency by 40%, and eliminated deployment bottlenecks.',
            'type' => 'webinar',
            'status' => 'scheduled',
            'event_date' => now()->addDays(10)->setTime(18, 0, 0),
            'metadata' => [
                'registration_url' => 'https://workspace.google.com/events/microservices-2026',
                'target_audience' => 'Backend Engineers, Tech Leads, DevOps',
                'speaker' => 'Alex Dimitrov, Principal Architect',
                'tags' => ['Go', 'Kotlin', 'Microservices', 'Distributed Systems'],
            ],
        ]);

        // [T-14 Pre-event] Design promo banners & visual assets
        Task::create([
            'event_id' => $event->id,
            'title' => 'Design promo banners & visual assets',
            'description' => 'Design promotional banners and visual social assets for webinar launch across channels.',
            'assigned_role' => 'designer',
            'status' => 'completed',
            'due_date' => now()->subDays(4)->setTime(12, 0, 0),
            'assigned_to' => 'Elena (Lead Designer)',
            'asset_url' => 'https://images.unsplash.com/photo-1542744094-3a31727560fa?auto=format&fit=crop&w=1200&q=80',
        ]);

        // [T-5 Pre-event] Draft LinkedIn announcement & tech brief
        Task::create([
            'event_id' => $event->id,
            'title' => 'Draft LinkedIn announcement & tech brief',
            'description' => 'Write comprehensive technical brief and LinkedIn announcement copy highlighting architectural takeaways.',
            'assigned_role' => 'copywriter',
            'status' => 'in_progress',
            'due_date' => now()->addDays(5)->setTime(17, 0, 0),
            'assigned_to' => 'Martin (Content)',
            'asset_url' => 'https://docs.google.com/document/d/1demo-brief/edit',
        ]);

        // [T-2 Pre-event] Dispatch community email blast
        Task::create([
            'event_id' => $event->id,
            'title' => 'Dispatch community email blast',
            'description' => 'Dispatch primary email invitation campaign to newsletter subscribers and engineering contacts.',
            'assigned_role' => 'marketing_lead',
            'status' => 'pending',
            'due_date' => now()->addDays(8)->setTime(10, 0, 0),
            'assigned_to' => 'Sarah (Growth)',
            'asset_url' => null,
        ]);

        // [T-0 Ad-Hoc / Custom Task] Live studio tech run with guest speakers
        Task::create([
            'event_id' => $event->id,
            'title' => 'Live studio tech run with guest speakers',
            'description' => 'Verify OBS streams, audio levels, and backup Zoom links.',
            'assigned_role' => 'operations',
            'status' => 'pending',
            'due_date' => now()->addDays(10)->setTime(16, 0, 0),
            'assigned_to' => 'Krasimir (Ops)',
            'asset_url' => null,
        ]);

        // [T+1 Post-event] Upload session recording & slides to Drive/YouTube
        Task::create([
            'event_id' => $event->id,
            'title' => 'Upload session recording & slides to Drive/YouTube',
            'description' => 'Upload processed session recording and presentation slides to Drive and YouTube channel.',
            'assigned_role' => 'operations',
            'status' => 'pending',
            'due_date' => now()->addDays(11)->setTime(17, 0, 0),
            'assigned_to' => 'Krasimir (Ops)',
            'asset_url' => null,
        ]);

        // [T+2 Post-event] Send attendee survey & feedback recap
        Task::create([
            'event_id' => $event->id,
            'title' => 'Send attendee survey & feedback recap',
            'description' => 'Dispatch post-webinar thank-you email and feedback survey to attendees.',
            'assigned_role' => 'marketing_lead',
            'status' => 'pending',
            'due_date' => now()->addDays(12)->setTime(11, 0, 0),
            'assigned_to' => 'Sarah (Growth)',
            'asset_url' => null,
        ]);
    }

    /**
     * Event 2: High-Urgency Conference (Triggers Red Alarms in Operations Cockpit).
     */
    protected function seedAtRiskConference(): void
    {
        $event = Event::create([
            'title' => 'Enterprise Cloud & AI Summit 2026',
            'subtitle' => 'Customer keynotes, AI architecture tracks, and partner lounges',
            'description' => 'Full-day hybrid conference featuring customer keynotes, AI architecture tracks, partner booths, and hands-on developer workshops.',
            'type' => 'conference',
            'status' => 'scheduled',
            'event_date' => now()->addDays(3)->setTime(9, 30, 0),
            'metadata' => [
                'registration_url' => 'https://workspace.google.com/events/ai-summit',
                'venue' => 'Grand Hotel Millennium, Hall Sofia',
            ],
        ]);

        // [T-7 Overdue Milestone] Finalize speaker keynote presentation decks (OVERDUE by 48h)
        Task::create([
            'event_id' => $event->id,
            'title' => 'Finalize speaker keynote presentation decks',
            'description' => 'Review keynote slide decks, partner presentations, and stage branding visuals.',
            'assigned_role' => 'designer',
            'status' => 'pending',
            'due_date' => now()->subDays(2)->setTime(18, 0, 0),
            'assigned_to' => 'Elena (Lead Designer)',
            'asset_url' => null,
        ]);

        // [T-1 Urgent Milestone] Stage AV, microphones & LED backdrop check (DUE TOMORROW)
        Task::create([
            'event_id' => $event->id,
            'title' => 'Stage AV, microphones & LED backdrop check',
            'description' => 'Conduct comprehensive audio/visual check, wireless microphone testing, and LED backdrop rehearsal.',
            'assigned_role' => 'operations',
            'status' => 'in_progress',
            'due_date' => now()->addDay()->setTime(18, 0, 0),
            'assigned_to' => 'Krasimir (Ops)',
            'asset_url' => 'https://drive.google.com/drive/folders/demo-av-checklist',
        ]);

        // [T-3 Completed] Publish agenda & partner press release
        Task::create([
            'event_id' => $event->id,
            'title' => 'Publish agenda & partner press release',
            'description' => 'Publish official summit schedule on the event portal and distribute wire press release to media partners.',
            'assigned_role' => 'copywriter',
            'status' => 'completed',
            'due_date' => now()->subDay()->setTime(12, 0, 0),
            'assigned_to' => 'Martin (Content)',
            'asset_url' => 'https://docs.google.com/document/d/demo-press-release/edit',
        ]);
    }

    /**
     * Event 3: Concluded Event (Past / Archive Tab & Historic Deliverables).
     */
    protected function seedPastArchivedEvent(): void
    {
        $event = Event::create([
            'title' => 'Q3 Backend Observability & Distributed Tracing Deep Dive',
            'subtitle' => 'OpenTelemetry instrumentations and real-world latency debugging',
            'description' => 'Technical deep dive into OpenTelemetry spans, context propagation, and distributed trace analysis across high-throughput services.',
            'type' => 'webinar',
            'status' => 'completed',
            'event_date' => now()->subDays(20)->setTime(17, 0, 0),
            'metadata' => [
                'attended_count' => 142,
                'recording_url' => 'https://youtube.com/watch?v=demo-recording',
                'slides_url' => 'https://drive.google.com/file/d/demo-slides/view',
            ],
        ]);

        // Task 1: Designer completed banner
        Task::create([
            'event_id' => $event->id,
            'title' => 'Visual banners and deck assets',
            'description' => 'Branded webinar presentation deck and promotional banners.',
            'assigned_role' => 'designer',
            'status' => 'completed',
            'due_date' => now()->subDays(25)->setTime(12, 0, 0),
            'assigned_to' => 'Elena (Lead Designer)',
            'asset_url' => 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&w=1200&q=80',
        ]);

        // Task 2: Technical blog recap
        Task::create([
            'event_id' => $event->id,
            'title' => 'Technical blog recap & social summary',
            'description' => 'Deep dive blog article outlining OpenTelemetry instrumentations and takeaways.',
            'assigned_role' => 'copywriter',
            'status' => 'completed',
            'due_date' => now()->subDays(21)->setTime(16, 0, 0),
            'assigned_to' => 'Martin (Content)',
            'asset_url' => 'https://docs.google.com/document/d/demo-blog-recap/edit',
        ]);

        // Task 3: YouTube upload & archive
        Task::create([
            'event_id' => $event->id,
            'title' => 'Publish recording to YouTube & archive slide deck',
            'description' => 'Uploaded edited session recording to YouTube and archived slides.',
            'assigned_role' => 'operations',
            'status' => 'completed',
            'due_date' => now()->subDays(19)->setTime(15, 0, 0),
            'assigned_to' => 'Krasimir (Ops)',
            'asset_url' => 'https://youtube.com/watch?v=demo-recording',
        ]);

        // Task 4: Feedback scores
        Task::create([
            'event_id' => $event->id,
            'title' => 'Post-event feedback score aggregation',
            'description' => 'Aggregated attendee NPS and feedback responses into analytics dashboard.',
            'assigned_role' => 'marketing_lead',
            'status' => 'completed',
            'due_date' => now()->subDays(18)->setTime(14, 0, 0),
            'assigned_to' => 'Sarah (Growth)',
            'asset_url' => null,
        ]);
    }

    /**
     * Event 4: Future Planning Event (Drafts Tab).
     */
    protected function seedDraftEvent(): void
    {
        $event = Event::create([
            'title' => '2027 Strategic Tech Roadmap & Hackathon Planning',
            'subtitle' => 'Internal cross-functional hackathon and AI prototypes',
            'description' => 'Two-day internal hackathon and strategic planning session focusing on LLM workflows and microservice consolidation.',
            'type' => 'internal_meeting',
            'status' => 'draft',
            'event_date' => now()->addDays(40)->setTime(10, 0, 0),
            'metadata' => [
                'internal_channel' => '#hackathon-2027',
                'target_teams' => 10,
            ],
        ]);

        Task::create([
            'event_id' => $event->id,
            'title' => 'Draft hackathon challenges & evaluation criteria',
            'description' => 'Define hackathon scoring rubric, track challenges, and team submission portal requirements.',
            'assigned_role' => 'marketing_lead',
            'status' => 'pending',
            'due_date' => now()->addDays(25)->setTime(12, 0, 0),
            'assigned_to' => 'Sarah (Growth)',
            'asset_url' => null,
        ]);
    }

    /**
     * Event 5: Postponed / Cancelled Event (Cancelled Tab).
     */
    protected function seedCancelledEvent(): void
    {
        $event = Event::create([
            'title' => 'Legacy Monolith Decomposition Workshop',
            'subtitle' => 'Hands-on refactoring workshop',
            'description' => 'Hands-on workshop detailing bounded contexts, strangler fig patterns, and database decomposition.',
            'type' => 'workshop',
            'status' => 'cancelled',
            'event_date' => now()->addDays(14)->setTime(14, 0, 0),
            'metadata' => [
                'cancellation_reason' => 'Postponed to Q1 2027 due to international speaker schedule conflict.',
                'cancelled_by' => 'Engineering Director',
            ],
        ]);

        Task::create([
            'event_id' => $event->id,
            'title' => 'Venue reservation booking',
            'description' => 'Reserve workshop training facility and setup high-power workstations.',
            'assigned_role' => 'operations',
            'status' => 'pending',
            'due_date' => now()->addDays(7)->setTime(10, 0, 0),
            'assigned_to' => 'Krasimir (Ops)',
            'asset_url' => null,
        ]);
    }
}
