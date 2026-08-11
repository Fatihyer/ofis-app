@extends('layouts.app')

@section('title', '| Users')

@section('content')
@php
    $canFullyManageUsers = Auth::user()->hasPermissionTo('Administer roles & permissions');
    $now = now();
@endphp
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h1>
                    <i class="fa fa-users"></i> {{ $canFullyManageUsers ? 'Administration utilisateurs' : 'Utilisateurs chauffeurs' }}
                    @if($canFullyManageUsers)
                        <a href="{{ route('roles.index') }}" class="btn btn-default pull-right">Rôles</a>
                        <a href="{{ route('permissions.index') }}" class="btn btn-default pull-right">Permissions</a>
                    @endif
                </h1>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Date d'ajout</th>
                                <th>Rôles</th>
                                <th>Statut</th>
                                <th>Dernière connexion</th>
                                <th>Providers <i class="fa fa-eye" aria-hidden="true"></i></th>
                                <th>Opérations</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $user->roles->pluck('name')->implode(' ') }}</td>
                                    <td>
                                        @php
                                            $lastSeen = $user->last_seen_at ? \Carbon\Carbon::parse($user->last_seen_at) : null;
                                            $minutesSinceSeen = $lastSeen ? $lastSeen->diffInMinutes($now) : null;
                                        @endphp
                                        @if($lastSeen && $minutesSinceSeen <= 5)
                                            <span class="badge badge-success">En ligne</span>
                                            <br><span class="text-muted">{{ $lastSeen->format('d/m/Y H:i') }}</span>
                                        @elseif($lastSeen && $minutesSinceSeen <= 30)
                                            <span class="badge badge-warning">Récemment actif</span>
                                            <br><span class="text-muted">{{ $lastSeen->format('d/m/Y H:i') }}</span>
                                        @elseif($lastSeen)
                                            <span class="badge badge-secondary">Hors ligne</span>
                                            <br><span class="text-muted">{{ $lastSeen->format('d/m/Y H:i') }}</span>
                                        @else
                                            <span class="badge badge-secondary">Inconnu</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->last_login_at)
                                            <strong>{{ \Carbon\Carbon::parse($user->last_login_at)->format('d/m/Y H:i') }}</strong>
                                            <br>
                                            <span class="text-muted">{{ $user->last_login_ip ?: 'IP non disponible' }}</span>
                                        @else
                                            <span class="text-muted">Jamais connecté</span>
                                        @endif
                                    </td>
                                    <td>
                                        @foreach ($user->acentes as $acente)
                                            {{ $acente->name }},
                                        @endforeach
                                    </td>
                                    <td>
                                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-info pull-left" style="margin-right: 3px;">Modifier</a>
                                        <a href="{{ route('users.pass', $user->id) }}" class="btn btn-warning pull-left" style="margin-right: 3px;">Mot de passe</a>
                                        @if($canFullyManageUsers)
                                            {!! Form::open(['method' => 'DELETE', 'route' => ['users.destroy', $user->id] ]) !!}
                                            {!! Form::submit('Supprimer', ['class' => 'btn btn-danger']) !!}
                                            {!! Form::close() !!}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <a href="{{ route('users.create') }}" class="btn btn-success">Ajouter un utilisateur</a>
            </div>
        </div>
    </div>
</div>
@endsection
