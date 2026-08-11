@extends('layouts.app')

@section('title', '| Modifier le rôle')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-1"><i class="fa fa-key"></i> Modifier le rôle</h1>
            <div class="text-muted">{{ $role->name }}</div>
        </div>
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">Retour</a>
    </div>

    {{ Form::model($role, ['route' => ['roles.update', $role->id], 'method' => 'PUT']) }}
        <div class="card mb-3" style="border-radius:8px;">
            <div class="card-body">
                <div class="form-group mb-0">
                    {{ Form::label('name', 'Nom du rôle') }}
                    {{ Form::text('name', null, ['class' => 'form-control', 'maxlength' => 60]) }}
                </div>
            </div>
        </div>

        @include('roles.partials.permissions_matrix', ['role' => $role, 'permissionGroups' => $permissionGroups])

        <div class="mt-3">
            {{ Form::submit('Enregistrer', ['class' => 'btn btn-primary']) }}
        </div>
    {{ Form::close() }}
</div>
@endsection
