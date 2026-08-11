@php
    $selectedPermissionIds = collect(old('permissions', isset($role) ? $role->permissions->pluck('id')->all() : []))->map(fn($id) => (int) $id)->all();
    $buckets = [
        'view' => 'Voir',
        'create' => 'Créer',
        'update' => 'Modifier',
        'delete' => 'Supprimer',
        'manage' => 'Gérer',
        'other' => 'Autres',
    ];
@endphp

<style>
    .permission-matrix-wrap {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
    }
    .permission-matrix {
        margin: 0;
        min-width: 860px;
    }
    .permission-matrix thead th {
        background: #111827;
        color: #fff;
        border-color: #111827;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .02em;
        text-align: center;
        vertical-align: middle;
    }
    .permission-matrix td {
        vertical-align: top;
        text-align: center;
    }
    .permission-module {
        text-align: left !important;
        min-width: 190px;
    }
    .permission-module strong {
        display: block;
        color: #0f172a;
        font-size: 14px;
    }
    .permission-module small {
        color: #64748b;
        font-weight: 700;
    }
    .permission-cell {
        display: grid;
        gap: 6px;
        justify-items: start;
        text-align: left;
        min-width: 120px;
    }
    .permission-check {
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0;
        font-size: 12px;
        font-weight: 700;
        color: #334155;
    }
    .permission-check input {
        width: 15px;
        height: 15px;
    }
    .permission-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
</style>

<div class="permission-actions">
    <h5 class="mb-0"><b>Permissions</b></h5>
    <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-outline-primary" id="checkAllPermissions">Tout cocher</button>
        <button type="button" class="btn btn-outline-secondary" id="clearAllPermissions">Tout vider</button>
    </div>
</div>

<div class="permission-matrix-wrap table-responsive">
    <table class="table table-sm table-hover permission-matrix">
        <thead>
            <tr>
                <th>Module</th>
                @foreach($buckets as $bucketLabel)
                    <th>{{ $bucketLabel }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($permissionGroups as $group)
                <tr>
                    <td class="permission-module">
                        <strong>{{ $group['label'] }}</strong>
                        <small>{{ $group['key'] }}</small>
                    </td>
                    @foreach($buckets as $bucket => $bucketLabel)
                        @php
                            $items = $group['permissions']->where('bucket', $bucket)->values();
                        @endphp
                        <td>
                            <div class="permission-cell">
                                @forelse($items as $permission)
                                    <label class="permission-check" title="{{ $permission->name }}">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array((int) $permission->id, $selectedPermissionIds, true))>
                                        <span>{{ $permission->action === $bucket ? $bucketLabel : $permission->action }}</span>
                                    </label>
                                @empty
                                    <span class="text-muted">-</span>
                                @endforelse
                            </div>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var root = document.querySelector('.permission-matrix-wrap');
        var checkAll = document.getElementById('checkAllPermissions');
        var clearAll = document.getElementById('clearAllPermissions');
        if (!root) return;
        if (checkAll) {
            checkAll.addEventListener('click', function () {
                root.querySelectorAll('input[type="checkbox"]').forEach(function (input) { input.checked = true; });
            });
        }
        if (clearAll) {
            clearAll.addEventListener('click', function () {
                root.querySelectorAll('input[type="checkbox"]').forEach(function (input) { input.checked = false; });
            });
        }
    });
</script>
