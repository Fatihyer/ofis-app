@extends('layouts.app')

@section('style')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
@endsection

@section('content')
<div class="container">
    <h2>Add Transfer and Trajets</h2>
    <form action="{{ route('transfers.store') }}" method="POST">
        @csrf
        <input type="hidden" name="post_id" value="{{ $post->id ?? old('post_id') }}">
        <!-- Transfer Details -->
        <h4>Transfer Details</h4>
        <div class="form-group">
            <label for="start_date">Start Date</label>
            <input type="date" name="start_date" class="form-control" 
                   value="{{ old('start_date', $post->start_date ? $post->start_date->format('Y-m-d') : '') }}" required>
        </div>

        <div class="form-group">
            <label for="servicetype_id">Service Type</label>
            <select name="servicetype_id" class="form-control" required>
                <option value="" disabled selected>Select a Service Type</option>
                @foreach ($servicetype as $id => $name)
                    <option value="{{ $id }}" {{ old('servicetype_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="pax">Passengers</label>
            <input type="number" name="pax" class="form-control" 
                   value="{{ old('pax', $post->pax ?? 0) }}" required>
        </div>

        <div class="form-group">
            <label for="vehicule_id">Vehicule</label>
            <select name="vehicule_id" class="form-control" required>
                <option value="" disabled selected>Select a Vehicule</option>
                @foreach ($vehicules as $id => $name)
                    <option value="{{ $id }}" {{ old('vehicule_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="card mb-3 mt-3 border-warning external-vehicle-panel">
            <button class="card-header bg-warning text-dark py-2 w-100 text-left border-0" type="button" data-bs-toggle="collapse" data-bs-target="#externalVehicleCollapsenewcreate_blade1" aria-expanded="{{ (old('vehicle_provider_acente_id') || old('external_vehicle_price') || old('external_vehicle_note')) ? 'true' : 'false' }}" aria-controls="externalVehicleCollapsenewcreate_blade1">
                <i class="fas fa-truck-loading"></i> Véhicule extérieur / location
            </button>
            <div id="externalVehicleCollapsenewcreate_blade1" class="collapse {{ (old('vehicle_provider_acente_id') || old('external_vehicle_price') || old('external_vehicle_note')) ? 'show' : '' }}">
                <div class="card-body">
                    <div class="form-group">
                        <label for="vehicle_provider_acente_id">Fournisseur véhicule</label>
                        <select name="vehicle_provider_acente_id" class="form-control">
                            <option value="">Aucun fournisseur véhicule</option>
                            @foreach (($acentes ?? \App\Models\Acente::orderBy('name')->pluck('name', 'id')) as $id => $name)
                                <option value="{{ $id }}" {{ old('vehicle_provider_acente_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="external_vehicle_price">Prix fournisseur</label>
                            <input type="number" step="0.01" min="0" name="external_vehicle_price" class="form-control" value="{{ old('external_vehicle_price') }}">
                        </div>
                        <div class="form-group col-md-8">
                            <label for="external_vehicle_note">Note / plaque extérieure</label>
                            <input type="text" name="external_vehicle_note" class="form-control" value="{{ old('external_vehicle_note') }}" placeholder="Plaque, modèle, référence fournisseur...">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="firma_id">Firma</label>
            <select name="firma_id" id="firma_id" class="form-control" required>
                <option value="" disabled selected>Select a Firma</option>
                @foreach ($firmas as $id => $name)
                    <option value="{{ $id }}" {{ old('firma_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="driver_id">Driver</label>
            <select name="driver_id" id="driver_id" class="form-control" required>
                <option value="" disabled selected>Select a driver</option>
            </select>
        </div>
        @if(\Illuminate\Support\Facades\Schema::hasColumn('transfers', 'second_driver_id'))
        <div class="form-group">
            <label for="second_driver_id">Double équipage / 2e chauffeur</label>
            <select name="second_driver_id" id="second_driver_id" class="form-control">
                <option value="">Sans double équipage</option>
                @foreach (($acentes ?? \App\Models\Acente::orderBy('name')->pluck('name', 'id')) as $id => $name)
                    <option value="{{ $id }}" {{ old('second_driver_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="form-group">
            <label for="comments">Comments</label>
            <textarea name="comments"  class="form-control"  id=""></textarea>
            
        </div>

         <!-- Suivi Mission -->
         <div class="form-check">
            <label for="mission">Suivi Mission</label>
            <input type="checkbox" name="mission" value="1" class="form-check-label" checked>
        </div>
        <div class="form-check">
            <label for="accueil">Paneau d'acceuil</label>
            <input type="checkbox" name="accueil" value="1" class="form-check-label">

        </div>


        <!-- Trajets Details -->
        <h4>Trajets</h4>
        <table class="table" id="sortable-trajets">
            <thead>
                <tr>
                    <th width="10%">Type</th> 
                    <th width="23%">Name</th>
                    <th width="40%">Google Address</th>
                    <th  width="15%">Date & Time</th>
                    <th >Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <select name="trajets[0][type]" class="form-control" required>
                           <option value="adress">Adress</option>
                            <option value="flight">Flight</option>
                            <option value="hotel">Hotel</option>
                            <option value="restaurant">Restaurant</option>
                            <option value="gar">Gar</option>
                            <option value="depot">Depot</option>
                           
                        </select>
                    </td>
                    <td><input type="text" name="trajets[0][from]" class="form-control" required></td>
                    <td><input type="text" id="google_address_0" name="trajets[0][google_address]" class="form-control"></td>
                    <td><input type="datetime-local" name="trajets[0][datetime]" class="form-control" value="{{ old('start_date', $post->start_date ? $post->start_date->format('Y-m-d\TH:i') : '') }}" required></td>
                    <td><input type="number" name="trajets[0][order]" class="form-control  form-control-sm order-input" value="1" readonly></td>
                    <td><button type="button" class="btn btn-danger remove-trajet">Remove</button></td>
                </tr>
            </tbody>
        </table>
        <hr>
        <div class="form-group">
            <label for="km">Kilometre</label>
            <input type="number" name="km" class="form-control"  value="" required>
        </div>
        <button type="button" id="add-trajet" class="btn btn-secondary">Add Another Trajet</button>
        <button type="button" id="add-depot" class="btn btn-warning">Add Depot</button>
        <button type="button" class="btn btn-primary" onclick="openGoogleMapsItinerary()">View Itinerary on Google Maps</button>

       <div class="alert alert-warning mt-5"> Merci de controler l'adresse de google Map, ca arrive qu'il ecrit une adresse differente </div>
        <button type="submit" class="btn btn-success">Save</button>
    </form>
</div>
@endsection

@section('scripts')
@include('transfert.partials.vehicle-availability-script')
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places&callback=initAutocomplete" async defer></script>
<script>
    let trajetIndex = 1;
    const defaultDepotAddress = @json($defaultDepotAddress ?? '3 Rue de la Butte, Drancy, France');

    function escapeAttribute(value) {
        return $('<div>').text(value || '').html().replace(/"/g, '&quot;');
    }

    function initAutocomplete() {
        initGoogleAutocomplete('google_address_0');
    }

    function initGoogleAutocomplete(inputId) {
        const input = document.getElementById(inputId);
        if (input) {
            const autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.setFields(['formatted_address']);
            autocomplete.addListener('place_changed', function () {
                const place = autocomplete.getPlace();
                console.log('Selected Address:', place.formatted_address);
            });
        }
    }

    $(document).ready(function () {
        // CSRF Token Setup
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Firma Selection: Update Driver List
        $('#firma_id').change(function () {
            const firmaId = $(this).val();
            if (firmaId) {
                $.ajax({
                    url: "{{ route('selectAjaxFirma') }}",
                    method: 'POST',
                    data: { id: firmaId },
                    success: function (response) {
                        const driverSelect = $('#driver_id');
                        driverSelect.empty();
                        driverSelect.append('<option value="" disabled selected>Select a driver</option>');
                        if (response.options && Object.keys(response.options).length > 0) {
    // Gelen veriyi alfabetik sıraya göre sıralayın
                                const sortedOptions = Object.entries(response.options).sort((a, b) => {
                                    return a[1].localeCompare(b[1]); // İsimlere göre karşılaştırma
                                });

                                // Sıralanan veriyi dropdown'a ekleyin
                                $.each(sortedOptions, function (index, [id, name]) {
                                    driverSelect.append(`<option value="${id}">${name}</option>`);
                                });
                        } else {
                            alert('No drivers found for the selected firma.');
                        }
                    },
                    error: function () {
                        alert('Failed to fetch drivers. Please try again.');
                    }
                });
            }
        });

        // Add Trajet Row
        $('#add-trajet').click(function () {
            const newRow = `
                <tr>
                    <td>
                        <select name="trajets[${trajetIndex}][type]" class="form-control" required>
                            <option value="adress">Adress</option>
                            <option value="flight">Flight</option>
                            <option value="hotel">Hotel</option>
                            <option value="restaurant">Restaurant</option>
                            <option value="gar">Gar</option>
                                 <option value="depot">Depot</option>
                        </select>
                    </td>
                    <td><input type="text" name="trajets[${trajetIndex}][from]" class="form-control" required></td>
                    <td><input type="text" id="google_address_${trajetIndex}" name="trajets[${trajetIndex}][google_address]" class="form-control"></td>
                    <td><input type="datetime-local" name="trajets[${trajetIndex}][datetime]" value="{{ old('start_date', $post->start_date ? $post->start_date->format('Y-m-d\TH:i') : '') }}" class="form-control form-control-sm" required></td>
                    <td><input type="number" name="trajets[${trajetIndex}][order]" class="form-control order-input" value="${trajetIndex + 1}" readonly></td>
                    <td><button type="button" class="btn btn-danger remove-trajet">Remove</button></td>
                </tr>`;
            $('#sortable-trajets tbody').append(newRow);
            initGoogleAutocomplete(`google_address_${trajetIndex}`);
            trajetIndex++;
        });
        $('#add-depot').click(function () {
            const newRow = `
                <tr>
                    <td>
                        <select name="trajets[${trajetIndex}][type]" class="form-control" required>
                            <option value="adress">Adress</option>
                            <option value="flight">Flight</option>
                            <option value="hotel">Hotel</option>
                            <option value="restaurant">Restaurant</option>
                            <option value="gar">Gar</option>
                             <option value="depot" selected>Depot</option>
                        </select>
                    </td>
                    <td><input type="text" name="trajets[${trajetIndex}][from]" class="form-control" value="Depot" required></td>
                    <td><input type="text" id="google_address_${trajetIndex}" name="trajets[${trajetIndex}][google_address]" value="${escapeAttribute(defaultDepotAddress)}" class="form-control"></td>
                    <td><input type="datetime-local" name="trajets[${trajetIndex}][datetime]" value="{{ old('start_date', $post->start_date ? $post->start_date->format('Y-m-d\TH:i') : '') }}" class="form-control form-control-sm" required></td>
                    <td><input type="number" name="trajets[${trajetIndex}][order]" class="form-control order-input" value="${trajetIndex + 1}" readonly></td>
                    <td><button type="button" class="btn btn-danger remove-trajet">Remove</button></td>
                </tr>`;
            $('#sortable-trajets tbody').append(newRow);
            initGoogleAutocomplete(`google_address_${trajetIndex}`);
            trajetIndex++;
        });
        // Remove Row
        $('#sortable-trajets').on('click', '.remove-trajet', function () {
            $(this).closest('tr').remove();
            updateOrderNumbers();
        });

        // Sortable Rows
        $('#sortable-trajets tbody').sortable({
            update: function () {
                updateOrderNumbers();
            }
        });

        // Update Order Numbers
        function updateOrderNumbers() {
            $('#sortable-trajets tbody tr').each(function (index) {
                $(this).find('.order-input').val(index + 1);
            });
        }

        // Initialize Autocomplete for the First Row
        initGoogleAutocomplete('google_address_0');
    });
    function getAllGoogleAddresses() {
        const addresses = [];
        $('input[name^="trajets"][name*="[google_address]"]').each(function () {
            if ($(this).val()) {
                addresses.push($(this).val());
            }
        });
        return addresses;
    }

    // Function to open Google Maps with the itinerary
    function openGoogleMapsItinerary() {
        const addresses = getAllGoogleAddresses();

        if (addresses.length < 2) {
            alert("You need at least a starting point and a destination!");
            return;
        }

        const origin = encodeURIComponent(addresses[0]); // Starting point
        const destination = encodeURIComponent(addresses[addresses.length - 1]); // Ending point
        const waypoints = addresses.slice(1, -1).map(encodeURIComponent).join('|'); // Intermediate stops

        let mapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${destination}`;

        if (waypoints) {
            mapsUrl += `&waypoints=${waypoints}`;
        }

        // Open the generated URL in a new tab
        window.open(mapsUrl, '_blank');
    }
</script>
@endsection
