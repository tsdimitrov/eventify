<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Info } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import eventsRoutes from '@/routes/events';
import type { EventType } from '@/types/event';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Events',
                href: eventsRoutes.index(),
            },
            {
                title: 'Create Event',
                href: eventsRoutes.create(),
            },
        ],
    },
});

const form = useForm({
    title: '',
    subtitle: '',
    description: '',
    event_date: '',
    type: 'webinar' as EventType,
});

function submit(): void {
    form.post(eventsRoutes.store.url());
}
</script>

<template>
    <div>
        <Head title="Create Event" />

        <div class="mx-auto max-w-3xl p-4 md:p-8">
            <div class="mb-6 flex items-center gap-2">
                <Button as-child variant="ghost" size="sm">
                    <Link :href="eventsRoutes.index()">
                        <ArrowLeft class="mr-1 h-4 w-4" />
                        Back to Events
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <Calendar class="h-5 w-5" />
                        </div>
                        <div>
                            <CardTitle class="text-2xl font-bold">Create New Event</CardTitle>
                            <CardDescription>
                                Set up the event details. Automated task schedules will be generated upon creation.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>

                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Title Field -->
                        <div class="space-y-2">
                            <Label for="title">
                                Event Title <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                type="text"
                                placeholder="e.g. Q4 Global Tech Summit 2026"
                                required
                                :class="{ 'border-destructive': form.errors.title }"
                            />
                            <InputError :message="form.errors.title" />
                        </div>

                        <!-- Subtitle Field -->
                        <div class="space-y-2">
                            <Label for="subtitle">Subtitle / Tagline</Label>
                            <Input
                                id="subtitle"
                                v-model="form.subtitle"
                                type="text"
                                placeholder="e.g. Innovations in Artificial Intelligence and Cloud Systems"
                                :class="{ 'border-destructive': form.errors.subtitle }"
                            />
                            <InputError :message="form.errors.subtitle" />
                        </div>

                        <!-- Event Type and Date Grid -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <!-- Type Field -->
                            <div class="space-y-2">
                                <Label for="type">
                                    Event Type <span class="text-destructive">*</span>
                                </Label>
                                <select
                                    id="type"
                                    v-model="form.type"
                                    required
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
                                    :class="{ 'border-destructive': form.errors.type }"
                                >
                                    <option value="webinar" class="bg-popover text-popover-foreground">
                                        Webinar
                                    </option>
                                    <option value="conference" class="bg-popover text-popover-foreground">
                                        Conference
                                    </option>
                                    <option value="workshop" class="bg-popover text-popover-foreground">
                                        Workshop
                                    </option>
                                    <option value="internal_meeting" class="bg-popover text-popover-foreground">
                                        Internal Meeting
                                    </option>
                                </select>
                                <InputError :message="form.errors.type" />
                            </div>

                            <!-- Date Field -->
                            <div class="space-y-2">
                                <Label for="event_date">
                                    Date & Time <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="event_date"
                                    v-model="form.event_date"
                                    type="datetime-local"
                                    required
                                    :class="{ 'border-destructive': form.errors.event_date }"
                                />
                                <InputError :message="form.errors.event_date" />
                            </div>
                        </div>

                        <!-- Description Field -->
                        <div class="space-y-2">
                            <Label for="description">Event Description</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                placeholder="Describe the agenda, target audience, and primary objectives..."
                                class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
                                :class="{ 'border-destructive': form.errors.description }"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <!-- Info box about automated task templates -->
                        <div class="flex items-start gap-3 rounded-lg border border-primary/20 bg-primary/5 p-4 text-sm text-foreground">
                            <Info class="h-5 w-5 shrink-0 text-primary" />
                            <div>
                                <p class="font-medium text-foreground">
                                    Automated Task Scheduling
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    Based on the selected <strong>{{ form.type }}</strong> type, tasks with offset due dates (promos, copy, deck prep, invitations) will be computed and created automatically.
                                </p>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex items-center justify-end gap-3 pt-4">
                            <Button as-child variant="outline">
                                <Link :href="eventsRoutes.index()">
                                    Cancel
                                </Link>
                            </Button>

                            <Button type="submit" :disabled="form.processing">
                                <Spinner v-if="form.processing" class="mr-2" />
                                <span>{{ form.processing ? 'Scheduling Event...' : 'Create Event' }}</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
