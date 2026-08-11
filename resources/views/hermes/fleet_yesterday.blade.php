@extends('layouts.app')

@section('content')
<form method="GET" action="{{ route('hermes.fleet.day') }}" class="row g-2 align-items-end mb-3">
  <div class="col-auto">
    <label class="form-label mb-0">Date (Paris)</label>
    <input type="date" name="day" value="{{ $date }}" class="form-control form-control-sm">
  </div>
  <div class="col-auto">
    <button class="btn btn-sm btn-primary">Show</button>
    <a class="btn btn-sm btn-outline-secondary" href="{{ route('hermes.fleet.yesterday') }}">Hier</a>
  </div>
</form>
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h3 class="mb-0">Hermes • Fleet Yesterday ({{ $date }})</h3>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('hermes.index') }}">Vehicule List</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted">N. de Vehicule</div>
                <div class="fs-4">{{ $summary['vehicle_count'] }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted">Sans Data</div>
                <div class="fs-4">{{ $summary['unavailable_count'] }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted">Total KM (Hermes)</div>
                <div class="fs-4">{{ $summary['total_km'] }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted">Toplam conduite (Hermes)</div>
                <div class="fs-4">{{ $summary['total_hhmm'] }}</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Vehicule</th>
                    <th>Plaque</th>
                    <th>Hermes UID</th>
                    <th class="text-end">KM</th>
                    <th class="text-end">Conduite</th>
                    <th>Premier Conduite</th>
                    <th>Derniere Conduite</th>
                    <th class="text-end">Km/h Max</th>
                    <th>Statu</th>
                </tr>   
                </thead>
                <tbody>
                @foreach($results as $r)
                    @php
                        $v = $r['vehicule'];
                        $sec = is_numeric($r['driving_seconds']) ? (int)$r['driving_seconds'] : null;
                        $hhmm = $sec !== null ? gmdate('H:i', $sec) : null;
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $v['name'] }}</td>
                        <td>{{ $v['plaka'] }}</td>
                        <td class="text-muted">{{ $v['hermes_uid'] }}</td>
                        <td class="text-end">{{ $r['distance_km'] ?? '-' }}</td>
                        <td class="text-end">{{ $hhmm ?? '-' }}</td>
                        <td>{{ $r['first_move'] ?? '-' }}</td>
                        <td>{{ $r['last_move'] ?? '-' }}</td>
                        <td class="text-end">{{ $r['max_speed'] ?? '-' }}</td>
                        <td>
                            @if($r['data_unavailable'])
                                <span class="badge bg-warning text-dark">data_unavailable</span>
                            @else
                                <span class="badge bg-success">ok</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
