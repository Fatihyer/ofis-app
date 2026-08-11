@extends('layouts.app')

@section('style')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<style>
.doc-card { border-radius: 8px; padding: 10px 14px; text-align: center; min-width: 90px; }
.doc-card .doc-label { font-size: .72em; font-weight: 700; text-transform: uppercase; letter-spacing:.04em; margin-bottom: 2px; }
.doc-card .doc-date  { font-size: .88em; font-weight: 600; }
.doc-ok   { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
.doc-warn { background:#fff3cd; color:#856404; border:1px solid #ffeeba; }
.doc-exp  { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
.doc-none { background:#f8f9fa; color:#6c757d; border:1px solid #dee2e6; }
.vehicle-doc-upload { border:1px solid #dbe3ef; background:#f8fafc; border-radius:8px; padding:12px; }
.vehicle-doc-table td, .vehicle-doc-table th { vertical-align: middle; }
.vehicle-doc-type { font-weight:800; color:#0f766e; }
.vehicle-doc-ext { display:inline-flex; align-items:center; justify-content:center; min-width:44px; border-radius:999px; padding:3px 8px; background:#e0f2fe; color:#075985; font-size:12px; font-weight:900; }
</style>
@endsection

@section('content')
@php
    $today = \Carbon\Carbon::today();
    $warn  = \Carbon\Carbon::today()->addDays(30);

    $docClass = function($val) use ($today, $warn) {
        if (!$val) return 'doc-none';
        $d = \Carbon\Carbon::parse($val);
        if ($d->lt($today))    return 'doc-exp';
        if ($d->lt($warn))     return 'doc-warn';
        return 'doc-ok';
    };
    $docDate = function($val) {
        if (!$val) return '—';
        return \Carbon\Carbon::parse($val)->format('d/m/Y');
    };

    $docs = [
        'C.T.'       => $vehicule->control,
        'Assurance'  => $vehicule->sigorta,
        'E.A.D.'     => $vehicule->ead_date,
        'Ext.'       => $vehicule->ext_date,
        'Lim.'       => $vehicule->lim_date,
        'Tach.'      => $vehicule->tach_date,
        'Vid.'       => $vehicule->vid_date,
    ];
    $activeTab = request('tab') === 'documents' ? 'documents' : 'missions';
@endphp

<div class="container-fluid">

    {{-- ── Header ── --}}
    <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
        <div class="me-2">
            <select class="form-select form-select-sm" id="vehiculeSwitch" style="min-width:180px;">
                @foreach($vehicules as $v)
                    <option value="{{ $v->id }}" @selected($vehicule->id == $v->id)>{{ $v->name }}</option>
                @endforeach
            </select>
        </div>
        <h3 class="mb-0 me-auto">
            {{ $vehicule->name }}
            @if($vehicule->plaka) <small class="text-muted fw-normal">· {{ $vehicule->plaka }}</small> @endif
            @if($vehicule->enpanne) <span class="badge bg-danger ms-2">En panne</span> @endif
        </h3>
        <button class="btn btn-primary btn-sm"
                data-bs-toggle="modal" data-bs-target="#editModal">
            ✏️ Modifier
        </button>
        <a href="{{ route('vehicules.show', ['vehicule' => $vehicule->id, 'tab' => 'documents']) }}" class="btn btn-outline-primary btn-sm">
            Documents {{ count($documents) }}
        </a>
        <a href="{{ route('vehicules.index') }}" class="btn btn-outline-secondary btn-sm">← Liste</a>
        <a href="{{ route('vehicules.controle-docs') }}" class="btn btn-outline-info btn-sm">📋 Contrôle docs</a>
    </div>

    {{-- ── Info + Documents ── --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body py-3">
                    <table class="table table-sm mb-0">
                        <tr><th class="text-muted fw-normal ps-0">Modèle</th><td>{{ $vehicule->yil ?: '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0">Capacité</th><td>{{ $vehicule->capacity ?: '—' }} pax</td></tr>
                        <tr><th class="text-muted fw-normal ps-0">Licences</th><td>{{ $vehicule->licence_count ?? '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0">Hermes ID</th><td>{{ $vehicule->hermes_uid ?: '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0">Réel</th><td>{{ $vehicule->real ? 'Oui' : 'Non' }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0">Sales</th><td>{{ $vehicule->sales ? 'Oui' : 'Non' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card h-100">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($docs as $label => $val)
                        <div class="doc-card {{ $docClass($val) }}">
                            <div class="doc-label">{{ $label }}</div>
                            <div class="doc-date">{{ $docDate($val) }}</div>
                        </div>
                        @endforeach
                        <div class="doc-card doc-none ms-3">
                            <div class="doc-label">Lic.</div>
                            <div class="doc-date">{{ $vehicule->licence_count ?? '—' }}</div>
                        </div>
                    </div>
                    @if($vehicule->remarques)
                    <div class="mt-2 text-muted small"><strong>Rmq :</strong> {{ $vehicule->remarques }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tabs ── --}}
    <ul class="nav nav-tabs" id="vehiculeTab">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'missions' ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-missions">Missions</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tab-maintenance">Maintenance</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tab-km">Kilométrage</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'documents' ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-documents">Documents</a>
        </li>
    </ul>

    <div class="tab-content border border-top-0 p-3 mb-4">

        {{-- Missions --}}
        <div class="tab-pane fade {{ $activeTab === 'missions' ? 'show active' : '' }}" id="tab-missions">
            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-auto">
                    <label class="form-label mb-1">Du</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                </div>
                <div class="col-auto">
                    <label class="form-label mb-1">Au</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                </div>
                <div class="col-auto"><button class="btn btn-sm btn-primary">Filtrer</button></div>
                @if(request('start_date') || request('end_date'))
                <div class="col-auto"><a href="{{ route('vehicules.show', $vehicule->id) }}" class="btn btn-sm btn-outline-secondary">Reset</a></div>
                @endif
            </form>
            <table class="table table-sm table-striped table-bordered" id="missionsTable">
                <thead>
                    <tr>
                        <th>Dosya</th>
                        <th>Transfer</th>
                        <th>Départ</th>
                        <th>Fin</th>
                        <th>Chauffeur</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfers as $transfer)
                    <tr>
                        <td><a href="{{ route('posts.show', $transfer->post->id) }}">{{ $transfer->post->id }}</a></td>
                        <td><a href="{{ route('transfers.show', $transfer->id) }}">{{ $transfer->id }}</a></td>
                        <td>{{ \Carbon\Carbon::parse($transfer->start_date)->format('d/m/Y H:i') }}</td>
                        <td>{{ \Carbon\Carbon::parse($transfer->end_date)->format('d/m/Y H:i') }}</td>
                        <td>{{ $transfer->driver->name ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Maintenance --}}
        <div class="tab-pane fade" id="tab-maintenance">
            @if($vehicule->maintenances->count())
            <table class="table table-sm table-bordered table-striped" id="maintenanceTable">
                <thead>
                    <tr><th>Date</th><th>Montant (€)</th><th>Description</th></tr>
                </thead>
                <tbody>
                    @foreach($vehicule->maintenances as $m)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($m->service_date)->format('d/m/Y') }}</td>
                        <td>{{ number_format($m->amount, 2, ',', ' ') }} €</td>
                        <td>{!! nl2br(e($m->description)) !!}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p class="text-muted mt-2">Aucun enregistrement de maintenance.</p>
            @endif
        </div>

        {{-- Kilométrage --}}
        <div class="tab-pane fade" id="tab-km">
            @if($vehicule->kilometers->count())
            <table class="table table-sm table-bordered table-striped">
                <thead><tr><th>Date</th><th>Kilométrage</th><th></th></tr></thead>
                <tbody>
                    @foreach($vehicule->kilometers as $km)
                    <tr>
                        <td>{{ $km->created_at->format('d/m/Y') }}</td>
                        <td>{{ number_format($km->kilometer, 0, ',', ' ') }} km</td>
                        <td>
                            <form action="{{ route('kilometers.destroy', $km->id) }}" method="POST" class="d-inline km-delete-form">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p class="text-muted mt-2">Aucun relevé kilométrique.</p>
            @endif
        </div>

        {{-- Documents --}}
        <div class="tab-pane fade {{ $activeTab === 'documents' ? 'show active' : '' }}" id="tab-documents">
            <div class="vehicle-doc-upload mb-3">
                <form action="{{ route('vehicules.documents.store', $vehicule->id) }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label mb-1">Type document</label>
                        <input type="text" name="document_type" list="vehicleDocumentTypes" class="form-control form-control-sm" value="Carte grise" required>
                        <datalist id="vehicleDocumentTypes">
                            <option value="Carte grise">
                            <option value="Assurance">
                            <option value="Contrôle technique">
                            <option value="Licence transport">
                            <option value="E.A.D.">
                            <option value="Extincteur">
                            <option value="Limiteur">
                            <option value="Tachygraphe">
                            <option value="Vidange / visite">
                            <option value="Autre">
                        </datalist>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label mb-1">Fichier</label>
                        <input type="file" name="document_file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.webp,.heic,.doc,.docx,.xls,.xlsx,.csv,.txt,.xml,.zip" required>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm btn-primary w-100" type="submit">Ajouter</button>
                    </div>
                    <div class="col-12">
                        <small class="text-muted">PDF, image, Word, Excel, CSV, TXT, XML ou ZIP. Taille max 20 MB.</small>
                    </div>
                </form>
            </div>

            @if(count($documents))
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped vehicle-doc-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Fichier</th>
                                <th>Format</th>
                                <th>Taille</th>
                                <th>Ajouté</th>
                                <th style="width:150px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documents as $document)
                                <tr>
                                    <td class="vehicle-doc-type">{{ $document['type'] }}</td>
                                    <td>
                                        <a href="{{ $document['url'] }}" target="_blank">{{ $document['name'] }}</a>
                                    </td>
                                    <td><span class="vehicle-doc-ext">{{ $document['extension'] }}</span></td>
                                    <td>{{ number_format($document['size_kb'], 1, ',', ' ') }} KB</td>
                                    <td>{{ $document['uploaded_at']->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <a href="{{ $document['url'] }}" target="_blank" class="btn btn-sm btn-outline-primary">Ouvrir</a>
                                        <form action="{{ route('vehicules.documents.destroy', [$vehicule->id, $document['filename']]) }}" method="POST" class="d-inline vehicle-doc-delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Suppr.</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mt-2">Aucun document véhicule enregistré.</p>
            @endif
        </div>

    </div>
</div>

{{-- ── Edit Modal ── --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier — {{ $vehicule->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            {{ Form::open(['route' => 'vehicule.guncel']) }}
            {{ Form::hidden('id', $vehicule->id) }}
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-12">
                        <label>Nom</label>
                        {{ Form::text('name', $vehicule->name, ['class' => 'form-control']) }}
                    </div>
                    <div class="col-6">
                        <label>Immatriculation</label>
                        {{ Form::text('plaka', $vehicule->plaka, ['class' => 'form-control']) }}
                    </div>
                    <div class="col-6">
                        <label>Modèle</label>
                        {{ Form::text('yil', $vehicule->yil, ['class' => 'form-control']) }}
                    </div>
                    <div class="col-6">
                        <label>Capacité</label>
                        {{ Form::selectRange('capacity', 1, 60, $vehicule->capacity, ['class' => 'form-control']) }}
                    </div>
                    <div class="col-6">
                        <label>Licences (nb)</label>
                        {{ Form::number('licence_count', $vehicule->licence_count, ['class' => 'form-control', 'min' => 0]) }}
                    </div>
                    <div class="col-6">
                        <label>C.T.</label>
                        {{ Form::date('control', $vehicule->control ? \Carbon\Carbon::parse($vehicule->control)->format('Y-m-d') : '', ['class' => 'form-control']) }}
                    </div>
                    <div class="col-6">
                        <label>Assurance</label>
                        {{ Form::date('sigorta', $vehicule->sigorta ? \Carbon\Carbon::parse($vehicule->sigorta)->format('Y-m-d') : '', ['class' => 'form-control']) }}
                    </div>
                    <div class="col-6">
                        <label>E.A.D.</label>
                        {{ Form::date('ead_date', $vehicule->ead_date ? \Carbon\Carbon::parse($vehicule->ead_date)->format('Y-m-d') : '', ['class' => 'form-control']) }}
                    </div>
                    <div class="col-6">
                        <label>Extincteur</label>
                        {{ Form::date('ext_date', $vehicule->ext_date ? \Carbon\Carbon::parse($vehicule->ext_date)->format('Y-m-d') : '', ['class' => 'form-control']) }}
                    </div>
                    <div class="col-6">
                        <label>Limiteur</label>
                        {{ Form::date('lim_date', $vehicule->lim_date ? \Carbon\Carbon::parse($vehicule->lim_date)->format('Y-m-d') : '', ['class' => 'form-control']) }}
                    </div>
                    <div class="col-6">
                        <label>Tachygraphe</label>
                        {{ Form::date('tach_date', $vehicule->tach_date ? \Carbon\Carbon::parse($vehicule->tach_date)->format('Y-m-d') : '', ['class' => 'form-control']) }}
                    </div>
                    <div class="col-6">
                        <label>Vidange/Visite</label>
                        {{ Form::date('vid_date', $vehicule->vid_date ? \Carbon\Carbon::parse($vehicule->vid_date)->format('Y-m-d') : '', ['class' => 'form-control']) }}
                    </div>
                    <div class="col-6">
                        <label>Hermes ID</label>
                        {{ Form::text('hermes_uid', $vehicule->hermes_uid, ['class' => 'form-control']) }}
                    </div>
                    <div class="col-12">
                        <label>Remarques</label>
                        {{ Form::textarea('remarques', $vehicule->remarques, ['class' => 'form-control', 'rows' => 2]) }}
                    </div>
                    <div class="col-4">
                        <div class="form-check mt-2">
                            {{ Form::checkbox('real', '1', $vehicule->real, ['class' => 'form-check-input', 'id' => 'edit-real']) }}
                            <label class="form-check-label" for="edit-real">Réel</label>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-check mt-2">
                            {{ Form::checkbox('sales', '1', $vehicule->sales, ['class' => 'form-check-input', 'id' => 'edit-sales']) }}
                            <label class="form-check-label" for="edit-sales">Sales</label>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-check mt-2">
                            {{ Form::checkbox('enpanne', '1', $vehicule->enpanne, ['class' => 'form-check-input', 'id' => 'edit-enpanne']) }}
                            <label class="form-check-label" for="edit-enpanne">En panne</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                {{ Form::submit('Enregistrer', ['class' => 'btn btn-primary']) }}
            </div>
            {{ Form::close() }}
        </div>
    </div>
</div>
@endsection

@section('footer')
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script>
document.getElementById('vehiculeSwitch').addEventListener('change', function () {
    window.location.href = '/vehicules/' + this.value;
});

document.querySelectorAll('.km-delete-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (confirm('Supprimer ce relevé kilométrique ?')) form.submit();
    });
});

document.querySelectorAll('.vehicle-doc-delete-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (confirm('Supprimer ce document véhicule ?')) form.submit();
    });
});

$(function () {
    $('#missionsTable').DataTable({
        order: [[2, 'desc']],
        pageLength: 25,
        language: {
            search: "Rechercher:", zeroRecords: "Aucun résultat",
            info: "_START_–_END_ / _TOTAL_",
            paginate: { previous: "Préc.", next: "Suiv." }
        }
    });
    $('#maintenanceTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            search: "Rechercher:", zeroRecords: "Aucun résultat",
            info: "_START_–_END_ / _TOTAL_",
            paginate: { previous: "Préc.", next: "Suiv." }
        }
    });
});
</script>
@endsection
