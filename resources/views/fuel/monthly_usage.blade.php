@extends('layouts.app')

@section('content')


<form action="{{ route('fuel.monthlyFuelUsage')}}" method="GET" class="row g-3 mb-4">
    @csrf
    <div class="col-md-3">
        <label for="start_date" class="form-label">Start Date</label>
        <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $startDate }}" required>
    </div>
    <div class="col-md-3">
        <label for="end_date" class="form-label">End Date</label>
        <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $endDate }}" required>
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-primary">Filter</button>
    </div>
</form>
<div class="container mt-5">
    <h2>Monthly Fuel Usage Per Vehicle</h2>

    <!-- Debug: Print JSON Data -->
    <pre>{{ $chartDataJson }}</pre>

    <!-- Existing chart rendering code -->
    <canvas id="fuelChart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var ctx = document.getElementById('fuelChart').getContext('2d');

        // Parse the JSON data passed from Blade
        var chartData = JSON.parse({!! json_encode($chartDataJson) !!});

        console.log(chartData); // Log the JSON object to the console for inspection

        // Extracting labels from the chartData
        var labels = Object.keys(chartData[Object.keys(chartData).find(vehicle => Object.keys(chartData[vehicle]).length > 0)]);

        // Generate datasets from the parsed JSON data
        var datasets = Object.keys(chartData).map(vehicule => ({
            label: vehicule,
            data: labels.map(month => chartData[vehicule][month] || 0),
            fill: false,
            borderColor: getRandomColor(),
            tension: 0.1
        }));

        // Creating the chart
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets,
            },
            options: {
                responsive: true,
                scales: {
                    x: { title: { display: true, text: 'Month' } },
                    y: { title: { display: true, text: 'Total Amount' } },
                },
            },
        });

        function getRandomColor() {
            var letters = '0123456789ABCDEF';
            var color = '#';
            for (var i = 0; i < 6; i++) {
                color += letters[Math.floor(Math.random() * 16)];
            }
            return color;
        }
    });
</script>

@endsection
