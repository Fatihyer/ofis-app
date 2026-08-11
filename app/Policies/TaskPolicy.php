<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ($ability !== 'create' && $user->can('tasks.manage')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $this->canManageOfficeTasks($user)
            || (int) $task->created_by === (int) $user->id
            || $task->isAssignedTo($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Superadmin');
    }

    public function update(User $user, Task $task): bool
    {
        return $this->canManageOfficeTasks($user)
            || (int) $task->created_by === (int) $user->id
            || $user->can('tasks.update');
    }

    public function changeStatus(User $user, Task $task): bool
    {
        return $this->update($user, $task) || $task->isAssignedTo($user);
    }

    public function accept(User $user, Task $task): bool
    {
        return $this->view($user, $task) && !in_array($task->status, [Task::STATUS_DONE, Task::STATUS_CANCELLED], true);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->canManageOfficeTasks($user) || $user->can('tasks.delete');
    }

    private function canManageOfficeTasks(User $user): bool
    {
        return $user->hasAnyRole(['Superadmin', 'Admin'])
            || $user->can('tasks.manage');
    }
}
