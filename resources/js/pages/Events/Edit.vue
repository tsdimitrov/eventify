<script setup lang="ts">
import { Head, Link, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    Calendar,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import eventsRoutes from '@/routes/events';
import type { Event, EventStatus, EventType } from '@/types/event';

const props = defineProps<{
    event: Event;
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Events',
            href: eventsRoutes.index(),
        },
        {
            title: props.event.title,
            href: eventsRoutes.show.url(props.event.id),
        },
        {
            title: 'Edit',
            href: eventsRoutes.edit.url(props.event.id),
        },
    ],
});

function formatForDateTimeLocal(dateString: string | null | undefined): string {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';
    const pad = (n: number) => String(n).padStart(2, '0');
    const yyyy = date.getFullYear();
    const mm = pad(date.getMonth() + 1);
    const dd = pad(date.getDate());
    const hh = pad(date.getHours());
    const min = pad(date.getMinutes());
    return `${yyyy}-${mm}-${dd}T${hh}:${min}`;
}

const form = useForm({
    title: props.event.title,
    subtitle: props.event.subtitle ?? '',
    description: props.event.description ?? '',
    event_date: formatForDateTimeLocal(props.event.event_date),
    type: props.event.type as EventType,
    status: props.event.status as EventStatus,
    reschedule_tasks: true,
});

const showDeleteDialog = ref(false);
const isDeleting = ref(false);

function submit(): void {
    form.put(eventsRoutes.update.url(props.event.id));
}

function confirmDelete(): void {
    isDeleting.value = true;
    router.delete(eventsRoutes.destroy.url(props.event.id), {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteDialog.value = false;
        },
    });
}
</script>

<template>
    <div>
        <Head :title="`Edit ${event.title}`" />

        <div class="mx-auto max-w-3xl space-y-6 p-4 md:p-8">
            <div class="flex items-center justify-between">
                <Button as-child variant="ghost" size="sm">
                    <Link :href="eventsRoutes.show.url(event.id)">
                        <ArrowLeft class="mr-1.5 h-4 w-4" />
                        Back to Event
                    </Link>
                </Button>
            </div>

            <!-- Main Edit Form Card -->
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <Calendar class="h-5 w-5" />
                        </div>
                        <div>
                            <CardTitle class="text-2xl font-bold">Edit Event</CardTitle>
                            <CardDescription>
                                Update event details, postpone dates, or adjust event lifecycle status.
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

                        <!-- Event Type and Status Grid -->
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

                            <!-- Status Field -->
                            <div class="space-y-2">
                                <Label for="status">
                                    Status <span class="text-destructive">*</span>
                                </Label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    required
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
                                    :class="{ 'border-destructive': form.errors.status }"
                                >
                                    <option value="draft" class="bg-popover text-popover-foreground">
                                        Draft
                                    </option>
                                    <option value="scheduled" class="bg-popover text-popover-foreground">
                                        Scheduled
                                    </option>
                                    <option value="completed" class="bg-popover text-popover-foreground">
                                        Completed
                                    </option>
                                    <option value="cancelled" class="bg-popover text-popover-foreground">
                                        Cancelled
                                    </option>
                                </select>
                                <InputError :message="form.errors.status" />
                            </div>
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

                        <!-- Intelligent Rescheduling Checkbox Box -->
                        <div class="rounded-lg border border-primary/20 bg-primary/5 p-4">
                            <div class="flex items-start gap-3">
                                <Checkbox
                                    id="reschedule_tasks"
                                    v-model:checked="form.reschedule_tasks"
                                    class="mt-0.5"
                                />
                                <div class="space-y-1">
                                    <Label
                                        for="reschedule_tasks"
                                        class="cursor-pointer font-medium text-foreground"
                                    >
                                        Automatically shift due dates of pending tasks
                                    </Label>
                                    <p class="text-xs text-muted-foreground">
                                        If the event date is moved forward or backward, all uncompleted (pending & in-progress) tasks will automatically shift by the exact difference in time. Completed tasks remain unaltered.
                                    </p>
                                </div>
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

                        <!-- Submit Buttons -->
                        <div class="flex items-center justify-end gap-3 pt-4">
                            <Button as-child variant="outline">
                                <Link :href="eventsRoutes.show.url(event.id)">
                                    Cancel
                                </Link>
                            </Button>

                            <Button type="submit" :disabled="form.processing">
                                <Spinner v-if="form.processing" class="mr-2" />
                                <span>{{ form.processing ? 'Saving Changes...' : 'Save Changes' }}</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Danger Zone / Archive Event Card -->
            <Card class="border-destructive/30">
                <CardHeader>
                    <div class="flex items-center gap-3 text-destructive">
                        <AlertTriangle class="h-5 w-5" />
                        <CardTitle class="text-lg font-bold">Danger Zone</CardTitle>
                    </div>
                    <CardDescription>
                        Archive this event to hide it from the active dashboard. Associated tasks and history are preserved.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-muted-foreground">
                            Once archived, this event will no longer appear in active schedules or task boards.
                        </div>
                        <Button
                            variant="destructive"
                            size="sm"
                            type="button"
                            @click="showDeleteDialog = true"
                        >
                            <Trash2 class="mr-1.5 h-4 w-4" />
                            Archive Event
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Archive Confirmation Dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Archive Event</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to archive <strong>"{{ event.title }}"</strong>? This event will be moved to the archive and removed from active operations boards.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button
                        variant="outline"
                        type="button"
                        :disabled="isDeleting"
                        @click="showDeleteDialog = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        type="button"
                        :disabled="isDeleting"
                        @click="confirmDelete"
                    >
                        <Spinner v-if="isDeleting" class="mr-2" />
                        <span>{{ isDeleting ? 'Archiving...' : 'Confirm Archive' }}</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
