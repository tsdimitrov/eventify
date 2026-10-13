export type EventType = 'webinar' | 'conference' | 'internal_meeting' | 'workshop';
export type EventStatus = 'draft' | 'scheduled' | 'completed' | 'cancelled';
export type TaskStatus = 'pending' | 'in_progress' | 'completed';

export interface Task {
    id: number;
    event_id: number;
    title: string;
    description: string | null;
    due_date: string;
    status: TaskStatus;
    assigned_role: string | null;
    assigned_to: string | null;
    asset_url: string | null;
    event?: Event;
    created_at?: string;
    updated_at?: string;
}

export interface Event {
    id: number;
    title: string;
    subtitle: string | null;
    description: string | null;
    event_date: string;
    type: EventType;
    status: EventStatus;
    metadata: Record<string, unknown> | null;
    tasks_count?: number;
    completed_tasks_count?: number;
    tasks?: Task[];
    created_at?: string;
    updated_at?: string;
}

export interface SocialDraftItem {
    platform: string;
    character_limit?: number;
    subject?: string;
    text: string;
    hashtags?: string[];
}

export interface SocialDrafts {
    linkedin: SocialDraftItem;
    twitter: SocialDraftItem;
    email: SocialDraftItem;
    [key: string]: SocialDraftItem | undefined;
}

export interface DashboardKPI {
    active_events_count: number;
    pending_tasks_count: number;
    overdue_tasks_count: number;
    completed_this_week_count: number;
}

export interface RedFlags {
    overdue_tasks: Task[];
    at_risk_events: Event[];
}

export interface UpcomingEvent extends Event {
    tasks_count: number;
    completed_tasks_count: number;
    overdue_tasks_count: number;
}


