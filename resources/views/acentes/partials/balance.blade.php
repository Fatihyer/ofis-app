@hasanyrole('Admin|ofis')
@php
    $periodDebit = ($balanceSummary ?? collect())->sum('debit_total');
    $periodCredit = ($balanceSummary ?? collect())->sum('credit_total');
    $periodNet = ($balanceSummary ?? collect())->sum('net_total');
    $formatMoney = function ($amount) {
        return number_format((float) $amount, 2, ',', ' ');
    };
    $typeLabel = function ($type) {
        return match (class_basename($type)) {
            'Invoice' => 'Factures',
            'Transfer' => 'Transferts',
            'Hotel' => 'Hôtels',
            'Other' => 'Autres services',
            'Stock' => 'Stocks',
            'Offset' => 'Paiements / compensations',
            default => class_basename($type ?: 'Autre'),
        };
    };
@endphp

<style>
.ledger-page { color: #172033; }
.ledger-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
.ledger-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-bottom: 14px; }
.ledger-tile { border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; padding: 12px; box-shadow: 0 1px 2px rgba(15,23,42,.05); }
.ledger-tile small { display: block; color: #64748b; font-weight: 750; margin-bottom: 5px; }
.ledger-tile strong { font-size: 22px; line-height: 1; }
.ledger-tile.credit { border-color: #bbf7d0; background: #f0fdf4; }
.ledger-tile.debit { border-color: #fecaca; background: #fef2f2; }
.ledger-tile.net-positive { border-color: #bae6fd; background: #f0f9ff; }
.ledger-tile.net-negative { border-color: #fed7aa; background: #fff7ed; }
.ledger-card { border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; margin-bottom: 14px; overflow: hidden; }
.ledger-card-head { padding: 10px 12px; background: #f8fafc; border-bottom: 1px solid #e5e7eb; font-weight: 850; display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.ledger-table { margin-bottom: 0; font-size: 13px; }
.ledger-table th { background: #f1f5f9; color: #334155; font-weight: 800; white-space: nowrap; }
.ledger-table td { vertical-align: middle; }
.ledger-amount { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
.ledger-debit { color: #991b1b; font-weight: 800; }
.ledger-credit { color: #166534; font-weight: 800; }
.ledger-net { font-weight: 900; }
.ledger-link { font-weight: 800; }
.ledger-detail { color: #64748b; max-width: 360px; white-space: normal; }
.ledger-badge { border-radius: 999px; background: #eef2ff; color: #3730a3; padding: 3px 7px; font-size: 11px; font-weight: 850; }
@media (max-width: 767.98px) { .ledger-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } .ledger-table { font-size: 12px; } }
</style>

<div class="ledger-page">
    <div class="ledger-actions">
        <a class="btn btn-success btn-sm" href="{{ route('acente.excellist', $acente->id) }}?daterange={{ request('daterange') }}"><i class="fas fa-file-excel"></i> Export Excel</a>
        <button id="exportButton" class="btn btn-outline-primary btn-sm" type="button"><i class="fas fa-table"></i> Export tableau</button>
    </div>

    <div class="ledger-summary">
        <div class="ledger-tile debit"><small>Total débit</small><strong>{{ $formatMoney($periodDebit) }}</strong></div>
        <div class="ledger-tile credit"><small>Total crédit</small><strong>{{ $formatMoney($periodCredit) }}</strong></div>
        <div class="ledger-tile {{ $periodNet >= 0 ? 'net-positive' : 'net-negative' }}"><small>Solde net période</small><strong>{{ $formatMoney(abs($periodNet)) }}</strong><div class="text-muted small">{{ $periodNet >= 0 ? 'Créditeur' : 'Débiteur' }}</div></div>
        <div class="ledger-tile"><small>Mouvements</small><strong>{{ $harekets->total() }}</strong></div>
    </div>

    <div class="ledger-card">
        <div class="ledger-card-head">
            <span>Soldes par devise</span>
            <span class="text-muted small">Période sélectionnée</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm ledger-table">
                <thead><tr><th>Devise</th><th class="ledger-amount">Débit</th><th class="ledger-amount">Crédit</th><th class="ledger-amount">Solde</th><th></th></tr></thead>
                <tbody>
                    @forelse(($balanceSummary ?? collect()) as $row)
                        <tr>
                            <td><strong>{{ optional($row->kur)->short_name ?: optional($row->kur)->name ?: $row->kur_id }}</strong></td>
                            <td class="ledger-amount ledger-debit">{{ $formatMoney($row->debit_total) }}</td>
                            <td class="ledger-amount ledger-credit">{{ $formatMoney($row->credit_total) }}</td>
                            <td class="ledger-amount ledger-net {{ $row->net_total >= 0 ? 'ledger-credit' : 'ledger-debit' }}">{{ $formatMoney(abs($row->net_total)) }} {{ $row->net_total >= 0 ? 'Créditeur' : 'Débiteur' }}</td>
                            <td class="text-end"><a href="{{ route('offsets.create') }}?rakam={{ abs($row->net_total) }}&acente={{ $acente->id }}" title="Créer une compensation"><i class="fas fa-arrows-alt-h"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Aucun mouvement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="ledger-card">
        <div class="ledger-card-head"><span>Répartition par type</span></div>
        <div class="table-responsive">
            <table class="table table-sm ledger-table">
                <thead><tr><th>Type</th><th class="ledger-amount">Nombre</th><th class="ledger-amount">Débit</th><th class="ledger-amount">Crédit</th></tr></thead>
                <tbody>
                    @forelse(($balanceTypeSummary ?? collect()) as $row)
                        <tr>
                            <td>{{ $typeLabel($row->type_label) }}</td>
                            <td class="ledger-amount">{{ $row->movement_count }}</td>
                            <td class="ledger-amount ledger-debit">{{ $formatMoney($row->debit_total) }}</td>
                            <td class="ledger-amount ledger-credit">{{ $formatMoney($row->credit_total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Aucune donnée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="ledger-card">
        <div class="ledger-card-head">
            <span>Extrait de compte</span>
            <span class="text-muted small">{{ $harekets->firstItem() ?? 0 }}-{{ $harekets->lastItem() ?? 0 }} / {{ $harekets->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover ledger-table" id="AcentesTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Dossier</th>
                        <th>Type</th>
                        <th>Libellé</th>
                        <th>Paiement</th>
                        <th class="ledger-amount">Débit</th>
                        <th class="ledger-amount">Crédit</th>
                        <th class="ledger-amount">Solde page</th>
                        <th>Pièces</th>
                    </tr>
                </thead>
                <tbody>
                    @php $pageBalance = 0; @endphp
                    @forelse ($harekets as $hareket)
                        @php
                            $debit = (int) $hareket->ab === 1 ? (float) $hareket->amount : 0;
                            $credit = (int) $hareket->ab === 2 ? (float) $hareket->amount : 0;
                            $pageBalance += ($credit - $debit);
                            $typeName = $typeLabel($hareket->hareketable_type);
                            $paymentLabel = optional($hareket->payment)->name;
                            $paymentDetail = null;
                            if (!$paymentLabel && class_basename($hareket->hareketable_type) === 'Offset' && $hareket->hareketable) {
                                $offset = $hareket->hareketable;
                                $counterparty = ((int) $offset->a_acente_id === (int) $hareket->acente_id)
                                    ? optional($offset->borclu)->name
                                    : optional($offset->alacakli)->name;
                                $paymentLabel = $counterparty ? 'Compensation via ' . $counterparty : 'Compensation';
                                $paymentDetail = $offset->aciklama;
                            }
                        @endphp
                        <tr>
                            <td>{{ date('d/m/Y H:i', strtotime($hareket->tarih)) }}</td>
                            <td>
                                @if ($hareket->post)
                                    <a class="ledger-link" href="{{ route('posts.show', $hareket->post->id) }}">FP{{ $hareket->post->id }}</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td><span class="ledger-badge">{{ $typeName }}</span></td>
                            <td class="ledger-detail">
                                @if(class_basename($hareket->hareketable_type) === 'Invoice' && $hareket->hareketable)
                                    Facture <a href="{{ route('invoices.edit', $hareket->hareketable->id) }}">#{{ $hareket->hareketable->id }}</a> {{ $hareket->hareketable->resmi }}
                                @elseif(class_basename($hareket->hareketable_type) === 'Offset' && $hareket->hareketable)
                                    Compensation <a href="{{ route('offsets.edit', $hareket->hareketable->id) }}">#{{ $hareket->hareketable->id }}</a>
                                @else
                                    {{ $hareket->aciklama ?: '-' }}
                                @endif
                                @if($hareket->invoiceno)
                                    <div class="text-muted small">Facture fournisseur: {{ $hareket->invoiceno }}</div>
                                @endif
                            </td>
                            <td>
                                {{ $paymentLabel ?: '-' }}
                                @if($paymentDetail)
                                    <div class="text-muted small">{{ $paymentDetail }}</div>
                                @endif
                            </td>
                            <td class="ledger-amount ledger-debit">{{ $debit ? $formatMoney($debit) : '-' }}</td>
                            <td class="ledger-amount ledger-credit">{{ $credit ? $formatMoney($credit) : '-' }}</td>
                            <td class="ledger-amount ledger-net {{ $pageBalance >= 0 ? 'ledger-credit' : 'ledger-debit' }}">{{ $formatMoney(abs($pageBalance)) }}</td>
                            <td>
                                @if ($hareket->files->count() > 0)
                                    @foreach($hareket->files as $file)
                                        <a href="{{ asset('storage/'.$file->path) }}" target="_blank" class="text-decoration-none me-1"><i class="fas fa-paperclip text-success"></i></a>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">Aucun mouvement pour cette période.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {!! $harekets->appends(request()->except('page'))->links('vendor/pagination/bootstrap-4') !!}
</div>
@endhasanyrole
