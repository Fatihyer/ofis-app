@extends('layouts.app')

@section('content')
<style>
.vehicle-cost-table th { white-space: nowrap; background:#1f2937; color:#fff; position:sticky; top:0; z-index:2; }
.vehicle-cost-table td { white-space: nowrap; vertical-align: middle; }
.vehicle-cost-table .vehicle-name { min-width:220px; white-space: normal; font-weight:800; }
.cost-positive { font-weight:800; color:#0f172a; }
.cost-muted { color:#94a3b8; }
</style>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 mb-0">Coûts mensuels par véhicule</h1>
            <div class="text-muted">Carburant, kilomètres, entretien et frais garage par véhicule.</div>
        </div>
        <a href="{{ route('vehicle_maintenance.index') }}" class="btn btn-outline-secondary btn-sm">Frais véhicules</a>
    </div>

    <form method="GET" class="card card-body mb-3" style="max-width:420px;">
        <label>Mois</label>
        <div class="input-group">
            <input type="month" name="month" class="form-control" value="{{ $month }}">
            <div class="input-group-append"><button class="btn btn-primary">Afficher</button></div>
        </div>
    </form>

    @php
        $totals = [
            'fuel_amount' => $rows->sum('fuel_amount'),
            'fuel_liters' => $rows->sum('fuel_liters'),
            'maintenance_amount' => $rows->sum('maintenance_amount'),
            'hermes_km' => $rows->sum('hermes_km'),
            'planning_km' => $rows->sum('planning_km'),
        ];
    @endphp
    <div class="row mb-3">
        <div class="col-md-2"><div class="card card-body"><small>Km Hermes</small><strong>{{ number_format($totals['hermes_km'], 0, ',', ' ') }}</strong></div></div>
        <div class="col-md-2"><div class="card card-body"><small>Km planning</small><strong>{{ number_format($totals['planning_km'], 0, ',', ' ') }}</strong></div></div>
        <div class="col-md-2"><div class="card card-body"><small>Carburant</small><strong>{{ number_format($totals['fuel_amount'], 2, ',', ' ') }} €</strong></div></div>
        <div class="col-md-2"><div class="card card-body"><small>Litres</small><strong>{{ number_format($totals['fuel_liters'], 2, ',', ' ') }} L</strong></div></div>
        <div class="col-md-2"><div class="card card-body"><small>Frais</small><strong>{{ number_format($totals['maintenance_amount'], 2, ',', ' ') }} €</strong></div></div>
        <div class="col-md-2"><div class="card card-body"><small>Total</small><strong>{{ number_format($totals['fuel_amount'] + $totals['maintenance_amount'], 2, ',', ' ') }} €</strong></div></div>
    </div>

    <div class="table-responsive card">
        <table class="table table-sm table-hover vehicle-cost-table mb-0">
            <thead>
                <tr>
                    <th>Véhicule</th>
                    <th class="text-right">Km Hermes</th>
                    <th class="text-right">Km planning</th>
                    <th class="text-right">Transferts</th>
                    <th class="text-right">Carburant</th>
                    <th class="text-right">Litres</th>
                    <th class="text-right">Conso / 100</th>
                    <th class="text-right">Entretien</th>
                    <th class="text-right">Contrôle</th>
                    <th class="text-right">Carrosserie</th>
                    <th class="text-right">Garage</th>
                    <th class="text-right">Pneus</th>
                    <th class="text-right">Assurance</th>
                    <th class="text-right">Autres</th>
                    <th class="text-right">Total frais</th>
                    <th class="text-right">Coût / km</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    @php
                        $km = (float) $row->hermes_km > 0 ? (float) $row->hermes_km : (float) $row->planning_km;
                        $totalCost = (float) $row->fuel_amount + (float) $row->maintenance_amount;
                        $consumption = $km > 0 && (float) $row->fuel_liters > 0 ? ((float) $row->fuel_liters / $km) * 100 : null;
                        $costKm = $km > 0 ? $totalCost / $km : null;
                        $other = (float) $row->reparation + (float) $row->lavage + (float) $row->autre;
                    @endphp
                    <tr>
                        <td class="vehicle-name"><a href="{{ route('vehicules.show', $row->id) }}">{{ trim(($row->plaka ? $row->plaka . ' - ' : '') . $row->name) }}</a></td>
                        <td class="text-right">{{ number_format($row->hermes_km, 0, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($row->planning_km, 0, ',', ' ') }}</td>
                        <td class="text-right">{{ (int) $row->transfer_count }}</td>
                        <td class="text-right cost-positive">{{ number_format($row->fuel_amount, 2, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($row->fuel_liters, 2, ',', ' ') }}</td>
                        <td class="text-right">{{ $consumption !== null ? number_format($consumption, 2, ',', ' ') : '-' }}</td>
                        <td class="text-right">{{ number_format($row->entretien, 2, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($row->controle_technique, 2, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($row->carrosserie, 2, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($row->garage, 2, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($row->pneus, 2, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($row->assurance, 2, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($other, 2, ',', ' ') }}</td>
                        <td class="text-right cost-positive">{{ number_format($totalCost, 2, ',', ' ') }}</td>
                        <td class="text-right">{{ $costKm !== null ? number_format($costKm, 2, ',', ' ') : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
