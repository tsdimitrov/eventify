<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import {
    AlertCircle,
    Calendar,
    Check,
    CheckCircle2,
    CheckSquare,
    Clock,
    ExternalLink,
    Filter,
    Layers,
    Link as LinkIcon,
    Pencil,
    User,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import tasksRoutes from '@/routes/tasks';
import type { Task, TaskStatus } from '@/types/event';

const props = defineProps<{
    tasks: Task[];
    filters: {
        role?: string;
        status?: string;
    };
    availableRoles: string[];
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Team Task Board',
            href: tasksRoutes.index(),
        },
    ],
});

// Filter states
const activeRole = computed(() => props.filters.role || 'all');
const activeStatus = computed(() => props.filters.status || 'all');

// All unique roles to display in filter pills
const filterRoles = computed(() => {
    const set = new Set<string>();
    // Pre-populate with standard team roles if not already present
    ['designer', 'copywriter', 'marketing_lead', 'operations'].forEach((r) => set.add(r));
    props.availableRoles.forEach((r) => set.add(r));
    return Array.from(set);
});

function applyFilter(key: 'role' | 'status', value: string): void {
    const newRole = key === 'role' ? value : activeRole.value;
    const newStatus = key === 'status' ? value : activeStatus.value;

    router.get(
        tasksRoutes.index.url(),
        {
            role: newRole === 'all' ? undefined : newRole,
            status: newStatus === 'all' ? undefined : newStatus,
        },
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        },
    );
}

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

// Urgency calculation & formatted dates
function getDueUrgency(dueDateString: string, status: string): {
    label: string;
    badgeClass: string;
    isOverdue: boolean;
} {
    if (status === 'completed') {
        return {
            label: 'Completed',
            badgeClass:
                'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            isOverdue: false,
        };
    }

    const due = new Date(dueDateString).getTime();
    const now = Date.now();
    const diffHours = (due - now) / (1000 * 60 * 60);
    const diffDays = Math.ceil(diffHours / 24);

    if (diffHours < 0) {
        return {
            label: `Overdue (${Math.abs(diffDays)}d ago)`,
            badgeClass:
                'bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-300 border-red-200 dark:border-red-800 font-semibold',
            isOverdue: true,
        };
    }

    if (diffHours <= 48) {
        return {
            label: `Due within 48h`,
            badgeClass:
                'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border-amber-200 dark:border-amber-800 font-semibold',
            isOverdue: false,
        };
    }

    if (diffDays <= 7) {
        return {
            label: `Due in ${diffDays}d`,
            badgeClass:
                'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border-amber-200 dark:border-amber-800 font-medium',
            isOverdue: false,
        };
    }

    return {
        label: `Due in ${diffDays}d`,
        badgeClass: 'bg-muted text-muted-foreground border-border font-medium',
        isOverdue: false,
    };
}

function formatDueDate(dueDate: string): string {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(dueDate));
}

function formatRoleName(role: string | null): string {
    if (!role) return 'Unassigned';
    return role.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
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
</script>

<template>
    <div>
        <Head title="Team Task Board" />

        <div class="flex flex-col gap-6 p-4 md:p-8">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground md:text-3xl">
                        Team Task Board
                    </h1>
                    <p class="text-sm text-muted-foreground md:text-base">
                        Cross-event task hub for tracking design, copy, marketing, and operations deadlines.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Button as-child variant="outline" size="sm">
                        <Link :href="eventsRoutes.index()">
                            <Calendar class="mr-1.5 h-4 w-4" />
                            View Events
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Filter Controls -->
            <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                <CardContent class="space-y-4 p-4 sm:p-5">
                    <!-- Role Filter Pills -->
                    <div class="space-y-2">
                        <div class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            <User class="h-3.5 w-3.5" />
                            <span>Filter By Role</span>
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5">
                            <button
                                type="button"
                                @click="applyFilter('role', 'all')"
                                class="rounded-full px-3 py-1 text-xs font-medium transition-colors"
                                :class="
                                    activeRole === 'all'
                                        ? 'bg-primary text-primary-foreground shadow-xs'
                                        : 'bg-muted/70 text-muted-foreground hover:bg-muted hover:text-foreground'
                                "
                            >
                                All Roles
                            </button>

                            <button
                                v-for="role in filterRoles"
                                :key="role"
                                type="button"
                                @click="applyFilter('role', role)"
                                class="rounded-full px-3 py-1 text-xs font-medium transition-colors"
                                :class="
                                    activeRole === role
                                        ? 'bg-primary text-primary-foreground shadow-xs'
                                        : 'bg-muted/70 text-muted-foreground hover:bg-muted hover:text-foreground'
                                "
                            >
                                {{ formatRoleName(role) }}
                            </button>
                        </div>
                    </div>

                    <!-- Status Filter Tabs -->
                    <div class="space-y-2 pt-2 border-t border-border/60">
                        <div class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            <Filter class="h-3.5 w-3.5" />
                            <span>Filter By Status</span>
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5">
                            <button
                                type="button"
                                @click="applyFilter('status', 'all')"
                                class="rounded-md px-3 py-1 text-xs font-medium transition-colors"
                                :class="
                                    activeStatus === 'all'
                                        ? 'bg-accent text-accent-foreground font-semibold'
                                        : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground'
                                "
                            >
                                All Statuses
                            </button>

                            <button
                                type="button"
                                @click="applyFilter('status', 'pending')"
                                class="rounded-md px-3 py-1 text-xs font-medium transition-colors"
                                :class="
                                    activeStatus === 'pending'
                                        ? 'bg-accent text-accent-foreground font-semibold'
                                        : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground'
                                "
                            >
                                Pending
                            </button>

                            <button
                                type="button"
                                @click="applyFilter('status', 'in_progress')"
                                class="rounded-md px-3 py-1 text-xs font-medium transition-colors"
                                :class="
                                    activeStatus === 'in_progress'
                                        ? 'bg-amber-500/20 text-amber-700 dark:text-amber-300 font-semibold'
                                        : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground'
                                "
                            >
                                In Progress
                            </button>

                            <button
                                type="button"
                                @click="applyFilter('status', 'completed')"
                                class="rounded-md px-3 py-1 text-xs font-medium transition-colors"
                                :class="
                                    activeStatus === 'completed'
                                        ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 font-semibold'
                                        : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground'
                                "
                            >
                                Completed
                            </button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Tasks Summary & Count -->
            <div class="flex items-center justify-between text-xs text-muted-foreground">
                <span class="font-medium text-foreground">
                    Showing {{ tasks.length }} {{ tasks.length === 1 ? 'task' : 'tasks' }}
                </span>
                <span v-if="activeRole !== 'all' || activeStatus !== 'all'">
                    Filtered active
                </span>
            </div>

            <!-- Empty State -->
            <div
                v-if="tasks.length === 0"
                class="flex min-h-[300px] flex-col items-center justify-center rounded-xl border border-dashed border-border p-8 text-center"
            >
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <CheckSquare class="h-7 w-7" />
                </div>
                <h3 class="mt-4 text-lg font-semibold text-foreground">No tasks found</h3>
                <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                    No tasks match the selected role and status filters. Try resetting the filters or create a new event.
                </p>
                <Button
                    v-if="activeRole !== 'all' || activeStatus !== 'all'"
                    variant="outline"
                    size="sm"
                    class="mt-4"
                    @click="
                        router.get(
                            tasksRoutes.index.url(),
                            {},
                            { preserveState: true, replace: true, preserveScroll: true },
                        )
                    "
                >
                    Clear Filters
                </Button>
            </div>

            <!-- Task Cards View -->
            <div v-else class="space-y-3">
                <Card
                    v-for="task in tasks"
                    :key="task.id"
                    class="transition-all hover:border-primary/40"
                    :class="{
                        'opacity-75 bg-muted/20': task.status === 'completed',
                        'border-red-400/50 dark:border-red-800/60': getDueUrgency(task.due_date, task.status).isOverdue,
                    }"
                >
                    <CardContent class="p-4 sm:p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <!-- Left: Task Details & Associated Event -->
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

                                    <!-- Due Date & Urgency Highlight Badge -->
                                    <span
                                        :class="getDueUrgency(task.due_date, task.status).badgeClass"
                                        class="inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs"
                                    >
                                        <Clock class="h-3 w-3" />
                                        <span>{{ getDueUrgency(task.due_date, task.status).label }}</span>
                                        <span class="opacity-70 font-mono">({{ formatDueDate(task.due_date) }})</span>
                                    </span>

                                    <!-- Associated Event link -->
                                    <Link
                                        v-if="task.event"
                                        :href="eventsRoutes.show.url(task.event.id)"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                                    >
                                        <Calendar class="h-3 w-3" />
                                        <span>{{ task.event.title }}</span>
                                    </Link>
                                </div>

                                <!-- Task Title -->
                                <h3
                                    class="text-base font-semibold leading-tight text-foreground"
                                    :class="{ 'line-through text-muted-foreground': task.status === 'completed' }"
                                >
                                    {{ task.title }}
                                </h3>

                                <!-- Parsed Description Template Preview -->
                                <p
                                    v-if="task.description"
                                    class="text-xs text-muted-foreground leading-relaxed line-clamp-2 max-w-3xl"
                                >
                                    {{ task.description }}
                                </p>
                            </div>

                            <!-- Right: Status Toggle & Asset Link Actions -->
                            <div class="flex flex-wrap items-center gap-3 shrink-0 pt-2 lg:pt-0">
                                <!-- Asset URL Link & Edit Button -->
                                <div class="flex items-center gap-1.5">
                                    <a
                                        v-if="task.asset_url"
                                        :href="task.asset_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 rounded-md border border-border bg-background px-2.5 py-1 text-xs font-medium text-foreground hover:bg-accent hover:text-accent-foreground"
                                    >
                                        <LinkIcon class="h-3.5 w-3.5 text-primary" />
                                        <span>Asset</span>
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

                                <!-- Instant Status Toggle -->
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

            <!-- Asset URL Modal -->
            <Dialog :open="editingTask !== null" @update:open="(val: boolean) => !val && closeAssetModal()">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Submit / Attach Asset URL</DialogTitle>
                        <DialogDescription>
                            Attach Google Drive, Figma, or documentation link for
                            <strong>{{ editingTask?.title }}</strong>.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-3 py-2">
                        <Label for="task_asset_url">Asset URL</Label>
                        <Input
                            id="task_asset_url"
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
                            {{ isUpdatingAsset ? 'Saving...' : 'Save Asset URL' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
