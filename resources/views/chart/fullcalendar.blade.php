@extends('layouts.app')

@section('style')
<style>
.monthly-page{background:#f4f7fb;min-height:calc(100vh - 90px);padding:18px}.monthly-hero{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:16px}.monthly-title h1{margin:0;color:#0f172a;font-size:27px;font-weight:900}.monthly-title p{margin:4px 0 0;color:#64748b;font-weight:700}.month-nav{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.month-current{min-width:190px;text-align:center;background:#0f172a;color:#fff;border-radius:9px;padding:8px 14px;font-weight:900;text-transform:capitalize}
.filter-card,.panel,.stat-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 8px 22px rgba(15,23,42,.05)}.filter-card{padding:12px;margin-bottom:15px}.filter-row{display:grid;grid-template-columns:180px minmax(180px,1fr) minmax(180px,1fr) auto;gap:10px;align-items:end}.filter-card label{display:block;color:#475569;font-size:12px;font-weight:900;margin-bottom:4px}
.stats-grid{display:grid;grid-template-columns:repeat(6,minmax(130px,1fr));gap:11px;margin-bottom:15px}.stat-card{padding:14px}.stat-card span{display:block;color:#64748b;font-size:12px;font-weight:850;text-transform:uppercase}.stat-card strong{display:block;color:#0f172a;font-size:25px;line-height:1.15;margin-top:4px}.stat-card.alert-card{border-color:#fca5a5;background:#fff7f7}.stat-card.alert-card strong{color:#b91c1c}.stat-card.success-card{border-color:#86efac;background:#f0fdf4}.stat-card.success-card strong{color:#15803d}
.panel{margin-bottom:15px;overflow:hidden}.panel-head{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:13px 15px;border-bottom:1px solid #e2e8f0}.panel-head h2{font-size:16px;font-weight:900;color:#0f172a;margin:0}.panel-head small{color:#64748b;font-weight:700}.workload{display:grid;grid-template-columns:repeat(31,minmax(28px,1fr));gap:5px;align-items:end;overflow-x:auto;padding:15px;min-height:205px}.workday{min-width:28px;text-align:center;text-decoration:none}.workbar-wrap{height:130px;display:flex;align-items:flex-end;justify-content:center;background:#f8fafc;border-radius:6px;overflow:hidden}.workbar{width:100%;min-height:3px;background:linear-gradient(180deg,#2563eb,#1d4ed8);border-radius:5px 5px 0 0}.workday.busy .workbar{background:linear-gradient(180deg,#f59e0b,#d97706)}.workday.very-busy .workbar{background:linear-gradient(180deg,#ef4444,#b91c1c)}.workday.today .workbar-wrap{outline:3px solid #38bdf8}.workcount{display:block;font-size:11px;font-weight:900;color:#0f172a;margin:4px 0 1px}.workdate{display:block;font-size:10px;color:#64748b;font-weight:800}.workday:hover .workbar-wrap{filter:brightness(.94)}
.table-tools{display:flex;gap:8px;align-items:center}.table-tools input{width:260px}.monthly-table{margin:0}.monthly-table th{background:#f8fafc;color:#475569;font-size:11px;text-transform:uppercase;white-space:nowrap}.monthly-table td{vertical-align:middle;color:#1e293b}.file-title{font-weight:900;color:#0f172a}.route-copy{max-width:330px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.status-pill{display:inline-flex;border-radius:999px;padding:4px 9px;font-size:11px;font-weight:900;background:#e2e8f0;color:#334155}.status-confirmed{background:#dcfce7;color:#166534}.status-canceled{background:#fee2e2;color:#991b1b}.status-waiting{background:#fef3c7;color:#92400e}.assignment-warning{display:inline-flex;align-items:center;gap:4px;color:#b91c1c;font-size:11px;font-weight:900}.empty-state{text-align:center;padding:40px 15px;color:#64748b;font-weight:800}
@media(max-width:1200px){.stats-grid{grid-template-columns:repeat(3,1fr)}}@media(max-width:800px){.monthly-page{padding:10px}.monthly-hero{display:block}.month-nav{margin-top:12px}.filter-row{grid-template-columns:1fr}.stats-grid{grid-template-columns:repeat(2,1fr)}.table-tools{display:block}.table-tools input{width:100%;margin-top:8px}.route-copy{max-width:190px}}
</style>
@endsection

@section('content')
@php
    $queryBase = array_filter([
        'agency_id' => $selectedAgencyId,
        'status_id' => $selectedStatusId,
    ]);
    $statusClass = function ($status) {
        return match (strtolower((string) $status)) {
            'confirmed', 'invoiced', 'payed', 'checked' => 'status-confirmed',
            'canceled' => 'status-canceled',
            'waiting', 'sent', 'modified' => 'status-waiting',
            default => '',
        };
    };
@endphp
<div class="monthly-page">
    <div class="monthly-hero">
        <div class="monthly-title">
            <h1>Planning mensuel des opérations</h1>
            <p>Vue complète des dossiers et transferts du mois</p>
        </div>
        <div class="month-nav">
            <a class="btn btn-outline-dark btn-sm" href="{{ route('charts', array_merge($queryBase, ['month' => $month->copy()->subMonth()->format('Y-m')])) }}"><i class="fa fa-chevron-left"></i></a>
            <div class="month-current">{{ $month->locale('fr')->isoFormat('MMMM YYYY') }}</div>
            <a class="btn btn-outline-dark btn-sm" href="{{ route('charts', array_merge($queryBase, ['month' => $month->copy()->addMonth()->format('Y-m')])) }}"><i class="fa fa-chevron-right"></i></a>
            <a class="btn btn-primary btn-sm" href="{{ route('charts', array_merge($queryBase, ['month' => now()->format('Y-m')])) }}">Ce mois</a>
        </div>
    </div>

    <form method="GET" action="{{ route('charts') }}" class="filter-card">
        <div class="filter-row">
            <div>
                <label for="month">Mois</label>
                <input type="month" class="form-control form-control-sm" id="month" name="month" value="{{ $month->format('Y-m') }}">
            </div>
            <div>
                <label for="agency_id">Agence</label>
                <select class="form-control form-control-sm" id="agency_id" name="agency_id">
                    <option value="">Toutes les agences</option>
                    @foreach($agencies as $agency)
                        <option value="{{ $agency->id }}" @selected($selectedAgencyId === (int) $agency->id)>{{ $agency->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status_id">Statut</label>
                <select class="form-control form-control-sm" id="status_id" name="status_id">
                    <option value="">Tous les statuts</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->id }}" @selected($selectedStatusId === (int) $status->id)>{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-dark btn-sm" type="submit"><i class="fa fa-filter"></i> Filtrer</button>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('charts', ['month' => $month->format('Y-m')]) }}">Effacer</a>
            </div>
        </div>
    </form>

    <div class="stats-grid">
        <div class="stat-card"><span>Dossiers</span><strong>{{ number_format($stats['files'], 0, ',', ' ') }}</strong></div>
        <div class="stat-card"><span>Transferts</span><strong>{{ number_format($stats['transfers'], 0, ',', ' ') }}</strong></div>
        <div class="stat-card"><span>Passagers</span><strong>{{ number_format($stats['pax'], 0, ',', ' ') }}</strong></div>
        <div class="stat-card alert-card"><span>À affecter</span><strong>{{ number_format($stats['unassigned'], 0, ',', ' ') }}</strong></div>
        <div class="stat-card success-card"><span>Confirmés</span><strong>{{ number_format($stats['confirmed'], 0, ',', ' ') }}</strong></div>
        <div class="stat-card"><span>Annulés</span><strong>{{ number_format($stats['cancelled'], 0, ',', ' ') }}</strong></div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <div>
                <h2>Charge de travail par jour</h2>
                <small>Nombre de transferts; cliquez sur un jour pour ouvrir le planning journalier</small>
            </div>
        </div>
        <div class="workload">
            @foreach($dailyWorkload as $day)
                @php
                    $height = $day['transfers'] > 0 ? max(8, round(($day['transfers'] / $maxDailyTransfers) * 100)) : 2;
                    $loadClass = $day['transfers'] >= 25 ? 'very-busy' : ($day['transfers'] >= 15 ? 'busy' : '');
                @endphp
                <a class="workday {{ $loadClass }} {{ $day['is_today'] ? 'today' : '' }}" href="{{ route('day', ['start_date' => $day['date']]) }}" title="{{ $day['files'] }} dossiers · {{ $day['transfers'] }} transferts">
                    <div class="workbar-wrap"><div class="workbar" style="height:{{ $height }}%"></div></div>
                    <span class="workcount">{{ $day['transfers'] }}</span>
                    <span class="workdate">{{ $day['weekday'] }} {{ $day['day'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <div>
                <h2>Dossiers du mois</h2>
                <small>{{ $files->count() }} dossier(s) correspondant aux filtres</small>
            </div>
            <div class="table-tools">
                <input type="search" class="form-control form-control-sm" id="monthlySearch" placeholder="Rechercher dossier, agence, trajet…">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover monthly-table" id="monthlyFilesTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Dossier</th>
                        <th>Agence</th>
                        <th>Trajet</th>
                        <th>Pax</th>
                        <th>Transferts</th>
                        <th>Affectation</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($files as $item)
                        @php
                            $firstTransfer = $item->transfer->first();
                            $lastTransfer = $item->transfer->last();
                            $routeFrom = $firstTransfer?->from ?: '-';
                            $routeTo = $lastTransfer?->target ?: '-';
                            $unassignedCount = $item->transfer->filter(fn ($transfer) => !$transfer->driver_id || !$transfer->vehicule_id)->count();
                        @endphp
                        <tr>
                            <td class="text-nowrap">
                                <strong>{{ optional($item->start_date ? \Carbon\Carbon::parse($item->start_date) : null)?->format('d/m') ?? '-' }}</strong>
                                @if($item->end_date && \Carbon\Carbon::parse($item->end_date)->toDateString() !== \Carbon\Carbon::parse($item->start_date)->toDateString())
                                    <span class="text-muted">→ {{ \Carbon\Carbon::parse($item->end_date)->format('d/m') }}</span>
                                @endif
                            </td>
                            <td><div class="file-title">#{{ $item->id }} · {{ $item->title ?: 'Sans titre' }}</div></td>
                            <td>{{ $item->acente?->name ?: '-' }}</td>
                            <td><div class="route-copy" title="{{ $routeFrom }} → {{ $routeTo }}">{{ $routeFrom }} → {{ $routeTo }}</div></td>
                            <td><strong>{{ $item->pax ?: '-' }}</strong></td>
                            <td><span class="badge bg-primary">{{ $item->transfer->count() }}</span></td>
                            <td>
                                @if($unassignedCount)
                                    <span class="assignment-warning"><i class="fa fa-exclamation-triangle"></i> {{ $unassignedCount }} à affecter</span>
                                @else
                                    <span class="text-success fw-bold"><i class="fa fa-check"></i> Complet</span>
                                @endif
                            </td>
                            <td><span class="status-pill {{ $statusClass($item->status?->name) }}">{{ $item->status?->name ?: '-' }}</span></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="{{ url('posts/' . $item->id) }}">Ouvrir</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="empty-state">Aucun dossier pour ce mois et ces filtres.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('footer')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('monthlySearch');
    const rows = Array.from(document.querySelectorAll('#monthlyFilesTable tbody tr'));
    if (!input) return;

    input.addEventListener('input', function () {
        const needle = input.value.toLocaleLowerCase('fr').trim();
        rows.forEach(row => {
            row.style.display = !needle || row.textContent.toLocaleLowerCase('fr').includes(needle) ? '' : 'none';
        });
    });
});
</script>
@endsection
