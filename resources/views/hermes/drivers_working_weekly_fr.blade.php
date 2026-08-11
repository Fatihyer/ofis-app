@extends('layouts.app')

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
@endphp

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <h3 class="mb-1">Rapport hebdomadaire — Temps de travail chauffeurs</h3>
      <div class="text-muted">
        Semaine : <strong>{{ $start }}</strong> → <strong>{{ $end }}</strong> (réf: {{ $ref }}) —
        Chauffeurs : {{ $summary['count'] }} —
        Activité Hermes : <strong>{{ $secToHHMM($summary['driving_sec']) }}</strong> —
        Planning : <strong>{{ $secToHHMM($summary['planning_sec'] ?? 0) }}</strong> —
        Mission : <strong>{{ $secToHHMM($summary['mission_sec'] ?? 0) }}</strong>
      </div>
    </div>

    <form method="GET" action="{{ route('hermes.drivers.working.weekly') }}" class="d-flex flex-wrap gap-2">
      <input type="date" name="day" value="{{ $ref }}" class="form-control form-control-sm" style="max-width:180px;">
      <button class="btn btn-primary btn-sm">Afficher</button>
      <a class="btn btn-outline-secondary btn-sm" href="{{ route('hermes.drivers.working.daily', ['day' => $ref]) }}">Jour</a>
      <a class="btn btn-outline-secondary btn-sm" href="{{ route('hermes.drivers.working.monthly', ['month' => \Carbon\Carbon::parse($ref)->format('Y-m')]) }}">Mois</a>
    </form>
  </div>

  @if(isset($comparisonEnd) && $comparisonEnd !== $end)
  <div class="alert alert-warning mb-3">
    <strong>Période comparée :</strong> le planning est compté jusqu'au <strong>{{ \Carbon\Carbon::parse($comparisonEnd)->format('d/m/Y') }}</strong>, car Hermes est archivé jusqu'à cette date. Les prestations futures du mois ne sont pas incluses dans ce total.
  </div>
@endif

  <div class="alert alert-info mb-3">
    <strong>Source chauffeur :</strong> Hermes fournit les temps du véhicule. Pour les vans sans tachygraphe, le chauffeur vient du planning interne. Pour fiabiliser le temps chauffeur, compléter les missions réelles.
  </div>

  @if(!empty($quality['items']))
    <div class="alert alert-warning mb-3">
      <strong>Données à compléter :</strong>
      @foreach($quality['items'] as $item)
        <span class="badge bg-dark ms-1">{{ $item['label'] }}: {{ $item['count'] }}</span>
      @endforeach
    </div>
  @endif

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:90px;">ID</th>
              <th>Chauffeur</th>
              <th style="width:90px;" class="text-center">Jours</th>
              <th style="width:120px;" class="text-end">Activité Hermes</th>
              <th style="width:120px;" class="text-end">Planning</th>
              <th style="width:120px;" class="text-end">Mission réel</th>
              <th style="width:120px;" class="text-end">Écart</th>
              <th style="width:90px;" class="text-center">Transferts</th>
              <th style="width:110px;" class="text-center">Début</th>
              <th style="width:110px;" class="text-center">Fin</th>
              <th style="width:140px;" class="text-center">Règle UE indicatif</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rows as $r)
              @php
                $drive = (int)($r->driving_sec ?? 0);
                $eu_ok = $drive <= 56*3600;
                $gapSec = (int)($r->time_gap_sec ?? 0);
                $gapClass = abs($gapSec) > 3600 ? 'text-danger fw-bold' : 'text-muted';
                $gapSign = $gapSec > 0 ? '+' : ($gapSec < 0 ? '-' : '');
              @endphp
              <tr>
                <td class="text-muted">#{{ $r->acente_id }}</td>
                <td class="fw-semibold">
                  {{ $r->driver_name ?: '—' }}
                  @if(($r->source ?? 'hermes') === 'planning')
                    <span class="badge bg-warning text-dark ms-1">Planning sans Hermes</span>
                  @endif
                  @foreach(($r->quality_warnings ?? []) as $warning)
                    <span class="badge bg-danger ms-1">{{ $warningLabels[$warning] ?? $warning }}</span>
                  @endforeach
                </td>
                <td class="text-center">{{ $r->days_count }}</td>
                <td class="text-end"><strong>{{ $secToHHMM($r->driving_sec) }}</strong></td>
                <td class="text-end">{{ $secToHHMM($r->planning_sec ?? 0) }}</td>
                <td class="text-end">{{ $secToHHMM($r->mission_sec ?? 0) }}</td>
                <td class="text-end {{ $gapClass }}">{{ $gapSign }}{{ $secToHHMM(abs($gapSec)) }}</td>
                <td class="text-center">{{ $r->transfer_count ?? 0 }}</td>
                <td class="text-center">{{ $minToTime($r->begin_minute) }}</td>
                <td class="text-center">{{ $minToTime($r->end_minute) }}</td>
                <td class="text-center">
                  @if($eu_ok)
                    <span class="badge bg-success">OK</span>
                  @else
                    <span class="badge bg-danger">Dépassement</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="11" class="text-center text-muted py-4">Aucune donnée sur cette semaine.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
