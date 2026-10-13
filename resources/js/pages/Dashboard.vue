<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import {
    AlertOctagon,
    AlertTriangle,
    ArrowRight,
    Calendar,
    Check,
    CheckCircle2,
    Clock,
    ExternalLink,
    FileText,
    Flame,
    Plus,
    TrendingUp,
    User,
    Zap,
} from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import eventsRoutes from '@/routes/events';
import tasksRoutes from '@/routes/tasks';
import type {
    DashboardKPI,
    RedFlags,
    Task,
    UpcomingEvent,
} from '@/types/event';

const props = defineProps<{
    kpi: DashboardKPI;
    redFlags: RedFlags;
    upcomingEvents: UpcomingEvent[];
    workloadByRole: Record<string, number>;
    recentAssets: Task[];
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Cockpit',
            href: dashboard(),
        },
    ],
});

// Current formatted date
const currentDateFormatted = computed(() => {
    return new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date());
});

// Helper for countdown
function getEventCountdown(dateString: string): string {
    const target = new Date(dateString).getTime();
    const now = Date.now();
    const diffDays = Math.ceil((target - now) / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'T-0 Days (Today)';
    if (diffDays > 0) return `T-${diffDays} Days`;
    return `T+${Math.abs(diffDays)} Days`;
}

function formatShortDate(dateString: string): string {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
    }).format(new Date(dateString));
}

function getOverdueLabel(dueDateString: string): string {
    const target = new Date(dueDateString).getTime();
    const now = Date.now();
    const diffDays = Math.max(1, Math.ceil((now - target) / (1000 * 60 * 60 * 24)));
    return `Overdue by ${diffDays} ${diffDays === 1 ? 'day' : 'days'}`;
}

function getRoleBadgeClass(role: string | null): string {
    switch (role) {
        case 'designer':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-950/50 dark:text-purple-300 border-purple-200 dark:border-purple-800';
        case 'copywriter':
            return 'bg-sky-100 text-sky-800 dark:bg-sky-950/50 dark:text-sky-300 border-sky-200 dark:border-sky-800';
        case 'marketing_lead':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border-amber-200 dark:border-amber-800';
        case 'operations':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
        default:
            return 'bg-secondary text-secondary-foreground';
    }
}

function getEventHealth(event: UpcomingEvent): {
    status: 'on_track' | 'at_risk';
    label: string;
    badgeClass: string;
} {
    const eventTime = new Date(event.event_date).getTime();
    const now = Date.now();
    const diffHours = (eventTime - now) / (1000 * 60 * 60);

    const totalTasks = event.tasks_count || 1;
    const completionPercent = Math.round(((event.completed_tasks_count || 0) / totalTasks) * 100);

    if (event.overdue_tasks_count > 0) {
        return {
            status: 'at_risk',
            label: `${event.overdue_tasks_count} Overdue Tasks`,
            badgeClass: 'bg-red-500/10 text-red-700 dark:text-red-300 border-red-500/30 font-semibold',
        };
    }

    if (diffHours <= 48 && completionPercent < 50) {
        return {
            status: 'at_risk',
            label: 'At Risk (< 50% completed)',
            badgeClass: 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/30 font-semibold',
        };
    }

    return {
        status: 'on_track',
        label: 'On Track',
        badgeClass: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30 font-semibold',
    };
}

function getAssetBadgeInfo(url: string): { label: string; bgClass: string } {
    const lower = url.toLowerCase();
    if (lower.includes('docs.google.com')) {
        return { label: 'Google Docs', bgClass: 'bg-sky-500/10 text-sky-700 dark:text-sky-300 border-sky-500/30' };
    }
    if (lower.includes('drive.google.com')) {
        return { label: 'Google Drive', bgClass: 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/30' };
    }
    if (lower.includes('figma.com')) {
        return { label: 'Figma Asset', bgClass: 'bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-500/30' };
    }
    if (lower.includes('images.unsplash.com') || lower.includes('.png') || lower.includes('.jpg') || lower.includes('.jpeg')) {
        return { label: 'Creative Image', bgClass: 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-500/30' };
    }
    return { label: 'Attachment', bgClass: 'bg-muted text-foreground border-border' };
}

// Workload calculation
const allStandardRoles = ['designer', 'copywriter', 'marketing_lead', 'operations'];
const activeWorkloadRoles = computed(() => {
    const roles = new Set([...allStandardRoles, ...Object.keys(props.workloadByRole)]);
    return Array.from(roles);
});

const maxRoleWorkload = computed(() => {
    const counts = Object.values(props.workloadByRole);
    return counts.length > 0 ? Math.max(...counts, 1) : 1;
});

const totalActiveWorkload = computed(() => {
    return Object.values(props.workloadByRole).reduce((acc, val) => acc + val, 0);
});

const hasBlockages = computed(() => {
    return (
        (props.redFlags?.overdue_tasks?.length ?? 0) > 0 ||
        (props.redFlags?.at_risk_events?.length ?? 0) > 0
    );
});
</script>

<template>
    <div>
        <Head title="Operations Cockpit" />

        <div class="flex flex-col gap-6 p-4 md:p-8">
            <!-- 1. Header & Quick Actions -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold tracking-tight text-foreground md:text-3xl">
                            Operations Cockpit
                        </h1>
                        <span class="inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                            Live Feed
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground sm:text-sm mt-0.5">
                        Executive blockage radar & 30-day cross-functional execution snapshot • {{ currentDateFormatted }}
                    </p>
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    <Button as-child variant="outline" size="sm" class="text-xs">
                        <Link :href="tasksRoutes.index.url()">
                            <CheckCircle2 class="mr-1.5 h-3.5 w-3.5 text-primary" />
                            Open Task Board
                        </Link>
                    </Button>

                    <Button as-child size="sm" class="text-xs">
                        <Link :href="eventsRoutes.create()">
                            <Plus class="mr-1.5 h-3.5 w-3.5" />
                            Schedule Event
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- 2. Top KPI Ribbon (4 Stat Cards) -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Active Events (30d) -->
                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Active Events (30d)
                        </CardTitle>
                        <div class="h-8 w-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                            <Calendar class="h-4 w-4" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-foreground">
                            {{ kpi.active_events_count }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Scheduled across next 30 days
                        </p>
                    </CardContent>
                </Card>

                <!-- Pending Deliverables -->
                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Pending Deliverables
                        </CardTitle>
                        <div class="h-8 w-8 rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                            <Clock class="h-4 w-4" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-foreground">
                            {{ kpi.pending_tasks_count }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            In-progress & queued tasks
                        </p>
                    </CardContent>
                </Card>

                <!-- Critical Bottlenecks (Overdue) -->
                <Card
                    class="border-sidebar-border/70 dark:border-sidebar-border transition-colors"
                    :class="
                        kpi.overdue_tasks_count > 0
                            ? 'border-red-500/40 bg-red-500/5 dark:bg-red-950/20'
                            : ''
                    "
                >
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Critical Bottlenecks
                        </CardTitle>
                        <div
                            class="h-8 w-8 rounded-lg flex items-center justify-center"
                            :class="
                                kpi.overdue_tasks_count > 0
                                    ? 'bg-red-500/20 text-red-600 dark:text-red-400'
                                    : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                            "
                        >
                            <Flame v-if="kpi.overdue_tasks_count > 0" class="h-4 w-4" />
                            <Check v-else class="h-4 w-4" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-2">
                            <span
                                class="text-2xl font-bold"
                                :class="kpi.overdue_tasks_count > 0 ? 'text-red-600 dark:text-red-400' : 'text-foreground'"
                            >
                                {{ kpi.overdue_tasks_count }}
                            </span>
                            <span
                                v-if="kpi.overdue_tasks_count > 0"
                                class="relative flex h-2.5 w-2.5"
                            >
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75" />
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500" />
                            </span>
                        </div>
                        <div class="mt-1">
                            <span
                                v-if="kpi.overdue_tasks_count > 0"
                                class="text-xs font-semibold text-red-600 dark:text-red-400"
                            >
                                Requires immediate intervention
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400"
                            >
                                Zero Overdue ✓
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Completed This Week -->
                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Completed This Week
                        </CardTitle>
                        <div class="h-8 w-8 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                            <TrendingUp class="h-4 w-4" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-foreground">
                            {{ kpi.completed_this_week_count }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Shipped deliverables velocity
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- 3. Section 1: Blockage Radar (The "Fire Alarm") -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div
                            class="h-6 w-6 rounded-md flex items-center justify-center"
                            :class="hasBlockages ? 'bg-red-500/20 text-red-600 dark:text-red-400' : 'bg-emerald-500/10 text-emerald-600'"
                        >
                            <Flame v-if="hasBlockages" class="h-3.5 w-3.5" />
                            <CheckCircle2 v-else class="h-3.5 w-3.5" />
                        </div>
                        <h2 class="text-base font-bold tracking-tight text-foreground">
                            Blockage Radar & Risk Warnings
                        </h2>
                    </div>

                    <span class="text-xs text-muted-foreground">
                        {{ hasBlockages ? 'Action items requiring unblocking' : 'No operational bottlenecks' }}
                    </span>
                </div>

                <!-- No Blockages (Calm Green State) -->
                <div
                    v-if="!hasBlockages"
                    class="flex items-center gap-3 rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-4 text-xs font-medium text-emerald-700 dark:text-emerald-300"
                >
                    <CheckCircle2 class="h-4 w-4 shrink-0 text-emerald-600" />
                    <span>🟢 All systems on schedule. No overdue bottlenecks or critical role blockages detected.</span>
                </div>

                <!-- Active Blockages Grid -->
                <div v-else class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <!-- Overdue Task Cards -->
                    <div
                        v-for="task in redFlags.overdue_tasks"
                        :key="task.id"
                        class="flex flex-col justify-between rounded-xl border border-red-500/30 bg-red-500/5 p-3.5 dark:bg-red-950/20 transition-all hover:border-red-500/50"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5">
                                    <span
                                        v-if="task.assigned_role"
                                        :class="getRoleBadgeClass(task.assigned_role)"
                                        class="rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider border"
                                    >
                                        {{ task.assigned_role.replace(/_/g, ' ') }}
                                    </span>
                                    <span class="rounded bg-red-500/20 text-red-700 dark:text-red-300 px-1.5 py-0.5 text-[10px] font-bold border border-red-500/30">
                                        {{ getOverdueLabel(task.due_date) }}
                                    </span>
                                </div>

                                <span v-if="task.assigned_to" class="text-[11px] text-muted-foreground flex items-center gap-1">
                                    <User class="h-3 w-3" />
                                    {{ task.assigned_to }}
                                </span>
                            </div>

                            <div>
                                <h3 class="text-sm font-semibold text-foreground leading-snug">
                                    {{ task.title }}
                                </h3>
                                <p v-if="task.event" class="text-xs text-muted-foreground mt-0.5">
                                    Event: <strong class="text-foreground">{{ task.event.title }}</strong>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2.5 mt-2 border-t border-red-500/20">
                            <span class="text-[11px] font-mono text-red-600 dark:text-red-400">
                                Due: {{ formatShortDate(task.due_date) }}
                            </span>

                            <Button as-child size="sm" variant="destructive" class="h-7 px-2.5 text-xs">
                                <Link :href="task.event ? eventsRoutes.show.url(task.event.id) : tasksRoutes.index.url()">
                                    Resolve / View Task
                                    <ArrowRight class="ml-1 h-3 w-3" />
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <!-- At-Risk Event Cards -->
                    <div
                        v-for="event in redFlags.at_risk_events"
                        :key="`risk-${event.id}`"
                        class="flex flex-col justify-between rounded-xl border border-amber-500/30 bg-amber-500/5 p-3.5 dark:bg-amber-950/20 transition-all hover:border-amber-500/50"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="rounded bg-amber-500/20 text-amber-700 dark:text-amber-300 px-1.5 py-0.5 text-[10px] font-bold border border-amber-500/30 flex items-center gap-1">
                                    <AlertTriangle class="h-3 w-3" />
                                    At-Risk Milestone
                                </span>

                                <span class="text-xs font-mono font-semibold text-amber-700 dark:text-amber-300">
                                    {{ getEventCountdown(event.event_date) }}
                                </span>
                            </div>

                            <div>
                                <h3 class="text-sm font-semibold text-foreground leading-snug">
                                    {{ event.title }}
                                </h3>
                                <p class="text-xs text-amber-800 dark:text-amber-300 mt-0.5 font-medium">
                                    ⚠️ Critical creative/copy tasks pending within 5 days of launch
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2.5 mt-2 border-t border-amber-500/20">
                            <span class="text-[11px] text-muted-foreground">
                                Date: {{ formatShortDate(event.event_date) }}
                            </span>

                            <Button as-child size="sm" variant="outline" class="h-7 px-2.5 text-xs border-amber-500/40 hover:bg-amber-500/10 text-amber-800 dark:text-amber-200">
                                <Link :href="eventsRoutes.show.url(event.id)">
                                    Inspect Event
                                    <ArrowRight class="ml-1 h-3 w-3" />
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Section 2: Two-Column Operational Layout -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 items-start">
                <!-- Left Column (60%): 30-Day Event Roadmap -->
                <div class="space-y-4 lg:col-span-7">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold tracking-tight text-foreground">
                                30-Day Event Roadmap
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                Chronological pipeline & delivery health for scheduled milestones
                            </p>
                        </div>

                        <Badge variant="outline" class="text-xs font-mono">
                            {{ upcomingEvents.length }} upcoming
                        </Badge>
                    </div>

                    <!-- Empty state if no upcoming events -->
                    <div
                        v-if="upcomingEvents.length === 0"
                        class="rounded-xl border border-dashed border-border p-8 text-center text-muted-foreground"
                    >
                        <Calendar class="mx-auto h-8 w-8 text-muted-foreground/60 mb-2" />
                        <p class="text-sm font-medium">No events scheduled over the next 30 days.</p>
                        <Button as-child variant="outline" size="sm" class="mt-3 text-xs">
                            <Link :href="eventsRoutes.create()">
                                Schedule New Event
                            </Link>
                        </Button>
                    </div>

                    <!-- Roadmap Cards List -->
                    <div v-else class="space-y-3">
                        <Card
                            v-for="event in upcomingEvents"
                            :key="event.id"
                            class="transition-all hover:border-primary/40 group"
                        >
                            <CardContent class="p-4 sm:p-5">
                                <div class="space-y-3">
                                    <!-- Header Row -->
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <Badge variant="default" class="capitalize text-xs">
                                                {{ event.type }}
                                            </Badge>
                                            <span class="rounded bg-muted px-2 py-0.5 text-xs font-mono font-semibold text-foreground">
                                                {{ getEventCountdown(event.event_date) }}
                                            </span>
                                            <!-- Health Badge -->
                                            <span
                                                :class="getEventHealth(event).badgeClass"
                                                class="inline-flex items-center rounded-md border px-2 py-0.5 text-[11px]"
                                            >
                                                {{ getEventHealth(event).label }}
                                            </span>
                                        </div>

                                        <span class="text-xs text-muted-foreground flex items-center gap-1">
                                            <Calendar class="h-3.5 w-3.5" />
                                            {{ formatShortDate(event.event_date) }}
                                        </span>
                                    </div>

                                    <!-- Event Title & Link -->
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <h3 class="text-base font-semibold text-foreground group-hover:text-primary transition-colors">
                                                <Link :href="eventsRoutes.show.url(event.id)">
                                                    {{ event.title }}
                                                </Link>
                                            </h3>
                                            <p v-if="event.subtitle" class="text-xs text-muted-foreground line-clamp-1 mt-0.5">
                                                {{ event.subtitle }}
                                            </p>
                                        </div>

                                        <Button as-child variant="ghost" size="sm" class="h-8 px-2 text-xs shrink-0">
                                            <Link :href="eventsRoutes.show.url(event.id)">
                                                Control Room
                                                <ArrowRight class="ml-1 h-3.5 w-3.5" />
                                            </Link>
                                        </Button>
                                    </div>

                                    <!-- Progress Bar & Metrics -->
                                    <div class="space-y-1.5 pt-1">
                                        <div class="flex items-center justify-between text-xs text-muted-foreground">
                                            <span>
                                                Delivery Progress:
                                                <strong class="text-foreground">
                                                    {{ event.completed_tasks_count }} / {{ event.tasks_count }}
                                                </strong>
                                                tasks completed
                                            </span>
                                            <span class="font-mono font-semibold text-foreground">
                                                {{
                                                    event.tasks_count > 0
                                                        ? Math.round((event.completed_tasks_count / event.tasks_count) * 100)
                                                        : 0
                                                }}%
                                            </span>
                                        </div>

                                        <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                            <div
                                                class="h-full transition-all duration-500"
                                                :class="
                                                    event.overdue_tasks_count > 0
                                                        ? 'bg-amber-500'
                                                        : 'bg-primary'
                                                "
                                                :style="{
                                                    width: `${
                                                        event.tasks_count > 0
                                                            ? Math.round((event.completed_tasks_count / event.tasks_count) * 100)
                                                            : 0
                                                    }%`,
                                                }"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Right Column (40%): Team Capacity & Asset Feed -->
                <div class="space-y-5 lg:col-span-5">
                    <!-- 1. Team Workload Breakdown -->
                    <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardHeader class="pb-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-base font-bold tracking-tight text-foreground flex items-center gap-1.5">
                                        <Zap class="h-4 w-4 text-primary" />
                                        Team Capacity Snapshot
                                    </CardTitle>
                                    <CardDescription class="text-xs text-muted-foreground">
                                        Active deliverables queued by operational role
                                    </CardDescription>
                                </div>

                                <span class="rounded bg-muted px-2 py-0.5 text-xs font-mono font-medium text-foreground">
                                    {{ totalActiveWorkload }} active
                                </span>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-3.5 pt-0">
                            <div
                                v-for="role in activeWorkloadRoles"
                                :key="role"
                                class="space-y-1.5"
                            >
                                <div class="flex items-center justify-between text-xs">
                                    <span
                                        :class="getRoleBadgeClass(role)"
                                        class="rounded px-2 py-0.5 font-semibold text-[11px] capitalize border"
                                    >
                                        {{ role.replace(/_/g, ' ') }}
                                    </span>

                                    <span class="font-mono text-xs font-medium text-foreground">
                                        {{ workloadByRole[role] || 0 }} {{ (workloadByRole[role] || 0) === 1 ? 'task' : 'tasks' }}
                                    </span>
                                </div>

                                <div class="h-2 w-full overflow-hidden rounded-full bg-muted/60">
                                    <div
                                        class="h-full bg-primary/80 transition-all duration-500 rounded-full"
                                        :style="{
                                            width: `${Math.round(((workloadByRole[role] || 0) / maxRoleWorkload) * 100)}%`,
                                        }"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- 2. Live Deliverables Stream (Recent Assets) -->
                    <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardHeader class="pb-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-base font-bold tracking-tight text-foreground flex items-center gap-1.5">
                                        <FileText class="h-4 w-4 text-primary" />
                                        Recent Deliverables Feed
                                    </CardTitle>
                                    <CardDescription class="text-xs text-muted-foreground">
                                        Approved assets, decks, and campaign creative
                                    </CardDescription>
                                </div>

                                <span class="text-xs text-muted-foreground">
                                    Latest {{ recentAssets.length }}
                                </span>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-2.5 pt-0">
                            <div
                                v-if="recentAssets.length === 0"
                                class="rounded-lg border border-dashed border-border p-4 text-center text-xs text-muted-foreground"
                            >
                                No completed deliverables with attached asset links yet.
                            </div>

                            <div
                                v-for="asset in recentAssets"
                                :key="asset.id"
                                class="flex items-center justify-between gap-3 rounded-lg border border-border/70 bg-muted/30 p-2.5 transition-colors hover:bg-muted/60"
                            >
                                <div class="min-w-0 flex-1 space-y-1">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span
                                            v-if="asset.asset_url"
                                            :class="getAssetBadgeInfo(asset.asset_url).bgClass"
                                            class="rounded px-1.5 py-0.5 text-[10px] font-semibold border"
                                        >
                                            {{ getAssetBadgeInfo(asset.asset_url).label }}
                                        </span>

                                        <span
                                            v-if="asset.assigned_role"
                                            :class="getRoleBadgeClass(asset.assigned_role)"
                                            class="rounded px-1.5 py-0.5 text-[10px] font-semibold capitalize border"
                                        >
                                            {{ asset.assigned_role.replace(/_/g, ' ') }}
                                        </span>
                                    </div>

                                    <h4 class="text-xs font-semibold text-foreground truncate">
                                        {{ asset.title }}
                                    </h4>

                                    <p v-if="asset.event" class="text-[11px] text-muted-foreground truncate">
                                        {{ asset.event.title }}
                                    </p>
                                </div>

                                <div class="shrink-0">
                                    <Button
                                        v-if="asset.asset_url"
                                        as-child
                                        size="sm"
                                        variant="outline"
                                        class="h-7 px-2 text-xs flex items-center gap-1"
                                    >
                                        <a
                                            :href="asset.asset_url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <span>Inspect</span>
                                            <ExternalLink class="h-3 w-3" />
                                        </a>
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </div>
</template>
