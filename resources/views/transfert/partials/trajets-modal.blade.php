<table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Tip</th>
            <th>Konum</th>
            <th>Google Adresi</th>
            <th>Zaman</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $index => $trajet)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $trajet->type }}</td>
            <td>{{ $trajet->from }}</td>
            <td>{{ $trajet->google_address }}</td>
            <td>{{ \Carbon\Carbon::parse($trajet->datetime)->format('d/m/Y H:i') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@if($origin && $destination)
<div class="mt-3">
    <iframe
        width="100%"
        height="400"
        style="border:0"
        loading="lazy"
        allowfullscreen
        referrerpolicy="no-referrer-when-downgrade"
        src="https://www.google.com/maps/embed/v1/directions?key={{ env('GOOGLE_MAPS_API_KEY') }}&origin={{ $origin }}&destination={{ $destination }}@if($waypoints)&waypoints={{ $waypoints }}@endif">
    </iframe>
</div>
@endif
