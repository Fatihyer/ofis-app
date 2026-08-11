@extends('layouts.app')

@section('content')

<div class="container mt-5">
    <h2>Filter Fuel Purchases</h2>
    <a href="{{ route('fuel.createPurchase') }}" class="btn btn-success">Add Fuel</a>
    <form action="{{ route('fuel.list')}}" method="GET" class="row g-3 mb-4">
        @csrf
        <div class="col-md-2">
            <label for="start_date" class="form-label">Start Date</label>
            <input type="date" id="start_date" name="start_date" class="form-control" value="{{ request('start_date') }}" required>
        </div>
        <div class="col-md-2">
            <label for="end_date" class="form-label">End Date</label>
            <input type="date" id="end_date" name="end_date" class="form-control" value="{{ request('end_date') }}" required>
        </div>
        <div class="col-md-2">
            <label for="card_type" class="form-label">Card Type</label>
            <select id="card_type" name="card_type" class="form-select form-control">
                <option value="">Select Carte</option>
                <option value="DKV" {{ request('card_type') == 'DKV' ? 'selected' : '' }}>DKV</option>
                <option value="Total" {{ request('card_type') == 'Total' ? 'selected' : '' }}>Total</option>
                <option value="Liquide" {{ request('card_type') == 'Liquide' ? 'selected' : '' }}>Liquide</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="acente_id" class="form-label">Driver (Acente)</label>
            <select id="acente_id" name="acente_id" class="form-select form-control">
                <option value="">Select Driver</option>
                @foreach ($acentes as $acente)
                    <option value="{{ $acente->id }}" {{ request('acente_id') == $acente->id ? 'selected' : '' }}>{{ $acente->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="vehicule_id" class="form-label">Vehicule</label>
            <select id="vehicule_id" name="vehicule_id" class="form-select form-control">
                <option value="">Select vehicule</option>
                @foreach ($vehicules as $vehicule)
                    <option value="{{ $vehicule->id }}" {{ request('vehicule_id') == $vehicule->id ? 'selected' : '' }}>{{ $vehicule->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>

    <h2>Fuel Purchases</h2>
    <table class="table table-striped">
    @php
    $currentSort = request('sort_by');
    $currentOrder = request('order', 'asc');
    $reverseOrder = $currentOrder === 'asc' ? 'desc' : 'asc';

    function sort_icon($column, $currentSort, $currentOrder) {
        if ($currentSort !== $column) return '';
        return $currentOrder === 'asc' ? '↑' : '↓';
    }
@endphp
    <thead>
    <tr>
        <th>
            <a href="{{ route('fuel.list', array_merge(request()->all(), ['sort_by' => 'fuelCard.card_number', 'order' => $reverseOrder])) }}">
                Fuel Card Number {{ sort_icon('fuelCard.card_number', $currentSort, $currentOrder) }}
            </a>
        </th>
        <th>
            <a href="{{ route('fuel.list', array_merge(request()->all(), ['sort_by' => 'fuelCard.acente.name', 'order' => $reverseOrder])) }}">
                Driver {{ sort_icon('fuelCard.acente.name', $currentSort, $currentOrder) }}
            </a>
        </th>
        <th>
            <a href="{{ route('fuel.list', array_merge(request()->all(), ['sort_by' => 'fuelCard.card_type', 'order' => $reverseOrder])) }}">
                Card Type {{ sort_icon('fuelCard.card_type', $currentSort, $currentOrder) }}
            </a>
        </th>
        <th>
            <a href="{{ route('fuel.list', array_merge(request()->all(), ['sort_by' => 'produit', 'order' => $reverseOrder])) }}">
                Produit {{ sort_icon('produit', $currentSort, $currentOrder) }}
            </a>
        </th>
        <th>
            <a href="{{ route('fuel.list', array_merge(request()->all(), ['sort_by' => 'kilometer', 'order' => $reverseOrder])) }}">
                Kilometer {{ sort_icon('kilometer', $currentSort, $currentOrder) }}
            </a>
        </th>
        <th>
            <a href="{{ route('fuel.list', array_merge(request()->all(), ['sort_by' => 'volume', 'order' => $reverseOrder])) }}">
                Volume {{ sort_icon('volume', $currentSort, $currentOrder) }}
            </a>
        </th>
        <th>
            <a href="{{ route('fuel.list', array_merge(request()->all(), ['sort_by' => 'amount', 'order' => $reverseOrder])) }}">
                Amount {{ sort_icon('amount', $currentSort, $currentOrder) }}
            </a>
        </th>
        <th>
            <a href="{{ route('fuel.list', array_merge(request()->all(), ['sort_by' => 'purchase_date', 'order' => $reverseOrder])) }}">
                Purchase Date {{ sort_icon('purchase_date', $currentSort, $currentOrder) }}
            </a>
        </th>
        <th>
            <a href="{{ route('fuel.list', array_merge(request()->all(), ['sort_by' => 'vehicule.name', 'order' => $reverseOrder])) }}">
                Vehicle {{ sort_icon('vehicule.name', $currentSort, $currentOrder) }}
            </a>
        </th>
        <th>Edit</th>
    </tr>
</thead>
        <tbody>
            @foreach ($fuelPurchases as $purchase)
                <tr>
                    
                    <td>{{ $purchase->fuelCard->card_number }} </td>
                    <td>
                        @if (isset($purchase->acente))
                            {{ $purchase->acente->name }}
                       
                        @endif
                        <br> <small>
                         propriétaire:
                        {{ $purchase->fuelCard->acente->name }} </small>
                    </td>
                    <td>{{ $purchase->fuelCard->card_type }}</td>
                    <td>{{ $purchase->produit}}</td>
                    <td>{{ $purchase->kilometer}}</td>
                    <td>{{ $purchase->volume}}</td>
                    <td>{{ $purchase->amount }}</td>
                    <td>{{ $purchase->purchase_date }}</td>
                    <td>{{ $purchase->vehicule->name }}</td>
                    <td>
                       
                        <a href="{{route('fuel.editPurchase',[$purchase->fuelCard->id,$purchase->id])}}"> Edit</a>
                        <form action="{{ route('fuel.deletePurchase', [$purchase->fuelCard->id, $purchase->id]) }}" method="POST" style="display:inline;">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this purchase?')">
        Delete
    </button>
</form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="mt-4">
        <h3>Total Amount: {{ $totalAmount }} euros</h3>
    </div>
</div>

<div class="container mt-5">
    <!-- Existing content like the form and table -->

    <!-- Button to navigate to the Monthly Fuel Usage page -->
    <div class="mt-4">
        <a href="{{ route('fuel.monthlyFuelUsage') }}" class="btn btn-secondary">View Monthly Fuel Usage</a>
    </div>

    <!-- Existing content like the total amount -->
</div>
@endsection
