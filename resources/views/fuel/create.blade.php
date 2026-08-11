@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Add Fuel Card</h1>
    <form action="{{ route('fuel.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="card_number">Card Number</label>
            <input type="text" class="form-control" id="card_number" name="card_number" required>
        </div>
        <div class="form-group">
            <label for="card_type">Card Type</label>
            <select class="form-control" id="card_type" name="card_type" required>
                <option value="DKV">DKV</option>
                <option value="Total">Total</option>
                <option value="Liquide">Liquide</option>
       
            </select>
        </div>
        <div class="form-group">
            <label for="driver_id">Driver</label>
            <select class="form-control" id="acente_id" name="acente_id" required>
                @foreach($drivers as $driver)
                    <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
    <label>Vehicule</label>

    <select name="vehicule_id" class="form-control select2">

        <option value="">Aucun vehicule</option>

        @foreach($vehicules as $vehicule)
            <option value="{{ $vehicule->id }}">
                {{ $vehicule->name }}
            </option>
        @endforeach

    </select>
</div>
        <button type="submit" class="btn btn-primary">Add Fuel Card</button>
    </form>
</div>
@endsection
