@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Fuel Cards</h1>
    <a href="{{ route('fuel.cardlist') }}" class="btn btn-danger">Card List</a> 
    <a href="{{ route('fuel.create') }}" class="btn btn-primary">Add Fuel Card</a> 
    <a href="{{ route('fuel.createPurchase') }}" class="btn btn-success">Add Fuel</a>

    <table class="table">
        <thead>
            <tr>
                <th>Card Number</th>
                <th>Card Type</th>
                <th>Driver</th>
                 <th>Vehicle</th>
                <th>Fuel Purchases</th>
                
            </tr>
        </thead>
        <tbody>
            @foreach($fuelCards as $fuelCard)
                <tr>
                    <td>       <a href="{{ route('fuel.showPurchases', $fuelCard->id ) }}">
                        {{ $fuelCard->card_number }}</a> <a href="{{ route('fuel.edit', $fuelCard->id ) }}" class="btn btn-secondary">Edit</a></td>
                    <td>{{ $fuelCard->card_type }}</td>
                 <td>{{ optional($fuelCard->acente)->name ?? '-' }}</td>
<td>{{ optional($fuelCard->vehicule)->name ?? '-' }}</td>
                    <td>
                        <form action="{{ route('fuel.addPurchasebycard', $fuelCard->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="number" name="amount" id="amount" placeholder="00.00" step="0.01" min="0" required class="form-control">
                                
                                <label for="kilometer">Kilometer</label>
                                <input type="number" name="kilometer" id="kilometer" required class="form-control">
                          

                                <label for="purchase_date">Purchase Date</label>
                                <input type="datetime-local" name="purchase_date" id="purchase_date" required class="form-control">
                            
                                <label for="vehicule_id">Vehicule</label>
                                <select name="vehicule_id" id="vehicule_id" class="form-control" required>
                                    @foreach($vehicules as $vehicule)
                                        <option value="{{ $vehicule->id }}">{{ $vehicule->name }}</option>
                                    @endforeach
                                </select>
                         
                            <button type="submit" class="btn btn-success">Add Purchase</button>
                        </div>
                        </form>


                       
                                
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
