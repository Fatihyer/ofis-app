@extends('layouts.app')

@section('title', '| Statistiques dossiers')

@section('content')
<style>
.stats-page { max-width: 1180px; margin: 0 auto; color: #172033; }
.stats-head { display: flex; justify-content: space-between; gap: 12px; align-items: end; flex-wrap: wrap; margin-bottom: 14px; }
.stats-head h1 { font-size: 24px; margin: 0; font-weight: 850; }
.stats-card { border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; box-shadow: 0 1px 2px rgba(15,23,42,.05); }
.stats-filter { display: flex; gap: 8px; flex-wrap: wrap; align-items: end; padding: 12px; }
.stats-filter label { font-size: 12px; color: #64748b; font-weight: 750; margin-bottom: 3px; }
.stats-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 10px; margin: 14px 0; }
.stats-tile { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; background: #f8fafc; }
.stats-tile small { display: block; color: #64748b; font-weight: 750; margin-bottom: 5px; }
.stats-tile strong { font-size: 24px; line-height: 1; }
.stats-table th { background: #f1f5f9; color: #334155; font-weight: 800; }
.stats-table td, .stats-table th { vertical-align: middle; }
.user-name { font-weight: 800; }
@media (max-width: 767.98px) { .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .stats-filter .form-control { min-width: 150px; } }
</style>

<div class="stats-page">
    <div class="stats-head">
        <div>
            <h1>Statistiques dossiers</h1>
            <div class="text-muted">Dossiers ouverts par utilisateur, hors services congé.</div>
        </div>
    </div>

    <div class="stats-card mb-3">
        <form method="GET" action="{{ route('posts.userFileStats') }}" class="stats-filter">
            <div>
                <label>Début</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="form-control">
            </div>
            <div>
                <label>Fin</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="form-control">
            </div>
            <button class="btn btn-primary" type="submit"><i class="fa fa-filter"></i> Filtrer</button>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stats-tile"><small>Dossiers</small><strong>{{ $totals['file_count'] }}</strong></div>
        <div class="stats-tile"><small>Services total</small><strong>{{ $totals['service_count'] }}</strong></div>
        <div class="stats-tile"><small>Transferts</small><strong>{{ $totals['transfer_count'] }}</strong></div>
        <div class="stats-tile"><small>Hôtels</small><strong>{{ $totals['hotel_count'] }}</strong></div>
        <div class="stats-tile"><small>Autres</small><strong>{{ $totals['other_count'] }}</strong></div>
        <div class="stats-tile"><small>Stocks</small><strong>{{ $totals['stock_count'] }}</strong></div>
    </div>

    <div class="stats-card table-responsive">
        <table class="table table-hover mb-0 stats-table">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th class="text-end">Dossiers ouverts</th>
                    <th class="text-end">Services total</th>
                    <th class="text-end">Transferts</th>
                    <th class="text-end">Hôtels</th>
                    <th class="text-end">Autres</th>
                    <th class="text-end">Stocks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stats as $row)
                    <tr>
                        <td><span class="user-name">{{ $row->user_name }}</span></td>
                        <td class="text-end"><strong>{{ $row->file_count }}</strong></td>
                        <td class="text-end"><strong>{{ $row->service_count }}</strong></td>
                        <td class="text-end">{{ $row->transfer_count }}</td>
                        <td class="text-end">{{ $row->hotel_count }}</td>
                        <td class="text-end">{{ $row->other_count }}</td>
                        <td class="text-end">{{ $row->stock_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucune donnée pour cette période.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="text-muted small mt-2">Services congé exclus: {{ implode(', ', $congeIds) ?: '-' }}</div>
</div>
@endsection
