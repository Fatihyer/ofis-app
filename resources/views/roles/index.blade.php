@extends('layouts.app')

@section('title', '| Rôles')

@section('style')
<style>
.roles-page{background:#f8fafc;min-height:calc(100vh - 90px);padding:16px}.roles-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:14px}.roles-head h1{margin:0;font-size:25px;font-weight:850;color:#0f172a}.roles-head small{color:#64748b;font-weight:750}.roles-actions{display:flex;gap:6px;flex-wrap:wrap}.roles-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.role-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 22px rgba(15,23,42,.06);overflow:hidden}.role-card-h{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;padding:12px 14px;border-bottom:1px solid #e5e7eb}.role-card-h h4{margin:0;font-size:18px;font-weight:850;color:#0f172a}.role-counts{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}.role-count{border-radius:999px;background:#eef2ff;color:#3730a3;padding:4px 8px;font-weight:850;font-size:12px}.role-count.users{background:#dcfce7;color:#166534}.role-body{padding:12px 14px}.role-users{border:1px solid #e5e7eb;border-radius:8px;background:#f8fafc;margin-bottom:12px;overflow:hidden}.role-users-head{display:flex;justify-content:space-between;gap:10px;align-items:center;padding:9px 10px;border-bottom:1px solid #e5e7eb}.role-users-head strong{color:#0f172a}.role-users-body{padding:10px}.role-users-select{min-height:118px;font-size:13px}.role-user-tags{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}.role-user-tag{border:1px solid #dbe4ef;background:#fff;border-radius:999px;padding:4px 8px;font-weight:750;font-size:12px;color:#334155}.role-protected{color:#64748b;font-weight:750;font-size:12px;margin:0}.perm-group{border:1px solid #e5e7eb;border-radius:8px;margin-bottom:10px;overflow:hidden}.perm-group summary{cursor:pointer;background:#f8fafc;padding:8px 10px;font-weight:850;color:#334155;list-style:none}.perm-group summary::-webkit-details-marker{display:none}.perm-checks{display:flex;flex-wrap:wrap;gap:7px;padding:10px}.perm-checks label{border:1px solid #e5e7eb;border-radius:999px;padding:5px 8px;background:#fff;font-weight:750;font-size:12px;margin:0}.perm-checks input{margin-right:4px}.role-footer{display:flex;justify-content:space-between;gap:8px;align-items:center;padding:12px 14px;border-top:1px solid #e5e7eb;background:#fff}.delete-inline{display:inline}.role-danger{color:#991b1b}.role-tools{display:flex;gap:5px;flex-wrap:wrap}@media(max-width:1100px){.roles-grid{grid-template-columns:1fr}.roles-page{padding:10px}.roles-head{display:block}.roles-actions{margin-top:10px}}
</style>
@endsection

@section('content')
<div class="roles-page">
    <div class="roles-head">
        <div>
            <h1>Rôles & permissions</h1>
                            <small>Regrouper les utilisateurs bureau et attribuer les permissions par rôle.</small>
        </div>
        <div class="roles-actions">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">Utilisateurs</a>
            <a href="{{ route('permissions.index') }}" class="btn btn-outline-secondary btn-sm">Permissions</a>
            <a href="{{ route('roles.create') }}" class="btn btn-success btn-sm">Ajouter un rôle</a>
        </div>
    </div>

    <div class="roles-grid">
        @foreach($roles as $role)
            <div class="role-card">
                <div class="role-card-h">
                        <div>
                            <h4>{{ $role->name }}</h4>
                            <div class="text-muted small">Guard: {{ $role->guard_name }}</div>
                        </div>
                        <div class="role-counts">
                            <span class="role-count users">{{ $role->users->count() }} utilisateur(s)</span>
                            <span class="role-count">{{ $role->permissions->count() }} permission(s)</span>
                        </div>
                </div>
                <div class="role-body">
                        <div class="role-users">
                            <div class="role-users-head">
                                <strong>Utilisateurs du groupe</strong>
                                <small class="text-muted">{{ $role->name }}</small>
                            </div>
                            <div class="role-users-body">
                                @if(in_array($role->name, ['Superadmin', 'Driver'], true))
                                    <p class="role-protected">Rôle protégé: modification des utilisateurs désactivée ici.</p>
                                @else
                                    <form method="POST" action="{{ route('roles.users.update', $role->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <select name="users[]" multiple class="form-control role-users-select">
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ $role->users->contains('id', $user->id) ? 'selected' : '' }}>
                                                    {{ $user->name }} - {{ $user->email }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-outline-primary btn-sm mt-2">Enregistrer les utilisateurs</button>
                                    </form>
                                @endif

                                @if($role->users->isNotEmpty())
                                    <div class="role-user-tags">
                                        @foreach($role->users->sortBy('name')->take(10) as $roleUser)
                                            <span class="role-user-tag">{{ $roleUser->name }}</span>
                                        @endforeach
                                        @if($role->users->count() > 10)
                                            <span class="role-user-tag">+{{ $role->users->count() - 10 }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                </div>

                <form method="POST" action="{{ route('roles.permissions.update', $role->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="role-body">
                        @foreach($permissionGroups as $group)
                            @php
                                $groupChecked = $group['permissions']->filter(fn($permission) => $role->permissions->contains('id', $permission->id))->count();
                            @endphp
                            <details class="perm-group" {{ $groupChecked ? 'open' : '' }}>
                                <summary>{{ $group['label'] }} <span class="text-muted small">{{ $groupChecked }}/{{ $group['permissions']->count() }}</span></summary>
                                <div class="perm-checks">
                                    @foreach($group['permissions'] as $permission)
                                        <label>
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" {{ $role->permissions->contains('id', $permission->id) ? 'checked' : '' }}>
                                            {{ $permission->label }}
                                            @if($permission->bucket === 'other')<span class="text-muted">({{ $permission->action }})</span>@endif
                                        </label>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>
                    <div class="role-footer">
                        <button class="btn btn-primary btn-sm">Enregistrer</button>
                        <div class="role-tools">
                            <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-outline-secondary btn-sm">Renommer</a>
                        </div>
                    </div>
                </form>
                @if(!in_array($role->name, ['Superadmin', 'Admin', 'Driver'], true))
                    <div class="px-3 pb-3 text-right">
                        {!! Form::open(['method' => 'DELETE', 'route' => ['roles.destroy', $role->id], 'class' => 'delete-inline', 'onsubmit' => "return confirm('Supprimer ce rôle ?');"]) !!}
                        {!! Form::submit('Supprimer', ['class' => 'btn btn-outline-danger btn-sm']) !!}
                        {!! Form::close() !!}
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
