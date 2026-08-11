@extends('layouts.app')

@section('style')
<style>
.pc-page{background:#f8fafc;min-height:calc(100vh - 90px);padding:16px}.pc-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:14px}.pc-head h1{font-size:24px;font-weight:850;color:#0f172a;margin:0}.pc-muted{color:#64748b}.pc-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 22px rgba(15,23,42,.06);margin-bottom:14px;overflow:hidden}.pc-card-h{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:12px 14px;border-bottom:1px solid #e5e7eb}.pc-filters{display:flex;gap:8px;flex-wrap:wrap;align-items:end}.pc-filters label{font-size:12px;font-weight:800;color:#475569;margin-bottom:3px}.pc-stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:14px}.pc-stat{display:block;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:12px;text-decoration:none;color:#0f172a}.pc-stat strong{display:block;font-size:24px;line-height:1}.pc-stat span{font-size:12px;color:#64748b;font-weight:750}.pc-table th{font-size:11px;text-transform:uppercase;color:#475569;white-space:nowrap}.pc-table td{vertical-align:top}.pc-badge{display:inline-flex;align-items:center;border-radius:999px;padding:4px 9px;font-size:11px;font-weight:850}.pc-ok{background:#dcfce7;color:#166534}.pc-missing{background:#ffedd5;color:#9a3412}.pc-extra{background:#dbeafe;color:#1d4ed8}.pc-warn{background:#fef3c7;color:#92400e}.pc-danger{background:#fee2e2;color:#991b1b}.pc-neutral{background:#f1f5f9;color:#475569}.pc-money{font-weight:850;white-space:nowrap}.pc-links a{display:block;font-weight:800}.pc-small{font-size:12px;color:#64748b}.pc-actions{display:flex;gap:8px;flex-wrap:wrap}.pc-alert{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:8px;padding:10px 12px;margin-bottom:14px;font-weight:700}@media(max-width:768px){.pc-page{padding:10px}.pc-head{display:block}.pc-filters .form-control{width:100%}.pc-card-h{display:block}.pc-actions{margin-top:8px}}
</style>
@endsection

@section('content')
@php
    $statusMeta = [
        'ok' => ['OK', 'pc-ok'],
        'missing_pennylane' => ['Absent Pennylane', 'pc-missing'],
        'extra_pennylane' => ['Absent Laravel', 'pc-extra'],
        'amount_mismatch' => ['Montant different', 'pc-danger'],
        'date_mismatch' => ['Date differente', 'pc-warn'],
        'unmapped_supplier' => ['Fournisseur non jumele', 'pc-warn'],
        'company_missing' => ['Societe manquante', 'pc-neutral'],
    ];
    $fmt = fn($amount) => number_format((float) $amount, 2, ',', ' ') . ' EUR';
@endphp

<div class="pc-page">
    <div class="pc-head">
        <div>
            <h1>Contrôle Pennylane fournisseurs</h1>
            <div class="pc-muted">Comparaison entre les factures fournisseurs Pennylane et les mouvements prestataires Laravel.</div>
        </div>
        <div class="pc-actions">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('pennylane.index', ['account' => $accountKey, 'resource' => 'suppliers', 'limit' => 100]) }}">Jumeler les fournisseurs</a>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('invoices.pennylane.compare', ['sirket_id' => $selectedSirketId, 'start_date' => $startDate, 'end_date' => $endDate]) }}">Contrôle factures clients</a>
        </div>
    </div>

    @if(!empty($unmappedSupplierIds))
        <div class="pc-alert">{{ count($unmappedSupplierIds) }} fournisseur(s) Pennylane ne sont pas encore jumelés. Ouvrez le jumelage fournisseurs avant de valider les écarts.</div>
    @endif

    <div class="pc-card">
        <div class="pc-card-h">
            <strong>Filtres</strong>
            <span class="pc-muted">{{ $accountKey }}</span>
        </div>
        <div class="p-3">
            <form method="GET" action="{{ route('invoices.provider-pennylane.compare') }}" class="pc-filters">
                <div>
                    <label>Société</label>
                    <select name="sirket_id" class="form-control form-control-sm">
                        @foreach($sirkets as $id => $name)
                            <option value="{{ $id }}" {{ (int)$selectedSirketId === (int)$id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Début</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="form-control form-control-sm">
                </div>
                <div>
                    <label>Fin</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="form-control form-control-sm">
                </div>
                <div>
                    <label>Statut</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Tous</option>
                        @foreach($statusMeta as $key => $meta)
                            <option value="{{ $key }}" {{ $statusFilter === $key ? 'selected' : '' }}>{{ $meta[0] }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary btn-sm" type="submit">Filtrer</button>
            </form>
        </div>
    </div>

    <div class="pc-stat-grid">
        @foreach($statusMeta as $key => $meta)
            <a class="pc-stat" href="{{ route('invoices.provider-pennylane.compare', ['sirket_id'=>$selectedSirketId,'start_date'=>$startDate,'end_date'=>$endDate,'status'=>$key]) }}"><strong>{{ $stats[$key] ?? 0 }}</strong><span>{{ $meta[0] }}</span></a>
        @endforeach
    </div>

    <div class="pc-card">
        <div class="pc-card-h">
            <strong>Résultats</strong>
            <span class="pc-muted">{{ count($rows) }} ligne(s) · Laravel: {{ $laravelMovements->count() }} · Pennylane: {{ count($pennylaneInvoices) }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 pc-table">
                <thead class="table-light">
                    <tr>
                        <th>Contrôle</th>
                        <th>N° facture</th>
                        <th>Laravel</th>
                        <th>Pennylane</th>
                        <th>Écart</th>
                        <th>Liens</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $meta = $statusMeta[$row['status']] ?? ['Statut', 'pc-neutral'];
                            $laravel = $row['laravel'];
                            $penny = $row['pennylane'];
                            $pennyAmount = $penny ? ($penny['currency_amount'] ?? $penny['amount'] ?? 0) : null;
                        @endphp
                        <tr>
                            <td><span class="pc-badge {{ $meta[1] }}">{{ $meta[0] }}</span></td>
                            <td>
                                <strong>{{ $row['number'] ?: '-' }}</strong>
                                @if($row['matched_number'] && $row['matched_number'] !== $row['number'])
                                    <div class="pc-small">clé: {{ $row['matched_number'] }}</div>
                                @endif
                            </td>
                            <td>
                                @if($laravel)
                                    <div><strong>{{ optional($laravel->acente)->name ?: 'Prestataire #' . $laravel->acente_id }}</strong></div>
                                    <div class="pc-small">Date: {{ substr((string)$laravel->tarih, 0, 10) }}</div>
                                    <div class="pc-money">{{ $fmt($laravel->amount) }}</div>
                                    @if(!$laravel->sirket_id)<div class="pc-small">Société non renseignée</div>@endif
                                    @if($laravel->post)<div class="pc-small">Dossier #{{ $laravel->post_id }}</div>@endif
                                @else
                                    <span class="pc-muted">Absent</span>
                                @endif
                            </td>
                            <td>
                                @if($penny)
                                    <div><strong>{{ $penny['label'] ?? $penny['filename'] ?? 'Facture Pennylane' }}</strong></div>
                                    <div class="pc-small">Date: {{ $penny['date'] ?? '-' }} · ID {{ $penny['id'] ?? '-' }}</div>
                                    <div class="pc-small">Fournisseur Pennylane: {{ $row['supplier_id'] ?? '-' }}</div>
                                    <div class="pc-money">{{ $fmt($pennyAmount) }}</div>
                                    <div class="pc-small">{{ $penny['payment_status'] ?? '-' }} · {{ !empty($penny['reconciled']) ? 'rapprochée' : 'non rapprochée' }}</div>
                                @else
                                    <span class="pc-muted">Absent</span>
                                @endif
                            </td>
                            <td>
                                @if($row['amount_diff'] !== null)
                                    <div class="pc-money">{{ $fmt($row['amount_diff']) }}</div>
                                @else
                                    <span class="pc-muted">-</span>
                                @endif
                                @if($row['date_diff'])
                                    <div class="pc-small">Date différente</div>
                                @endif
                            </td>
                            <td class="pc-links">
                                @if($laravel && $laravel->post)
                                    <a href="{{ route('posts.show', $laravel->post_id) }}">Ouvrir le dossier #{{ $laravel->post_id }}</a>
                                @endif
                                @if($laravel && $laravel->acente)
                                    <a href="{{ route('acentes.show', $laravel->acente_id) }}">Balance prestataire</a>
                                @endif
                                @if($penny && !empty($penny['public_file_url']))
                                    <a href="{{ $penny['public_file_url'] }}" target="_blank" rel="noopener">PDF Pennylane</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center pc-muted py-4">Aucun résultat pour cette période.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
