@extends('layouts.app')

@section('style')
<link href="{{ asset('css/daterangepicker.css') }}" rel="stylesheet">
<style>
    .timeline-page {
        color: #172033;
        max-width: 100%;
        overflow-x: hidden;
    }
    .timeline-header {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 12px;
    }
    .timeline-title h1 {
        margin: 0;
        font-size: 22px;
        font-weight: 800;
    }
    .timeline-title p {
        margin: 4px 0 0;
        color: #64748b;
        font-weight: 600;
    }
    .timeline-actions,
    .timeline-shortcuts {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .timeline-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
    }
    .timeline-filter {
        display: grid;
        grid-template-columns: minmax(220px, 1.3fr) repeat(3, minmax(160px, 1fr)) auto;
        gap: 10px;
        align-items: end;
    }
    .timeline-filter label {
        display: block;
        font-size: 12px;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 4px;
    }
    .timeline-depot-filter {
        grid-column: 1 / -1;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 0 10px;
        border-bottom: 1px solid #e5e7eb;
    }
    .timeline-depot-title {
        font-size: 12px;
        color: #64748b;
        font-weight: 750;
        min-width: 56px;
    }
    .timeline-depot-options {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .timeline-depot-option {
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
    .timeline-depot-option input {
        margin: 0;
    }
    .timeline-depot-option.active {
        border-color: #2563eb;
        background: #eff6ff;
        color: #1d4ed8;
    }
    .timeline-stats {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 10px;
        margin: 12px 0;
    }
    .timeline-stat {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        padding: 12px;
        min-height: 78px;
    }
    .timeline-stat small {
        display: block;
        color: #64748b;
        font-weight: 750;
        margin-bottom: 5px;
    }
    .timeline-stat strong {
        font-size: 24px;
        line-height: 1;
    }
    .timeline-stat.warning { background: #fff7ed; border-color: #fed7aa; }
    .timeline-stat.danger { background: #fef2f2; border-color: #fecaca; }
    .timeline-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }
    .legend-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 4px;
    }
    .day-board {
        margin-top: 14px;
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
    }
    .day-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px 14px;
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
        min-width: 980px;
    }
    .day-heading strong {
        font-size: 16px;
    }
    .timeline-grid {
        min-width: 980px;
    }
    .timeline-axis,
    .timeline-row {
        display: grid;
        grid-template-columns: 220px minmax(760px, 1fr);
    }
    .timeline-axis {
        position: sticky;
        top: 0;
        z-index: 4;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
    }
    .axis-label,
    .resource-cell {
        border-right: 1px solid #e5e7eb;
        padding: 9px 12px;
        background: #fff;
    }
    .axis-hours,
    .lane {
        position: relative;
        min-height: 48px;
        background-image: linear-gradient(to right, rgba(148, 163, 184, .28) 1px, transparent 1px);
        background-size: calc(100% / var(--hour-count)) 100%;
    }
    .axis-hours {
        min-height: 40px;
        display: grid;
        grid-template-columns: repeat(var(--hour-count), minmax(0, 1fr));
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
    }
    .axis-hours span {
        padding: 10px 0 0 8px;
        border-right: 1px solid #e5e7eb;
    }
    .timeline-row {
        border-bottom: 1px solid #eef2f7;
    }
    .timeline-row:last-child {
        border-bottom: 0;
    }
    .resource-name {
        display: block;
        font-weight: 850;
        color: #0f172a;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .resource-meta {
        color: #64748b;
        font-size: 12px;
        font-weight: 650;
    }
    .lane {
        min-height: 58px;
        padding: 8px 0;
    }
    .timeline-bar {
        position: absolute;
        top: 8px;
        height: 42px;
        border-radius: 7px;
        padding: 6px 8px;
        color: #fff;
        text-decoration: none;
        box-shadow: 0 4px 10px rgba(15, 23, 42, .15);
        overflow: hidden;
        min-width: 46px;
        border: 1px solid rgba(255, 255, 255, .28);
    }
    .timeline-bar:hover {
        color: #fff;
        text-decoration: none;
        filter: brightness(.96);
    }
    .timeline-bar strong,
    .timeline-bar span {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        line-height: 1.15;
    }
    .timeline-bar strong {
        font-size: 12px;
    }
    .timeline-bar span {
        font-size: 11px;
        opacity: .9;
    }
    .bar-success { background: #16a34a; }
    .bar-pending { background: #64748b; }
    .bar-warning { background: #f59e0b; color: #111827; }
    .bar-warning:hover { color: #111827; }
    .bar-danger { background: #dc2626; }
    .bar-mission { background: #2563eb; }
    .bar-done { background: #0f766e; }
    .empty-day {
        padding: 22px;
        color: #64748b;
        text-align: center;
    }
    @media (max-width: 1199.98px) {
        .timeline-filter,
        .timeline-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 767.98px) {
        .timeline-header { display: block; }
        .timeline-actions { margin-top: 10px; }
        .timeline-filter,
        .timeline-stats { grid-template-columns: 1fr; }
        .timeline-depot-filter { align-items: flex-start; flex-direction: column; }
        .timeline-title h1 { font-size: 19px; }
    }
</style>
@endsection

@section('content')
@php
    $stats = $operationStats ?? [];
    $hourCount = max(1, count($hours) - 1);
    $surplaceMinutes = (int) (optional(\App\Models\Option::where('name', 'surplaceMinBefore')->first())->value ?? 15);
@endphp

<div class="timeline-page">
    <div class="timeline-header">
        <div class="timeline-title">
            <h1>Vue horaire des opérations</h1>
            <p>{{ $daterangeDisplay }} · {{ $stats['total'] ?? $transfers->count() }} transfert(s)</p>
        </div>
        <div class="timeline-actions">
            <a class="btn btn-outline-dark btn-sm" href="{{ route('ev', request()->except('page')) }}"><i class="fas fa-table"></i> Vue tableau</a>
            <a class="btn btn-dark btn-sm" href="{{ route('ev.timeline', request()->except('page')) }}"><i class="fas fa-stream"></i> Vue horaire</a>
            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="window.print()">Imprimer</button>
        </div>
    </div>

    @if($rangeLimited)
        <div class="alert alert-warning">
            La vue horaire est limitée à 14 jours pour rester rapide. Réduisez la période si besoin.
        </div>
    @endif

    <div class="timeline-card card mb-3">
        <div class="card-body">
            <form method="get" name="timelineFilter" class="timeline-filter" action="{{ route('ev.timeline') }}">
                @if(($depotOptions ?? collect())->isNotEmpty())
                    <div class="timeline-depot-filter">
                        <div class="timeline-depot-title">Dépôt</div>
                        <div class="timeline-depot-options" role="radiogroup" aria-label="Dépôt">
                            <label class="timeline-depot-option {{ $selectedDepotId === null ? 'active' : '' }}">
                                <input type="radio" name="depot_id" value="" onchange="this.form.submit()" {{ $selectedDepotId === null ? 'checked' : '' }}>
                                <span>Tous</span>
                            </label>
                            @foreach($depotOptions as $depot)
                                <label class="timeline-depot-option {{ (int) $selectedDepotId === (int) $depot->id ? 'active' : '' }}">
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
                        value="{{ $daterangeDisplay }}"
                        autocomplete="off" />
                    <input type="hidden" name="start_date" id="hiddenStartDate" value="{{ $startStr }}">
                    <input type="hidden" name="end_date" id="hiddenEndDate" value="{{ $endStr }}">
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
                <div>
                    <label>Raccourcis</label>
                    <div class="timeline-shortcuts">
                        <button type="submit" name="dateOption" value="yesterday" class="btn btn-outline-primary {{ request('dateOption') === 'yesterday' ? 'active' : '' }}">Hier</button>
                        <button type="submit" name="dateOption" value="today" class="btn btn-outline-primary {{ request('dateOption') === 'today' || !request()->hasAny(['start_date', 'dateOption']) ? 'active' : '' }}">Aujourd'hui</button>
                        <button type="submit" name="dateOption" value="tomorrow" class="btn btn-outline-primary {{ request('dateOption') === 'tomorrow' ? 'active' : '' }}">Demain</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="timeline-stats">
        <div class="timeline-stat">
            <small>Transferts</small>
            <strong>{{ $stats['total'] ?? 0 }}</strong>
        </div>
        <div class="timeline-stat {{ ($stats['without_driver'] ?? 0) ? 'danger' : '' }}">
            <small>Sans chauffeur</small>
            <strong>{{ $stats['without_driver'] ?? 0 }}</strong>
        </div>
        <div class="timeline-stat {{ ($stats['without_vehicle'] ?? 0) ? 'warning' : '' }}">
            <small>Sans véhicule réel</small>
            <strong>{{ $stats['without_vehicle'] ?? 0 }}</strong>
        </div>
        <div class="timeline-stat">
            <small>Confirmés chauffeur</small>
            <strong>{{ $stats['confirmed'] ?? 0 }}</strong>
        </div>
        <div class="timeline-stat">
            <small>Missions</small>
            <strong>{{ $stats['missions'] ?? 0 }}</strong>
        </div>
    </div>

    <div class="timeline-legend">
        <span><i class="legend-dot" style="background:#16a34a"></i> Chauffeur confirmé</span>
        <span><i class="legend-dot" style="background:#64748b"></i> En attente</span>
        <span><i class="legend-dot" style="background:#f59e0b"></i> Véhicule à définir</span>
        <span><i class="legend-dot" style="background:#dc2626"></i> Chauffeur manquant</span>
        <span><i class="legend-dot" style="background:#2563eb"></i> En mission</span>
    </div>

    @forelse($timelineDays as $day)
        @php
            $timelineStart = $day->date->copy()->startOfDay()->addHours($minHour);
            $timelineEnd = $day->date->copy()->startOfDay()->addHours($maxHour);
            $totalMinutes = max(60, $timelineStart->diffInMinutes($timelineEnd));
        @endphp
        <section class="day-board">
            <div class="day-heading">
                <strong>{{ ucfirst($day->date->translatedFormat('l d/m/Y')) }}</strong>
                <span class="badge bg-light text-dark border">{{ $day->transfer_count }} transfert(s)</span>
            </div>
            <div class="timeline-grid" style="--hour-count: {{ $hourCount }};">
                <div class="timeline-axis">
                    <div class="axis-label">Chauffeur</div>
                    <div class="axis-hours">
                        @foreach($hours as $hour)
                            @if(!$loop->last)
                                <span>{{ sprintf('%02d:00', $hour) }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>

                @foreach($day->rows as $row)
                    <div class="timeline-row">
                        <div class="resource-cell">
                            <span class="resource-name">{{ $row->name }}</span>
                            <span class="resource-meta">{{ $row->transfers->count() }} service(s)</span>
                        </div>
                        <div class="lane">
                            @foreach($row->transfers as $transfer)
                                @php
                                    $barStart = \Carbon\Carbon::parse($transfer->ofis_start ?: $transfer->start_date);
                                    $barEnd = \Carbon\Carbon::parse($transfer->end_date ?: $transfer->start_date);
                                    $clientStartAt = \Carbon\Carbon::parse($transfer->start_date);
                                    $clientMeetingAt = $clientStartAt->copy()->subMinutes($surplaceMinutes);
                                    if ($barEnd->lte($barStart)) {
                                        $barEnd = $barStart->copy()->addMinutes(30);
                                    }
                                    $visibleStart = $barStart->lt($timelineStart) ? $timelineStart->copy() : $barStart;
                                    $visibleEnd = $barEnd->gt($timelineEnd) ? $timelineEnd->copy() : $barEnd;
                                    if ($visibleEnd->lte($visibleStart)) {
                                        $visibleEnd = $visibleStart->copy()->addMinutes(15);
                                    }
                                    $left = max(0, min(98, round(($timelineStart->diffInMinutes($visibleStart, false) / $totalMinutes) * 100, 3)));
                                    $width = max(2, min(100 - $left, round(($visibleStart->diffInMinutes($visibleEnd) / $totalMinutes) * 100, 3)));

                                    $driverName = trim(optional($transfer->driver)->name ?? '');
                                    $vehiculeName = trim(optional($transfer->vehicule)->name ?? '');
                                    $hasRealDriver = $transfer->driver && !in_array($driverName, $emptyAssignmentNames, true);
                                    $requiresRealVehicule = (int) optional($transfer->servicetype)->firma_id === 3;
                                    $hasRealVehicule = $transfer->vehicule && $transfer->vehicule->real && !in_array($vehiculeName, $emptyAssignmentNames, true);
                                    $hasRequestedVehiculeType = $transfer->vehicule && !$hasRealVehicule && !in_array($vehiculeName, $emptyAssignmentNames, true);
                                    $driverConfirmed = !empty($transfer->driver_app_confirmed_at) || !empty($transfer->driver_confirmed_at);
                                    $secondDriverAssigned = !empty($transfer->second_driver_id) && (int) $transfer->second_driver_id !== (int) $transfer->driver_id;
                                    $secondDriverConfirmed = !$secondDriverAssigned || !empty($transfer->second_driver_app_confirmed_at);

                                    if ($transfer->missionr && $transfer->missionr->finish) {
                                        $barClass = 'bar-done';
                                    } elseif ($transfer->missionr) {
                                        $barClass = 'bar-mission';
                                    } elseif (!$hasRealDriver) {
                                        $barClass = 'bar-danger';
                                    } elseif ($requiresRealVehicule && !$hasRealVehicule) {
                                        $barClass = 'bar-warning';
                                    } elseif ($driverConfirmed && $secondDriverConfirmed) {
                                        $barClass = 'bar-success';
                                    } else {
                                        $barClass = 'bar-pending';
                                    }

                                    $vehiculeLabel = $hasRealVehicule
                                        ? (optional($transfer->vehicule)->plaka ?: optional($transfer->vehicule)->name)
                                        : ($hasRequestedVehiculeType ? optional($transfer->vehicule)->name . ' à définir' : 'Sans véhicule');
                                    $routeLabel = trim(($transfer->from ?: '-') . ' -> ' . ($transfer->target ?: '-'));
                                    $titleParts = array_filter([
                                        '#' . $transfer->id,
                                        'Dossier #' . optional($transfer->post)->id,
                                        optional(optional($transfer->post)->acente)->name,
                                        optional($transfer->driver)->name,
                                        $vehiculeLabel,
                                        'Sur place ' . $clientMeetingAt->format('H:i'),
                                        'Client ' . $clientStartAt->format('H:i'),
                                        $routeLabel,
                                    ]);
                                @endphp
                                <a class="timeline-bar {{ $barClass }}"
                                   href="{{ route('transfers.show', $transfer->id) }}"
                                   style="left: {{ $left }}%; width: {{ $width }}%;"
                                   title="{{ implode(' · ', $titleParts) }}">
                                    <strong>{{ $barStart->format('H:i') }} · #{{ $transfer->id }} · {{ optional(optional($transfer->post)->acente)->name ? \Illuminate\Support\Str::limit(optional($transfer->post->acente)->name, 16) : 'Agence' }}</strong>
                                    <span>Sur place {{ $clientMeetingAt->format('H:i') }} · Client {{ $clientStartAt->format('H:i') }} · {{ \Illuminate\Support\Str::limit($vehiculeLabel, 18) }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div class="timeline-card empty-day mt-3">Aucun transfert pour cette sélection.</div>
    @endforelse
</div>
@endsection

@section('footer')
<script src="{{ asset('/js/moment.min.js') }}"></script>
<script src="{{ asset('/js/daterangepicker.js') }}"></script>
<script>
  const startFromServer = "{{ $startStr ?? '' }}";
  const endFromServer = "{{ $endStr ?? '' }}";
  const $timelineRange = $('input[name="daterange"]');

  $timelineRange.daterangepicker({
      locale: {
          format: 'DD/MM/YYYY',
          applyLabel: 'Appliquer',
          cancelLabel: 'Annuler',
          daysOfWeek: ['Di','Lu','Ma','Me','Je','Ve','Sa'],
          monthNames: ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre']
      },
      autoUpdateInput: true,
      startDate: startFromServer ? moment(startFromServer, "YYYY-MM-DD") : moment(),
      endDate: endFromServer ? moment(endFromServer, "YYYY-MM-DD") : moment()
  });

  $timelineRange.on('apply.daterangepicker', function(ev, picker) {
      $('#hiddenStartDate').val(picker.startDate.format('YYYY-MM-DD'));
      $('#hiddenEndDate').val(picker.endDate.format('YYYY-MM-DD'));
      document.forms['timelineFilter'].submit();
  });
</script>
@endsection
