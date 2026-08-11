@extends('layouts.app')

@section('content')
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">Hermes • Fleet DB ({{ $date }})</h3>
            <div class="text-muted small">
                Kaynak: <code>hermes_daily_stats</code>
            </div>
        </div>

        <form method="GET" action="{{ route('hermes.fleet.db') }}" class="d-flex gap-2">
            <input type="date" name="day" value="{{ $date }}" class="form-control form-control-sm">
            <button class="btn btn-sm btn-primary" type="submit">Göster</button>
        </form>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Araç sayısı</div>
                <div class="fs-4 fw-bold">{{ $summary['vehicle_count'] }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Toplam KM</div>
                <div class="fs-4 fw-bold">{{ $summary['total_km'] }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Toplam sürüş</div>
                <div class="fs-4 fw-bold">{{ $summary['total_hhmm'] }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Toplam saniye</div>
                <div class="fs-6 fw-semibold">{{ $summary['total_seconds'] }}</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Araç</th>
                        <th>Plaka</th>
                        <th>Hermes UID</th>
                        <th class="text-end">KM</th>
                        <th class="text-end">Sürüş</th>
                        <th>İlk hareket</th>
                        <th>Son hareket</th>
                        <th class="text-end">Max</th>
                        <th class="text-muted">DB updated</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $r)
                    @php
                        $km = is_numeric($r->distance_km) ? number_format((float)$r->distance_km, 2) : '-';
                        $dur = is_numeric($r->duration_sec) ? gmdate('H:i', (int)$r->duration_sec) : '-';
                        $first = $minToTime($r->begin_minute) ?? '-';
                        $last  = $minToTime($r->end_minute) ?? '-';
                        $max   = is_numeric($r->max_speed) ? (int)$r->max_speed : '-';
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $r->vehicule_name }}</td>
                        <td>{{ $r->vehicule_plaka ?? '-' }}</td>
                        <td><code>{{ $r->vehicule_hermes_uid ?? '-' }}</code></td>
                        <td class="text-end">{{ $km }}</td>
                        <td class="text-end">{{ $dur }}</td>
                        <td>{{ $first }}</td>
                        <td>{{ $last }}</td>
                        <td class="text-end">{{ $max }}</td>
                        <td class="text-muted small">{{ $r->updated_at }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            Bu gün için DB’de kayıt yok. (Önce <code>hermes:archive-daily</code> çalışmış olmalı)
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
