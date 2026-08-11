@extends('layouts.app')

@section('content')


<div class="container">
    <h1>Add Purchase</h1>

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    <form action="{{ route('fuel.addPurchase') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="fuel_card_id">Fuel Card</label>
            <select name="fuel_card_id" id="fuel_card_id" class="form-control" required>
                <option value="">Select Fuel Card</option>
                @foreach($fuelCards as $fuelCard)
                    <option value="{{ $fuelCard->id }}">{{ $fuelCard->card_number }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="driver_id">Driver</label>
            <select name="driver_id" id="driver_id" class="form-control" required>
                <option value="">Select Driver</option>
                @foreach($drivers as $driver)
                    <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="vehicule_id">Vehicule</label>
            <select name="vehicule_id" id="vehicule_id" class="form-control" required>
                <option value="">Select Vehicule</option>
                @foreach($vehicules as $vehicule)
                    <option value="{{$vehicule->id}}">{{ $vehicule->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="purchase_date">Purchase Date</label>
            <input type="datetime-local" name="purchase_date" id="purchase_date" value="" required class="form-control">
        </div>
        <div class="form-group">
            <label for="amount">Amount</label>
            <input type="number" step="0.01" name="amount" id="amount" class="form-control" placeholder="00.00" required>
        </div>
        <div class="form-group">
            <label for="kilometer">Kilometer</label>
            <input type="number"  name="kilometer" id="kilometer" class="form-control"  required>
        </div>

        <div class="form-group">
            <label for="amount">Comment</label>
            <input type="text" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">Add Purchase</button>
    </form>
</div>
@endsection