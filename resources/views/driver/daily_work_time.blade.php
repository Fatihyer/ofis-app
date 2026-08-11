@extends('layouts.app')

@section('style')
    <title>Driver Work Time Calendar</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" rel="stylesheet" />
@endsection

@section('content')
<div class="container mt-4">
    <h2 class="mb-4">Driver Work Time Calendar</h2>

    <form method="GET" action="{{ url('/driver-calendar') }}" class="row g-3 mb-4">
        <div class="col-md-3">
            <label for="year" class="form-label">Select Year:</label>
            <select name="year" id="year" class="form-select">
                @for ($y = date('Y'); $y >= date('Y') - 5; $y--)
                    <option value="{{ $y }}" {{ $y == $selectedYear ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>

        <div class="col-md-3">
            <label for="month" class="form-label">Select Month:</label>
            <select name="month" id="month" class="form-select">
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $m == $selectedMonth ? 'selected' : '' }}>
                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                    </option>
                @endfor
            </select>
        </div>

        <div class="col-md-4">
            <label for="driver_id" class="form-label">Select Driver:</label>
            <select name="driver_id" id="driver_id" class="form-select">
                <option value="">All</option>
                @foreach ($drivers as $id => $name)
                    <option value="{{ $id }}" {{ $id == $selectedDriverId ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <div id="calendar" class="mb-4"></div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Summary for {{ date('F', mktime(0, 0, 0, $selectedMonth, 1)) }} {{ $selectedYear }}</h5>
            <p class="card-text">Worked Days: <strong>{{ $workedDays }}</strong></p>
            <p class="card-text">Not Worked Days: <strong>{{ $notWorkedDays }}</strong></p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>

<script>
  $(document).ready(function () {
    $('#calendar').fullCalendar({
        header: {
            left: '',
            center: 'title',
            right: ''
        },
        events: @json($events),
        defaultDate: '{{ $selectedYear }}-{{ str_pad($selectedMonth, 2, '0', STR_PAD_LEFT) }}-01',

        // Tooltip için description gösterimi
        eventRender: function(event, element) {
            if (event.description) {
                element.attr('title', event.description);
            }
        }
    });
});
</script>
@endsection
