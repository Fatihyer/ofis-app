@extends('layouts.app')

@section('content')

<div class="container">
    <h3 class="card-title">Vehicule</h3>
<a href="{{ url()->current() }}" class="btn btn-outline-secondary mb-3">
    <i class="fas fa-sync-alt"></i> Yenile
</a>
    <h4>Hermes Araç Listesi</h4>
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>UID</th>
                <th>Plaka</th>
                <th>Ad</th>
                <th>Şoför</th>
                <th>Son Durum</th>
                <th>Konum</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vehicles as $vehicle)
                <tr>
                    <td>{{ $vehicle['uid'] ?? '' }}</td>
                    <td>{{ $vehicle['immat'] ?? '' }}</td>
                    <td>{{ $vehicle['name'] ?? '' }}</td>
                   @php
    $uid = $vehicle['uid'] ?? null;
    $vehicule = $uid ? ($vehiculeMap[$uid] ?? null) : null;
@endphp

<td>
    <div><code>{{ $uid }}</code></div>

    @if($vehicule)
        <div class="small text-success">
            <span class="badge bg-success">OK</span>
            ID: {{ $vehicule->id }} — {{ $vehicule->name }}
        </div>
    @else
        <div class="small text-danger">
            <span class="badge bg-danger">Yok</span>
            vehicules tablosunda eşleşme yok
        </div>
    @endif
</td>

                   @php
                        $statusCode = $vehicle['last_position']['status']['status'] ?? null;
                        $statusColor = match($statusCode) {
                            0 => 'danger',  // Arrêt
                            1 => 'success', // Conduite
                            3 => 'warning', // Moteur tournant
                            default => 'secondary'
                        };

                        $statusLabel = $vehicle['last_position']['status']['label'] ?? 'Bilinmiyor';
                    @endphp

                    <td>
                        <span class="badge badge-{{ $statusColor }}">
                            {{ $statusLabel }}
                        </span>
                    </td>

                    <td>
                        @if(isset($vehicle['last_position']['latitude']))
                            <button class="btn btn-sm btn-outline-info show-map"
                            data-lat="{{ $vehicle['last_position']['latitude'] }}"
                            data-lng="{{ $vehicle['last_position']['longitude'] }}"
                            data-name="{{ $vehicle['name'] }}"
                            data-status="{{ $vehicle['last_position']['status']['status'] ?? -1 }}">
                            <i class="fas fa-map-marker-alt"></i> Harita
                        </button>

                        @else
                            <span class="text-muted">Yok</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="mapModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="mapTitle">Araç Konumu</h5></div>
      <div class="modal-body">
        <div id="map" style="height: 400px;"></div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('footer')
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}"></script>

<script>
    let map;
   $(document).on('click', '.show-map', function () {
    const lat = parseFloat($(this).data('lat'));
    const lng = parseFloat($(this).data('lng'));
    const name = $(this).data('name');
    const status = parseInt($(this).data('status'));

let iconUrl = '';
switch (status) {
    case 0: // Arrêt
        iconUrl = 'http://maps.google.com/mapfiles/ms/icons/red-dot.png';
        break;
    case 1: // Conduite
        iconUrl = 'http://maps.google.com/mapfiles/ms/icons/green-dot.png';
        break;
    case 3: // Moteur tournant
        iconUrl = 'http://maps.google.com/mapfiles/ms/icons/yellow-dot.png';
        break;
    default:
        iconUrl = 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png';
}

    const pos = { lat, lng };
    const map = new google.maps.Map(document.getElementById("map"), {
        zoom: 14,
        center: pos
    });

    new google.maps.Marker({
        position: pos,
        map: map,
        title: name + ' (' + status + ')',
        icon: iconUrl
    });

    $('#mapTitle').text(name + ' - ' + status);
    $('#mapModal').modal('show');
});

</script>
@endsection
