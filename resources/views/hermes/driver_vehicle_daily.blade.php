@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="mb-1">Hermes — Rapport quotidien véhicule / chauffeur</h3>
            <div class="text-muted">Date: <strong>{{ $date }}</strong> — Lignes: {{ $summary['count'] }} — Hermes: {{ $summary['total_km'] }} km / {{ $summary['total_hhmm'] }} — Planning: {{ $summary['planning_hhmm'] }} — Mission: {{ $summary['mission_hhmm'] }}</div>
        </div>

        <form method="GET" action="{{ route('hermes.driver_vehicle_daily') }}" class="d-flex gap-2">
            <input type="date" name="day" value="{{ $date }}" class="form-control" style="max-width: 170px;">
            <button class="btn btn-primary">Afficher</button>
            <a class="btn btn-outline-secondary" href="{{ route('hermes.driver_vehicle_daily', ['day' => \Carbon\Carbon::yesterday('Europe/Paris')->toDateString()]) }}">Hier</a>
            <a class="btn btn-outline-secondary" href="{{ route('hermes.driver_vehicle_daily', ['day' => \Carbon\Carbon::today('Europe/Paris')->toDateString()]) }}">Aujourd'hui</a>
        </form>
    </div>
  <div class="alert alert-info mb-3">
    <strong>À savoir :</strong> Hermes fournit les données du véhicule. Pour les vans sans tachygraphe, le chauffeur est déduit du planning interne. Les heures fiables doivent être contrôlées avec Planning et Mission réel.
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
                            <th style="width:70px;">#</th>
                            <th>Véhicule</th>
                            <th style="width:140px;">Plaque</th>
                            <th>Chauffeur</th>
                            <th style="width:120px;" class="text-end">Km</th>
                            <th style="width:120px;" class="text-end">Hermes</th>
                            <th style="width:120px;" class="text-end">Planning</th>
                            <th style="width:120px;" class="text-end">Mission</th>
                            <th style="width:120px;" class="text-end">Écart</th>
                            <th style="width:110px;" class="text-center">Début</th>
                            <th style="width:110px;" class="text-center">Fin</th>
                            <th style="width:110px;" class="text-end">Max</th>
                            <th style="width:170px;">Mis à jour</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $r)
                        @php
                            $hasDriver = !empty($r->acente_id);
                            $km = is_numeric($r->distance_km) ? number_format((float)$r->distance_km, 2, '.', ' ') : '-';
                            $hhmm = $secToHHMM($r->driving_sec) ?? '-';
                            $planning = $secToHHMM($r->planning_sec) ?? '-';
                            $mission = $secToHHMM($r->mission_sec) ?? '-';
                            $gap = $secToHHMM(abs($r->time_gap_sec ?? 0)) ?? '-';
                            $gapClass = abs((int)($r->time_gap_sec ?? 0)) > 3600 ? 'text-danger fw-bold' : 'text-muted';
                            $gapSign = ((int)($r->time_gap_sec ?? 0)) > 0 ? '+' : (((int)($r->time_gap_sec ?? 0)) < 0 ? '-' : '');
                            $warningLabels = [
                                'chauffeur_manquant' => 'Chauffeur manquant',
                                'planning_manquant' => 'Planning manquant',
                                'mission_non_renseignee' => 'Mission non renseignée',
                                'ecart_important' => 'Écart > 1h',
                                'planning_sans_hermes' => 'Planning sans Hermes',
                                'plusieurs_transferts_sans_mission' => 'Plusieurs transferts sans mission',
                            ];
                            $first = $minToTime($r->begin_minute) ?? '-';
                            $last = $minToTime($r->end_minute) ?? '-';
                            $max = is_numeric($r->max_speed) ? (int)$r->max_speed : '-';
                        @endphp
                        <tr>
                            <td class="text-muted">#{{ $r->vehicule_id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $r->vehicule_name ?? '—' }}</div>
                            </td>
                            <td><span class="badge bg-dark">{{ $r->vehicule_plaka ?? '—' }}</span></td>
                            <td>
                                @if($hasDriver)
                                    <span class="badge bg-success">#{{ $r->acente_id }}</span>
                                    <span class="ms-1">{{ $r->driver_name ?? '—' }}</span>
                                @else
                                    <span class="badge bg-warning text-dark">Sans chauffeur / location</span>
                                @endif
                                @foreach(($r->quality_warnings ?? []) as $warning)
                                    <span class="badge bg-danger ms-1">{{ $warningLabels[$warning] ?? $warning }}</span>
                                @endforeach
                            </td>
                            <td class="text-end">{{ $km }}</td>
                            <td class="text-end">{{ $hhmm }}</td>
                            <td class="text-end">{{ $planning }}</td>
                            <td class="text-end">{{ $mission }}</td>
                            <td class="text-end {{ $gapClass }}">{{ $gapSign }}{{ $gap }}</td>
                            <td class="text-center">{{ $first }}</td>
                            <td class="text-center">{{ $last }}</td>
                            <td class="text-end">{{ $max }}</td>
                            <td class="text-muted">{{ $r->updated_at }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center text-muted py-4">
                                Aucune donnée pour cette date.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
