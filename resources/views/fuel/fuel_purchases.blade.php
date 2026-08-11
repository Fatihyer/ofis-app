@extends('layouts.app')

@section('content')
    <div style="width: 80%; margin: auto;">

        <!-- Date Filter Form -->
        <form method="GET" action="{{ url('/fuel-purchases-chart') }}" style="margin-bottom: 20px;">
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}">
            
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}">
            
            <button type="submit">Filter</button>
        </form>

        <canvas id="fuelChart"></canvas>
    </div>
    @endsection


    @section('scripts') 
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctx = document.getElementById('fuelChart').getContext('2d');

    const data = {
        labels: @json($fuelData->pluck('vehicle_name')),
        datasets: [{
            label: 'Fuel Purchases per Vehicle',
            data: @json($fuelData->pluck('total_amount')),
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    };

    new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Fuel Purchases per Vehicle'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

@endsection
