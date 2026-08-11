@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Fuel Cards</h1>
    <a href="{{ route('fuel.create') }}" class="btn btn-primary">Add Fuel Card</a> <a href="{{ route('fuel.createPurchase') }}" class="btn btn-info btn-sm">Add Fuel</a>
    <table class="table">
        <thead>
            <tr>
            <th>id</th>    
            <th>Card Number</th>
                <th>Card Type</th>
                <th>Driver</th>
               <th>#</th>
                
                
            </tr>
        </thead>
        <tbody>
            @foreach($fuelCards as $fuelCard)
                <tr>
                    <td>{{ $fuelCard->id }}</td>
                    <td>       <a href="{{ route('fuel.showPurchases', $fuelCard->id ) }}">
                        {{ $fuelCard->card_number }}</a> </td>
                    <td>{{ $fuelCard->card_type }}</td>
          <td>{{ $fuelCard->acente?->name ?? '-' }}</td>
<td>{{ $fuelCard->vehicule?->name ?? '-' }}</td>
                    <td><a href="{{ route('fuel.edit', $fuelCard->id ) }}" class="btn btn-info">Edit</a>

                    @if($fuelCard->deleted_at) 
                    
                <a href="{{ route('fuel.recover', $fuelCard->id) }}" class="btn btn-success">Recover</a>
                     

                    @else
                    <form action="{{ route('fuel.deleteCard',$fuelCard->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger" type="submit">Delete</button>
                        </form>
                    @endif
                    

                </td> 
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
