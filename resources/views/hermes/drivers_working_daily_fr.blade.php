@extends('layouts.app')

@section('style')
<style>
  .driver-hours-page { background:#f8fafc; min-height:calc(100vh - 90px); padding:16px; }
  .driver-hours-head { display:flex; justify-content:space-between; gap:14px; align-items:flex-start; margin-bottom:14px; }
  .driver-hours-head h3 { margin:0; font-size:24px; font-weight:850; color:#0f172a; }
  .driver-hours-head .muted { color:#64748b; font-weight:700; }
  .hours-stats { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:10px; margin-bottom:14px; }
  .hours-stat { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:12px; }
  .hours-stat small { display:block; color:#64748b; text-transform:uppercase; font-size:11px; font-weight:850; }
  .hours-stat strong { display:block; color:#0f172a; font-size:22px; margin-top:6px; }
  .quality-row { display:flex; flex-wrap:wrap; gap:7px; margin-bottom:12px; }
  .quality-chip { border:1px solid #fbbf24; background:#fffbeb; color:#92400e; border-radius:999px; padding:5px 9px; font-weight:800; font-size:12px; }
  .hours-card { background:#fff; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 8px 22px rgba(15,23,42,.06); overflow:hidden; }
  .hours-table th { font-size:12px; text-transform:uppercase; color:#475569; white-space:nowrap; }
  .hours-table td { vertical-align:middle; }
  .source-badge { border-radius:999px; padding:4px 8px; font-size:12px; font-weight:850; }
  .source-hermes { background:#dcfce7; color:#166534; }
  .source-planning { background:#fee2e2; color:#991b1b; }
  .warning-badge { display:inline-block; margin:2px 3px 2px 0; border-radius:999px; padding:3px 7px; background:#fff7ed; color:#9a3412; border:1px solid #fed7aa; font-size:11px; font-weight:800; }
  .gap-plus { color:#166534; font-weight:850; }
  .gap-minus { color:#b91c1c; font-weight:850; }
  @media (max-width: 991.98px) { .driver-hours-head { display:block; } .driver-hours-head form { margin-top:10px; } .hours-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } }
  @media (max-width: 575.98px) { .driver-hours-page { padding:10px; } .hours-stats { grid-template-columns:1fr; } }
</style>
@endsection

@section('content')
@php
  $secToHHMM = $fmt['secToHHMM'];
  $minToTime = $fmt['minToTime'];
  $warningLabels = [
    'chauffeur_manquant' => 'Chauffeur manquant',
    'planning_manquant' => 'Hermes sans planning',
    'mission_non_renseignee' => 'Mission non renseignée',
    'ecart_important' => 'Écart > 1h',
    'planning_sans_hermes' => 'Planning sans Hermes',
    'plusieurs_transferts_sans_mission' => 'Plusieurs transferts sans mission',
    'activite_hermes_tres_longue' => 'Activité Hermes > 14h',
    'activite_superieure_amplitude' => 'Activité > amplitude',
  ];
  $presenceTotal = $rows->sum(fn($r) => (int)($r->presence_sec ?? 0));
@endphp

<div class="driver-hours-page">
  <div class="driver-hours-head">
    <div>
      <h3>Temps de travail chauffeurs</h3>
      <div class="muted">Rapport journalier Hermes + planning système · {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</div>
    </div>

    <form method="GET" action="{{ route('hermes.drivers.working.daily') }}" class="d-flex flex-wrap gap-2">
      <input type="date" name="day" value="{{ $date }}" class="form-control form-control-sm" style="max-width:180px;">
      <button class="btn btn-primary btn-sm">Afficher</button>
      <a class="btn btn-outline-secondary btn-sm" href="{{ route('hermes.drivers.working.daily', ['day' => \Carbon\Carbon::yesterday('Europe/Paris')->toDateString()]) }}">Hier</a>
      <a class="btn btn-outline-secondary btn-sm" href="{{ route('hermes.drivers.working.weekly', ['day' => $date]) }}">Semaine</a>
      <a class="btn btn-outline-secondary btn-sm" href="{{ route('hermes.drivers.working.monthly', ['month' => \Carbon\Carbon::parse($date)->format('Y-m')]) }}">Mois</a>
    </form>
  </div>

  <div class="hours-stats">
    <div class="hours-stat"><small>Chauffeurs</small><strong>{{ $summary['count'] }}</strong></div>
    <div class="hours-stat"><small>Amplitude Hermes</small><strong>{{ $secToHHMM($presenceTotal) }}</strong></div>
    <div class="hours-stat"><small>Activité Hermes</small><strong>{{ $secToHHMM($summary['driving_sec']) }}</strong></div>
    <div class="hours-stat"><small>Planifié système</small><strong>{{ $secToHHMM($summary['planning_sec'] ?? 0) }}</strong></div>
    <div class="hours-stat"><small>Mission réelle</small><strong>{{ $secToHHMM($summary['mission_sec'] ?? 0) }}</strong></div>
  </div>

  @if(!empty($quality['items']))
    <div class="quality-row">
      @foreach($quality['items'] as $item)
        <span class="quality-chip">{{ $item['label'] }}: {{ $item['count'] }}</span>
      @endforeach
    </div>
  @endif

  <div class="hours-card">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle hours-table">
        <thead class="table-light">
          <tr>
            <th style="width:80px;">ID</th>
            <th>Chauffeur</th>
            <th class="text-center">Source</th>
            <th class="text-end">Amplitude</th>
            <th class="text-end">Activité Hermes</th>
            <th class="text-end">Planifié</th>
            <th class="text-end">Mission</th>
            <th class="text-end">Écart</th>
            <th class="text-center">Début</th>
            <th class="text-center">Fin</th>
            <th class="text-center">Règle UE indicatif</th>
            <th>Alertes</th>
            <th>MAJ</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
            @php
              $drive = (int)($r->driving_sec ?? 0);
              $planning = (int)($r->planning_sec ?? 0);
              $gap = (int)($r->time_gap_sec ?? ($drive - $planning));
              $eu_ok = $drive <= 9*3600;
              $source = $r->source ?? 'hermes';
            @endphp
            <tr>
              <td class="text-muted">#{{ $r->acente_id }}</td>
              <td class="fw-semibold">{{ $r->driver_name ?: '—' }}</td>
              <td class="text-center"><span class="source-badge {{ $source === 'planning' ? 'source-planning' : 'source-hermes' }}">{{ $source === 'planning' ? 'Planning' : 'Hermes' }}</span></td>
              <td class="text-end">{{ isset($r->presence_sec) ? $secToHHMM($r->presence_sec) : '—' }}</td>
              <td class="text-end"><strong>{{ $secToHHMM($r->driving_sec) }}</strong></td>
              <td class="text-end">{{ $secToHHMM($r->planning_sec ?? 0) }} <span class="text-muted small">({{ (int)($r->transfer_count ?? 0) }})</span></td>
              <td class="text-end">{{ $secToHHMM($r->mission_sec ?? 0) }}</td>
              <td class="text-end"><span class="{{ $gap >= 0 ? 'gap-plus' : 'gap-minus' }}">{{ $gap >= 0 ? '+' : '-' }}{{ $secToHHMM(abs($gap)) }}</span></td>
              <td class="text-center">{{ $minToTime($r->begin_minute) }}</td>
              <td class="text-center">{{ $minToTime($r->end_minute) }}</td>
              <td class="text-center">
                @if($eu_ok)
                  <span class="badge bg-success">OK</span>
                @else
                  <span class="badge bg-danger">Dépassement</span>
                @endif
              </td>
              <td>
                @forelse(($r->quality_warnings ?? []) as $warning)
                  <span class="warning-badge">{{ $warningLabels[$warning] ?? $warning }}</span>
                @empty
                  <span class="text-muted small">—</span>
                @endforelse
              </td>
              <td class="text-muted small">{{ $r->updated_at ?: '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="13" class="text-center text-muted py-4">Aucune donnée pour cette date.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
