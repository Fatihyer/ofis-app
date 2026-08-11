<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Task::class);

        $user = $request->user();
        $canManage = Task::userCanManage($user);
        $scope = $canManage ? $request->input('scope', 'all') : 'mine';

        $tasks = Task::query()
            ->with(['creator:id,name,email', 'assignees:id,name,email'])
            ->when($scope === 'mine', fn ($query) => $query->visibleTo($user))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->input('priority')))
            ->when($canManage && $request->filled('assigned_to'), function ($query) use ($request) {
                $query->whereHas('assignees', fn ($assignees) => $assignees->where('users.id', $request->integer('assigned_to')));
            })
            ->when($request->input('due') === 'overdue', fn ($query) => $query->whereNotNull('due_at')->where('due_at', '<', now())->whereNotIn('status', [Task::STATUS_DONE, Task::STATUS_CANCELLED]))
            ->when($request->input('due') === 'today', fn ($query) => $query->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()]))
            ->when($request->input('due') === 'week', fn ($query) => $query->whereBetween('due_at', [now()->startOfDay(), now()->addWeek()->endOfDay()]))
            ->orderByRaw("case when status = 'done' then 1 when status = 'cancelled' then 2 else 0 end")
            ->orderByRaw("case priority when 'urgent' then 0 when 'high' then 1 when 'normal' then 2 else 3 end")
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $users = $this->assignableUsers();

        return view('tasks.index', [
            'tasks' => $tasks,
            'users' => $users,
            'statuses' => Task::statuses(),
            'priorities' => Task::priorities(),
            'canManage' => $canManage,
            'scope' => $scope,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Task::class);

        return view('tasks.create', [
            'task' => new Task([
                'status' => Task::STATUS_OPEN,
                'priority' => Task::PRIORITY_NORMAL,
            ]),
            'users' => $this->assignableUsers(),
            'statuses' => Task::statuses(),
            'priorities' => Task::priorities(),
            'relatedTypes' => Task::relatedTypeOptions(),
        ]);
    }

    public function store(StoreTaskRequest $request)
    {
        $data = $this->validatedTaskData($request->validated());
        $data['created_by'] = $request->user()->id;
        $data['completed_at'] = $data['status'] === Task::STATUS_DONE ? now() : null;

        $task = Task::create($data);
        $this->syncAssignees($task, $request->input('assignees', []), $request->user()->id);
        $this->notifyNewAssignees($task, $request->input('assignees', []), $request->user());

        return redirect()->route('tasks.show', $task)->with('flash_message', 'Tâche créée.');
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);

        $task->load(['creator:id,name,email', 'assignees:id,name,email']);

        return view('tasks.show', [
            'task' => $task,
            'statuses' => Task::statuses(),
            'priorities' => Task::priorities(),
        ]);
    }

    public function edit(Task $task)
    {
        $this->authorize('update', $task);

        $task->load('assignees:id,name,email');

        return view('tasks.edit', [
            'task' => $task,
            'users' => $this->assignableUsers(),
            'statuses' => Task::statuses(),
            'priorities' => Task::priorities(),
            'relatedTypes' => Task::relatedTypeOptions(),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $data = $this->validatedTaskData($request->validated());
        $data['completed_at'] = $data['status'] === Task::STATUS_DONE
            ? ($task->completed_at ?: now())
            : null;

        $oldAssignees = $task->assignees()->pluck('users.id')->all();

        $task->update($data);
        $this->syncAssignees($task, $request->input('assignees', []), $request->user()->id);
        $newAssignees = array_values(array_diff(array_map('intval', $request->input('assignees', [])), array_map('intval', $oldAssignees)));
        $this->notifyNewAssignees($task, $newAssignees, $request->user());

        return redirect()->route('tasks.show', $task)->with('flash_message', 'Tâche mise à jour.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $this->authorize('changeStatus', $task);

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(Task::statuses()))],
        ]);

        $task->update([
            'status' => $data['status'],
            'completed_at' => $data['status'] === Task::STATUS_DONE ? now() : null,
        ]);

        return back()->with('flash_message', 'Statut de la tâche mis à jour.');
    }

    public function accept(Request $request, Task $task)
    {
        $this->authorize('accept', $task);

        if (!$task->isAssignedTo($request->user())) {
            $task->assignees()->attach($request->user()->id, ['assigned_by' => $request->user()->id]);
        }

        $task->update([
            'status' => Task::STATUS_IN_PROGRESS,
            'completed_at' => null,
        ]);

        return back()->with('flash_message', "Vous avez pris la tâche.");
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();

        return redirect()->route('tasks.index')->with('flash_message', 'Tâche supprimée.');
    }

    private function assignableUsers()
    {
        return User::query()
            ->whereNull('deleted_at')
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'Driver'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function validatedTaskData(array $data): array
    {
        $dueAt = !empty($data['due_at']) ? Carbon::parse($data['due_at']) : null;
        $taskableType = $data['taskable_type'] ?? null;
        $taskableId = $data['taskable_id'] ?? null;

        if (empty($taskableType) || empty($taskableId)) {
            $taskableType = null;
            $taskableId = null;
        }

        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'priority' => $data['priority'],
            'due_at' => $dueAt,
            'taskable_type' => $taskableType,
            'taskable_id' => $taskableId,
            'taskable_label' => $data['taskable_label'] ?? null,
        ];
    }

    private function syncAssignees(Task $task, array $assigneeIds, int $assignedBy): void
    {
        $sync = collect($assigneeIds)
            ->filter()
            ->unique()
            ->mapWithKeys(fn ($userId) => [(int) $userId => ['assigned_by' => $assignedBy]])
            ->all();

        $task->assignees()->sync($sync);
    }

    private function notifyNewAssignees(Task $task, array $assigneeIds, User $assignedBy): void
    {
        $users = User::whereIn('id', collect($assigneeIds)->filter()->unique()->all())->get();

        foreach ($users as $user) {
            $user->notify(new TaskAssignedNotification($task, $assignedBy));
        }
    }
}
