@extends('layouts.app')

@section('style')
<style>
.talep-options-page{background:#f8fafc;min-height:calc(100vh - 90px);padding:16px}.talep-options-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:14px}.talep-options-head h2{margin:0;color:#0f172a;font-size:25px;font-weight:850}.talep-options-head small{display:block;color:#64748b;font-weight:700;margin-top:3px}.talep-options-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 22px rgba(15,23,42,.06);overflow:hidden}.talep-options-card-h{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:12px 14px;border-bottom:1px solid #e5e7eb}.talep-options-body{padding:14px}.service-type-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:10px}.service-type-option{display:flex;align-items:center;gap:9px;border:1px solid #e5e7eb;border-radius:8px;padding:10px 11px;background:#fff;min-height:44px}.service-type-option:hover{border-color:#94a3b8}.service-type-option input{margin:0}.service-type-option span{font-weight:800;color:#334155}.service-type-option small{color:#64748b;font-weight:800}.talep-options-actions{display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;margin-top:14px}.fuel-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:10px}.fuel-option{border:1px solid #e5e7eb;border-radius:8px;background:#fff;padding:10px}.fuel-option label{font-weight:850;color:#334155;font-size:12px}.fuel-option .input-group-text{font-weight:800}@media(max-width:700px){.talep-options-page{padding:10px}.talep-options-head{display:block}.talep-options-head .btn{margin-top:10px}.service-type-grid{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
<div class="talep-options-page">
    <div class="talep-options-head">
        <div>
            <h2>Talep Options</h2>
            <small>Types de service visibles dans les formulaires de demande</small>
        </div>
        <a href="{{ route('talepler.index') }}" class="btn btn-secondary btn-sm">Retour aux demandes</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Veuillez corriger les erreurs suivantes :</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('talepler.options.update') }}" method="POST">
        @csrf
        <div class="talep-options-card">
            <div class="talep-options-card-h">
                <strong>Service types</strong>
                <span class="text-muted small">{{ count($selectedServiceTypeIds) }} sélectionné(s)</span>
            </div>
            <div class="talep-options-body">
                <div class="service-type-grid">
                    @foreach($serviceTypes as $serviceType)
                        <label class="service-type-option">
                            <input type="checkbox" name="service_type_ids[]" value="{{ $serviceType->id }}" {{ in_array((int) $serviceType->id, $selectedServiceTypeIds, true) ? 'checked' : '' }}>
                            <span>{{ $serviceType->name }}</span>
                            <small>#{{ $serviceType->id }}</small>
                        </label>
                    @endforeach
                </div>



        <div class="talep-options-card mt-3">
            <div class="talep-options-card-h">
                <strong>Carburant</strong>
                <span class="text-muted small">Consommation par type de véhicule</span>
            </div>
            <div class="talep-options-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Prix mazout / litre</label>
                        <div class="input-group">
                            <input type="number" step="0.001" min="0" name="fuel_price_per_liter" class="form-control" value="{{ old('fuel_price_per_liter', $fuelPricePerLiter) }}">
                            <span class="input-group-text">EUR/L</span>
                        </div>
                    </div>
                </div>

                <div class="fuel-grid">
                    @foreach($vehicleFuelTypes as $code => $label)
                        <div class="fuel-option">
                            <label class="form-label">{{ $label }}</label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" max="100" name="fuel_consumptions[{{ $code }}]" class="form-control" value="{{ old('fuel_consumptions.' . $code, $fuelConsumptions[$code] ?? '') }}">
                                <span class="input-group-text">L/100 km</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

                <div class="talep-options-actions">
                    <a href="{{ route('talepler.index') }}" class="btn btn-outline-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
