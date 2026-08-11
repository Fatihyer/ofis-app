@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Fuel Card</h1>
    <form action="{{ route('fuel.update', $fuelCard->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="card_number">Card Number</label>
            <input type="text" class="form-control" id="card_number" name="card_number" value="{{ $fuelCard->card_number }}" required>
        </div>
        <div class="form-group">
            <label for="card_type">Card Type</label>
            <select class="form-control" id="card_type" name="card_type" required>
                <option value="DKV" {{ $fuelCard->card_type == 'DKV' ? 'selected' : '' }}>DKV</option>
                <option value="Total" {{ $fuelCard->card_type == 'Total' ? 'selected' : '' }}>Total</option>
                <option value="Liquide" {{ $fuelCard->card_type == 'Liquide' ? 'selected' : '' }}>Liquide</option>
            </select>
        </div>
        <div class="form-group">
            <label for="acente_id">Driver</label>
            <select class="form-control" id="driver_id" name="acente_id" required>
                @foreach($drivers as $driver)
                    <option value="{{ $driver->id }}" {{ $fuelCard->acente_id == $driver->id ? 'selected' : '' }}>{{ $driver->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
    <label>Vehicule</label>

    <select name="vehicule_id" class="form-control select2">

        <option value="">Aucun vehicule</option>

        @foreach($vehicules as $vehicule)
            <option value="{{ $vehicule->id }}"
                {{ $fuelCard->vehicule_id == $vehicule->id ? 'selected' : '' }}>
                {{ $vehicule->name }}
            </option>
        @endforeach

    </select>
</div>

        <button type="submit" class="btn btn-primary">Update Fuel Card</button>
    </form>
</div>
@endsection
