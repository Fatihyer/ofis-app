<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Task $task,
        private ?User $assignedBy = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Nouvelle tâche assignée',
            'message' => ($this->assignedBy?->name ?: 'Le bureau') . ' vous a assigné la tâche: ' . $this->task->title,
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'assigned_by' => $this->assignedBy?->name,
            'url' => route('tasks.show', $this->task),
        ];
    }
}
