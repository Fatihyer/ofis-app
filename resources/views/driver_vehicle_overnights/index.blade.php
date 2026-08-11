@extends('layouts.app')

@section('title', '| Découchers')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">Découchers chauffeurs</h3>
            <small class="text-muted">Suivi des nuits hors dépôt et point de départ pour le calcul en route.</small>
        </div>
        <a href="{{ route('driver-vehicle-overnights.create') }}" class="btn btn-primary btn-sm">
            <i class="fa fa-plus"></i> Ajouter
        </a>
    </div>

    @if($missingTable)
        <div class="alert alert-warning">
            La table <strong>driver_vehicle_overnights</strong> n'existe pas encore. Exécutez le SQL prévu avant d'utiliser ce module.
        </div>
    @else
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-3">
                        <label>Date début</label>
                        <input type="date" name="start_date" value="{{ $startDate }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label>Date fin</label>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label>Chauffeur</label>
                        <select name="driver_id" class="form-control">
                            <option value="">Tous</option>
                            @foreach($drivers as $id => $name)
                                <option value="{{ $id }}" {{ (int) $selectedDriverId === (int) $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-dark btn-block">Filtrer</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mb-3">
            @foreach($summaryByDriver as $summary)
                <div class="col-md-3 mb-2">
                    <div class="card h-100">
                        <div class="card-body py-2">
                            <strong>{{ $summary['driver'] }}</strong>
                            <div>{{ $summary['count'] }} nuit(s)</div>
                            <small class="text-muted">{{ number_format($summary['amount'], 2, ',', ' ') }} EUR</small>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Chauffeur</th>
                            <th>Véhicule</th>
                            <th>Lieu</th>
                            <th>Dossier / transfert</th>
                            <th>Montant</th>
                            <th>Créé par</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($overnights as $overnight)
                            <tr>
                                <td>{{ optional($overnight->overnight_date)->format('d/m/Y') }}</td>
                                <td>{{ optional($overnight->driver)->name ?? '-' }}</td>
                                <td>{{ optional($overnight->vehicule)->name ?? '-' }}</td>
                                <td>
                                    <strong>{{ $overnight->city ?: '-' }}</strong>
                                    <div class="text-muted small">{{ $overnight->google_address ?: $overnight->address }}</div>
                                </td>
                                <td>
                                    @if($overnight->post_id)
                                        <a href="{{ route('posts.show', $overnight->post_id) }}">Dossier #{{ $overnight->post_id }}</a>
                                    @endif
                                    @if($overnight->transfer_id)
                                        <div><a href="{{ route('transfers.show', $overnight->transfer_id) }}">Transfert #{{ $overnight->transfer_id }}</a></div>
                                    @endif
                                </td>
                                <td>{{ $overnight->amount ? number_format((float) $overnight->amount, 2, ',', ' ') . ' EUR' : '-' }}</td>
                                <td>{{ optional($overnight->creator)->name ?? '-' }}</td>
                                <td class="text-right">
                                    <a href="{{ route('driver-vehicle-overnights.edit', $overnight->id) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('driver-vehicle-overnights.destroy', $overnight->id) }}" class="d-inline" onsubmit="return confirm('Supprimer ce découcher ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Aucun découcher sur la période.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $overnights->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
