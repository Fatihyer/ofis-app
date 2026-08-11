@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Fuel Purchase for Card Number {{ $fuelCard->card_number }}</h1>
    <form action="{{ route('fuel.updatePurchase', [$fuelCard->id, $purchase->id]) }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="amount">Amount</label>
            <input type="number" name="amount" id="amount" value="{{ $purchase->amount }}" step="0.01" min="0" required class="form-control">
        </div>
        <div class="form-group">
            <label for="amount">Driver</label>
            <select name="vehicule_id" id="vehicule_id" class="form-control" required>
                @foreach($drivers as $driver)
                    <option value="{{ $driver->id }}" {{ $purchase->driver_id == $driver->id ? 'selected' : '' }}>{{ $driver->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="amount">Carte Propriete:</label>
            {{$purchase->fuelCard->acente->name}}
        </div>

        <div class="form-group">
            <label for="kilometer">kilometer</label>
            <input type="number" name="kilometer" id="kilometer" value="{{ $purchase->kilometer }}"  required class="form-control">
        </div>
        <div class="form-group">
            <label for="purchase_date">Purchase Date</label>
            <input type="datetime-local" name="purchase_date" id="purchase_date" value="{{ $purchase->purchase_date }}" required class="form-control">
        </div>
        <div class="form-group">
            <label for="vehicule_id">Vehicule</label>
            <select name="vehicule_id" id="vehicule_id" class="form-control" required>
                @foreach($vehicules as $vehicule)
                    <option value="{{ $vehicule->id }}" {{ $purchase->vehicule_id == $vehicule->id ? 'selected' : '' }}>{{ $vehicule->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="amount">Comment</label>
            <input type="text" class="form-control" value="{{ $purchase->comment }}">
        </div>
        <button type="submit" class="btn btn-success">Update Purchase</button>
        <a href="{{ route('fuel.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
