@extends('layouts.app')

@section('content')
<style>
.depot-page { max-width: 1500px; }
.depot-card { border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; }
.depot-table th { white-space: nowrap; font-size: 12px; text-transform: uppercase; color: #64748b; background: #f8fafc; }
.depot-table td { vertical-align: middle; }
.depot-badge { border: 1px solid #dbe3ef; background: #f8fafc; color: #334155; border-radius: 999px; padding: 3px 9px; font-size: 12px; }
</style>

<div class="container-fluid depot-page">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Dépôts</h3>
            <div class="text-muted">Adresses utilisées pour les calculs d’approche, retour dépôt, prix et en route.</div>
        </div>
        <a href="{{ route('vehicle-price-rules.index') }}" class="btn btn-outline-secondary">Tarifs véhicules</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="depot-card mb-4">
        <div class="card-header bg-white"><strong>Nouveau dépôt</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('depots.store') }}">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Nom</label>
                        <input type="text" name="name" class="form-control" placeholder="Dépôt Paris" required>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small text-muted">Code</label>
                        <input type="text" name="code" class="form-control" placeholder="PARIS">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Adresse</label>
                        <input type="text" name="address" class="form-control" placeholder="Adresse Google complète" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Ville</label>
                        <input type="text" name="city" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small text-muted">Ordre</label>
                        <input type="number" name="sort_order" class="form-control" value="0" min="0">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small text-muted d-block">Défaut</label>
                        <input type="checkbox" name="is_default" value="1">
                    </div>
                    <div class="col-md-1">
                        <input type="hidden" name="active" value="1">
                        <button type="submit" class="btn btn-primary w-100">Ajouter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="depot-card mb-4">
        <div class="table-responsive">
            <table class="table table-hover depot-table mb-0">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Code</th>
                        <th>Adresse</th>
                        <th>Ville</th>
                        <th>GPS</th>
                        <th>Ordre</th>
                        <th>Défaut</th>
                        <th>Actif</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($depots as $depot)
                        <tr>
                            <form method="POST" action="{{ route('depots.update', $depot->id) }}">
                                @csrf
                                @method('PUT')
                                <td><input type="text" name="name" class="form-control form-control-sm" value="{{ $depot->name }}" required></td>
                                <td><input type="text" name="code" class="form-control form-control-sm" value="{{ $depot->code }}"></td>
                                <td><input type="text" name="address" class="form-control form-control-sm" value="{{ $depot->address }}" required></td>
                                <td>
                                    <input type="text" name="city" class="form-control form-control-sm mb-1" value="{{ $depot->city }}" placeholder="Ville">
                                    <input type="text" name="postal_code" class="form-control form-control-sm" value="{{ $depot->postal_code }}" placeholder="CP">
                                    <input type="hidden" name="country" value="{{ $depot->country ?: 'France' }}">
                                </td>
                                <td class="text-nowrap">
                                    <input type="number" step="0.0000001" name="lat" class="form-control form-control-sm mb-1" value="{{ $depot->lat }}" placeholder="Lat">
                                    <input type="number" step="0.0000001" name="lng" class="form-control form-control-sm" value="{{ $depot->lng }}" placeholder="Lng">
                                </td>
                                <td><input type="number" name="sort_order" class="form-control form-control-sm" value="{{ $depot->sort_order ?? 0 }}" min="0"></td>
                                <td class="text-center">
                                    <input type="hidden" name="is_default" value="0">
                                    <input type="checkbox" name="is_default" value="1" @checked($depot->is_default)>
                                </td>
                                <td class="text-center">
                                    <input type="hidden" name="active" value="0">
                                    <input type="checkbox" name="active" value="1" @checked($depot->active)>
                                </td>
                                <td class="text-nowrap">
                                    <button type="submit" class="btn btn-sm btn-primary">Enregistrer</button>
                            </form>
                            <form method="POST" action="{{ route('depots.destroy', $depot->id) }}" class="d-inline" onsubmit="return confirm('Supprimer ce dépôt ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                            </form>
                                </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="depot-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Affectation véhicules</strong>
            <span class="depot-badge">{{ $vehicules->count() }} véhicule(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover depot-table mb-0">
                <thead>
                    <tr>
                        <th>Véhicule</th>
                        <th>Plaque</th>
                        <th>Capacité</th>
                        <th>Dépôt</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicules as $vehicule)
                        <tr>
                            <form method="POST" action="{{ route('depots.vehicle.update') }}">
                                @csrf
                                <input type="hidden" name="vehicule_id" value="{{ $vehicule->id }}">
                                <td>{{ $vehicule->name }}</td>
                                <td>{{ $vehicule->plaka ?: '-' }}</td>
                                <td>{{ $vehicule->capacity ?: '-' }}</td>
                                <td style="max-width: 280px;">
                                    <select name="depot_id" class="form-control form-control-sm">
                                        <option value="">Aucun / automatique</option>
                                        @foreach($depots as $depot)
                                            <option value="{{ $depot->id }}" @selected((int) $vehicule->depot_id === (int) $depot->id)>
                                                {{ $depot->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><button type="submit" class="btn btn-sm btn-outline-primary">Mettre à jour</button></td>
                            </form>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">Aucun véhicule.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
