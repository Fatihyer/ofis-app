@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Fuel Purchases for Card Number {{ $fuelCard->card_number }}</h1>
    <a href="{{ route('fuel.index') }}" class="btn btn-secondary">Back to Fuel Cards</a>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Amount</th>
                <th>Purchase Date</th>
                <th>Vehicule</th>
                <th>Comment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fuelCard->fuelPurchases as $purchase)
                <tr>
                    <td>{{ $purchase->id }}</td>
                    <td>{{ $purchase->amount }}euros</td>
                    <td>{{ $purchase->purchase_date }}</td>
                    <td>{{ $purchase->vehicule->name }}</td>
                    <td>{{ $purchase->comment}}</td>
                    <td>
                        <a href="{{ route('fuel.editPurchase', [$fuelCard->id, $purchase->id]) }}" class="btn btn-info btn-sm">Edit</a>
                        <form action="{{ route('fuel.deletePurchase', [$fuelCard->id, $purchase->id]) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                  
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
