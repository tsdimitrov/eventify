<script setup lang="ts">
import { Head, Link, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    Calendar,
    Check,
    CheckCircle2,
    Clock,
    ExternalLink,
    FileText,
    Link as LinkIcon,
    Pencil,
    Plus,
    Sparkles,
    User,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import eventsRoutes from '@/routes/events';
import SocialLaunchpad from '@/components/SocialLaunchpad.vue';
import tasksRoutes from '@/routes/tasks';
import type { Event, SocialDrafts, Task, TaskStatus } from '@/types/event';

const props = defineProps<{
    event: Event;
    socialDrafts: SocialDrafts;
    bannerAssetUrl?: string | null;
}>();

const effectiveBannerUrl = computed(() => {
    return props.event.tasks?.find((t) => t.assigned_role === 'designer')?.asset_url ?? props.bannerAssetUrl ?? null;
});

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
    ],
});

// Modal state for editing asset URL
const editingTask = ref<Task | null>(null);
const assetUrlInput = ref('');
const isUpdatingAsset = ref(false);

function openAssetModal(task: Task): void {
    editingTask.value = task;
    assetUrlInput.value = task.asset_url ?? '';
}

function closeAssetModal(): void {
    editingTask.value = null;
    assetUrlInput.value = '';
}

function saveAssetUrl(): void {
    if (!editingTask.value) return;

    isUpdatingAsset.value = true;
    router.patch(
        tasksRoutes.update.url(editingTask.value.id),
        { asset_url: assetUrlInput.value.trim() || null },
        {
            preserveScroll: true,
            onFinish: () => {
                isUpdatingAsset.value = false;
                closeAssetModal();
            },
        },
    );
}

// Updating task status directly
function updateStatus(task: Task, newStatus: TaskStatus): void {
    if (task.status === newStatus) return;

    router.patch(
        tasksRoutes.update.url(task.id),
        { status: newStatus },
        {
            preserveScroll: true,
        },
    );
}

// Date & Relative T-minus helpers
function formatEventDate(dateString: string): string {
    return new Intl.DateTimeFormat('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(dateString));
}

function formatTaskDueDate(dueDate: string): string {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(dueDate));
}

function getEventCountdown(dateString: string): string {
    const target = new Date(dateString).getTime();
    const now = Date.now();
    const diffDays = Math.ceil((target - now) / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'T-0 Days (Today)';
    if (diffDays > 0) return `T-${diffDays} Days`;
    return `T+${Math.abs(diffDays)} Days (Past)`;
}

function getTaskTimelineBadge(taskDueDate: string, eventDate: string): {
    label: string;
    badgeClass: string;
} {
    const taskTime = new Date(taskDueDate);
    const eventTime = new Date(eventDate);

    const taskDay = new Date(taskTime.getFullYear(), taskTime.getMonth(), taskTime.getDate()).getTime();
    const eventDay = new Date(eventTime.getFullYear(), eventTime.getMonth(), eventTime.getDate()).getTime();
    const diffDays = Math.round((taskDay - eventDay) / (1000 * 60 * 60 * 24));

    if (diffDays === 0) {
        return {
            label: 'T-0: Event Day',
            badgeClass: 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        };
    }

    if (diffDays < 0) {
        return {
            label: `T${diffDays} Days: Preparation`,
            badgeClass: 'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300 border-blue-200 dark:border-blue-800',
        };
    }

    return {
        label: `T+${diffDays} Days: Post-Event Wrap-up`,
        badgeClass: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
    };
}

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

const showCreateTaskDialog = ref(false);

const createTaskForm = useForm({
    event_id: props.event.id,
    title: '',
    description: '',
    due_date: formatForDateTimeLocal(props.event.event_date),
    assigned_role: 'operations',
    assigned_to: '',
});

function openCreateTaskModal(): void {
    createTaskForm.reset();
    createTaskForm.event_id = props.event.id;
    createTaskForm.due_date = formatForDateTimeLocal(props.event.event_date);
    createTaskForm.assigned_role = 'operations';
    createTaskForm.clearErrors();
    showCreateTaskDialog.value = true;
}

function submitCreateTask(): void {
    createTaskForm.post(tasksRoutes.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateTaskDialog.value = false;
            createTaskForm.reset();
        },
    });
}

function formatTypeName(type: string): string {
    return type.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
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

// Progress metrics
const totalTasks = computed(() => props.event.tasks?.length ?? 0);
const completedTasks = computed(
    () => props.event.tasks?.filter((t) => t.status === 'completed').length ?? 0,
);
const inProgressTasks = computed(
    () => props.event.tasks?.filter((t) => t.status === 'in_progress').length ?? 0,
);
const progressPercent = computed(() => {
    if (totalTasks.value === 0) return 0;
    return Math.round((completedTasks.value / totalTasks.value) * 100);
});
</script>

<template>
    <div>
        <Head :title="event.title" />

        <div class="flex flex-col gap-6 p-4 md:p-8">
            <!-- Back navigation -->
            <div class="flex items-center justify-between">
                <Button as-child variant="ghost" size="sm">
                    <Link :href="eventsRoutes.index()">
                        <ArrowLeft class="mr-1.5 h-4 w-4" />
                        Back to Events
                    </Link>
                </Button>

                <div class="flex items-center gap-2">
                    <Button as-child variant="outline" size="sm">
                        <Link :href="eventsRoutes.edit.url(event.id)">
                            <Pencil class="mr-1.5 h-3.5 w-3.5" />
                            Edit Event
                        </Link>
                    </Button>

                    <Badge variant="outline" class="text-xs uppercase tracking-wider">
                        {{ event.status }}
                    </Badge>
                </div>
            </div>

            <!-- Header Section -->
            <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                <CardHeader class="pb-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge variant="default" class="capitalize">
                                    {{ formatTypeName(event.type) }}
                                </Badge>
                                <Badge variant="secondary" class="font-mono font-semibold">
                                    {{ getEventCountdown(event.event_date) }}
                                </Badge>
                            </div>

                            <CardTitle class="text-2xl font-bold tracking-tight text-foreground md:text-3xl">
                                {{ event.title }}
                            </CardTitle>

                            <CardDescription v-if="event.subtitle" class="text-base text-muted-foreground">
                                {{ event.subtitle }}
                            </CardDescription>
                        </div>

                        <div class="flex items-center gap-2 rounded-lg bg-muted/60 px-4 py-2 text-sm text-foreground">
                            <Calendar class="h-4 w-4 text-primary" />
                            <span class="font-medium">{{ formatEventDate(event.event_date) }}</span>
                        </div>
                    </div>

                    <p v-if="event.description" class="mt-3 text-sm text-muted-foreground whitespace-pre-line">
                        {{ event.description }}
                    </p>
                </CardHeader>

                <CardContent class="space-y-4 pt-0">
                    <!-- Metadata tags/cards if present -->
                    <div
                        v-if="event.metadata && Object.keys(event.metadata).length > 0"
                        class="grid grid-cols-1 gap-3 rounded-lg border border-border/60 bg-muted/30 p-3 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div
                            v-for="(val, key) in event.metadata"
                            :key="key"
                            class="flex flex-col text-xs"
                        >
                            <span class="font-medium uppercase tracking-wider text-muted-foreground">
                                {{ String(key).replace(/_/g, ' ') }}
                            </span>
                            <span class="mt-0.5 font-medium text-foreground">
                                <template v-if="Array.isArray(val)">
                                    {{ val.join(', ') }}
                                </template>
                                <template v-else>
                                    {{ String(val) }}
                                </template>
                            </span>
                        </div>
                    </div>

                    <!-- Progress overview -->
                    <div class="rounded-lg bg-muted/50 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 text-sm font-medium">
                            <span class="flex items-center gap-1.5 text-foreground">
                                <CheckCircle2 class="h-4 w-4 text-primary" />
                                Overall Progress: {{ completedTasks }} of {{ totalTasks }} Tasks Completed
                            </span>
                            <div class="flex items-center gap-3 text-xs text-muted-foreground">
                                <span>{{ inProgressTasks }} in progress</span>
                                <span>{{ totalTasks - completedTasks - inProgressTasks }} pending</span>
                            </div>
                        </div>

                        <div class="mt-2.5 h-2.5 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full bg-primary transition-all duration-500"
                                :style="{ width: `${progressPercent}%` }"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Main Content Grid (Desktop: Tasks on left 60%, Social Launchpad on right 40%) -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 items-start">
                <!-- Task Management Board / List -->
                <div class="space-y-4 lg:col-span-7">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-xl font-bold tracking-tight text-foreground">
                                Execution Tasks
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Scheduled chronologically according to event offset templates.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="text-xs text-muted-foreground font-mono">
                                {{ event.tasks?.length ?? 0 }} total tasks
                            </span>
                            <Button size="sm" @click="openCreateTaskModal">
                                <Plus class="mr-1.5 h-4 w-4" />
                                Add Custom Task
                            </Button>
                        </div>
                    </div>

                    <!-- Empty state if no tasks -->
                    <div
                        v-if="!event.tasks || event.tasks.length === 0"
                        class="rounded-xl border border-dashed border-border p-8 text-center text-muted-foreground"
                    >
                        <p>No tasks generated for this event.</p>
                        <Button size="sm" class="mt-4" @click="openCreateTaskModal">
                            <Plus class="mr-1.5 h-4 w-4" />
                            Add First Task
                        </Button>
                    </div>

                    <!-- Tasks List -->
                    <div v-else class="space-y-3">
                        <Card
                            v-for="task in event.tasks"
                            :key="task.id"
                            class="transition-colors hover:border-primary/40"
                            :class="{
                                'opacity-75 bg-muted/20': task.status === 'completed',
                            }"
                        >
                            <CardContent class="p-4 sm:p-5">
                                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                    <!-- Task Details -->
                                    <div class="space-y-2 flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <!-- Role Badge -->
                                            <span
                                                v-if="task.assigned_role"
                                                :class="getRoleBadgeClass(task.assigned_role)"
                                                class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-semibold capitalize"
                                            >
                                                {{ task.assigned_role.replace(/_/g, ' ') }}
                                            </span>

                                            <!-- Relative T-minus / T-plus and Due Date -->
                                            <span
                                                :class="getTaskTimelineBadge(task.due_date, event.event_date).badgeClass"
                                                class="inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-semibold font-mono"
                                            >
                                                <Clock class="h-3 w-3" />
                                                {{ getTaskTimelineBadge(task.due_date, event.event_date).label }}
                                            </span>

                                            <span class="inline-flex items-center text-xs text-muted-foreground font-mono">
                                                Due {{ formatTaskDueDate(task.due_date) }}
                                            </span>

                                            <!-- Assignee if any -->
                                            <span
                                                v-if="task.assigned_to"
                                                class="inline-flex items-center gap-1 text-xs text-muted-foreground"
                                            >
                                                <User class="h-3 w-3" />
                                                {{ task.assigned_to }}
                                            </span>
                                        </div>

                                        <!-- Task Title -->
                                        <h3
                                            class="text-base font-semibold leading-tight text-foreground"
                                            :class="{ 'line-through text-muted-foreground': task.status === 'completed' }"
                                        >
                                            {{ task.title }}
                                        </h3>

                                        <!-- Parsed Description -->
                                        <p
                                            v-if="task.description"
                                            class="text-xs text-muted-foreground leading-relaxed max-w-3xl"
                                        >
                                            {{ task.description }}
                                        </p>
                                    </div>

                                    <!-- Actions & Status Selector -->
                                    <div class="flex flex-wrap items-center gap-2 shrink-0 pt-2 xl:pt-0">
                                        <!-- Asset URL link or Add Asset Button -->
                                        <div class="flex items-center">
                                            <a
                                                v-if="task.asset_url"
                                                :href="task.asset_url"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center gap-1.5 rounded-md border border-border bg-background px-2.5 py-1 text-xs font-medium text-foreground hover:bg-accent hover:text-accent-foreground"
                                            >
                                                <LinkIcon class="h-3.5 w-3.5 text-primary" />
                                                <span>View Asset</span>
                                                <ExternalLink class="h-3 w-3 opacity-60" />
                                            </a>

                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                class="text-xs text-muted-foreground hover:text-foreground"
                                                @click="openAssetModal(task)"
                                            >
                                                <Pencil class="mr-1 h-3 w-3" />
                                                {{ task.asset_url ? 'Edit' : 'Attach Link' }}
                                            </Button>
                                        </div>

                                        <!-- Interactive Status Toggle (pending -> in_progress -> completed) -->
                                        <div class="flex items-center rounded-lg border border-border p-0.5 bg-muted/40">
                                            <button
                                                type="button"
                                                @click="updateStatus(task, 'pending')"
                                                class="rounded px-2.5 py-1 text-xs font-medium transition-all"
                                                :class="
                                                    task.status === 'pending'
                                                        ? 'bg-background text-foreground shadow-xs'
                                                        : 'text-muted-foreground hover:text-foreground'
                                                "
                                            >
                                                Pending
                                            </button>

                                            <button
                                                type="button"
                                                @click="updateStatus(task, 'in_progress')"
                                                class="rounded px-2.5 py-1 text-xs font-medium transition-all"
                                                :class="
                                                    task.status === 'in_progress'
                                                        ? 'bg-amber-500/20 text-amber-700 dark:text-amber-300 font-semibold shadow-xs'
                                                        : 'text-muted-foreground hover:text-foreground'
                                                "
                                            >
                                                In Progress
                                            </button>

                                            <button
                                                type="button"
                                                @click="updateStatus(task, 'completed')"
                                                class="flex items-center gap-1 rounded px-2.5 py-1 text-xs font-medium transition-all"
                                                :class="
                                                    task.status === 'completed'
                                                        ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 font-semibold shadow-xs'
                                                        : 'text-muted-foreground hover:text-foreground'
                                                "
                                            >
                                                <Check v-if="task.status === 'completed'" class="h-3 w-3" />
                                                Completed
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Social Launchpad & Asset Hub (Right 40% -> lg:col-span-5) -->
                <div class="space-y-4 lg:col-span-5">
                    <SocialLaunchpad
                        :event="event"
                        :drafts="socialDrafts"
                        :banner-url="effectiveBannerUrl"
                    />
                </div>
            </div>

            <!-- Asset URL Edit Modal -->
            <Dialog :open="editingTask !== null" @update:open="(val: boolean) => !val && closeAssetModal()">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Attach Asset / Drive Link</DialogTitle>
                        <DialogDescription>
                            Link design files, copy drafts, or shared folders for
                            <strong>{{ editingTask?.title }}</strong>.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-3 py-2">
                        <Label for="asset_url">Asset URL (Google Drive, Figma, Docs, etc.)</Label>
                        <Input
                            id="asset_url"
                            v-model="assetUrlInput"
                            type="url"
                            placeholder="https://drive.google.com/..."
                            autocomplete="off"
                        />
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="closeAssetModal">
                            Cancel
                        </Button>
                        <Button :disabled="isUpdatingAsset" @click="saveAssetUrl">
                            {{ isUpdatingAsset ? 'Saving...' : 'Save Link' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Add Custom Task Modal -->
            <Dialog v-model:open="showCreateTaskDialog">
                <DialogContent class="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Add Custom Task</DialogTitle>
                        <DialogDescription>
                            Create an ad-hoc or post-event operational task linked to this event.
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitCreateTask" class="space-y-4 py-2">
                        <!-- Task Title -->
                        <div class="space-y-2">
                            <Label for="task_title">
                                Task Title <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="task_title"
                                v-model="createTaskForm.title"
                                type="text"
                                placeholder="e.g. Upload recording to Drive or Survey blast"
                                required
                                :class="{ 'border-destructive': createTaskForm.errors.title }"
                            />
                            <InputError :message="createTaskForm.errors.title" />
                        </div>

                        <!-- Due Date & Role Grid -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="task_due_date">
                                    Due Date & Time <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="task_due_date"
                                    v-model="createTaskForm.due_date"
                                    type="datetime-local"
                                    required
                                    :class="{ 'border-destructive': createTaskForm.errors.due_date }"
                                />
                                <InputError :message="createTaskForm.errors.due_date" />
                            </div>

                            <div class="space-y-2">
                                <Label for="task_assigned_role">
                                    Assigned Role <span class="text-destructive">*</span>
                                </Label>
                                <select
                                    id="task_assigned_role"
                                    v-model="createTaskForm.assigned_role"
                                    required
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
                                    :class="{ 'border-destructive': createTaskForm.errors.assigned_role }"
                                >
                                    <option value="operations" class="bg-popover text-popover-foreground">Operations</option>
                                    <option value="marketing_lead" class="bg-popover text-popover-foreground">Marketing Lead</option>
                                    <option value="designer" class="bg-popover text-popover-foreground">Designer</option>
                                    <option value="copywriter" class="bg-popover text-popover-foreground">Copywriter</option>
                                </select>
                                <InputError :message="createTaskForm.errors.assigned_role" />
                            </div>
                        </div>

                        <!-- Assignee (optional) -->
                        <div class="space-y-2">
                            <Label for="task_assigned_to">Assignee Name / Email</Label>
                            <Input
                                id="task_assigned_to"
                                v-model="createTaskForm.assigned_to"
                                type="text"
                                placeholder="e.g. Alex Dimitrov or alex@example.com"
                                :class="{ 'border-destructive': createTaskForm.errors.assigned_to }"
                            />
                            <InputError :message="createTaskForm.errors.assigned_to" />
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label for="task_description">Description / Notes</Label>
                            <textarea
                                id="task_description"
                                v-model="createTaskForm.description"
                                rows="3"
                                placeholder="Provide specific operational instructions or requirements..."
                                class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
                                :class="{ 'border-destructive': createTaskForm.errors.description }"
                            />
                            <InputError :message="createTaskForm.errors.description" />
                        </div>

                        <DialogFooter class="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                :disabled="createTaskForm.processing"
                                @click="showCreateTaskDialog = false"
                            >
                                Cancel
                            </Button>
                            <Button type="submit" :disabled="createTaskForm.processing">
                                <Spinner v-if="createTaskForm.processing" class="mr-2" />
                                <span>{{ createTaskForm.processing ? 'Adding Task...' : 'Add Task' }}</span>
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
