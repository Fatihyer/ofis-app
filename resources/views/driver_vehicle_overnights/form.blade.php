@php
    $overnightDate = old('overnight_date', optional($overnight->overnight_date)->format('Y-m-d') ?: ($overnight->overnight_date ?: now()->toDateString()));
@endphp

@if($transfer)
    <div class="alert alert-info">
        Découcher lié au transfert
        <a href="{{ route('transfers.show', $transfer->id) }}">#{{ $transfer->id }}</a>
        @if($transfer->post_id)
            · dossier <a href="{{ route('posts.show', $transfer->post_id) }}">#{{ $transfer->post_id }}</a>
        @endif
    </div>
@endif

<input type="hidden" name="transfer_id" value="{{ old('transfer_id', $overnight->transfer_id) }}">
<input type="hidden" name="post_id" value="{{ old('post_id', $overnight->post_id) }}">

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label>Date du découcher</label>
            <input type="date" name="overnight_date" class="form-control" value="{{ $overnightDate }}" required>
        </div>
    </div>
    <div class="col-md-5">
        <div class="form-group">
            <label>Chauffeur</label>
            <select name="driver_id" class="form-control">
                <option value="">Non défini</option>
                @foreach($drivers as $id => $name)
                    <option value="{{ $id }}" {{ (int) old('driver_id', $overnight->driver_id) === (int) $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Véhicule</label>
            <select name="vehicule_id" class="form-control">
                <option value="">Non défini</option>
                @foreach($vehicules as $id => $name)
                    <option value="{{ $id }}" {{ (int) old('vehicule_id', $overnight->vehicule_id) === (int) $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Ville</label>
            <input type="text" name="city" class="form-control" value="{{ old('city', $overnight->city) }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Motif</label>
            <select name="reason" class="form-control">
                @foreach($reasons as $key => $label)
                    <option value="{{ $key }}" {{ old('reason', $overnight->reason ?: 'decoucher') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Montant</label>
            <input type="number" step="0.01" min="0" name="amount" class="form-control" value="{{ old('amount', $overnight->amount) }}">
        </div>
    </div>
</div>

<div class="form-group">
    <label>Adresse</label>
    <input type="text" name="address" class="form-control" value="{{ old('address', $overnight->address) }}">
</div>

<div class="form-group">
    <label>Adresse Google</label>
    <input type="text" id="overnight_google_address" name="google_address" class="form-control" value="{{ old('google_address', $overnight->google_address) }}">
</div>

<div class="form-group">
    <label>Notes</label>
    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $overnight->notes) }}</textarea>
</div>

@if(!$overnight->exists)
    <div class="card mb-3" style="border-radius:8px;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Nuits suivantes</strong>
                <div class="text-muted small">Une ligne par nuit si le chauffeur dort dans des hôtels différents.</div>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="addOvernightRow">
                <i class="fa fa-plus"></i> Ajouter une nuit
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0" id="overnightRowsTable">
                <thead>
                    <tr>
                        <th style="min-width:140px;">Date</th>
                        <th style="min-width:140px;">Ville</th>
                        <th style="min-width:230px;">Adresse Google / hôtel</th>
                        <th style="min-width:120px;">Montant</th>
                        <th style="min-width:180px;">Notes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(old('nights', []) as $index => $night)
                        <tr>
                            <td><input type="date" name="nights[{{ $index }}][overnight_date]" class="form-control form-control-sm" value="{{ $night['overnight_date'] ?? '' }}"></td>
                            <td><input type="text" name="nights[{{ $index }}][city]" class="form-control form-control-sm" value="{{ $night['city'] ?? '' }}"></td>
                            <td>
                                <input type="text" name="nights[{{ $index }}][google_address]" class="form-control form-control-sm overnight-extra-google" value="{{ $night['google_address'] ?? '' }}" placeholder="Hôtel, adresse...">
                                <input type="hidden" name="nights[{{ $index }}][address]" value="{{ $night['address'] ?? '' }}">
                            </td>
                            <td><input type="number" step="0.01" min="0" name="nights[{{ $index }}][amount]" class="form-control form-control-sm" value="{{ $night['amount'] ?? '' }}"></td>
                            <td><input type="text" name="nights[{{ $index }}][notes]" class="form-control form-control-sm" value="{{ $night['notes'] ?? '' }}"></td>
                            <td><button type="button" class="btn btn-outline-danger btn-sm remove-overnight-row"><i class="fa fa-trash"></i></button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('driver-vehicle-overnights.index') }}" class="btn btn-outline-secondary">Retour</a>
    <button type="submit" class="btn btn-primary">Enregistrer</button>
</div>

@section('scripts')
    @parent
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places&callback=initOvernightAutocomplete" async defer></script>
    <script>
        let overnightExtraIndex = {{ count(old('nights', [])) }};

        function initOvernightAutocomplete() {
            const input = document.getElementById('overnight_google_address');
            if (input) {
                attachOvernightAutocomplete(input);
            }

            document.querySelectorAll('.overnight-extra-google').forEach(attachOvernightAutocomplete);
        }

        function attachOvernightAutocomplete(input) {
            if (!input || !window.google || input.dataset.autocompleteReady) return;
            input.dataset.autocompleteReady = '1';
            const autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.setFields(['formatted_address']);
            autocomplete.addListener('place_changed', function () {
                const place = autocomplete.getPlace();
                if (place.formatted_address) input.value = place.formatted_address;
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const addButton = document.getElementById('addOvernightRow');
            const tableBody = document.querySelector('#overnightRowsTable tbody');

            if (addButton && tableBody) {
                addButton.addEventListener('click', function () {
                    const index = overnightExtraIndex++;
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td><input type="date" name="nights[${index}][overnight_date]" class="form-control form-control-sm"></td>
                        <td><input type="text" name="nights[${index}][city]" class="form-control form-control-sm"></td>
                        <td>
                            <input type="text" name="nights[${index}][google_address]" class="form-control form-control-sm overnight-extra-google" placeholder="Hôtel, adresse...">
                            <input type="hidden" name="nights[${index}][address]">
                        </td>
                        <td><input type="number" step="0.01" min="0" name="nights[${index}][amount]" class="form-control form-control-sm"></td>
                        <td><input type="text" name="nights[${index}][notes]" class="form-control form-control-sm"></td>
                        <td><button type="button" class="btn btn-outline-danger btn-sm remove-overnight-row"><i class="fa fa-trash"></i></button></td>
                    `;
                    tableBody.appendChild(row);
                    attachOvernightAutocomplete(row.querySelector('.overnight-extra-google'));
                });

                tableBody.addEventListener('click', function (event) {
                    const button = event.target.closest('.remove-overnight-row');
                    if (button) button.closest('tr').remove();
                });
            }
        });
    </script>
@endsection
