@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 mb-0">Frais véhicules</h1>
            <div class="text-muted">Entretien, garage, carrosserie, contrôle technique et autres frais.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('vehicle_maintenance.monthly_costs') }}" class="btn btn-outline-primary btn-sm">Coûts mensuels</a>
            <a href="{{ route('vehicle_maintenance.create') }}" class="btn btn-primary btn-sm">Ajouter un frais</a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <form method="GET" class="card card-body mb-3">
        <div class="row align-items-end">
            <div class="col-md-2 form-group"><label>Début</label><input type="date" name="start_date" class="form-control" value="{{ $startDate }}"></div>
            <div class="col-md-2 form-group"><label>Fin</label><input type="date" name="end_date" class="form-control" value="{{ $endDate }}"></div>
            <div class="col-md-3 form-group"><label>Véhicule</label><select name="vehicule_id" class="form-control"><option value="">Tous</option>@foreach($vehicules as $vehicule)<option value="{{ $vehicule->id }}" @selected((string)$vehiculeId === (string)$vehicule->id)>{{ trim(($vehicule->plaka ? $vehicule->plaka . ' - ' : '') . $vehicule->name) }}</option>@endforeach</select></div>
            <div class="col-md-3 form-group"><label>Catégorie</label><select name="category" class="form-control"><option value="">Toutes</option>@foreach($categories as $key => $label)<option value="{{ $key }}" @selected($category === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-2 form-group"><button class="btn btn-primary btn-block">Filtrer</button></div>
        </div>
    </form>

    <div class="alert alert-light border d-flex justify-content-between"><strong>Total période</strong><strong>{{ number_format($totalAmount, 2, ',', ' ') }} €</strong></div>

    <div class="table-responsive card">
        <table class="table table-striped table-hover mb-0">
            <thead class="thead-dark"><tr><th>Date</th><th>Véhicule</th><th>Catégorie</th><th>Fournisseur</th><th>Description</th><th>N° facture</th><th class="text-right">Montant</th><th>Cari</th><th></th></tr></thead>
            <tbody>
                @forelse($maintenances as $maintenance)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($maintenance->service_date)->format('d/m/Y') }}</td>
                        <td>{{ trim((optional($maintenance->vehicule)->plaka ? optional($maintenance->vehicule)->plaka . ' - ' : '') . optional($maintenance->vehicule)->name) }}</td>
                        <td>{{ $categories[$maintenance->category] ?? 'Autre' }}</td>
                        <td>{{ optional($maintenance->acente)->name ?: '-' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($maintenance->description, 80) }}</td>
                        <td>{{ $maintenance->invoiceno ?: '-' }}</td>
                        <td class="text-right"><strong>{{ number_format((float)$maintenance->amount, 2, ',', ' ') }}</strong></td>
                        <td>{!! $maintenance->hareket_id ? '<span class="badge badge-success">OK</span>' : '<span class="badge badge-secondary">-</span>' !!}</td>
                        <td class="text-right">
                            <a href="{{ route('vehicle_maintenance.edit', $maintenance->id) }}" class="btn btn-sm btn-outline-warning">Modifier</a>
                            @role('Superadmin')
                            <form action="{{ route('vehicle_maintenance.destroy', $maintenance->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce frais ?');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Supprimer</button></form>
                            @endrole
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Aucun frais pour cette période.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
