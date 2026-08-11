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
        .depot-pill, .vehicle-info-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 4px;
            padding: 2px 7px;
            border-radius: 999px;
            border: 1px solid #d7dee9;
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.2;
        }
        .vehicle-info-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 4px;
            margin-top: 4px;
        }
        .vehicle-info-pill.capacity {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }
        .vehicle-info-pill.pax {
            background: #ecfdf5;
            border-color: #bbf7d0;
            color: #166534;
        }
    </style>

    @section('content')
<div class="container">
    <h1 class="mt-4">Vehicle Usage Timeline for {{ $date->format('F j, Y') }}</h1>
    
    <div class="mb-3">
        <form method="POST" action="{{ route('vehiculescontrolviewByDate') }}">
            @csrf
            <div class="form-group">
                <label for="date">Select Date:</label>
                <input type="date" id="date" name="date" class="form-control" value="{{ $date->format('Y-m-d') }}" required>
            </div>
            <button type="submit" class="btn btn-primary">View Vehicle Usage</button>
        </form>
    </div>

    <div class="mb-3">
        <a href="{{ route('vehiculescontrolviewBySpecificDate', ['date' => $date->copy()->subDay()->format('Y-m-d')]) }}" class="btn btn-secondary">Day Before</a>
        <a class="btn btn-primary" href="{{route('vehiculescontrol')}}">Today</a>
        <a href="{{ route('vehiculescontrolviewBySpecificDate', ['date' => $date->copy()->addDay()->format('Y-m-d')]) }}" class="btn btn-secondary">Day After</a>
      
        <a class="btn btn-danger" href="{{route('driverUsage.index')}}">Driver Usage</a>
        <a href="{{ route('day') }}" class="btn btn-warning"> Shuttle</a>
    </div>

    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>Vehicle</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Pax</th>
                <th>Dossier</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vehicles as $vehiculeId => $transfers)
                @php($firstTransfer = $transfers->first())
                @foreach($transfers as $index => $transfer)
                    <tr>
                        @if ($index === 0)
                            <td rowspan="{{ $transfers->count() }}">
                                <a href="{{ route('vehicules.show', $transfer->vehicule_id) }}">
                                    {{ $firstTransfer->vehicle_name }}
                                </a>
                                @if($firstTransfer->vehicle_plate)
                                    <small class="text-muted d-block">{{ $firstTransfer->vehicle_plate }}</small>
                                @endif
                                <div class="vehicle-info-row">
                                    <span class="depot-pill">
                                        <i class="fas fa-warehouse"></i>
                                        {{ $firstTransfer->depot_code ?: ($firstTransfer->depot_name ?: 'Sans dépôt') }}
                                    </span>
                                    <span class="vehicle-info-pill capacity">
                                        <i class="fas fa-users"></i>
                                        Capacité {{ $firstTransfer->vehicle_capacity ?: '-' }}
                                    </span>
                                </div>
                            </td>
                        @endif
                        <td>{{ \Carbon\Carbon::parse($transfer->start_date)->format('H:i') }}</td>
                        <td>{{ \Carbon\Carbon::parse($transfer->end_date)->format('H:i') }}</td>
                        <td><strong>{{ $transfer->pax ?: '-' }}</strong></td>
                        <td>
                            @if($transfer->post_id)
                                <a href="{{ route('posts.show', $transfer->post_id) }}">#{{ $transfer->post_id }}</a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
    @if($unusedVehiclesWithLastUsage->isNotEmpty())
    <h2>Vehicles Not Used</h2>
    <ul>
        @foreach($unusedVehiclesWithLastUsage as $vehicle)
            <li>
                {{ $vehicle['name'] }}
                @if(!empty($vehicle['plaka']))
                    <small class="text-muted">({{ $vehicle['plaka'] }})</small>
                @endif
                <span class="depot-pill">
                    <i class="fas fa-warehouse"></i>
                    {{ $vehicle['depot_code'] ?: ($vehicle['depot_name'] ?: 'Sans dépôt') }}
                </span>
                <span class="vehicle-info-pill capacity">
                    <i class="fas fa-users"></i>
                    Capacité {{ $vehicle['capacity'] ?: '-' }}
                </span>
                @if ($vehicle['last_used_at'])
                 <small class="text-primary">  - Last Used: {{ \Carbon\Carbon::parse($vehicle['last_used_at'])->format('j F , Y H:i') }}</small> 
                <span class="text-danger"> ({{ \Carbon\Carbon::parse($vehicle['last_used_at'])->diffInDays(\Carbon\Carbon::today()) }} days ago)</span>
                @else
                <span class="text-danger">  - Never Used</span>
                @endif
            </li>
        @endforeach
    </ul>
@endif
</div>

@endsection