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
        <div>
            <h1 class="h3 mb-1">{{ $task->title }}</h1>
            <div class="text-muted">Créée par {{ $task->creator->name ?? '-' }} le {{ $task->created_at?->format('d/m/Y H:i') }}</div>
        </div>
        <div>
            <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">Retour</a>
            @can('accept', $task)
                <form method="POST" action="{{ route('tasks.accept', $task) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">J’ai pris la tâche</button>
                </form>
            @endcan
            @can('update', $task)
                <a href="{{ route('tasks.edit', $task) }}" class="btn btn-primary">Modifier</a>
            @endcan
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-3">
            <div class="card h-100">
                <div class="card-header"><strong>Détails</strong></div>
                <div class="card-body">
                    <div class="mb-3" style="white-space: pre-wrap;">{{ $task->description ?: '-' }}</div>

                    @if($task->taskable_label || $task->taskable_type || $task->taskable_id)
                        <div class="border rounded bg-light p-3">
                            <strong>Lien</strong>
                            <div>{{ $task->taskable_label ?: '-' }}</div>
                            @if($task->taskable_type && $task->taskable_id)
                                <small class="text-muted">{{ class_basename($task->taskable_type) }} #{{ $task->taskable_id }}</small>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card mb-3">
                <div class="card-header"><strong>Suivi</strong></div>
                <div class="card-body">
                    <div class="mb-2">
                        <span class="badge bg-{{ $statusColors[$task->status] ?? 'secondary' }}">{{ $task->statusLabel() }}</span>
                        <span class="badge bg-{{ $priorityColors[$task->priority] ?? 'secondary' }}">{{ $task->priorityLabel() }}</span>
                    </div>
                    <div class="mb-2"><strong>Échéance:</strong> {{ $task->due_at ? $task->due_at->format('d/m/Y H:i') : '-' }}</div>
                    <div class="mb-2"><strong>Terminée:</strong> {{ $task->completed_at ? $task->completed_at->format('d/m/Y H:i') : '-' }}</div>

                    @can('changeStatus', $task)
                        <form method="POST" action="{{ route('tasks.status', $task) }}" class="mt-3">
                            @csrf
                            @method('PATCH')
                            <label class="form-label">Changer le statut</label>
                            <div class="input-group">
                                <select name="status" class="form-control">
                                    @foreach($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-outline-primary" type="submit">OK</button>
                            </div>
                        </form>
                    @endcan
                </div>
            </div>

            <div class="card">
                <div class="card-header"><strong>Assignée à</strong></div>
                <div class="card-body">
                    @forelse($task->assignees as $assignee)
                        <div class="border rounded p-2 mb-2">
                            <strong>{{ $assignee->name }}</strong>
                            @if($assignee->email)
                                <div class="small text-muted">{{ $assignee->email }}</div>
                            @endif
                        </div>
                    @empty
                        <span class="text-muted">Aucun utilisateur assigné.</span>
                    @endforelse
                </div>
            </div>

            @can('delete', $task)
                <form method="POST" action="{{ route('tasks.destroy', $task) }}" class="mt-3" onsubmit="return confirm('Supprimer cette tâche ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100">Supprimer</button>
                </form>
            @endcan
        </div>
    </div>
</div>
@endsection
