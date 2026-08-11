@php
    $routeTransfers = collect($post->transfer ?? [])
        ->sortBy('start_date')
        ->map(function ($transfer) {
            $points = collect($transfer->trajets ?? [])
                ->sortBy('datetime')
                ->map(function ($trajet) {
                    return trim((string) ($trajet->google_address ?: $trajet->from));
                })
                ->filter()
                ->values();

            if ($points->count() < 2) {
                return null;
            }

            $origin = $points->first();
            $destination = $points->last();
            $waypoints = $points->slice(1, max($points->count() - 2, 0))->values();
            $mapsUrl = 'https://www.google.com/maps/dir/?api=1'
                . '&origin=' . urlencode($origin)
                . '&destination=' . urlencode($destination);

            if ($waypoints->count() > 0) {
                $mapsUrl .= '&waypoints=' . urlencode($waypoints->implode('|'));
            }

            $startLabel = $transfer->start_date
                ? \Carbon\Carbon::parse($transfer->start_date)->format('d/m H:i')
                : 'Sans date';

            return [
                'id' => (int) $transfer->id,
                'label' => '#' . $transfer->id . ' · ' . $startLabel,
                'service' => optional($transfer->servicetype)->name ?: 'Transfert',
                'km' => (float) ($transfer->km ?? 0),
                'origin' => $origin,
                'destination' => $destination,
                'waypoints' => $waypoints->all(),
                'points' => $points->all(),
                'maps_url' => $mapsUrl,
            ];
        })
        ->filter()
        ->values();

    $routeTotalKm = $routeTransfers->sum('km');
@endphp

@if($routeTransfers->count() > 0)
    <div class="card post-route-map-card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span><i class="fas fa-route"></i> Carte itinéraire</span>
            <span class="text-muted small">
                {{ $routeTransfers->count() }} trajet(s)
                @if($routeTotalKm > 0)
                    · {{ number_format($routeTotalKm, 0, ',', ' ') }} km
                @endif
            </span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-8 mb-3 mb-lg-0">
                    <div id="postRouteMap" class="post-route-map"></div>
                    <div id="postRouteMapStatus" class="post-route-map-status text-muted small mt-2">
                        Chargement de la carte...
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="post-route-list">
                        @foreach($routeTransfers as $route)
                            <div class="post-route-item">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <strong>{{ $route['label'] }}</strong>
                                        <div class="text-muted small">{{ $route['service'] }}</div>
                                    </div>
                                    <a class="btn btn-outline-primary btn-sm" href="{{ $route['maps_url'] }}" target="_blank" rel="noopener">
                                        Carte
                                    </a>
                                </div>
                                <div class="small mt-2">
                                    <div><span class="text-muted">Départ:</span> {{ \Illuminate\Support\Str::limit($route['origin'], 70) }}</div>
                                    <div><span class="text-muted">Arrivée:</span> {{ \Illuminate\Support\Str::limit($route['destination'], 70) }}</div>
                                    @if($route['km'] > 0)
                                        <div class="text-muted">{{ number_format($route['km'], 0, ',', ' ') }} km</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.postRouteMapData = @json($routeTransfers);
        window.postRouteMapGoogleKey = @json(config('services.google_maps.api_key'));
    </script>
@endif
