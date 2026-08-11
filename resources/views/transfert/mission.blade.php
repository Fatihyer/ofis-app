@extends('layouts.kaptan')

@section('content')
@php
    $start = \Carbon\Carbon::parse($transfer->start_date);
    $end = \Carbon\Carbon::parse($transfer->end_date);
    $minutesBefore = (int) (optional(\App\Models\Option::where('name', 'surplaceMinBefore')->first())->value ?? 15);
    $ofisStart = $transfer->ofis_start ? \Carbon\Carbon::parse($transfer->ofis_start) : $start->copy()->subMinutes(105);
    $surplacePrevu = $start->copy()->subMinutes($minutesBefore);
    $driverName = optional($transfer->driver)->name ?: optional(Auth::user())->name;
    $vehiculeName = optional($transfer->vehicule)->name ?: 'Véhicule non défini';
    $vehiculePlate = optional($transfer->vehicule)->plaka;
    $serviceName = optional($transfer->servicetype)->name ?: 'Service';
    $clients = optional($transfer->post)->client ?? collect();
    $trajets = $transfer->trajets->sortBy('datetime')->values();
    $durationText = $start->diffInHours($end) . ' h';

    $step = 'start';
    if ($mission && $mission->finish_depot) {
        $step = 'closed';
    } elseif ($mission && $mission->finish) {
        $step = 'return_depot';
    } elseif ($mission && $mission->taked) {
        $step = 'dropoff';
    } elseif ($mission && $mission->surplace) {
        $step = 'onboard';
    } elseif ($mission && $mission->hareket) {
        $step = 'surplace';
    }

    $stepIndex = ['start' => 0, 'surplace' => 1, 'onboard' => 2, 'dropoff' => 3, 'return_depot' => 4, 'closed' => 5][$step];
    $publicMissionToken = $publicMissionToken ?? null;
    $nowParis = \Carbon\Carbon::now('Europe/Paris');
    $missionCanStartAt = $ofisStart->copy()->setTimezone('Europe/Paris');
    $isOfficeMissionUser = Auth::check() && (
        Auth::user()->hasAnyRole(['Superadmin', 'Admin', 'ofis', 'transport'])
        || Auth::user()->hasAnyPermission(['transfers.operations', 'ofis', 'transport'])
    );
    $linkedDriverIds = Auth::check() ? Auth::user()->acentes()->pluck('acentes.id')->map(fn ($id) => (int) $id)->toArray() : [];
    $isSecondDriverMissionView = Auth::check()
        && !$isOfficeMissionUser
        && \Illuminate\Support\Facades\Schema::hasColumn('transfers', 'second_driver_id')
        && in_array((int) ($transfer->second_driver_id ?? 0), $linkedDriverIds, true)
        && !in_array((int) ($transfer->driver_id ?? 0), $linkedDriverIds, true);
    $hasSecondDriver = \Illuminate\Support\Facades\Schema::hasColumn('transfers', 'second_driver_id') && !empty($transfer->second_driver_id);
    if (Auth::check() && !$isOfficeMissionUser) {
        $driverConfirmedForMission = $isSecondDriverMissionView
            ? !empty($transfer->second_driver_app_confirmed_at)
            : !empty($transfer->driver_app_confirmed_at);
    } else {
        $driverConfirmedForMission = !empty($transfer->driver_app_confirmed_at)
            && (!$hasSecondDriver || !empty($transfer->second_driver_app_confirmed_at));
    }
    $missionStartWarnings = [];
    if (!$driverConfirmedForMission) {
        $missionStartWarnings[] = $hasSecondDriver && (!Auth::check() || $isOfficeMissionUser)
            ? 'Le chauffeur principal et le 2e chauffeur doivent confirmer le service avant de démarrer la mission.'
            : 'Veuillez confirmer le service avant de démarrer la mission.';
    }
    if ($nowParis->lt($missionCanStartAt)) {
        $missionStartWarnings[] = 'La mission ne peut pas démarrer avant l’heure En route prévue: ' . $missionCanStartAt->format('d/m/Y H:i') . '.';
    }
    $canStartMissionNow = empty($missionStartWarnings);
    $publicPrimaryRefusedAt = ($publicMissionToken && ($transfer->driver_app_refused_at ?? null))
        ? \Carbon\Carbon::parse($transfer->driver_app_refused_at)
        : null;
    $publicDriverRole = $publicDriverRole ?? 'primary_driver';
    $publicHasSecondDriver = \Illuminate\Support\Facades\Schema::hasColumn('transfers', 'second_driver_id') && !empty($transfer->second_driver_id);
    $publicSecondRefusedAt = ($publicMissionToken && $publicHasSecondDriver && ($transfer->second_driver_app_refused_at ?? null))
        ? \Carbon\Carbon::parse($transfer->second_driver_app_refused_at)
        : null;
    $publicPendingConfirmationRole = null;
    if ($publicMissionToken) {
        if ($publicDriverRole === 'second_driver' && $publicHasSecondDriver && empty($transfer->second_driver_app_confirmed_at)) {
            $publicPendingConfirmationRole = 'second_driver';
        } elseif (empty($transfer->driver_app_confirmed_at)) {
            $publicPendingConfirmationRole = 'primary_driver';
        } elseif ($publicHasSecondDriver && empty($transfer->second_driver_app_confirmed_at)) {
            $publicPendingConfirmationRole = 'second_driver';
        }
    }
    $publicPendingDriverName = $publicPendingConfirmationRole === 'second_driver'
        ? optional($transfer->secondDriver)->name
        : optional($transfer->driver)->name;
    $publicPendingRefusedAt = $publicPendingConfirmationRole === 'second_driver'
        ? $publicSecondRefusedAt
        : $publicPrimaryRefusedAt;
@endphp

<style>
.driver-mission {
    max-width: 980px;
    margin: 0 auto;
    padding: 14px 10px 32px;
    color: #172033;
}
.mission-hero, .mission-card {
    background: #fff;
    border: 1px solid #dbe5f0;
    border-radius: 10px;
    box-shadow: 0 10px 28px rgba(15, 23, 42, .08);
}
.mission-hero {
    padding: 16px;
    border-left: 6px solid #0d6efd;
}
.mission-title {
    font-size: 22px;
    font-weight: 850;
    margin: 0;
    letter-spacing: 0;
}
.mission-subtitle {
    color: #64748b;
    font-size: 13px;
    font-weight: 700;
}
.mission-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border-radius: 999px;
    padding: 5px 9px;
    background: #eff6ff;
    color: #0d6efd;
    font-size: 12px;
    font-weight: 800;
}
.mission-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 9px;
    margin-top: 14px;
}
.mission-info {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f8fafc;
    padding: 9px 10px;
    min-width: 0;
}
.mission-label {
    display: block;
    color: #64748b;
    font-size: 11px;
    font-weight: 850;
    text-transform: uppercase;
}
.mission-value {
    display: block;
    color: #0f172a;
    font-size: 14px;
    font-weight: 800;
    overflow-wrap: anywhere;
}
.step-strip {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 6px;
    margin: 14px 0;
}
.step-item {
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    padding: 8px 6px;
    text-align: center;
    font-size: 11px;
    font-weight: 850;
}
.step-item.done {
    background: #dcfce7;
    border-color: #86efac;
    color: #166534;
}
.step-item.current {
    background: #dbeafe;
    border-color: #93c5fd;
    color: #1d4ed8;
}
.mission-card {
    margin-top: 12px;
    padding: 14px;
}
.card-title-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 10px;
}
.card-title-line h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 850;
}
.next-action {
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    background: #eff6ff;
    padding: 14px;
}
.next-action h3 {
    margin: 0 0 6px;
    font-size: 18px;
    font-weight: 850;
}
.big-action {
    min-height: 54px;
    font-size: 16px;
    font-weight: 850;
    border-radius: 10px;
}
.km-form {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 12px;
    background: #fbfdff;
}
.km-form label, .radio-title {
    font-size: 12px;
    font-weight: 850;
    color: #475569;
}
.route-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.route-list li {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr);
    gap: 10px;
    padding: 12px 0;
    border-bottom: 1px solid #edf2f7;
}
.route-list li:last-child { border-bottom: 0; }
.route-index {
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #e0f2fe;
    color: #0369a1;
    font-weight: 850;
}
.route-text {
    min-width: 0;
    overflow-wrap: anywhere;
}
.route-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 7px;
}
.passenger-list {
    display: grid;
    gap: 8px;
}
.passenger-row {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 9px;
    background: #f8fafc;
}
.alert-soft {
    border: 1px solid #fed7aa;
    background: #fff7ed;
    color: #9a3412;
    border-radius: 8px;
    padding: 10px;
    font-weight: 750;
}
@media (max-width: 767px) {
    .driver-mission { padding: 10px 8px 26px; }
    .mission-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .step-strip { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .mission-title { font-size: 20px; }
}
@media (max-width: 420px) {
    .mission-grid { grid-template-columns: 1fr; }
    .step-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>

<div class="driver-mission">
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($transfer->accueil)
        <div class="alert-soft mb-3">N'oubliez pas de prendre le panneau d'accueil ou les documents nécessaires au bureau.</div>
    @endif

    <section class="mission-hero">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <div class="mission-subtitle">Bonjour {{ $driverName ?: 'chauffeur' }}</div>
                <h1 class="mission-title">Mission #{{ $transfer->id }}</h1>
                <div class="mt-1 text-muted">{{ $serviceName }} - {{ $start->format('d/m/Y') }}</div>
            </div>
            <span class="mission-pill"><i class="fa fa-clock"></i> {{ $durationText }} prévues</span>
        </div>

        <div class="mission-grid">
            <div class="mission-info"><span class="mission-label">Départ dépôt</span><span class="mission-value">{{ $ofisStart->format('H:i') }}</span></div>
            <div class="mission-info"><span class="mission-label">Sur place</span><span class="mission-value">{{ $surplacePrevu->format('H:i') }}</span></div>
            <div class="mission-info"><span class="mission-label">Prise en charge</span><span class="mission-value">{{ $start->format('H:i') }}</span></div>
            <div class="mission-info"><span class="mission-label">Fin prévue</span><span class="mission-value">{{ $end->format($end->isSameDay($start) ? 'H:i' : 'd/m H:i') }}</span></div>
            <div class="mission-info"><span class="mission-label">Passagers</span><span class="mission-value">{{ $transfer->pax }} pax</span></div>
            <div class="mission-info"><span class="mission-label">Véhicule</span><span class="mission-value">{{ $vehiculeName }} @if($vehiculePlate)<small class="text-muted d-block">{{ $vehiculePlate }}</small>@endif</span></div>
            <div class="mission-info"><span class="mission-label">Départ</span><span class="mission-value">{{ $transfer->from }}</span></div>
            <div class="mission-info"><span class="mission-label">Arrivée</span><span class="mission-value">{{ $transfer->target }}</span></div>
        </div>
    </section>

    <div class="step-strip">
        @foreach([
            'Départ dépôt', 'Sur place', 'Client à bord', 'Dépose', 'Retour dépôt', 'Terminée'
        ] as $i => $label)
            <div class="step-item {{ $i < $stepIndex ? 'done' : '' }} {{ $i === $stepIndex ? 'current' : '' }}">{{ $label }}</div>
        @endforeach
    </div>

    <section class="mission-card">
        <div class="card-title-line"><h2>Action maintenant</h2><span class="badge badge-light border">Étape {{ min($stepIndex + 1, 6) }}/6</span></div>

        @if($publicMissionToken && $publicPendingConfirmationRole)
            <div class="next-action">
                <h3>Confirmation du service</h3>
                @if($publicPendingRefusedAt)
                    <div class="alert alert-danger mb-3">
                        Service refusé le {{ $publicPendingRefusedAt->format('d/m/Y H:i') }}.
                    </div>
                @else
                    <p class="mb-3 text-muted">
                        Merci de confirmer que vous acceptez ce service avant de démarrer la mission.
                        @if($publicPendingDriverName)
                            <span class="d-block">Chauffeur: {{ $publicPendingDriverName }}</span>
                        @endif
                    </p>
                @endif
                <form action="{{ route('mission.public.confirm', $publicMissionToken) }}" method="POST" class="mb-2">
                    @csrf
                    <input type="hidden" name="driver_role" value="{{ $publicPendingConfirmationRole }}">
                    <button type="submit" class="btn btn-success btn-block big-action">
                        <i class="fa fa-check-circle"></i> Confirmer le service
                    </button>
                </form>
                <form action="{{ route('mission.public.refuse', $publicMissionToken) }}" method="POST" onsubmit="return confirm('Refuser ce service ?');">
                    @csrf
                    <input type="hidden" name="driver_role" value="{{ $publicPendingConfirmationRole }}">
                    <input type="hidden" name="driver_refusal_reason" value="{{ $publicPendingConfirmationRole === 'second_driver' ? 'Refus public 2e chauffeur' : 'Refus public chauffeur' }}">
                    <button type="submit" class="btn btn-outline-danger btn-block">
                        <i class="fa fa-times"></i> Refuser le service
                    </button>
                </form>
            </div>
        @elseif (!$mission)
            <div class="next-action">
                <h3>Commencer la mission</h3>
                <p class="mb-3 text-muted">Saisissez le kilométrage actuel du véhicule avant de quitter le dépôt.</p>
                @if(!$canStartMissionNow)
                    <div class="alert alert-warning">
                        @foreach($missionStartWarnings as $warning)
                            <div>{{ $warning }}</div>
                        @endforeach
                    </div>
                @endif
                <form action="{{ $publicMissionToken ? route('mission.public.start', $publicMissionToken) : route('startmission', $transfer->id) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="depart_km">Kilométrage départ</label>
                        <input type="number" name="depart_km" id="depart_km" class="form-control form-control-lg" min="0" max="5000000" inputmode="numeric" required {{ $canStartMissionNow ? '' : 'disabled' }}>
                    </div>
                    <button type="submit" class="btn btn-danger btn-block big-action" {{ $canStartMissionNow ? '' : 'disabled' }}>
                        @if ($transfer->accueil)J'ai pris le panneau - @endif Départ du dépôt
                    </button>
                </form>
            </div>
        @elseif ($mission->hareket && !$mission->surplace)
            <div class="next-action">
                <h3>Aller au point de départ</h3>
                <p class="mb-2">Départ dépôt enregistré à <strong>{{ date('H:i', strtotime($mission->hareket)) }}</strong>.</p>
                <p class="mb-3 text-muted">Sur place prévu à {{ $surplacePrevu->format('H:i') }} - {{ $transfer->from }}</p>
                <a href="{{ $publicMissionToken ? route('mission.public.onplace', [$publicMissionToken, $mission->id]) : route('onplacemission', $mission->id) }}" class="btn btn-danger btn-block big-action">Je suis sur place</a>
            </div>
        @elseif ($mission->surplace && !$mission->taked)
            <div class="next-action">
                <h3>Attendre et embarquer le client</h3>
                <p class="mb-3">Sur place enregistré à <strong>{{ date('H:i', strtotime($mission->surplace)) }}</strong>.</p>
                <a href="{{ $publicMissionToken ? route('mission.public.onboard', [$publicMissionToken, $mission->id]) : route('onboardmission', $mission->id) }}" class="btn btn-danger btn-block big-action">Client à bord</a>
            </div>
        @elseif ($mission->taked && !$mission->finish)
            <div class="next-action">
                <h3>Suivre l'itinéraire</h3>
                <p class="mb-3">Client embarqué à <strong>{{ date('H:i', strtotime($mission->taked)) }}</strong>. Destination finale: <strong>{{ $transfer->target }}</strong>.</p>
                <a href="{{ $publicMissionToken ? route('mission.public.finish', [$publicMissionToken, $mission->id]) : route('finishmission', $mission->id) }}" class="btn btn-success btn-block big-action">Client déposé</a>
            </div>
        @elseif ($mission->finish && !$mission->finish_depot)
            <div class="next-action">
                <h3>Retour au dépôt</h3>
                <p class="mb-3">Client déposé à <strong>{{ date('H:i', strtotime($mission->finish)) }}</strong>. Saisissez le kilométrage final au dépôt.</p>
                <form onsubmit="return validateCleaningForm()" action="{{ $publicMissionToken ? route('mission.public.finishDepot', [$publicMissionToken, $mission->id]) : route('finishmissiondepot', $mission->id) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="finish_km">Kilométrage retour dépôt</label>
                        <input type="number" name="finish_km" id="finish_km" class="form-control form-control-lg" min="{{ $mission->depart_km ?? 0 }}" max="5000000" inputmode="numeric" required>
                        @if($mission->depart_km !== null)<small class="text-muted">Départ: {{ $mission->depart_km }} km</small>@endif
                    </div>
                    <div class="radio-title mb-1">Véhicule nettoyé</div>
                    <div class="custom-control custom-radio custom-control-inline">
                        <input class="custom-control-input" type="radio" name="cleaningStatus" id="cleanedYes" value="yes">
                        <label class="custom-control-label" for="cleanedYes">Oui</label>
                    </div>
                    <div class="custom-control custom-radio custom-control-inline">
                        <input class="custom-control-input" type="radio" name="cleaningStatus" id="cleanedNo" value="no">
                        <label class="custom-control-label" for="cleanedNo">Non</label>
                    </div>
                    <button type="submit" class="btn btn-danger btn-block big-action mt-3">Mission terminée au dépôt</button>
                </form>
            </div>
        @else
            @php
                $hareket = \Carbon\Carbon::parse($mission->hareket);
                $finishDepot = \Carbon\Carbon::parse($mission->finish_depot);
                $diffInHours = $hareket->diffInHours($finishDepot);
                $diffInMinutes = $hareket->diffInMinutes($finishDepot) % 60;
                $kmDone = ($mission->finish_km !== null && $mission->depart_km !== null) ? $mission->finish_km - $mission->depart_km : null;
            @endphp
            <div class="next-action">
                <h3>Mission terminée</h3>
                <p class="mb-1">Fin dépôt à <strong>{{ date('H:i', strtotime($mission->finish_depot)) }}</strong>.</p>
                <p class="mb-0">{{ $kmDone !== null ? $kmDone . ' km' : 'Kilométrage non complet' }} - {{ $diffInHours }} h {{ $diffInMinutes }} min</p>
            </div>
        @endif
    </section>

    @if($mission)
        <section class="mission-card">
            <div class="card-title-line"><h2>Kilométrage</h2><span class="badge badge-light border">modifiable</span></div>
            <form action="{{ $publicMissionToken ? route('mission.public.kilometers', [$publicMissionToken, $mission->id]) : route('missions.updateKilometers', $mission->id) }}" method="POST" class="km-form">
                @csrf
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="update_depart_km">Départ dépôt</label>
                        <input type="number" name="depart_km" id="update_depart_km" class="form-control" min="0" max="5000000" inputmode="numeric" value="{{ $mission->depart_km }}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="update_finish_km">Retour dépôt</label>
                        <input type="number" name="finish_km" id="update_finish_km" class="form-control" min="0" max="5000000" inputmode="numeric" value="{{ $mission->finish_km }}" placeholder="À remplir en fin de mission">
                    </div>
                </div>
                <button class="btn btn-outline-primary btn-block">Mettre à jour les kilomètres</button>
            </form>
        </section>
    @endif

    <section class="mission-card">
        <div class="card-title-line"><h2>Itinéraire</h2><span class="badge badge-primary">{{ $trajets->count() }} étapes</span></div>
        @if($transfer->comments)
            <div class="alert alert-light border mb-3">{!! nl2br(e($transfer->comments)) !!}</div>
        @endif
        <ul class="route-list">
            @forelse ($trajets as $index => $trajet)
                @php
                    $trajetTime = \Carbon\Carbon::parse($trajet->datetime);
                    $displayTrajetTime = $index === 0 ? $trajetTime->copy()->subMinutes($minutesBefore) : $trajetTime;
                @endphp
                <li>
                    <div><span class="route-index">{{ $index + 1 }}</span></div>
                    <div class="route-text">
                        <strong>
                            {{ $displayTrajetTime->format($displayTrajetTime->isSameDay($start) ? 'H:i' : 'd/m H:i') }}
                            - {{ $index === 0 ? 'Sur place client' : ucfirst($trajet->type) }}
                        </strong>
                        <div>{{ $trajet->from }}</div>
                        @if($trajet->google_address)
                            <div class="text-muted small">{{ $trajet->google_address }}</div>
                            <div class="route-actions">
                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($trajet->google_address) }}" target="_blank" class="btn btn-sm btn-outline-primary">Google Maps</a>
                                <a href="geo:0,0?q={{ urlencode($trajet->google_address) }}" class="btn btn-sm btn-outline-secondary">App Maps</a>
                            </div>
                        @endif
                    </div>
                </li>
            @empty
                <li><div></div><div class="text-muted">Aucun trajet renseigné.</div></li>
            @endforelse
        </ul>
    </section>

    <section class="mission-card">
        <div class="card-title-line"><h2>Passagers</h2><span class="badge badge-light border">{{ $transfer->pax }} pax</span></div>
        @if($clients->count())
            <div class="passenger-list">
                @foreach($clients as $client)
                    @php $phone = preg_replace('/\D/', '', $client->tel ?? ''); @endphp
                    <div class="passenger-row">
                        <strong>{{ trim(($client->name ?? '') . ' ' . ($client->surname ?? '')) ?: 'Passager' }}</strong>
                        @if($client->tel)
                            <div class="text-muted">{{ $client->tel }}</div>
                            <div class="route-actions">
                                <a href="tel:{{ $client->tel }}" class="btn btn-sm btn-outline-primary">Appeler</a>
                                @if($phone)<a href="https://wa.me/{{ $phone }}" target="_blank" class="btn btn-sm btn-outline-success">WhatsApp</a>@endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-muted">Aucun passager renseigné.</div>
        @endif
    </section>

    <div class="d-flex flex-wrap gap-2 mt-3">
        <a href="{{ $publicMissionToken ? route('mission.public', $publicMissionToken) : route('kaptanshow') }}" class="btn btn-secondary btn-block">Retour à mes missions</a>
    </div>
</div>
@endsection

@section('scripts')
<script>
function validateCleaningForm() {
    var cleaningStatus = document.querySelector('input[name="cleaningStatus"]:checked');
    if (!cleaningStatus) {
        alert('Merci de sélectionner le statut de nettoyage du véhicule.');
        return false;
    }
    return true;
}
</script>
@endsection

