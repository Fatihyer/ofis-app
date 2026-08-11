@extends('layouts.app')

@section('title', '| Permissions')

@section('style')
<style>
.perm-page{background:#f8fafc;min-height:calc(100vh - 90px);padding:16px}.perm-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:14px}.perm-head h1{margin:0;font-size:25px;font-weight:850;color:#0f172a}.perm-head small{color:#64748b;font-weight:750}.perm-actions{display:flex;gap:6px;flex-wrap:wrap}.perm-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.perm-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 22px rgba(15,23,42,.06);overflow:hidden}.perm-card-h{padding:12px 14px;border-bottom:1px solid #e5e7eb;background:#f8fafc;display:flex;justify-content:space-between}.perm-card-h strong{color:#0f172a}.perm-list{padding:10px;display:grid;gap:7px}.perm-row{display:flex;justify-content:space-between;gap:8px;align-items:center;border:1px solid #e5e7eb;border-radius:8px;padding:8px;background:#fff}.perm-name{font-weight:850;color:#334155}.perm-tools{display:flex;gap:5px;align-items:center}.delete-inline{display:inline}@media(max-width:900px){.perm-grid{grid-template-columns:1fr}.perm-head{display:block}.perm-actions{margin-top:10px}.perm-page{padding:10px}}
</style>
@endsection

@section('content')
<div class="perm-page">
    <div class="perm-head">
        <div><h1>Permissions</h1><small>{{ $permissions->count() }} permission(s) classées par module.</small></div>
        <div class="perm-actions"><a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">Utilisateurs</a><a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">Rôles</a><a href="{{ route('permissions.create') }}" class="btn btn-success btn-sm">Ajouter une permission</a></div>
    </div>
    <div class="perm-grid">
        @foreach($permissionGroups as $module => $items)
            <div class="perm-card">
                <div class="perm-card-h"><strong>{{ ucfirst(str_replace(['-', '_'], ' ', $module)) }}</strong><span class="text-muted small">{{ $items->count() }}</span></div>
                <div class="perm-list">
                    @foreach($items as $permission)
                        <div class="perm-row">
                            <span class="perm-name">{{ $permission->name }}</span>
                            <span class="perm-tools">
                                <a href="{{ route('permissions.edit', $permission->id) }}" class="btn btn-outline-secondary btn-sm">Modifier</a>
                                {!! Form::open(['method' => 'DELETE', 'route' => ['permissions.destroy', $permission->id], 'class' => 'delete-inline', 'onsubmit' => "return confirm('Supprimer cette permission ?');"]) !!}
                                {!! Form::submit('Supprimer', ['class' => 'btn btn-outline-danger btn-sm']) !!}
                                {!! Form::close() !!}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
