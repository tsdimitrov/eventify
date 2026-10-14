<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Archive,
    ArrowRight,
    Ban,
    Calendar,
    CheckCircle2,
    Clock,
    FileText,
    Plus,
} from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import eventsRoutes from '@/routes/events';
import type { Event, EventTab, PaginatedEvents, TabCounts } from '@/types/event';

const props = defineProps<{
    events: PaginatedEvents | Event[];
    currentTab?: EventTab;
    tabCounts?: TabCounts;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Events',
                href: eventsRoutes.index(),
            },
        ],
    },
});

const activeTab = computed<EventTab>(() => props.currentTab ?? 'upcoming');

const counts = computed<TabCounts>(() => props.tabCounts ?? {
    upcoming: 0,
    past: 0,
    drafts: 0,
    cancelled: 0,
});

const eventsList = computed<Event[]>(() => {
    if (Array.isArray(props.events)) {
        return props.events;
    }
    return props.events?.data ?? [];
});

const pagination = computed<PaginatedEvents | null>(() => {
    if (!Array.isArray(props.events) && props.events?.links) {
        return props.events;
    }
    return null;
});

const tabs = computed(() => [
    { key: 'upcoming' as const, label: 'Upcoming', count: counts.value.upcoming },
    { key: 'past' as const, label: 'Past / Archive', count: counts.value.past },
    { key: 'drafts' as const, label: 'Drafts', count: counts.value.drafts },
    { key: 'cancelled' as const, label: 'Cancelled', count: counts.value.cancelled },
]);

function switchTab(tab: EventTab): void {
    router.get(
        eventsRoutes.index.url(),
        { tab },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(date);
}

function formatDateShort(dateString: string): string {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(date);
}

function getCountdown(dateString: string): { label: string; isPast: boolean } {
    const target = new Date(dateString).getTime();
    const now = Date.now();
    const diffDays = Math.ceil((target - now) / (1000 * 60 * 60 * 24));

    if (diffDays === 0) {
        return { label: 'Today', isPast: false };
    }
    if (diffDays === 1) {
        return { label: 'Tomorrow', isPast: false };
    }
    if (diffDays > 1) {
        return { label: `In ${diffDays} days`, isPast: false };
    }
    if (diffDays === -1) {
        return { label: 'Yesterday', isPast: true };
    }
    return { label: `${Math.abs(diffDays)} days ago`, isPast: true };
}

function getTypeBadgeVariant(type: Event['type']): 'default' | 'secondary' | 'outline' {
    switch (type) {
        case 'conference':
            return 'default';
        case 'webinar':
            return 'secondary';
        default:
            return 'outline';
    }
}

function formatTypeName(type: string): string {
    return type.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
</script>

<template>
    <div>
        <Head title="Events" />

        <div class="flex flex-col gap-6 p-4 md:p-8">
            <!-- Header section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground md:text-3xl">
                        Events
                    </h1>
                    <p class="text-sm text-muted-foreground md:text-base">
                        Plan, coordinate, and review lifecycle stages of scheduled events.
                    </p>
                </div>

                <div>
                    <Button as-child class="w-full sm:w-auto">
                        <Link :href="eventsRoutes.create()">
                            <Plus class="mr-2 h-4 w-4" />
                            Create Event
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Tab Navigation Bar -->
            <div class="border-b border-border">
                <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Event Status Tabs">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        @click="switchTab(tab.key)"
                        class="flex items-center gap-2 whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors"
                        :class="[
                            activeTab === tab.key
                                ? 'border-primary text-primary font-semibold'
                                : 'border-transparent text-muted-foreground hover:border-muted-foreground/30 hover:text-foreground',
                        ]"
                    >
                        <span>{{ tab.label }}</span>
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums transition-colors"
                            :class="[
                                activeTab === tab.key
                                    ? 'bg-primary/10 text-primary'
                                    : 'bg-muted text-muted-foreground',
                            ]"
                        >
                            {{ tab.count }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- Empty States per Tab -->
            <div
                v-if="eventsList.length === 0"
                class="flex min-h-[380px] flex-col items-center justify-center rounded-xl border border-dashed border-border p-8 text-center"
            >
                <template v-if="activeTab === 'upcoming'">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <Calendar class="h-8 w-8" />
                    </div>
                    <h2 class="mt-4 text-xl font-semibold text-foreground">No upcoming events scheduled</h2>
                    <p class="mt-2 max-w-sm text-sm text-muted-foreground">
                        No upcoming events scheduled. Click 'Schedule Event' to start planning.
                    </p>
                    <Button as-child class="mt-6">
                        <Link :href="eventsRoutes.create()">
                            <Plus class="mr-2 h-4 w-4" />
                            Schedule Event
                        </Link>
                    </Button>
                </template>

                <template v-else-if="activeTab === 'past'">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-muted text-muted-foreground">
                        <Archive class="h-8 w-8" />
                    </div>
                    <h2 class="mt-4 text-xl font-semibold text-foreground">No past events archived yet</h2>
                    <p class="mt-2 max-w-sm text-sm text-muted-foreground">
                        No past events archived yet. Completed events will automatically appear here.
                    </p>
                </template>

                <template v-else-if="activeTab === 'drafts'">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400">
                        <FileText class="h-8 w-8" />
                    </div>
                    <h2 class="mt-4 text-xl font-semibold text-foreground">No draft events</h2>
                    <p class="mt-2 max-w-sm text-sm text-muted-foreground">
                        No draft events. All events are actively scheduled.
                    </p>
                    <Button as-child variant="outline" class="mt-6">
                        <Link :href="eventsRoutes.create()">
                            <Plus class="mr-2 h-4 w-4" />
                            Create New Draft
                        </Link>
                    </Button>
                </template>

                <template v-else-if="activeTab === 'cancelled'">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-muted text-muted-foreground">
                        <Ban class="h-8 w-8" />
                    </div>
                    <h2 class="mt-4 text-xl font-semibold text-foreground">No cancelled events</h2>
                    <p class="mt-2 max-w-sm text-sm text-muted-foreground">
                        No cancelled events.
                    </p>
                </template>
            </div>

            <!-- Events Grid -->
            <div v-else class="space-y-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="event in eventsList"
                        :key="event.id"
                        class="group flex flex-col justify-between transition-all duration-200 hover:shadow-md"
                        :class="{
                            'opacity-90 bg-muted/20 border-muted-foreground/20': activeTab === 'past',
                            'border-destructive/30': event.status === 'cancelled',
                        }"
                    >
                        <CardHeader class="pb-3">
                            <div class="flex items-center justify-between gap-2">
                                <Badge :variant="getTypeBadgeVariant(event.type)" class="capitalize">
                                    {{ formatTypeName(event.type) }}
                                </Badge>

                                <!-- Concluded on [Date] for Past events -->
                                <Badge
                                    v-if="activeTab === 'past'"
                                    variant="outline"
                                    class="font-mono text-xs text-muted-foreground border-muted-foreground/30 bg-muted/40"
                                >
                                    Concluded on {{ formatDateShort(event.event_date) }}
                                </Badge>

                                <!-- Draft badge -->
                                <Badge
                                    v-else-if="event.status === 'draft'"
                                    variant="outline"
                                    class="text-xs bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/20"
                                >
                                    Draft
                                </Badge>

                                <!-- Cancelled badge -->
                                <Badge
                                    v-else-if="event.status === 'cancelled'"
                                    variant="destructive"
                                    class="text-xs"
                                >
                                    Cancelled
                                </Badge>

                                <!-- Standard countdown for Upcoming -->
                                <div
                                    v-else
                                    class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground"
                                >
                                    <Clock class="h-3.5 w-3.5" />
                                    <span :class="{ 'text-amber-500 font-semibold': !getCountdown(event.event_date).isPast }">
                                        {{ getCountdown(event.event_date).label }}
                                    </span>
                                </div>
                            </div>

                            <CardTitle
                                class="mt-2 text-xl font-semibold leading-tight line-clamp-1"
                                :class="{ 'line-through text-muted-foreground': event.status === 'cancelled' }"
                            >
                                <Link :href="eventsRoutes.show(event.id)" class="hover:underline">
                                    {{ event.title }}
                                </Link>
                            </CardTitle>

                            <CardDescription v-if="event.subtitle" class="line-clamp-1">
                                {{ event.subtitle }}
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-4 pb-4">
                            <div class="flex items-center text-sm text-muted-foreground">
                                <Calendar class="mr-2 h-4 w-4 shrink-0 text-foreground/70" />
                                <span>{{ formatDate(event.event_date) }}</span>
                            </div>

                            <!-- Deliverables badge for Past tab -->
                            <div v-if="activeTab === 'past'" class="flex items-center gap-2">
                                <Badge
                                    variant="secondary"
                                    class="text-xs bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/20"
                                >
                                    All deliverables archived
                                </Badge>
                            </div>

                            <!-- Task Completion Progress -->
                            <div class="space-y-1.5 rounded-lg bg-muted/50 p-3">
                                <div class="flex items-center justify-between text-xs font-medium">
                                    <span class="flex items-center gap-1 text-foreground/80">
                                        <CheckCircle2 class="h-3.5 w-3.5 text-primary" />
                                        Tasks Progress
                                    </span>
                                    <span class="text-muted-foreground font-mono">
                                        {{ event.completed_tasks_count ?? 0 }} / {{ event.tasks_count ?? 0 }} completed
                                    </span>
                                </div>

                                <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full bg-primary transition-all duration-500"
                                        :style="{
                                            width: (event.tasks_count && event.tasks_count > 0)
                                                ? `${Math.round(((event.completed_tasks_count ?? 0) / event.tasks_count) * 100)}%`
                                                : '0%',
                                        }"
                                    />
                                </div>
                            </div>
                        </CardContent>

                        <CardFooter class="pt-0">
                            <Button as-child variant="ghost" class="w-full justify-between group-hover:bg-accent">
                                <Link :href="eventsRoutes.show(event.id)">
                                    <span class="text-sm font-medium">
                                        {{ activeTab === 'past' ? 'View Archive' : 'Manage Tasks' }}
                                    </span>
                                    <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                                </Link>
                            </Button>
                        </CardFooter>
                    </Card>
                </div>

                <!-- Pagination Component -->
                <div
                    v-if="pagination && pagination.total > pagination.per_page"
                    class="flex flex-col items-center justify-between gap-4 border-t border-border pt-6 sm:flex-row"
                >
                    <div class="text-xs text-muted-foreground">
                        Showing
                        <span class="font-medium text-foreground">{{ pagination.from ?? 0 }}</span>
                        to
                        <span class="font-medium text-foreground">{{ pagination.to ?? 0 }}</span>
                        of
                        <span class="font-medium text-foreground">{{ pagination.total }}</span>
                        events
                    </div>

                    <nav class="flex items-center gap-1" aria-label="Pagination">
                        <template v-for="(link, i) in pagination.links" :key="i">
                            <span
                                v-if="!link.url"
                                class="inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-xs text-muted-foreground opacity-50"
                                v-html="link.label"
                            />
                            <Link
                                v-else
                                :href="link.url"
                                preserve-scroll
                                preserve-state
                                class="inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-xs font-medium transition-colors"
                                :class="[
                                    link.active
                                        ? 'bg-primary text-primary-foreground font-semibold shadow-xs'
                                        : 'border border-input bg-background hover:bg-accent hover:text-accent-foreground text-foreground',
                                ]"
                                v-html="link.label"
                            />
                        </template>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>
