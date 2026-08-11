@extends('layouts.app')

@section('style')
<style>
    .future-planning-page {
        background: #f8fafc;
        min-height: calc(100vh - 90px);
        padding: 18px;
    }
    .future-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        margin-bottom: 18px;
    }
    .future-title h1 {
        margin: 0;
        font-size: 26px;
        font-weight: 800;
        color: #111827;
    }
    .future-title p {
        margin: 5px 0 0;
        color: #64748b;
        font-weight: 600;
    }
    .future-filter {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 12px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .06);
    }
    .future-kpis {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 16px;
    }
    .future-kpi {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 14px;
        min-height: 92px;
    }
    .future-kpi span {
        display: block;
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .future-kpi strong {
        display: block;
        margin-top: 8px;
        color: #0f172a;
        font-size: 27px;
        line-height: 1;
    }
    .future-table-wrap {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
    }
    .future-table {
        margin-bottom: 0;
    }
    .future-table thead th {
        background: #111827;
        color: #ffffff;
        border-color: #111827;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .03em;
        white-space: nowrap;
    }
    .future-table td {
        vertical-align: middle;
    }
    .day-name {
        display: block;
        font-weight: 800;
        color: #0f172a;
    }
    .day-date {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }
    .load-track {
        height: 10px;
        border-radius: 999px;
        background: #e5e7eb;
        overflow: hidden;
        min-width: 130px;
    }
    .load-bar {
        height: 100%;
        background: linear-gradient(90deg, #16a34a, #f59e0b);
        border-radius: 999px;
    }
    .vehicle-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border: 1px solid #dbeafe;
        background: #eff6ff;
        color: #1e3a8a;
        border-radius: 999px;
        padding: 4px 8px;
        margin: 2px;
        font-size: 12px;
        font-weight: 800;
    }
    .vehicle-chip.panne {
        border-color: #fecaca;
        background: #fef2f2;
        color: #991b1b;
    }
    .vehicle-chip.unassigned {
        border-color: #fecaca;
        background: #fff1f2;
        color: #be123c;
    }
    .vehicle-chip.subcontracted {
        border-color: #fed7aa;
        background: #fff7ed;
        color: #9a3412;
    }
    .vehicle-chip.not-required {
        border-color: #cbd5e1;
        background: #f8fafc;
        color: #475569;
    }
    .subcontracted-detail {
        border: 1px solid #fed7aa;
        background: #fff7ed;
        color: #9a3412;
        border-radius: 8px;
        padding: 6px 8px;
        min-width: 220px;
        font-weight: 800;
    }
    .subcontracted-detail small {
        display: block;
        color: #64748b;
        font-weight: 700;
    }
    .future-vehicle-count {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border-radius: 999px;
        background: #e0f2fe;
        color: #075985;
        border: 1px solid #bae6fd;
        padding: 5px 10px;
        font-size: 13px;
        font-weight: 900;
        white-space: nowrap;
    }
    .future-vehicle-count strong,
    .future-vehicle-count span {
        color: inherit !important;
        font-weight: 900;
    }
    .available-type-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 250px;
    }
    .available-depot-group {
        border: 1px solid #e5e7eb;
        background: #ffffff;
        border-radius: 8px;
        padding: 6px;
    }
    .available-depot-title {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        color: #0f172a;
        font-size: 12px;
        font-weight: 900;
        margin-bottom: 4px;
    }
    .available-depot-title span:last-child {
        color: #64748b;
    }
    .available-depot-types {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }
    .available-type-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border-radius: 999px;
        padding: 4px 8px;
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
        border: 1px solid #d1d5db;
        background: #f8fafc;
        color: #111827;
    }
    .available-type-pill.van {
        background: #ecfdf5;
        border-color: #bbf7d0;
        color: #166534;
    }
    .available-type-pill.sprinter {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
    }
    .available-type-pill.coach {
        background: #fff7ed;
        border-color: #fed7aa;
        color: #9a3412;
    }
    .planning-status-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-top: 4px;
    }
    .planning-status-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 4px 9px;
        font-size: 11px;
        font-weight: 900;
        line-height: 1.15;
        white-space: nowrap;
        border: 1px solid transparent;
    }
    .planning-status-badge.assign {
        background: #fee2e2;
        border-color: #fecaca;
        color: #991b1b;
    }
    .planning-status-badge.subcontracted {
        background: #fff7ed;
        border-color: #fed7aa;
        color: #9a3412;
    }
    .planning-status-badge.no-vehicle {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #334155;
    }
    details.future-details summary {
        cursor: pointer;
        color: #2563eb;
        font-weight: 800;
        list-style: none;
    }
    details.future-details summary::-webkit-details-marker {
        display: none;
    }
    .transfer-mini-table {
        margin-top: 10px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
    }
    .transfer-mini-table table {
        margin: 0;
    }
    .transfer-mini-table th {
        background: #f1f5f9;
        color: #334155;
        font-size: 11px;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .transfer-mini-table td {
        font-size: 12px;
    }
    .assign-vehicle-form {
        display: flex;
        gap: 6px;
        align-items: center;
        min-width: 260px;
    }
    .assign-vehicle-form select {
        min-width: 170px;
    }
    @media (max-width: 991.98px) {
        .future-header {
            display: block;
        }
        .future-filter {
            margin-top: 12px;
        }
        .future-kpis {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 575.98px) {
        .future-planning-page {
            padding: 10px;
        }
        .future-kpis {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="future-planning-page">
    <div class="future-header">
        <div class="future-title">
            <h1>Planning futur des véhicules</h1>
            <p>Nombre de véhicules utilisés jour par jour, hors services congé et transferts annulés.</p>
        </div>
        <form class="future-filter" method="GET" action="{{ route('planning.futur') }}">
            <div class="form-row align-items-end">
                <div class="form-group col-md-3 mb-2">
                    <label class="mb-1 font-weight-bold">Du</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $start->format('Y-m-d') }}">
                </div>
                <div class="form-group col-md-3 mb-2">
                    <label class="mb-1 font-weight-bold">Au</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $end->format('Y-m-d') }}">
                </div>
                <div class="form-group col-md-4 mb-2">
                    <label class="mb-1 font-weight-bold">Dépôt</label>
                    <select name="depot_id" class="form-control form-control-sm">
                        <option value="">Tous</option>
                        @foreach($depotOptions as $depot)
                            <option value="{{ $depot->id }}" {{ (int) $selectedDepotId === (int) $depot->id ? 'selected' : '' }}>
                                {{ $depot->planning_label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 mb-2">
                    <button class="btn btn-primary btn-sm btn-block" type="submit">Voir</button>
                </div>
            </div>
            <div class="d-flex flex-wrap" style="gap: 6px;">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('planning.futur', ['start_date' => now()->format('Y-m-d'), 'end_date' => now()->addDays(7)->format('Y-m-d'), 'depot_id' => $selectedDepotId]) }}">7 jours</a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('planning.futur', ['start_date' => now()->format('Y-m-d'), 'end_date' => now()->addDays(30)->format('Y-m-d'), 'depot_id' => $selectedDepotId]) }}">30 jours</a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('planning.futur', ['start_date' => now()->startOfMonth()->format('Y-m-d'), 'end_date' => now()->endOfMonth()->format('Y-m-d'), 'depot_id' => $selectedDepotId]) }}">Mois en cours</a>
            </div>
        </form>
    </div>

    <div class="future-kpis">
        <div class="future-kpi">
            <span>Véhicules réels</span>
            <strong>{{ $totalRealVehicles }}</strong>
        </div>
        <div class="future-kpi">
            <span>Transferts période</span>
            <strong>{{ $totalTransfers }}</strong>
        </div>
        <div class="future-kpi">
            <span>Pic véhicules</span>
            <strong>{{ $peakDay ? $peakDay->vehicle_count : 0 }}</strong>
        </div>
        <div class="future-kpi">
            <span>À affecter</span>
            <strong class="{{ $totalUnassignedTransfers > 0 ? 'text-danger' : 'text-success' }}">{{ $totalUnassignedTransfers }}</strong>
        </div>
        <div class="future-kpi">
            <span>Jour le plus chargé</span>
            <strong style="font-size: 18px; line-height: 1.2;">{{ $peakDay ? $peakDay->date->translatedFormat('d/m/Y') : '-' }}</strong>
        </div>
    </div>

    <div class="future-table-wrap table-responsive">
        <table class="table table-sm future-table">
            <thead>
                <tr>
                    <th>Jour</th>
                    <th>Véhicules utilisés</th>
                    <th>Charge</th>
                    <th>Disponibles</th>
                    <th>Transferts</th>
                    <th>Véhicules</th>
                    <th>Détail</th>
                </tr>
            </thead>
            <tbody>
                @foreach($days as $day)
                    @php
                        $loadPercent = $maxVehicleCount > 0 ? round(($day->vehicle_count / $maxVehicleCount) * 100) : 0;
                    @endphp
                    <tr>
                        <td style="min-width: 125px;">
                            <span class="day-name">{{ ucfirst($day->date->translatedFormat('l')) }}</span>
                            <span class="day-date">{{ $day->date->format('d/m/Y') }}</span>
                        </td>
                        <td>
                            <span class="future-vehicle-count"><strong>{{ $day->vehicle_count }}</strong><span>/ {{ $totalRealVehicles }}</span></span>
                        </td>
                        <td>
                            <div class="load-track" title="{{ $day->vehicle_count }} véhicules">
                                <div class="load-bar" style="width: {{ $loadPercent }}%;"></div>
                            </div>
                        </td>
                        <td>
                            <div class="available-type-list" title="{{ $day->available_count }} véhicules disponibles">
                                @foreach($day->available_by_depot as $availableDepot)
                                    <div class="available-depot-group">
                                        <div class="available-depot-title">
                                            <span>{{ $availableDepot->label }}</span>
                                            <span>
                                                {{ $availableDepot->count }} dispo
                                                @if(($availableDepot->panne_count ?? 0) > 0)
                                                    · {{ $availableDepot->panne_count }} en panne
                                                @endif
                                            </span>
                                        </div>
                                        <div class="available-depot-types">
                                            @foreach($availableDepot->types as $availableType)
                                                <span class="available-type-pill {{ $availableType->type }}">
                                                    {{ $availableType->label }}
                                                    <strong>{{ $availableType->count }}</strong>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            <strong>{{ $day->transfer_count }}</strong>
                            <div class="planning-status-badges">
                                @if($day->unassigned_count > 0)
                                    <span class="planning-status-badge assign">{{ $day->unassigned_count }} à affecter</span>
                                @endif
                                @if($day->subcontracted_count > 0)
                                    <span class="planning-status-badge subcontracted">{{ $day->subcontracted_count }} sous-traité</span>
                                @endif
                                @if($day->vehicle_not_required_count > 0)
                                    <span class="planning-status-badge no-vehicle">{{ $day->vehicle_not_required_count }} sans véhicule requis</span>
                                @endif
                            </div>
                        </td>
                        <td style="min-width: 260px;">
                            @foreach($day->vehicles as $vehicle)
                                <a class="vehicle-chip {{ $vehicle->enpanne ? 'panne' : '' }}" href="{{ route('vehicules.show', $vehicle->id) }}">
                                    <i class="fas fa-bus"></i>
                                    {{ $vehicle->plaka ?: $vehicle->name }}
                                </a>
                            @endforeach
                            @foreach($day->subcontracted_transfers as $subcontractedTransfer)
                                <a class="vehicle-chip subcontracted" href="{{ route('transfers.show', $subcontractedTransfer->id) }}" title="Véhicule sous-traité">
                                    <i class="fas fa-handshake"></i>
                                    #{{ $subcontractedTransfer->id }} · {{ optional($subcontractedTransfer->start_date)->format('H:i') }} · Sous-traité{{ $subcontractedTransfer->externalVehicleProvider ? ' · ' . $subcontractedTransfer->externalVehicleProvider->name : '' }}
                                </a>
                            @endforeach
                            @foreach($day->vehicle_not_required_transfers as $noVehicleTransfer)
                                <a class="vehicle-chip not-required" href="{{ route('transfers.show', $noVehicleTransfer->id) }}" title="Véhicule non requis">
                                    <i class="fas fa-user-tie"></i>
                                    #{{ $noVehicleTransfer->id }} · {{ optional($noVehicleTransfer->start_date)->format('H:i') }} · Sans véhicule requis
                                </a>
                            @endforeach
                            @foreach($day->unassigned_transfers as $unassignedTransfer)
                                <a class="vehicle-chip unassigned" href="{{ route('transfers.show', $unassignedTransfer->id) }}" title="Transfert à affecter">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    #{{ $unassignedTransfer->id }} · {{ optional($unassignedTransfer->start_date)->format('H:i') }} · À affecter
                                </a>
                            @endforeach
                            @if($day->vehicles->isEmpty() && $day->unassigned_count === 0 && $day->subcontracted_count === 0 && $day->vehicle_not_required_count === 0)
                                <span class="text-muted">Aucun véhicule réel</span>
                            @endif
                        </td>
                        <td style="min-width: 160px;">
                            @if($day->transfer_count > 0)
                                <details class="future-details">
                                    <summary>Voir les transferts</summary>
                                    <div class="transfer-mini-table table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Dossier</th>
                                                    <th>Heure</th>
                                                    <th>Dépôt</th>
                                                    <th>Véhicule</th>
                                                    <th>Chauffeur</th>
                                                    <th>Service</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($day->transfers as $transfer)
                                                    <tr>
                                                        <td><a href="{{ route('transfers.show', $transfer->id) }}">#{{ $transfer->id }}</a></td>
                                                        <td>
                                                            @if($transfer->post_id)
                                                                <a href="{{ route('posts.show', $transfer->post_id) }}">#{{ $transfer->post_id }}</a>
                                                                @if(optional(optional($transfer->post)->acente)->name)
                                                                    <div class="text-muted small">{{ $transfer->post->acente->name }}</div>
                                                                @endif
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ optional($transfer->start_date)->format('H:i') }}</td>
                                                        <td>
                                                            @php
                                                                $transferDepotId = (int) ($transfer->depot_id ?: optional($transfer->vehicule)->depot_id);
                                                                $vehicleDepotId = (int) optional($transfer->vehicule)->depot_id;
                                                                $transferDepotLabel = $transferDepotId ? ($depotLabels[$transferDepotId] ?? ('Dépôt #' . $transferDepotId)) : 'Sans dépôt';
                                                            @endphp
                                                            <span class="badge bg-light text-dark border">{{ $transferDepotLabel }}</span>
                                                            @if($transfer->vehicule && $transferDepotId && $vehicleDepotId && $transferDepotId !== $vehicleDepotId)
                                                                <div class="text-danger small font-weight-bold">Véhicule autre dépôt</div>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @php
                                                                $isSubcontractedTransfer = !$transfer->vehicule || !$transfer->vehicule->real || $transfer->vehicule->sales;
                                                                $isSubcontractedTransfer = $isSubcontractedTransfer && ($transfer->vehicle_provider_acente_id || (float) $transfer->external_vehicle_price > 0 || trim((string) $transfer->external_vehicle_note) !== '');
                                                                $requiresRealVehicle = (int) optional($transfer->servicetype)->firma_id === 3;
                                                            @endphp
                                                            @if($transfer->vehicule && $transfer->vehicule->real && !$transfer->vehicule->sales)
                                                                {{ $transfer->vehicule->plaka ?: $transfer->vehicule->name }}
                                                            @elseif(!$requiresRealVehicle && !$isSubcontractedTransfer)
                                                                <span class="badge bg-light text-muted border">Véhicule non requis</span>
                                                            @elseif($isSubcontractedTransfer)
                                                                <div class="subcontracted-detail">
                                                                    <i class="fas fa-handshake"></i> Sous-traité
                                                                    <small>{{ optional($transfer->externalVehicleProvider)->name ?: 'Fournisseur à vérifier' }}</small>
                                                                    @if($transfer->external_vehicle_price)
                                                                        <small>{{ number_format((float) $transfer->external_vehicle_price, 2, ',', ' ') }} EUR</small>
                                                                    @endif
                                                                    @if($transfer->external_vehicle_note)
                                                                        <small>{{ $transfer->external_vehicle_note }}</small>
                                                                    @endif
                                                                </div>
                                                            @else
                                                                <form method="POST" action="{{ route('planning.futur.assignVehicle') }}" class="assign-vehicle-form">
                                                                    @csrf
                                                                    <input type="hidden" name="transfer_id" value="{{ $transfer->id }}">
                                                                    <input type="hidden" name="start_date" value="{{ $start->format('Y-m-d') }}">
                                                                    <input type="hidden" name="end_date" value="{{ $end->format('Y-m-d') }}">
                                                                    <input type="hidden" name="depot_id" value="{{ $selectedDepotId }}">
                                                                    <select name="vehicule_id" class="form-control form-control-sm" required>
                                                                        <option value="">Affecter un véhicule</option>
                                                                        @foreach($assignableVehicles as $vehicle)
                                                                            <option value="{{ $vehicle->id }}">
                                                                                {{ $vehicle->plaka ?: $vehicle->name }}
                                                                                @if($vehicle->depot_id)
                                                                                    - {{ $depotLabels[$vehicle->depot_id] ?? 'Dépôt #' . $vehicle->depot_id }}
                                                                                @endif
                                                                                {{ $vehicle->enpanne ? ' - en panne' : '' }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                    <button type="submit" class="btn btn-sm btn-primary">Affecter</button>
                                                                </form>
                                                            @endif
                                                        </td>
                                                        <td>{{ optional($transfer->driver)->name ?: '-' }}</td>
                                                        <td>{{ optional($transfer->servicetype)->name ?: '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
