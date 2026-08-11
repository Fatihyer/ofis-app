@php
    $idx = $index;
    $today = now('Europe/Paris')->toDateString();
    $row = $row ?? [];
@endphp
<div class="transfer-card">
    <div class="transfer-card-header">
        <div class="transfer-card-title">Transfert <span class="transfer-number">{{ is_numeric($idx) ? ((int) $idx + 1) : '' }}</span></div>
        <button type="button" class="btn btn-outline-danger btn-sm btn-icon-sm remove-transfer" title="Supprimer"><i class="fa fa-trash"></i></button>
    </div>
    <div class="transfer-grid">
        <div class="form-row">
            <div class="form-group col-md-3">
                <label>Service</label>
                <select name="transfers[{{ $idx }}][servicetype_id]" class="form-control" required>
                    @foreach($servicetypes['transfer'] as $id => $name)
                        <option value="{{ $id }}" {{ (string)($row['servicetype_id'] ?? '') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-2">
                <label>Date</label>
                <input type="date" name="transfers[{{ $idx }}][date]" class="form-control" value="{{ $row['date'] ?? $today }}" required>
            </div>
            <div class="form-group col-md-2">
                <label>Début</label>
                <input type="time" name="transfers[{{ $idx }}][start_time]" class="form-control quick-start-time" value="{{ $row['start_time'] ?? '' }}" required>
            </div>
            <div class="form-group col-md-2">
                <label>Fin</label>
                <input type="time" name="transfers[{{ $idx }}][end_time]" class="form-control quick-end-time" value="{{ $row['end_time'] ?? '' }}" required>
            </div>
            <div class="form-group col-md-3">
                <label>Pax</label>
                <input type="number" name="transfers[{{ $idx }}][pax]" class="form-control" value="{{ $row['pax'] ?? old('pax', 1) }}" min="0">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Lieu de départ</label>
                <input type="text" name="transfers[{{ $idx }}][from]" class="form-control" value="{{ $row['from'] ?? '' }}" required>
            </div>
            <div class="form-group col-md-6">
                <label>Lieu d'arrivée</label>
                <input type="text" name="transfers[{{ $idx }}][target]" class="form-control" value="{{ $row['target'] ?? '' }}" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-4">
                <label>Chauffeur / fournisseur</label>
                <select name="transfers[{{ $idx }}][driver_id]" class="form-control" required>
                    @foreach($drivers as $id => $name)
                        <option value="{{ $id }}" {{ (string)($row['driver_id'] ?? 890) === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-4">
                <label>Véhicule</label>
                <select name="transfers[{{ $idx }}][vehicule_id]" class="form-control" required>
                    @foreach($vehicules as $id => $name)
                        <option value="{{ $id }}" {{ (string)($row['vehicule_id'] ?? 22) === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-2">
                <label>Km</label>
                <input type="number" name="transfers[{{ $idx }}][km]" class="form-control" value="{{ $row['km'] ?? 0 }}" min="0">
            </div>
            <div class="form-group col-md-2">
                <label>Options</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="transfers[{{ $idx }}][mission]" value="1" {{ !array_key_exists('mission', $row) || !empty($row['mission']) ? 'checked' : '' }}>
                    <label class="form-check-label">Suivi mission</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="transfers[{{ $idx }}][accueil]" value="1" {{ !empty($row['accueil']) ? 'checked' : '' }}>
                    <label class="form-check-label">Panneau accueil</label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Notes opération</label>
            <input type="text" name="transfers[{{ $idx }}][comments]" class="form-control" value="{{ $row['comments'] ?? '' }}">
        </div>

        <button class="btn btn-sm btn-outline-warning" type="button" data-toggle="collapse" data-target="#externalVehicle{{ $idx }}">
            <i class="fas fa-truck-loading"></i> Véhicule extérieur / location
        </button>
        <div id="externalVehicle{{ $idx }}" class="collapse {{ !empty($row['vehicle_provider_acente_id']) || !empty($row['external_vehicle_price']) || !empty($row['external_vehicle_note']) ? 'show' : '' }}">
            <div class="external-box">
                <div class="form-row">
                    <div class="form-group col-md-5">
                        <label>Fournisseur véhicule</label>
                        <select name="transfers[{{ $idx }}][vehicle_provider_acente_id]" class="form-control">
                            <option value="">Aucun</option>
                            @foreach($acentes as $id => $name)
                                <option value="{{ $id }}" {{ (string)($row['vehicle_provider_acente_id'] ?? '') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Prix fournisseur</label>
                        <input type="number" step="0.01" min="0" name="transfers[{{ $idx }}][external_vehicle_price]" class="form-control" value="{{ $row['external_vehicle_price'] ?? '' }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Note / plaque extérieure</label>
                        <input type="text" name="transfers[{{ $idx }}][external_vehicle_note]" class="form-control" value="{{ $row['external_vehicle_note'] ?? '' }}" placeholder="Plaque, modèle, référence...">
                    </div>
                </div>
                <div class="form-text-soft">Si le véhicule est loué à l'extérieur, renseignez le fournisseur ici.</div>
            </div>
        </div>
    </div>
</div>
