@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Transfer and Trajets</h2>
    <form action="{{ route('updateWithTrajets', $transfer->id) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="post_id" value="{{ $transfer->post->id ?? old('post_id') }}">
        @php
            $vehicleLocked = \Illuminate\Support\Facades\Schema::hasColumn('transfers', 'vehicle_locked') && (bool) $transfer->vehicle_locked;
            $canChangeVehicle = !$vehicleLocked || (Auth::user() && Auth::user()->hasRole('Superadmin'));
        @endphp
        <!-- Transfer Details -->
        <h4>Transfer Details</h4>
        <div class="form-group">
            <label for="ofis_start">Ofis Start</label>
            <input type="text" name="ofis_start"  disabled class="form-control" 
                   value="{{ old('ofis_start', $transfer->ofis_start ? $transfer->ofis_start->format('d-m-Y H:i') : '') }}" >
        </div>
        <div class="form-group">
            <label for="start_date">Start Date</label>
            <input type="text" name="start_date"  disabled class="form-control" 
                   value="{{ old('start_date', $transfer->start_date ? $transfer->start_date->format('d-m-Y H:i') : '') }}" >
        </div>

        <div class="form-group">
            <label for="servicetype_id">Service Type</label>
            <select name="servicetype_id" class="form-control" required>
                <option value="" disabled>Select a Service Type</option>
                @foreach ($serviceTypes as $id => $name)
                    <option value="{{ $id }}" {{ $transfer->servicetype_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="pax">Passengers</label>
            <input type="number" name="pax" class="form-control" 
                   value="{{ old('pax', $transfer->pax) }}" required>
        </div>

        <div class="form-group">
            <label for="vehicule_id">Vehicule</label>
            @if($vehicleLocked && !$canChangeVehicle)
                <input type="hidden" name="vehicule_id" value="{{ $transfer->vehicule_id }}">
            @endif
            <select name="vehicule_id" class="form-control" data-current-transfer-id="{{ $transfer->id }}" required {{ $canChangeVehicle ? '' : 'disabled' }}>
                <option value="" disabled>Select a Vehicule</option>
                @foreach ($vehicules as $id => $name)
                    <option value="{{ $id }}" {{ $transfer->vehicule_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            @if($vehicleLocked)
                <div class="alert alert-warning mt-2 mb-0">Araç bloke edildi, değiştirilemez.</div>
            @endif
            @if(Auth::user() && Auth::user()->hasRole('Superadmin'))
                <input type="hidden" name="vehicle_locked" value="0">
                <div class="form-check mt-2">
                    <input type="checkbox" name="vehicle_locked" value="1" class="form-check-input" id="vehicle_locked" {{ $vehicleLocked ? 'checked' : '' }}>
                    <label class="form-check-label" for="vehicle_locked">Véhicule bloqué - ne pas changer sans déblocage</label>
                </div>
            @endif
        </div>

        <div class="card mb-3 mt-3 border-warning external-vehicle-panel">
            <button class="card-header bg-warning text-dark py-2 w-100 text-left border-0" type="button" data-bs-toggle="collapse" data-bs-target="#externalVehicleCollapsenewedit_blade1" aria-expanded="{{ old('vehicle_provider_acente_id', $transfer->vehicle_provider_acente_id) || old('external_vehicle_price', $transfer->external_vehicle_price) || old('external_vehicle_note', $transfer->external_vehicle_note) ? 'true' : 'false' }}" aria-controls="externalVehicleCollapsenewedit_blade1">
                <i class="fas fa-truck-loading"></i> Véhicule extérieur / location
            </button>
            <div id="externalVehicleCollapsenewedit_blade1" class="collapse {{ old('vehicle_provider_acente_id', $transfer->vehicle_provider_acente_id) || old('external_vehicle_price', $transfer->external_vehicle_price) || old('external_vehicle_note', $transfer->external_vehicle_note) ? 'show' : '' }}">
                <div class="card-body">
                    <div class="form-group">
                        <label for="vehicle_provider_acente_id">Fournisseur véhicule</label>
                        <select name="vehicle_provider_acente_id" class="form-control">
                            <option value="">Aucun fournisseur véhicule</option>
                            @foreach (($acentes ?? \App\Models\Acente::orderBy('name')->pluck('name', 'id')) as $id => $name)
                                <option value="{{ $id }}" {{ (int)($transfer->vehicle_provider_acente_id ?? 0) === (int)$id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="external_vehicle_price">Prix fournisseur</label>
                            <input type="number" step="0.01" min="0" name="external_vehicle_price" class="form-control" value="{{ old('external_vehicle_price', $transfer->external_vehicle_price) }}">
                        </div>
                        <div class="form-group col-md-8">
                            <label for="external_vehicle_note">Note / plaque extérieure</label>
                            <input type="text" name="external_vehicle_note" class="form-control" value="{{ old('external_vehicle_note', $transfer->external_vehicle_note) }}" placeholder="Plaque, modèle, référence fournisseur...">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="firma_id">Firma</label>
            <select name="firma_id" id="firma_id"  class="form-control" >
                <option value="" selected>Select a Firma</option>
                @foreach ($firmas as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="driver_id">Driver</label>
            <select name="driver_id" id="driver_id" class="form-control"  required>
                <option value="{{$transfer->driver->id}}">{{$transfer->driver->name}}</option>
              </select>
              @if(\Illuminate\Support\Facades\Schema::hasColumn('transfers', 'second_driver_id'))
              <div class="form-group mt-2">
                <label for="second_driver_id">Double équipage / 2e chauffeur</label>
                <select name="second_driver_id" id="second_driver_id" class="form-control">
                    <option value="">Sans double équipage</option>
                    @foreach (($acentes ?? \App\Models\Acente::orderBy('name')->pluck('name', 'id')) as $id => $name)
                        <option value="{{ $id }}" {{ (int)($transfer->second_driver_id ?? 0) === (int)$id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
              </div>
              @endif
           
              <div class="form-group">
                <label for="comments">Comments</label>
                <textarea name="comments"  class="form-control"  id="">{{$transfer->comments}}</textarea>
                
            </div>   
        </div>
         <!-- Suivi Mission -->
         <div class="form-check">
            <label for="mission">Suivi Mission</label>
            <input type="checkbox" name="mission" value="1" {{ $transfer->mission ? 'checked' : '' }} class="form-check-label">
        </div>
        <div class="form-check">
            <label for="accueil">Paneau d'acceuil</label>
            <input type="checkbox" name="accueil" value="1" {{ $transfer->accueil ? 'checked' : '' }} class="form-check-label">
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
              
                    
                
                @foreach ($transfer->trajets as $index => $trajet)
                    <tr>
                        <td>
                            <input type="hidden" name="trajets[{{ $index }}][id]" value="{{ $trajet->id }}">
                            <select name="trajets[{{ $index }}][type]" class="form-control" required>
                                <option value="adress" {{ $trajet->type == 'adress' ? 'selected' : '' }}>Adress</option>
                                <option value="flight" {{ $trajet->type == 'flight' ? 'selected' : '' }}>Flight</option>
                                <option value="hotel" {{ $trajet->type == 'hotel' ? 'selected' : '' }}>Hotel</option>
                                <option value="restaurant" {{ $trajet->type == 'restaurant' ? 'selected' : '' }}>Restaurant</option>
                                <option value="gar" {{ $trajet->type == 'gar' ? 'selected' : '' }}>Gar</option>
                                <option value="depot" {{ $trajet->type == 'depot' ? 'selected' : '' }}>Depot</option>
                            </select>
                        </td>
                        <td><input type="text" name="trajets[{{ $index }}][from]" class="form-control" value="{{ $trajet->from }}" required></td>
                        <td><input type="text" name="trajets[{{ $index }}][google_address]" class="form-control" value="{{ $trajet->google_address }}"></td>
                        <td><input type="datetime-local" name="trajets[{{ $index }}][datetime]" class="form-control C" value="{{ $trajet->datetime->format('Y-m-d\TH:i') }}" required></td>
                        <td><input type="number" name="trajets[{{ $index }}][order]" class="form-control form-control-sm order-input" value="{{ $trajet->order }}" readonly></td>
                        <td><button type="button" class="btn btn-danger remove-trajet"><i class="fa fa-trash"></i></button></td>
                    </tr>
                @endforeach
              
               
                  
               
            </tbody>
        </table>
        <hr>
        <div class="form-group">
            <label for="km">Kilometre</label>
            <input type="number" name="km" class="form-control"  value="{{ old('km', $transfer->km) }}" required>
        </div>
        <button type="button" id="add-trajet" class="btn btn-secondary">Add Another Trajet</button>
        <button type="button" id="add-depot" class="btn btn-warning">Add Depot</button>
        <a href="{{route('posts.show',$transfer->post_id)}}">Go to File</a>
        <button type="button" class="btn btn-primary" onclick="openGoogleMapsItinerary()">View Itinerary on Google Maps</button>

        <button type="submit" class="btn btn-success mt-3">Save</button>
        <br>
        <div class="alert alert-warning mt-5"> Merci de controler l'adresse de google Map, ca arrive qu'il ecrit une adresse differente </div>
        @if ($transfer->trajets->count() == 0)
           <span class="text-danger"> !!!!!!bunlar eski bilgiler yeniden girin:</span><br>
          From   {{ $transfer->from }}  {{ $transfer->start_date->format('Y-m-d\TH:i') }}  <br>
          to   {{ $transfer->target}}   {{ $transfer->end_date->format('Y-m-d\TH:i') }}
        @endif
    </form>
</div>
@endsection
@section('scripts')
@include('transfert.partials.vehicle-availability-script')
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places&callback=initAutocomplete&loading=async" async defer></script>
<script>
    // Initialize trajetIndex based on the number of existing trajets
    let trajetIndex = {{ $transfer->trajets && $transfer->trajets->count() > 0 ? $transfer->trajets->count() : 2 }};
    const defaultDepotAddress = @json($defaultDepotAddress ?? '3 Rue de la Butte, Drancy, France');

    function escapeAttribute(value) {
        return $('<div>').text(value || '').html().replace(/"/g, '&quot;');
    }

    // Function to initialize Google Autocomplete for a specific input
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

    // Function to initialize Google Autocomplete for existing rows
    function initAutocomplete() {
        @foreach ($transfer->trajets as $index => $trajet)
            initGoogleAutocomplete('google_address_{{ $index }}');
        @endforeach
    }

    $(document).ready(function () {
        // CSRF Token Setup
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        // Fetch Drivers based on Firma
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

                        if (response.options) {
                            const sortedOptions = Object.entries(response.options).sort((a, b) => {
                                return a[1].localeCompare(b[1]);
                            });

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

        // Add new Trajet Row
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
                    <td><input type="datetime-local" name="trajets[${trajetIndex}][datetime]" class="form-control form-control-sm" required></td>
                    <td><input type="number" name="trajets[${trajetIndex}][order]" class="form-control form-control-sm order-input" value="${trajetIndex + 1}" readonly></td>
                    <td><button type="button" class="btn btn-danger remove-trajet"><i class="fa fa-trash"></i> </button></td>
                </tr>`;
            $('#sortable-trajets tbody').append(newRow);
            initGoogleAutocomplete(`google_address_${trajetIndex}`);
            trajetIndex++;
            updateOrderNumbers(); // Update order numbers dynamically
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
                    <td><input type="datetime-local" name="trajets[${trajetIndex}][datetime]" value="{{ old('start_date', $transfer->start_date ? $transfer->start_date->format('Y-m-d\TH:i') : '') }}" class="form-control form-control-sm" required></td>
                    <td><input type="number" name="trajets[${trajetIndex}][order]" class="form-control form-control-sm order-input" value="${trajetIndex + 1}" readonly></td>
                    <td><button type="button" class="btn btn-danger remove-trajet"><i class="fa fa-trash"></i> </button></td>
                </tr>`;
            $('#sortable-trajets tbody').append(newRow);
            initGoogleAutocomplete(`google_address_${trajetIndex}`);
            trajetIndex++;
        });
        // Remove Row
        $('#sortable-trajets').on('click', '.remove-trajet', function () {
            $(this).closest('tr').remove();
            updateOrderNumbers(); // Update order numbers dynamically after removal
        });

        // Sortable Rows with Reordering
        $('#sortable-trajets tbody').sortable({
            update: function () {
                updateOrderNumbers(); // Update order numbers dynamically after sorting
            }
        });

        // Update order numbers dynamically for all rows
        function updateOrderNumbers() {
            $('#sortable-trajets tbody tr').each(function (index) {
                $(this).find('.order-input').val(index + 1); // Update the displayed order

                // Update the name attributes dynamically to match the new order
                $(this).find('input[name^="trajets"][name*="[id]"]').attr('name', `trajets[${index}][id]`);
                $(this).find('select[name^="trajets"]').attr('name', `trajets[${index}][type]`);
                $(this).find('input[name^="trajets"][name*="[from]"]').attr('name', `trajets[${index}][from]`);
                $(this).find('input[name^="trajets"][name*="[google_address]"]').attr('name', `trajets[${index}][google_address]`);
                $(this).find('input[name^="trajets"][name*="[datetime]"]').attr('name', `trajets[${index}][datetime]`);
                $(this).find('input[name^="trajets"][name*="[order]"]').attr('name', `trajets[${index}][order]`);
            });
        }

        // Initialize Autocomplete for existing rows
        initAutocomplete();
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
