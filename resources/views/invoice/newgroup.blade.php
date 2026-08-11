@extends('layouts.app')

@section('title', '| Nouvelle facture groupée')

@section('style')
<style>
.group-invoice-page{max-width:980px;margin:0 auto}
.group-invoice-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 20px rgba(15,23,42,.06);overflow:hidden}
.group-invoice-head{padding:14px 16px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;gap:10px;align-items:center}
.group-invoice-head h4{margin:0;font-weight:800;color:#111827}
.group-invoice-body{padding:16px}
.group-invoice-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.group-invoice-list{border:1px solid #e5e7eb;border-radius:8px;max-height:460px;overflow:auto;background:#f8fafc}
.group-invoice-row{display:grid;grid-template-columns:28px 1fr auto;gap:10px;align-items:center;margin:0;padding:10px 12px;border-bottom:1px solid #e5e7eb;cursor:pointer;background:#fff}
.group-invoice-row:last-child{border-bottom:0}
.group-invoice-row:hover{background:#f1f5f9}
.group-invoice-row input{width:18px;height:18px}
.group-invoice-main strong{display:block;color:#111827}
.group-invoice-main small{display:block;color:#64748b;font-weight:600}
.group-invoice-amount{font-weight:800;white-space:nowrap;color:#0f172a}
@media(max-width:768px){.group-invoice-grid{grid-template-columns:1fr}.group-invoice-row{grid-template-columns:28px 1fr}.group-invoice-amount{grid-column:2}}
</style>
@endsection

@section('content')
<div class="group-invoice-page">
    <div class="group-invoice-card">
        <div class="group-invoice-head">
            <h4>Nouvelle facture groupée</h4>
            <span class="badge badge-secondary">{{ $invoices->count() }} facture(s)</span>
        </div>

        <div class="group-invoice-body">
            <form action="{{ route('groupinvoices.store') }}" method="POST">
                {{ csrf_field() }}
                <input type="hidden" name="acente" value="{{ $acente }}">

                <div class="group-invoice-grid mb-3">
                    <div>
                        <label for="invoicetarih">Date</label>
                        <input type="date" name="tarih" id="invoicetarih" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div>
                        <label for="resmi">N° facture</label>
                        <input type="text" name="resmi" id="resmi" class="form-control">
                    </div>
                    <div>
                        <label for="sirket_id">Société</label>
                        <select name="sirket_id" id="sirket_id" class="form-control" required>
                            <option value="">Choisir</option>
                            @foreach($sirkets as $id => $name)
                                <option value="{{ $id }}" {{ (string)$selectedSirketId === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="mb-0">Factures</label>
                    @if($invoices->isNotEmpty())
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="select-all-invoices">Tout sélectionner</button>
                    @endif
                </div>

                @if($invoices->isEmpty())
                    <div class="alert alert-warning mb-0">Aucune facture disponible pour cette agence.</div>
                @else
                    <div class="group-invoice-list mb-3">
                        @foreach($invoices as $invoice)
                            <label class="group-invoice-row">
                                <input type="checkbox" name="invoicelist[]" value="{{ $invoice->id }}">
                                <span class="group-invoice-main">
                                    <strong>Facture #{{ $invoice->id }} · Dossier #{{ $invoice->post_id }}</strong>
                                    <small>{{ optional($invoice->sirket)->name ?: '-' }} · {{ $invoice->tarih ? date('d/m/Y', strtotime($invoice->tarih)) : '-' }}{{ $invoice->resmi ? ' · N° '.$invoice->resmi : '' }}</small>
                                </span>
                                <span class="group-invoice-amount">{{ number_format((float)$invoice->amount, 2, ',', ' ') }} {{ optional($invoice->kur)->short_name ?: '' }}</span>
                            </label>
                        @endforeach
                    </div>

                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection

@section('footer')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var button = document.getElementById('select-all-invoices');
    if (!button) {
        return;
    }

    button.addEventListener('click', function () {
        var checkboxes = document.querySelectorAll('input[name="invoicelist[]"]');
        var shouldCheck = Array.prototype.some.call(checkboxes, function (checkbox) {
            return !checkbox.checked;
        });

        Array.prototype.forEach.call(checkboxes, function (checkbox) {
            checkbox.checked = shouldCheck;
        });
    });
});
</script>
@endsection
