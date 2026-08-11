@extends('layouts.app')

@section('content')
@php
    $statusClass = match ($row->email_status) {
        'sent' => 'success',
        'failed' => 'danger',
        'not_tried' => 'secondary',
        default => 'light',
    };

    $statusLabel = match ($row->email_status) {
        'sent' => 'Envoyé',
        'failed' => 'Échec',
        'not_tried' => 'Non envoyé',
        default => $row->email_status ?: '-',
    };

    $retourLabel = filter_var($row->retour, FILTER_VALIDATE_BOOLEAN) ? 'Oui' : ($row->retour ? $row->retour : 'Non');
    $surPlaceLabel = filter_var($row->car_sur_place, FILTER_VALIDATE_BOOLEAN) ? 'Oui' : ($row->car_sur_place ? $row->car_sur_place : 'Non');
@endphp

<div class="container-fluid py-3 cansu-show">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">Demande Cansu #{{ $row->id }}</h1>
            <div class="text-muted">
                Reçue le {{ optional($row->created_at)->format('d/m/Y H:i') ?? '-' }}
                @if($row->lang)
                    <span class="ms-2 badge bg-light text-dark border">{{ strtoupper($row->lang) }}</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('cansu.index') }}" class="btn btn-outline-secondary">Retour à la liste</a>
            @if($row->client_email)
                <a href="mailto:{{ $row->client_email }}" class="btn btn-primary">Envoyer un e-mail</a>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="border rounded bg-white p-3 h-100">
                <div class="text-muted small">Statut e-mail</div>
                <div class="mt-1"><span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded bg-white p-3 h-100">
                <div class="text-muted small">Passagers</div>
                <div class="fs-5 fw-semibold">{{ $row->cansu_passengers ?? '-' }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded bg-white p-3 h-100">
                <div class="text-muted small">Heure de service</div>
                <div class="fs-5 fw-semibold">{{ optional($row->cansu_time)->format('H:i') ?? ($row->cansu_time ?: '-') }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded bg-white p-3 h-100">
                <div class="text-muted small">Retour</div>
                <div class="fs-5 fw-semibold">{{ $retourLabel }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold">Trajet</div>
                <div class="card-body">
                    <div class="route-block mb-3">
                        <div class="text-muted small">Lieu de départ</div>
                        <div class="fw-semibold">{{ $row->cansu_start ?: '-' }}</div>
                    </div>
                    <div class="route-line"></div>
                    <div class="route-block mt-3">
                        <div class="text-muted small">Lieu d'arrivée</div>
                        <div class="fw-semibold">{{ $row->cansu_end ?: '-' }}</div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <div class="text-muted small">Voiture sur place</div>
                            <div>{{ $surPlaceLabel }}</div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="text-muted small">Date/heure retour</div>
                            <div>{{ optional($row->retour_datetime)->format('d/m/Y H:i') ?? ($row->retour_datetime ?: '-') }}</div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="text-muted small">Langue</div>
                            <div>{{ $row->lang ? strtoupper($row->lang) : '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white fw-semibold">Notes client</div>
                <div class="card-body">
                    @if($row->client_notes)
                        <div class="notes-box">{!! nl2br(e($row->client_notes)) !!}</div>
                    @else
                        <span class="text-muted">Aucune note.</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold">Client</div>
                <div class="card-body">
                    <dl class="row mb-0 detail-list">
                        <dt class="col-sm-4">Nom</dt>
                        <dd class="col-sm-8">{{ $row->client_name ?: '-' }}</dd>

                        <dt class="col-sm-4">Téléphone</dt>
                        <dd class="col-sm-8">
                            @if($row->client_phone)
                                <a href="tel:{{ $row->client_phone }}">{{ $row->client_phone }}</a>
                            @else
                                -
                            @endif
                        </dd>

                        <dt class="col-sm-4">E-mail</dt>
                        <dd class="col-sm-8">
                            @if($row->client_email)
                                <a href="mailto:{{ $row->client_email }}">{{ $row->client_email }}</a>
                            @else
                                -
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white fw-semibold">Informations techniques</div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="text-muted small">ID</div>
                            <div class="fw-semibold">{{ $row->id }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Création</div>
                            <div class="fw-semibold">{{ optional($row->created_at)->format('d/m/Y H:i') ?? '-' }}</div>
                        </div>
                    </div>

                    <details>
                        <summary class="btn btn-sm btn-outline-secondary">Afficher les données brutes</summary>
                        <pre class="raw-data mt-3 mb-0">{{ json_encode($row->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .cansu-show .card,
    .cansu-show .border {
        border-color: #e5e7eb !important;
        border-radius: 8px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .cansu-show .card-header {
        border-bottom-color: #edf0f3;
    }

    .cansu-show .route-block {
        padding-left: 18px;
        border-left: 3px solid #2563eb;
    }

    .cansu-show .route-line {
        width: 3px;
        height: 28px;
        margin-left: 0;
        background: #bfdbfe;
    }

    .cansu-show .notes-box {
        padding: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #f8fafc;
        white-space: normal;
    }

    .cansu-show .detail-list dt {
        color: #6b7280;
        font-weight: 600;
    }

    .cansu-show .raw-data {
        max-height: 360px;
        overflow: auto;
        padding: 12px;
        border-radius: 8px;
        background: #111827;
        color: #f9fafb;
        font-size: 12px;
    }
</style>
@endsection
