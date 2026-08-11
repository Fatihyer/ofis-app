@extends('layouts.app')

@section('style')
<style>
.invoice-show{background:#f8fafc;min-height:calc(100vh - 90px);padding:14px}.is-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:14px}.is-title h1{margin:0;font-size:24px;font-weight:850;color:#0f172a}.is-title small{color:#64748b;font-weight:700}.is-actions{display:flex;gap:6px;flex-wrap:wrap}.is-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 20px rgba(15,23,42,.06);margin-bottom:14px;overflow:hidden}.is-card-h{padding:12px 14px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;gap:10px;align-items:center}.is-card-h strong{color:#0f172a}.is-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.is-field{border:1px solid #e5e7eb;border-radius:8px;padding:10px;background:#fff}.is-field span{display:block;font-size:11px;font-weight:850;text-transform:uppercase;color:#64748b}.is-field strong{display:block;color:#111827;margin-top:3px}.is-address{white-space:pre-line;line-height:1.45}.is-table th{font-size:12px;text-transform:uppercase;color:#475569}.is-money{text-align:right;white-space:nowrap}.is-total{font-size:20px;font-weight:900;color:#0f172a}@media(max-width:992px){.is-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.is-head{display:block}.is-actions{margin-top:10px}}@media(max-width:576px){.invoice-show{padding:8px}.is-grid{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
@php
    $clientName = $detail['tittle'] ?? optional($invoice->acente)->name;
    $addressParts = array_filter([$detail['address'] ?? null, $detail['postal'] ?? null, $detail['city'] ?? null, $detail['country_name'] ?? null]);
@endphp
<div class="invoice-show">
    <div class="is-head">
        <div class="is-title">
            <h1>Facture #{{ $invoice->resmi ?: $invoice->id }}</h1>
            <small>Proforma {{ $invoice->id }} · Dossier #{{ $invoice->post_id }}</small>
        </div>
        <div class="is-actions">
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('invoices.index', ['sirket_id' => $invoice->sirket_id]) }}">Liste</a>
            <a class="btn btn-sm btn-primary" href="{{ route('invoices.edit', $invoice->id) }}">Modifier</a>
            @if($invoice->post_id)<a class="btn btn-sm btn-outline-info" href="{{ route('posts.show', $invoice->post_id) }}">Dossier</a>@endif
            @if($invoice->acente_id)<a class="btn btn-sm btn-outline-info" href="{{ route('acentes.show', $invoice->acente_id) }}">Client</a>@endif
            <a class="btn btn-sm btn-success" target="_blank" href="{{ route('invoicetopdf', [$invoice->id, 'html']) }}">Voir facture</a>
            <a class="btn btn-sm btn-danger" target="_blank" href="{{ route('invoicetopdf', [$invoice->id, 'pdf']) }}">PDF</a>
            @role('Superadmin')
                @if($invoice->pennylane_customer_invoice_id)
                    <span class="btn btn-sm btn-outline-success disabled">Pennylane #{{ $invoice->pennylane_customer_invoice_id }}</span>
                @else
                    <form class="d-inline" action="{{ route('invoices.pennylane.draft', $invoice->id) }}" method="POST" onsubmit="return confirm('Créer un brouillon Pennylane pour cette facture ?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-dark">Pennylane brouillon</button>
                    </form>
                @endif
                <form class="deleteinvoice d-inline" action="{{ route('invoices.destroy', $invoice->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                </form>
            @endrole
        </div>
    </div>

    <div class="is-card">
        <div class="is-card-h"><strong>Informations</strong><span class="text-muted small">{{ optional($invoice->sirket)->name }}</span></div>
        <div class="p-3 is-grid">
            <div class="is-field"><span>Société</span><strong>{{ optional($invoice->sirket)->name ?: '-' }}</strong></div>
            <div class="is-field"><span>Date</span><strong>{{ $invoice->tarih ? date('d/m/Y', strtotime($invoice->tarih)) : '-' }}</strong></div>
            <div class="is-field"><span>N° facture</span><strong>{{ $invoice->resmi ?: '-' }}</strong></div>
            <div class="is-field"><span>Avoir</span><strong>{{ $invoice->avoir ?: '-' }}</strong></div>
            <div class="is-field"><span>Client interne</span><strong>{{ optional($invoice->acente)->name ?: '-' }}</strong></div>
            <div class="is-field"><span>Compte</span><strong>{{ optional($invoice->account)->name ?: '-' }}</strong></div>
            <div class="is-field"><span>Devise</span><strong>{{ optional($invoice->kur)->short_name ?: '-' }}</strong></div>
            <div class="is-field"><span>Total</span><strong class="is-total">{{ number_format((float)$invoice->amount, 2, ',', ' ') }} {{ optional($invoice->kur)->short_name ?: '€' }}</strong></div>
            <div class="is-field"><span>Pennylane</span><strong>{{ $invoice->pennylane_customer_invoice_id ? '#'.$invoice->pennylane_customer_invoice_id.' · '.($invoice->pennylane_customer_invoice_status ?: 'draft') : '-' }}</strong></div>
        </div>
    </div>

    <div class="is-card">
        <div class="is-card-h"><strong>Client facturé</strong></div>
        <div class="p-3">
            <h5 class="mb-2">{{ $clientName ?: '-' }}</h5>
            <div class="is-address text-muted">{{ implode("\n", $addressParts) ?: '-' }}</div>
            @if(!empty($detail['vd']) || !empty($detail['vdno']))
                <div class="mt-2"><strong>TVA / Taxe:</strong> {{ $detail['vd'] ?? '' }} {{ $detail['vdno'] ?? '' }}</div>
            @endif
            @if(!empty($detail['not']))
                <div class="alert alert-light border mt-3 mb-0">{{ $detail['not'] }}</div>
            @endif
        </div>
    </div>

    <div class="is-card">
        <div class="is-card-h"><strong>Lignes de facture</strong></div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 is-table">
                <thead class="thead-light"><tr><th>Description</th><th>TVA</th><th class="is-money">Montant</th></tr></thead>
                <tbody>
                @forelse($invoice->invoicedetail as $line)
                    <tr>
                        <td>{{ $line->comments }}</td>
                        <td>{{ optional($line->kdv)->name ?? optional($line->kdv)->value ?? '-' }}</td>
                        <td class="is-money">{{ number_format((float)$line->amount, 2, ',', ' ') }} {{ optional($invoice->kur)->short_name ?: '€' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">Aucune ligne.</td></tr>
                @endforelse
                </tbody>
                <tfoot><tr><th colspan="2" class="text-right">Total</th><th class="is-money">{{ number_format((float)$invoice->amount, 2, ',', ' ') }} {{ optional($invoice->kur)->short_name ?: '€' }}</th></tr></tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
