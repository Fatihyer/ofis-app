<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Feuille de route</title>
    <style>
        @page { margin: 18px 22px; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10.5px;
            color: #1f2933;
            line-height: 1.28;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; text-align: left; }
        .topbar { border-bottom: 3px solid #111827; padding-bottom: 8px; margin-bottom: 8px; }
        .brand { font-size: 17px; font-weight: bold; letter-spacing: .3px; color: #111827; }
        .company-lines { font-size: 9.5px; color: #374151; margin-top: 4px; }
        .logo { max-width: 142px; max-height: 62px; }
        .doc-title {
            background: #111827;
            color: #fff;
            padding: 8px 10px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .doc-subtitle {
            border: 1px solid #111827;
            border-top: 0;
            padding: 6px 10px;
            font-size: 9.5px;
            color: #374151;
        }
        .badge {
            display: inline-block;
            border: 1px solid #111827;
            padding: 3px 7px;
            font-weight: bold;
            font-size: 10px;
            margin-left: 4px;
        }
        .section { margin-top: 8px; border: 1px solid #cbd5e1; }
        .section-title {
            background: #eef2f7;
            color: #111827;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            padding: 5px 7px;
            border-bottom: 1px solid #cbd5e1;
            letter-spacing: .25px;
        }
        .section-body { padding: 6px 7px; }
        .label { color: #667085; font-size: 8.8px; text-transform: uppercase; }
        .value { font-weight: bold; color: #111827; font-size: 10.5px; }
        .muted { color: #667085; }
        .box { border: 1px solid #d0d5dd; padding: 5px; min-height: 18px; }
        .soft-box { border: 1px solid #d0d5dd; background: #f8fafc; padding: 5px; }
        .grid td { padding: 4px; }
        .data-table th {
            background: #f2f4f7;
            border: 1px solid #cbd5e1;
            padding: 5px;
            font-size: 9px;
            text-transform: uppercase;
        }
        .data-table td {
            border: 1px solid #d0d5dd;
            padding: 5px;
            min-height: 16px;
        }
        .route-table td { border-bottom: 1px solid #e4e7ec; padding: 5px 3px; }
        .route-step { width: 24px; text-align: center; }
        .step-pill {
            display: inline-block;
            width: 18px;
            height: 18px;
            line-height: 18px;
            border-radius: 9px;
            background: #111827;
            color: #fff;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
        }
        .notice {
            border: 1px solid #d0d5dd;
            padding: 6px 8px;
            font-size: 9px;
            color: #475467;
            margin-top: 8px;
        }
        .signature-box { height: 42px; border: 1px solid #111827; margin-top: 4px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
@php
    $surplaceBeforeMinutes = optional($surplaceMinBefore)->value ?? 0;
    $comments = (string) ($transfer->comments ?? '');
    $start = $transfer->start_date ? \Carbon\Carbon::parse($transfer->start_date) : null;
    $end = $transfer->end_date ? \Carbon\Carbon::parse($transfer->end_date) : null;
    $ofisStart = $transfer->ofis_start ? \Carbon\Carbon::parse($transfer->ofis_start) : null;
    $serviceStart = $start ? $start->copy()->subMinutes((int) $surplaceBeforeMinutes) : null;
    $isMultiDay = $start && $end && !$start->isSameDay($end);
    $agency = optional(optional($transfer->post)->acente);
    $clients = optional($transfer->post)->client ?? collect();
    $driverName = optional($transfer->driver)->tittle ?: optional($transfer->driver)->name;
    $secondDriverName = optional($transfer->secondDriver)->tittle ?: optional($transfer->secondDriver)->name;
@endphp

<table class="topbar">
    <tr>
        <td style="width: 68%;">
            <div class="brand">Paris Via SARL</div>
            <div class="company-lines">
                3 Rue de la Butte, 93700 Drancy<br>
                SIRET: 750 473 902 00021 - TVA: FR17 750 473 902<br>
                Tél: +33 (0)6 46 43 01 48
            </div>
        </td>
        <td style="width: 32%; text-align: right;">
            @if(!empty($sirket->logo))
                <img src="{{ asset('images/'.$sirket->logo) }}" class="logo" alt="Paris Via">
            @endif
        </td>
    </tr>
</table>

<div class="doc-title">
    Feuille de route - Billet collectif <span class="badge">N° {{ $transfer->id }}</span>
</div>
<div class="doc-subtitle">
    Service occasionnel collectif de transport public routier de voyageurs - Billet collectif valant ordre de mission conformément à l'arrêté ministériel du 28/12/2011.
</div>

<table class="section">
    <tr><td class="section-title" colspan="4">Identification du service</td></tr>
    <tr class="grid">
        <td style="width: 25%;"><div class="label">Dossier</div><div class="value">#{{ $transfer->post_id }}</div></td>
        <td style="width: 25%;"><div class="label">Transfert</div><div class="value">#{{ $transfer->id }}</div></td>
        <td style="width: 25%;"><div class="label">Date</div><div class="value">{{ $isMultiDay ? $start->format('d/m/Y').' au '.$end->format('d/m/Y') : optional($start)->format('d/m/Y') }}</div></td>
        <td style="width: 25%;"><div class="label">Nature</div><div class="value">Transport occasionnel</div></td>
    </tr>
</table>

<table style="margin-top: 8px;">
    <tr>
        <td style="width: 49%; padding-right: 5px;">
            <table class="section">
                <tr><td class="section-title" colspan="2">Horaires de mission</td></tr>
                <tr class="grid">
                    <td><div class="label">Convocation dépôt</div><div class="value">{{ $ofisStart ? $ofisStart->format('d/m/Y H:i') : 'Non défini' }}</div></td>
                    <td><div class="label">Sur place client</div><div class="value">{{ $serviceStart ? $serviceStart->format('H:i') : '-' }}</div></td>
                </tr>
                <tr class="grid">
                    <td><div class="label">Départ client</div><div class="value">{{ $start ? $start->format('d/m/Y H:i') : '-' }}</div></td>
                    <td><div class="label">Fin prévue</div><div class="value">{{ $end ? $end->format('d/m/Y H:i') : '-' }}</div></td>
                </tr>
                <tr class="grid">
                    <td><div class="label">Fin réelle</div><div class="box">{{ !empty($transfer->missionr->finish) ? date('d/m/Y H:i', strtotime($transfer->missionr->finish)) : '&nbsp;' }}</div></td>
                    <td><div class="label">Temps supplémentaire</div><div class="box">&nbsp;</div></td>
                </tr>
            </table>
        </td>
        <td style="width: 51%; padding-left: 5px;">
            <table class="section">
                <tr><td class="section-title" colspan="2">Moyens affectés</td></tr>
                <tr class="grid">
                    <td style="width: 48%;"><div class="label">Véhicule</div><div class="value">{{ optional($transfer->vehicule)->name ?? 'Non défini' }}</div></td>
                    <td style="width: 52%;"><div class="label">Immatriculation</div><div class="value">{{ optional($transfer->vehicule)->plate ?? optional($transfer->vehicule)->immat ?? '-' }}</div></td>
                </tr>
                <tr class="grid">
                    <td colspan="2"><div class="label">Chauffeur principal</div><div class="value">{{ $driverName ?: 'Non défini' }}</div><div class="muted">Téléphone: {{ optional($transfer->driver)->tel ?? '-' }} @if(!empty($transfer->driver_app_confirmed_at)) - Confirmé le {{ date('d/m/Y H:i', strtotime($transfer->driver_app_confirmed_at)) }} @endif</div></td>
                </tr>
                @if(!empty($transfer->second_driver_id) && optional($transfer->secondDriver)->id)
                    <tr class="grid">
                        <td colspan="2"><div class="label">2e chauffeur - Double équipage</div><div class="value">{{ $secondDriverName ?: 'Non défini' }}</div><div class="muted">Téléphone: {{ optional($transfer->secondDriver)->tel ?? '-' }} @if(!empty($transfer->second_driver_app_confirmed_at)) - Confirmé le {{ date('d/m/Y H:i', strtotime($transfer->second_driver_app_confirmed_at)) }} @else - Confirmation en attente @endif</div></td>
                    </tr>
                @endif
            </table>
        </td>
    </tr>
</table>

<table class="section">
    <tr><td class="section-title" colspan="2">Itinéraire et service</td></tr>
    <tr>
        <td class="section-body" style="width: 62%; border-right: 1px solid #cbd5e1;">
            <table class="route-table">
                <tr>
                    <td class="route-step"><span class="step-pill">D</span></td>
                    <td><strong>{{ $serviceStart ? $serviceStart->format('H:i') : '-' }}</strong> - Sur place client: {{ $transfer->from ?: '-' }}</td>
                </tr>
                @foreach ($transfer->trajets as $index => $trajet)
                    @php
                        $trajetTime = $trajet->datetime ? \Carbon\Carbon::parse($trajet->datetime) : null;
                        $displayTrajetTime = $trajetTime && $index === 0 ? $trajetTime->copy()->subMinutes((int) $surplaceBeforeMinutes) : $trajetTime;
                    @endphp
                    <tr>
                        <td class="route-step"><span class="step-pill">{{ $index + 1 }}</span></td>
                        <td>
                            <strong>{{ $displayTrajetTime ? $displayTrajetTime->format($start && $displayTrajetTime->isSameDay($start) ? 'H:i' : 'd/m H:i') : '-' }}</strong>
                            - {{ $index === 0 ? 'Sur place client' : $trajet->type }}: {{ $trajet->from }} {{ $trajet->google_address }}
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td class="route-step"><span class="step-pill">A</span></td>
                    <td><strong>{{ $end ? $end->format('H:i') : '-' }}</strong> - Destination: {{ $transfer->target ?: '-' }}</td>
                </tr>
            </table>
        </td>
        <td class="section-body" style="width: 38%;">
            <div class="label">Passagers / contacts</div>
            @forelse($clients as $client)
                <div>{{ trim(($client->name ?? '').' '.($client->surname ?? '')) }} - {{ $client->tel ?? '-' }}</div>
            @empty
                <div class="muted">Aucun contact client renseigné</div>
            @endforelse
            <div style="margin-top: 8px;" class="label">Instructions</div>
            <div>{{ strlen($comments) > 180 ? substr($comments, 0, 180).'...' : ($comments ?: '-') }}</div>
            @if(strlen($comments) > 180)
                <div class="muted">Voir détail en annexe.</div>
            @endif
        </td>
    </tr>
</table>

<table style="margin-top: 8px;">
    <tr>
        <td style="width: 50%; padding-right: 5px;">
            <table class="section">
                <tr><td class="section-title" colspan="3">Kilométrage à renseigner</td></tr>
                <tr class="data-table">
                    <th></th><th>Garage</th><th>Client</th>
                </tr>
                <tr class="data-table"><td>Départ</td><td>&nbsp;</td><td>&nbsp;</td></tr>
                <tr class="data-table"><td>Arrivée</td><td>&nbsp;</td><td>&nbsp;</td></tr>
                <tr class="data-table"><td>Sortie France</td><td>&nbsp;</td><td>&nbsp;</td></tr>
                <tr class="data-table"><td>Entrée France</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            </table>
        </td>
        <td style="width: 50%; padding-left: 5px;">
            <table class="section">
                <tr><td class="section-title" colspan="2">Frais de mission à renseigner</td></tr>
                <tr class="data-table"><th>Nature</th><th>Montant / observation</th></tr>
                <tr class="data-table"><td>Parking</td><td>&nbsp;</td></tr>
                <tr class="data-table"><td>Péage</td><td>&nbsp;</td></tr>
                <tr class="data-table"><td>Repas</td><td>&nbsp;</td></tr>
                <tr class="data-table"><td>Autres frais</td><td>&nbsp;</td></tr>
            </table>
        </td>
    </tr>
</table>

<table class="section">
    <tr><td class="section-title" colspan="2">Donneur d'ordre</td></tr>
    <tr class="grid">
        <td style="width: 55%;"><div class="label">Personne, établissement, association ou groupement pour le compte duquel le transport est exécuté</div><div class="value">{{ $agency->tittle ?? $agency->name ?? 'Non défini' }}</div><div>{{ trim(($agency->address ?? '').' '.($agency->city ?? '')) }}</div></td>
        <td style="width: 20%;"><div class="label">Téléphone</div><div class="value">{{ $agency->tel ?? '-' }}</div></td>
        <td style="width: 25%;"><div class="label">Passagers prévus / réels</div><div class="value">{{ $transfer->pax ?: '-' }} / ______</div></td>
    </tr>
</table>

<table class="section">
    <tr><td class="section-title" colspan="4">Clôture de mission et signatures</td></tr>
    <tr class="grid">
        <td style="width: 22%;"><div class="label">Heure fin de service</div><div class="box">&nbsp;</div></td>
        <td style="width: 22%;"><div class="label">Heure retour garage</div><div class="box">&nbsp;</div></td>
        <td style="width: 28%;"><div class="label">Signature chauffeur principal</div><div class="signature-box"></div></td>
        <td style="width: 28%;"><div class="label">Signature 2e chauffeur</div><div class="signature-box">@if(empty($transfer->second_driver_id) || !optional($transfer->secondDriver)->id)<span class="muted">Non applicable</span>@endif</div></td>
    </tr>
</table>

<div class="notice">
    Prix du transport selon facture (Dossier n° {{ $transfer->post_id }}). Les horaires mentionnés sont donnés à titre indicatif. En toute circonstance, le ou les conducteurs doivent respecter le Code de la route, la réglementation sociale européenne et française applicable au transport routier de voyageurs, ainsi que les temps de conduite, de pause et de repos. Document à conserver à bord pendant la mission et à remettre à l'exploitation après service.
</div>

@if (strlen($comments) > 180)
    <div class="page-break"></div>
    <table class="section">
        <tr><td class="section-title">Instructions détaillées</td></tr>
        <tr><td class="section-body">{!! nl2br(e($comments)) !!}</td></tr>
    </table>
@endif
</body>
</html>
