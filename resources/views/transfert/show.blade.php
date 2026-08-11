@extends('layouts.app')

@section('title', '| Transfert #' . $transfers->id)

@section('content')
@php
    $statusColor = optional(optional($transfers->status)->color)->name ?? 'secondary';
    $statusName = optional($transfers->status)->name ?? 'Statut inconnu';
    $statusHex = [
        'success' => '#198754',
        'warning' => '#f59e0b',
        'danger' => '#dc3545',
        'info' => '#0dcaf0',
        'primary' => '#0d6efd',
        'secondary' => '#64748b',
        'dark' => '#212529',
        'light' => '#cbd5e1',
    ][$statusColor] ?? '#64748b';
    $serviceName = optional($transfers->servicetype)->name ?? 'Service non défini';
    $start = \Carbon\Carbon::parse($transfers->start_date);
    $end = \Carbon\Carbon::parse($transfers->end_date);
    $durationHours = $end->diffInHours($start);
    $minutesBefore = \App\Models\Option::where('name', 'surplaceMinBefore')->first();
    $surplaceMinutes = (int) ($minutesBefore?->value ?? 15);
    $surplaceTime = $start->copy()->subMinutes($surplaceMinutes);
    $ofisStart = $transfers->ofis_start ? \Carbon\Carbon::parse($transfers->ofis_start) : $start->copy()->subMinutes(105);
    $emptyAssignmentNames = ['-', '--', '---', '----'];
    $driverName = trim(optional($transfers->driver)->name ?? '');
    $vehiculeName = trim(optional($transfers->vehicule)->name ?? '');
    $hasRealDriver = $transfers->driver && !in_array($driverName, $emptyAssignmentNames, true);
    $providerTypeId = (int) optional($transfers->servicetype)->firma_id;
    $driverProviderTypeIds = [3, 9, 12, 13, 14];
    $isDriverProvider = in_array($providerTypeId, $driverProviderTypeIds, true);
    $requiresRealVehicule = $providerTypeId === 3;
    $providerLabelFr = $isDriverProvider ? 'Chauffeur' : ($providerTypeId === 1 ? 'Guide' : ($providerTypeId === 5 ? 'Personnel' : 'Prestataire'));
    $providerLabelEn = $isDriverProvider ? 'Driver' : ($providerTypeId === 1 ? 'Guide' : ($providerTypeId === 5 ? 'Staff' : 'Provider'));
    $providerLabelTr = $isDriverProvider ? 'Şoför' : ($providerTypeId === 1 ? 'Rehber' : ($providerTypeId === 5 ? 'Personel' : 'Tedarikçi'));
    $providerLabelLowerFr = mb_strtolower($providerLabelFr);
    $hasRealVehicule = $transfers->vehicule && $transfers->vehicule->real && !in_array($vehiculeName, $emptyAssignmentNames, true);
    $clients = optional($transfers->post)->client ?? collect();
    $clientContact = $clients->map(function ($client) {
        return trim(($client->name ?? '') . ' ' . ($client->surname ?? '') . ' ' . ($client->tel ?? ''));
    })->filter()->implode("\n");
    $clientPhones = $clients->map(function ($client) {
        $phone = preg_replace('/\D+/', '', $client->tel ?? '');
        return [
            'name' => trim(($client->name ?? '') . ' ' . ($client->surname ?? '')) ?: 'Client',
            'tel' => $client->tel ?? '',
            'phone' => $phone,
        ];
    })->filter(fn ($client) => !empty($client['phone']))->values();
    $post = $transfers->post;
    $acente = optional($post)->acente;
    $overnights = $overnights ?? collect();
    $currentOvernight = $overnights->first();
    $canOperate = Auth::user()->hasAnyRole(['Superadmin', 'Admin', 'ofis', 'transport']) || Auth::user()->hasAnyPermission(['transfers.operations', 'ofis', 'transport']);

    $clientDriverName = $hasRealDriver ? $transfers->driver->name : 'Non défini';
    $clientDriverPhone = $hasRealDriver ? ($transfers->driver->tel ?: 'Non renseigné') : 'Non défini';
    $clientVehicle = $hasRealVehicule ? trim(($transfers->vehicule->name ?? '') . ' ' . ($transfers->vehicule->plaka ?? '')) : ($requiresRealVehicule ? 'Non défini' : 'Non requis');

    $clientWhatsappTexts = [
        'fr' => 'Bonjour,' . PHP_EOL . PHP_EOL .
            'Voici les coordonnées de votre chauffeur pour votre transfert #' . $transfers->id . ' :' . PHP_EOL .
            'Date: ' . $start->format('d/m/Y') . PHP_EOL .
            'Heure de prise en charge: ' . $start->format('H:i') . PHP_EOL .
            'Lieu de prise en charge: ' . ($transfers->from ?: '-') . PHP_EOL .
            'Lieu de dépose: ' . ($transfers->target ?: '-') . PHP_EOL .
            'Service: ' . $serviceName . PHP_EOL .
            $providerLabelFr . ': ' . $clientDriverName . PHP_EOL .
            'Téléphone ' . $providerLabelLowerFr . ': ' . $clientDriverPhone . PHP_EOL .
            'Véhicule: ' . $clientVehicle . PHP_EOL . PHP_EOL .
            'Cordialement,' . PHP_EOL .
            'Paris Via',
        'en' => 'Hello,' . PHP_EOL . PHP_EOL .
            'Here are your driver details for transfer #' . $transfers->id . ':' . PHP_EOL .
            'Date: ' . $start->format('d/m/Y') . PHP_EOL .
            'Pick-up time: ' . $start->format('H:i') . PHP_EOL .
            'Pick-up location: ' . ($transfers->from ?: '-') . PHP_EOL .
            'Drop-off location: ' . ($transfers->target ?: '-') . PHP_EOL .
            'Service: ' . $serviceName . PHP_EOL .
            $providerLabelEn . ': ' . $clientDriverName . PHP_EOL .
            $providerLabelEn . ' phone: ' . $clientDriverPhone . PHP_EOL .
            'Vehicle: ' . $clientVehicle . PHP_EOL . PHP_EOL .
            'Best regards,' . PHP_EOL .
            'Paris Via',
        'tr' => 'Merhaba,' . PHP_EOL . PHP_EOL .
            '#' . $transfers->id . ' numaralı transferiniz için şöför bilgileriniz aşağıdadır:' . PHP_EOL .
            'Tarih: ' . $start->format('d/m/Y') . PHP_EOL .
            'Alış saati: ' . $start->format('H:i') . PHP_EOL .
            'Alış noktası: ' . ($transfers->from ?: '-') . PHP_EOL .
            'Bırakış noktası: ' . ($transfers->target ?: '-') . PHP_EOL .
            'Servis: ' . $serviceName . PHP_EOL .
            $providerLabelTr . ': ' . $clientDriverName . PHP_EOL .
            $providerLabelTr . ' telefonu: ' . $clientDriverPhone . PHP_EOL .
            'Araç: ' . $clientVehicle . PHP_EOL . PHP_EOL .
            'Saygılarımızla,' . PHP_EOL .
            'Paris Via',
    ];
    $clientWhatsappText = $clientWhatsappTexts['fr'];
    $driverAppConfirmedAt = $transfers->driver_app_confirmed_at ? \Carbon\Carbon::parse($transfers->driver_app_confirmed_at) : null;
    $driverAppConfirmedBy = $transfers->driver_app_confirmed_by ? \App\Models\User::find($transfers->driver_app_confirmed_by) : null;
    $driverAppRefusedAt = ($transfers->driver_app_refused_at ?? null) ? \Carbon\Carbon::parse($transfers->driver_app_refused_at) : null;
    $driverAppRefusedBy = ($transfers->driver_app_refused_by ?? null) ? \App\Models\User::find($transfers->driver_app_refused_by) : null;
    $driverReconfirmAt = ($transfers->driver_app_reconfirm_required_at ?? null) ? \Carbon\Carbon::parse($transfers->driver_app_reconfirm_required_at) : null;
    $driverReconfirmReason = $transfers->driver_app_reconfirm_reason ?? null;
    $doubleEquipageEnabled = \Illuminate\Support\Facades\Schema::hasColumn('transfers', 'second_driver_id');
    $hasSecondDriver = $doubleEquipageEnabled && ($transfers->second_driver_id ?? null) && $transfers->secondDriver;
    $secondDriverConfirmedAt = ($doubleEquipageEnabled && ($transfers->second_driver_app_confirmed_at ?? null)) ? \Carbon\Carbon::parse($transfers->second_driver_app_confirmed_at) : null;
    $secondDriverConfirmedBy = ($doubleEquipageEnabled && ($transfers->second_driver_app_confirmed_by ?? null)) ? \App\Models\User::find($transfers->second_driver_app_confirmed_by) : null;
    $secondDriverRefusedAt = ($doubleEquipageEnabled && ($transfers->second_driver_app_refused_at ?? null)) ? \Carbon\Carbon::parse($transfers->second_driver_app_refused_at) : null;
    $secondDriverRefusedBy = ($doubleEquipageEnabled && ($transfers->second_driver_app_refused_by ?? null)) ? \App\Models\User::find($transfers->second_driver_app_refused_by) : null;
    $secondDriverReconfirmAt = ($doubleEquipageEnabled && ($transfers->second_driver_app_reconfirm_required_at ?? null)) ? \Carbon\Carbon::parse($transfers->second_driver_app_reconfirm_required_at) : null;
    $secondDriverReconfirmReason = $transfers->second_driver_app_reconfirm_reason ?? null;
    $missionPublicReady = (bool) $driverAppConfirmedAt && (!$hasSecondDriver || (bool) $secondDriverConfirmedAt);
    $missionPublicBlocker = null;
    if (!$driverAppConfirmedAt) {
        $missionPublicBlocker = 'Disponible après confirmation du ' . $providerLabelLowerFr . ' principal.';
    } elseif ($hasSecondDriver && !$secondDriverConfirmedAt) {
        $missionPublicBlocker = 'Disponible après confirmation du 2e chauffeur.';
    }
    $driverConfirmUrl = route('showdriver', $transfers->id);
    $publicMissionToken = \App\Http\Controllers\MissionController::publicMissionTokenFor((int) $transfers->id);
    $publicMissionUrl = route('mission.public', $publicMissionToken);
    $secondDriverPublicMissionUrl = $publicMissionUrl . '?driver_role=second_driver';
    $driverConfirmPhone = $hasRealDriver ? (($transfers->driver->whatsapp ?? null) ?: ($transfers->driver->tel ?? null)) : null;
    $driverConfirmPhoneClean = $driverConfirmPhone ? preg_replace('/\D+/', '', $driverConfirmPhone) : '';
    $driverConfirmText = implode(PHP_EOL, [
        'Bonjour '.($hasRealDriver ? $transfers->driver->name : $providerLabelFr).',',
        'Merci de confirmer votre mission.',
        'Transfert #'.$transfers->id.' - '.$start->format('d/m/Y H:i'),
        'Service: '.$serviceName,
        'Départ: '.($transfers->from ?: '-'),
        'Arrivée: '.($transfers->target ?: '-'),
        'Lien de confirmation: '.$secondDriverPublicMissionUrl,
        'Merci.',
    ]);
    $publicDriverConfirmText = implode(PHP_EOL, [
        'Bonjour '.($hasRealDriver ? $transfers->driver->name : $providerLabelFr).',',
        'Merci de confirmer votre service.',
        'Transfert #'.$transfers->id.' - '.$start->format('d/m/Y H:i'),
        'Service: '.$serviceName,
        'Départ: '.($transfers->from ?: '-'),
        'Arrivée: '.($transfers->target ?: '-'),
        'Lien public confirmation / mission: '.$publicMissionUrl,
        'Merci.',
    ]);
    $secondDriverConfirmPhone = $hasSecondDriver ? (($transfers->secondDriver->whatsapp ?? null) ?: ($transfers->secondDriver->tel ?? null)) : null;
    $secondDriverConfirmPhoneClean = $secondDriverConfirmPhone ? preg_replace('/\D+/', '', $secondDriverConfirmPhone) : '';
    $secondDriverConfirmText = implode(PHP_EOL, [
        'Bonjour '.($hasSecondDriver ? $transfers->secondDriver->name : 'Capitaine').',',
        'Merci de confirmer votre mission en double équipage.',
        'Transfert #'.$transfers->id.' - '.$start->format('d/m/Y H:i'),
        'Service: '.$serviceName,
        'Départ: '.($transfers->from ?: '-'),
        'Arrivée: '.($transfers->target ?: '-'),
        'Lien de confirmation: '.$driverConfirmUrl,
        'Merci.',
    ]);

    if (isset($transfers->status->id) && $transfers->status->id > 1) {
        $whatsappText = '<<SERVICE DE TRANSPORT PUBLIC DE PERSONNES - BILLET COLLECTIF>>' . PHP_EOL .
            '(Arrêté du 14 février 1986 - Article 5) et ordre de mission (Arrêté du 6 janvier 1993 - Article 3)' . PHP_EOL . PHP_EOL .
            'Date: ' . $start->format('d/m/Y D') . PHP_EOL .
            '*En route: ' . $ofisStart->format('H:i') . '*' . PHP_EOL .
            '*Heure prise en charge: ' . $surplaceTime->format('H:i') . '*' . PHP_EOL .
            'Heure de dépose: ' . $end->format('H:i') . PHP_EOL .
            'Service: ' . $serviceName . PHP_EOL .
            'Passagers: ' . $transfers->pax . ' pax' . PHP_EOL .
            '*Lieu de prise en charge: ' . $transfers->from . '*' . PHP_EOL .
            'Lieu de dépose: ' . $transfers->target . PHP_EOL .
            'Véhicule: ' . ($hasRealVehicule ? $transfers->vehicule->name : ($requiresRealVehicule ? 'Non défini' : 'Non requis')) . PHP_EOL .
            $providerLabelFr . ': ' . ($hasRealDriver ? $transfers->driver->name : 'Non défini') . PHP_EOL .
            'Commentaires: ' . ($transfers->comments ?? '-') . PHP_EOL .
            ($missionPublicReady ? 'Lien suivi mission: ' . $publicMissionUrl . PHP_EOL : 'Lien confirmation ' . $providerLabelLowerFr . ': ' . $publicMissionUrl . PHP_EOL) .
            'Contact:' . PHP_EOL . ($clientContact ?: '-') . PHP_EOL . PHP_EOL .
            'Trajets:' . PHP_EOL;

        foreach ($transfers->trajets as $index => $trajet) {
            $trajetTime = \Carbon\Carbon::parse($trajet->datetime);
            $whatsappText .= ($index + 1) . '. ' . $trajetTime->format($trajetTime->isSameDay($start) ? 'H:i' : 'd/m/Y H:i') .
                ' - ' . $trajet->type . ': ' . $trajet->from . ' ' . $trajet->google_address . PHP_EOL;
        }
    } else {
        $whatsappText = $serviceName . ' - ANNULÉ' . PHP_EOL .
            'Date: ' . $start->format('d/m/Y D H:i') . PHP_EOL .
            'Départ: ' . $transfers->from . PHP_EOL .
            'Commentaires: ' . ($transfers->comments ?? '-');
    }
@endphp

<style>
.transfer-show {
    max-width: 1320px;
    margin: 0 auto;
    color: #1f2937;
}
.transfer-hero {
    border: 1px solid #dbe4ef;
    border-left: 6px solid var(--transfer-status, #64748b);
    border-radius: 8px;
    background: #fff;
    padding: 16px;
    box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
}
.transfer-title {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}
.transfer-title h1 {
    font-size: 24px;
    margin: 0;
    font-weight: 800;
    letter-spacing: 0;
}
.transfer-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: flex-end;
}
.info-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-top: 14px;
}
.info-tile {
    border: 1px solid #e5e7eb;
    border-radius: 7px;
    background: #f8fafc;
    padding: 10px 12px;
    min-width: 0;
}
.info-label {
    display: block;
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
}
.info-value {
    display: block;
    color: #111827;
    font-size: 14px;
    font-weight: 750;
    overflow-wrap: anywhere;
}
.transfer-panel {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
    margin-top: 14px;
    overflow: hidden;
}
.panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 11px 14px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
}
.panel-head h2 {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
}
.panel-body {
    padding: 14px;
}
.timeline-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}
.time-card {
    border: 1px solid #e2e8f0;
    border-radius: 7px;
    padding: 10px;
    background: #fff;
}
.time-card strong {
    display: block;
    font-size: 18px;
    line-height: 1.2;
}
.time-card span {
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
}
.public-link-box {
    border: 1px solid #dbe4ef;
    background: #f8fafc;
    border-radius: 8px;
    padding: 10px;
    margin-bottom: 12px;
}
.public-link-box.locked {
    border-color: #fde68a;
    background: #fffbeb;
}
.public-link-box.ready {
    border-color: #bbf7d0;
    background: #f0fdf4;
}
.public-link-box label {
    display: block;
    color: #64748b;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    margin-bottom: 6px;
}
.public-link-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto;
    gap: 6px;
    align-items: center;
}
.public-link-row input {
    font-size: 12px;
}
@media (max-width: 640px) {
    .public-link-row { grid-template-columns: 1fr; }
}
.route-list {
    list-style: none;
    margin: 0;
    padding: 0;
}
.route-list li {
    display: grid;
    grid-template-columns: 42px 115px minmax(0, 1fr);
    gap: 10px;
    padding: 10px 0;
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
    font-weight: 800;
}
.route-time {
    font-weight: 800;
    color: #334155;
}
.route-place {
    min-width: 0;
    overflow-wrap: anywhere;
}
.assignment-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
.assignment-box {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px;
    background: #fbfdff;
}
.assignment-box .icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #eef2ff;
    color: #1d4ed8;
    margin-right: 8px;
}
.comment-box, .whatsapp-box {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 7px;
    padding: 12px;
    white-space: pre-line;
}
.form-compact label {
    font-size: 12px;
    font-weight: 800;
    color: #475569;
}
.voucher-list a {
    display: inline-block;
    margin: 2px 5px 2px 0;
}
@media (max-width: 991px) {
    .info-grid, .timeline-grid, .assignment-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .transfer-actions { justify-content: flex-start; margin-top: 10px; }
}
@media (max-width: 640px) {
    .info-grid, .timeline-grid, .assignment-grid { grid-template-columns: 1fr; }
    .route-list li { grid-template-columns: 34px minmax(0, 1fr); }
    .route-time { grid-column: 2; }
.transfer-title h1 { font-size: 20px; }
}
.overnight-banner {
    margin-top: 14px;
    border: 1px solid #bae6fd;
    background: #f0f9ff;
    color: #075985;
    border-radius: 8px;
    padding: 10px 12px;
    display: flex;
    gap: 10px;
    align-items: flex-start;
    font-weight: 800;
}
.overnight-banner small {
    display: block;
    color: #0369a1;
    font-weight: 700;
    margin-top: 2px;
}
</style>

<div class="transfer-show" style="--transfer-status: {{ $statusHex }};">
    <div class="transfer-hero">
        <div class="row align-items-start">
            <div class="col-lg-7">
                <div class="transfer-title">
                    <h1>Transfert #{{ $transfers->id }}</h1>
                    <span class="badge bg-{{ $statusColor }}">{{ $statusName }}</span>
                    <span class="badge bg-light text-dark border">Dossier #{{ optional($post)->id }}</span>
                </div>
                <div class="mt-2 text-muted">
                    {{ optional($acente)->name ?? 'Agence non définie' }}
                    @if(optional($post)->id)
                        <a href="{{ route('posts.show', $post->id) }}" class="ml-2">Voir le dossier</a>
                    @endif
                </div>
            </div>
            <div class="col-lg-5">
                <div class="transfer-actions">
                    <a class="btn btn-outline-secondary btn-sm" href="{{ url()->previous() }}"><i class="fa fa-arrow-left"></i> Retour</a>
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('transfers.edit', $transfers->id) }}"><i class="fa fa-edit"></i> Modifier</a>
                    <button onclick="window.print()" class="btn btn-outline-dark btn-sm"><i class="fa fa-print"></i> Imprimer</button>
                    @if($canOperate)
                        <a href="{{ route('transfershowpdf', $transfers->id) }}" class="btn btn-outline-danger btn-sm"><i class="fa fa-file-pdf"></i> Confirmation</a>
                        <a href="{{ route('transferMissionshowpdf', $transfers->id) }}" class="btn btn-outline-danger btn-sm"><i class="fa fa-road"></i> Feuille de route</a>
                        <a href="{{ route('driver-vehicle-overnights.create', ['transfer_id' => $transfers->id]) }}" class="btn btn-outline-info btn-sm"><i class="fa fa-bed"></i> Découcher</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-tile"><span class="info-label">Service</span><span class="info-value">{{ $serviceName }}</span></div>
            <div class="info-tile"><span class="info-label">Date</span><span class="info-value">{{ $start->format('d/m/Y') }}</span></div>
            <div class="info-tile"><span class="info-label">Horaires</span><span class="info-value">{{ $start->format('H:i') }} - {{ $end->format('H:i') }}</span><span class="info-subvalue">{{ $durationHours }} h prévues</span></div>
            <div class="info-tile"><span class="info-label">Passagers</span><span class="info-value">{{ $transfers->pax }} pax</span></div>
        </div>

        @if($currentOvernight)
            <div class="overnight-banner">
                <i class="fa fa-bed mt-1"></i>
                <div>
                    Découcher prévu pour cette nuit
                    <small>
                        {{ optional($currentOvernight->overnight_date)->format('d/m/Y') }}
                        · {{ optional($currentOvernight->driver)->name ?? 'Chauffeur non défini' }}
                        · {{ $currentOvernight->city ?: ($currentOvernight->google_address ?: $currentOvernight->address ?: 'Lieu non défini') }}
                    </small>
                </div>
            </div>
        @endif
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="transfer-panel">
                <div class="panel-head"><h2>Itinéraire</h2></div>
                <div class="panel-body">
                    <div class="comment-box mb-3"><strong>{{ $transfers->from }}</strong> -> <strong>{{ $transfers->target }}</strong></div>
                    @if ($transfers->trajets->count() == 0)
                        <div class="alert alert-warning mb-0">Anciennes informations: le trajet doit être ressaisi.</div>
                    @else
                        <ul class="route-list">
                            @foreach ($transfers->trajets as $index => $trajet)
                                @php $trajetTime = \Carbon\Carbon::parse($trajet->datetime); @endphp
                                <li>
                                    <div><span class="route-index">{{ $index + 1 }}</span></div>
                                    <div class="route-time">{{ $trajetTime->format($trajetTime->isSameDay($start) ? 'H:i' : 'd/m H:i') }}</div>
                                    <div class="route-place">
                                        <strong>{{ ucfirst($trajet->type) }}:</strong> {{ $trajet->from }}
                                        @if($trajet->google_address)
                                            <div class="text-muted small">{{ $trajet->google_address }}</div>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="transfer-panel">
                <div class="panel-head"><h2>Commentaires et passagers</h2></div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="comment-box">{{ $transfers->comments ?: 'Aucun commentaire.' }}</div>
                        </div>
                        <div class="col-md-6">
                            @if($clients->count())
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead><tr><th>Passager</th><th>Téléphone</th></tr></thead>
                                        <tbody>
                                            @foreach($clients as $client)
                                                <tr>
                                                    <td>{{ trim(($client->name ?? '') . ' ' . ($client->surname ?? '')) ?: '-' }}</td>
                                                    <td>{{ $client->tel ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-muted">Aucun passager renseigné.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="transfer-panel">
                <div class="panel-head"><h2>Mission</h2></div>
                <div class="panel-body">
                    @if ($transfers->mission == true)
                        <a href="{{ route('mission', $transfers->id) }}" class="btn btn-danger btn-sm mb-3"><i class="fa fa-play"></i> Démarrer la mission</a>
                    @endif

                    @if (isset($transfers->missionr->hareket))
                        <div class="timeline-grid mb-3">
                            <div class="time-card"><span>Prise de service réelle</span><strong>{{ $transfers->missionr->hareket ? date('H:i', strtotime($transfers->missionr->hareket)) : '-' }}</strong></div>
                            <div class="time-card"><span>Sur place réel</span><strong>{{ $transfers->missionr->surplace ? date('H:i', strtotime($transfers->missionr->surplace)) : '-' }}</strong></div>
                            <div class="time-card"><span>Prise en charge réelle</span><strong>{{ $transfers->missionr->taked ? date('H:i', strtotime($transfers->missionr->taked)) : '-' }}</strong></div>
                            <div class="time-card"><span>Fin de mission réelle</span><strong>{{ $transfers->missionr->finish ? date('H:i', strtotime($transfers->missionr->finish)) : '-' }}</strong></div>
                        </div>
                        <form method="POST" action="{{ route('update_times') }}" class="form-compact">
                            @csrf
                            <div class="row">
                                @if ($transfers->missionr->hareket)
                                    <div class="col-md-6 form-group"><label>Prise de service réelle</label><input type="datetime-local" class="form-control" name="new_hareket" value="{{ date('Y-m-d\TH:i', strtotime($transfers->missionr->hareket)) }}"></div>
                                @endif
                                @if ($transfers->missionr->surplace)
                                    <div class="col-md-6 form-group"><label>Sur place réel</label><input type="datetime-local" class="form-control" name="new_surplace" value="{{ date('Y-m-d\TH:i', strtotime($transfers->missionr->surplace)) }}"></div>
                                @endif
                                @if ($transfers->missionr->taked)
                                    <div class="col-md-6 form-group"><label>Prise en charge réelle</label><input type="datetime-local" class="form-control" name="new_taked" value="{{ date('Y-m-d\TH:i', strtotime($transfers->missionr->taked)) }}"></div>
                                @endif
                                @if ($transfers->missionr->finish)
                                    <div class="col-md-6 form-group"><label>Fin de mission réelle</label><input type="datetime-local" class="form-control" name="new_finish" value="{{ date('Y-m-d\TH:i', strtotime($transfers->missionr->finish)) }}"></div>
                                @endif
                            </div>
                            <input type="hidden" name="transfers_id" value="{{ $transfers->id }}">
                            <button type="submit" class="btn btn-primary btn-sm">Mettre à jour les horaires</button>
                        </form>
                    @else
                        <div class="text-muted">Aucune mission réelle enregistrée.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="transfer-panel">
                <div class="panel-head"><h2>Horaires opérationnels</h2></div>
                <div class="panel-body">
                    <div class="timeline-grid" style="grid-template-columns: 1fr 1fr;">
                        <div class="time-card"><span>En route</span><strong>{{ $ofisStart->format('H:i') }}</strong></div>
                        <div class="time-card"><span>Sur place</span><strong>{{ $surplaceTime->format('H:i') }}</strong></div>
                        <div class="time-card"><span>Prise en charge</span><strong>{{ $start->format('H:i') }}</strong></div>
                        <div class="time-card"><span>Fin prévue</span><strong>{{ $end->format('H:i') }}</strong></div>
                    </div>
                    <hr>
                    @if($canOperate)
                        <form method="POST" action="{{ route('updateofisstart') }}" class="form-compact">
                            @csrf
                            <input type="hidden" name="transfer_id" value="{{ $transfers->id }}">
                            <label>Prise de service</label>
                            <input type="datetime-local" name="ofis_start" class="form-control" value="{{ $transfers->ofis_start ? date('Y-m-d\TH:i', strtotime($transfers->ofis_start)) : '' }}">
                            <button class="btn btn-primary btn-sm mt-2">Enregistrer</button>
                        </form>
                        @if ($transfers->ofis_start)
                            <form method="POST" action="{{ route('updateofisstartnull') }}" class="mt-2">
                                @csrf
                                <input type="hidden" name="transfer_id" value="{{ $transfers->id }}">
                                <button class="btn btn-outline-danger btn-sm">Effacer l'heure</button>
                            </form>
                        @endif
                    @elseif(!$transfers->ofis_start)
                        <div class="text-danger mt-2">Heure de prise de service non définie.</div>
                    @endif
                </div>
            </div>

            <div class="transfer-panel">
                <div class="panel-head">
                    <h2>Découcher</h2>
                    @if($canOperate)
                        <a href="{{ route('driver-vehicle-overnights.create', ['transfer_id' => $transfers->id]) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fa fa-plus"></i> Ajouter
                        </a>
                    @endif
                </div>
                <div class="panel-body">
                    @if($overnights->count())
                        @foreach($overnights as $overnight)
                            <div class="time-card mb-2">
                                <span>{{ optional($overnight->overnight_date)->format('d/m/Y') }} · {{ optional($overnight->driver)->name ?? 'Chauffeur non défini' }}</span>
                                <strong>{{ $overnight->city ?: ($overnight->google_address ?: $overnight->address ?: '-') }}</strong>
                                <div class="text-muted small">{{ optional($overnight->vehicule)->name ?? 'Véhicule non défini' }}</div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-muted">Aucun découcher lié à ce transfert.</div>
                    @endif
                </div>
            </div>

            <div class="transfer-panel">
                <div class="panel-head"><h2>Affectation</h2></div>
                <div class="panel-body">
                    <div class="public-link-box {{ $missionPublicReady ? 'ready' : 'locked' }}">
                        <label>Mission / suivi chauffeur</label>
                        @if($missionPublicReady)
                            <div class="public-link-row">
                                <input type="text" class="form-control form-control-sm" id="publicMissionLink" value="{{ $publicMissionUrl }}" readonly>
                                <a class="btn btn-outline-primary btn-sm" target="_blank" href="{{ $publicMissionUrl }}">
                                    <i class="fa fa-external-link-alt"></i> Ouvrir
                                </a>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('publicMissionLink').value)">
                                    <i class="fa fa-copy"></i> Copier
                                </button>
                            </div>
                            <div class="text-muted small mt-1">Lien sans connexion pour suivi mission, km, frais et photos.</div>
                        @else
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="badge bg-warning text-dark">Mission verrouillée</span>
                                <span class="text-muted small">{{ $missionPublicBlocker }}</span>
                            </div>
                            <div class="transfer-actions justify-content-start mt-2">
                                <a class="btn btn-outline-primary btn-sm" target="_blank" href="{{ $publicMissionUrl }}">
                                    <i class="fa fa-check-circle"></i> Ouvrir lien public
                                </a>
                                @if($driverConfirmPhoneClean)
                                    <a class="btn btn-success btn-sm" target="_blank" href="https://api.whatsapp.com/send?phone={{ $driverConfirmPhoneClean }}&text={{ rawurlencode($publicDriverConfirmText) }}">
                                        <i class="fab fa-whatsapp"></i> Envoyer confirmation
                                    </a>
                                @endif
                                <a class="btn btn-outline-secondary btn-sm" target="_blank" href="{{ $driverConfirmUrl }}">
                                    <i class="fa fa-user-lock"></i> Interface chauffeur connecté
                                </a>
                            </div>
                        @endif
                    </div>

                    <div class="assignment-grid" style="grid-template-columns: 1fr;">
                        <div class="assignment-box">
                            <span class="icon"><i class="fa fa-user-tie"></i></span>
                            <strong>{{ $providerLabelFr }}</strong>
                            <div class="mt-2">
                                @if($hasRealDriver)
                                    <a href="{{ route('acentes.show', $transfers->driver->id) }}?src=service">{{ $transfers->driver->name }}</a>
                                    <div class="text-muted small">{{ $transfers->driver->tel ?: 'Téléphone non renseigné' }}</div>
                                    <div class="mt-2">
                                        <strong class="d-block small text-muted">Confirmation {{ $providerLabelLowerFr }}</strong>
                                        @if($driverAppRefusedAt)
                                            <span class="badge bg-danger">Service refusé {{ $driverAppRefusedAt->format('d/m/Y H:i') }}</span>
                                            @if($driverAppRefusedBy)
                                                <div class="text-muted small">Par {{ $driverAppRefusedBy->name }}{{ $driverAppRefusedBy->email ? ' - '.$driverAppRefusedBy->email : '' }}</div>
                                            @endif
                                        @elseif($driverAppConfirmedAt)
                                            <span class="badge bg-success">Confirmé {{ $driverAppConfirmedAt->format('d/m/Y H:i') }}</span>
                                            @if($driverAppConfirmedBy)
                                                <div class="text-muted small">Par {{ $driverAppConfirmedBy->name }}{{ $driverAppConfirmedBy->email ? ' - '.$driverAppConfirmedBy->email : '' }}</div>
                                            @endif
                                        @else
                                            @if($driverReconfirmAt)
                                                <span class="badge bg-warning text-dark">Service modifié à reconfirmer {{ $driverReconfirmAt->format('d/m/Y H:i') }}</span>
                                                @if($driverReconfirmReason)<div class="text-muted small">{{ $driverReconfirmReason }}</div>@endif
                                            @else
                                                <span class="badge bg-warning text-dark">En attente</span>
                                            @endif
                                            <div class="transfer-actions justify-content-start mt-2">
                                                <a class="btn btn-outline-primary btn-sm" target="_blank" href="{{ $driverConfirmUrl }}"><i class="fa fa-external-link-alt"></i> Lien {{ $providerLabelLowerFr }}</a>
                                                @if($driverConfirmPhoneClean)
                                                    <a class="btn btn-success btn-sm" target="_blank" href="https://api.whatsapp.com/send?phone={{ $driverConfirmPhoneClean }}&text={{ rawurlencode($driverConfirmText) }}"><i class="fab fa-whatsapp"></i> Demander confirmation</a>
                                                @endif
                                                @if($canOperate)
                                                    <form method="POST" action="{{ route('transfers.driverAppConfirm', $transfers->id) }}" class="d-inline" onsubmit="return confirm('Confirmer au nom du {{ $providerLabelLowerFr }} principal ?');">
                                                        @csrf
                                                        <input type="hidden" name="driver_role" value="primary_driver">
                                                        <button class="btn btn-outline-success btn-sm" type="submit"><i class="fa fa-user-check"></i> Confirmer au nom du {{ $providerLabelLowerFr }}</button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="badge bg-danger">Sans {{ $providerLabelLowerFr }}</span>
                                @endif

                                @if($hasSecondDriver)
                                    <hr>
                                    <strong>Double équipage / 2e chauffeur</strong>
                                    <div class="mt-2">
                                        <a href="{{ route('acentes.show', $transfers->secondDriver->id) }}?src=service">{{ $transfers->secondDriver->name }}</a>
                                        <div class="text-muted small">{{ $transfers->secondDriver->tel ?: 'Téléphone non renseigné' }}</div>
                                        <div class="mt-2">
                                            <strong class="d-block small text-muted">Confirmation 2e chauffeur</strong>
                                            @if($secondDriverRefusedAt)
                                                <span class="badge bg-danger">Service refusé {{ $secondDriverRefusedAt->format('d/m/Y H:i') }}</span>
                                                @if($secondDriverRefusedBy)
                                                    <div class="text-muted small">Par {{ $secondDriverRefusedBy->name }}{{ $secondDriverRefusedBy->email ? ' - '.$secondDriverRefusedBy->email : '' }}</div>
                                                @endif
                                            @elseif($secondDriverConfirmedAt)
                                                <span class="badge bg-success">Confirmé {{ $secondDriverConfirmedAt->format('d/m/Y H:i') }}</span>
                                                @if($secondDriverConfirmedBy)
                                                    <div class="text-muted small">Par {{ $secondDriverConfirmedBy->name }}{{ $secondDriverConfirmedBy->email ? ' - '.$secondDriverConfirmedBy->email : '' }}</div>
                                                @endif
                                            @else
                                                @if($secondDriverReconfirmAt)
                                                    <span class="badge bg-warning text-dark">Service modifié à reconfirmer {{ $secondDriverReconfirmAt->format('d/m/Y H:i') }}</span>
                                                    @if($secondDriverReconfirmReason)<div class="text-muted small">{{ $secondDriverReconfirmReason }}</div>@endif
                                                @else
                                                    <span class="badge bg-warning text-dark">En attente</span>
                                                @endif
                                                <div class="transfer-actions justify-content-start mt-2">
                                                    <a class="btn btn-outline-primary btn-sm" target="_blank" href="{{ $secondDriverPublicMissionUrl }}"><i class="fa fa-external-link-alt"></i> Lien public 2e chauffeur</a>
                                                    <a class="btn btn-outline-secondary btn-sm" target="_blank" href="{{ $driverConfirmUrl }}"><i class="fa fa-user-lock"></i> Interface chauffeur connecté</a>
                                                    @if($secondDriverConfirmPhoneClean)
                                                        <a class="btn btn-success btn-sm" target="_blank" href="https://api.whatsapp.com/send?phone={{ $secondDriverConfirmPhoneClean }}&text={{ rawurlencode($secondDriverConfirmText) }}"><i class="fab fa-whatsapp"></i> Demander confirmation 2e chauffeur</a>
                                                    @endif
                                                    @if($canOperate)
                                                        <form method="POST" action="{{ route('transfers.driverAppConfirm', $transfers->id) }}" class="d-inline" onsubmit="return confirm('Confirmer au nom du 2e chauffeur ?');">
                                                            @csrf
                                                            <input type="hidden" name="driver_role" value="second_driver">
                                                            <button class="btn btn-outline-success btn-sm" type="submit"><i class="fa fa-user-check"></i> Confirmer au nom du 2e chauffeur</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="assignment-box">
                            <span class="icon"><i class="fa fa-car-side"></i></span>
                            <strong>Véhicule</strong>
                            <div class="mt-2">
                                @if($hasRealVehicule)
                                    <a href="{{ route('vehicules.show', $transfers->vehicule_id) }}">{{ $transfers->vehicule->name }}</a>
                                    <div class="text-muted small">{{ $transfers->vehicule->plaka ?: 'Plaque non renseignée' }}</div>
                                    @if($transfers->vehicle_provider_acente_id || $transfers->external_vehicle_note || $transfers->external_vehicle_price)
                                        <div class="mt-2 small">
                                            @if($transfers->externalVehicleProvider)
                                                <span class="badge bg-warning text-dark">Véhicule extérieur: {{ $transfers->externalVehicleProvider->name }}</span>
                                            @endif
                                            @if($transfers->external_vehicle_price)
                                                <div class="text-muted">Prix fournisseur: {{ number_format((float)$transfers->external_vehicle_price, 2, ',', ' ') }} €</div>
                                            @endif
                                            @if($transfers->external_vehicle_note)
                                                <div class="text-muted">{{ $transfers->external_vehicle_note }}</div>
                                            @endif
                                        </div>
                                    @endif
                                @elseif($requiresRealVehicule)
                                    <span class="badge bg-warning text-dark">Sans véhicule réel</span>
                                @else
                                    <span class="badge bg-light text-muted border">Véhicule non requis</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @hasanyrole('Admin|ofis')
                        <hr>
                        <div class="small text-danger">
                            <strong>Paiement:</strong> {{ isset($transfers->harekets[0]->payment->name) ? $transfers->harekets[0]->payment->name : '-' }}
                            @if(isset($transfers->harekets[0]->offset_id) && $transfers->harekets[0]->offset_id > 0)
                                <strong>{{ abs($transfers->harekets[0]->offset->harekets[0]->amount) }}{{ $transfers->harekets[0]->offset->harekets[0]->kur->name }}</strong>
                            @endif
                        </div>
                    @endhasanyrole
                </div>
            </div>

            <div class="transfer-panel">
                <div class="panel-head"><h2>Statut</h2></div>
                <div class="panel-body">
                    {{ Form::model($transfers, ['route' => ['transfers.update', $transfers->id], 'method' => 'PUT', 'class' => 'transferupdate form-compact']) }}
                        <label>Statut chauffeur</label>
                        @if ($transfers->status_id == 3)
                            @php $disabledOptions = [2, 3]; @endphp
                            <select name="status_id" class="form-control">
                                @foreach($status as $key => $value)
                                    <option value="{{ $key }}" @if(in_array($key, $disabledOptions)) disabled @endif @if($key == $transfers->status_id) selected @endif>{{ $value }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-block mt-1">Pour modifier, informez le chauffeur et passez le statut en modification.</small>
                        @else
                            {{ Form::select('status_id', $status, $transfers->status_id, ['class' => 'form-control']) }}
                        @endif
                        {{ Form::hidden('dcomments', $transfers->dcomments) }}
                        <button class="btn btn-primary btn-sm mt-2">Enregistrer</button>
                        <span class="result ml-2"></span>
                    {{ Form::close() }}

                    <form action="{{ route('transfers.updateClientStatus', $transfers->id) }}" method="POST" class="form-compact mt-3">
                        @csrf
                        <label>Coordonnées {{ $providerLabelLowerFr }} envoyées au client</label>
                        <div>
                            <label class="mr-3"><input type="radio" name="client_status_id" value="1" {{ $transfers->client_status_id == 1 ? 'checked' : '' }}> Oui</label>
                            <label><input type="radio" name="client_status_id" value="0" {{ $transfers->client_status_id == 0 ? 'checked' : '' }}> Non</label>
                        </div>
                        @error('client_status_id')<p class="text-danger mb-1">{{ $message }}</p>@enderror
                        <button type="submit" class="btn btn-outline-primary btn-sm">Enregistrer</button>
                    </form>
                </div>
            </div>

            <div class="transfer-panel">
                <div class="panel-head"><h2>Notes {{ $providerLabelLowerFr }}</h2></div>
                <div class="panel-body">
                    {{ Form::model($transfers, ['route' => ['transfers.update', $transfers->id], 'method' => 'PUT', 'class' => 'form-compact']) }}
                        {{ Form::text('dcomments', $transfers->dcomments, ['class' => 'form-control', 'placeholder' => 'Commentaire chauffeur / guide / personnel']) }}
                        {{ Form::hidden('status_id', $transfers->status_id) }}
                        <button class="btn btn-primary btn-sm mt-2">Enregistrer</button>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>

    @if($canOperate)
        <div class="transfer-panel">
            <div class="panel-head"><h2>Communication {{ $providerLabelLowerFr }}</h2></div>
            <div class="panel-body">
                <div class="transfer-actions justify-content-start mb-3">
                    @if(isset($transfers->status->id) && $transfers->status->id > 1)
                        @if($hasRealDriver && isset($transfers->driver->whatsapp))
                            <a target="blank" href="{{ $transfers->driver->whatsapp }}" class="btn btn-success btn-sm"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                        @else
                            <a target="blank" href="https://api.whatsapp.com/send?{{ ($hasRealDriver && $transfers->driver->tel) ? 'phone='.$transfers->driver->tel.'&' : '' }}text={{ rawurlencode($whatsappText) }}" class="btn btn-success btn-sm"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                        @endif
                        <a href="{{ route('transfers.sms', $transfers->id) }}" class="btn btn-outline-success btn-sm"><i class="fa fa-sms"></i> SMS</a>
                        <form action="{{ route('send.whatsapp') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="transfer_id" value="{{ $transfers->id }}">
                            <button type="submit" class="btn btn-outline-success btn-sm"><i class="fa fa-paper-plane"></i> Envoyer WhatsApp</button>
                        </form>
                    @else
                        <a target="blank" href="https://api.whatsapp.com/send?{{ ($hasRealDriver && $transfers->driver->tel) ? 'phone='.$transfers->driver->tel.'&' : '' }}text={{ rawurlencode($whatsappText) }}" class="btn btn-danger btn-sm"><i class="fab fa-whatsapp"></i> WhatsApp annulation</a>
                    @endif
                </div>
                <label class="font-weight-bold">Texte WhatsApp</label>
                <textarea id="textArea" class="form-control whatsapp-box" rows="8">{{ $whatsappText }}</textarea>
                <button id="copyButton" class="btn btn-outline-primary btn-sm mt-2" type="button"><i class="fa fa-copy"></i> Copier</button>
            </div>
        </div>

        <div class="transfer-panel">
            <div class="panel-head"><h2>Communication client</h2></div>
            <div class="panel-body">
                <div class="transfer-actions justify-content-start mb-3">
                    @forelse($clientPhones as $clientPhone)
                        <a target="blank" href="https://api.whatsapp.com/send?phone={{ $clientPhone['phone'] }}&text={{ rawurlencode($clientWhatsappTexts['fr']) }}" class="btn btn-success btn-sm client-whatsapp-link" data-phone="{{ $clientPhone['phone'] }}">
                            <i class="fab fa-whatsapp"></i> WhatsApp {{ $clientPhone['name'] }}
                        </a>
                    @empty
                        <span class="badge bg-warning text-dark">Téléphone client non renseigné</span>
                    @endforelse
                    <a href="tel:{{ $clientPhones->first()['phone'] ?? '' }}" class="btn btn-outline-primary btn-sm {{ $clientPhones->isEmpty() ? 'disabled' : '' }}"><i class="fa fa-phone"></i> Appeler client</a>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="font-weight-bold">Clients</label>
                        <div class="small text-muted" style="white-space: pre-line;">{{ $clientContact ?: 'Aucun contact client renseigné' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="font-weight-bold">{{ $providerLabelFr }} envoyé au client</label>
                        <div>
                            @if($transfers->client_status_id == 1)
                                <span class="badge bg-success">Oui</span>
                            @else
                                <span class="badge bg-warning text-dark">Non</span>
                            @endif
                        </div>
                    </div>
                </div>

                <ul class="nav nav-tabs mb-2" id="clientMessageTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active client-message-tab" type="button" data-lang="fr" data-target="clientTextAreaFr">Français</button></li>
                    <li class="nav-item"><button class="nav-link client-message-tab" type="button" data-lang="en" data-target="clientTextAreaEn">English</button></li>
                    <li class="nav-item"><button class="nav-link client-message-tab" type="button" data-lang="tr" data-target="clientTextAreaTr">Türkçe</button></li>
                </ul>

                <div class="client-message-pane" data-lang="fr">
                    <label class="font-weight-bold">Texte WhatsApp client</label>
                    <textarea id="clientTextAreaFr" class="form-control whatsapp-box client-message-text" rows="8">{{ $clientWhatsappTexts['fr'] }}</textarea>
                    <button class="btn btn-outline-primary btn-sm mt-2 copy-client-language" type="button" data-target="clientTextAreaFr"><i class="fa fa-copy"></i> Copier</button>
                </div>
                <div class="client-message-pane d-none" data-lang="en">
                    <label class="font-weight-bold">Client WhatsApp text</label>
                    <textarea id="clientTextAreaEn" class="form-control whatsapp-box client-message-text" rows="8">{{ $clientWhatsappTexts['en'] }}</textarea>
                    <button class="btn btn-outline-primary btn-sm mt-2 copy-client-language" type="button" data-target="clientTextAreaEn"><i class="fa fa-copy"></i> Copy</button>
                </div>
                <div class="client-message-pane d-none" data-lang="tr">
                    <label class="font-weight-bold">Müşteri WhatsApp metni</label>
                    <textarea id="clientTextAreaTr" class="form-control whatsapp-box client-message-text" rows="8">{{ $clientWhatsappTexts['tr'] }}</textarea>
                    <button class="btn btn-outline-primary btn-sm mt-2 copy-client-language" type="button" data-target="clientTextAreaTr"><i class="fa fa-copy"></i> Kopyala</button>
                </div>
            </div>
        </div>
    @endif

    <div class="transfer-panel mb-4">
        <div class="panel-head"><h2>Voucher</h2></div>
        <div class="panel-body">
            <form action="{{ route('transfers.voucherupload') }}" method="POST" enctype="multipart/form-data" class="form-compact">
                @csrf
                <div class="custom-file">
                    <input type="file" name="image" class="custom-file-input" id="customFile">
                    <input type="hidden" name="id" value="{{ $transfers->id }}">
                    <input type="hidden" name="post_id" value="{{ $transfers->post_id }}">
                    <label class="custom-file-label" for="customFile">Choisir une image</label>
                </div>
                <button type="submit" class="btn btn-primary btn-sm mt-2">Téléverser</button>
            </form>
            <div class="voucher-list mt-3">
                <strong>Fichiers:</strong>
                @forelse ($files as $file)
                    <a href="/voucher/{{ $transfers->id }}/{{ basename($file) }}" class="badge bg-light text-dark border">{{ basename($file) }}</a>
                @empty
                    <span class="text-muted">Aucun voucher.</span>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer')
<script>
$('.transferupdate').on('submit', function(e) {
    var form = $(this);
    var submit = form.find('[type=submit]');
    var result = form.find('.result');
    e.preventDefault();
    $.ajax({
        type: form.attr('method'),
        url: form.attr('action'),
        data: form.serialize(),
        beforeSend: function() {
            submit.prop('disabled', true).text('Enregistrement...');
            result.html('');
        },
        success: function() {
            submit.prop('disabled', false).text('Enregistrer');
            result.html('<i class="fa fa-check text-success"></i>');
        },
        error: function() {
            submit.prop('disabled', false).text('Enregistrer');
            result.html('<span class="text-danger">Erreur</span>');
        }
    });
});

$('.custom-file-input').on('change', function() {
    var fileName = $(this).val().split('\\').pop();
    $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
});

document.addEventListener('DOMContentLoaded', function() {
    function bindCopyButton(buttonId, textAreaId) {
        var button = document.getElementById(buttonId);
        var textArea = document.getElementById(textAreaId);

        if (!button || !textArea) return;

        button.addEventListener('click', function() {
            textArea.select();
            document.execCommand('copy');
            button.innerHTML = '<i class="fa fa-check"></i> Copié';
        });
    }

    bindCopyButton('copyButton', 'textArea');

    function activeClientText() {
        var activePane = document.querySelector('.client-message-pane:not(.d-none) textarea');
        return activePane ? activePane.value : '';
    }

    function refreshClientWhatsappLinks() {
        var text = encodeURIComponent(activeClientText());
        document.querySelectorAll('.client-whatsapp-link').forEach(function(link) {
            link.href = 'https://api.whatsapp.com/send?phone=' + link.dataset.phone + '&text=' + text;
        });
    }

    document.querySelectorAll('.client-message-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.client-message-tab').forEach(function(item) { item.classList.remove('active'); });
            document.querySelectorAll('.client-message-pane').forEach(function(pane) { pane.classList.add('d-none'); });

            tab.classList.add('active');
            var pane = document.querySelector('.client-message-pane[data-lang="' + tab.dataset.lang + '"]');
            if (pane) pane.classList.remove('d-none');
            refreshClientWhatsappLinks();
        });
    });

    document.querySelectorAll('.copy-client-language').forEach(function(button) {
        button.addEventListener('click', function() {
            var textArea = document.getElementById(button.dataset.target);
            if (!textArea) return;
            textArea.select();
            document.execCommand('copy');
            button.innerHTML = '<i class="fa fa-check"></i> Copié';
        });
    });

    document.querySelectorAll('.client-message-text').forEach(function(textArea) {
        textArea.addEventListener('input', refreshClientWhatsappLinks);
    });

    refreshClientWhatsappLinks();
});
</script>
@endsection
