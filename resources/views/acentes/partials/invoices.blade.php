@hasanyrole('Admin|ofis')
<style>
    .invoice-penny-badge{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:4px 9px;font-size:12px;font-weight:700}
    .invoice-penny-ok{background:#e8f7ef;color:#166534}
    .invoice-penny-warning{background:#fff7df;color:#92400e}
    .invoice-penny-missing{background:#fdecec;color:#991b1b}
    .invoice-penny-card{min-width:190px;line-height:1.35}
    .invoice-penny-meta{font-size:12px;color:#6b7280;margin-top:4px}
    .invoice-action-stack{display:flex;gap:6px;flex-wrap:wrap}
</style>
<a href="{{ route('groupinvoices.create', 'acente=' . $acente->id) }}" class="btn btn-success">Nouvelle facture groupée</a>
<div class="row">
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th scope="col">Dossier</th>
                    <th scope="col">Facture Laravel</th>
                    <th>Facture groupée</th>
                    <th scope="col">@lang('app.date')</th>
                    <th scope="col">@lang('app.exchange')</th>
                    <th scope="col">@lang('app.amount')</th>
                    <th scope="col">@lang('app.legal')</th>
                    <th>Pennylane</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $itop = 0; ?>
                @foreach ($invoices as $invoice)
                    @php
                        $pennyMatch = ($pennylaneInvoiceMatches ?? [])[$invoice->id] ?? null;
                        $penny = $pennyMatch['pennylane'] ?? null;
                        $pennyStatus = $pennyMatch['status'] ?? 'not_checked';
                        $pennyClass = $pennyStatus === 'ok' ? 'invoice-penny-ok' : ($pennyStatus === 'missing' ? 'invoice-penny-missing' : 'invoice-penny-warning');
                        $pennyLabel = [
                            'ok' => 'Présente',
                            'missing' => 'Absente',
                            'amount_mismatch' => 'Montant différent',
                            'date_mismatch' => 'Date différente',
                            'not_checked' => 'Non vérifiée',
                        ][$pennyStatus] ?? 'À contrôler';
                        $pennyAccount = $pennyMatch['account'] ?? ($invoice->sirket_id == 3 ? 'francevia' : 'parisvia');
                    @endphp
                    <tr>
                        <th scope="row"><a href="{{ route('posts.show', $invoice->post->id) }}"><b>FP{{ $invoice->post->id }} </b></a></th>
                        <?php $detail = unserialize($invoice->detail); ?>
                        <td>{{ $invoice->id }}</td>
                        <td><a href="{{ route('groupinvoices.edit', $invoice->groupinvoice[0]->id ?? '') }}">{{ $invoice->groupinvoice[0]->id ?? '' }}</a></td>
                        <td>{{ date('d-m-Y', strtotime($invoice->tarih)) }}</td>
                        <td>{{ $invoice->kur->name }}</td>
                        <td>{{ $invoice->amount }} {{ $invoice->kur->short_name }}</td>
                        <td>{{ $invoice->resmi }}</td>
                        <td>
                            <div class="invoice-penny-card">
                                <span class="invoice-penny-badge {{ $pennyClass }}">{{ $pennyLabel }}</span>
                                @if($penny)
                                    <div class="invoice-penny-meta">
                                        {{ $penny['invoice_number'] ?? 'Sans numéro' }} ·
                                        {{ $penny['date'] ?? '-' }} ·
                                        {{ number_format((float) ($penny['amount'] ?? 0), 2, ',', ' ') }} €
                                        @if(isset($penny['status']))
                                            <br>Statut: {{ $penny['status'] }}
                                        @endif
                                        @if(abs((float) ($pennyMatch['amount_diff'] ?? 0)) > 0.01)
                                            <br>Écart: {{ number_format((float) $pennyMatch['amount_diff'], 2, ',', ' ') }} €
                                        @endif
                                    </div>
                                @else
                                    <div class="invoice-penny-meta">Aucune facture Pennylane trouvée avec ce numéro.</div>
                                @endif
                                <a class="btn btn-outline-secondary btn-sm mt-1" href="{{ route('pennylane.index', ['account' => $pennyAccount, 'resource' => 'customer_invoices', 'limit' => 100]) }}">Voir Pennylane</a>
                                <a class="btn btn-outline-primary btn-sm mt-1" href="{{ route('invoices.pennylane.compare', ['sirket_id' => $invoice->sirket_id ?: 2, 'start_date' => date('Y-m-d', strtotime($invoice->tarih)), 'end_date' => date('Y-m-d', strtotime($invoice->tarih))]) }}">Contrôler</a>
                            </div>
                        </td>
                        <td>
                            <div class="invoice-action-stack">
                                <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn btn-primary btn-sm">Modifier</a>
                                <a href="{{route('invoicetopdf',[$invoice->id,'pdf'] )}}" class="btn btn-danger btn-sm">PDF</a>
                            </div>
                        </td>
                    </tr>
                    <?php $itop += $invoice->amount; ?>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5"></td>
                    <td>{{ $itop }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="panel-heading">Page {{ $invoices->currentPage() }} of {{ $invoices->lastPage() }}</div>
    <div class="text-center">
        {!! $invoices->links() !!}
    </div>
</div>
@endhasrole
