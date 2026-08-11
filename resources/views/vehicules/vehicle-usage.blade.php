@extends('layouts.app')

@section('style')
<style>
    .usage-page {
        padding: 18px;
    }
    .usage-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 16px;
    }
    .usage-title h1 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        color: #172033;
    }
    .usage-title p {
        margin: 4px 0 0;
        color: #667085;
        font-size: 13px;
    }
    .usage-filter {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 14px;
        margin-bottom: 16px;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
    }
    .usage-filter-row {
        display: grid;
        grid-template-columns: repeat(5, minmax(140px, 1fr));
        gap: 12px;
        align-items: end;
    }
    .usage-filter label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #475467;
        margin-bottom: 5px;
    }
    .usage-filter .form-control {
        height: 38px;
        border-radius: 6px;
    }
    .usage-kpis {
        display: grid;
        grid-template-columns: repeat(4, minmax(140px, 1fr));
        gap: 12px;
        margin-bottom: 16px;
    }
    .usage-kpi {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 14px;
    }
    .usage-kpi span {
        color: #667085;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .usage-kpi strong {
        display: block;
        margin-top: 5px;
        font-size: 22px;
        line-height: 1.1;
        color: #101828;
    }
    .usage-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 12px;
    }
    .usage-tabs a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 36px;
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid #d0d5dd;
        color: #344054;
        background: #fff;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
    }
    .usage-tabs a.active {
        color: #fff;
        background: #1f4e79;
        border-color: #1f4e79;
    }
    .usage-table-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
    }
    .usage-table-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px;
        border-bottom: 1px solid #e5e7eb;
    }
    .usage-table-head h2 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: #172033;
    }
    .usage-table-wrap {
        overflow-x: auto;
    }
    .usage-table {
        width: 100%;
        min-width: 1040px;
        margin: 0;
        border-collapse: collapse;
    }
    .usage-table th {
        background: #f8fafc;
        color: #475467;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .03em;
        white-space: nowrap;
        padding: 10px 12px;
        border-bottom: 1px solid #e5e7eb;
    }
    .usage-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
        color: #1d2939;
        font-size: 13px;
    }
    .usage-table tbody tr:hover {
        background: #f9fbfd;
    }
    .vehicule-main {
        font-weight: 700;
        color: #101828;
        white-space: nowrap;
    }
    .vehicule-sub {
        color: #667085;
        font-size: 12px;
        margin-top: 2px;
    }
    .usage-badge {
        display: inline-flex;
        align-items: center;
        min-height: 24px;
        border-radius: 999px;
        padding: 3px 8px;
        font-size: 12px;
        font-weight: 700;
        background: #eef4ff;
        color: #1d4ed8;
        white-space: nowrap;
    }
    .usage-badge.warning {
        background: #fff7ed;
        color: #c2410c;
    }
    .usage-badge.muted {
        background: #f2f4f7;
        color: #667085;
    }
    .usage-number {
        text-align: right;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .usage-gap-pos { color: #047857; font-weight: 700; }
    .usage-gap-neg { color: #b42318; font-weight: 700; }
    .usage-empty {
        padding: 24px;
        text-align: center;
        color: #667085;
    }
    .usage-chart-panel {
        padding: 18px;
    }
    .usage-chart-wrap {
        position: relative;
        width: 100%;
        min-height: 480px;
    }
    .usage-chart-note {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 12px;
        color: #667085;
        font-size: 12px;
    }
    @media (max-width: 900px) {
        .usage-page { padding: 12px; }
        .usage-header { display: block; }
        .usage-filter-row { grid-template-columns: 1fr 1fr; }
        .usage-kpis { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 560px) {
        .usage-filter-row, .usage-kpis { grid-template-columns: 1fr; }
        .usage-tabs { flex-wrap: wrap; }
    }
</style>
@endsection

@section('content')
@php
    $formatKm = fn($value) => number_format((float) $value, 1, ',', ' ');
    $formatDuration = function ($seconds) {
        $seconds = (int) $seconds;
        if ($seconds <= 0) return '-';
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        return sprintf('%dh%02d', $hours, $minutes);
    };
    $formatMinute = function ($minute) {
        if ($minute === null || $minute === '') return '-';
        $minute = (int) $minute;
        $hour = intdiv($minute, 60) % 24;
        $min = $minute % 60;
        return sprintf('%02d:%02d', $hour, $min);
    };
    $queryDaily = request()->except('mode') + ['mode' => 'daily'];
    $queryMonthly = request()->except('mode') + ['mode' => 'monthly'];
    $queryChart = request()->except('mode') + ['mode' => 'chart'];
@endphp

<div class="usage-page">
    <div class="usage-header">
        <div class="usage-title">
            <h1>Utilisation des véhicules</h1>
            <p>Comparaison entre les transferts planifiés et les kilomètres réels Hermes.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('vehiculesusage') }}" class="usage-filter">
        <input type="hidden" name="mode" value="{{ $mode }}">
        <div class="usage-filter-row">
            <div>
                <label for="start_date">Date début</label>
                <input type="date" id="start_date" name="start_date" value="{{ $startDate->toDateString() }}" class="form-control">
            </div>
            <div>
                <label for="end_date">Date fin</label>
                <input type="date" id="end_date" name="end_date" value="{{ $endDate->toDateString() }}" class="form-control">
            </div>
            <div>
                <label for="vehicule_id">Véhicule</label>
                <select id="vehicule_id" name="vehicule_id" class="form-control">
                    <option value="">Tous les véhicules</option>
                    @foreach($allVehicles as $vehicule)
                        <option value="{{ $vehicule->id }}" {{ (string) $vehicleId === (string) $vehicule->id ? 'selected' : '' }}>
                            {{ $vehicule->plaka ?: 'Sans plaque' }} - {{ $vehicule->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary btn-block">Filtrer</button>
            </div>
            <div>
                <a href="{{ route('vehiculesusage') }}" class="btn btn-light btn-block">Réinitialiser</a>
            </div>
        </div>
    </form>

    <div class="usage-kpis">
        <div class="usage-kpi"><span>Véhicules</span><strong>{{ $totals['vehicles'] }}</strong></div>
        <div class="usage-kpi"><span>Transferts</span><strong>{{ number_format($totals['transfers'], 0, ',', ' ') }}</strong></div>
        <div class="usage-kpi"><span>Km Hermes</span><strong>{{ $formatKm($totals['real_km']) }}</strong></div>
        <div class="usage-kpi"><span>Durée Hermes</span><strong>{{ $formatDuration($totals['duration_sec']) }}</strong></div>
    </div>

    <div class="usage-tabs">
        <a href="{{ route('vehiculesusage', $queryDaily) }}" class="{{ $mode === 'daily' ? 'active' : '' }}">Vue journalière</a>
        <a href="{{ route('vehiculesusage', $queryMonthly) }}" class="{{ $mode === 'monthly' ? 'active' : '' }}">Vue mensuelle</a>
        <a href="{{ route('vehiculesusage', $queryChart) }}" class="{{ $mode === 'chart' ? 'active' : '' }}">Graphique mensuel</a>
    </div>

    <div class="usage-table-card">
        <div class="usage-table-head">
            <h2>{{ $mode === 'daily' ? 'Utilisation journalière' : ($mode === 'chart' ? 'Graphique mensuel' : 'Utilisation mensuelle') }}</h2>
            <span class="usage-badge muted">{{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}</span>
        </div>

        <div class="usage-table-wrap">
            @if($mode === 'chart')
                <div class="usage-chart-panel">
                    @if(count($chartData['labels']))
                        <div class="usage-chart-wrap">
                            <canvas id="monthlyUsageChart"></canvas>
                        </div>
                        <div class="usage-chart-note">
                            <span class="usage-badge">Km réels Hermes</span>
                            <span class="usage-badge muted">Km planifiés</span>
                            <span class="usage-badge warning">Jours utilisés</span>
                        </div>
                    @else
                        <div class="usage-empty">Aucune donnée graphique pour cette période.</div>
                    @endif
                </div>
            @elseif($mode === 'daily')
                <table class="usage-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Véhicule</th>
                            <th>Hermes</th>
                            <th class="usage-number">Transferts</th>
                            <th class="usage-number">Km planifiés</th>
                            <th class="usage-number">Km réels</th>
                            <th class="usage-number">Écart</th>
                            <th class="usage-number">Durée</th>
                            <th>Horaires Hermes</th>
                            <th>Service</th>
                            <th class="usage-number">Vitesse max</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyRows as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}</td>
                                <td>
                                    <div class="vehicule-main">{{ $row->plaka ?: 'Sans plaque' }}</div>
                                    <div class="vehicule-sub">{{ $row->vehicule_name }}</div>
                                </td>
                                <td>
                                    @if($row->has_hermes)
                                        <span class="usage-badge">Actif</span>
                                    @else
                                        <span class="usage-badge warning">Sans Hermes</span>
                                    @endif
                                </td>
                                <td class="usage-number">{{ $row->transfer_count }}</td>
                                <td class="usage-number">{{ $formatKm($row->planned_km) }}</td>
                                <td class="usage-number">{{ $formatKm($row->real_km) }}</td>
                                <td class="usage-number {{ $row->gap_km >= 0 ? 'usage-gap-pos' : 'usage-gap-neg' }}">{{ $formatKm($row->gap_km) }}</td>
                                <td class="usage-number">{{ $formatDuration($row->duration_sec) }}</td>
                                <td>{{ $formatMinute($row->begin_minute) }} - {{ $formatMinute($row->end_minute) }}</td>
                                <td>
                                    @if($row->first_service)
                                        {{ \Carbon\Carbon::parse($row->first_service)->format('H:i') }} - {{ \Carbon\Carbon::parse($row->last_service)->format('H:i') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="usage-number">{{ $row->max_speed ? number_format($row->max_speed, 0, ',', ' ') . ' km/h' : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="13" class="usage-empty">Aucune utilisation trouvée pour cette période.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="usage-table">
                    <thead>
                        <tr>
                            <th>Mois</th>
                            <th>Véhicule</th>
                            <th>Hermes</th>
                            <th class="usage-number">Jours utilisés</th>
                            <th class="usage-number">Jours service</th>
                            <th class="usage-number">Jours Hermes</th>
                            <th class="usage-number">Transferts</th>
                            <th class="usage-number">Km planifiés</th>
                            <th class="usage-number">Km réels</th>
                            <th class="usage-number">Écart</th>
                            <th class="usage-number">Moy./jour</th>
                            <th class="usage-number">Moy./transfert</th>
                            <th class="usage-number">Durée</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($monthlyRows as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->month . '-01')->format('m/Y') }}</td>
                                <td>
                                    <div class="vehicule-main">{{ $row->plaka ?: 'Sans plaque' }}</div>
                                    <div class="vehicule-sub">{{ $row->vehicule_name }}</div>
                                </td>
                                <td>
                                    @if($row->has_hermes)
                                        <span class="usage-badge">Actif</span>
                                    @else
                                        <span class="usage-badge warning">Sans Hermes</span>
                                    @endif
                                </td>
                                <td class="usage-number"><strong>{{ $row->active_days }}</strong></td>
                                <td class="usage-number">{{ $row->service_days ?? 0 }}</td>
                                <td class="usage-number">{{ $row->hermes_days ?? 0 }}</td>
                                <td class="usage-number">{{ $row->transfer_count }}</td>
                                <td class="usage-number">{{ $formatKm($row->planned_km) }}</td>
                                <td class="usage-number">{{ $formatKm($row->real_km) }}</td>
                                <td class="usage-number {{ $row->gap_km >= 0 ? 'usage-gap-pos' : 'usage-gap-neg' }}">{{ $formatKm($row->gap_km) }}</td>
                                <td class="usage-number">{{ $formatKm($row->avg_real_km_day) }}</td>
                                <td class="usage-number">{{ $formatKm($row->avg_real_km_transfer) }}</td>
                                <td class="usage-number">{{ $formatDuration($row->duration_sec) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="13" class="usage-empty">Aucune utilisation trouvée pour cette période.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection


@section('scripts')
@if($mode === 'chart')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    const labels = @json($chartData['labels']);
    if (!labels.length) return;

    const usedDays = @json($chartData['usedDays']);
    const realKm = @json($chartData['realKm']);
    const plannedKm = @json($chartData['plannedKm']);
    const canvas = document.getElementById('monthlyUsageChart');
    if (!canvas) return;

    new Chart(canvas, {
        data: {
            labels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Km réels Hermes',
                    data: realKm,
                    yAxisID: 'km',
                    backgroundColor: 'rgba(31, 78, 121, 0.78)',
                    borderColor: 'rgba(31, 78, 121, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    type: 'bar',
                    label: 'Km planifiés',
                    data: plannedKm,
                    yAxisID: 'km',
                    backgroundColor: 'rgba(102, 112, 133, 0.32)',
                    borderColor: 'rgba(102, 112, 133, 0.8)',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    type: 'line',
                    label: 'Jours utilisés',
                    data: usedDays,
                    yAxisID: 'days',
                    borderColor: 'rgba(194, 65, 12, 1)',
                    backgroundColor: 'rgba(194, 65, 12, 0.12)',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.25
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const value = Number(context.parsed.y || 0);
                            if (context.dataset.yAxisID === 'days') {
                                return context.dataset.label + ': ' + value + ' jours';
                            }
                            return context.dataset.label + ': ' + value.toLocaleString('fr-FR') + ' km';
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: { autoSkip: false, maxRotation: 55, minRotation: 35 },
                    grid: { display: false }
                },
                km: {
                    type: 'linear',
                    position: 'left',
                    beginAtZero: true,
                    title: { display: true, text: 'Kilomètres' }
                },
                days: {
                    type: 'linear',
                    position: 'right',
                    beginAtZero: true,
                    suggestedMax: 31,
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: 'Jours utilisés' },
                    ticks: { precision: 0 }
                }
            }
        }
    });
})();
</script>
@endif
@endsection
