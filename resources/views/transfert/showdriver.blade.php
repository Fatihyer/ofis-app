@extends('layouts.kaptan')

@section('title', '| Mission chauffeur #' . $transfers->id)

@section('content')
@php
    $start = \Carbon\Carbon::parse($transfers->start_date);
    $end = \Carbon\Carbon::parse($transfers->end_date);
    $surplaceMinutes = (int) (optional(\App\Models\Option::where('name', 'surplaceMinBefore')->first())->value ?? 15);
    $surplace = $start->copy()->subMinutes($surplaceMinutes);
    $ofisStart = $transfers->ofis_start ? \Carbon\Carbon::parse($transfers->ofis_start) : $start->copy()->subHour();
    $durationMinutes = $end->diffInMinutes($start);
    $durationLabel = intdiv($durationMinutes, 60) . ' h ' . str_pad($durationMinutes % 60, 2, '0', STR_PAD_LEFT);
    $statusName = optional($transfers->status)->name ?: 'Statut inconnu';
    $statusColor = optional(optional($transfers->status)->color)->name ?: 'secondary';
    $driverName = optional($transfers->driver)->name ?: 'Chauffeur non défini';
    $driverPhone = optional($transfers->driver)->tel ?: null;
    $vehicleName = trim((optional($transfers->vehicule)->name ?: 'Véhicule non défini') . ' ' . (optional($transfers->vehicule)->plaka ?: ''));
    $clients = optional($transfers->post)->client ?: collect();
    $clientText = $clients->map(function ($client) {
        return trim(($client->name ?? '') . ' ' . ($client->surname ?? '') . ' ' . ($client->tel ?? ''));
    })->filter()->implode("\n");
    $mission = $transfers->missionr;
    $publicMissionToken = \App\Http\Controllers\MissionController::publicMissionTokenFor((int) $transfers->id);
    $publicMissionUrl = route('mission.public', $publicMissionToken);
    $linkedDriverIds = Auth::user() ? Auth::user()->acentes()->pluck('acentes.id')->map(fn ($id) => (int) $id)->toArray() : [];
    $isSecondDriverView = \Illuminate\Support\Facades\Schema::hasColumn('transfers', 'second_driver_id')
        && in_array((int) ($transfers->second_driver_id ?? 0), $linkedDriverIds, true)
        && !in_array((int) ($transfers->driver_id ?? 0), $linkedDriverIds, true);
    $driverAppConfirmedAt = $isSecondDriverView
        ? (($transfers->second_driver_app_confirmed_at ?? null) ? \Carbon\Carbon::parse($transfers->second_driver_app_confirmed_at) : null)
        : ($transfers->driver_app_confirmed_at ? \Carbon\Carbon::parse($transfers->driver_app_confirmed_at) : null);
    $driverAppRefusedAt = $isSecondDriverView
        ? (($transfers->second_driver_app_refused_at ?? null) ? \Carbon\Carbon::parse($transfers->second_driver_app_refused_at) : null)
        : (($transfers->driver_app_refused_at ?? null) ? \Carbon\Carbon::parse($transfers->driver_app_refused_at) : null);
    $driverRefuseEnabled = $isSecondDriverView
        ? \Illuminate\Support\Facades\Schema::hasColumn('transfers', 'second_driver_app_refused_at')
        : \Illuminate\Support\Facades\Schema::hasColumn('transfers', 'driver_app_refused_at');
    $driverReconfirmAt = $isSecondDriverView
        ? (($transfers->second_driver_app_reconfirm_required_at ?? null) ? \Carbon\Carbon::parse($transfers->second_driver_app_reconfirm_required_at) : null)
        : (($transfers->driver_app_reconfirm_required_at ?? null) ? \Carbon\Carbon::parse($transfers->driver_app_reconfirm_required_at) : null);
    $driverReconfirmReason = $isSecondDriverView
        ? ($transfers->second_driver_app_reconfirm_reason ?? null)
        : ($transfers->driver_app_reconfirm_reason ?? null);
    $canOffice = Auth::user()->hasAnyRole(['Superadmin', 'Admin', 'ofis', 'transport']) || Auth::user()->hasAnyPermission(['transfers.operations', 'ofis', 'transport']);
    $hasSecondDriver = \Illuminate\Support\Facades\Schema::hasColumn('transfers', 'second_driver_id') && ($transfers->second_driver_id ?? null) && $transfers->secondDriver;
    $primaryDriverConfirmedAt = $transfers->driver_app_confirmed_at ? \Carbon\Carbon::parse($transfers->driver_app_confirmed_at) : null;
    $primaryDriverRefusedAt = ($transfers->driver_app_refused_at ?? null) ? \Carbon\Carbon::parse($transfers->driver_app_refused_at) : null;
    $secondDriverConfirmedAt = ($hasSecondDriver && ($transfers->second_driver_app_confirmed_at ?? null)) ? \Carbon\Carbon::parse($transfers->second_driver_app_confirmed_at) : null;
    $secondDriverRefusedAt = ($hasSecondDriver && ($transfers->second_driver_app_refused_at ?? null)) ? \Carbon\Carbon::parse($transfers->second_driver_app_refused_at) : null;
    $firstClientPhone = $clients->map(fn ($client) => preg_replace('/\D+/', '', $client->tel ?? ''))->filter()->first();
    $whatsappText = '';
    if (isset($transfers->status->id) && $transfers->status->id > 1) {
        $whatsappText = '<<SERVICE DE TRANSPORT PUBLIC DE PERSONNES - BILLET COLLECTIF>>' . PHP_EOL .
            'Date: ' . $start->format('d/m/Y D') . PHP_EOL .
            '*En route: ' . $ofisStart->format('H:i') . '*' . PHP_EOL .
            '*Sur place client: ' . $surplace->format('H:i') . '*' . PHP_EOL .
            '*Heure prise en charge: ' . $start->format('H:i') . '*' . PHP_EOL .
            'Heure de dépose: ' . $end->format('H:i') . PHP_EOL .
            'Service: ' . optional($transfers->servicetype)->name . PHP_EOL .
            'Passagers: ' . $transfers->pax . ' pax' . PHP_EOL .
            '*Lieu de prise en charge: ' . $transfers->from . '*' . PHP_EOL .
            'Lieu de dépose: ' . $transfers->target . PHP_EOL .
            'Véhicule: ' . $vehicleName . PHP_EOL .
            'Chauffeur: ' . $driverName . PHP_EOL .
            'Commentaires: ' . ($transfers->comments ?: '-') . PHP_EOL .
            'Contact:' . PHP_EOL . ($clientText ?: '-');
    } else {
        $whatsappText = optional($transfers->servicetype)->name . ' - ANNULÉ' . PHP_EOL .
            'Date: ' . $start->format('d/m/Y D H:i') . PHP_EOL .
            'Départ: ' . $transfers->from . PHP_EOL .
            'Commentaires: ' . ($transfers->comments ?: '-');
    }
@endphp

<style>
.driver-page {
    max-width: 980px;
    margin: 0 auto 32px;
    padding: 10px;
    color: #172033;
}
.driver-hero {
    border-radius: 14px;
    background: linear-gradient(135deg, #111827 0%, #1f2937 52%, #334155 100%);
    color: #fff;
    padding: 18px;
    box-shadow: 0 16px 38px rgba(15, 23, 42, .22);
}
.driver-topline {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    align-items: flex-start;
    flex-wrap: wrap;
}
.driver-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #e5e7eb;
    text-decoration: none;
    font-weight: 700;
}
.driver-service {
    font-size: clamp(22px, 7vw, 38px);
    line-height: 1.02;
    margin: 16px 0 8px;
    font-weight: 850;
    letter-spacing: 0;
}
.driver-date {
    color: #cbd5e1;
    font-size: 15px;
}
.status-chip {
    border-radius: 999px;
    padding: 7px 11px;
    background: rgba(255,255,255,.14);
    color: #fff;
    font-size: 13px;
    font-weight: 800;
}
.action-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin-top: 16px;
}
.driver-action {
    min-height: 46px;
    border-radius: 10px;
    font-weight: 800;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    white-space: normal;
}
.driver-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .07);
    margin-top: 12px;
    overflow: hidden;
}
.driver-card-head {
    padding: 13px 14px;
    border-bottom: 1px solid #edf2f7;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
.driver-card-head h2 {
    font-size: 16px;
    margin: 0;
    font-weight: 850;
}
.driver-card-body { padding: 14px; }
.time-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}
.time-tile {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px;
    background: #fff;
}
.time-label {
    display: block;
    font-size: 11px;
    color: #64748b;
    font-weight: 850;
    text-transform: uppercase;
}
.time-value {
    display: block;
    margin-top: 5px;
    font-size: 21px;
    font-weight: 900;
}
.route-step {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr);
    gap: 10px;
    padding: 12px 0;
    border-bottom: 1px solid #edf2f7;
}
.route-step:last-child { border-bottom: 0; }
.step-dot {
    width: 34px;
    height: 34px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #eff6ff;
    color: #1d4ed8;
    font-weight: 900;
}
.address-line {
    font-size: 16px;
    font-weight: 800;
    word-break: break-word;
}
.meta-line { color: #64748b; font-size: 13px; margin-top: 3px; }
.info-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}
.info-box {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px;
    background: #f8fafc;
    min-width: 0;
}
.info-box strong { display: block; font-size: 13px; color: #64748b; margin-bottom: 5px; }
.mission-line {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
}
.mission-pill {
    border-radius: 10px;
    background: #f1f5f9;
    padding: 10px;
    text-align: center;
}
.mission-pill span { display: block; color: #64748b; font-size: 11px; font-weight: 850; }
.mission-pill strong { display: block; font-size: 17px; margin-top: 4px; }
.form-control, .custom-file-label { border-radius: 10px; }
@media (max-width: 767.98px) {
    .driver-page { padding: 8px; }
    .driver-hero { border-radius: 12px; padding: 16px; }
    .action-grid, .time-grid, .info-list, .mission-line { grid-template-columns: 1fr; }
    .driver-action { width: 100%; }
    .driver-card-body { padding: 12px; }
    .time-tile { display: flex; align-items: center; justify-content: space-between; }
    .time-value { font-size: 20px; margin-top: 0; }
}
</style>

<div class="driver-page">
    <section class="driver-hero">
        <div class="driver-topline">
            <a class="driver-back" href="{{ url()->previous() }}"><i class="fa fa-arrow-left"></i> Retour</a>
            <span class="status-chip">{{ $statusName }}</span>
        </div>
        <div class="driver-service">{{ optional($transfers->servicetype)->name ?: 'Service' }}</div>
        <div class="driver-date">{{ $start->format('d/m/Y') }} · {{ $durationLabel }} prévus · #{{ $transfers->id }}</div>

        @if($canOffice && $hasSecondDriver)
            <div class="alert alert-light border mt-3 mb-0" style="color:#0f172a;">
                <div class="d-flex flex-wrap" style="gap: 8px;">
                    <span class="badge {{ $primaryDriverRefusedAt ? 'bg-danger' : ($primaryDriverConfirmedAt ? 'bg-success' : 'bg-warning text-dark') }}">
                        Chauffeur principal: {{ $driverName }} -
                        @if($primaryDriverRefusedAt) refusé {{ $primaryDriverRefusedAt->format('H:i') }}
                        @elseif($primaryDriverConfirmedAt) confirmé {{ $primaryDriverConfirmedAt->format('H:i') }}
                        @else en attente
                        @endif
                    </span>
                    <span class="badge {{ $secondDriverRefusedAt ? 'bg-danger' : ($secondDriverConfirmedAt ? 'bg-success' : 'bg-warning text-dark') }}">
                        2e chauffeur: {{ $transfers->secondDriver->name }} -
                        @if($secondDriverRefusedAt) refusé {{ $secondDriverRefusedAt->format('H:i') }}
                        @elseif($secondDriverConfirmedAt) confirmé {{ $secondDriverConfirmedAt->format('H:i') }}
                        @else en attente
                        @endif
                    </span>
                </div>
                @if(!$primaryDriverConfirmedAt && !$primaryDriverRefusedAt)
                    <form method="POST" action="{{ route('transfers.driverAppConfirm', $transfers->id) }}" class="d-inline-block mt-2 mr-2" onsubmit="return confirm('Confirmer au nom du chauffeur principal ?');">
                        @csrf
                        <input type="hidden" name="driver_role" value="primary_driver">
                        <button class="btn btn-outline-success btn-sm" type="submit"><i class="fa fa-user-check"></i> Confirmer chauffeur principal</button>
                    </form>
                @endif
                @if(!$secondDriverConfirmedAt && !$secondDriverRefusedAt)
                    <div class="small mt-2">Le service reste à confirmer par le 2e chauffeur.</div>
                    <form method="POST" action="{{ route('transfers.driverAppConfirm', $transfers->id) }}" class="d-inline-block mt-2" onsubmit="return confirm('Confirmer au nom du 2e chauffeur ?');">
                        @csrf
                        <input type="hidden" name="driver_role" value="second_driver">
                        <button class="btn btn-outline-success btn-sm" type="submit"><i class="fa fa-user-check"></i> Confirmer 2e chauffeur</button>
                    </form>
                @endif
            </div>
        @endif

        @if(!$driverAppConfirmedAt && !$driverAppRefusedAt && $driverReconfirmAt)
            <div class="alert alert-warning mt-3 mb-0" style="color:#78350f; font-weight:800;">
                Modification le {{ $driverReconfirmAt->format('d/m/Y H:i') }}. Veuillez reconfirmer le service.
                @if($driverReconfirmReason)
                    <div class="small mt-1">{{ $driverReconfirmReason }}</div>
                @endif
            </div>
        @endif

        <div class="action-grid">
            @if($driverAppRefusedAt)
                <span class="btn btn-danger driver-action disabled"><i class="fa fa-times-circle"></i> Service refusé {{ $driverAppRefusedAt->format('H:i') }}</span>
            @elseif($driverAppConfirmedAt)
                <span class="btn btn-success driver-action disabled"><i class="fa fa-check-circle"></i> {{ $isSecondDriverView ? 'Service confirmé 2e chauffeur' : 'Service confirmé chauffeur principal' }} {{ $driverAppConfirmedAt->format('H:i') }}</span>
            @else
                <form method="POST" action="{{ route('transfers.driverAppConfirm', $transfers->id) }}">
                    @csrf
                    <button class="btn btn-primary driver-action" type="submit"><i class="fa fa-check"></i> Confirmation du service</button>
                </form>
                @if($driverRefuseEnabled)
                    <form method="POST" action="{{ route('transfers.driverAppRefuse', $transfers->id) }}">
                        @csrf
                        <input type="hidden" name="driver_refusal_reason" value="Refus depuis l'interface chauffeur">
                        <button class="btn btn-outline-danger driver-action" type="submit" onclick="return confirm('Refuser ce service ?')"><i class="fa fa-times"></i> Refuser le service</button>
                    </form>
                @endif
            @endif
            @if($transfers->mission)
                <a href="{{ route('mission', $transfers->id) }}" class="btn btn-danger driver-action"><i class="fa fa-play"></i> Mission</a>
                <a href="{{ $publicMissionUrl }}" target="_blank" class="btn btn-outline-light driver-action"><i class="fa fa-link"></i> Lien public mission</a>
            @endif
            @if($firstClientPhone)
                <a href="tel:{{ $firstClientPhone }}" class="btn btn-success driver-action"><i class="fa fa-phone"></i> Appeler client</a>
            @endif
            <button onclick="yazdir()" class="btn btn-outline-light driver-action" type="button"><i class="fa fa-print"></i> Imprimer</button>
        </div>
    </section>

    <section class="driver-card">
        <div class="driver-card-head"><h2>Horaires</h2><span class="meta-line">Heures prévues</span></div>
        <div class="driver-card-body time-grid">
            <div class="time-tile"><span class="time-label">En route</span><span class="time-value">{{ $ofisStart->format('H:i') }}</span></div>
            <div class="time-tile"><span class="time-label">Sur place</span><span class="time-value">{{ $surplace->format('H:i') }}</span></div>
            <div class="time-tile"><span class="time-label">Prise en charge</span><span class="time-value">{{ $start->format('H:i') }}</span></div>
            <div class="time-tile"><span class="time-label">Fin prévue</span><span class="time-value">{{ $end->format('H:i') }}</span></div>
        </div>
    </section>

    <section class="driver-card">
        <div class="driver-card-head"><h2>Itinéraire</h2><span class="meta-line">{{ $transfers->pax }} pax</span></div>
        <div class="driver-card-body">
            @if ($transfers->trajets->count())
                @foreach ($transfers->trajets as $index => $trajet)
                    @php
                        $trajetTime = \Carbon\Carbon::parse($trajet->datetime);
                        $displayTrajetTime = $index === 0 ? $trajetTime->copy()->subMinutes($surplaceMinutes) : $trajetTime;
                    @endphp
                    <div class="route-step">
                        <div class="step-dot">{{ $index + 1 }}</div>
                        <div>
                            <div class="address-line">{{ $trajet->from }} {{ $trajet->google_address }}</div>
                            <div class="meta-line">
                                {{ $displayTrajetTime->format($displayTrajetTime->isSameDay($start) ? 'H:i' : 'd/m/Y H:i') }}
                                @if($index === 0) · Sur place client @endif
                                · {{ $trajet->type }}
                            </div>
                            <a class="btn btn-outline-primary btn-sm mt-2" target="_blank" href="https://www.google.com/maps/search/?api=1&query={{ urlencode(trim($trajet->from . ' ' . $trajet->google_address)) }}"><i class="fa fa-map-marker-alt"></i> Ouvrir Maps</a>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="route-step">
                    <div class="step-dot">A</div>
                    <div><div class="address-line">{{ $transfers->from }}</div><div class="meta-line">Départ</div></div>
                </div>
                <div class="route-step">
                    <div class="step-dot">B</div>
                    <div><div class="address-line">{{ $transfers->target }}</div><div class="meta-line">Arrivée</div></div>
                </div>
            @endif
        </div>
    </section>

    <section class="driver-card">
        <div class="driver-card-head"><h2>Contacts et véhicule</h2></div>
        <div class="driver-card-body info-list">
            <div class="info-box">
                <strong>Clients</strong>
                <div style="white-space: pre-line;">{{ $clientText ?: 'Aucun contact renseigné' }}</div>
            </div>
            <div class="info-box">
                <strong>Chauffeur</strong>
                <div>{{ $driverName }}</div>
                @if($driverPhone)<a href="tel:{{ preg_replace('/\D+/', '', $driverPhone) }}">{{ $driverPhone }}</a>@endif
            </div>
            <div class="info-box">
                <strong>Véhicule</strong>
                <div>{{ $vehicleName ?: 'Non défini' }}</div>
            </div>
            <div class="info-box">
                <strong>Commentaires</strong>
                <div>{{ $transfers->comments ?: '-' }}</div>
            </div>
        </div>
    </section>

    @if($driverAppConfirmedAt)
        <section class="driver-card">
            <div class="driver-card-head"><h2>Mission réelle</h2></div>
            <div class="driver-card-body">
                @if ($mission)
                    <div class="mission-line mb-3">
                        <div class="mission-pill"><span>En route</span><strong>{{ $mission->hareket ? date('H:i', strtotime($mission->hareket)) : '-' }}</strong></div>
                        <div class="mission-pill"><span>Sur place</span><strong>{{ $mission->surplace ? date('H:i', strtotime($mission->surplace)) : '-' }}</strong></div>
                        <div class="mission-pill"><span>À bord</span><strong>{{ $mission->taked ? date('H:i', strtotime($mission->taked)) : '-' }}</strong></div>
                        <div class="mission-pill"><span>Fin</span><strong>{{ $mission->finish ? date('H:i', strtotime($mission->finish)) : '-' }}</strong></div>
                    </div>
                    <form id="time_form" method="POST" action="{{ route('update_times') }}">
                        @csrf
                        <input type="hidden" name="transfers_id" value="{{ $transfers->id }}">
                        @if ($mission->hareket)
                            <label>En route réel</label>
                            <input type="datetime-local" class="form-control mb-2" name="new_hareket" value="{{ date('Y-m-d\TH:i', strtotime($mission->hareket)) }}">
                        @endif
                        @if ($mission->surplace)
                            <label>Sur place réel</label>
                            <input type="datetime-local" class="form-control mb-2" name="new_surplace" value="{{ date('Y-m-d\TH:i', strtotime($mission->surplace)) }}">
                        @endif
                        @if ($mission->taked)
                            <label>Prise en charge réelle</label>
                            <input type="datetime-local" class="form-control mb-2" name="new_taked" value="{{ date('Y-m-d\TH:i', strtotime($mission->taked)) }}">
                        @endif
                        @if ($mission->finish)
                            <label>Fin réelle</label>
                            <input type="datetime-local" class="form-control mb-2" name="new_finish" value="{{ date('Y-m-d\TH:i', strtotime($mission->finish)) }}">
                        @endif
                        <button type="submit" class="btn btn-primary driver-action mt-2"><i class="fa fa-save"></i> Mettre à jour</button>
                    </form>
                @else
                    <div class="text-muted">Aucune mission démarrée.</div>
                    @if($transfers->mission)
                        <a href="{{ route('mission', $transfers->id) }}" class="btn btn-danger driver-action mt-2"><i class="fa fa-play"></i> Démarrer la mission</a>
                    @endif
                @endif
            </div>
        </section>
    @else
        <section class="driver-card">
            <div class="driver-card-head"><h2>Mission réelle</h2></div>
            <div class="driver-card-body text-muted">
                Veuillez confirmer le service pour afficher le suivi de mission réelle.
            </div>
        </section>
    @endif

    <section class="driver-card">
        <div class="driver-card-head"><h2>Notes chauffeur</h2></div>
        <div class="driver-card-body">
            {{ Form::model($transfers, ['route' => ['transfers.update', $transfers->id], 'method' => 'PUT']) }}
                {{ Form::text('dcomments', $transfers->dcomments, ['class' => 'form-control', 'placeholder' => 'Commentaire chauffeur / guide']) }}
                {{ Form::hidden('status_id', $transfers->status_id) }}
                <button class="btn btn-primary driver-action mt-2" type="submit"><i class="fa fa-save"></i> Enregistrer</button>
            {{ Form::close() }}
        </div>
    </section>

    @if($canOffice)
        <section class="driver-card">
            <div class="driver-card-head"><h2>Communication bureau</h2></div>
            <div class="driver-card-body">
                <div class="action-grid mb-3">
                    @if(isset($transfers->status->id) && $transfers->status->id > 1)
                        <a target="_blank" href="https://api.whatsapp.com/send?{{ $driverPhone ? 'phone='.preg_replace('/\D+/', '', $driverPhone).'&' : '' }}text={{ rawurlencode($whatsappText) }}" class="btn btn-success driver-action"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                        <a href="{{ route('transfers.sms', $transfers->id) }}" class="btn btn-outline-success driver-action"><i class="fa fa-sms"></i> SMS</a>
                    @endif
                    <a href="{{ route('transfershowpdf', $transfers->id) }}" class="btn btn-outline-danger driver-action"><i class="fa fa-file-pdf"></i> PDF confirmation</a>
                    <a href="{{ route('transferMissionshowpdf', $transfers->id) }}" class="btn btn-outline-danger driver-action"><i class="fa fa-route"></i> Feuille de route</a>
                </div>
                <label class="font-weight-bold">Texte WhatsApp</label>
                <textarea id="textArea" class="form-control" rows="6">{{ $whatsappText }}</textarea>
                <button id="copyButton" class="btn btn-outline-primary driver-action mt-2" type="button"><i class="fa fa-copy"></i> Copier</button>
            </div>
        </section>
    @endif

    <section class="driver-card">
        <div class="driver-card-head"><h2>Voucher</h2></div>
        <div class="driver-card-body">
            <form action="{{ route('transfers.voucherupload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="custom-file mb-2">
                    <input type="file" name="image" class="custom-file-input" id="customFile">
                    <input type="hidden" name="id" value="{{ $transfers->id }}">
                    <input type="hidden" name="post_id" value="{{ $transfers->post_id }}">
                    <label class="custom-file-label" for="customFile">Choisir un fichier</label>
                </div>
                <button type="submit" class="btn btn-primary driver-action"><i class="fa fa-upload"></i> Envoyer</button>
            </form>
            @if(count($files))
                <div class="mt-3">
                    <strong>Fichiers:</strong>
                    @foreach ($files as $file)
                        <a class="badge badge-light" href="/voucher/{{ $transfers->id }}/{{ basename($file) }}">{{ basename($file) }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</div>
@endsection

@section('footer')
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script>
$('.custom-file-input').on('change', function() {
  var fileName = $(this).val().split('\\').pop();
  $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
});

document.addEventListener('DOMContentLoaded', function() {
    var copyButton = document.getElementById('copyButton');
    if (copyButton) {
        copyButton.addEventListener('click', function() {
            var textArea = document.getElementById('textArea');
            if (!textArea) return;
            textArea.select();
            document.execCommand('copy');
            copyButton.innerHTML = '<i class="fa fa-check"></i> Copié';
        });
    }
});
</script>
@endsection
