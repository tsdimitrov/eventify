<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Event;
use App\Models\Task;
use App\Models\TaskTemplate;
use Illuminate\Database\Eloquent\Collection;

class GenerateTasksForEventAction
{
    /**
     * Execute the action to generate tasks for an event based on task templates.
     *
     * @return Collection<int, Task>
     */
    public function execute(Event $event, bool $force = false): Collection
    {
        if (! $force && $event->tasks()->exists()) {
            return $event->tasks()->get();
        }

        $templates = TaskTemplate::where('event_type', $event->type)->get();

        if ($templates->isEmpty()) {
            return new Collection;
        }

        $tasksData = $templates->map(fn (TaskTemplate $template): array => [
            'title' => $template->title,
            'description' => $this->parseDescription($template->description_template, $event),
            'due_date' => $event->event_date->copy()->addDays($template->offset_days),
            'status' => 'pending',
            'assigned_role' => $template->assigned_role,
        ])->all();

        /** @var Collection<int, Task> $tasks */
        $tasks = $event->tasks()->createMany($tasksData);

        return $tasks;
    }

    /**
     * Invoke the action.
     *
     * @return Collection<int, Task>
     */
    public function __invoke(Event $event, bool $force = false): Collection
    {
        return $this->execute($event, $force);
    }

    /**
     * Parse simple template placeholder tags within a description template.
     */
    protected function parseDescription(?string $template, Event $event): ?string
    {
        if ($template === null) {
            return null;
        }

        return preg_replace([
            '/\{\{\s*(event_)?title\s*\}\}/i',
            '/\{\{\s*(event_)?date\s*\}\}/i',
            '/\{\{\s*(event_)?type\s*\}\}/i',
        ], [
            $event->title,
            $event->event_date->format('Y-m-d'),
            $event->type,
        ], $template) ?? $template;
    }
}
