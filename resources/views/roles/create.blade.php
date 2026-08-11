@extends('layouts.app')

@section('title', '| Ajouter un rôle')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-1"><i class="fa fa-key"></i> Ajouter un rôle</h1>
            <div class="text-muted">Sélectionnez les permissions par module.</div>
        </div>
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">Retour</a>
    </div>

    {{ Form::open(['url' => 'roles']) }}
        <div class="card mb-3" style="border-radius:8px;">
            <div class="card-body">
                <div class="form-group mb-0">
                    {{ Form::label('name', 'Nom du rôle') }}
                    {{ Form::text('name', null, ['class' => 'form-control', 'maxlength' => 60]) }}
                </div>
            </div>
        </div>

        @include('roles.partials.permissions_matrix', ['permissionGroups' => $permissionGroups])

        <div class="mt-3">
            {{ Form::submit('Ajouter', ['class' => 'btn btn-primary']) }}
        </div>
    {{ Form::close() }}
</div>
@endsection
