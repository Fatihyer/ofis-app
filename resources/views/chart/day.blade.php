@extends('layouts.app')
@section('style')
<link href="{{ asset('css/daterangepicker.css') }}" rel="stylesheet">
<style>
    .navette-page { background:#f8fafc; min-height:calc(100vh - 90px); padding:16px; }
    .navette-header { display:flex; justify-content:space-between; gap:14px; align-items:flex-start; margin-bottom:14px; }
    .navette-title h1 { margin:0; font-size:25px; font-weight:850; color:#0f172a; }
    .navette-title small { color:#64748b; font-weight:750; }
    .navette-filter { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:12px; box-shadow:0 8px 22px rgba(15,23,42,.06); }
    .quick-row { display:flex; flex-wrap:wrap; gap:7px; align-items:center; }
    .depot-filter-row { display:flex; align-items:center; gap:8px; margin-bottom:10px; padding-bottom:10px; border-bottom:1px solid #eef2f7; }
    .depot-filter-label { color:#64748b; font-size:11px; font-weight:850; text-transform:uppercase; letter-spacing:.03em; }
    .depot-radio-group { display:inline-flex; flex-wrap:wrap; gap:5px; align-items:center; }
    .depot-radio {
        display:inline-flex;
        align-items:center;
        min-height:31px;
        padding:6px 12px;
        border:1px solid #dbe3ef;
        border-radius:6px;
        background:#fff;
        color:#475569;
        font-size:12px;
        font-weight:850;
        line-height:1;
        text-decoration:none;
        white-space:nowrap;
    }
    .depot-radio:hover { text-decoration:none; color:#0f172a; border-color:#94a3b8; }
    .depot-radio.active { background:#111827; border-color:#111827; color:#fff; box-shadow:0 1px 4px rgba(15,23,42,.18); }
    .stat-grid { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:10px; margin-bottom:14px; }
    .stat-tile { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:12px; min-height:82px; }
    .stat-tile small { display:block; color:#64748b; font-size:11px; text-transform:uppercase; font-weight:850; letter-spacing:.02em; }
    .stat-tile strong { display:block; color:#0f172a; font-size:25px; line-height:1; margin-top:7px; }
    .stat-tile.warning { border-left:4px solid #f59e0b; }
    .stat-tile.danger { border-left:4px solid #ef4444; }
    .navette-card { background:#fff; border:1px solid #e5e7eb; border-radius:8px; margin-bottom:10px; overflow:hidden; box-shadow:0 8px 22px rgba(15,23,42,.05); }
    .navette-card.conge { opacity:.86; }
    .time-panel { height:100%; padding:14px; background:#111827; color:#fff; display:grid; gap:8px; align-content:center; text-align:center; }
    .time-panel .time-label { color:#cbd5e1; font-size:11px; font-weight:850; text-transform:uppercase; }
    .time-panel .time-value { font-size:24px; font-weight:900; line-height:1; }
    .time-panel .time-small { color:#e2e8f0; font-size:12px; font-weight:750; }
    .service-badge { display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:5px 9px; font-size:12px; font-weight:850; }
    .service-badge.status { background:#e0f2fe; color:#075985; }
    .service-badge.warn { background:#fef3c7; color:#92400e; }
    .service-badge.ok { background:#dcfce7; color:#166534; }
    .service-badge.danger { background:#fee2e2; color:#991b1b; }
    .navette-body { padding:12px 14px; }
    .navette-topline { display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:flex-start; }
    .navette-route { font-size:15px; font-weight:850; color:#0f172a; margin-top:8px; }
    .navette-meta { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
    .navette-meta span, .navette-meta a { display:inline-flex; align-items:center; gap:5px; border:1px solid #e5e7eb; border-radius:999px; padding:4px 8px; font-size:12px; font-weight:800; background:#f8fafc; color:#334155; }
    .navette-meta .chauffeur-pill { background:#eef2ff; border-color:#c7d2fe; color:#3730a3; }
    .navette-meta .driver-days { border:0; background:#3730a3; color:#fff; padding:2px 6px; font-size:11px; margin-left:3px; }
    .navette-meta .driver-days:empty { display:none; }
    .navette-meta .dossier-days { background:#fff7ed; border-color:#fed7aa; color:#9a3412; }
    .navette-actions { display:flex; flex-wrap:wrap; gap:6px; margin-top:10px; }
    .trajet-list { margin-top:9px; display:grid; gap:5px; }
    .trajet-item { background:#f8fafc; border:1px solid #e5e7eb; border-radius:7px; padding:6px 8px; font-size:12px; color:#334155; }
    .client-list { margin-top:8px; font-size:12px; color:#475569; }
    @media (max-width: 991.98px) { .navette-header { display:block; } .navette-filter { margin-top:10px; } .stat-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width: 575.98px) { .navette-page { padding:9px; } .stat-grid { grid-template-columns:1fr; } .time-panel { text-align:left; } }
</style>
@endsection

@section('content')
@php
    $today = date('Y-m-d');
    $startDate = request('start_date') ?? $today;
    $yesterdayQuick = date('Y-m-d', strtotime('-1 day'));
    $tomorrowQuick = date('Y-m-d', strtotime('+1 day'));
    $prevDate = date('Y-m-d', strtotime($startDate . ' -1 day'));
    $nextDate = date('Y-m-d', strtotime($startDate . ' +1 day'));
    $totalTransfers = $data->count();
    $totalPax = (int) $data->sum('pax');
    $missionCount = $data->where('mission', true)->count();
    $withoutDriver = $data->filter(fn($t) => !$t->driver || in_array(trim(optional($t->driver)->name ?? ''), ['-', '--', '---', '----'], true))->count();
    $withoutVehicle = $data->filter(function($t) {
        $requiresRealVehicule = (int) optional($t->servicetype)->firma_id === 3;
        if (!$requiresRealVehicule) return false;
        $vehicleName = trim(optional($t->vehicule)->name ?? '');
        return !$t->vehicule || !$t->vehicule->real || in_array($vehicleName, ['-', '--', '---', '----'], true);
    })->count();
    $airportCount = $data->filter(fn($t) => optional(optional($t->post)->acente)->airportshuttle)->count();
    $surplaceBefore = \App\Models\Option::where('name', 'surplaceMinBefore')->value('value') ?? 15;
@endphp

<div class="navette-page">
    <div class="navette-header">
        <div class="navette-title">
            <h1>Navette</h1>
            <small>{{ \Carbon\Carbon::parse($startDate)->translatedFormat('l d/m/Y') }} · planning quotidien</small>
        </div>
        <form method="get" name="tarih" class="navette-filter">
            <div class="depot-filter-row">
                <span class="depot-filter-label">Dépôt</span>
                <div class="depot-radio-group" role="radiogroup" aria-label="Dépôt">
                    <a role="radio" aria-checked="{{ $selectedDepotId === null ? 'true' : 'false' }}" class="depot-radio {{ $selectedDepotId === null ? 'active' : '' }}" href="{{ route('day', ['start_date' => $startDate, 'acente' => request('acente')]) }}">Tous</a>
                    @foreach($depotOptions as $depot)
                        <a role="radio" aria-checked="{{ (int) $selectedDepotId === (int) $depot->id ? 'true' : 'false' }}" class="depot-radio {{ (int) $selectedDepotId === (int) $depot->id ? 'active' : '' }}" href="{{ route('day', ['start_date' => $startDate, 'acente' => request('acente'), 'depot_id' => $depot->id]) }}">{{ $depot->planning_label }}</a>
                    @endforeach
                </div>
            </div>
            <div class="quick-row mb-2">
                <a href="{{ route('day', ['start_date' => $prevDate, 'acente' => request('acente'), 'depot_id' => $selectedDepotId]) }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-chevron-left"></i></a>
                <a href="{{ route('day', ['start_date' => $yesterdayQuick, 'acente' => request('acente'), 'depot_id' => $selectedDepotId]) }}" class="btn {{ request('start_date') === $yesterdayQuick ? 'btn-secondary' : 'btn-light' }} btn-sm">Hier</a>
                <a href="{{ route('day', ['start_date' => $today, 'acente' => request('acente'), 'depot_id' => $selectedDepotId]) }}" class="btn {{ request('start_date') === $today || !request('start_date') ? 'btn-secondary' : 'btn-light' }} btn-sm">Aujourd'hui</a>
                <a href="{{ route('day', ['start_date' => $tomorrowQuick, 'acente' => request('acente'), 'depot_id' => $selectedDepotId]) }}" class="btn {{ request('start_date') === $tomorrowQuick ? 'btn-secondary' : 'btn-light' }} btn-sm">Demain</a>
                <a href="{{ route('day', ['start_date' => $nextDate, 'acente' => request('acente'), 'depot_id' => $selectedDepotId]) }}" class="btn btn-outline-secondary btn-sm"><i class="fa fa-chevron-right"></i></a>
                <input type="text" name="daterange" class="form-control form-control-sm" style="width:126px;" value="{{ date('d/m/Y', strtotime($startDate)) }}">
                <input type="hidden" name="start_date" id="hiddenStartDate" value="{{ $startDate }}">
            </div>
            <div class="quick-row">
                <select name="acente" class="form-control form-control-sm" onchange="this.form.submit()" style="min-width:190px;">
                    <option value="">Toutes les agences</option>
                    @foreach ($acenteListesi as $acente)
                        <option value="{{ $acente }}" {{ request('acente') == $acente ? 'selected' : '' }}>{{ $acente }}</option>
                    @endforeach
                </select>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('vehiculescontrol') }}">Véhicules</a>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('driverUsage.index') }}">Chauffeurs</a>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('heuredetravail') }}">Temps de travail</a>
            </div>
        </form>
    </div>

    <div class="stat-grid">
        <div class="stat-tile"><small>Transferts</small><strong>{{ $totalTransfers }}</strong><div class="text-muted small">sur la journée</div></div>
        <div class="stat-tile"><small>Passagers</small><strong>{{ $totalPax }}</strong><div class="text-muted small">total pax</div></div>
        <div class="stat-tile {{ $withoutDriver ? 'danger' : '' }}"><small>Sans chauffeur</small><strong>{{ $withoutDriver }}</strong><div class="text-muted small">à traiter</div></div>
        <div class="stat-tile {{ $withoutVehicle ? 'warning' : '' }}"><small>Sans véhicule</small><strong>{{ $withoutVehicle }}</strong><div class="text-muted small">Driver Paris Via</div></div>
        <div class="stat-tile"><small>Missions</small><strong>{{ $missionCount }}</strong><div class="text-muted small">avec suivi</div></div>
        <div class="stat-tile"><small>Airport shuttle</small><strong>{{ $airportCount }}</strong><div class="text-muted small">agences shuttle</div></div>
    </div>

    @forelse ($data as $value)
        @php
            $isConge = in_array($value->servicetype_id, $congeIds, true);
            $start = \Carbon\Carbon::parse($value->start_date);
            $end = \Carbon\Carbon::parse($value->end_date);
            $ofisStart = $value->ofis_start ? \Carbon\Carbon::parse($value->ofis_start) : null;
            $surplaceTime = $start->copy()->subMinutes($surplaceBefore);
            $durationMinutes = max(0, $end->diffInMinutes($start));
            $durationLabel = intdiv($durationMinutes, 60).'h'.str_pad($durationMinutes % 60, 2, '0', STR_PAD_LEFT);
            $googleAddresses = $value->trajets->pluck('google_address')->filter()->values();
            $origin = $googleAddresses->first();
            $destination = $googleAddresses->last();
            $waypoints = $googleAddresses->slice(1, max(0, $googleAddresses->count() - 2))->implode('|');
            $requiresRealVehicule = (int) optional($value->servicetype)->firma_id === 3;
            $hasVehicle = $value->vehicule && $value->vehicule->real && !in_array(trim($value->vehicule->name ?? ''), ['-', '--', '---', '----'], true);
            $vehicleDepotId = (int) optional($value->vehicule)->depot_id;
            $storedTransferDepotId = (int) $value->depot_id;
            $transferDepotId = $vehicleDepotId ?: $storedTransferDepotId;
            $transferDepotLabel = $transferDepotId ? ($depotLabels[$transferDepotId] ?? 'Dépôt #'.$transferDepotId) : 'Sans dépôt';
            $statusColor = optional(optional($value->status)->color)->name ?: 'secondary';
            $postStart = optional($value->post)->start_date ? \Carbon\Carbon::parse($value->post->start_date)->startOfDay() : $start->copy()->startOfDay();
            $postEnd = optional($value->post)->end_date ? \Carbon\Carbon::parse($value->post->end_date)->startOfDay() : $end->copy()->startOfDay();
            if ($postEnd->lt($postStart)) {
                $postEnd = $postStart->copy();
            }
            $dossierTotalDays = max(1, $postStart->diffInDays($postEnd) + 1);
            $dossierCurrentDay = min($dossierTotalDays, max(1, $postStart->diffInDays($start->copy()->startOfDay()) + 1));
        @endphp
        <div class="navette-card {{ $isConge ? 'conge' : '' }}">
            <div class="row no-gutters">
                <div class="col-lg-2">
                    <div class="time-panel" onclick="location.href='{{ route('transfers.show', $value->id) }}';" style="cursor:pointer;">
                        @if($isConge)
                            <div class="time-label">Service</div>
                            <div class="time-value">{{ optional($value->servicetype)->name }}</div>
                        @else
                            <div><span class="time-label">En route</span><div class="time-value">{{ $ofisStart ? $ofisStart->format('H:i') : '?' }}</div></div>
                            <div><span class="time-label">Sur place</span><div class="time-value">{{ $surplaceTime->format('H:i') }}</div></div>
                            <div class="time-small">PEC {{ $start->format('H:i') }} · Fin {{ $end->format('H:i') }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-lg-10">
                    <div class="navette-body">
                        <div class="navette-topline">
                            <div>
                                <span class="service-badge status">#{{ $value->id }} · {{ optional($value->servicetype)->name ?: 'Service' }}</span>
                                <span class="service-badge ok">Dépôt: {{ $transferDepotLabel }}</span>
                                <span class="service-badge {{ $statusColor === 'danger' ? 'danger' : 'ok' }}">{{ optional($value->status)->name ?: 'Statut' }}</span>
                                @if($storedTransferDepotId && $vehicleDepotId && $storedTransferDepotId !== $vehicleDepotId)<span class="service-badge danger">Dépôt transfert à corriger</span>@endif
                                @if($value->vehicle_locked)<span class="service-badge danger">Véhicule bloqué</span>@endif
                                @if(optional(optional($value->post)->acente)->airportshuttle)<span class="service-badge warn">Airport shuttle</span>@endif
                            </div>
                            <div class="navette-actions">
                                <a class="btn btn-outline-dark btn-sm" href="{{ route('transfers.show', $value->id) }}">Transfert</a>
                                <a class="btn btn-outline-primary btn-sm" href="{{ route('posts.show', $value->post_id) }}">Dossier #{{ $value->post_id }}</a>
                                @if($origin && $destination)
                                    <a class="btn btn-outline-success btn-sm" target="_blank" href="https://www.google.com/maps/dir/?api=1&origin={{ urlencode($origin) }}&waypoints={{ urlencode($waypoints) }}&destination={{ urlencode($destination) }}"><i class="fa fa-map"></i> Maps</a>
                                @endif
                                @if (Auth::user() && Auth::user()->hasRole('Superadmin'))
                                    @php
                                        $datestart = \Carbon\Carbon::parse($value->start_date);
                                        $dateend = \Carbon\Carbon::parse($value->end_date);
                                    @endphp
                                    <a class="btn btn-primary btn-sm" href="#" data-bs-toggle="modal"
                                        data-id="{{ $value->id }}"
                                        data-servicetype="{{ $value->servicetype->id }}"
                                        data-date="{{ $datestart->format('Y-m-d') }}"
                                        data-time="{{ $datestart->format('H:i') }}"
                                        data-dateend="{{ $dateend->format('Y-m-d') }}"
                                        data-endtime="{{ $dateend->format('H:i') }}"
                                        data-ofisdate="{{ $ofisStart ? $ofisStart->format('Y-m-d') : '' }}"
                                        data-ofistime="{{ $ofisStart ? $ofisStart->format('H:i') : '' }}"
                                        data-from="{{ $value->from }}"
                                        data-to="{{ $value->target }}"
                                        data-vehicule="{{ $value->vehicule ? $value->vehicule->id : '' }}"
                                        data-vehicle-locked="{{ $value->vehicle_locked ? 1 : 0 }}"
                                        data-driver="{{ isset($value->driver->id) ? $value->driver->id : '' }}"
                                        data-pax="{{ $value->pax }}"
                                        data-comments="{{ $value->comments }}"
                                        data-mission="{{ $value->mission }}"
                                        data-firma="{{ isset($value->driver->firmas[0]) ? $value->driver->firmas[0]->id : 0 }}"
                                        data-status="{{ $value->status_id }}"
                                        data-bs-target="#edittransfert">Modifier en route</a>
                                    <a class="btn btn-outline-primary btn-sm" href="{{ route('transfers.edit', $value->id) }}">Modifier trajet</a>
                                @endif
                            </div>
                        </div>

                        <div class="navette-route"><strong>{{ $value->pax }} pax</strong> · {{ $value->from ?: '-' }} <i class="fa fa-arrow-right mx-1"></i> {{ $value->target ?: '-' }}</div>

                        <div class="navette-meta">
                            <span><i class="fa fa-clock"></i> Durée {{ $durationLabel }}</span>
                            <span class="dossier-days"><i class="fa fa-calendar-day"></i> Jour {{ $dossierCurrentDay }}/{{ $dossierTotalDays }}</span>
                            @if($value->driver)
                                <a href="{{ route('acentes.show', $value->driver->id) }}" class="chauffeur-pill"><i class="fa fa-user-tie"></i> {{ $value->driver->name }} <span class="driver-days" data-driver-id="{{ $value->driver->id }}" data-driver-name="{{ $value->driver->name }}"></span></a>
                                <a href="#" class="driver-name" data-driver-id="{{ $value->driver->id }}" data-driver-name="{{ $value->driver->name }}"><i class="fa fa-calendar"></i> Heures</a>
                                <a href="#" class="driver-calendar" data-driver-id="{{ $value->driver->id }}" data-driver-name="{{ $value->driver->name }}">Calendrier</a>
                            @else
                                <span class="text-danger"><i class="fa fa-user-slash"></i> Sans chauffeur</span>
                            @endif
                            @if($hasVehicle)
                                <a href="{{ route('vehicules.show', $value->vehicule_id) }}"><i class="fa fa-car-side"></i> {{ $value->vehicule->plaka ?: $value->vehicule->name }}</a>
                            @elseif($requiresRealVehicule)
                                <span class="text-warning"><i class="fa fa-car-side"></i> Sans véhicule</span>
                            @else
                                <span><i class="fa fa-car-side"></i> Véhicule non requis</span>
                            @endif
                            <a href="{{ route('acentes.show', $value->post->acente->id) }}"><i class="fa fa-building"></i> {{ $value->post->acente->name }}</a>
                            @if($value->post->user)<span><i class="fa fa-user"></i> {{ $value->post->user->name }}</span>@endif
                            <span><i class="fa fa-road"></i> KM {{ $value->km ?: '-' }}</span>
                        </div>

                        @if($value->comments)
                            <div class="mt-2 text-muted"><i class="fa fa-comment"></i> {{ $value->comments }}</div>
                        @endif

                        @if($value->trajets->count())
                            <div class="trajet-list">
                                @foreach ($value->trajets as $index => $trajet)
                                    <div class="trajet-item"><strong>{{ $index + 1 }}. {{ \Carbon\Carbon::parse($trajet->datetime)->format('H:i') }} · {{ $trajet->type }}</strong> — {{ $trajet->from }} {{ $trajet->google_address }}</div>
                                @endforeach
                            </div>
                        @endif

                        @if($value->post->client->count())
                            <div class="client-list">
                                <strong>Clients:</strong>
                                @foreach ($value->post->client as $misafir)
                                    {{ trim(($misafir->tittle ?? '').' '.($misafir->name ?? '').' '.($misafir->surname ?? '').' '.($misafir->tel ?? '')) }}@if(!$loop->last) · @endif
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-2 small">
                            <strong>Mission:</strong>
                            @if (isset($value->missionr->hareket))
                                @php
                                    $missionTimes = [
                                        'Départ' => $value->missionr->hareket,
                                        'Sur place' => $value->missionr->surplace,
                                        'À bord' => $value->missionr->taked,
                                        'Fin' => $value->missionr->finish,
                                    ];
                                @endphp
                                @foreach($missionTimes as $label => $time)
                                    <span>{{ $label }} {{ $time ? date('H:i', strtotime($time)) : 'Non défini' }}</span>@if(!$loop->last), @endif
                                @endforeach
                            @else
                                <span class="text-muted">Aucune mission réelle</span>
                            @endif
                            @if (isset($value->harekets[0]->payment->color_id))
                                <span class="badge badge-{{ $value->harekets[0]->payment->color->name }} ml-2">{{ $value->harekets[0]->payment->name }}</span>
                                @hasrole('Admin')
                                    <span class="text-muted ml-2">Sale {{ $value->harekets[0]->default_price }} · Achat {{ $value->harekets[0]->amount }}</span>
                                @endhasrole
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-light border">Aucun transfert pour cette journée.</div>
    @endforelse
</div>

    <!-- Modal -->
   <!-- Modal HTML -->
<div class="modal fade" id="driverModal" tabindex="-1" role="dialog" aria-labelledby="driverModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="driverModalLabel">Driver Work Hours</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul id="workHoursList" class="list-group">
                    <!-- List of work hours will be appended here by JavaScript -->
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Calendar Modal HTML -->
<div class="modal fade" id="calendarModal" tabindex="-1" role="dialog" aria-labelledby="calendarModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="calendarModalLabel">Driver Monthly Calendar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between mb-3">
                    <a href="#" id="prevMonth" class="btn btn-outline-primary">&larr; Previous Month</a>
                    <span id="driverName"></span>
                    <a href="#" id="nextMonth" class="btn btn-outline-primary">Next Month &rarr;</a>
                </div>
                <table id="calendarTable" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Lundi</th>
                            <th>Mardi</th>
                            <th>Mercredi</th>
                            <th>Jeudi</th>
                            <th>Vendredi</th>
                            <th>Samedi</th>
                            <th>Dimanche</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Calendar days will be appended here by JavaScript -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@include ('transfert.edit') {{-- Including create blade file --}}
@endsection

@section('footer') 

<script src="{{ asset('/js/moment.min.js') }}"></script>
<script src="{{ asset('/js/daterangepicker.js') }}"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

<script>
    $(document).ready(function() {
        $('input[name="daterange"]').daterangepicker({
            singleDatePicker: true,
            locale: {
                format: 'DD/MM/YYYY'
            }
        });

        $('input[name="daterange"]').on('apply.daterangepicker', function(ev, picker) {
            $('#hiddenStartDate').val(picker.startDate.format('YYYY-MM-DD'));
            document.forms['tarih'].submit();
        });

        function getMonthName(monthNumber) {
            const monthNames = [
                "Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet",
                "Août", "Septembre", "Octobre", "Novembre", "Décembre"
            ];
            return monthNames[monthNumber - 1];
        }

        // Fetch driver work days
        $.ajax({
            url: '{{ route("getDriverWorkDays") }}',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $('.driver-days').each(function() {
                    var driverId = $(this).data('driver-id');
                    var driverData = response.driverWorkDays.find(driver => driver.driver_id == driverId);
                    if (driverData) {
                        $(this).text(driverData.days_worked + ' j');
                    }
                });
            },
            error: function(xhr, status, error) {
                console.error(error);
            }
        });

        $('.driver-name').on('click', function(e) {
            e.preventDefault();
            var driverId = $(this).data('driver-id');
            var driverName = $(this).data('driver-name');
            $.ajax({
                url: '{{ url("/get-driver-hours") }}/' + driverId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var workHoursList = $('#workHoursList');
                    workHoursList.empty();

                    if (response.hoursWorked.length === 0) {
                        workHoursList.append('<li class="list-group-item">Not Work</li>');
                    } else {
                        $.each(response.hoursWorked, function(index, work) {
                            var listItem = '<li class="list-group-item' + (work.hours_worked === 'congé' ? ' text-danger' : '') + '">' +
                                work.date + ': ' + work.hours_worked +
                                '</li>';
                            workHoursList.append(listItem);
                        });
                    }

                    $('#driverModalLabel').text('Driver: ' + driverName + ' Work Hours');
                    $('#driverModal').modal('show');
                },
                error: function(xhr, status, error) {
                    console.error(error);
                }
            });
        });

        // Show modal on driver name click
        $('.driver-calendar').on('click', function(e) {
            e.preventDefault();
            var driverId = $(this).data('driver-id');
            var driverName = $(this).data('driver-name');
            loadMonthlyCalendar(driverId, driverName, new Date().getFullYear(), new Date().getMonth() + 1);
        });

        function loadMonthlyCalendar(driverId, driverName, year, month) {
            $.ajax({
                url: '{{ url("/get-driver-monthly-calendar") }}/' + driverId + '/' + year + '/' + month,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var calendarTable = $('#calendarTable tbody');
                    calendarTable.empty();

                    var daysInMonth = new Date(year, month, 0).getDate();
                    var startDate = new Date(year, month - 1, 1);
                    var dayOfWeek = startDate.getDay() || 7; // Adjust for Monday start

                    // Generate calendar rows and cells
                    var row = $('<tr></tr>');
                    for (var i = 1; i < dayOfWeek; i++) {
                        row.append('<td></td>');
                    }
                    for (var day = 1; day <= daysInMonth; day++) {
                        if (dayOfWeek > 7) {
                            calendarTable.append(row);
                            row = $('<tr></tr>');
                            dayOfWeek = 1;
                        }

                        var date = year + '-' + (month < 10 ? '0' : '') + month + '-' + (day < 10 ? '0' : '') + day;
                        var dayContent = response.calendar[date] === 'congé' ? '<span class="text-danger">' + day + '</span>' : day;

                        row.append('<td>' + dayContent + '</td>');
                        dayOfWeek++;
                    }
                    if (row.children().length) {
                        calendarTable.append(row);
                    }

                    var monthName = getMonthName(month);

                    $('#calendarModalLabel').text('Driver: ' + driverName + ' - ' + monthName + ' ' + year);
                    $('#prevMonth').data('driver-id', driverId).data('driver-name', driverName).data('year', year).data('month', month - 1);
                    $('#nextMonth').data('driver-id', driverId).data('driver-name', driverName).data('year', year).data('month', month + 1);
                    $('#calendarModal').modal('show');
                },
                error: function(xhr, status, error) {
                    console.error(error);
                }
            });
        }

        $('#prevMonth').on('click', function(e) {
            e.preventDefault();
            var driverId = $(this).data('driver-id');
            var driverName = $(this).data('driver-name');
            var year = $(this).data('year');
            var month = $(this).data('month');

            if (month < 1) {
                year--;
                month = 12;
            }

            loadMonthlyCalendar(driverId, driverName, year, month);
        });

        $('#nextMonth').on('click', function(e) {
            e.preventDefault();
            var driverId = $(this).data('driver-id');
            var driverName = $(this).data('driver-name');
            var year = $(this).data('year');
            var month = $(this).data('month');

            if (month > 12) {
                year++;
                month = 1;
            }

            loadMonthlyCalendar(driverId, driverName, year, month);
        });
    });
</script>

@include ('transfert.createEdit-js') {{-- Including create blade file --}}


@endsection
