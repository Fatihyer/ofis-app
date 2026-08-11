@extends('layouts.app')

@section('style')
<style>
    .subcontract-page{background:#f8fafc;min-height:calc(100vh - 90px);padding:18px}.sub-head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;margin-bottom:14px}.sub-head h1{font-size:25px;font-weight:850;color:#0f172a;margin:0}.sub-muted{color:#64748b}.sub-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 22px rgba(15,23,42,.06);margin-bottom:14px;overflow:hidden}.sub-card-h{display:flex;justify-content:space-between;gap:10px;align-items:center;padding:12px 14px;border-bottom:1px solid #e5e7eb}.sub-filters{display:flex;flex-wrap:wrap;gap:8px;align-items:end}.sub-filters label{font-size:12px;font-weight:850;color:#475569;margin-bottom:3px}.sub-stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:14px}.sub-stat{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:12px}.sub-stat span{display:block;color:#64748b;font-size:12px;font-weight:850;text-transform:uppercase}.sub-stat strong{display:block;color:#0f172a;font-size:24px;line-height:1.1;margin-top:6px}.sub-table th{font-size:11px;text-transform:uppercase;color:#475569;white-space:nowrap}.sub-table td{vertical-align:top}.sub-money{font-weight:900;white-space:nowrap}.sub-badge{display:inline-flex;align-items:center;border-radius:999px;padding:4px 9px;font-size:11px;font-weight:850}.sub-ok{background:#dcfce7;color:#166534}.sub-warn{background:#fef3c7;color:#92400e}.sub-danger{background:#fee2e2;color:#991b1b}.sub-info{background:#dbeafe;color:#1d4ed8}.sub-provider-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:10px}.sub-provider{border:1px solid #e5e7eb;border-radius:8px;padding:10px;background:#fff}.sub-provider strong{display:block;color:#0f172a}.sub-provider .amount{font-size:18px;font-weight:900}.sub-links a{display:block;font-weight:800}.sub-note{font-size:12px;color:#64748b}.sub-actions{display:flex;gap:8px;flex-wrap:wrap}@media(max-width:768px){.subcontract-page{padding:10px}.sub-head{display:block}.sub-actions{margin-top:10px}.sub-filters .form-control{width:100%}.sub-card-h{display:block}}
</style>
@endsection

@section('content')
@php
    $money = fn($amount) => number_format((float) $amount, 2, ',', ' ') . ' EUR';
@endphp
<div class="subcontract-page">
    <div class="sub-head">
        <div>
            <h1>Véhicules sous-traités</h1>
            <div class="sub-muted">Suivi des véhicules extérieurs, fournisseurs et coûts saisis dans les transferts.</div>
        </div>
        <div class="sub-actions">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('balanceprovider') }}">Balance prestataire</a>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('invoices.provider-pennylane.compare') }}">Contrôle fournisseurs Pennylane</a>
        </div>
    </div>

    <div class="sub-stat-grid">
        <div class="sub-stat"><span>Transferts</span><strong>{{ $stats['total'] }}</strong></div>
        <div class="sub-stat"><span>Coût fournisseur</span><strong>{{ $money($stats['amount']) }}</strong></div>
        <div class="sub-stat"><span>Fournisseurs</span><strong>{{ $stats['providers'] }}</strong></div>
        <div class="sub-stat"><span>Prix manquant</span><strong class="{{ $stats['missing_price'] ? 'text-danger' : 'text-success' }}">{{ $stats['missing_price'] }}</strong></div>
        <div class="sub-stat"><span>Fournisseur manquant</span><strong class="{{ $stats['missing_provider'] ? 'text-danger' : 'text-success' }}">{{ $stats['missing_provider'] }}</strong></div>
    </div>

    <div class="sub-card">
        <div class="sub-card-h"><strong>Filtres</strong><span class="sub-muted">{{ $start->format('d/m/Y') }} - {{ $end->format('d/m/Y') }}</span></div>
        <div class="p-3">
            <form method="GET" action="{{ route('vehicules.subcontracted') }}" class="sub-filters">
                <div><label>Du</label><input type="date" name="start_date" value="{{ $start->format('Y-m-d') }}" class="form-control form-control-sm"></div>
                <div><label>Au</label><input type="date" name="end_date" value="{{ $end->format('Y-m-d') }}" class="form-control form-control-sm"></div>
                <div>
                    <label>Fournisseur</label>
                    <select name="provider_id" class="form-control form-control-sm">
                        <option value="0">Tous</option>
                        @foreach($providerOptions as $id => $name)
                            <option value="{{ $id }}" {{ (int)$providerId === (int)$id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Statut</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Tous</option>
                        <option value="ready" {{ $status === 'ready' ? 'selected' : '' }}>Complet</option>
                        <option value="missing_price" {{ $status === 'missing_price' ? 'selected' : '' }}>Prix manquant</option>
                        <option value="missing_provider" {{ $status === 'missing_provider' ? 'selected' : '' }}>Fournisseur manquant</option>
                    </select>
                </div>
                <div><label>Recherche</label><input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Dossier, transfert, plaque..."></div>
                <button class="btn btn-primary btn-sm" type="submit">Filtrer</button>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('vehicules.subcontracted') }}">Réinitialiser</a>
            </form>
        </div>
    </div>

    @if($providerSummaries->isNotEmpty())
        <div class="sub-card">
            <div class="sub-card-h"><strong>Résumé par fournisseur</strong><span class="sub-muted">{{ $providerSummaries->count() }} fournisseur(s)</span></div>
            <div class="p-3 sub-provider-grid">
                @foreach($providerSummaries as $provider)
                    <div class="sub-provider">
                        <strong>{{ $provider->name }}</strong>
                        <div class="amount">{{ $money($provider->amount) }}</div>
                        <div class="sub-note">{{ $provider->count }} transfert(s) · {{ $provider->missing_price }} prix manquant(s)</div>
                        @if($provider->id > 0)
                            <a href="{{ route('acentes.show', ['acente' => $provider->id, 'src' => 'balance', 'start_date' => $start->format('Y-m-d'), 'end_date' => $end->format('Y-m-d')]) }}">Voir la balance</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="sub-card">
        <div class="sub-card-h"><strong>Détail des transferts</strong><span class="sub-muted">{{ $transfers->total() }} ligne(s)</span></div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 sub-table">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Dossier / transfert</th>
                        <th>Client</th>
                        <th>Fournisseur véhicule</th>
                        <th>Véhicule</th>
                        <th>Chauffeur</th>
                        <th>Trajet</th>
                        <th>Prix fournisseur</th>
                        <th>Statut</th>
                        <th>Mouvement</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $transfer)
                        @php
                            $missingProvider = !$transfer->vehicle_provider_acente_id;
                            $missingPrice = (float) $transfer->external_vehicle_price <= 0;
                            $isReady = !$missingProvider && !$missingPrice;
                        @endphp
                        <tr>
                            <td><strong>{{ optional($transfer->start_date)->format('d/m/Y') }}</strong><div class="sub-note">{{ optional($transfer->start_date)->format('H:i') }} - {{ optional($transfer->end_date)->format('H:i') }}</div></td>
                            <td class="sub-links">
                                <a href="{{ route('posts.show', $transfer->post_id) }}">Dossier #{{ $transfer->post_id }}</a>
                                <a href="{{ route('transfers.show', $transfer->id) }}">Transfert #{{ $transfer->id }}</a>
                            </td>
                            <td>{{ optional(optional($transfer->post)->acente)->name ?: '-' }}</td>
                            <td>
                                @if($transfer->externalVehicleProvider)
                                    <a href="{{ route('acentes.show', ['acente' => $transfer->vehicle_provider_acente_id, 'src' => 'balance']) }}"><strong>{{ $transfer->externalVehicleProvider->name }}</strong></a>
                                @else
                                    <span class="sub-badge sub-danger">À renseigner</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ optional($transfer->vehicule)->name ?: 'Véhicule extérieur / ---' }}</div>
                                @if($transfer->external_vehicle_note)<div class="sub-note">{{ $transfer->external_vehicle_note }}</div>@endif
                            </td>
                            <td>{{ optional($transfer->driver)->name ?: '-' }}</td>
                            <td><strong>{{ $transfer->from ?: '-' }}</strong><div class="sub-note">{{ $transfer->target ?: '-' }}</div><div class="sub-note">{{ optional($transfer->servicetype)->name ?: '-' }}</div></td>
                            <td class="sub-money {{ $missingPrice ? 'text-danger' : '' }}">{{ $missingPrice ? 'À saisir' : $money($transfer->external_vehicle_price) }}</td>
                            <td>
                                @if($isReady)
                                    <span class="sub-badge sub-ok">Complet</span>
                                @else
                                    @if($missingProvider)<span class="sub-badge sub-danger mb-1">Fournisseur manquant</span>@endif
                                    @if($missingPrice)<span class="sub-badge sub-warn">Prix manquant</span>@endif
                                @endif
                                <div class="sub-note">{{ optional($transfer->status)->name ?: '-' }}</div>
                            </td>
                            <td>
                                @php($movement = $movementMap[$transfer->id] ?? null)
                                @if($movement)
                                    <span class="sub-badge sub-info mb-1">Mouvement #{{ $movement->id }}</span>
                                    <div class="sub-note">{{ $money($movement->amount) }}</div>
                                @else
                                    <span class="sub-badge sub-warn mb-1">Non créé</span>
                                @endif
                                @if($isReady)
                                    <form method="POST" action="{{ route('vehicules.subcontracted.movement', $transfer->id) }}" class="mt-1">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary btn-sm">{{ $movement ? 'Mettre à jour' : 'Créer mouvement' }}</button>
                                    </form>
                                @else
                                    <div class="sub-note">Compléter fournisseur et prix.</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center sub-muted py-4">Aucun véhicule sous-traité sur cette période.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-2">{{ $transfers->links() }}</div>
    </div>
</div>
@endsection
