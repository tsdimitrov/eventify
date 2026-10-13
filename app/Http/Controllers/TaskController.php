<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    /**
     * Display a listing of tasks across events.
     */
    public function index(Request $request): Response
    {
        $tasks = Task::query()
            ->with(['event:id,title,type,event_date'])
            ->when($request->filled('role') && $request->role !== 'all', fn (Builder $query): Builder => $query->where('assigned_role', $request->role))
            ->when($request->filled('status') && $request->status !== 'all', fn (Builder $query): Builder => $query->where('status', $request->status))
            ->orderBy('due_date', 'asc')
            ->get();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'filters' => $request->only(['role', 'status']),
            'availableRoles' => Task::distinct()->whereNotNull('assigned_role')->pluck('assigned_role'),
        ]);
    }

    /**
     * Update the specified task.
     */
    public function update(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'required', 'string', 'in:pending,in_progress,completed'],
            'asset_url' => ['nullable', 'url'],
            'assigned_to' => ['nullable', 'string', 'max:255'],
        ]);

        $task->update($validated);

        return back()->with('success', 'Task updated.');
    }
}
