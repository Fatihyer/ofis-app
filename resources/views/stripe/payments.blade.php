@extends('layouts.app')

@section('style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.stripe-page{padding:14px;background:#f8fafc;min-height:calc(100vh - 90px)}
.stripe-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 20px rgba(15,23,42,.06);margin-bottom:14px;overflow:hidden}
.stripe-card-h{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:12px 14px;border-bottom:1px solid #e5e7eb}
.stripe-card-h h3{margin:0;font-size:20px;font-weight:850;color:#0f172a}
.stripe-form-grid{display:grid;grid-template-columns:repeat(5,minmax(150px,1fr));gap:10px;align-items:end}
.stripe-table th{font-size:12px;text-transform:uppercase;white-space:nowrap}
.stripe-table td{vertical-align:middle}
.stripe-badge{display:inline-flex;border-radius:999px;padding:4px 9px;font-size:12px;font-weight:800}
.stripe-badge-ok{background:#e8f7ef;color:#166534}
.stripe-badge-wait{background:#fff7df;color:#92400e}
.stripe-pagination svg{width:16px!important;height:16px!important}
@media(max-width:992px){.stripe-form-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:576px){.stripe-form-grid{grid-template-columns:1fr}.stripe-page{padding:8px}}
</style>
@endsection

@section('content')
<div class="stripe-page">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="stripe-card">
        <div class="stripe-card-h">
            <div>
                <h3>Paiements Stripe</h3>
                <small class="text-muted">Récupération des paiements réussis et comptabilisation dans le compte courant.</small>
            </div>
        </div>
        <div class="p-3">
            <form action="{{ route('stripe.payments.sync') }}" method="POST">
                @csrf
                <div class="stripe-form-grid">
                    <div class="form-group mb-0">
                        <label>Société</label>
                        <select name="sirket_id" class="form-control" required>
                            @foreach($sirkets as $id => $name)
                                <option value="{{ $id }}" {{ (string)$selectedSirketId === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Du</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                    </div>
                    <div class="form-group mb-0">
                        <label>Au</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                    </div>
                    <div class="form-group mb-0">
                        <button class="btn btn-primary">Récupérer Stripe</button>
                    </div>
                    <div class="small text-muted">
                        Webhook: <code>{{ url('/stripe/webhook') }}</code>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="stripe-card">
        <div class="stripe-card-h">
            <h3>Liste des paiements</h3>
            <form method="GET" action="{{ route('stripe.payments.index') }}" class="form-inline mb-0">
                <input type="hidden" name="sirket_id" value="{{ $selectedSirketId }}">
                <input type="hidden" name="start_date" value="{{ $startDate }}">
                <input type="hidden" name="end_date" value="{{ $endDate }}">
                <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>À comptabiliser</option>
                    <option value="posted" {{ $status === 'posted' ? 'selected' : '' }}>Comptabilisés</option>
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Tous</option>
                </select>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm mb-0 stripe-table">
                <thead class="thead-dark">
                    <tr>
                        <th>Date</th>
                        <th>Stripe</th>
                        <th>Client</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Comptabilisation</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($payments as $payment)
                    @php
                        $description = $payment->description ?: 'Paiement Stripe '.$payment->stripe_id;
                        $intentKey = $payment->payment_intent_id ?: $payment->stripe_id;
                        $postedElsewhere = !$payment->offset_id && $intentKey && $postedIntentKeys->has($intentKey);
                        $isPosted = (bool) $payment->offset_id || $postedElsewhere;
                    @endphp
                    <tr>
                        <td>{{ optional($payment->paid_at)->format('d/m/Y H:i') }}</td>
                        <td>
                            <strong>{{ $payment->stripe_id }}</strong><br>
                            <small class="text-muted">{{ $description }}</small>
                        </td>
                        <td>
                            {{ $payment->customer_email ?: '-' }}
                            @if($payment->acente)
                                <br><small class="text-muted">{{ $payment->acente->name }}</small>
                            @endif
                        </td>
                        <td class="text-success font-weight-bold">{{ number_format($payment->amount, 2, ',', ' ') }} {{ $payment->currency }}</td>
                        <td>
                            <span class="stripe-badge {{ $isPosted ? 'stripe-badge-ok' : 'stripe-badge-wait' }}">
                                {{ $payment->offset_id ? 'Comptabilisé' : ($postedElsewhere ? 'Déjà comptabilisé' : 'À comptabiliser') }}
                            </span>
                            @if($payment->offset_id)
                                <br><small class="text-muted">Offset #{{ $payment->offset_id }}</small>
                            @elseif($postedElsewhere)
                                <br><small class="text-muted">Ce paiement Stripe a déjà été traité.</small>
                            @endif
                        </td>
                        <td>
                            @if(!$isPosted)
                                <form method="POST" action="{{ route('stripe.payments.offset', $payment) }}">
                                    @csrf
                                    <input type="hidden" name="amount" value="{{ $payment->amount }}">
                                    <input type="hidden" name="kur_id" value="1">
                                    <textarea name="aciklama" class="form-control form-control-sm mb-1" rows="2" required>{{ $description }} - {{ $payment->customer_email }} - {{ $payment->stripe_id }}</textarea>
                                    <div class="d-flex flex-wrap" style="gap:6px">
                                        <select name="a_acente_id" class="form-control form-control-sm select-stripe-account" required style="min-width:190px">
                                            <option value="">Compte Stripe interne</option>
                                            @foreach($stripeAccounts as $id => $name)
                                                <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                        <select name="b_acente_id" class="form-control form-control-sm select-acente" required style="min-width:220px">
                                            <option value="">Client / agence</option>
                                            @foreach($acenteler as $id => $name)
                                                <option value="{{ $id }}" {{ (string)$payment->acente_id === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-success">Créer écriture</button>
                                    </div>
                                </form>
                            @else
                                <span class="text-muted">Déjà traité</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun paiement Stripe disponible.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 stripe-pagination">{{ $payments->links() }}</div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function(){
    $('.select-acente,.select-stripe-account').select2({width:'100%'});
});
</script>
@endsection
