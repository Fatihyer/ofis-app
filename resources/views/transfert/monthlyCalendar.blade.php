@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Monthly Calendar for {{ $driver->name }}</h2>
    <h4>{{ \Carbon\Carbon::create($year, $month)->isoFormat('MMMM YYYY') }}</h4>

    <form action="{{ route('monthlyCalendar') }}" method="GET" class="mb-4">
        <div class="form-row">
            <div class="form-group col-md-3">
                <label for="driver_id">Driver</label>
                <select id="driver_id" name="driver_id" class="form-control" required>
                    @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}" {{ $driver->id == request('driver_id') ? 'selected' : '' }}>
                            {{ $driver->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-3">
                <label for="month">Month</label>
                <select id="month" name="month" class="form-control">
                    @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ $i == request('month', now()->month) ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($i)->isoFormat('MMMM') }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="form-group col-md-3">
                <label for="year">Year</label>
                <select id="year" name="year" class="form-control">
                    @for($i = now()->year; $i >= now()->year - 5; $i--)
                        <option value="{{ $i }}" {{ $i == request('year', now()->year) ? 'selected' : '' }}>
                            {{ $i }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="form-group col-md-3 align-self-end">
                <button type="submit" class="btn btn-primary">Show Calendar</button>
            </div>
        </div>
    </form>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Lundi</th>
                <th>Mardi</th>
                <th>Mercredi</th>
                <th>Jeudi</th>
                <th>Vendredi</th>
                <th>Samedi</th>
                <th>Dimanche</th>
            </tr>
        </thead>
        <tbody>
            @php
                $startOfMonth = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
                $endOfMonth = \Carbon\Carbon::create($year, $month, $daysInMonth)->endOfMonth();
                $currentDay = $startOfMonth->copy()->startOfWeek();
                $endOfCalendar = $endOfMonth->copy()->endOfWeek();
            @endphp
            @while ($currentDay <= $endOfCalendar)
                <tr>
                    @for ($i = 0; $i < 7; $i++)
                        @if ($currentDay->month != $month)
                            <td class="text-muted">{{ $currentDay->day }}</td>
                        @elseif (in_array($currentDay->format('Y-m-d'), $notWorkingDays))
                            <td class="bg-danger text-white">{{ $currentDay->day }}</td>
                        @else
                            <td>{{ $currentDay->day }}</td>
                        @endif
                        @php
                            $currentDay->addDay();
                        @endphp
                    @endfor
                </tr>
            @endwhile
        </tbody>
    </table>
</div>
@endsection
