@extends('layouts.app')

@section('style')
<style>
.ng-page{background:#f8fafc;min-height:calc(100vh - 90px);padding:16px}.ng-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:14px}.ng-head h1{margin:0;font-size:25px;font-weight:850;color:#0f172a}.ng-head small{color:#64748b;font-weight:750}.ng-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.ng-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 22px rgba(15,23,42,.06);overflow:hidden}.ng-card-h{display:flex;justify-content:space-between;gap:10px;align-items:center;padding:12px 14px;border-bottom:1px solid #e5e7eb;background:#fff}.ng-card-h strong{color:#0f172a}.ng-body{padding:14px}.ng-group{border:1px solid #e5e7eb;border-radius:8px;padding:12px;margin-bottom:10px;background:#fff}.ng-group h5{margin:0 0 4px;font-weight:850}.ng-desc{color:#64748b;font-size:12px;margin-bottom:8px}.ng-select{min-height:130px}.ng-table th{font-size:12px;text-transform:uppercase;color:#475569;background:#f8fafc;white-space:nowrap}.ng-table td{vertical-align:middle;font-size:13px}.ng-checks{display:flex;flex-wrap:wrap;gap:8px}.ng-checks label{border:1px solid #e5e7eb;border-radius:999px;padding:5px 8px;background:#f8fafc;font-weight:750;margin:0}@media(max-width:1000px){.ng-grid{grid-template-columns:1fr}.ng-page{padding:10px}}
</style>
@endsection

@section('content')
<div class="ng-page">
    <div class="ng-head"><div><h1>Groupes de notifications</h1><small>Choisir quels utilisateurs voient quels toasters.</small></div></div>
    <div class="ng-grid">
        <div class="ng-card"><div class="ng-card-h"><strong>Utilisateurs par groupe</strong></div><div class="ng-body">
            @foreach($groups as $group)
                <form method="POST" action="{{ route('notification-groups.users', $group->id) }}" class="ng-group">
                    @csrf @method('PUT')
                    <h5>{{ $group->name }}</h5><div class="ng-desc">{{ $group->description }}</div>
                    <select name="users[]" multiple class="form-control ng-select">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $group->users->contains('id', $user->id) ? 'selected' : '' }}>{{ $user->name }} - {{ $user->email }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-primary mt-2">Enregistrer</button>
                </form>
            @endforeach
        </div></div>
        <div class="ng-card"><div class="ng-card-h"><strong>Alertes par groupe</strong></div>
            <form method="POST" action="{{ route('notification-groups.alert-types') }}">@csrf @method('PUT')
                <div class="table-responsive"><table class="table table-sm ng-table mb-0"><thead><tr><th>Alerte</th><th>Niveau</th><th>Groupes</th></tr></thead><tbody>
                @foreach($alertTypes as $alert)
                    <tr><td><strong>{{ $alert->name }}</strong><div class="text-muted small">{{ $alert->slug }}</div></td><td>{{ $alert->level }}</td><td><div class="ng-checks">
                    @foreach($groups as $group)
                        <label><input type="checkbox" name="alert_groups[{{ $alert->id }}][]" value="{{ $group->id }}" {{ $alert->groups->contains('id', $group->id) ? 'checked' : '' }}> {{ $group->name }}</label>
                    @endforeach
                    </div></td></tr>
                @endforeach
                </tbody></table></div><div class="p-3 border-top"><button class="btn btn-primary">Enregistrer les alertes</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
