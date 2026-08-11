@extends('layouts.kaptan')

@section('title', '| Mes transferts')

@section('content')
@php
    $formatTime = function ($value) {
        return $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '-';
    };
    $surplaceMinutes = (int) (optional(\App\Models\Option::where('name', 'surplaceMinBefore')->first())->value ?? 15);
@endphp

<style>
.driver-transfer-page {
    max-width: 1120px;
    margin: 0 auto 32px;
    padding: 12px 10px;
    color: #172033;
}
.driver-transfer-head, .driver-transfer-card {
    background: #fff;
    border: 1px solid #dbe5f0;
    border-radius: 10px;
    box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
}
.driver-transfer-head {
    padding: 14px;
    margin-bottom: 12px;
}
.driver-transfer-head h1 {
    font-size: 24px;
    font-weight: 850;
    margin: 0;
    letter-spacing: 0;
}
.driver-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}
.driver-transfer-list {
    display: grid;
    gap: 10px;
}
.driver-transfer-card {
    overflow: hidden;
}
.driver-transfer-card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    padding: 12px 14px;
    border-bottom: 1px solid #edf2f7;
    background: #f8fafc;
}
.driver-transfer-title {
    min-width: 0;
}
.driver-transfer-title a {
    font-size: 17px;
    font-weight: 850;
    text-decoration: none;
}
.driver-transfer-meta {
    display: block;
    color: #64748b;
    font-size: 12px;
    margin-top: 2px;
}
.driver-status {
    border-radius: 999px;
    padding: 5px 9px;
    font-size: 12px;
    font-weight: 850;
    white-space: nowrap;
    background: #eef2ff;
    color: #3730a3;
}
.driver-transfer-body {
    padding: 14px;
}
.driver-info-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}
.driver-info-box {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fbfdff;
    padding: 10px;
    min-width: 0;
}
.driver-info-box span {
    display: block;
    color: #64748b;
    font-size: 11px;
    font-weight: 850;
    text-transform: uppercase;
}
.driver-info-box strong, .driver-info-box div {
    display: block;
    margin-top: 4px;
    overflow-wrap: anywhere;
}
.driver-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}
.driver-actions .btn {
    font-weight: 800;
    border-radius: 8px;
}
.driver-detail-text {
    color: #475569;
    white-space: pre-line;
}
@media (max-width: 767px) {
    .driver-transfer-page { padding: 8px; }
    .driver-transfer-head h1 { font-size: 21px; }
    .driver-transfer-card-head { display: block; }
    .driver-status { display: inline-flex; margin-top: 8px; }
    .driver-info-grid { grid-template-columns: 1fr; }
    .driver-actions .btn { width: 100%; min-height: 44px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
}
</style>

<div class="driver-transfer-page">
    <section class="driver-transfer-head">
        <h1>Mes transferts</h1>
        <div class="text-muted mt-1">Liste des missions visibles selon votre planning chauffeur.</div>

        @if (session('status'))
            <div class="alert alert-success mt-3 mb-0">{{ session('status') }}</div>
        @endif

        <div class="driver-filter-row">
            <form method="get" class="d-inline">
                <input type="hidden" name="dateOption" value="yesterday">
                <button type="submit" class="btn btn-outline-secondary btn-sm {{ request('dateOption') === 'yesterday' ? 'active' : '' }}">Hier</button>
            </form>
            <form method="get" class="d-inline">
                <input type="hidden" name="dateOption" value="today">
                <button type="submit" class="btn btn-primary btn-sm {{ request('dateOption', 'today') === 'today' ? 'active' : '' }}">Aujourd'hui</button>
            </form>
            <form method="get" class="d-inline">
                <input type="hidden" name="dateOption" value="tomorrow">
                <button type="submit" class="btn btn-outline-secondary btn-sm {{ request('dateOption') === 'tomorrow' ? 'active' : '' }}">Demain</button>
            </form>
            @if(request()->has('dateOption'))
                <a href="{{ route('ev') }}" class="btn btn-link btn-sm">Réinitialiser</a>
            @endif
        </div>
    </section>

    <div class="driver-transfer-list">
        @forelse($transfers as $transfer)
            @php
                $clients = optional($transfer->post)->client ?: collect();
                $clientLines = collect($clients)->map(function ($c) {
                    return trim(($c->name ?? '').' '.($c->surname ?? '').' '.($c->tel ?? ''));
                })->filter()->implode("\n");
                $mission = $transfer->missionr;
                $start = \Carbon\Carbon::parse($transfer->start_date);
                $end = \Carbon\Carbon::parse($transfer->end_date);
                $surplace = $start->copy()->subMinutes($surplaceMinutes);
                $ofisStart = $transfer->ofis_start ? \Carbon\Carbon::parse($transfer->ofis_start) : null;
                $confirmLabel = $transfer->driver_app_confirmed_at
                    ? 'Confirmé '.\Carbon\Carbon::parse($transfer->driver_app_confirmed_at)->format('H:i')
                    : 'À confirmer';
            @endphp
            <article class="driver-transfer-card">
                <div class="driver-transfer-card-head">
                    <div class="driver-transfer-title">
                        <a href="{{ route('showdriver', $transfer->id) }}">Transfert #{{ $transfer->id }}</a>
                        <span class="driver-transfer-meta">Sur place {{ $surplace->format('d/m/Y H:i') }} · Client {{ $start->format('H:i') }} · {{ optional($transfer->servicetype)->name ?: 'Service' }}</span>
                    </div>
                    <span class="driver-status">{{ optional($transfer->status)->name ?: 'Statut' }}</span>
                </div>
                <div class="driver-transfer-body">
                    <div class="driver-info-grid">
                        <div class="driver-info-box"><span>Départ bureau</span><strong>{{ $ofisStart ? $ofisStart->format('H:i') : '-' }}</strong></div>
                        <div class="driver-info-box"><span>Sur place client</span><strong>{{ $surplace->format('H:i') }}</strong></div>
                        <div class="driver-info-box"><span>Prise en charge</span><strong>{{ $start->format('H:i') }}</strong></div>
                        <div class="driver-info-box"><span>Fin prévue</span><strong>{{ $end->format($end->isSameDay($start) ? 'H:i' : 'd/m H:i') }}</strong></div>
                        <div class="driver-info-box"><span>Départ</span><strong>{{ $transfer->from ?: '-' }}</strong></div>
                        <div class="driver-info-box"><span>Arrivée</span><strong>{{ $transfer->target ?: '-' }}</strong></div>
                        <div class="driver-info-box"><span>Confirmation</span><strong>{{ $confirmLabel }}</strong></div>
                        <div class="driver-info-box"><span>Véhicule</span><div>{{ optional($transfer->vehicule)->name ?: '-' }} @if(optional($transfer->vehicule)->plaka)<small class="text-muted d-block">{{ $transfer->vehicule->plaka }}</small>@endif</div></div>
                        <div class="driver-info-box"><span>Mission réelle</span><div>@if($mission) En route {{ $mission->hareket ? date('H:i', strtotime($mission->hareket)) : '-' }} @else Non démarrée @endif</div></div>
                        <div class="driver-info-box"><span>Clients / notes</span><div class="driver-detail-text">{{ $clientLines ?: 'Aucun contact' }}@if($transfer->comments)
{{ $transfer->comments }}@endif @if($transfer->dcomments)
{{ $transfer->dcomments }}@endif</div></div>
                    </div>
                    <div class="driver-actions">
                        <a href="{{ route('showdriver', $transfer->id) }}" class="btn btn-primary btn-sm"><i class="fa fa-eye"></i> Voir le transfert</a>
                        @if ($transfer->mission)
                            <a href="{{ route('mission', $transfer->id) }}" class="btn btn-danger btn-sm"><i class="fa fa-play"></i> Suivi mission</a>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="driver-transfer-card"><div class="driver-transfer-body text-center text-muted">Aucun transfert à afficher.</div></div>
        @endforelse
    </div>

    <div class="mt-3">
        {{ $transfers->appends(request()->except('page'))->links('vendor/pagination/bootstrap-4') }}
    </div>
</div>
@endsection
