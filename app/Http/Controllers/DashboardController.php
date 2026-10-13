<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Task;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the Executive Operations Cockpit dashboard.
     */
    public function index(): Response
    {
        $now = now();
        $thirtyDaysFromNow = $now->copy()->addDays(30);
        $fiveDaysFromNow = $now->copy()->addDays(5);

        // 1. Top-Level KPI Metrics
        $activeEventsCount = Event::query()
            ->whereBetween('event_date', [$now, $thirtyDaysFromNow])
            ->count();

        $pendingTasksCount = Task::query()
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereHas('event', fn ($q) => $q->whereBetween('event_date', [$now, $thirtyDaysFromNow]))
            ->count();

        $overdueTasksCount = Task::query()
            ->where('due_date', '<', $now)
            ->where('status', '!=', 'completed')
            ->count();

        $completedThisWeekCount = Task::query()
            ->where('status', 'completed')
            ->where('updated_at', '>=', $now->copy()->startOfWeek())
            ->count();

        // 2. Blockage Radar (redFlags)
        $overdueTasks = Task::query()
            ->where('due_date', '<', $now)
            ->where('status', '!=', 'completed')
            ->with('event:id,title,type')
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        $atRiskEvents = Event::query()
            ->whereBetween('event_date', [$now, $fiveDaysFromNow])
            ->whereHas('tasks', function ($q): void {
                $q->whereIn('status', ['pending', 'in_progress'])
                    ->whereIn('assigned_role', ['designer', 'copywriter']);
            })
            ->with([
                'tasks' => fn ($q) => $q->whereIn('status', ['pending', 'in_progress'])
                    ->whereIn('assigned_role', ['designer', 'copywriter']),
            ])
            ->get();

        // 3. 30-Day Event Roadmap (upcomingEvents)
        $upcomingEvents = Event::query()
            ->whereBetween('event_date', [$now, $thirtyDaysFromNow])
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'completed'),
                'tasks as overdue_tasks_count' => fn ($q) => $q->whereIn('status', ['pending', 'in_progress'])->where('due_date', '<', $now),
            ])
            ->orderBy('event_date', 'asc')
            ->get();

        // 4. Team Capacity / Workload Snapshot (workloadByRole)
        $workloadByRole = Task::query()
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('assigned_role')
            ->selectRaw('assigned_role, count(*) as total_tasks')
            ->groupBy('assigned_role')
            ->pluck('total_tasks', 'assigned_role')
            ->all();

        // 5. Recent Deliverables Feed (recentAssets)
        $recentAssets = Task::query()
            ->where('status', 'completed')
            ->whereNotNull('asset_url')
            ->where('asset_url', '!=', '')
            ->with('event:id,title')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'kpi' => [
                'active_events_count' => $activeEventsCount,
                'pending_tasks_count' => $pendingTasksCount,
                'overdue_tasks_count' => $overdueTasksCount,
                'completed_this_week_count' => $completedThisWeekCount,
            ],
            'redFlags' => [
                'overdue_tasks' => $overdueTasks,
                'at_risk_events' => $atRiskEvents,
            ],
            'upcomingEvents' => $upcomingEvents,
            'workloadByRole' => $workloadByRole,
            'recentAssets' => $recentAssets,
        ]);
    }
}
