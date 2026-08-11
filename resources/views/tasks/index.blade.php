@extends('layouts.app')

@section('content')
@php
    $statusColors = [
        'open' => 'secondary',
        'in_progress' => 'primary',
        'waiting' => 'warning',
        'done' => 'success',
        'cancelled' => 'dark',
    ];
    $priorityColors = [
        'low' => 'secondary',
        'normal' => 'info',
        'high' => 'warning',
        'urgent' => 'danger',
    ];
@endphp

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h3 mb-0">Tâches</h1>
        @if(auth()->user()->can('create', \App\Models\Task::class))
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">Nouvelle tâche</a>
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('tasks.index') }}" class="row align-items-end">
                @if($canManage)
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Vue</label>
                        <select name="scope" class="form-control">
                            <option value="all" @selected($scope === 'all')>Toutes</option>
                            <option value="mine" @selected($scope === 'mine')>Mes tâches</option>
                        </select>
                    </div>
                @endif

                <div class="col-md-2 mb-2">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-control">
                        <option value="">Tous</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <label class="form-label">Priorité</label>
                    <select name="priority" class="form-control">
                        <option value="">Toutes</option>
                        @foreach($priorities as $value => $label)
                            <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <label class="form-label">Échéance</label>
                    <select name="due" class="form-control">
                        <option value="">Toutes</option>
                        <option value="overdue" @selected(request('due') === 'overdue')>En retard</option>
                        <option value="today" @selected(request('due') === 'today')>Aujourd'hui</option>
                        <option value="week" @selected(request('due') === 'week')>7 jours</option>
                    </select>
                </div>

                @if($canManage)
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Assignée à</label>
                        <select name="assigned_to" class="form-control">
                            <option value="">Tous</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected((string) request('assigned_to') === (string) $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-1 mb-2">
                    <button class="btn btn-outline-primary w-100" type="submit">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tâche</th>
                        <th>Statut</th>
                        <th>Priorité</th>
                        <th>Assignée à</th>
                        <th>Échéance</th>
                        <th>Lien</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr>
                            <td>
                                <a href="{{ route('tasks.show', $task) }}" class="fw-bold">{{ $task->title }}</a>
                                <div class="small text-muted">Créée par {{ $task->creator->name ?? '-' }}</div>
                            </td>
                            <td><span class="badge bg-{{ $statusColors[$task->status] ?? 'secondary' }}">{{ $task->statusLabel() }}</span></td>
                            <td><span class="badge bg-{{ $priorityColors[$task->priority] ?? 'secondary' }}">{{ $task->priorityLabel() }}</span></td>
                            <td>
                                @forelse($task->assignees as $assignee)
                                    <span class="badge bg-light text-dark border">{{ $assignee->name }}</span>
                                @empty
                                    <span class="text-muted">-</span>
                                @endforelse
                            </td>
                            <td>
                                @if($task->due_at)
                                    <span class="{{ $task->due_at->isPast() && !in_array($task->status, ['done', 'cancelled'], true) ? 'text-danger fw-bold' : '' }}">
                                        {{ $task->due_at->format('d/m/Y H:i') }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $task->taskable_label ?: '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('tasks.show', $task) }}" class="btn btn-sm btn-outline-secondary">Voir</a>
                                @can('update', $task)
                                    <a href="{{ route('tasks.edit', $task) }}" class="btn btn-sm btn-outline-primary">Modifier</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Aucune tâche.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tasks->hasPages())
            <div class="card-footer">
                {{ $tasks->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
