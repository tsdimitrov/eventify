<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Event;
use Carbon\CarbonInterface;

class RescheduleEventTasksAction
{
    /**
     * Reschedule all uncompleted tasks for an event based on the date shift.
     */
    public function execute(Event $event, CarbonInterface $oldDate, CarbonInterface $newDate): int
    {
        if ($oldDate->equalTo($newDate)) {
            return 0;
        }

        $diffInSeconds = (int) $oldDate->diffInSeconds($newDate, false);

        if ($diffInSeconds === 0) {
            return 0;
        }

        $tasks = $event->tasks()
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_date')
            ->get();

        $count = 0;

        foreach ($tasks as $task) {
            $task->due_date = $task->due_date->copy()->addSeconds($diffInSeconds);
            $task->save();
            $count++;
        }

        return $count;
    }
}
