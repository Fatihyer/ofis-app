@php
    $fuelOptions = json_decode((string) \App\Models\Option::where('name', 'talepFuelConsumptions')->value('value'), true) ?: [];
    $defaultFuelConsumption = (float) ($fuelOptions['DEFAULT'] ?? 8);
    $defaultFuelConsumption = $defaultFuelConsumption > 0 ? $defaultFuelConsumption : 8;
    $defaultFuelPrice = (float) (\App\Models\Option::where('name', 'talepFuelPricePerLiter')->value('value') ?: 1.90);
    $defaultFuelPrice = $defaultFuelPrice > 0 ? $defaultFuelPrice : 1.90;
    $summaryDays = isset($talep) ? $talep->days->count() : 0;
    $summaryDistanceMeters = isset($talep) ? (int) $talep->days->sum('distance_meters') : 0;
    $summaryDistanceKm = $summaryDistanceMeters > 0 ? round($summaryDistanceMeters / 1000, 1) : 0;
    $summaryAverageKm = $summaryDays > 0 && $summaryDistanceKm > 0 ? round($summaryDistanceKm / $summaryDays, 1) : 0;
    $summaryFuelCost = isset($talep) ? (float) $talep->days->sum('fuel_amount') : 0;
    if ($summaryFuelCost <= 0 && $summaryDistanceKm > 0) {
        $summaryFuelCost = round(($summaryDistanceKm * $defaultFuelConsumption / 100) * $defaultFuelPrice, 2);
    }
    $summaryTolls = isset($talep) ? (float) $talep->days->sum('toll_amount') : 0;
@endphp
<div class="card">
    <div class="card-header">
        Carte / Itinéraire
    </div>

    <div class="card-body">
        <div class="row mb-3 route-summary" data-fuel-consumption="{{ $defaultFuelConsumption }}" data-fuel-price="{{ $defaultFuelPrice }}">
            <div class="col-md-6 col-xl mb-2">
                <div class="border rounded p-2 h-100">
                    <small class="text-muted">Nombre de jours</small>
                    <div class="fw-bold" id="summary_days">{{ $summaryDays ?: '-' }}</div>
                </div>
            </div>
            <div class="col-md-6 col-xl mb-2">
                <div class="border rounded p-2 h-100">
                    <small class="text-muted">Distance totale</small>
                    <div class="fw-bold" id="summary_total_distance">{{ $summaryDistanceKm > 0 ? number_format($summaryDistanceKm, 1, ',', ' ') . ' km' : '-' }}</div>
                </div>
            </div>
            <div class="col-md-6 col-xl mb-2">
                <div class="border rounded p-2 h-100">
                    <small class="text-muted">Distance moyenne / jour</small>
                    <div class="fw-bold" id="summary_average_distance">{{ $summaryAverageKm > 0 ? $summaryAverageKm . ' km' : '-' }}</div>
                </div>
            </div>
            <div class="col-md-6 col-xl mb-2">
                <div class="border rounded p-2 h-100">
                    <small class="text-muted">Carburant estimé</small>
                    <div class="fw-bold" id="summary_fuel">{{ $summaryFuelCost > 0 ? number_format($summaryFuelCost, 2, ',', ' ') . ' EUR' : '-' }}</div>
                </div>
            </div>
            <div class="col-md-6 col-xl mb-2">
                <div class="border rounded p-2 h-100">
                    <small class="text-muted">Péages</small>
                    <div class="fw-bold" id="summary_tolls">{{ $summaryTolls > 0 ? number_format($summaryTolls, 2, ',', ' ') . ' EUR' : '-' }}</div>
                </div>
            </div>
        </div>
        <div id="route_preview_error" class="alert alert-warning d-none mb-3"></div>

        <div id="map" style="width: 100%; height: 520px; background: #f8f9fa; border:1px solid #ddd;"></div>

        <div class="mt-3">
            <div class="mb-2">
                <strong>Début principal :</strong>
                <span id="main_start_preview">-</span>
            </div>

            <div class="mb-2">
                <strong>Fin principale :</strong>
                <span id="main_end_preview">-</span>
            </div>

            <div class="mb-2">
                <strong>Étapes intermédiaires :</strong>
                <span id="main_waypoints_preview">-</span>
            </div>

            <div class="mb-2">
                <strong>Distance :</strong>
                <span id="route_distance">-</span>
            </div>

            <div class="mb-2">
                <strong>Durée :</strong>
                <span id="route_duration">-</span>
            </div>

            <div class="mb-2">
                <strong>Durée trafic :</strong>
                <span id="route_traffic_duration">-</span>
            </div>
        </div>

        <div class="mt-3">
            <button type="button" id="calculateRouteBtn" class="btn btn-primary">
                Calculer l’itinéraire principal
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let map;
    let routePolyline = null;
    let routeMarkers = [];
    let geocoder = null;

    function showRoutePreviewError(message) {
        const alert = document.getElementById('route_preview_error');
        const text = message || "Une erreur est survenue lors du calcul de l'itinéraire.";

        if (alert) {
            alert.textContent = text;
            alert.classList.remove('d-none');
            return;
        }

        window.alert(text);
    }

    function clearRoutePreviewError() {
        const alert = document.getElementById('route_preview_error');

        if (!alert) return;

        alert.textContent = '';
        alert.classList.add('d-none');
    }

    function initMap() {
        if (typeof google === 'undefined' || !google.maps) {
            console.error('Google Maps non chargé.');
            return;
        }

        map = new google.maps.Map(document.getElementById('map'), {
            center: { lat: 48.8566, lng: 2.3522 },
            zoom: 7
        });

        geocoder = new google.maps.Geocoder();
    }

    function getOperationRows() {
        return Array.from(document.querySelectorAll('.operation-row'));
    }

    function isUsableStop(value) {
        const normalized = (value || '').trim().toLowerCase();

        return normalized &&
            !['-', '--', '---', 'n/a', 'na', 'non defini', 'non défini', 'inconnu', 'unknown', 'null'].includes(normalized);
    }

    function getMainOrigin() {
        const rows = getOperationRows();
        if (!rows.length) return '';

        return rows[0].querySelector('.operation-pickup')?.value.trim() || '';
    }

    function getMainDestination() {
        const rows = getOperationRows();
        if (!rows.length) return '';

        return rows[rows.length - 1].querySelector('.operation-dropoff')?.value.trim() || '';
    }

    function getMainWaypoints() {
        const rows = getOperationRows();
        if (!rows.length) return [];

        const origin = getMainOrigin();
        const destination = getMainDestination();

        const rawStops = [];

        rows.forEach((row, index) => {
            const pickup = row.querySelector('.operation-pickup')?.value.trim() || '';
            const dropoff = row.querySelector('.operation-dropoff')?.value.trim() || '';
            const rowWaypoints = Array.from(row.querySelectorAll('.operation-waypoint'))
                .map(input => input.value.trim())
                .filter(isUsableStop);

            if (index > 0 && isUsableStop(pickup)) {
                rawStops.push(pickup);
            }

            rowWaypoints.forEach(waypoint => rawStops.push(waypoint));

            if (index < rows.length - 1 && isUsableStop(dropoff)) {
                rawStops.push(dropoff);
            }
        });

        const cleaned = rawStops.filter(stop => isUsableStop(stop) && stop !== origin && stop !== destination);

        return [...new Set(cleaned)];
    }

   function refreshMainPreview() {
    const origin = getMainOrigin();
    const destination = getMainDestination();
    const waypoints = getMainWaypoints();

    document.getElementById('main_start_preview').innerText = origin || '-';
    document.getElementById('main_end_preview').innerText = destination || '-';
    document.getElementById('main_waypoints_preview').innerText =
        waypoints.length ? waypoints.join(' → ') : '-';
}

    function formatMoney(value) {
        return value > 0 ? value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' EUR' : '-';
    }

    function calculateFuel(distanceMeters) {
        const summary = document.querySelector('.route-summary');
        const consumption = Number(summary?.dataset.fuelConsumption || 8);
        const fuelPrice = Number(summary?.dataset.fuelPrice || 1.90);
        const km = Number(distanceMeters || 0) / 1000;
        const liters = km > 0 ? (km * consumption / 100) : 0;

        return {
            liters: liters,
            amount: liters > 0 ? liters * fuelPrice : 0,
        };
    }

    function updateOperationFuel(row, distanceMeters) {
        const fuel = calculateFuel(distanceMeters);
        const fuelAmountInput = row.querySelector('.operation-fuel-amount');
        const fuelLitersInput = row.querySelector('.operation-fuel-liters');

        if (fuelAmountInput) fuelAmountInput.value = fuel.amount > 0 ? fuel.amount.toFixed(2) : '';
        if (fuelLitersInput) fuelLitersInput.value = fuel.liters > 0 ? fuel.liters.toFixed(2) : '';
    }

    function updateRouteSummary() {
        const rows = getOperationRows();
        const dayCount = rows.length;
        let totalMeters = 0;
        let totalTolls = 0;
        let totalFuelCost = 0;

        rows.forEach(row => {
            const distanceInput = row.querySelector('input[name$="[distance_meters]"]');
            const tollInput = row.querySelector('.operation-toll');
            const fuelInput = row.querySelector('.operation-fuel-amount');
            totalMeters += Number(distanceInput?.value || row.dataset.distanceMeters || 0);
            totalTolls += Number(tollInput?.value || row.dataset.tollAmount || 0);
            totalFuelCost += Number(fuelInput?.value || row.dataset.fuelAmount || 0);
        });

        const totalKm = totalMeters / 1000;
        const averageKm = dayCount > 0 && totalKm > 0 ? totalKm / dayCount : 0;
        const fuelCost = totalFuelCost > 0 ? totalFuelCost : calculateFuel(totalMeters).amount;

        const daysEl = document.getElementById('summary_days');
        const totalDistanceEl = document.getElementById('summary_total_distance');
        const averageEl = document.getElementById('summary_average_distance');
        const fuelEl = document.getElementById('summary_fuel');
        const tollsEl = document.getElementById('summary_tolls');

        if (daysEl) daysEl.innerText = dayCount || '-';
        if (totalDistanceEl) totalDistanceEl.innerText = totalKm > 0 ? totalKm.toFixed(1).replace('.', ',') + ' km' : '-';
        if (averageEl) averageEl.innerText = averageKm > 0 ? averageKm.toFixed(1).replace('.', ',') + ' km' : '-';
        if (fuelEl) fuelEl.innerText = formatMoney(fuelCost);
        if (tollsEl) tollsEl.innerText = formatMoney(totalTolls);
        if (window.updateTalepOperationTotals) window.updateTalepOperationTotals();
    }

    function clearMapRoute() {
        if (routePolyline) {
            routePolyline.setMap(null);
            routePolyline = null;
        }

        routeMarkers.forEach(marker => marker.setMap(null));
        routeMarkers = [];
    }

    function geocodeAddress(address) {
        return new Promise((resolve, reject) => {
            if (!geocoder) {
                reject(new Error('Geocoder non initialisé'));
                return;
            }

            geocoder.geocode({ address: address }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    resolve(results[0].geometry.location);
                } else {
                    reject(new Error('Geocoding failed for: ' + address + ' / ' + status));
                }
            });
        });
    }

    async function drawRouteWithMarkers(encoded, origin, waypoints, destination) {
        if (!map) return;

        if (
            typeof google === 'undefined' ||
            !google.maps ||
            !google.maps.geometry ||
            !google.maps.geometry.encoding
        ) {
            console.error('Library geometry manquante.');
            return;
        }

        clearMapRoute();

        const path = google.maps.geometry.encoding.decodePath(encoded);

        routePolyline = new google.maps.Polyline({
            path: path,
            geodesic: true,
            strokeColor: '#007bff',
            strokeOpacity: 1,
            strokeWeight: 4
        });

        routePolyline.setMap(map);

        const allStops = [origin, ...waypoints, destination];
        const labels = [];

        for (let i = 0; i < allStops.length; i++) {
            if (i === 0) {
                labels.push('A');
            } else if (i === allStops.length - 1) {
                labels.push('B');
            } else {
                labels.push(String(i));
            }
        }

        for (let i = 0; i < allStops.length; i++) {
            const stopAddress = allStops[i];
            if (!stopAddress) continue;

            try {
                const position = await geocodeAddress(stopAddress);

                const marker = new google.maps.Marker({
                    position: position,
                    map: map,
                    label: labels[i],
                    title: stopAddress
                });

                routeMarkers.push(marker);
            } catch (error) {
                console.error('Marker geocode error:', error);
            }
        }

        const bounds = new google.maps.LatLngBounds();
        path.forEach(point => bounds.extend(point));
        map.fitBounds(bounds);
    }

    function fetchRoute(origin, destination, waypoints = []) {
        clearRoutePreviewError();

        return fetch("{{ route('talepler.routePreview') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            },
            body: JSON.stringify({
                origin: origin,
                destination: destination,
                waypoints: waypoints
            })
        })
        .then(async response => {
            let data = {};

            try {
                data = await response.json();
            } catch (e) {
                throw new Error('Réponse invalide du serveur');
            }

            if (!response.ok) {
                console.error('API ERROR:', data);
                throw new Error(data.error_detail || data.message || 'Erreur serveur');
            }

            console.log('ROUTE OK:', data);
            return data;
        });
    }

    document.addEventListener('input', function (e) {
        if (
            e.target.classList.contains('operation-pickup') ||
            e.target.classList.contains('operation-dropoff') ||
            e.target.classList.contains('operation-waypoint')
        ) {
            refreshMainPreview();
        }
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('removeOperationRow') || e.target.classList.contains('addOperationWaypoint') || e.target.classList.contains('removeOperationWaypoint') || e.target.id === 'addOperationRow') {
            setTimeout(refreshMainPreview, 100);
        }

        if (e.target.classList.contains('calculateOperationRoute')) {
            const row = e.target.closest('.operation-row');
            if (!row) return;

            const rowIndex = row.dataset.index;
            const origin = row.querySelector('.operation-pickup')?.value.trim() || '';
            const destination = row.querySelector('.operation-dropoff')?.value.trim() || '';
            const waypoints = Array.from(row.querySelectorAll('.operation-waypoint'))
                .map(input => input.value.trim())
                .filter(isUsableStop);

            if (!origin || !destination) {
                alert('Veuillez renseigner le début et la fin.');
                return;
            }

            fetchRoute(origin, destination, waypoints)
                .then(res => {
                    const distanceEl = row.querySelector('.operation-distance');
                    const durationEl = row.querySelector('.operation-duration');

                    if (distanceEl) distanceEl.innerText = res.distance_text ?? '-';
                    if (durationEl) durationEl.innerText = res.duration_text ?? '-';

                    const distanceInput = row.querySelector(`input[name="operations[${rowIndex}][distance_meters]"]`);
                    const durationInput = row.querySelector(`input[name="operations[${rowIndex}][duration_seconds]"]`);
                    const trafficInput = row.querySelector(`input[name="operations[${rowIndex}][traffic_duration_seconds]"]`);
                    const polylineInput = row.querySelector(`input[name="operations[${rowIndex}][polyline]"]`);
                    const tollInput = row.querySelector(`input[name="operations[${rowIndex}][toll_amount]"]`);
                    const tollCurrencyInput = row.querySelector(`input[name="operations[${rowIndex}][toll_currency]"]`);

                    if (distanceInput) distanceInput.value = res.distance_meters ?? '';
                    if (durationInput) durationInput.value = res.duration_seconds ?? '';
                    if (trafficInput) trafficInput.value = res.traffic_duration_seconds ?? '';
                    if (polylineInput) polylineInput.value = res.polyline ?? '';
                    if (tollInput && res.toll_amount !== null && res.toll_amount !== undefined) tollInput.value = Number(res.toll_amount).toFixed(2);
                    if (tollCurrencyInput) tollCurrencyInput.value = res.toll_currency ?? 'EUR';
                    updateOperationFuel(row, res.distance_meters ?? 0);
                    row.dataset.distanceMeters = res.distance_meters ?? '';
                    row.dataset.tollAmount = res.toll_amount ?? tollInput?.value ?? '';
                    row.dataset.fuelAmount = row.querySelector('.operation-fuel-amount')?.value ?? '';
                    updateRouteSummary();
                })
                .catch(err => {
                    console.error('ROW ROUTE ERROR:', err);
                    showRoutePreviewError(err.message);
                });
        }
    });

    const mainButton = document.getElementById('calculateRouteBtn');
    if (mainButton) {
        mainButton.addEventListener('click', function () {
            const origin = getMainOrigin();
            const destination = getMainDestination();
            const waypoints = getMainWaypoints();

            console.log('MAIN ORIGIN:', origin);
            console.log('MAIN DESTINATION:', destination);
            console.log('MAIN WAYPOINTS:', waypoints);

            if (!origin || !destination) {
                alert('Veuillez renseigner le début de la première opération et la fin de la dernière opération.');
                return;
            }

            fetchRoute(origin, destination, waypoints)
                .then(async res => {
                    document.getElementById('route_distance').innerText = res.distance_text ?? '-';
                    document.getElementById('route_duration').innerText = res.duration_text ?? '-';
                    document.getElementById('route_traffic_duration').innerText = res.traffic_duration_text ?? '-';

                    if (res.polyline) {
                        await drawRouteWithMarkers(res.polyline, origin, waypoints, destination);
                    }
                })
                .catch(err => {
                    console.error('MAIN ROUTE ERROR:', err);
                    showRoutePreviewError(err.message);
                });
        });
    }

    function bootRoutePreview(retries = 0) {
        if (typeof google === 'undefined' || !google.maps) {
            if (retries < 40) {
                setTimeout(() => bootRoutePreview(retries + 1), 250);
                return;
            }

            showRoutePreviewError("Google Maps ne s'est pas chargé. Vérifiez la clé API Google Maps.");
            refreshMainPreview();
            updateRouteSummary();
            return;
        }

        initMap();
        refreshMainPreview();
        updateRouteSummary();

        @if(!empty($autoCalculate))
            setTimeout(function () {
                const origin = getMainOrigin();
                const destination = getMainDestination();

                if (origin && destination && mainButton) {
                    mainButton.click();
                }
            }, 600);
        @endif
    }

    bootRoutePreview();
});
</script>
