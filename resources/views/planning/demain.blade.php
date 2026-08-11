@extends('layouts.app')

@php
    $doubleEquipageEnabled = \Illuminate\Support\Facades\Schema::hasColumn('transfers', 'second_driver_id');
@endphp

@section('style')
<style>
    .planning-page { padding: 18px; color: #172033; }
    .planning-head { display: flex; justify-content: space-between; gap: 14px; align-items: flex-start; margin-bottom: 16px; }
    .planning-title h1 { margin: 0; font-size: 24px; font-weight: 800; }
    .planning-title p { margin: 4px 0 0; color: #667085; font-size: 13px; }
    .planning-filter { display: flex; gap: 8px; align-items: end; background: #fff; border: 1px solid #e5e7eb; padding: 12px; border-radius: 8px; }
    .planning-filter label { display: block; font-size: 12px; color: #667085; font-weight: 700; margin-bottom: 4px; }
    .planning-filter .form-control { height: 38px; min-width: 160px; }
    .planning-kpis { display: grid; grid-template-columns: repeat(7, minmax(120px, 1fr)); gap: 10px; margin-bottom: 14px; }
    .planning-kpi { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; }
    .planning-kpi span { color: #667085; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
    .planning-kpi strong { display: block; margin-top: 4px; font-size: 21px; line-height: 1.1; }
    .planning-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr); gap: 14px; align-items: start; }
    .planning-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 2px rgba(16, 24, 40, .04); margin-bottom: 14px; }
    .planning-card-head { display: flex; justify-content: space-between; gap: 10px; align-items: center; padding: 12px 14px; border-bottom: 1px solid #e5e7eb; background: #fbfcfe; }
    .planning-card-head h2 { margin: 0; font-size: 16px; font-weight: 800; }
    .planning-table-wrap { overflow-x: auto; }
    .planning-table { width: 100%; min-width: 980px; border-collapse: collapse; margin: 0; }
    .assignment-table { min-width: 920px; }
    .assignment-controls { display: grid; grid-template-columns: minmax(150px, 1fr) minmax(220px, 2fr) minmax(220px, 2fr) minmax(220px, 2fr); gap: 8px; align-items: center; }
    .assignment-controls .form-control { min-width: 0; }
    @@media (max-width: 900px) { .assignment-controls { grid-template-columns: 1fr; } }
    .planning-table th { padding: 9px 10px; background: #f8fafc; color: #475467; font-size: 11px; text-transform: uppercase; white-space: nowrap; border-bottom: 1px solid #e5e7eb; }
    .planning-table td { padding: 9px 10px; border-bottom: 1px solid #eef2f7; font-size: 13px; vertical-align: top; }
    .planning-table tbody tr:hover { background: #f9fbfd; }
    .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .main-line { font-weight: 800; color: #101828; }
    .sub-line { color: #667085; font-size: 12px; margin-top: 2px; }
    .badge-soft { display: inline-flex; align-items: center; min-height: 23px; padding: 3px 8px; border-radius: 999px; font-size: 12px; font-weight: 800; background: #eef4ff; color: #1d4ed8; white-space: nowrap; }
    .badge-ok { background: #ecfdf3; color: #047857; }
    .badge-warn { background: #fff7ed; color: #c2410c; }
    .badge-danger { background: #fef3f2; color: #b42318; }
    .badge-muted { background: #f2f4f7; color: #667085; }
    .warning-list { margin: 0; padding-left: 16px; color: #b42318; }
    .side-list { list-style: none; margin: 0; padding: 0; max-height: 420px; overflow: auto; }
    .side-list li { display: flex; justify-content: space-between; gap: 10px; padding: 9px 12px; border-bottom: 1px solid #eef2f7; }
    .side-list li:last-child { border-bottom: 0; }
    .side-name { font-weight: 800; }
    .side-meta { color: #667085; font-size: 12px; }
    .alert-block { padding: 12px 14px; border-bottom: 1px solid #eef2f7; }
    .alert-block:last-child { border-bottom: 0; }
    .empty { padding: 18px; text-align: center; color: #667085; }
    .quick-links { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
    .whatsapp-confirm-action { display: inline-flex; align-items: center; gap: 5px; margin-top: 6px; white-space: nowrap; }
    @@media (max-width: 1200px) { .planning-kpis { grid-template-columns: repeat(3, 1fr); } .planning-grid { grid-template-columns: 1fr; } }
    @@media (max-width: 680px) { .planning-page { padding: 12px; } .planning-head, .planning-filter { display: block; } .planning-filter .btn { margin-top: 8px; width: 100%; } .planning-kpis { grid-template-columns: 1fr 1fr; } }
</style>
@endsection

@section('content')
@php
    $formatTime = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('H:i') : '-';
    $formatMinutes = function ($minutes) {
        $minutes = (int) $minutes;
        if ($minutes <= 0) return '-';
        return sprintf('%dh%02d', intdiv($minutes, 60), $minutes % 60);
    };
@endphp
<div class="planning-page">
    <div class="planning-head">
        <div class="planning-title">
            <h1>Planning opérationnel</h1>
            <p>Préparation du {{ $date->format('d/m/Y') }}: transferts, véhicules, chauffeurs et alertes.</p>
        </div>
        <form class="planning-filter" method="GET" action="{{ route('planning.demain') }}">
            <div>
                <label for="date">Date</label>
                <input type="date" id="date" name="date" class="form-control" value="{{ $date->toDateString() }}">
            </div>
            <div>
                <label for="depot_id">Dépôt</label>
                <select id="depot_id" name="depot_id" class="form-control">
                    <option value="">Tous</option>
                    @foreach($depotOptions as $depot)
                        <option value="{{ $depot->id }}" {{ (int) $selectedDepotId === (int) $depot->id ? 'selected' : '' }}>{{ $depot->planning_label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Afficher</button>
            <a class="btn btn-light" href="{{ route('planning.demain') }}">Demain</a>
        </form>
    </div>

    <div class="quick-links">
        <a class="btn btn-outline-primary btn-sm" href="{{ route('vehiculescontrolviewBySpecificDate', ['date' => $date->toDateString()]) }}">Utilisation véhicules</a>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('heuredetravail') }}">Heures chauffeurs</a>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('day') }}?start_date={{ $date->toDateString() }}{{ $selectedDepotId ? '&depot_id='.$selectedDepotId : '' }}">Planning navette</a>
        <a class="btn btn-outline-primary btn-sm" href="{{ url('/ev') }}?start_date={{ $date->toDateString() }}&end_date={{ $date->toDateString() }}{{ $selectedDepotId ? '&depot_id='.$selectedDepotId : '' }}">Opérations</a>
    </div>

    <div class="planning-kpis">
        <div class="planning-kpi"><span>Transferts</span><strong>{{ $summary['transfers'] }}</strong></div>
        <div class="planning-kpi"><span>Véhicules utilisés</span><strong>{{ $summary['vehicles_used'] }}</strong></div>
        <div class="planning-kpi"><span>Véhicules libres</span><strong>{{ $summary['vehicles_available'] }}</strong></div>
        <div class="planning-kpi"><span>Véhicules panne</span><strong>{{ $summary['vehicles_broken'] }}</strong></div>
        <div class="planning-kpi"><span>Chauffeurs utilisés</span><strong>{{ $summary['drivers_used'] }}</strong></div>
        <div class="planning-kpi"><span>Chauffeurs libres</span><strong>{{ $summary['drivers_available'] }}</strong></div>
        <div class="planning-kpi"><span>Alertes</span><strong>{{ $summary['warnings'] }}</strong></div>
    </div>

    <div class="planning-grid">
        <div>
            <div class="planning-card">
                <div class="planning-card-head">
                    <h2>Transferts du jour</h2>
                    <span class="badge-soft badge-muted">{{ $serviceTransfers->count() }} lignes</span>
                </div>
                <div class="planning-table-wrap">
                    <table class="planning-table">
                        <thead>
                            <tr>
                                <th>Horaire</th>
                                <th>Dossier</th>
                                <th>Service</th>
                                <th>Trajet</th>
                                <th>Dépôt</th>
                                <th>Chauffeur</th>
                                <th>Véhicule</th>
                                <th class="num">Km</th>
                                <th>Confirmation chauffeur</th>
                                <th>Contrôle</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transferWarnings as $row)
                                @php
                                    $transfer = $row->transfer;
                                    $driverUsers = $transfer->driver ? $transfer->driver->users : collect();
                                    $hasDriverUser = $driverUsers->isNotEmpty();
                                    $driverUserLabel = $driverUsers->pluck('email')->filter()->implode(', ');
                                    $driverPhone = ($transfer->driver->whatsapp ?? null) ?: ($transfer->driver->tel ?? null);
                                    $driverPhoneClean = $driverPhone ? preg_replace('/\D+/', '', $driverPhone) : '';
                                    $planningDateLabel = $date->isTomorrow() ? 'demain' : 'le '.$date->format('d/m/Y');
                                    $driverConfirmUrl = route('showdriver', $transfer->id);
                                    $publicMissionUrl = route('mission.public', \App\Http\Controllers\MissionController::publicMissionTokenFor((int) $transfer->id));
                                    $driverWhatsappText = implode("\n", [
                                        'Bonjour '.($transfer->driver->name ?? 'Capitaine').',',
                                        'Merci de confirmer votre mission '.$planningDateLabel.'.',
                                        'Transfert #'.$transfer->id.' - '.$formatTime($transfer->start_date),
                                        'Service: '.($transfer->servicetype->name ?? '-'),
                                        'Départ: '.($transfer->from ?: '-'),
                                        'Arrivée: '.($transfer->target ?: '-'),
                                        'Véhicule: '.(($transfer->vehicule->plaka ?? '-') . ' ' . ($transfer->vehicule->name ?? '')),
                                        'Lien de confirmation: '.$driverConfirmUrl,
                                        'Suivi mission sans connexion: '.$publicMissionUrl,
                                        'Merci.',
                                    ]);
                                @endphp
                                <tr>
                                    <td><span class="main-line">{{ $formatTime($transfer->start_date) }}</span><div class="sub-line">{{ $formatTime($transfer->end_date) }}</div></td>
                                    <td><a class="main-line" href="{{ route('transfers.show', $transfer->id) }}">#{{ $transfer->id }}</a><div class="sub-line">Dossier <a href="{{ route('posts.show', $transfer->post_id) }}">{{ $transfer->post_id }}</a></div></td>
                                    <td><div class="main-line">{{ $transfer->servicetype->name ?? '-' }}</div><div class="sub-line">{{ $transfer->status->name ?? '-' }}</div></td>
                                    <td><div class="main-line">{{ Str::limit($transfer->from, 42) }}</div><div class="sub-line">{{ Str::limit($transfer->target, 42) }}</div></td>
                                    <td>
                                        @php
                                            $transferDepotId = (int) ($transfer->depot_id ?: optional($transfer->vehicule)->depot_id);
                                            $vehicleDepotId = (int) optional($transfer->vehicule)->depot_id;
                                        @endphp
                                        <span class="badge-soft badge-muted">{{ $transferDepotId ? ($depotLabels[$transferDepotId] ?? 'Dépôt #'.$transferDepotId) : 'Sans dépôt' }}</span>
                                        @if($transfer->vehicule && $transferDepotId && $vehicleDepotId && $transferDepotId !== $vehicleDepotId)
                                            <div class="sub-line text-danger">Véhicule autre dépôt</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="main-line">{{ $transfer->driver->name ?? 'Non défini' }}</div>
                                        @if($transfer->driver)
                                            @if($hasDriverUser)
                                                <div class="sub-line">Compte: {{ $driverUserLabel ?: 'lié' }}</div>
                                            @else
                                                <div class="sub-line text-danger">Compte utilisateur manquant</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td><div class="main-line">{{ $transfer->vehicule->plaka ?? '-' }}</div><div class="sub-line">{{ $transfer->vehicule->name ?? 'Non défini' }}</div></td>
                                    <td class="num">{{ number_format((float) $transfer->km, 0, ',', ' ') }}</td>
                                    <td>
                                        @if($transfer->driver_app_confirmed_at)
                                            <span class="badge-soft badge-ok">Confirmé {{ \Carbon\Carbon::parse($transfer->driver_app_confirmed_at)->format('H:i') }}</span>
                                        @else
                                            <span class="badge-soft badge-warn">En attente</span>
                                            @if($driverPhoneClean)
                                                <div><a target="_blank" href="https://api.whatsapp.com/send?phone={{ $driverPhoneClean }}&text={{ rawurlencode($driverWhatsappText) }}" class="btn btn-success btn-sm whatsapp-confirm-action"><i class="fab fa-whatsapp"></i> Demander confirmation</a></div>
                                            @else
                                                <div class="sub-line">Téléphone manquant</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if(count($row->warnings))
                                            <ul class="warning-list">
                                                @foreach($row->warnings as $warning)
                                                    <li>{{ $warning }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="badge-soft badge-ok">OK</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="empty">Aucun transfert pour cette date.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="planning-card">
                <div class="planning-card-head">
                    <h2>Modifier chauffeur / véhicule</h2>
                    <span class="badge-soft badge-muted">Met aussi à jour le mouvement chauffeur</span>
                </div>
                <div class="planning-table-wrap">
                    <table class="planning-table assignment-table">
                        <thead>
                            <tr>
                                <th>Transfert</th>
                                <th>Chauffeur</th>
                                <th>Véhicule</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($serviceTransfers as $transfer)
                                <tr>
                                    <td>
                                        <div class="main-line"><a href="{{ route('transfers.show', $transfer->id) }}">#{{ $transfer->id }}</a> · {{ $formatTime($transfer->start_date) }}</div>
                                        <div class="sub-line">Dossier <a href="{{ route('posts.show', $transfer->post_id) }}">{{ $transfer->post_id }}</a></div>
                                        <div class="sub-line">{{ Str::limit($transfer->from, 36) }} → {{ Str::limit($transfer->target, 36) }}</div>
                                    </td>
                                    <td>
                                        <form id="planning-assignment-{{ $transfer->id }}" method="POST" action="{{ route('planning.assignment.update') }}">
                                            @csrf
                                            <input type="hidden" name="transfer_id" value="{{ $transfer->id }}">
                                            <input type="hidden" name="planning_date" value="{{ $date->toDateString() }}">
                                            <input type="hidden" name="depot_id" value="{{ $selectedDepotId }}">
                                            @php
                                                $selectedDriver = $assignmentDrivers->firstWhere('id', (int) $transfer->driver_id);
                                                $selectedTypeId = $selectedDriver?->primary_type_id ?: optional($driverTypes->first())->id;
                                                $currentVehicleIsExternal = $transfer->vehicule && (empty($transfer->vehicule->plaka) || !$transfer->vehicule->real);
                                            @endphp
                                            <div class="assignment-controls" data-assignment-row>
                                                <select class="form-control form-control-sm js-driver-type" data-current-type="{{ $selectedTypeId }}" aria-label="Type chauffeur">
                                                    @foreach($driverTypes as $type)
                                                        <option value="{{ $type->id }}" {{ (int) $selectedTypeId === (int) $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                                    @endforeach
                                                </select>
                                                <select name="driver_id" class="form-control form-control-sm js-driver-select" data-current-driver="{{ $transfer->driver_id }}" required>
                                                    @foreach($assignmentDrivers as $driver)
                                                        <option value="{{ $driver->id }}" data-types="{{ $driver->type_ids->implode(',') }}" {{ (int) $transfer->driver_id === (int) $driver->id ? 'selected' : '' }} {{ $driver->is_inactive ? 'data-warning=inactive' : '' }}>{{ $driver->label }}{{ $driver->is_inactive ? ' (inactive)' : '' }}</option>
                                                    @endforeach
                                                </select>
                                                @if($doubleEquipageEnabled)
                                                    <select name="second_driver_id" class="form-control form-control-sm" aria-label="Double équipage">
                                                        <option value="">Double équipage: aucun</option>
                                                        @foreach($assignmentDrivers as $driver)
                                                            <option value="{{ $driver->id }}" data-types="{{ $driver->type_ids->implode(',') }}" {{ (int)($transfer->second_driver_id ?? 0) === (int) $driver->id ? 'selected' : '' }} {{ $driver->is_inactive ? 'data-warning=inactive' : '' }}>{{ $driver->label }}{{ $driver->is_inactive ? ' (inactive)' : '' }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                                <select name="vehicule_id" class="form-control form-control-sm">
                                                    <option value="" {{ !$transfer->vehicule_id ? 'selected' : '' }}>Véhicule extérieur / ---</option>
                                                    @if($currentVehicleIsExternal)
                                                        <option value="{{ $transfer->vehicule_id }}" selected>Véhicule extérieur / ---</option>
                                                    @endif
                                                    @foreach($assignmentVehicles as $vehicle)
                                                        @if(!$currentVehicleIsExternal || (int) $vehicle->id !== (int) $transfer->vehicule_id)
                                                            <option value="{{ $vehicle->id }}" {{ (int) $transfer->vehicule_id === (int) $vehicle->id ? 'selected' : '' }} {{ $vehicle->enpanne ? 'data-warning=panne' : '' }}>{{ $vehicle->label }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="main-line">{{ $currentVehicleIsExternal ? 'Véhicule extérieur / ---' : ($transfer->vehicule->plaka ?? 'Véhicule extérieur / ---') }}</div>
                                        <div class="sub-line">{{ $transfer->vehicule->name ?? 'Non défini' }}</div>
                                    </td>
                                    <td><button type="submit" form="planning-assignment-{{ $transfer->id }}" class="btn btn-sm btn-primary btn-block">Enregistrer</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="empty">Aucun transfert à modifier pour cette date.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="planning-card">
                <div class="planning-card-head"><h2>Alertes planning</h2><span class="badge-soft badge-warn">À vérifier</span></div>
                @forelse($vehicleConflicts as $warning)
                    <div class="alert-block">
                        <span class="badge-soft badge-danger">Véhicule</span>
                        <strong>{{ $warning->name }}</strong>: conflit entre #{{ $warning->first->id }} ({{ $formatTime($warning->first->start_date) }}-{{ $formatTime($warning->first->end_date) }}) et #{{ $warning->second->id }} ({{ $formatTime($warning->second->start_date) }}-{{ $formatTime($warning->second->end_date) }}).
                    </div>
                @empty
                @endforelse
                @forelse($driverConflicts as $warning)
                    <div class="alert-block">
                        <span class="badge-soft badge-danger">Chauffeur</span>
                        <strong>{{ $warning->name }}</strong>: conflit entre #{{ $warning->first->id }} et #{{ $warning->second->id }}.
                    </div>
                @empty
                @endforelse
                @if($vehicleConflicts->isEmpty() && $driverConflicts->isEmpty())
                    <div class="empty">Aucun conflit horaire détecté.</div>
                @endif
            </div>
        </div>

        <div>
            <div class="planning-card">
                <div class="planning-card-head"><h2>Véhicules disponibles</h2><span class="badge-soft badge-ok">{{ $availableVehicles->count() }}</span></div>
                <ul class="side-list">
                    @forelse($availableVehicles as $vehicle)
                        <li><div><div class="side-name">{{ $vehicle->plaka ?: 'Sans plaque' }}</div><div class="side-meta">{{ $vehicle->name }}</div></div><span class="badge-soft badge-ok">Libre</span></li>
                    @empty
                        <li class="empty">Aucun véhicule libre.</li>
                    @endforelse
                </ul>
            </div>

            <div class="planning-card">
                <div class="planning-card-head"><h2>Véhicules indisponibles</h2><span class="badge-soft badge-danger">{{ $brokenVehicles->count() }}</span></div>
                <ul class="side-list">
                    @forelse($brokenVehicles as $vehicle)
                        <li><div><div class="side-name">{{ $vehicle->plaka ?: 'Sans plaque' }}</div><div class="side-meta">{{ $vehicle->name }}</div></div><span class="badge-soft badge-danger">Panne</span></li>
                    @empty
                        <li class="empty">Aucun véhicule en panne.</li>
                    @endforelse
                </ul>
            </div>

            <div class="planning-card">
                <div class="planning-card-head"><h2>Chauffeurs disponibles</h2><span class="badge-soft badge-ok">{{ $availableDrivers->count() }}</span></div>
                <ul class="side-list">
                    @forelse($availableDrivers as $driver)
                        <li><div><div class="side-name">{{ $driver->name }}</div><div class="side-meta">{{ $driver->worked_days_last7 }} jours travaillés sur les 7 derniers</div></div><span class="badge-soft badge-ok">Disponible</span></li>
                    @empty
                        <li class="empty">Aucun chauffeur disponible selon les critères.</li>
                    @endforelse
                </ul>
            </div>

            <div class="planning-card">
                <div class="planning-card-head"><h2>Chauffeurs à surveiller</h2><span class="badge-soft badge-warn">{{ $riskDrivers->count() }}</span></div>
                <ul class="side-list">
                    @forelse($riskDrivers as $driver)
                        <li>
                            <div><div class="side-name">{{ $driver->name }}</div><div class="side-meta">{{ $driver->worked_days_with_target }} jours / 7 · {{ $formatMinutes($driver->target_minutes) }} prévus</div></div>
                            <span class="badge-soft {{ $driver->is_over_limit ? 'badge-danger' : 'badge-warn' }}">{{ $driver->is_over_limit ? 'Limite' : 'Long' }}</span>
                        </li>
                    @empty
                        <li class="empty">Aucun chauffeur à risque.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection


@section('scripts')
<script>
(function () {
    function optionMatchesType(option, typeId) {
        const types = (option.dataset.types || '').split(',').filter(Boolean);
        return !typeId || types.includes(String(typeId));
    }

    function refreshDriverSelect(row, keepCurrent) {
        const typeSelect = row.querySelector('.js-driver-type');
        const driverSelect = row.querySelector('.js-driver-select');
        if (!typeSelect || !driverSelect) return;

        const typeId = typeSelect.value;
        const currentDriver = keepCurrent ? driverSelect.value : null;
        let firstVisible = null;
        let currentVisible = false;

        Array.from(driverSelect.options).forEach(function (option) {
            const visible = optionMatchesType(option, typeId);
            option.hidden = !visible;
            option.disabled = !visible;
            if (visible && !firstVisible) firstVisible = option;
            if (visible && currentDriver && option.value === currentDriver) currentVisible = true;
        });

        if (currentDriver && currentVisible) {
            driverSelect.value = currentDriver;
        } else if (firstVisible) {
            driverSelect.value = firstVisible.value;
        }
    }

    document.querySelectorAll('[data-assignment-row]').forEach(function (row) {
        refreshDriverSelect(row, true);
        const typeSelect = row.querySelector('.js-driver-type');
        if (typeSelect) {
            typeSelect.addEventListener('change', function () {
                refreshDriverSelect(row, false);
            });
        }
    });
})();
</script>
@endsection
