
@extends('layouts.app')

@section('style')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

@endsection
@section('content')
<style>
.fuel-import-page { width: 100%; max-width: none; padding: 0 12px 24px; }
.fuel-import-head { display: flex; justify-content: space-between; align-items: end; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
.fuel-import-head h1 { margin: 0; font-size: 24px; font-weight: 850; }
.fuel-import-card { border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; padding: 12px; margin-bottom: 12px; }
.fuel-import-table-wrap { width: 100%; overflow: auto; max-height: calc(100vh - 260px); border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; }
.fuel-import-table { min-width: 1500px; margin-bottom: 0; }
.fuel-import-table th { position: sticky; top: 0; z-index: 4; background: #1f2937; color: #fff; white-space: nowrap; }
.fuel-import-table td { vertical-align: middle; white-space: nowrap; }
.fuel-import-table .form-control, .fuel-import-table .select2-container { min-width: 180px; }
.fuel-import-table input[name="kilometrage"] { min-width: 110px; }
.fuel-action-col { position: sticky; right: 105px; z-index: 3; background: #fff; min-width: 115px; box-shadow: -6px 0 10px rgba(15,23,42,.06); }
.fuel-delete-col { position: sticky; right: 0; z-index: 3; background: #fff; min-width: 105px; box-shadow: -6px 0 10px rgba(15,23,42,.04); }
.fuel-import-table thead .fuel-action-col, .fuel-import-table thead .fuel-delete-col { z-index: 6; background: #111827; }
.select2-container--open { z-index: 9999; }
@media (max-width: 768px) { .fuel-import-table-wrap { max-height: calc(100vh - 220px); } }
</style>
<div class="container-fluid fuel-import-page">

@if(session('success'))
        <div style="color: green;">{{ session('success') }}</div>
    @endif
    <div class="fuel-import-head">
        <div>
            <h1>Import carburant</h1>
            <div class="text-muted">Contrôlez les lignes importées puis enregistrez ou supprimez chaque ligne.</div>
        </div>
    </div>
    <form action="{{ route('fuel.import') }}" method="POST" enctype="multipart/form-data" class="fuel-import-card">
        @csrf
        <div class="mb-3">
            <label for="file" class="form-label">Fichier Excel</label>
            <input type="file" class="form-control" id="file" name="file"  required>
        </div>
        <button type="submit" class="btn btn-primary">Importer</button>

    
    </form>
    <div class="fuel-import-table-wrap">
    <table class="table table-bordered table-striped fuel-import-table">
    <thead class="thead-dark">
                <tr>
                <th>Date</th>
<th>Immatriculation</th>
<th>Numéro de carte</th>
<th>Litres</th>
<th>Montant (€)</th>
<th>Type de carburant</th>
<th>Kilomètre</th>
<th>Driver</th>
<th>Vehicule</th>
<th class="fuel-action-col">Enregistrer</th>
<th class="fuel-delete-col">Effacer</th>

                </tr>
            </thead>
            <tbody>
    @php
        if (!function_exists('normalizeCard')) {
            function normalizeCard($num) {
                return preg_replace('/\D/', '', ltrim($num, '0'));
            }
        }
    @endphp

    @foreach($rows as $row)
        @php
            $selectedCard = normalizeCard($row->card_raw);
            $matched = false;
            $saveFormId = 'fuel-save-' . $row->id;
            $deleteFormId = 'fuel-delete-' . $row->id;
        @endphp

        <tr>
            <td>{{ $row->authorized_at }}</td>
            <td>{{ $row->vehicule_raw }}</td>
            <td>
                <div class="small text-muted mb-1">{{ $selectedCard }}</div>
                <select form="{{ $saveFormId }}" name="fuel_card_id" class="form-control form-control-sm select2 fuel-card-select" required>
                    <option value="">Sélectionner une carte</option>
                    @foreach($fuelCards as $card)
                        @php
                            $normalizedCard = normalizeCard($card->card_number);
                            $isSelected = str_starts_with($normalizedCard, $selectedCard) || str_starts_with($selectedCard, $normalizedCard);
                            if ($isSelected) $matched = true;
                        @endphp
                        <option value="{{ $card->id }}" data-vehicule="{{ $card->vehicule_id }}" data-acente="{{ $card->acente_id }}" {{ $isSelected ? 'selected' : '' }}>
                            {{ $card->card_number }} - {{ $card->acente?->name ?? '' }}
                        </option>
                    @endforeach
                    @if(!$matched)
                        <option value="" selected disabled>La carte n'est pas enregistrée</option>
                    @endif
                </select>
            </td>
            <td><strong>{{ $row->volume }}</strong></td>
            <td><strong>{{ $row->amount }}</strong></td>
            <td>{{ $row->fuel_type }}</td>
            <td><input form="{{ $saveFormId }}" type="number" name="kilometrage" class="form-control form-control-sm" required value="{{ $row->kilometrage }}"></td>
            <td>
                <select form="{{ $saveFormId }}" name="acente_id" class="form-control form-control-sm select2 acente-select" required>
                    <option value="">Sélectionner</option>
                    @foreach($acenteler as $acente)
                        <option value="{{ $acente->id }}">{{ $acente->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select form="{{ $saveFormId }}" name="vehicule_id" class="form-control form-control-sm select2 vehicule-select" required>
                    <option value="">Sélectionner</option>
                    @foreach($vehicules as $vehicule)
                        <option value="{{ $vehicule->id }}">{{ $vehicule->name }}</option>
                    @endforeach
                </select>
            </td>
            <td class="fuel-action-col">
                <form id="{{ $saveFormId }}" action="{{ route('fuel.import.save', $row->id) }}" method="POST" class="m-0">
                    @csrf
                </form>
                <button form="{{ $saveFormId }}" type="submit" class="btn btn-success btn-sm w-100">Enregistrer</button>
            </td>
            <td class="fuel-delete-col">
                <form id="{{ $deleteFormId }}" action="{{ route('fuel.import.delete', $row->id) }}" method="POST" class="m-0" onsubmit="return confirm('Supprimer cette ligne ?');">
                    @csrf
                    @method('DELETE')
                </form>
                <button form="{{ $deleteFormId }}" type="submit" class="btn btn-danger btn-sm w-100">Supprimer</button>
            </td>
        </tr>
    @endforeach
</tbody>

        </table>
   
</div> </div> </div>
@endsection
@section('scripts')

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Sélectionner',
            allowClear: true
        });
    });

$(document).on('change', '.fuel-card-select', function() {

    var vehiculeId = $(this).find(':selected').data('vehicule');

    var row = $(this).closest('tr');

    var vehiculeSelect = row.find('.vehicule-select');

    if (vehiculeId) {

        vehiculeSelect.val(vehiculeId).trigger('change');

    }

});
$('.fuel-card-select').each(function() {

    var vehiculeId = $(this).find(':selected').data('vehicule');

    if (vehiculeId) {

        var row = $(this).closest('tr');

        row.find('.vehicule-select')
           .val(vehiculeId)
           .trigger('change');

    }

});
$(document).on('change', '.fuel-card-select', function() {

    var vehiculeId = $(this).find(':selected').data('vehicule');
    var acenteId   = $(this).find(':selected').data('acente');

    var row = $(this).closest('tr');

    if (vehiculeId) {
        row.find('.vehicule-select').val(vehiculeId).trigger('change');
    }

    if (acenteId) {
        row.find('.acente-select').val(acenteId).trigger('change');
    }
});
$('.fuel-card-select').each(function() {

    var vehiculeId = $(this).find(':selected').data('vehicule');
    var acenteId   = $(this).find(':selected').data('acente');

    var row = $(this).closest('tr');

    if (vehiculeId) {
        row.find('.vehicule-select').val(vehiculeId).trigger('change');
    }

    if (acenteId) {
        row.find('.acente-select').val(acenteId).trigger('change');
    }
});


</script>
@endsection