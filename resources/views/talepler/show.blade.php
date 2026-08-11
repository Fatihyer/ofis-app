@extends('layouts.app')

@section('style')
<style>
.talep-operation-card{border:1px solid #dbe3ef;border-radius:10px;overflow:hidden;box-shadow:0 8px 18px rgba(15,23,42,.06)}
.talep-operation-card .card-header{background:#f8fafc;border-bottom:1px solid #dbe3ef}
.operation-section{border:1px solid #e5e7eb;border-radius:8px;padding:12px;background:#fff;margin-bottom:12px}
.operation-section-title{font-size:12px;font-weight:900;text-transform:uppercase;color:#475569;margin-bottom:10px;letter-spacing:.02em}
.operation-field{min-height:54px}.operation-field strong{display:block;color:#64748b;font-size:12px;margin-bottom:2px}.operation-field span{color:#0f172a;font-weight:800}.operation-note-box{border:1px solid #e5e7eb;border-radius:8px;background:#f8fafc;padding:10px;min-height:86px}.operation-note-box.admin{background:#fff7ed;border-color:#fed7aa}.operation-note-box strong{display:block;color:#64748b;font-size:12px;margin-bottom:5px}.operation-note-box .note-text{white-space:pre-wrap;color:#0f172a;font-weight:700}
.operation-route-metrics{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}.operation-metric{border:1px solid #bfdbfe;background:#eff6ff;color:#1e3a8a;border-radius:8px;padding:7px 10px;font-weight:900}.operation-metric strong{color:#1d4ed8;margin-right:4px}
.operation-price-grid{display:grid;grid-template-columns:repeat(3,minmax(160px,1fr));gap:10px}.operation-price-box{border:1px solid #e5e7eb;border-radius:8px;padding:10px;background:#f8fafc}.operation-price-box strong{display:block;color:#64748b;font-size:12px;margin-bottom:4px}.operation-price-box .price-value{font-size:16px;font-weight:900;color:#0f172a}.operation-price-box.team-price{border-color:#f59e0b;background:#fffbeb}.operation-price-box.team-price strong,.operation-price-box.team-price .price-value{color:#92400e}
.devis-total-grid{display:grid;grid-template-columns:repeat(4,minmax(170px,1fr));gap:12px;margin-top:18px;padding-top:18px;border-top:2px solid #dbe3ef}.devis-total-box{border:1px solid #cbd5e1;border-radius:10px;padding:14px;background:#f8fafc}.devis-total-box strong{display:block;color:#475569;font-size:13px;margin-bottom:5px}.devis-total-box .devis-total-value{font-size:21px;font-weight:900;color:#0f172a}.devis-total-box.team{border-color:#f59e0b;background:#fffbeb}.devis-total-box.team strong,.devis-total-box.team .devis-total-value{color:#92400e}.devis-total-box.system{border-color:#3b82f6;background:#eff6ff}.devis-total-box.system strong,.devis-total-box.system .devis-total-value{color:#1d4ed8}.devis-total-box.admin{border-color:#10b981;background:#ecfdf5}.devis-total-box.admin strong,.devis-total-box.admin .devis-total-value{color:#047857}.devis-total-box.ai{border-color:#8b5cf6;background:#f5f3ff}.devis-total-box.ai strong,.devis-total-box.ai .devis-total-value{color:#6d28d9}
.operation-title-line{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.same-day-badge{display:inline-flex;align-items:center;border:1px solid #14b8a6;background:#ccfbf1;color:#0f766e;border-radius:999px;padding:3px 9px;font-size:12px;font-weight:900}.same-day-summary{border:1px solid #99f6e4;background:#f0fdfa;border-radius:8px;padding:12px;margin-bottom:14px}.same-day-summary-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px}.same-day-summary-head strong{color:#0f766e;font-weight:900}.same-day-summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:10px}.same-day-summary-item{border:1px solid #ccfbf1;background:#fff;border-radius:8px;padding:10px}.same-day-summary-item strong{display:block;color:#0f172a;font-weight:900}.same-day-summary-item span{display:block;color:#64748b;font-size:12px;font-weight:800;margin-top:2px}.same-day-visual{display:flex;gap:12px;border:1px solid #99f6e4;background:#f0fdfa;border-radius:8px;padding:11px 12px;margin-bottom:12px}.same-day-rail{width:38px;display:flex;align-items:center;justify-content:center;position:relative;flex:0 0 38px}.same-day-rail:before{content:"";position:absolute;top:0;bottom:0;width:3px;background:#2dd4bf;border-radius:999px}.same-day-rail span{position:relative;z-index:1;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#0f766e;color:#fff;font-weight:900}.same-day-copy{min-width:0}.same-day-copy strong{display:block;color:#0f766e;font-weight:900}.same-day-meta,.same-day-numbers{display:flex;gap:8px;flex-wrap:wrap;margin-top:4px}.same-day-meta span,.same-day-numbers span{border:1px solid #ccfbf1;background:#fff;border-radius:999px;padding:3px 8px;color:#475569;font-size:12px;font-weight:800}.same-day-path{margin-top:6px;color:#0f172a;font-weight:850}.same-day-path span{color:#0f766e;margin:0 4px}
.history-card .card-header{background:#f8fafc}.history-title{display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-weight:900}.history-toggle{min-width:86px}.history-toggle-icon{display:inline-block;font-weight:900;margin-right:5px}.history-toggle[aria-expanded="true"] .history-toggle-icon{transform:rotate(90deg)}
.admin-comment-input{width:100%;min-height:160px;padding:12px;font-size:15px;line-height:1.5;resize:vertical}
@media(min-width:1200px){.talep-side-sticky{position:sticky;top:82px;max-height:calc(100vh - 96px);overflow-y:auto;padding-right:4px;scrollbar-width:thin}.talep-side-sticky .card:last-child{margin-bottom:0}.talep-side-sticky .mail-thread-body{max-height:430px}}
@media(max-width:900px){.operation-price-grid,.devis-total-grid{grid-template-columns:1fr}.same-day-visual{padding:10px}.same-day-rail{display:none}}
</style>
@endsection

@section('content')
@php
    $statusOptions = [
        'En attente',
        'Informations manquantes',
        'Prix admin attendu',
        'Prix communiqué',
        'Relance à faire',
        'Confirmé',
        'Annulé',
        'Perdu',
    ];

    $firstDay = $talep->days->first();
    $missingItems = collect([
        'Agence' => empty($talep->acente_id),
        'Responsable' => empty($talep->user_id),
        'Client' => empty($talep->customer_name),
        'Téléphone / email client' => empty($talep->customer_phone) && empty($talep->customer_email),
        'Pays' => empty($talep->country),
        'Nombre de passagers' => empty($talep->total_pax),
        'Type de service' => empty($talep->service_type_id) && empty($talep->service_type),
        'Véhicule demandé' => empty($talep->vehicule_id) && empty($talep->vehicle_type),
        'Date opération' => !$firstDay || empty($firstDay->service_date),
        'Heure opération' => !$firstDay || empty($firstDay->start_time),
        'Début' => empty($talep->pickup_location) && (!$firstDay || empty($firstDay->pickup_location)),
        'Fin' => empty($talep->dropoff_location) && (!$firstDay || empty($firstDay->dropoff_location)),
        'Prix admin' => $talep->final_total === null || (float) $talep->final_total <= 0,
    ])->filter()->keys();

    $convertedPost = optional($talep->convertedTransfer)->post;
@endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Détail de la demande #{{ $talep->id }}</h2>
            <div class="text-muted">
                {{ $talep->request_no ?? 'Sans numéro' }}
                @if($talep->created_at)
                    · Créée le {{ $talep->created_at->format('d/m/Y H:i') }}
                @endif
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            @if($previousTalep)
                <a href="{{ route('talepler.show', $previousTalep->id) }}" class="btn btn-outline-dark">
                    <i class="fa fa-chevron-left"></i> Précédente
                </a>
            @else
                <button class="btn btn-outline-secondary" disabled><i class="fa fa-chevron-left"></i> Précédente</button>
            @endif
            @if($nextTalep)
                <a href="{{ route('talepler.show', $nextTalep->id) }}" class="btn btn-outline-dark">
                    Suivante <i class="fa fa-chevron-right"></i>
                </a>
            @else
                <button class="btn btn-outline-secondary" disabled>Suivante <i class="fa fa-chevron-right"></i></button>
            @endif
            <a href="{{ route('talepler.edit', $talep->id) }}" class="btn btn-primary">Modifier</a>
            <a href="{{ route('talepler.index') }}" class="btn btn-secondary">Retour à la liste</a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-7 mb-4">
            <div class="card mb-4">
                <div class="card-header">Informations générales</div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr><th style="width: 220px;">Date de la demande</th><td>{{ optional($talep->talep_tarihi)->format('d/m/Y H:i') ?? '-' }}</td></tr>
                            <tr><th>Canal</th><td>{{ $talep->talep_kanali ?? '-' }}</td></tr>
                            <tr><th>Pays</th><td>{{ $talep->country ?? '-' }}</td></tr>
                            <tr><th>Agence</th><td>{{ $talep->acente->name ?? '-' }}</td></tr>
                            <tr><th>Responsable</th><td>{{ $talep->user->name ?? '-' }}</td></tr>
                            <tr><th>Client</th><td>{{ $talep->customer_name ?? '-' }}</td></tr>
                            <tr><th>Téléphone</th><td>{{ $talep->customer_phone ?? '-' }}</td></tr>
                            <tr><th>Email</th><td>{{ $talep->customer_email ?? '-' }}</td></tr>
                            <tr><th>Passagers</th><td>{{ $talep->total_pax ?? '-' }}</td></tr>
                            <tr><th>Type de service</th><td>{{ $talep->serviceType->name ?? $talep->service_type ?? '-' }}</td></tr>
                            <tr><th>Véhicule</th><td>{{ $talep->vehicule->name ?? $talep->vehicle_type ?? '-' }}</td></tr>
                            <tr><th>Statut</th><td>{{ $talep->konfirme_durumu ?? '-' }}</td></tr>
                            @if($talep->convertedTransfer && $talep->convertedTransfer->post)
                                <tr>
                                    <th>Dossier créé</th>
                                    <td>
                                        <a href="{{ route('posts.show', $talep->convertedTransfer->post->id) }}" class="btn btn-sm btn-success">
                                            Ouvrir le dossier #{{ $talep->convertedTransfer->post->id }}
                                        </a>
                                        <a href="{{ route('transfers.show', $talep->convertedTransfer->id) }}" class="btn btn-sm btn-outline-success">
                                            Transfert #{{ $talep->convertedTransfer->id }}
                                        </a>
                                    </td>
                                </tr>
                            @endif
                            <tr><th>Relance</th><td>{{ $talep->relance_yapildi ? 'Oui' : 'Non' }}</td></tr>
                            <tr><th>Devise</th><td>{{ $talep->currency ?? 'EUR' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>Statut et suivi</span>
                    <small id="talepStatusMessage" class="text-muted"></small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Statut de la demande</label>
                            <select class="form-control" id="talepStatusSelect" data-id="{{ $talep->id }}">
                                @foreach($statusOptions as $statusOption)
                                    <option value="{{ $statusOption }}" {{ ($talep->konfirme_durumu ?? '') === $statusOption ? 'selected' : '' }}>
                                        {{ $statusOption }}
                                    </option>
                                @endforeach
                                @if($talep->konfirme_durumu && !in_array($talep->konfirme_durumu, $statusOptions, true))
                                    <option value="{{ $talep->konfirme_durumu }}" selected>{{ $talep->konfirme_durumu }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Statut dossier</label>
                            @if($convertedPost)
                                <div class="border rounded bg-light p-2">
                                    <a href="{{ route('posts.show', $convertedPost->id) }}" class="fw-bold">Dossier #{{ $convertedPost->id }}</a>
                                    <span class="text-muted">· {{ optional($convertedPost->status)->name ?? 'Statut non défini' }}</span>
                                </div>
                            @else
                                <div class="border rounded bg-light p-2 text-muted">Aucun dossier créé pour le moment.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>Informations manquantes</span>
                    <span class="badge {{ $missingItems->count() ? 'bg-warning text-dark' : 'bg-success' }}">
                        {{ $missingItems->count() ? $missingItems->count() . ' point(s) à compléter' : 'Complet' }}
                    </span>
                </div>
                <div class="card-body">
                    @if($missingItems->count())
                        <div class="mb-3">
                            @foreach($missingItems as $missingItem)
                                <span class="badge bg-light text-dark border me-1 mb-1">{{ $missingItem }}</span>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-success mb-3">Les informations principales sont complètes.</div>
                    @endif

                    <label for="missingInfoInput" class="form-label fw-bold">Note à compléter</label>
                    <textarea id="missingInfoInput" class="form-control" rows="3" placeholder="Ex: numéro de vol manquant, horaire à confirmer, adresse à vérifier...">{{ $talep->internal_notes ?? '' }}</textarea>
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <button type="button" class="btn btn-sm btn-primary" id="saveMissingInfo" data-id="{{ $talep->id }}">
                            Enregistrer
                        </button>
                        <small id="missingInfoMessage" class="text-muted"></small>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>Prix</span>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if(auth()->user()?->hasAnyRole(['Superadmin', 'Admin']))
                            <button
                                type="button"
                                class="btn btn-sm btn-success"
                                id="aiPriceSuggestionButton"
                                data-url="{{ route('talepler.aiPriceSuggestion', $talep->id) }}"
                            >
                                <i class="fa fa-magic"></i> Suggestion de prix par l’IA Paris Via
                            </button>
                        @endif
                        <form method="POST" action="{{ route('talepler.recalculatePrice', $talep->id) }}" class="mb-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">Recalculer le prix</button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="aiPriceSuggestionResult" class="alert alert-info m-3 d-none" role="status"></div>
                    <table class="table table-bordered mb-0">
                        <tbody>
                            @php
                                $canEditPrixAdmin = auth()->user()?->hasRole('Superadmin');
                                $quoteLinesForPrice = collect(data_get(optional($talep->quote)->calculation_json, 'lines', []));
                                $quoteAiTotal = optional($talep->quote)->system_total ?? $talep->system_total;
                                $quoteAiSubtotal = optional($talep->quote)->subtotal;
                                $quoteAiExtras = optional($talep->quote)->extras_total;
                                $quoteAiMargin = optional($talep->quote)->margin_amount;
                                $quoteAiVat = optional($talep->quote)->vat_amount;
                                $quoteAiDriverFees = (float) $quoteLinesForPrice->sum('driver_fees_total');
                                $quoteAiFuel = (float) $quoteLinesForPrice->sum('fuel_amount');
                                $quoteAiTolls = (float) $quoteLinesForPrice->sum('toll_amount');
                                $currency = $talep->currency ?? 'EUR';
                                $formatPrice = fn ($value) => $value !== null ? number_format((float) $value, 2, ',', ' ') . ' ' . $currency : '-';
                                $prixRetenu = $talep->second_discount_price
                                    ?? $talep->discount_price
                                    ?? $talep->final_total
                                    ?? $talep->system_total
                                    ?? $quoteAiTotal;
                                $discountReference = $talep->final_total ?? $talep->system_total ?? $quoteAiTotal;
                                $discountPercent = null;
                                $discountAmount = null;
                                if ($discountReference && (float) $discountReference > 0 && $talep->discount_price !== null) {
                                    $discountAmount = max(0, (float) $discountReference - (float) $talep->discount_price);
                                    $discountPercent = max(0, round(($discountAmount / (float) $discountReference) * 100, 1));
                                }
                            @endphp
                            <tr>
                                <th style="width: 220px;">Prix Laravel AI</th>
                                <td>
                                    <strong>{{ $formatPrice($quoteAiTotal) }}</strong>
                                    <div class="small text-muted">
                                        Calcul automatique: base, km, heures, nuit, péages, carburant, frais chauffeur, majorations/remises, marge et TVA.
                                    </div>
                                    @if($talep->quote)
                                        <div class="row g-2 mt-2">
                                            <div class="col-md-4"><div class="border rounded bg-light p-2 small">Sous-total: {{ number_format((float) $quoteAiSubtotal, 2, ',', ' ') }} {{ $talep->currency ?? 'EUR' }}</div></div>
                                            <div class="col-md-4"><div class="border rounded bg-light p-2 small">Extras: {{ number_format((float) $quoteAiExtras, 2, ',', ' ') }} {{ $talep->currency ?? 'EUR' }}</div></div>
                                            <div class="col-md-4"><div class="border rounded bg-light p-2 small">Marge: {{ number_format((float) $quoteAiMargin, 2, ',', ' ') }} {{ $talep->currency ?? 'EUR' }}</div></div>
                                            <div class="col-md-4"><div class="border rounded bg-light p-2 small">TVA: {{ number_format((float) $quoteAiVat, 2, ',', ' ') }} {{ $talep->currency ?? 'EUR' }}</div></div>
                                            <div class="col-md-4"><div class="border rounded bg-light p-2 small">Carburant: {{ number_format($quoteAiFuel, 2, ',', ' ') }} {{ $talep->currency ?? 'EUR' }}</div></div>
                                            <div class="col-md-4"><div class="border rounded bg-light p-2 small">Chauffeur: {{ number_format($quoteAiDriverFees, 2, ',', ' ') }} {{ $talep->currency ?? 'EUR' }}</div></div>
                                            <div class="col-md-4"><div class="border rounded bg-light p-2 small">Péages: {{ number_format($quoteAiTolls, 2, ',', ' ') }} {{ $talep->currency ?? 'EUR' }}</div></div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            <tr><th>Prix Equipe</th><td>{{ $formatPrice($talep->system_total) }}</td></tr>
                            <tr>
                                <th>Prix admin</th>
                                <td>
                                    @if($canEditPrixAdmin)
                                        <div class="input-group input-group-sm">
                                            <input
                                                type="number"
                                                step="0.01"
                                                id="show_prix_admin"
                                                class="form-control"
                                                value="{{ $talep->final_total ?? '' }}"
                                            >
                                        </div>
                                    @else
                                        <strong>{{ $formatPrice($talep->final_total) }}</strong>
                                    @endif
                                    <div class="small text-muted mt-1">Prix principal validé par l’administration.</div>
                                    <small id="showPrixAdminMessage" class="text-muted"></small>
                                    <div class="small text-muted mt-1" id="showPrixAdminMeta">
                                        Donné par: {{ $talep->adminPriceUser->name ?? '-' }}
                                        @if($talep->admin_price_updated_at)
                                            · {{ $talep->admin_price_updated_at->format('d/m/Y H:i') }}
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Prix remise</th>
                                <td>
                                    @if($canEditPrixAdmin)
                                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:nowrap;max-width:100%;">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                id="show_discount_price"
                                                class="form-control form-control-sm"
                                                style="width:100px;min-width:0;flex:0 0 100px;"
                                                value="{{ $talep->discount_price ?? '' }}"
                                            >
                                            <span class="text-muted small" style="white-space:nowrap;flex:0 0 auto;">Valable</span>
                                            <input
                                                type="date"
                                                id="show_discount_valid_until"
                                                class="form-control form-control-sm"
                                                style="width:128px;min-width:0;flex:0 0 128px;"
                                                value="{{ optional($talep->discount_valid_until)->format('Y-m-d') }}"
                                            >
                                            <span id="discountPercentDisplay" class="badge" style="white-space:nowrap;flex:0 0 auto;background:#e0f2fe;color:#075985;border:1px solid #7dd3fc;">{{ $discountPercent !== null ? number_format($discountPercent, 1, ',', ' ') . '% de remise · ' . number_format($discountAmount, 2, ',', ' ') . ' ' . $currency : '' }}</span>
                                        </div>
                                    @else
                                        {{ $formatPrice($talep->discount_price) }}
                                        @if($talep->discount_valid_until)
                                            <br><span class="text-muted small">Valable {{ $talep->discount_valid_until->format('d/m/Y') }}</span>
                                        @endif
                                        @if($discountPercent !== null)
                                            <br><span class="badge" style="background:#e0f2fe;color:#075985;border:1px solid #7dd3fc;">{{ number_format($discountPercent, 1, ',', ' ') }}% de remise · {{ number_format($discountAmount, 2, ',', ' ') }} {{ $currency }}</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Commentaire admin</th>
                                <td>
                                    @if($canEditPrixAdmin)
                                        <textarea
                                            id="show_comment_admin"
                                            rows="6"
                                            class="form-control admin-comment-input"
                                            style="width:100%;min-height:160px;padding:12px;font-size:15px;line-height:1.5;resize:vertical"
                                        >{{ $talep->comment_admin ?? '' }}</textarea>
                                    @else
                                        @if($talep->comment_admin)
                                            <div class="border rounded bg-light p-2">{!! nl2br(e($talep->comment_admin)) !!}</div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>2ème remise</th>
                                <td>
                                    @if($canEditPrixAdmin)
                                        <div class="input-group input-group-sm">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                id="show_second_discount_price"
                                                class="form-control"
                                                value="{{ $talep->second_discount_price ?? '' }}"
                                            >
                                            <div class="input-group-append"><span class="input-group-text">{{ $currency }}</span></div>
                                        </div>
                                    @else
                                        {{ $formatPrice($talep->second_discount_price) }}
                                    @endif
                                </td>
                            </tr>
                            <tr class="table-success">
                                <th>Prix retenu</th>
                                <td><strong id="prixRetenuDisplay">{{ $formatPrice($prixRetenu) }}</strong></td>
                            </tr>
                            @if($canEditPrixAdmin)
                                <tr>
                                    <th></th>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm" id="saveShowPrixAdmin" data-id="{{ $talep->id }}">Enregistrer</button>
                                    </td>
                                </tr>
                            @endif
                            <tr><th>Prix communiqué</th><td>{{ $formatPrice($talep->verilen_fiyat) }}</td></tr>
                            <tr><th>Prix confirmé</th><td>{{ $formatPrice($talep->confirmed_price) }}</td></tr>
                            @if($talep->is_manual_override || $talep->override_note)
                                <tr><th>Remplacement manuel</th><td>{{ $talep->is_manual_override ? 'Oui' : 'Non' }}</td></tr>
                                @if($talep->override_note)
                                    <tr><th>Note de remplacement</th><td>{{ $talep->override_note }}</td></tr>
                                @endif
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Message et notes</div>
                <div class="card-body">
                    <h6>Message</h6>
                    <div class="border rounded p-3 mb-3 bg-light">{!! $talep->uzun_mesaj ? nl2br(e($talep->uzun_mesaj)) : '-' !!}</div>
                    <h6>Notes internes</h6>
                    <div class="border rounded p-3 bg-light">{!! $talep->internal_notes ? nl2br(e($talep->internal_notes)) : '-' !!}</div>
                </div>
            </div>

            <div class="card mb-4" id="operationAdminPricesCard">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>Opérations</span>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-light text-dark border">
                            Total prix admin:
                            <strong id="operationAdminTotal">
                                {{ $talep->days->sum('admin_price') > 0 ? number_format((float) $talep->days->sum('admin_price'), 2, ',', ' ') . ' ' . ($talep->currency ?? 'EUR') : '-' }}
                            </strong>
                        </span>
                        <span class="badge bg-secondary">{{ $talep->days->count() }}</span>
                        @if($canEditPrixAdmin && $talep->days->count())
                            <button type="button" class="btn btn-sm btn-primary" id="saveOperationAdminPrices" data-id="{{ $talep->id }}">Enregistrer les prix</button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($canEditPrixAdmin && $talep->days->count())
                        <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span>Renseignez le prix admin de chaque opération. Le total est calculé automatiquement.</span>
                            <small id="operationAdminPriceMessage" class="text-muted"></small>
                        </div>
                    @endif
                    @php
                        $quoteLinesByDay = collect(data_get(optional($talep->quote)->calculation_json, 'lines', []))->keyBy('talep_day_id');
                        $operationGroupKeyForDay = function ($item) use ($talep) {
                            $dateKey = $item->service_date ? $item->service_date->format('Y-m-d') : 'no-date-' . $item->id;
                            $vehicleKey = strtolower(trim((string) ($item->vehicle_type ?: $talep->vehicle_type ?: 'sans-vehicule')));

                            return $dateKey . '|' . $vehicleKey;
                        };
                        $operationGroups = $talep->days->groupBy($operationGroupKeyForDay)->map(function ($items) {
                            $ordered = $items->sortBy(function ($item) {
                                $date = $item->service_date ? $item->service_date->format('Y-m-d') : '';
                                $time = $item->start_time ?: '99:99:99';

                                return $date . ' ' . $time . ' ' . str_pad((string) ($item->day_number ?? 0), 3, '0', STR_PAD_LEFT);
                            })->values();
                            $first = $ordered->first();
                            $last = $ordered->last();

                            return [
                                'count' => $ordered->count(),
                                'ordered' => $ordered,
                                'date' => optional($first->service_date)->format('d/m/Y') ?: '-',
                                'vehicle' => $first->vehicle_type ?: '-',
                                'start' => $first->pickup_location ?: '-',
                                'end' => $last->dropoff_location ?: '-',
                                'distance_km' => round(((float) $ordered->sum('distance_meters')) / 1000, 1),
                                'fuel_amount' => (float) $ordered->sum('fuel_amount'),
                                'admin_price' => (float) $ordered->sum('admin_price'),
                            ];
                        });
                        $operationGroupPositions = [];
                        foreach ($operationGroups as $groupData) {
                            foreach ($groupData['ordered'] as $position => $groupDay) {
                                $operationGroupPositions[$groupDay->id] = $position + 1;
                            }
                        }
                        $sameDayOperationGroups = $operationGroups->filter(fn ($group) => $group['count'] > 1);
                    @endphp
                    @if($sameDayOperationGroups->count())
                        <div class="same-day-summary">
                            <div class="same-day-summary-head">
                                <strong>Groupes même jour</strong>
                                <span class="badge bg-info text-dark">{{ $sameDayOperationGroups->count() }}</span>
                            </div>
                            <div class="same-day-summary-grid">
                                @foreach($sameDayOperationGroups as $sameDayGroup)
                                    <div class="same-day-summary-item">
                                        <strong>{{ $sameDayGroup['date'] }} · {{ $sameDayGroup['vehicle'] }}</strong>
                                        <span>{{ $sameDayGroup['count'] }} opérations</span>
                                        <span>{{ $sameDayGroup['start'] }} → {{ $sameDayGroup['end'] }}</span>
                                        @if($sameDayGroup['distance_km'] > 0)
                                            <span>{{ number_format($sameDayGroup['distance_km'], 1, ',', ' ') }} km cumulés</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @forelse($talep->days as $index => $day)
                        @php
                            $quoteLine = $quoteLinesByDay->get($day->id, []);
                            $waypoints = $day->via_points_json ?? [];
                            if (is_string($waypoints)) {
                                $decodedWaypoints = json_decode($waypoints, true);
                                $waypoints = is_array($decodedWaypoints) ? $decodedWaypoints : [];
                            }
                            $waypoints = array_values(array_filter((array) $waypoints));
                            $operationGroupKey = $operationGroupKeyForDay($day);
                            $operationGroup = $operationGroups->get($operationGroupKey);
                            $operationGroupCount = (int) data_get($operationGroup, 'count', 1);
                            $operationGroupPosition = $operationGroupPositions[$day->id] ?? 1;
                            $operationIsGrouped = $operationGroupCount > 1;
                        @endphp

                        <div class="card mb-3 operation-row talep-operation-card" data-index="{{ $index }}">
                            <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                <div class="operation-title-line d-flex align-items-center flex-wrap gap-2">
                                    <strong>Opération #{{ $day->day_number ?? $index + 1 }}</strong>
                                    @if(!empty($day->service_type))
                                        <span class="badge bg-info text-dark">{{ $day->service_type }}</span>
                                    @endif
                                    @if($operationIsGrouped)
                                        <span class="same-day-badge">Même jour {{ $operationGroupPosition }}/{{ $operationGroupCount }}</span>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary calculateOperationRoute">Calculer la distance</button>
                            </div>
                            <div class="card-body">
                                <input type="hidden" name="operations[{{ $index }}][distance_meters]" value="{{ $day->distance_meters ?? 0 }}">
                                <input type="hidden" name="operations[{{ $index }}][duration_seconds]" value="{{ $day->duration_seconds ?? 0 }}">
                                <input type="hidden" name="operations[{{ $index }}][traffic_duration_seconds]" value="{{ $day->traffic_duration_seconds ?? 0 }}">
                                <input type="hidden" class="operation-pickup" value="{{ $day->pickup_location ?? '' }}">
                                <input type="hidden" class="operation-dropoff" value="{{ $day->dropoff_location ?? '' }}">
                                <input type="hidden" class="operation-toll" value="{{ $day->toll_amount ?? 0 }}">
                                <input type="hidden" class="operation-fuel-amount" value="{{ $day->fuel_amount ?? 0 }}">
                                <input type="hidden" class="operation-fuel-liters" value="{{ $day->fuel_liters ?? 0 }}">
                                @foreach($waypoints as $waypoint)
                                    <input type="hidden" class="operation-waypoint" value="{{ $waypoint }}">
                                @endforeach

                                @if($operationIsGrouped)
                                    <div class="same-day-visual">
                                        <div class="same-day-rail"><span>{{ $operationGroupPosition }}</span></div>
                                        <div class="same-day-copy">
                                            <strong>Même jour / même véhicule</strong>
                                            <div class="same-day-meta">
                                                <span>{{ $operationGroup['date'] }}</span>
                                                <span>{{ $operationGroup['vehicle'] }}</span>
                                                <span>{{ $operationGroup['count'] }} opérations</span>
                                            </div>
                                            <div class="same-day-path">{{ $operationGroup['start'] }} <span>→</span> {{ $operationGroup['end'] }}</div>
                                            <div class="same-day-numbers">
                                                @if($operationGroup['distance_km'] > 0)
                                                    <span>{{ number_format($operationGroup['distance_km'], 1, ',', ' ') }} km cumulés</span>
                                                @endif
                                                @if($operationGroup['fuel_amount'] > 0)
                                                    <span>Carburant {{ number_format($operationGroup['fuel_amount'], 2, ',', ' ') }} EUR</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="operation-section">
                                    <div class="operation-section-title">Planning et itinéraire</div>
                                    <div class="row">
                                        <div class="col-md-3 mb-2 operation-field"><strong>Date</strong><span>{{ optional($day->service_date)->format('d/m/Y') ?? '-' }}</span></div>
                                        <div class="col-md-3 mb-2 operation-field"><strong>Début</strong><span>{{ $day->start_time ? substr($day->start_time, 0, 5) : '-' }}</span></div>
                                        <div class="col-md-3 mb-2 operation-field"><strong>Fin</strong><span>{{ $day->end_time ? substr($day->end_time, 0, 5) : '-' }}</span></div>
                                        <div class="col-md-3 mb-2 operation-field"><strong>Pax</strong><span>{{ $day->pax ?? '-' }}</span></div>
                                        <div class="col-md-6 mb-2 operation-field"><strong>Début adresse</strong><span>{{ $day->pickup_location ?? '-' }}</span></div>
                                        <div class="col-md-6 mb-2 operation-field"><strong>Fin adresse</strong><span>{{ $day->dropoff_location ?? '-' }}</span></div>
                                        <div class="col-12 operation-route-metrics">
                                            <span class="operation-metric"><strong>Distance:</strong> <span class="operation-distance">{{ !empty($day->distance_meters) ? round($day->distance_meters / 1000, 1).' km' : '-' }}</span></span>
                                            <span class="operation-metric"><strong>Durée:</strong> <span class="operation-duration">{{ !empty($day->duration_seconds) ? floor($day->duration_seconds / 60).' min' : '-' }}</span></span>
                                            <span class="operation-metric"><strong>Durée trafic:</strong> {{ !empty($day->traffic_duration_seconds) ? floor($day->traffic_duration_seconds / 60).' min' : '-' }}</span>
                                        </div>
                                        <div class="col-md-12 mt-2 operation-field"><strong>Étapes intermédiaires</strong><span>{{ count($waypoints) ? implode(' → ', $waypoints) : '-' }}</span></div>
                                    </div>
                                </div>

                                <div class="operation-section">
                                    <div class="operation-section-title">Service</div>
                                    <div class="row">
                                        <div class="col-md-4 mb-2 operation-field"><strong>Type de service</strong><span>{{ $day->service_type ?? '-' }}</span></div>
                                        <div class="col-md-4 mb-2 operation-field"><strong>Véhicule</strong><span>{{ $day->vehicle_type ?? '-' }}</span></div>
                                        <div class="col-md-4 mb-2 operation-field"><strong>Description</strong><span>{{ $day->route_description ?? '-' }}</span></div>
                                        <div class="col-md-4 mb-2">
                                            <div class="operation-note-box">
                                                <strong>Notes générales</strong>
                                                <div class="note-text">{!! $day->notes ? nl2br(e($day->notes)) : '-' !!}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <div class="operation-note-box">
                                                <strong>Note équipe</strong>
                                                <div class="note-text">{!! $day->note_equipe ? nl2br(e($day->note_equipe)) : '-' !!}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <div class="operation-note-box admin">
                                                <strong>Note admin</strong>
                                                @if($canEditPrixAdmin)
                                                    <textarea class="form-control form-control-sm operation-admin-note" rows="3" data-url="{{ route('talepler.days.adminNote', [$talep->id, $day->id]) }}">{{ $day->note_admin ?? '' }}</textarea>
                                                    <div class="d-flex align-items-center gap-2 mt-2">
                                                        <button type="button" class="btn btn-sm btn-warning saveOperationAdminNote">Enregistrer</button>
                                                        <small class="text-muted operation-admin-note-message"></small>
                                                    </div>
                                                @else
                                                    <div class="note-text">{!! $day->note_admin ? nl2br(e($day->note_admin)) : '-' !!}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="operation-section mb-0">
                                    <div class="operation-section-title">Prix et frais</div>
                                    <div class="operation-price-grid">
                                        <div class="operation-price-box">
                                            <strong>Prix AI</strong>
                                            <div class="price-value">{{ $day->system_price ? number_format((float) $day->system_price, 2, ',', ' ') . ' ' . ($talep->currency ?? 'EUR') : '-' }}</div>
                                        </div>
                                        <div class="operation-price-box team-price">
                                            <strong>Prix équipe</strong>
                                            <div class="price-value">{{ $day->final_price ? number_format((float) $day->final_price, 2, ',', ' ') . ' ' . ($talep->currency ?? 'EUR') : '-' }}</div>
                                        </div>
                                        <div class="operation-price-box">
                                            <strong>Prix admin opération</strong>
                                            @if($canEditPrixAdmin)
                                                <div class="input-group input-group-sm">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        class="form-control operation-admin-price"
                                                        data-day-id="{{ $day->id }}"
                                                        value="{{ $day->admin_price !== null ? (float) $day->admin_price : '' }}"
                                                        placeholder="0.00"
                                                    >
                                                    <span class="input-group-text">{{ $talep->currency ?? 'EUR' }}</span>
                                                </div>
                                                <small class="text-muted">Réservé superadmin</small>
                                            @else
                                                <div class="price-value">{{ $day->admin_price ? number_format((float) $day->admin_price, 2, ',', ' ') . ' ' . ($talep->currency ?? 'EUR') : '-' }}</div>
                                            @endif
                                        </div>
                                        <div class="operation-price-box"><strong>Péages</strong><div class="price-value">{{ $day->toll_amount !== null ? number_format((float) $day->toll_amount, 2, ',', ' ') . ' ' . ($day->toll_currency ?? 'EUR') : '-' }}</div></div>
                                        <div class="operation-price-box"><strong>Carburant estimé</strong><div class="price-value">{{ $day->fuel_amount ? number_format((float) $day->fuel_amount, 2, ',', ' ') . ' EUR' : '-' }}</div></div>
                                        <div class="operation-price-box"><strong>Frais chauffeur</strong><div class="price-value">{{ data_get($quoteLine, 'driver_fees_total') ? number_format((float) data_get($quoteLine, 'driver_fees_total'), 2, ',', ' ') . ' EUR' : '-' }}</div></div>
                                        <div class="operation-price-box"><strong>Découcher</strong><div class="price-value">{{ $day->decoucher !== null ? number_format((float) $day->decoucher, 2, ',', ' ') . ' ' . ($talep->currency ?? 'EUR') : '-' }}</div></div>
                                        <div class="operation-price-box"><strong>Parking</strong><div class="price-value">{{ $day->parking !== null ? number_format((float) $day->parking, 2, ',', ' ') . ' ' . ($talep->currency ?? 'EUR') : '-' }}</div></div>
                                        <div class="operation-price-box"><strong>Checkpoint</strong><div class="price-value">{{ $day->checkpoint !== null ? number_format((float) $day->checkpoint, 2, ',', ' ') . ' ' . ($talep->currency ?? 'EUR') : '-' }}</div></div>
                                        <div class="operation-price-box"><strong>Litres estimés</strong><div class="price-value">{{ $day->fuel_liters ? number_format((float) $day->fuel_liters, 2, ',', ' ') . ' L' : '-' }}</div></div>
                                        <div class="operation-price-box"><strong>Devise</strong><div class="price-value">{{ $talep->currency ?? 'EUR' }}</div></div>
                                    </div>
                                </div>

                                @if(!empty($quoteLine))
                                    @php
                                        $parts = data_get($quoteLine, 'parts', []);
                                        $dateAdjustments = collect(data_get($parts, 'date_adjustments', []));
                                    @endphp
                                    <details class="mt-3">
                                        <summary class="fw-bold text-primary">Détail du calcul</summary>
                                        <div class="table-responsive mt-2">
                                            <table class="table table-sm table-bordered mb-0">
                                                <tbody>
                                                    <tr>
                                                        <th style="width: 230px;">Règle tarifaire</th>
                                                        <td>
                                                            {{ data_get($quoteLine, 'vehicle_code', '-') }}
                                                            /
                                                            {{ data_get($quoteLine, 'service_code', '-') }}
                                                            @if(data_get($quoteLine, 'rule_id'))
                                                                <span class="text-muted">#{{ data_get($quoteLine, 'rule_id') }}</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Dépôt retenu</th>
                                                        <td>
                                                            {{ data_get($parts, 'depot_name') ?: '-' }}
                                                            @if(data_get($parts, 'depot_address'))
                                                                <span class="text-muted">· {{ data_get($parts, 'depot_address') }}</span>
                                                            @endif
                                                            @if(data_get($parts, 'depot_source'))
                                                                <span class="badge bg-light text-dark border">{{ data_get($parts, 'depot_source') }}</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Km facturation</th>
                                                        <td>
                                                            {{ number_format((float) data_get($parts, 'pricing_distance_km', data_get($quoteLine, 'distance_km', 0)), 1, ',', ' ') }} km
                                                            <span class="text-muted">
                                                                · commercial {{ number_format((float) data_get($parts, 'commercial_distance_km', 0), 1, ',', ' ') }} km
                                                                · approche {{ number_format((float) data_get($parts, 'approach_distance_km', 0), 1, ',', ' ') }} km
                                                                · retour dépôt {{ number_format((float) data_get($parts, 'return_depot_distance_km', 0), 1, ',', ' ') }} km
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <tr><th>Base</th><td>{{ number_format((float) data_get($parts, 'base', 0), 2, ',', ' ') }} {{ $talep->currency ?? 'EUR' }}</td></tr>
                                                    <tr>
                                                        <th>Km supplémentaires</th>
                                                        <td>{{ number_format((float) data_get($parts, 'extra_km', 0), 1, ',', ' ') }} km · {{ number_format((float) data_get($parts, 'extra_km_amount', 0), 2, ',', ' ') }} {{ $talep->currency ?? 'EUR' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Heures supplémentaires</th>
                                                        <td>{{ number_format((float) data_get($parts, 'extra_hours', 0), 2, ',', ' ') }} h · {{ number_format((float) data_get($parts, 'extra_hour_amount', 0), 2, ',', ' ') }} {{ $talep->currency ?? 'EUR' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Nuit</th>
                                                        <td>{{ number_format((float) data_get($parts, 'night_hours', 0), 2, ',', ' ') }} h · {{ number_format((float) data_get($parts, 'night_amount', 0), 2, ',', ' ') }} {{ $talep->currency ?? 'EUR' }}</td>
                                                    </tr>
                                                    <tr><th>Péages</th><td>{{ number_format((float) data_get($parts, 'toll_amount', 0), 2, ',', ' ') }} {{ $talep->currency ?? 'EUR' }}</td></tr>
                                                    <tr>
                                                        <th>Carburant estimé</th>
                                                        <td>
                                                            {{ number_format((float) data_get($parts, 'fuel_amount', 0), 2, ',', ' ') }} {{ $talep->currency ?? 'EUR' }}
                                                            <span class="text-muted">
                                                                · {{ number_format((float) data_get($parts, 'fuel_liters', 0), 2, ',', ' ') }} L
                                                                · {{ number_format((float) data_get($parts, 'fuel_consumption_l_100km', 0), 2, ',', ' ') }} L/100 km
                                                                · {{ number_format((float) data_get($parts, 'fuel_price_per_liter', 0), 3, ',', ' ') }} €/L
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Frais chauffeur</th>
                                                        <td>
                                                            {{ number_format((float) data_get($parts, 'driver_fees_total', 0), 2, ',', ' ') }} {{ $talep->currency ?? 'EUR' }}
                                                            <span class="text-muted">
                                                                · Repas: {{ (int) data_get($parts, 'driver_meal_count', 0) }} / {{ number_format((float) data_get($parts, 'driver_meal_amount', 0), 2, ',', ' ') }}
                                                                · Hôtel: {{ (int) data_get($parts, 'driver_hotel_count', 0) }} / {{ number_format((float) data_get($parts, 'driver_hotel_amount', 0), 2, ',', ' ') }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    @if($dateAdjustments->isNotEmpty())
                                                        <tr>
                                                            <th>Majorations / remises</th>
                                                            <td>
                                                                @foreach($dateAdjustments as $adjustment)
                                                                    <div>
                                                                        {{ data_get($adjustment, 'label', '-') }}
                                                                        · {{ data_get($adjustment, 'direction') === 'discount' ? 'Remise' : 'Majoration' }}
                                                                        · {{ number_format((float) data_get($adjustment, 'adjustment_value', 0), 2, ',', ' ') }}{{ data_get($adjustment, 'adjustment_type') === 'percent' ? '%' : ' EUR' }}
                                                                    </div>
                                                                @endforeach
                                                            </td>
                                                        </tr>
                                                    @endif
                                                    <tr><th>Sous-total ligne</th><td>{{ number_format((float) data_get($quoteLine, 'base_total', 0), 2, ',', ' ') }} {{ $talep->currency ?? 'EUR' }}</td></tr>
                                                    <tr><th>Extras ligne</th><td>{{ number_format((float) data_get($quoteLine, 'extras_total', 0), 2, ',', ' ') }} {{ $talep->currency ?? 'EUR' }}</td></tr>
                                                    <tr><th>Ajustement dates</th><td>{{ number_format((float) data_get($quoteLine, 'date_adjustment_total', 0), 2, ',', ' ') }} {{ $talep->currency ?? 'EUR' }}</td></tr>
                                                    <tr><th>Total HT ligne</th><td><strong>{{ number_format((float) data_get($quoteLine, 'total_ht', 0), 2, ',', ' ') }} {{ $talep->currency ?? 'EUR' }}</strong></td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </details>
                                @endif
                            </div>
                        </div>
                    @empty
                        @if($talep->pickup_location || $talep->dropoff_location)
                            <div class="operation-row d-none" data-index="0">
                                <input type="hidden" name="operations[0][distance_meters]" value="{{ $talep->distance_meters ?? 0 }}">
                                <input type="hidden" class="operation-pickup" value="{{ $talep->pickup_location ?? '' }}">
                                <input type="hidden" class="operation-dropoff" value="{{ $talep->dropoff_location ?? '' }}">
                                <input type="hidden" class="operation-toll" value="0">
                                <input type="hidden" class="operation-fuel-amount" value="0">
                                <input type="hidden" class="operation-fuel-liters" value="0">
                            </div>
                        @endif
                        <div class="text-muted">Aucune opération enregistrée.</div>
                    @endforelse
                    @php
                        $hasTeamTotal = $talep->days->contains(fn ($day) => $day->final_price !== null);
                        $teamTotal = (float) $talep->days->sum('final_price');
                        $hasSystemDayTotal = $talep->days->contains(fn ($day) => $day->system_price !== null);
                        $systemDevisTotal = optional($talep->quote)->system_total;
                        if ($systemDevisTotal === null && $hasSystemDayTotal) {
                            $systemDevisTotal = (float) $talep->days->sum('system_price');
                        }
                        $hasAdminTotal = $talep->days->contains(fn ($day) => $day->admin_price !== null);
                        $adminTotal = (float) $talep->days->sum('admin_price');
                    @endphp
                    <div class="devis-total-grid">
                        <div class="devis-total-box team">
                            <strong>Devis total équipe</strong>
                            <div class="devis-total-value">
                                {{ $hasTeamTotal ? number_format($teamTotal, 2, ',', ' ') . ' ' . ($talep->currency ?? 'EUR') : '-' }}
                            </div>
                        </div>
                        <div class="devis-total-box system">
                            <strong>Devis système total</strong>
                            <div class="devis-total-value">
                                {{ $systemDevisTotal !== null ? number_format((float) $systemDevisTotal, 2, ',', ' ') . ' ' . ($talep->currency ?? 'EUR') : '-' }}
                            </div>
                        </div>
                        <div class="devis-total-box admin">
                            <strong>Devis total admin</strong>
                            <div class="devis-total-value" id="devisTotalAdmin">
                                {{ $hasAdminTotal ? number_format($adminTotal, 2, ',', ' ') . ' ' . ($talep->currency ?? 'EUR') : '-' }}
                            </div>
                        </div>
                        <div class="devis-total-box ai">
                            <strong>Suggestion de prix IA</strong>
                            <div class="devis-total-value" id="devisTotalAiSuggestion">
                                {{ $talep->ai_suggested_total !== null ? number_format((float) $talep->ai_suggested_total, 2, ',', ' ') . ' ' . ($talep->currency ?? 'EUR') : '-' }}
                            </div>
                            @if($talep->ai_suggested_at)
                                <small>Calculée le {{ $talep->ai_suggested_at->format('d/m/Y H:i') }}</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">Fichiers</div>
                        <div class="card-body">
                            @forelse($talep->attachments as $attachment)
                                <a href="{{ asset('storage/talepler_ekleri/' . $attachment->dosya_adi) }}" target="_blank" class="d-block mb-2">
                                    {{ $attachment->orijinal_adi ?? $attachment->dosya_adi }}
                                </a>
                            @empty
                                <span class="text-muted">Aucun fichier.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">Devis système</div>
                        <div class="card-body">
                            @if($talep->quote)
                                @php
                                    $quoteLines = collect(data_get($talep->quote->calculation_json, 'lines', []));
                                    $quoteDriverFees = (float) $quoteLines->sum('driver_fees_total');
                                    $quoteTolls = (float) $quoteLines->sum('toll_amount');
                                    $quoteFuel = (float) $quoteLines->sum('fuel_amount');
                                    $quoteDateAdjustments = (float) $quoteLines->sum('date_adjustment_total');
                                @endphp
                                <div>Sous-total: {{ number_format((float) $talep->quote->subtotal, 2, ',', ' ') }} {{ $talep->quote->currency }}</div>
                                <div>Extras: {{ number_format((float) $talep->quote->extras_total, 2, ',', ' ') }} {{ $talep->quote->currency }}</div>
                                <div class="small text-muted ps-3">Péages: {{ number_format($quoteTolls, 2, ',', ' ') }} {{ $talep->quote->currency }}</div>
                                <div class="small text-muted ps-3">Carburant estimé: {{ number_format($quoteFuel, 2, ',', ' ') }} {{ $talep->quote->currency }}</div>
                                <div class="small text-muted ps-3">Frais chauffeur: {{ number_format($quoteDriverFees, 2, ',', ' ') }} {{ $talep->quote->currency }}</div>
                                @if(abs($quoteDateAdjustments) > 0)
                                    <div class="small text-muted ps-3">Majorations / remises: {{ number_format($quoteDateAdjustments, 2, ',', ' ') }} {{ $talep->quote->currency }}</div>
                                @endif
                                <div>Marge: {{ number_format((float) $talep->quote->margin_amount, 2, ',', ' ') }} {{ $talep->quote->currency }} ({{ $talep->quote->margin_percent ?? 0 }}%)</div>
                                <div>TVA: {{ number_format((float) $talep->quote->vat_amount, 2, ',', ' ') }} {{ $talep->quote->currency }} ({{ $talep->quote->vat_rate ?? 0 }}%)</div>
                                <div>Total système: <strong>{{ number_format((float) $talep->quote->system_total, 2, ',', ' ') }} {{ $talep->quote->currency }}</strong></div>
                                <div>Total final: {{ number_format((float) $talep->quote->final_total, 2, ',', ' ') }} {{ $talep->quote->currency }}</div>
                                @if($talep->quote->calculated_at)
                                    <div class="text-muted small mt-2">Calculé le {{ $talep->quote->calculated_at->format('d/m/Y H:i') }}</div>
                                @endif
                            @else
                                <span class="text-muted">Aucun devis système.</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4 history-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="history-title">
                        <span>Historique</span>
                        <span class="badge bg-light text-dark border">{{ $talep->histories->count() }}</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary history-toggle" id="talepHistoryToggle" aria-expanded="false" aria-controls="talepHistoryBody">
                        <span class="history-toggle-icon">›</span><span class="history-toggle-label">Afficher</span>
                    </button>
                </div>
                <div class="card-body p-0" id="talepHistoryBody" style="display:none;">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Utilisateur</th>
                                <th>Action</th>
                                <th>Champ</th>
                                <th>Ancien</th>
                                <th>Nouveau</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($talep->histories as $history)
                                <tr>
                                    <td>{{ optional($history->created_at)->format('d/m/Y H:i') }}</td>
                                    <td>{{ $history->user->name ?? '-' }}</td>
                                    <td>{{ $history->action_type ?? '-' }}</td>
                                    <td>{{ $history->field_name ?? '-' }}</td>
                                    <td>{{ $history->old_value ?? '-' }}</td>
                                    <td>{{ $history->new_value ?? '-' }}</td>
                                    <td>{{ $history->note ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-muted">Aucun historique.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5 mb-4">
            @include('talepler.partials.map', ['talep' => $talep, 'autoCalculate' => true])

            <div class="talep-side-sticky">
            @include('talepler.partials.mail-thread', ['talep' => $talep])

            {{-- Claude AI Pricing Assistant --}}
            @if(auth()->user()?->hasAnyRole(['Superadmin', 'Admin']))
            @php
                $claudeDays = $talep->days->map(function($d) {
                    return [
                        'date'     => optional($d->service_date)->format('d/m/Y') ?? '-',
                        'service'  => $d->service_type ?? '-',
                        'vehicle'  => $d->vehicle_type ?? '-',
                        'pax'      => $d->pax ?? '-',
                        'from'     => $d->pickup_location ?? '-',
                        'to'       => $d->dropoff_location ?? '-',
                        'km'       => $d->distance_meters ? round($d->distance_meters/1000, 1) : '-',
                        'admin_price' => $d->admin_price ?? null,
                    ];
                });
                $claudeInitMsg = "Dossier #{$talep->id} – Analyse de tarification\n";
                $claudeInitMsg .= "Client : " . ($talep->customer_name ?? '-') . " | Pax : " . ($talep->total_pax ?? '-') . "\n";
                $claudeInitMsg .= "Véhicule : " . ($talep->vehicule->name ?? $talep->vehicle_type ?? '-') . " | Service : " . ($talep->serviceType->name ?? $talep->service_type ?? '-') . "\n\n";
                $claudeInitMsg .= "Opérations :\n";
                foreach ($claudeDays as $i => $d) {
                    $claudeInitMsg .= ($i+1).". {$d['date']} | {$d['vehicle']} | {$d['service']} | {$d['pax']} pax | {$d['from']} → {$d['to']} | {$d['km']} km";
                    if ($d['admin_price']) $claudeInitMsg .= " | Prix admin : {$d['admin_price']}€";
                    $claudeInitMsg .= "\n";
                }
                $claudeInitMsg .= "\nDonne une suggestion de prix globale pour ce dossier (HT et TTC), en tenant compte du taux d'occupation et de la saison.";
            @endphp
            <div class="card mb-4" id="claudeCard">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-robot"></i> Claude – Assistant tarification</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="claudeToggle">
                        <i class="fa fa-chevron-down"></i>
                    </button>
                </div>
                <div id="claudeCardBody" class="card-body p-0">
                    <div id="claudeMessages" style="max-height:420px;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px;">
                        <div class="text-center text-muted small py-3">
                            Cliquez sur <strong>Analyser</strong> pour obtenir une suggestion de prix basée sur les données du dossier, la saison et le taux d'occupation.
                        </div>
                    </div>
                    <div class="border-top p-2 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success flex-shrink-0" id="claudeAnalyzeBtn"
                            data-msg="{{ e($claudeInitMsg) }}">
                            <i class="fa fa-magic"></i> Analyser
                        </button>
                        <input type="text" id="claudeInput" class="form-control form-control-sm"
                            placeholder="Question supplémentaire…" autocomplete="off">
                        <button type="button" class="btn btn-sm btn-primary flex-shrink-0" id="claudeSendBtn">
                            <i class="fa fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endif
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const statusSelect = document.getElementById('talepStatusSelect');
    const statusMessage = document.getElementById('talepStatusMessage');
    const missingInfoInput = document.getElementById('missingInfoInput');
    const saveMissingInfo = document.getElementById('saveMissingInfo');
    const missingInfoMessage = document.getElementById('missingInfoMessage');

    if (statusSelect) {
        statusSelect.addEventListener('change', function () {
            statusSelect.disabled = true;
            if (statusMessage) statusMessage.textContent = 'Mise à jour...';

            fetch(`/talepler/${statusSelect.dataset.id}/konfirme-durumu`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ konfirme_durumu: statusSelect.value })
            })
            .then(async response => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                    throw new Error(firstError || data.message || 'Erreur lors de la mise à jour du statut.');
                }

                if (statusMessage) {
                    statusMessage.textContent = data.message || 'Le statut de la demande a été mis à jour.';
                }

                if (data.post_url) {
                    setTimeout(() => window.location.reload(), 900);
                }
            })
            .catch(error => {
                if (statusMessage) statusMessage.textContent = error.message;
            })
            .finally(() => {
                statusSelect.disabled = false;
            });
        });
    }

    if (saveMissingInfo && missingInfoInput) {
        saveMissingInfo.addEventListener('click', function () {
            saveMissingInfo.disabled = true;
            if (missingInfoMessage) missingInfoMessage.textContent = 'Enregistrement...';

            fetch(`/talepler/${saveMissingInfo.dataset.id}/update-internal-notes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ internal_notes: missingInfoInput.value || null })
            })
            .then(async response => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                    throw new Error(firstError || data.message || 'Erreur lors de l’enregistrement.');
                }

                if (missingInfoMessage) {
                    missingInfoMessage.textContent = data.message || 'Enregistré.';
                }
            })
            .catch(error => {
                if (missingInfoMessage) missingInfoMessage.textContent = error.message;
            })
            .finally(() => {
                saveMissingInfo.disabled = false;
            });
        });
    }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const historyToggle = document.getElementById('talepHistoryToggle');
    const historyBody = document.getElementById('talepHistoryBody');

    if (historyToggle && historyBody) {
        historyToggle.addEventListener('click', function () {
            const expanded = historyToggle.getAttribute('aria-expanded') === 'true';
            historyToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            historyBody.style.display = expanded ? 'none' : '';
            const label = historyToggle.querySelector('.history-toggle-label');
            if (label) {
                label.textContent = expanded ? 'Afficher' : 'Masquer';
            }
        });
    }

    const button = document.getElementById('saveShowPrixAdmin');
    const input = document.getElementById('show_prix_admin');
    const commentAdmin = document.getElementById('show_comment_admin');
    const discountPrice = document.getElementById('show_discount_price');
    const discountValidUntil = document.getElementById('show_discount_valid_until');
    const secondDiscountPrice = document.getElementById('show_second_discount_price');
    const prixRetenuDisplay = document.getElementById('prixRetenuDisplay');
    const discountPercentDisplay = document.getElementById('discountPercentDisplay');
    const message = document.getElementById('showPrixAdminMessage');
    const priceCurrency = @json($currency);

    function formatTalepPrice(value) {
        const normalized = String(value || '').replace(',', '.');
        const number = parseFloat(normalized);
        if (!Number.isFinite(number)) return '-';
        return number.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + priceCurrency;
    }

    function updatePrixRetenuDisplay() {
        if (!prixRetenuDisplay) return;
        const selected = (secondDiscountPrice && secondDiscountPrice.value)
            || (discountPrice && discountPrice.value)
            || (input && input.value)
            || @json($talep->system_total ?? $quoteAiTotal ?? null);
        prixRetenuDisplay.innerText = formatTalepPrice(selected);
    }

    function updateDiscountPercentDisplay() {
        if (!discountPercentDisplay) return;
        const referenceValue = (input && input.value) || @json($talep->system_total ?? $quoteAiTotal ?? null);
        const reference = parseFloat(String(referenceValue || '').replace(',', '.'));
        const discount = parseFloat(String((discountPrice && discountPrice.value) || '').replace(',', '.'));
        if (!Number.isFinite(reference) || reference <= 0 || !Number.isFinite(discount)) {
            discountPercentDisplay.innerText = '';
            return;
        }
        const amount = Math.max(0, reference - discount);
        const percent = Math.max(0, (amount / reference) * 100);
        discountPercentDisplay.innerText = percent.toLocaleString('fr-FR', { maximumFractionDigits: 1 }) + '% de remise · ' + formatTalepPrice(amount);
    }

    [input, discountPrice, secondDiscountPrice].forEach(function (field) {
        if (field) {
            field.addEventListener('input', updatePrixRetenuDisplay);
            field.addEventListener('input', updateDiscountPercentDisplay);
        }
    });

    if (!button || !input) return;

    button.addEventListener('click', function () {
        button.disabled = true;
        if (message) message.innerText = 'Enregistrement...';

        fetch(`/talepler/${button.dataset.id}/update-fiyat`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                final_total: input.value || null,
                comment_admin: commentAdmin ? commentAdmin.value : null,
                discount_price: discountPrice ? (discountPrice.value || null) : null,
                discount_valid_until: discountValidUntil ? (discountValidUntil.value || null) : null,
                second_discount_price: secondDiscountPrice ? (secondDiscountPrice.value || null) : null
            })
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(data.message || 'Erreur lors de la mise à jour.');
            }

            if (message) message.innerText = data.message || 'Le prix admin a été mis à jour.';
            updatePrixRetenuDisplay();
            const meta = document.getElementById('showPrixAdminMeta');
            if (meta && data.admin_price_user_name) {
                meta.innerText = `Donné par: ${data.admin_price_user_name}${data.admin_price_updated_at ? ' · ' + data.admin_price_updated_at : ''}`;
            }
        })
        .catch(error => {
            if (message) message.innerText = error.message;
        })
        .finally(() => {
            button.disabled = false;
        });
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const currency = @json($talep->currency ?? 'EUR');
    const priceInputs = Array.from(document.querySelectorAll('.operation-admin-price'));
    const totalNode = document.getElementById('operationAdminTotal');
    const devisTotalAdminNode = document.getElementById('devisTotalAdmin');
    const saveButton = document.getElementById('saveOperationAdminPrices');
    const message = document.getElementById('operationAdminPriceMessage');
    const mainAdminInput = document.getElementById('show_prix_admin');
    const mainAdminMeta = document.getElementById('showPrixAdminMeta');

    function parsePrice(value) {
        const normalized = String(value || '').replace(',', '.').trim();
        if (!normalized) return null;
        const number = Number(normalized);
        return Number.isFinite(number) ? number : null;
    }

    function formatPrice(value) {
        if (value === null || value === undefined || Number.isNaN(value)) return '-';
        return new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value) + ' ' + currency;
    }

    function calculateTotal() {
        let total = 0;
        let hasPrice = false;

        priceInputs.forEach(input => {
            const value = parsePrice(input.value);
            if (value !== null) {
                hasPrice = true;
                total += value;
            }
        });

        if (totalNode) {
            totalNode.textContent = hasPrice ? formatPrice(total) : '-';
        }
        if (devisTotalAdminNode) {
            devisTotalAdminNode.textContent = hasPrice ? formatPrice(total) : '-';
        }

        return hasPrice ? total : null;
    }

    priceInputs.forEach(input => {
        input.addEventListener('input', function () {
            calculateTotal();
            if (message) message.textContent = 'Modifications non enregistrées.';
        });
    });

    calculateTotal();

    if (!saveButton || !priceInputs.length) return;

    saveButton.addEventListener('click', function () {
        const prices = {};
        priceInputs.forEach(input => {
            prices[input.dataset.dayId] = input.value === '' ? null : input.value;
        });

        saveButton.disabled = true;
        if (message) message.textContent = 'Enregistrement...';

        fetch(`/talepler/${saveButton.dataset.id}/operation-prices`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ prices })
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Erreur lors de la mise à jour des prix.');
            }

            if (message) message.textContent = data.message || 'Les prix admin ont été mis à jour.';
            if (totalNode) totalNode.textContent = data.total_formatted || formatPrice(calculateTotal());
            if (devisTotalAdminNode) devisTotalAdminNode.textContent = data.total_formatted || formatPrice(calculateTotal());
            if (mainAdminInput && data.total !== undefined && data.total !== null) {
                mainAdminInput.value = data.total;
            }
            if (mainAdminMeta && data.admin_price_user_name) {
                mainAdminMeta.innerText = `Donné par: ${data.admin_price_user_name}${data.admin_price_updated_at ? ' · ' + data.admin_price_updated_at : ''}`;
            }
        })
        .catch(error => {
            if (message) message.textContent = error.message;
        })
        .finally(() => {
            saveButton.disabled = false;
        });
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.saveOperationAdminNote').forEach(button => {
        button.addEventListener('click', function () {
            const box = button.closest('.operation-note-box');
            const textarea = box ? box.querySelector('.operation-admin-note') : null;
            const message = box ? box.querySelector('.operation-admin-note-message') : null;

            if (!textarea || !textarea.dataset.url) return;

            button.disabled = true;
            if (message) message.textContent = 'Enregistrement...';

            fetch(textarea.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ note_admin: textarea.value || null })
            })
            .then(async response => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(data.message || 'Erreur lors de l’enregistrement.');
                }
                if (message) message.textContent = data.message || 'Enregistré.';
            })
            .catch(error => {
                if (message) message.textContent = error.message;
            })
            .finally(() => {
                button.disabled = false;
            });
        });
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('aiPriceSuggestionButton');
    const result = document.getElementById('aiPriceSuggestionResult');
    const totalSuggestion = document.getElementById('devisTotalAiSuggestion');
    const currency = @json($talep->currency ?? 'EUR');

    if (!button || !result) return;

    const formatPrice = value => new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2
    }).format(Number(value || 0));

    button.addEventListener('click', function () {
        button.disabled = true;
        result.className = 'alert alert-info m-3';
        result.textContent = 'Calcul de la suggestion en cours…';

        fetch(button.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Impossible de calculer la suggestion.');
            }

            result.textContent = '';

            const title = document.createElement('div');
            title.className = 'fw-bold mb-2';
            title.textContent = `Suggestion totale Paris Via IA : ${formatPrice(data.total)}`;
            result.appendChild(title);
            if (totalSuggestion) {
                totalSuggestion.textContent = formatPrice(data.total);
            }

            (data.operations || []).forEach((operation, index) => {
                const line = document.createElement('div');
                line.className = 'small';
                const label = operation.service_date
                    ? `Opération ${index + 1} · ${operation.service_date}`
                    : `Opération ${index + 1}`;
                line.textContent = `${label} : ${formatPrice(operation.suggested_price)}`;
                result.appendChild(line);
            });

            const warning = document.createElement('div');
            warning.className = 'small mt-2 fw-bold';
            warning.textContent = data.warning || 'Validation par un administrateur obligatoire.';
            result.appendChild(warning);
        })
        .catch(error => {
            result.className = 'alert alert-danger m-3';
            result.textContent = error.message;
        })
        .finally(() => {
            button.disabled = false;
        });
    });
});
</script>
@if(config('services.google_maps.api_key'))
    <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places"></script>
@endif

<script>
(function () {
    const messagesEl = document.getElementById('claudeMessages');
    const analyzeBtn = document.getElementById('claudeAnalyzeBtn');
    const sendBtn    = document.getElementById('claudeSendBtn');
    const input      = document.getElementById('claudeInput');
    const toggle     = document.getElementById('claudeToggle');
    const body       = document.getElementById('claudeCardBody');

    if (!messagesEl) return;

    let history = [];

    function bubble(role, text) {
        const wrap = document.createElement('div');
        wrap.style.cssText = role === 'user'
            ? 'align-self:flex-end;max-width:85%;background:#0d6efd;color:#fff;border-radius:12px 12px 2px 12px;padding:8px 12px;font-size:13px;white-space:pre-wrap;'
            : 'align-self:flex-start;max-width:92%;background:#f1f5f9;color:#0f172a;border-radius:2px 12px 12px 12px;padding:8px 12px;font-size:13px;white-space:pre-wrap;';
        wrap.textContent = text;
        messagesEl.appendChild(wrap);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function thinking() {
        const el = document.createElement('div');
        el.id = 'claudeThinking';
        el.style.cssText = 'align-self:flex-start;color:#64748b;font-size:12px;padding:4px 8px;';
        el.textContent = '…';
        messagesEl.appendChild(el);
        messagesEl.scrollTop = messagesEl.scrollHeight;
        return el;
    }

    async function send(message) {
        if (!message.trim()) return;

        const first = messagesEl.querySelector('.text-center.text-muted');
        if (first) first.remove();

        bubble('user', message);
        const loader = thinking();

        if (analyzeBtn) analyzeBtn.disabled = true;
        if (sendBtn)    sendBtn.disabled = true;

        try {
            const res = await fetch('/api/claude/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ message, history })
            });
            const data = await res.json();
            loader.remove();

            if (!res.ok) throw new Error(data.message || 'Erreur Claude');

            history.push({ role: 'user', content: message });
            history.push({ role: 'assistant', content: data.answer });
            bubble('assistant', data.answer);
        } catch (e) {
            loader.remove();
            bubble('assistant', '⚠️ ' + e.message);
        } finally {
            if (analyzeBtn) analyzeBtn.disabled = false;
            if (sendBtn)    sendBtn.disabled = false;
            if (input)      input.value = '';
        }
    }

    if (analyzeBtn) {
        analyzeBtn.addEventListener('click', () => send(analyzeBtn.dataset.msg));
    }

    if (sendBtn && input) {
        sendBtn.addEventListener('click', () => send(input.value));
        input.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(input.value); } });
    }

    if (toggle && body) {
        toggle.addEventListener('click', () => {
            const hidden = body.style.display === 'none';
            body.style.display = hidden ? '' : 'none';
            toggle.innerHTML = hidden ? '<i class="fa fa-chevron-up"></i>' : '<i class="fa fa-chevron-down"></i>';
        });
    }
})();
</script>
@endsection
