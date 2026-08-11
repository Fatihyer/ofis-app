@extends('layouts.app')
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 5px;
            text-align: center;
        }
        .in-use {
            background-color: red;
            color: white;
        }
    </style>
        @section('content')
<div class="container">
    <h1 class="mt-4"> Board  {{ $date->format('F j, Y') }}</h1>
    
    <div class="mb-3">
        <form method="POST" action="{{ route('table-driverUsage.viewByDate') }}">
            @csrf
            <div class="form-group">
                <label for="date">Select Date:</label>
                <input type="date" id="date" name="date" class="form-control" value="{{ $date->format('Y-m-d') }}" required>
            </div>
            <button type="submit" class="btn btn-primary">View Transfers</button>
        </form>
    </div>

    <div class="mb-3">
        <a href="{{ route('table-driverUsage.viewBySpecificDate', ['date' => $date->copy()->subDay()->format('Y-m-d')]) }}" class="btn btn-secondary">Day Before</a>
        <a href="{{ route('table-driverUsage.index') }}" class="btn btn-primary">Today</a>
        <a href="{{ route('table-driverUsage.viewBySpecificDate', ['date' => $date->copy()->addDay()->format('Y-m-d')]) }}" class="btn btn-secondary">Day After</a>
        <a class="btn btn-danger" href="{{route('vehiculescontrol')}}">Vehicule Usage</a>
        <a href="{{ route('day') }}" class="btn btn-warning"> Shuttle</a>
       <a href="{{route('driver-calendar')}}" class="btn btn-info">Driver Work Time</a>
   
    </div>

    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>Driver</th>
                <th>Service Type</th>
                <th>Start Time</th>
                <th>End Time</th>
                 
            <th>Vehicule</th>

            </tr>
        </thead>
        <tbody>
            @foreach($acentes as $acente_name => $transfers)
                @foreach($transfers as $index => $transfer)
                    <tr>
                        @if ($index === 0)
                            <td rowspan="{{ $transfers->count() }}">{{ $acente_name }}</td>
                        @endif
                        <td>{{ $transfer->servicetype ? $transfer->servicetype->name : 'N/A' }}
                           <br> <small>{{$transfer->pax}}pax</small>

                        </td>
                        <td>
                            {{ \Carbon\Carbon::parse($transfer->start_date)->format('H:i') }}
                            @php
                                $matchingTrajet = $transfer->trajets->first(function($trajet) use ($transfer) {
                                    return \Carbon\Carbon::parse($trajet->datetime)->eq(\Carbon\Carbon::parse($transfer->start_date));
                                });
                            @endphp
                            @if($matchingTrajet)
                                <div style="font-size: 0.75em; color: gray;">
                                    
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($matchingTrajet->google_address) }}" target="_blank">
                                        {{ $matchingTrajet->from }} 🗺️
                                    </a>
                                </div>
                            @endif
                        </td>
                        <td>
    {{ \Carbon\Carbon::parse($transfer->end_date)->format('H:i') }}
    @php
        $matchingEndTrajet = $transfer->trajets->first(function($trajet) use ($transfer) {
            return \Carbon\Carbon::parse($trajet->datetime)->eq(\Carbon\Carbon::parse($transfer->end_date));
        });
    @endphp
    @if($matchingEndTrajet)
        <div style="font-size: 0.75em; color: gray;">
            → 
            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($matchingEndTrajet->google_address) }}" target="_blank">
                {{ $matchingEndTrajet->google_address }} 🗺️
            </a>
        </div>
    @endif
</td>
                        
                    <td>
                       {{ $transfer->vehicule ? $transfer->vehicule->name : 'N/A' }}
                        @if ($transfer->vehicule)
                            <div style="font-size: 0.75em; color: gray;">
                                <a href="{{ route('vehicules.show', $transfer->vehicule->id) }}">Details</a>
                            </div>
                        @endif
                    </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    @if($unusedAcentes->isNotEmpty())
    <h2>Driver Not Used</h2>
    <ul>
        @foreach($unusedAcentes as $id => $name)
            <li> <a href="{{ route('acentes.show', $id ) }}">  {{ $name }} </a> </li>
        @endforeach
    </ul>
@endif
</div>

</body>
</html>
@endsection