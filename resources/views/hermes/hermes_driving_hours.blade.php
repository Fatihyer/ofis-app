@extends('layouts.app')

@section('content')
<div class="container py-3">

    <h3 class="mb-1">
        Heures de conduite – Hermes
    </h3>
    <div class="text-muted mb-3">
        Chauffeur : <strong>{{ $acente->name }}</strong>
    </div>

    <div class="alert alert-warning small">
        ⚠️ Les données Hermes sont limitées aux <strong>{{ $retentionDays }} derniers jours</strong>.
        Les jours plus anciens ne sont pas disponibles (rétention).
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <div class="text-muted">Total ({{ $retentionDays }} jours)</div>
                <div class="fs-4 fw-bold">{{ $total_hours }} h</div>
            </div>
            <div class="text-end text-muted small">
                Source : Hermes track-info
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Heures de conduite</th>
                        <th>Distance (km)</th>
                        <th>Vitesse max</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($days as $d)
                        <tr>
                            <td>{{ $d['day'] }}</td>
                            <td>
                                <strong>{{ $d['duration_h'] }} h</strong>
                            </td>
                            <td>
                                {{ $d['distance'] ?? '—' }}
                            </td>
                            <td>
                                {{ $d['max_speed'] ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                Aucune donnée Hermes disponible
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
            ← Retour
        </a>
    </div>

</div>
@endsection
