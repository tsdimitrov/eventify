<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Calendar, CheckCircle2, Clock, Plus, Sparkles } from '@lucide/vue';
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
import type { Event } from '@/types/event';

defineProps<{
    events: Event[];
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
                        Plan and coordinate scheduled events with automated task generation.
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

            <!-- Empty State -->
            <div
                v-if="events.length === 0"
                class="flex min-h-[400px] flex-col items-center justify-center rounded-xl border border-dashed border-border p-8 text-center"
            >
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <Calendar class="h-8 w-8" />
                </div>
                <h2 class="mt-4 text-xl font-semibold text-foreground">No events scheduled</h2>
                <p class="mt-2 max-w-sm text-sm text-muted-foreground">
                    Get started by creating your first event. Associated task templates will be generated automatically.
                </p>
                <Button as-child class="mt-6">
                    <Link :href="eventsRoutes.create()">
                        <Plus class="mr-2 h-4 w-4" />
                        Create Your First Event
                    </Link>
                </Button>
            </div>

            <!-- Events Grid -->
            <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="event in events"
                    :key="event.id"
                    class="group flex flex-col justify-between transition-all duration-200 hover:shadow-md"
                >
                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between gap-2">
                            <Badge :variant="getTypeBadgeVariant(event.type)" class="capitalize">
                                {{ formatTypeName(event.type) }}
                            </Badge>

                            <div class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                <Clock class="h-3.5 w-3.5" />
                                <span :class="{ 'text-amber-500 font-semibold': !getCountdown(event.event_date).isPast }">
                                    {{ getCountdown(event.event_date).label }}
                                </span>
                            </div>
                        </div>

                        <CardTitle class="mt-2 text-xl font-semibold leading-tight line-clamp-1">
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
                                            : '0%'
                                    }"
                                />
                            </div>
                        </div>
                    </CardContent>

                    <CardFooter class="pt-0">
                        <Button as-child variant="ghost" class="w-full justify-between group-hover:bg-accent">
                            <Link :href="eventsRoutes.show(event.id)">
                                <span class="text-sm font-medium">Manage Tasks</span>
                                <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </Link>
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </div>
</template>
