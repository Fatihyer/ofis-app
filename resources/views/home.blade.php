@extends('layouts.app')

@section('style')
<link href="{{ asset('css/daterangepicker.css') }}" rel="stylesheet">
<style>
.operation-page {
    color: #172033;
    max-width: 100%;
    overflow-x: hidden;
}
.operation-hero {
    margin-bottom: 10px;
}
.operation-title h1 {
    font-size: 22px;
    margin: 0;
    font-weight: 750;
}
.operation-title p {
    margin: 4px 0 0;
    color: #64748b;
}
.operation-actions {
    display: flex;
    flex-wrap: nowrap;
    gap: 6px;
    overflow-x: auto;
    padding: 8px;
    margin-bottom: 14px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
}
.operation-actions .btn {
    flex: 0 0 auto;
    white-space: nowrap;
}
.operation-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
}
.operation-card .card-header {
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 700;
}
.stat-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 14px;
}
.stat-tile {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px;
    background: #fff;
    min-height: 82px;
}
.stat-tile small {
    color: #64748b;
    display: block;
    margin-bottom: 6px;
}
.stat-tile strong {
    font-size: 23px;
    line-height: 1;
}
.stat-tile.warning {
    border-color: #fed7aa;
    background: #fff7ed;
}
.stat-tile.danger {
    border-color: #fecaca;
    background: #fef2f2;
}
.operation-filter {
    display: grid;
    grid-template-columns: minmax(220px, 1.2fr) repeat(3, minmax(160px, 1fr)) auto;
    gap: 10px;
    align-items: end;
}
.operation-filter label {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 4px;
    font-weight: 650;
}
.operation-depot-filter {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 0 10px;
    border-bottom: 1px solid #e5e7eb;
}
.operation-depot-title {
    font-size: 12px;
    color: #64748b;
    font-weight: 750;
    min-width: 56px;
}
.operation-depot-options {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.operation-depot-option {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 32px;
    margin: 0;
    padding: 6px 10px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #fff;
    color: #334155;
    font-weight: 700;
    cursor: pointer;
}
.operation-depot-option input {
    margin: 0;
}
.operation-depot-option.active {
    border-color: #2563eb;
    background: #eff6ff;
    color: #1d4ed8;
}
.quick-date-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.alert-strip {
    margin: 14px 0;
}
.alert-strip .alert {
    margin: 0;
    border-radius: 8px;
    max-height: 130px;
    overflow-y: auto;
}
.operation-table-wrap {
    width: 100%;
    max-width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow-x: auto;
    overflow-y: visible;
    background: #fff;
    -webkit-overflow-scrolling: touch;
}
.operation-table {
    margin: 0;
    font-size: 13px;
    white-space: nowrap;
    width: max-content;
    min-width: 100%;
}
.operation-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #f1f5f9;
    color: #334155;
    border-bottom: 1px solid #cbd5e1;
    font-weight: 750;
}
.operation-table td,
.operation-table th {
    vertical-align: middle;
}
.operation-table tbody tr:hover {
    background: #f8fbff;
}
.file-cell {
    width: 118px;
    min-width: 118px;
    max-width: 118px;
    white-space: normal;
}
.file-cell a {
    font-weight: 700;
    text-decoration: none;
}
.file-cell .dossier-main {
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}
.file-cell .muted-line {
    max-width: 106px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.muted-line {
    color: #64748b;
    font-size: 12px;
}
.route-cell {
    width: 300px;
    min-width: 260px;
    max-width: 320px;
    white-space: normal;
}
.route-main {
    font-weight: 650;
}
.trajet-list {
    margin: 8px 0 0;
    padding: 0;
    list-style: none;
}
.trajet-list li {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 5px 7px;
    margin-top: 5px;
    background: #fff;
    overflow-wrap: anywhere;
}
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border-radius: 999px;
    padding: 3px 8px;
    font-size: 12px;
    font-weight: 700;
}
.status-waiting { background: #f1f5f9; color: #475569; }
.status-called { background: #fef3c7; color: #92400e; }
.status-sms { background: #dbeafe; color: #1d4ed8; }
.status-confirmed { background: #dcfce7; color: #166534; }
.floating-scroll-btn {
    position: fixed;
    z-index: 1050;
    width: 46px;
    height: 46px;
    right: 24px;
    border-radius: 50%;
}
#scrollTopBtn { bottom: 82px; display: none; }
#scrollBottomBtn { bottom: 28px; }
.toast-container {
    position: fixed;
    top: 70px;
    right: 20px;
    z-index: 1040;
    max-width: 360px;
}
.toast {
    display: block;
    padding: 10px 15px;
    margin-bottom: 10px;
    border-radius: 8px;
    color: #fff;
    opacity: 1;
    transition: opacity .5s ease-out;
}
.toast-warning { background-color: #f59e0b; }

.operation-table .col-time { width: 130px; min-width: 130px; max-width: 150px; white-space: normal; }
.operation-table .col-mission { width: 100px; min-width: 100px; max-width: 120px; white-space: normal; }
.operation-table .col-agence { width: 130px; min-width: 130px; max-width: 150px; white-space: normal; }
.operation-table .col-driver { width: 150px; min-width: 150px; max-width: 165px; white-space: normal; }
.operation-table .col-actions { width: 140px; min-width: 140px; max-width: 160px; white-space: normal; }
.operation-table td:not(.route-cell):not(.file-cell):not(.col-driver):not(.col-actions) { max-width: 180px; }
.operation-table a, .operation-table span, .operation-table div { overflow-wrap: anywhere; }


.operation-table tbody tr.operation-status-row > td {
    background-color: var(--operation-status-bg, transparent);
}
.operation-table tbody tr.operation-status-row:hover > td {
    filter: brightness(.98);
}
.operation-table tbody tr.operation-status-row > td:first-child {
    border-left: 5px solid var(--operation-status-border, #cbd5e1);
}
.operation-table tbody tr.operation-status-success { --operation-status-bg: #eaf7ef; --operation-status-border: #198754; }
.operation-table tbody tr.operation-status-warning { --operation-status-bg: #fff7e6; --operation-status-border: #ffc107; }
.operation-table tbody tr.operation-status-danger { --operation-status-bg: #fdecec; --operation-status-border: #dc3545; }
.operation-table tbody tr.operation-status-info { --operation-status-bg: #e8f4fb; --operation-status-border: #0dcaf0; }
.operation-table tbody tr.operation-status-primary { --operation-status-bg: #edf4ff; --operation-status-border: #0d6efd; }
.operation-table tbody tr.operation-status-secondary { --operation-status-bg: #f3f4f6; --operation-status-border: #6c757d; }
.operation-table tbody tr.operation-status-dark { --operation-status-bg: #eceff3; --operation-status-border: #212529; }
.operation-table tbody tr.operation-status-light { --operation-status-bg: #fafafa; --operation-status-border: #d1d5db; }
.operation-table tbody tr.operation-driver-confirmed {
    --operation-status-bg: #dcfce7;
    --operation-status-border: #16a34a;
}


.assignment-cell {
    min-width: 185px;
    max-width: 230px;
    font-family: inherit;
    font-size: 12px;
    line-height: 1.25;
    white-space: normal;
}
.assignment-card {
    display: grid;
    gap: 5px;
    width: 100%;
    min-width: 0;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    font: inherit;
}
.assignment-row {
    display: grid;
    grid-template-columns: 5px minmax(0, 1fr);
    align-items: start;
    column-gap: 7px;
    min-width: 0;
}
.assignment-icon {
    width: 5px;
    height: 22px;
    margin-top: 1px;
    display: block;
    border-radius: 999px;
    background: var(--assignment-color, #64748b);
    color: transparent;
    overflow: hidden;
}
.assignment-icon i { display: none; }
.assignment-row.vehicle-row .assignment-icon { background: #64748b; }
.assignment-row.second-driver-row .assignment-icon { background: #111827; }
.assignment-details {
    min-width: 0;
}
.assignment-text {
    display: block;
    min-width: 0;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font: inherit;
    font-weight: 700;
}
.assignment-driver-link {
    color: var(--assignment-color, #1f2937) !important;
}
.assignment-subtext {
    color: #475569;
    font-size: 11px;
    line-height: 1.25;
    overflow: visible;
    text-overflow: clip;
    white-space: normal;
    word-break: keep-all;
}
.assignment-plate {
    color: #111827;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .02em;
}
.assignment-map-row {
    padding-left: 12px;
    min-width: 0;
}
.assignment-map-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    max-width: 100%;
    height: 24px;
    padding: 2px 7px;
    border-radius: 999px;
    font: inherit;
    font-size: 11px;
    line-height: 1;
}
.assignment-cell .badge {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}


.service-cell {
    width: 120px;
    min-width: 120px;
    max-width: 130px;
    white-space: normal;
}
.service-name {
    display: block;
    max-width: 112px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-weight: 750;
}
.service-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 4px;
}

.my-dossiers-list {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}
.my-dossier-card {
    display: block;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 10px;
    color: inherit;
    text-decoration: none;
    background: #fff;
    min-height: 118px;
}
.my-dossier-card:hover { text-decoration: none; background: #f8fbff; border-color: #bfdbfe; }
.my-dossier-top { display: flex; justify-content: space-between; gap: 8px; align-items: flex-start; }
.my-dossier-id { font-weight: 850; color: #0f172a; }
.my-dossier-title { margin-top: 6px; font-weight: 750; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.my-dossier-meta { color: #64748b; font-size: 12px; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.my-dossier-badges { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 8px; }
.my-dossier-badges .badge { font-size: 11px; }
@media (max-width: 1199.98px) { .my-dossiers-list { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 767.98px) { .my-dossiers-list { grid-template-columns: 1fr; } }
@media (max-width: 1199.98px) {
    .stat-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .operation-filter { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 767.98px) {
    .stat-grid,
    .operation-filter { grid-template-columns: 1fr; }
    .operation-depot-filter { align-items: flex-start; flex-direction: column; }
    .operation-title h1 { font-size: 19px; }
}
</style>
@endsection

@section('content')
@php
    $stats = $operationStats ?? [];
    $totalTransfers = $stats['total'] ?? $transfers->total();
    $withoutDriverCount = $stats['without_driver'] ?? $nodriver->count();
    $withoutVehicleCount = $stats['without_vehicle'] ?? 0;
    $missionCount = $stats['missions'] ?? 0;
    $totalPax = $stats['pax'] ?? 0;
    $surplaceMinutes = (int) (optional(\App\Models\Option::where('name', 'surplaceMinBefore')->first())->value ?? 15);
@endphp

<div class="operation-page">
    <div class="toast-container">
        @foreach ($toastMessages as $message)
            <div class="toast toast-warning">{{ $message }}</div>
        @endforeach
        @foreach ($arabalar as $araba)
            @if (session('toast-danger-' . $araba->id))
                <div class="alert alert-danger mb-2">{{ session('toast-danger-' . $araba->id) }}</div>
            @endif
        @endforeach
    </div>

    <div class="operation-hero">
        <div class="operation-title">
            <h1>Tableau des opérations</h1>
            <p>{{ $daterangeDisplay ?? '' }} · {{ $totalTransfers }} transfert(s) trouvé(s)</p>
        </div>
    </div>

    <div class="operation-actions" aria-label="Actions rapides">
        <a class="btn btn-outline-dark btn-sm" href="{{ route('posts.index') }}">Dossiers</a>
        <a class="btn btn-outline-dark btn-sm" href="{{ route('acentes.index') }}">Prestataires</a>
        <a class="btn btn-warning btn-sm" href="{{ route('posts.create') }}">Nouveau dossier</a>
        <a class="btn btn-secondary btn-sm" href="{{ route('posts.createfromtransfert') }}">Depuis transfert</a>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('listegunluk') }}">CA transferts</a>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('heuredetravail') }}">Temps de travail</a>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('vehiculescontrol') }}">Véhicules</a>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('driverUsage.index') }}">Chauffeurs</a>
        @role('Superadmin')
            <a class="btn btn-dark btn-sm" href="{{ route('ev.timeline', request()->except('page')) }}"><i class="fas fa-stream"></i> Vue horaire</a>
        @endrole
    </div>

    <div class="stat-grid">
        <div class="stat-tile">
            <small>Transferts</small>
            <strong>{{ $totalTransfers }}</strong>
            <div class="muted-line">sur la période</div>
        </div>
        <div class="stat-tile">
            <small>Passagers</small>
            <strong>{{ $totalPax }}</strong>
            <div class="muted-line">total pax</div>
        </div>
        <div class="stat-tile {{ $withoutDriverCount ? 'danger' : '' }}">
            <small>Sans chauffeur</small>
            <strong>{{ $withoutDriverCount }}</strong>
            <div class="muted-line">à traiter</div>
        </div>
        <div class="stat-tile {{ $withoutVehicleCount ? 'warning' : '' }}">
            <small>Sans véhicule</small>
            <strong>{{ $withoutVehicleCount }}</strong>
            <div class="muted-line">à assigner</div>
        </div>
        <div class="stat-tile">
            <small>Missions</small>
            <strong>{{ $missionCount }}</strong>
            <div class="muted-line">avec mission</div>
        </div>
        <div class="stat-tile">
            <small>Période</small>
            <strong>{{ $transfers->count() }}</strong>
            <div class="muted-line">affiché(s) sur cette page</div>
        </div>
    </div>

    <div class="operation-card card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Mes dossiers</span>
            <span class="badge bg-light text-dark border">Aujourd'hui et à venir · {{ $myDossiers->count() }}</span>
        </div>
        <div class="card-body">
            @if($myDossiers->isEmpty())
                <div class="text-muted">Aucun dossier à venir ouvert par vous.</div>
            @else
                <div class="my-dossiers-list">
                    @foreach($myDossiers as $dossier)
                        @php
                            $billingStatus = $dossier->billing_status ?: 'to_invoice';
                            $billingMeta = [
                                'to_invoice' => ['À facturer', 'warning'],
                                'invoiced' => ['Facturé', 'success'],
                                'do_not_invoice' => ['Ne pas facturer', 'secondary'],
                            ][$billingStatus] ?? [$billingStatus, 'secondary'];
                            $paymentStatus = $dossier->payment_status ?: 'not_received';
                            $paymentMeta = [
                                'not_received' => ['Non encaissé', 'danger'],
                                'partial' => ['Partiel', 'warning'],
                                'received' => ['Encaissé', 'success'],
                            ][$paymentStatus] ?? [$paymentStatus, 'secondary'];
                            $serviceCount = (int)$dossier->transfer_count + (int)$dossier->hotels_count + (int)$dossier->others_count + (int)$dossier->stock_count;
                        @endphp
                        <a class="my-dossier-card" href="{{ route('posts.show', $dossier->id) }}">
                            <div class="my-dossier-top">
                                <span class="my-dossier-id">#{{ $dossier->id }}</span>
                                <span class="badge bg-{{ optional(optional($dossier->status)->color)->name ?? 'secondary' }}">{{ optional($dossier->status)->name ?? 'Statut' }}</span>
                            </div>
                            <div class="my-dossier-title">{{ $dossier->title ?: 'Sans titre' }}</div>
                            <div class="my-dossier-meta">{{ optional($dossier->acente)->name ?: 'Agence non définie' }}</div>
                            <div class="my-dossier-meta">
                                {{ $dossier->start_date ? \Carbon\Carbon::parse($dossier->start_date)->format('d/m/Y') : '-' }}
                                @if($dossier->end_date && $dossier->end_date !== $dossier->start_date)
                                    → {{ \Carbon\Carbon::parse($dossier->end_date)->format('d/m/Y') }}
                                @endif
                            </div>
                            <div class="my-dossier-badges">
                                <span class="badge bg-light text-dark border">{{ $serviceCount }} service(s)</span>
                                <span class="badge bg-{{ $billingMeta[1] }}">{{ $billingMeta[0] }}</span>
                                <span class="badge bg-{{ $paymentMeta[1] }}">{{ $paymentMeta[0] }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    <div class="operation-card card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Filtres</span>
            <div class="quick-date-buttons">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('ev', ['dateOption' => 'today']) }}">Réinitialiser</a>
            </div>
        </div>
        <div class="card-body">
            <form method="get" name="tarih" class="operation-filter">
                @if(($depotOptions ?? collect())->isNotEmpty())
                    <div class="operation-depot-filter">
                        <div class="operation-depot-title">Dépôt</div>
                        <div class="operation-depot-options" role="radiogroup" aria-label="Dépôt">
                            <label class="operation-depot-option {{ $selectedDepotId === null ? 'active' : '' }}">
                                <input type="radio" name="depot_id" value="" onchange="this.form.submit()" {{ $selectedDepotId === null ? 'checked' : '' }}>
                                <span>Tous</span>
                            </label>
                            @foreach($depotOptions as $depot)
                                <label class="operation-depot-option {{ (int) $selectedDepotId === (int) $depot->id ? 'active' : '' }}">
                                    <input type="radio" name="depot_id" value="{{ $depot->id }}" onchange="this.form.submit()" {{ (int) $selectedDepotId === (int) $depot->id ? 'checked' : '' }}>
                                    <span>{{ $depot->planning_label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div>
                    <label>Période</label>
                    <input type="text"
                        name="daterange"
                        class="form-control"
                        value="{{ $daterangeDisplay ?? (app('request')->input('daterange') ?? '') }}"
                        autocomplete="off" />
                    <input type="hidden" name="start_date" id="hiddenStartDate" value="{{ $startStr ?? app('request')->input('start_date') }}">
                    <input type="hidden" name="end_date" id="hiddenEndDate" value="{{ $endStr ?? app('request')->input('end_date') }}">
                </div>

                <div>
                    <label>Raccourcis</label>
                    <div class="quick-date-buttons">
                        <button type="submit" name="dateOption" value="yesterday" class="btn btn-outline-primary {{ app('request')->input('dateOption') === 'yesterday' ? 'active' : '' }}">Hier</button>
                        <button type="submit" name="dateOption" value="today" class="btn btn-outline-primary {{ app('request')->input('dateOption') === 'today' ? 'active' : '' }}">Aujourd’hui</button>
                        <button type="submit" name="dateOption" value="tomorrow" class="btn btn-outline-primary {{ app('request')->input('dateOption') === 'tomorrow' ? 'active' : '' }}">Demain</button>
                    </div>
                </div>

                <div>
                    <label>Chauffeur</label>
                    {{ Form::select('driver', ['' => 'Sélectionner', 'All' => 'Tous'] + $driver, request('driver'), ['class' => 'form-control', 'onchange' => 'this.form.submit()']) }}
                </div>
                <div>
                    <label>Véhicule</label>
                    {{ Form::select('vehicule', ['' => 'Sélectionner', 'All' => 'Tous'] + $vehicules, request('vehicule'), ['class' => 'form-control', 'onchange' => 'this.form.submit()']) }}
                </div>
                <div>
                    <label>Agence</label>
                    {{ Form::select('acente', ['' => 'Sélectionner', 'All' => 'Toutes'] + $acentes, request('acente'), ['class' => 'form-control', 'onchange' => 'this.form.submit()']) }}
                </div>
            </form>
        </div>
    </div>

    <div class="alert-strip">
        <div class="alert {{ $nodriver->count() ? 'alert-danger' : 'alert-light' }} d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <strong>Transferts sans chauffeur</strong>
                <div class="mt-1">
                    @forelse ($nodriver as $driverRow)
                        <a class="badge bg-danger text-decoration-none" href="{{ route('posts.show', $driverRow->post_id) }}">#{{ $driverRow->post_id }}</a>
                    @empty
                        <span class="text-muted">Aucun transfert sans chauffeur.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="operation-card card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Planning des transferts</span>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark border">Page {{ $transfers->currentPage() }} / {{ $transfers->lastPage() }}</span>
                <button class="btn btn-sm btn-outline-secondary" type="button" onclick="window.print()">Imprimer</button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="operation-table-wrap">
                <table class="table table-sm table-hover operation-table">
                    <thead>
                        <tr>
                            <th>Dossier</th>
                            <th class="col-time">Horaires</th>
                            <th class="col-mission">Mission</th>
                            <th class="col-agence">>Agence</th>
                            <th class="service-cell">Service</th>
                            <th class="col-driver">Chauffeur / Véhicule</th>
                            <th>Trajet</th>
                            <th>Pax</th>
                            <th>Statut</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $key => $transfer)
                            @php
                                $googleAddresses = $transfer->trajets->pluck('google_address')->filter();
                                $origin = $googleAddresses->first();
                                $destination = $googleAddresses->last();
                                $waypoints = $googleAddresses->slice(1, max($googleAddresses->count() - 2, 0))->implode('|');
                                $callLabel = match ($transfer->call_status) {
                                    'confirmed' => 'Confirmé',
                                    'called' => 'Appelé',
                                    'sms_sent' => 'SMS envoyé',
                                    default => 'En attente',
                                };
                                $callClass = match ($transfer->call_status) {
                                    'confirmed' => 'status-confirmed',
                                    'called' => 'status-called',
                                    'sms_sent' => 'status-sms',
                                    default => 'status-waiting',
                                };
                                $comment = (string) $transfer->comments;
                                $isLong = strlen($comment) > 120;
                                $shortComment = $isLong ? substr($comment, 0, 120) . '...' : $comment;
                                $clientNames = $transfer->post && $transfer->post->client
                                    ? $transfer->post->client->map(fn ($client) => trim(($client->title ? $client->title . ' ' : '') . $client->name . ' ' . $client->surname))->filter()->implode(', ')
                                    : '';
                                $statusColor = $transfer->status->color->name ?? 'light';
                                $driverConfirmed = !empty($transfer->driver_app_confirmed_at) || !empty($transfer->driver_confirmed_at);
                                $secondDriverAssigned = !empty($transfer->second_driver_id) && (int) $transfer->second_driver_id !== (int) $transfer->driver_id;
                                $secondDriverConfirmed = !$secondDriverAssigned || !empty($transfer->second_driver_app_confirmed_at);
                                $driverConfirmationClass = ($driverConfirmed && $secondDriverConfirmed) ? ' operation-driver-confirmed' : '';
                                $clientStartAt = \Carbon\Carbon::parse($transfer->start_date);
                                $clientMeetingAt = $clientStartAt->copy()->subMinutes($surplaceMinutes);
                            @endphp
                            <tr class="operation-status-row operation-status-{{ $statusColor }}{{ $driverConfirmationClass }}">
                                <td class="file-cell">
                                    <div class="dossier-main">
                                        <a href="{{ route('transfers.show', $transfer->id) }}" title="Voir le transfert"><i class="fa fa-eye"></i></a>
                                        <a href="{{ route('posts.show', $transfer->post->id) }}">#{{ $transfer->post->id }}</a>
                                        @if ($transfer->mission)
                                            <a href="{{ route('mission', $transfer->id) }}" title="Mission"><i class="fa fa-binoculars"></i></a>
                                        @endif
                                    </div>
                                    @if ($transfer->post->user)
                                        <div class="muted-line" title="{{ $transfer->post->user->name }}">{{ $transfer->post->user->name }}</div>
                                    @endif
                                    @if ($clientNames)
                                        <div class="muted-line" title="{{ $clientNames }}">{{ $clientNames }}</div>
                                    @endif
                                </td>
                                <td class="col-time">
                                    <div class="muted-line">
                                        En route:
                                        @if ($transfer->ofis_start)
                                            {{ date('d/m H:i', strtotime($transfer->ofis_start)) }}
                                        @else
                                            <span class="text-danger">Non défini</span>
                                        @endif
                                    </div>
                                    <div><strong>Sur place:</strong> {{ $clientMeetingAt->format($clientMeetingAt->isToday() ? 'H:i' : 'd/m/Y H:i') }}</div>
                                    <div><strong>Client:</strong> {{ $clientStartAt->format($clientStartAt->isToday() ? 'H:i' : 'd/m/Y H:i') }}</div>
                                    <div><strong>Fin:</strong> {{ date('H:i', strtotime($transfer->end_date)) }}</div>
                                    
                                </td>
                                <td>
                                    @if ($transfer->missionr)
                                        @if($transfer->missionr->finish)
                                            <span class="badge bg-danger">Fin réelle {{ date('H:i', strtotime($transfer->missionr->finish)) }}</span>
                                        @else
                                            <span class="badge bg-success">En mission</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td style="background-color:{{ isset($transfer->post->acente->color) ? $transfer->post->acente->color : '#334155' }}">
                                    <a class="text-white fw-bold" href="{{ route('acentes.show', $transfer->post->acente->id) }}?src=file">{{ $transfer->post->acente->name }}</a>
                                    @if ($transfer->post->acente->responsable_id)
                                        <div class="text-white-50">{{ $transfer->post->acente->responsable->name }}</div>
                                    @endif
                                </td>
                                <td class="service-cell">
                                    <span class="service-name" title="{{ $transfer->servicetype->name }}">{{ $transfer->servicetype->name }}</span>
                                    <div class="service-badges">
                                        @if ($transfer->accueil)
                                            <span class="badge bg-warning text-dark">Accueil</span>
                                        @endif
                                    </div>
                                </td>
                                @php
                                    $emptyAssignmentNames = ['-', '--', '---', '----'];
                                    $driverName = trim(optional($transfer->driver)->name ?? '');
                                    $secondDriverName = trim(optional($transfer->secondDriver)->name ?? '');
                                    $vehiculeName = trim(optional($transfer->vehicule)->name ?? '');
                                    $hasRealDriver = $transfer->driver && !in_array($driverName, $emptyAssignmentNames, true);
                                    $hasSecondDriver = $transfer->secondDriver
                                        && !in_array($secondDriverName, $emptyAssignmentNames, true)
                                        && (int) $transfer->second_driver_id !== (int) $transfer->driver_id;
                                    $requiresRealVehicule = (int) optional($transfer->servicetype)->firma_id === 3;
                                    $hasRealVehicule = $transfer->vehicule && $transfer->vehicule->real && !in_array($vehiculeName, $emptyAssignmentNames, true);
                                    $hasRequestedVehiculeType = $transfer->vehicule && !$hasRealVehicule && !in_array($vehiculeName, $emptyAssignmentNames, true);
                                    $driverColor = $hasRealDriver && !empty($transfer->driver->color) ? $transfer->driver->color : '#64748b';
                                @endphp
                                <td class="col-driver assignment-cell" style="--assignment-color: {{ $driverColor }};">
                                    <div class="assignment-card">
                                        <div class="assignment-row driver-row">
                                            <span class="assignment-icon"><i class="fas fa-user-tie"></i></span>
                                            @if ($hasRealDriver)
                                                <a href="{{ route('acentes.show', $transfer->driver->id) }}?src=service" class="assignment-text assignment-driver-link" title="{{ $transfer->driver->name }}">{{ $transfer->driver->name }}</a>
                                            @else
                                                <span class="badge bg-danger">Sans chauffeur</span>
                                            @endif
                                        </div>
                                        @if($hasSecondDriver)
                                            <div class="assignment-row second-driver-row">
                                                <span class="assignment-icon"><i class="fas fa-user-friends"></i></span>
                                                <div class="assignment-details">
                                                    <span class="badge bg-dark">Double équipage</span>
                                                    <a href="{{ route('acentes.show', $transfer->secondDriver->id) }}?src=service" class="assignment-text d-block" title="{{ $transfer->secondDriver->name }}">{{ $transfer->secondDriver->name }}</a>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="assignment-row vehicle-row">
                                            <span class="assignment-icon"><i class="fas fa-car-side"></i></span>
                                            @if ($hasRealVehicule)
                                                <div class="assignment-details">
                                                    <a href="{{ route('vehicules.show', $transfer->vehicule_id) }}" class="assignment-text d-block" title="{{ $transfer->vehicule->name }}">{{ $transfer->vehicule->name }}</a>
                                                    @if(optional($transfer->vehicule)->plaka)
                                                        <div class="assignment-subtext assignment-plate">{{ $transfer->vehicule->plaka }}</div>
                                                    @endif
                                                    @if($transfer->vehicle_locked)
                                                        <div class="mt-1"><span class="badge bg-warning text-dark">Véhicule bloqué</span></div>
                                                    @endif
                                                </div>
                                            @elseif($hasRequestedVehiculeType)
                                                <div class="assignment-details">
                                                    <span class="badge bg-info text-dark" title="Type de véhicule demandé">{{ $transfer->vehicule->name }}</span>
                                                    @if(optional($transfer->vehicule)->capacity)
                                                        <div class="assignment-subtext">{{ $transfer->vehicule->capacity }} places</div>
                                                    @endif
                                                    @if($requiresRealVehicule)
                                                        <div class="assignment-subtext text-warning fw-bold">Véhicule réel à assigner</div>
                                                    @endif
                                                </div>
                                            @elseif($requiresRealVehicule)
                                                <span class="badge bg-warning text-dark">Sans véhicule</span>
                                            @else
                                                <span class="badge bg-light text-muted border">Véhicule non requis</span>
                                            @endif
                                        </div>
                                        @if($hasRealVehicule && $transfer->vehicule->hermes_uid)
                                            <div class="assignment-map-row">
                                                <button class="btn btn-sm btn-outline-info show-map assignment-map-btn"
                                                    title="Position véhicule"
                                                    data-uid="{{ $transfer->vehicule->hermes_uid }}"
                                                    data-name="{{ $transfer->vehicule->name }}"
                                                    data-origin="{{ $origin }}"
                                                    data-destination="{{ $destination }}"
                                                    data-waypoints="{{ $waypoints }}">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    Carte
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="route-cell">
                                    <div class="route-main">{{ $transfer->from }} -> {{ $transfer->target }}</div>
                                    @if ($transfer->trajets->count() == 0)
                                        <div class="text-danger mt-1">Anciennes informations, trajet à ressaisir.</div>
                                    @else
                                        <ul class="trajet-list">
                                            @foreach ($transfer->trajets as $index => $trajet)
                                                @php
                                                    $trajetTime = \Carbon\Carbon::parse($trajet->datetime);
                                                    $displayTrajetTime = $index === 0 ? $trajetTime->copy()->subMinutes($surplaceMinutes) : $trajetTime;
                                                @endphp
                                                <li>
                                                    <span class="badge bg-primary">{{ $index + 1 }}</span>
                                                    {{ $displayTrajetTime->format($displayTrajetTime->isToday() ? 'H:i' : 'd/m/Y H:i') }}
                                                    @if($index === 0)
                                                        <span class="badge bg-warning text-dark">Sur place</span>
                                                    @endif
                                                    <strong>{{ $trajet->type }}:</strong>
                                                    {{ $trajet->from }}
                                                    @if($trajet->google_address)
                                                        <span class="muted-line d-block">{{ $trajet->google_address }}</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                        <div class="mt-1">
                                            <a href="https://www.google.com/maps/dir/?api=1&origin={{ $origin }}&waypoints={{ $waypoints }}&destination={{ $destination }}" target="_blank">Carte</a>
                                            <span class="muted-line ms-2">{{ $transfer->km }} km</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center {{ $transfer->pax > 8 ? 'bg-dark text-white' : '' }}">
                                    <strong>{{ $transfer->pax }}</strong>
                                </td>
                                <td>
                                    <span class="status-pill {{ $callClass }}">{{ $callLabel }}</span>
                                    @if ($transfer->called_at)
                                        <div class="muted-line">appel {{ date('H:i', strtotime($transfer->called_at)) }}</div>
                                    @endif
                                    <div class="muted-line mt-1">Chauffeur: {{ isset($transfer->status->name) ? $transfer->status->name : '-' }}</div>
                                    @if ($driverConfirmed && $secondDriverConfirmed)
                                        <div class="text-success mt-1">Service confirmé par le chauffeur</div>
                                    @endif
                                    @if (!$transfer->client_status_id)
                                        <div class="text-danger mt-1">Détails chauffeur non envoyés au client</div>
                                    @endif
                                </td>
                                <td class="col-actions">
                                    @hasanyrole('Admin|ofis')
                                        <a class="btn btn-primary btn-sm" href="{{ route('transfers.edit', $transfer->id) }}">Modifier</a>
                                    @endhasanyrole
                                    @if($comment)
                                        <div class="mt-2">
                                            <span class="comment-text">{{ $shortComment }}</span>
                                            @if($isLong)
                                                <div id="fullComment-{{ $key }}" class="collapse">{{ $comment }}</div>
                                                <a href="#" class="read-more" data-bs-toggle="collapse" data-bs-target="#fullComment-{{ $key }}">Lire la suite</a>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">Aucun transfert pour cette sélection.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                {!! $transfers->appends(\Request::except('page'))->links('vendor/pagination/bootstrap-4') !!}
            </div>
        </div>
    </div>

    @php
        $faturasizTotal = $faturasizTotal ?? $faturasiz->count();
    @endphp
    <div class="alert {{ $faturasizTotal ? 'alert-warning' : 'alert-light' }} mt-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <strong>Dossiers sans facture</strong>
                <span class="badge bg-dark ml-1">{{ $faturasizTotal }}</span>
                <div class="small text-muted">Selon le nouveau suivi: hors dossiers non facturables, factures supprimées ignorées, congés exclus.</div>
            </div>
            <a class="btn btn-sm btn-outline-dark" href="{{ route('posts.uninvoiced') }}">Voir la liste complète</a>
        </div>
        <div class="mt-2 d-flex flex-wrap gap-1">
            @forelse ($faturasiz as $fatura)
                @php
                    $serviceTotal = (int) ($fatura->transfer_count ?? 0)
                        + (int) ($fatura->hotel_count ?? 0)
                        + (int) ($fatura->other_count ?? 0)
                        + (int) ($fatura->stock_count ?? 0);
                    $labelParts = array_filter([
                        '#' . $fatura->id,
                        $fatura->acente_name ?? null,
                        $serviceTotal ? $serviceTotal . ' service(s)' : null,
                    ]);
                @endphp
                <a class="badge bg-warning text-dark text-decoration-none" href="{{ route('posts.show', $fatura->id) }}" title="{{ implode(' · ', $labelParts) }}">
                    #{{ $fatura->id }}
                    @if(!empty($fatura->acente_name))
                        · {{ \Illuminate\Support\Str::limit($fatura->acente_name, 22) }}
                    @endif
                </a>
            @empty
                <span class="text-muted">Aucun dossier en retard de facture.</span>
            @endforelse
            @if($faturasizTotal > $faturasiz->count())
                <a class="badge bg-dark text-white text-decoration-none" href="{{ route('posts.uninvoiced') }}">+{{ $faturasizTotal - $faturasiz->count() }} autres</a>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="mapModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="mapTitle">Position du véhicule</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        <div id="map" style="height: 420px;"></div>
      </div>
    </div>
  </div>
</div>

<button id="scrollTopBtn" onclick="scrollToTop()" class="btn btn-primary shadow floating-scroll-btn">
    <i class="fas fa-arrow-up"></i>
</button>
<button id="scrollBottomBtn" onclick="scrollToBottom()" class="btn btn-secondary shadow floating-scroll-btn">
    <i class="fas fa-arrow-down"></i>
</button>
@endsection

@section('footer')
<script src="{{ asset('/js/moment.min.js') }}"></script>
<script src="{{ asset('/js/daterangepicker.js') }}"></script>
<script>
  const startFromServer = "{{ $startStr ?? '' }}";
  const endFromServer   = "{{ $endStr ?? '' }}";
  const $dr = $('input[name="daterange"]');

  $dr.daterangepicker({
      locale: {
          format: 'DD/MM/YYYY',
          applyLabel: 'Appliquer',
          cancelLabel: 'Annuler',
          daysOfWeek: ['Di','Lu','Ma','Me','Je','Ve','Sa'],
          monthNames: ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre']
      },
      autoUpdateInput: true,
      startDate: startFromServer ? moment(startFromServer, "YYYY-MM-DD") : moment(),
      endDate:   endFromServer   ? moment(endFromServer,   "YYYY-MM-DD") : moment()
  });

  if (startFromServer && endFromServer) {
      $dr.val(
          moment(startFromServer, "YYYY-MM-DD").format("DD/MM/YYYY")
          + " - " +
          moment(endFromServer, "YYYY-MM-DD").format("DD/MM/YYYY")
      );
      $('#hiddenStartDate').val(startFromServer);
      $('#hiddenEndDate').val(endFromServer);
  }

  $dr.on('apply.daterangepicker', function(ev, picker) {
      $('#hiddenStartDate').val(picker.startDate.format('YYYY-MM-DD'));
      $('#hiddenEndDate').val(picker.endDate.format('YYYY-MM-DD'));
      document.forms['tarih'].submit();
  });

  @if (session('toast-danger'))
      toastr.error("{{ session('toast-danger') }}");
  @endif
</script>

<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}" async defer></script>
<script>
$(document).on('click', '.show-map', function () {
   function getIconByStatus(code) {
        switch (code) {
            case 0: return 'https://maps.google.com/mapfiles/ms/icons/red-dot.png';
            case 1: return 'https://maps.google.com/mapfiles/ms/icons/green-dot.png';
            case 2: return 'https://maps.google.com/mapfiles/ms/icons/yellow-dot.png';
            default: return 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png';
        }
    }

    const uid = $(this).data('uid');
    const name = $(this).data('name');
    const origin = $(this).data('origin');
    const destination = $(this).data('destination');
    const waypointsStr = $(this).data('waypoints');

    $.get(`/hermes/location/${uid}`, function (res) {
        if (res.latitude && res.longitude) {
            const pos = { lat: parseFloat(res.latitude), lng: parseFloat(res.longitude) };
            const map = new google.maps.Map(document.getElementById("map"), { center: pos, zoom: 10 });

            new google.maps.Marker({
                position: pos,
                map: map,
                title: name,
                icon: getIconByStatus(res.status_code)
            });

            if (origin && destination) {
                const directionsService = new google.maps.DirectionsService();
                const directionsRenderer = new google.maps.DirectionsRenderer();
                directionsRenderer.setMap(map);

                const waypoints = waypointsStr
                    ? waypointsStr.split('|').map(location => ({ location, stopover: true }))
                    : [];

                directionsService.route({
                    origin: origin,
                    destination: destination,
                    waypoints: waypoints,
                    travelMode: google.maps.TravelMode.DRIVING
                }, function (result, status) {
                    if (status === google.maps.DirectionsStatus.OK) {
                        directionsRenderer.setDirections(result);
                    } else {
                        console.error('Directions request failed due to ' + status);
                    }
                });
            }

            $('#mapTitle').text(name + ' - ' + res.status);
            $('#mapModal').modal('show');
        } else {
            alert('Position indisponible.');
        }
    });
});

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
function scrollToBottom() {
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
}
document.addEventListener('scroll', function () {
    const btn = document.getElementById('scrollTopBtn');
    if (btn) btn.style.display = window.scrollY > 300 ? 'block' : 'none';
});

</script>
@endsection
