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
    <h1 class="mt-4">Driver Usage Timeline for {{ $date->format('F j, Y') }}</h1>
    
    <div class="mb-3">
        <form method="POST" action="{{ route('driverUsage.viewByDate') }}">
            @csrf
            <div class="form-group">
                <label for="date">Select Date:</label>
                <input type="date" id="date" name="date" class="form-control" value="{{ $date->format('Y-m-d') }}" required>
            </div>
            <button type="submit" class="btn btn-primary">View Transfers</button>
        </form>
    </div>

    <div class="mb-3">
        <a href="{{ route('driverUsage.viewBySpecificDate', ['date' => $date->copy()->subDay()->format('Y-m-d')]) }}" class="btn btn-secondary">Day Before</a>
        <a href="{{ route('driverUsage.index') }}" class="btn btn-primary">Today</a>
        <a href="{{ route('driverUsage.viewBySpecificDate', ['date' => $date->copy()->addDay()->format('Y-m-d')]) }}" class="btn btn-secondary">Day After</a>
        <a class="btn btn-danger" href="{{route('vehiculescontrol')}}">Vehicule Usage</a>
        <a href="{{ route('day') }}" class="btn btn-warning"> Shuttle</a>
       <a href="{{route('driver-calendar')}}" class="btn btn-info">Driver Work Time</a>
   
    </div>

    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>Driver</th>
                <th>Start Time</th>
                <th>End Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach($acentes as $acente_name => $transfers)
                @foreach($transfers as $index => $transfer)
                    <tr>
                        @if ($index === 0)
                            <td rowspan="{{ $transfers->count() }}">{{ $acente_name }}</td>
                        @endif
                        <td>{{ \Carbon\Carbon::parse($transfer->start_date)->format('H:i') }}</td>
                        <td>{{ \Carbon\Carbon::parse($transfer->end_date)->format('H:i') }}</td>
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