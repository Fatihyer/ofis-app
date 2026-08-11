@extends('layouts.app')

@section('style')
<style>
.doc-table th { white-space: nowrap; text-align: center; }
.doc-table td { text-align: center; vertical-align: middle; }
.doc-table td.name-col { text-align: left; }

.d-ok   { background: #d4edda; color: #155724; font-weight: 600; border-radius: 4px; padding: 2px 6px; display:inline-block; }
.d-warn { background: #fff3cd; color: #856404; font-weight: 600; border-radius: 4px; padding: 2px 6px; display:inline-block; }
.d-exp  { background: #f8d7da; color: #721c24; font-weight: 600; border-radius: 4px; padding: 2px 6px; display:inline-block; }
.d-none { color: #aaa; }

.legend { display:inline-flex; align-items:center; gap:6px; margin-right:16px; font-size:.85em; }
</style>
@endsection

@section('content')
@php
    $today = \Carbon\Carbon::today();
    $warn  = \Carbon\Carbon::today()->addDays(30);

    $fmt = function($val) use ($today, $warn) {
        if (!$val) return '<span class="d-none">—</span>';
        $d = \Carbon\Carbon::parse($val);
        if ($d->lt($today))    $cls = 'd-exp';
        elseif ($d->lt($warn)) $cls = 'd-warn';
        else                   $cls = 'd-ok';
        return '<span class="'.$cls.'">'.$d->format('d/m/Y').'</span>';
    };
@endphp

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Contrôle documents véhicules</h2>
        <a href="{{ route('vehicules.index') }}" class="btn btn-sm btn-secondary">← Retour</a>
    </div>

    <div class="mb-3 d-flex flex-wrap gap-3">
        <span class="legend"><span class="d-ok">00/00</span> Valide</span>
        <span class="legend"><span class="d-warn">00/00</span> Expire &lt; 30 j</span>
        <span class="legend"><span class="d-exp">00/00</span> Expiré</span>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-sm doc-table" id="controleTable">
            <thead class="table-dark">
                <tr>
                    <th class="name-col">Véhicule</th>
                    <th>Plaque</th>
                    <th>C.T.</th>
                    <th>Assurance</th>
                    <th>E.A.D.</th>
                    <th>Ext.</th>
                    <th>Lim.</th>
                    <th>Tach.</th>
                    <th>Vid.</th>
                    <th>Lic.</th>
                    <th style="min-width:160px;">Rmq</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($vehicules as $v)
                @php
                    $dates = [$v->control, $v->sigorta, $v->ead_date, $v->ext_date, $v->lim_date, $v->tach_date, $v->vid_date];
                    $hasExp  = collect($dates)->filter()->some(fn($d) => \Carbon\Carbon::parse($d)->lt($today));
                    $hasWarn = !$hasExp && collect($dates)->filter()->some(fn($d) => \Carbon\Carbon::parse($d)->lt($warn));
                    $rowClass = $hasExp ? 'table-danger' : ($hasWarn ? 'table-warning' : '');
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="name-col">
                        <a href="{{ route('vehicules.show', $v->id) }}">{{ $v->name }}</a>
                        @if($v->enpanne) <span class="badge bg-danger ms-1">En panne</span> @endif
                    </td>
                    <td>{{ $v->plaka }}</td>
                    <td>{!! $fmt($v->control) !!}</td>
                    <td>{!! $fmt($v->sigorta) !!}</td>
                    <td>{!! $fmt($v->ead_date) !!}</td>
                    <td>{!! $fmt($v->ext_date) !!}</td>
                    <td>{!! $fmt($v->lim_date) !!}</td>
                    <td>{!! $fmt($v->tach_date) !!}</td>
                    <td>{!! $fmt($v->vid_date) !!}</td>
                    <td>{{ $v->licence_count ?? '—' }}</td>
                    <td style="text-align:left;font-size:.85em;white-space:normal;">{{ $v->remarques }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<script>
$(function () {
    $('#controleTable').DataTable({
        pageLength: 50,
        order: [[0, 'asc']],
        language: {
            search: "Rechercher:",
            lengthMenu: "Afficher _MENU_ véhicules",
            info: "_START_–_END_ sur _TOTAL_",
            zeroRecords: "Aucun résultat",
            emptyTable: "Aucune donnée",
            paginate: { previous: "Préc.", next: "Suiv." }
        }
    });
});
</script>
@endsection
