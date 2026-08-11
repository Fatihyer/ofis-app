@extends('layouts.app')

@section('title', '| Google Ads')

@section('content')
@php
    $uploadSummary = session('google_ads_upload_summary');
    $searchTermError = data_get($searchTermReport, '0.error');
    $keywordError = data_get($keywordReport, '0.error');
    $quality = $leadQuality['summary'] ?? [];
    $conversionStatusByKey = collect($leadConversionStatus ?? [])->keyBy('key');
    $missingClickStatus = $conversionStatusByKey->get('missing_click_id');
    $statusBadge = function ($status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'uploaded' => 'success',
            'pending' => 'primary',
            'failed', 'upload_failed' => 'danger',
            'missing_click_id', 'skipped_no_click_id' => 'warning',
            'skipped' => 'secondary',
            default => 'light',
        };
    };
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ');
    $short = fn ($value, $limit = 70) => \Illuminate\Support\Str::limit((string) $value, $limit);
@endphp

<style>
    .gads-page { background:#f8fafc; min-height:calc(100vh - 90px); padding:16px; }
    .gads-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; margin-bottom:14px; }
    .gads-head h2 { margin:0; color:#0f172a; font-size:24px; font-weight:850; }
    .gads-head small { display:block; color:#64748b; font-weight:700; margin-top:4px; }
    .gads-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:10px; margin-bottom:14px; }
    .gads-metric, .gads-card { background:#fff; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 8px 22px rgba(15,23,42,.05); }
    .gads-metric { padding:12px 14px; }
    .gads-metric span { display:block; color:#64748b; font-size:11px; font-weight:800; text-transform:uppercase; }
    .gads-metric strong { display:block; color:#0f172a; font-size:22px; line-height:1.1; margin-top:4px; }
    .gads-card { margin-bottom:14px; overflow:hidden; }
    .gads-card-h { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:12px 14px; border-bottom:1px solid #e5e7eb; }
    .gads-card-h h3 { margin:0; color:#0f172a; font-size:15px; font-weight:850; }
    .gads-card-body { padding:12px 14px; }
    .gads-status-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:10px; }
    .gads-status-item { border:1px solid #e5e7eb; border-left-width:5px; border-radius:8px; padding:12px; background:#fff; min-height:132px; }
    .gads-status-item.is-success { border-left-color:#16a34a; background:#f0fdf4; }
    .gads-status-item.is-warning { border-left-color:#f59e0b; background:#fffbeb; }
    .gads-status-item.is-primary { border-left-color:#2563eb; background:#eff6ff; }
    .gads-status-item.is-danger { border-left-color:#dc2626; background:#fef2f2; }
    .gads-status-item.is-secondary { border-left-color:#64748b; background:#f8fafc; }
    .gads-status-top { display:flex; justify-content:space-between; align-items:flex-start; gap:8px; }
    .gads-status-label { color:#334155; font-size:12px; font-weight:850; line-height:1.2; }
    .gads-status-count { color:#0f172a; font-size:28px; font-weight:900; line-height:1; margin-top:10px; }
    .gads-status-hint { color:#475569; font-size:12px; line-height:1.35; margin:8px 0 0; }
    .gads-status-bar { height:7px; border-radius:999px; overflow:hidden; background:rgba(148,163,184,.28); margin-top:10px; }
    .gads-status-fill { height:100%; border-radius:999px; background:#64748b; }
    .gads-status-item.is-success .gads-status-fill { background:#16a34a; }
    .gads-status-item.is-warning .gads-status-fill { background:#f59e0b; }
    .gads-status-item.is-primary .gads-status-fill { background:#2563eb; }
    .gads-status-item.is-danger .gads-status-fill { background:#dc2626; }
    .gads-status-note { margin-top:10px; border:1px solid #fde68a; background:#fffbeb; color:#92400e; border-radius:8px; padding:10px 12px; font-size:12px; font-weight:700; }
    .gads-table { margin:0; font-size:12px; }
    .gads-table th { color:#475569; background:#f8fafc; border-top:0; white-space:nowrap; }
    .gads-table td { vertical-align:top; }
    .gads-error { color:#991b1b; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:10px 12px; font-size:12px; }
    .gads-muted { color:#64748b; }
    .gads-pill { display:inline-flex; align-items:center; border-radius:999px; padding:4px 8px; font-size:11px; font-weight:800; background:#eef2ff; color:#3730a3; }
    .gads-actions { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:8px; }
    .gads-flash { white-space:pre-wrap; max-height:260px; overflow:auto; margin:0; font-size:12px; }
    .gads-list { margin:0; padding-left:18px; color:#334155; font-size:12px; }
    .gads-list li + li { margin-top:5px; }
    @media (max-width: 1000px) {
        .gads-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); }
        .gads-status-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); }
        .gads-head { display:block; }
        .gads-head form { margin-top:10px; }
    }
    @media (max-width: 640px) {
        .gads-grid { grid-template-columns:1fr; }
        .gads-status-grid { grid-template-columns:1fr; }
        .gads-page { padding:10px; }
    }
</style>

<div class="gads-page">
    <div class="gads-head">
        <div>
            <h2>Google Ads</h2>
            <small>Compte {{ $customerId }} · MCC {{ $loginCustomerId ?: '-' }} · API {{ $apiVersion }} · {{ $range['start'] }} / {{ $range['end'] }}</small>
        </div>
        <div class="gads-actions">
            <form method="POST" action="{{ route('google-ads.refresh') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">Actualiser</button>
            </form>
            <form method="POST" action="{{ route('google-ads.upload-form-leads') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary">Envoyer les leads en attente</button>
            </form>
        </div>
    </div>

    @if($uploadSummary)
        <div class="alert alert-info">
            Upload: {{ $uploadSummary['processed'] }} traité(s),
            {{ $uploadSummary['uploaded'] }} envoyé(s),
            {{ $uploadSummary['failed'] }} erreur(s),
            {{ $uploadSummary['skipped'] }} ignoré(s).
        </div>
    @endif
    @if(session('google_ads_message'))
        <div class="alert alert-success"><pre class="gads-flash">{{ session('google_ads_message') }}</pre></div>
    @endif
    @if(session('google_ads_error'))
        <div class="alert alert-danger"><pre class="gads-flash">{{ session('google_ads_error') }}</pre></div>
    @endif

    <div class="gads-grid">
        <div class="gads-metric">
            <span>Connexion API</span>
            <strong class="text-{{ $connection['ok'] ? 'success' : 'danger' }}">{{ $connection['ok'] ? 'OK' : 'Erreur' }}</strong>
        </div>
        <div class="gads-metric">
            <span>Leads capturés</span>
            <strong>{{ $leadCounts['total'] }}</strong>
        </div>
        <div class="gads-metric">
            <span>Avec click ID</span>
            <strong>{{ $leadCounts['with_click_id'] }}</strong>
        </div>
        <div class="gads-metric">
            <span>Aujourd'hui</span>
            <strong>{{ $leadCounts['today'] }}</strong>
        </div>
        <div class="gads-metric">
            <span>Confirmés 30j</span>
            <strong>{{ $quality['confirmed'] ?? 0 }}</strong>
        </div>
        <div class="gads-metric">
            <span>Taux confirmation</span>
            <strong>{{ $quality['confirm_rate'] ?? 0 }}%</strong>
        </div>
        <div class="gads-metric">
            <span>Valeur devis 30j</span>
            <strong>{{ $money($quality['quoted_value'] ?? 0) }} €</strong>
        </div>
        <div class="gads-metric">
            <span>Click ID 30j</span>
            <strong>{{ $quality['click_id_rate'] ?? 0 }}%</strong>
        </div>
    </div>

    <div class="gads-card">
        <div class="gads-card-h">
            <h3>Statut des conversions offline</h3>
            <span class="gads-pill">Form leads → Google Ads</span>
        </div>
        <div class="gads-card-body">
            <div class="gads-status-grid">
                @foreach($leadConversionStatus as $status)
                    <div class="gads-status-item is-{{ $status['tone'] }}">
                        <div class="gads-status-top">
                            <div>
                                <div class="gads-status-label">{{ $status['title'] }}</div>
                                <div class="gads-status-count">{{ $status['count'] }}</div>
                            </div>
                            <span class="badge bg-{{ $statusBadge($status['key']) }} {{ $status['tone'] === 'warning' ? 'text-dark' : '' }}">{{ $status['percent'] }}%</span>
                        </div>
                        <p class="gads-status-hint">{{ $status['hint'] }}</p>
                        <div class="gads-status-bar">
                            <div class="gads-status-fill" style="width: {{ min(100, $status['percent']) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if(($missingClickStatus['count'] ?? 0) > 0)
                <div class="gads-status-note">
                    {{ $missingClickStatus['count'] }} lead(s) ne peuvent pas partir à Google Ads parce que le click ID manque. C’est le point à surveiller en priorité.
                </div>
            @endif
        </div>
    </div>

    @unless($connection['ok'])
        <div class="gads-error mb-3">{{ $connection['error'] }}</div>
    @endunless

    <div class="row">
        <div class="col-xl-7">
            <div class="gads-card">
                <div class="gads-card-h">
                    <h3>Campagnes - 30 derniers jours</h3>
                </div>
                <div class="table-responsive">
                    <table class="table gads-table">
                        <thead>
                            <tr>
                                <th>Campagne</th>
                                <th>Statut</th>
                                <th>Coût</th>
                                <th>Clics</th>
                                <th>Conv.</th>
                                <th>CTR</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($campaigns as $campaign)
                                @if(isset($campaign['error']))
                                    <tr><td colspan="6"><div class="gads-error">{{ $campaign['error'] }}</div></td></tr>
                                @else
                                    <tr>
                                        <td>{{ $campaign['name'] }}</td>
                                        <td><span class="badge bg-light text-dark">{{ $campaign['status'] }}</span></td>
                                        <td>{{ $money($campaign['cost']) }} €</td>
                                        <td>{{ $campaign['clicks'] }}</td>
                                        <td>{{ $campaign['conversions'] }}</td>
                                        <td>{{ $campaign['ctr'] }}%</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="gads-card">
                <div class="gads-card-h">
                    <h3>Liste négative partagée</h3>
                    <div class="gads-actions">
                        <form method="POST" action="{{ route('google-ads.negative-keywords.apply-candidates') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger">Ajouter candidats sûrs</button>
                        </form>
                        <form method="POST" action="{{ route('google-ads.negative-keywords.clean') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Nettoyer doublons</button>
                        </form>
                        <form method="POST" action="{{ route('google-ads.shared-negative-list.setup') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">Créer / attacher</button>
                        </form>
                    </div>
                </div>
                <div class="gads-card-body">
                    @if(isset($sharedNegativeList[0]['error']))
                        <div class="gads-error">{{ $sharedNegativeList[0]['error'] }}</div>
                    @elseif($sharedNegativeList['exists'])
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-success">active</span>
                            <span class="badge bg-light text-dark">{{ $sharedNegativeList['keyword_count'] }} mots-clés</span>
                            <span class="badge bg-light text-dark">{{ count($sharedNegativeList['attached_campaigns']) }} campagne(s)</span>
                        </div>
                        <div class="gads-muted mt-2">
                            {{ $sharedNegativeList['name'] }}
                            @if(!empty($sharedNegativeList['attached_campaigns']))
                                · {{ collect($sharedNegativeList['attached_campaigns'])->pluck('name')->implode(', ') }}
                            @endif
                        </div>
                    @else
                        <div class="gads-muted">Pas encore créée. Le bouton prépare la liste et l’attache aux campagnes Search actives.</div>
                    @endif
                </div>
            </div>
            <div class="gads-card">
                <div class="gads-card-h">
                    <h3>Uploads form submit</h3>
                </div>
                <div class="gads-card-body">
                    <div class="d-flex flex-wrap gap-2">
                        @forelse($uploadCounts as $status => $count)
                            <span class="badge bg-{{ $statusBadge($status) }}">{{ $status }}: {{ $count }}</span>
                        @empty
                            <span class="gads-muted">Aucun upload enregistré.</span>
                        @endforelse
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table gads-table">
                        <thead>
                            <tr>
                                <th>Lead</th>
                                <th>Statut</th>
                                <th>Essais</th>
                                <th>Dernier essai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lastUploads as $upload)
                                <tr>
                                    <td>#{{ $upload->google_ads_lead_id }} / dossier {{ $upload->talep_id ?: '-' }}</td>
                                    <td><span class="badge bg-{{ $statusBadge($upload->upload_status) }}">{{ $upload->upload_status }}</span></td>
                                    <td>{{ $upload->upload_attempts }}</td>
                                    <td>{{ $upload->last_attempt_at ?: '-' }}</td>
                                </tr>
                                @if($upload->error_message)
                                    <tr>
                                        <td colspan="4" class="text-danger">{{ \Illuminate\Support\Str::limit($upload->error_message, 180) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="gads-card">
        <div class="gads-card-h">
            <h3>Qualité des leads WordPress → Laravel</h3>
            <span class="gads-pill">{{ $quality['range_label'] ?? '30 derniers jours' }}</span>
        </div>
        <div class="gads-card-body">
            <div class="row g-2">
                <div class="col-md-3"><span class="gads-muted">Leads</span><br><strong>{{ $quality['total'] ?? 0 }}</strong></div>
                <div class="col-md-3"><span class="gads-muted">Uploadés Ads</span><br><strong>{{ $quality['uploaded'] ?? 0 }}</strong></div>
                <div class="col-md-3"><span class="gads-muted">En attente upload</span><br><strong>{{ $quality['pending_upload'] ?? 0 }}</strong></div>
                <div class="col-md-3"><span class="gads-muted">Valeur moyenne</span><br><strong>{{ $money($quality['avg_value'] ?? 0) }} €</strong></div>
            </div>
        </div>
        <div class="row g-0">
            <div class="col-xl-6">
                <div class="table-responsive">
                    <table class="table gads-table">
                        <thead>
                            <tr>
                                <th>Campagne UTM</th>
                                <th>Leads</th>
                                <th>Confirmés</th>
                                <th>Taux</th>
                                <th>Valeur</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($leadQuality['campaigns'] ?? []) as $row)
                                <tr>
                                    <td>{{ $short($row->label, 58) }}</td>
                                    <td>{{ $row->total }}</td>
                                    <td>{{ $row->confirmed }}</td>
                                    <td>{{ $row->confirm_rate }}%</td>
                                    <td>{{ $money($row->quoted_value) }} €</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="gads-muted">Aucune donnée campagne.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="table-responsive">
                    <table class="table gads-table">
                        <thead>
                            <tr>
                                <th>Landing page</th>
                                <th>Leads</th>
                                <th>Click ID</th>
                                <th>Confirmés</th>
                                <th>Valeur</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($leadQuality['landing_pages'] ?? []) as $row)
                                <tr>
                                    <td title="{{ $row->label }}">{{ $short($row->label, 62) }}</td>
                                    <td>{{ $row->total }}</td>
                                    <td>{{ $row->click_id_rate }}%</td>
                                    <td>{{ $row->confirmed }}</td>
                                    <td>{{ $money($row->quoted_value) }} €</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="gads-muted">Aucune donnée landing.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="row g-0">
            <div class="col-xl-4">
                <div class="table-responsive">
                    <table class="table gads-table">
                        <thead><tr><th>Keyword UTM</th><th>Leads</th><th>Confirmés</th></tr></thead>
                        <tbody>
                            @forelse(($leadQuality['terms'] ?? []) as $row)
                                <tr><td>{{ $short($row->label, 42) }}</td><td>{{ $row->total }}</td><td>{{ $row->confirmed }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="gads-muted">Aucune donnée keyword.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="table-responsive">
                    <table class="table gads-table">
                        <thead><tr><th>Service</th><th>Leads</th><th>Confirmés</th></tr></thead>
                        <tbody>
                            @forelse(($leadQuality['services'] ?? []) as $row)
                                <tr><td>{{ $short($row->label, 42) }}</td><td>{{ $row->total }}</td><td>{{ $row->confirmed }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="gads-muted">Aucune donnée service.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="table-responsive">
                    <table class="table gads-table">
                        <thead><tr><th>Véhicule</th><th>Leads</th><th>Confirmés</th></tr></thead>
                        <tbody>
                            @forelse(($leadQuality['vehicles'] ?? []) as $row)
                                <tr><td>{{ $short($row->label, 42) }}</td><td>{{ $row->total }}</td><td>{{ $row->confirmed }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="gads-muted">Aucune donnée véhicule.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <div class="gads-card">
                <div class="gads-card-h">
                    <h3>Négatifs à ajouter / vérifier</h3>
                    <span class="gads-pill">Search terms</span>
                </div>
                @if($searchTermError)
                    <div class="gads-card-body"><div class="gads-error">{{ $searchTermError }}</div></div>
                @else
                    <div class="table-responsive">
                        <table class="table gads-table">
                            <thead>
                                <tr>
                                    <th>Terme</th>
                                    <th>Raison</th>
                                    <th>Coût</th>
                                    <th>Clics</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($searchTermReport['negative'] ?? []) as $row)
                                    <tr>
                                        <td>{{ $row['term'] }}</td>
                                        <td>{{ $row['classification']['negative'] }}</td>
                                        <td>{{ $money($row['cost']) }} €</td>
                                        <td>{{ $row['clicks'] }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('google-ads.negative-keywords.store') }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="campaign_id" value="{{ $row['campaign_id'] }}">
                                                <input type="hidden" name="term" value="{{ $row['term'] }}">
                                                <input type="hidden" name="match_type" value="EXACT">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Ajouter à la liste</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="gads-muted">Aucun candidat négatif détecté.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-6">
            <div class="gads-card">
                <div class="gads-card-h">
                    <h3>Termes à transformer en keywords</h3>
                    <span class="gads-pill">Opportunités</span>
                </div>
                @if($searchTermError)
                    <div class="gads-card-body"><div class="gads-error">{{ $searchTermError }}</div></div>
                @else
                    <div class="table-responsive">
                        <table class="table gads-table">
                            <thead>
                                <tr>
                                    <th>Terme</th>
                                    <th>Coût</th>
                                    <th>Clics</th>
                                    <th>Conv.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($searchTermReport['opportunities'] ?? []) as $row)
                                    <tr>
                                        <td>{{ $row['term'] }}</td>
                                        <td>{{ $money($row['cost']) }} €</td>
                                        <td>{{ $row['clicks'] }}</td>
                                        <td>{{ $row['conversions'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="gads-muted">Aucun terme fort détecté.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="gads-card">
        <div class="gads-card-h">
            <h3>Keywords à surveiller</h3>
            <span class="gads-pill">Coût sans conversion</span>
        </div>
        @if($keywordError)
            <div class="gads-card-body"><div class="gads-error">{{ $keywordError }}</div></div>
        @else
            <div class="table-responsive">
                <table class="table gads-table">
                    <thead>
                        <tr>
                            <th>Keyword</th>
                            <th>Match</th>
                            <th>Campagne</th>
                            <th>Coût</th>
                            <th>Clics</th>
                            <th>Conv.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($keywordReport['weak'] ?? []) as $row)
                            <tr>
                                <td>{{ $row['text'] }}</td>
                                <td>{{ $row['match_type'] }}</td>
                                <td>{{ $row['campaign'] }}</td>
                                <td>{{ $money($row['cost']) }} €</td>
                                <td>{{ $row['clicks'] }}</td>
                                <td>{{ $row['conversions'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="gads-muted">Aucun keyword faible détecté.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="gads-card">
        <div class="gads-card-h">
            <h3>Plan Ads + SEO</h3>
            <span class="gads-pill">Structure, annonces, landing</span>
        </div>
        <div class="row g-0">
            <div class="col-xl-4">
                <div class="table-responsive">
                    <table class="table gads-table">
                        <thead><tr><th>Campagne</th><th>Groupes</th><th>But</th></tr></thead>
                        <tbody>
                            @foreach(($adsPlan['structure'] ?? []) as $item)
                                <tr>
                                    <td>{{ $item['campaign'] }}</td>
                                    <td>{{ $item['groups'] }}</td>
                                    <td>{{ $item['goal'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="table-responsive">
                    <table class="table gads-table">
                        <thead><tr><th>Intent</th><th>Landing</th><th>Note</th></tr></thead>
                        <tbody>
                            @foreach(($adsPlan['landing_pages'] ?? []) as $item)
                                <tr>
                                    <td>{{ $item['intent'] }}</td>
                                    <td><a href="{{ $item['url'] }}" target="_blank" rel="noopener">{{ $short($item['url'], 46) }}</a></td>
                                    <td>{{ $item['note'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="gads-card-body">
                    @foreach(($adsPlan['ad_copy'] ?? []) as $copy)
                        <div class="mb-2">
                            <strong>{{ $copy['lang'] }} · {{ $copy['ad_group'] }}</strong><br>
                            <span class="gads-muted">{{ implode(' · ', $copy['headlines']) }}</span><br>
                            <span>{{ implode(' ', $copy['descriptions']) }}</span>
                        </div>
                    @endforeach
                    <hr>
                    <ul class="gads-list">
                        @foreach(($adsPlan['seo'] ?? []) as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="gads-card">
        <div class="gads-card-h">
            <h3>Conversion actions</h3>
        </div>
        <div class="table-responsive">
            <table class="table gads-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Optimisation</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($conversionActions as $action)
                        @if(isset($action['error']))
                            <tr><td colspan="4"><div class="gads-error">{{ $action['error'] }}</div></td></tr>
                        @else
                            <tr>
                                <td>{{ $action['name'] }}</td>
                                <td>{{ $action['type'] }}</td>
                                <td>{{ $action['status'] }}</td>
                                <td>
                                    <span class="badge bg-{{ $action['primary'] ? 'success' : 'secondary' }}">
                                        {{ $action['primary'] ? 'Principale' : 'Secondaire' }}
                                    </span>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="gads-card">
        <div class="gads-card-h">
            <h3>Derniers leads WordPress</h3>
        </div>
        <div class="table-responsive">
            <table class="table gads-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Click ID</th>
                        <th>Source</th>
                        <th>Diagnostic</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLeads as $lead)
                        <tr>
                            <td>#{{ $lead->id }} / dossier {{ $lead->talep_id ?: '-' }}</td>
                            <td>{{ $lead->created_at }}</td>
                            <td>
                                <strong>{{ $lead->customer_name ?: '-' }}</strong><br>
                                <span class="gads-muted">{{ $lead->customer_phone ?: $lead->customer_email ?: '-' }}</span>
                            </td>
                            <td>
                                @if($lead->gclid || $lead->gbraid || $lead->wbraid)
                                    <span class="badge bg-success">présent</span>
                                @else
                                    <span class="badge bg-warning text-dark">absent</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $lead->utm_source ?: '-' }}</strong>
                                <span class="gads-muted">/ {{ $lead->utm_medium ?: '-' }}</span><br>
                                <span class="gads-muted">{{ $lead->utm_campaign ?: '-' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $statusBadge($lead->conversion_status_key) }} {{ $lead->conversion_status_tone === 'warning' ? 'text-dark' : '' }}">
                                    {{ $lead->conversion_status_label }}
                                </span>
                                <div class="gads-muted mt-1">{{ $lead->conversion_status_action }}</div>
                                @if($lead->sync_error || $lead->error_message)
                                    <div class="text-danger mt-1">{{ $short($lead->sync_error ?: $lead->error_message, 120) }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @if($recentLeads->isEmpty())
                        <tr><td colspan="6" class="gads-muted">Aucun lead pour le moment.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
