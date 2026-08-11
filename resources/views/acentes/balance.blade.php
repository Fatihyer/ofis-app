@extends('layouts.app')

@section('style')
<style>
    .balance-page{background:#f8fafc;min-height:calc(100vh - 90px);padding:16px}
    .balance-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:14px}
    .balance-head h1{margin:0;font-size:25px;font-weight:850;color:#0f172a}
    .balance-head small{color:#64748b;font-weight:750}
    .balance-filter{display:flex;gap:8px;align-items:center;flex-wrap:wrap;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:10px;margin-bottom:12px}
    .balance-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:12px}
    .balance-stat{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:12px;box-shadow:0 8px 22px rgba(15,23,42,.05)}
    .balance-stat small{display:block;color:#64748b;font-size:11px;text-transform:uppercase;font-weight:850}
    .balance-stat strong{display:block;font-size:23px;color:#0f172a;line-height:1;margin-top:7px}
    .balance-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 22px rgba(15,23,42,.06);overflow:hidden}
    .balance-table th{font-size:12px;text-transform:uppercase;color:#475569;white-space:nowrap;background:#f8fafc}
    .balance-table td{vertical-align:middle;font-size:13px}
    .balance-amount{font-size:15px;font-weight:900;color:#0f172a;white-space:nowrap}
    .balance-pill{display:inline-flex;border-radius:999px;padding:4px 8px;font-size:12px;font-weight:850}
    .balance-pill.credit{background:#dcfce7;color:#166534}
    .balance-pill.debit{background:#fee2e2;color:#991b1b}
    .balance-info{max-width:420px;color:#64748b}
    @media(max-width:900px){.balance-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.balance-head{display:block}}
    @media(max-width:575px){.balance-page{padding:10px}.balance-stats{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
@php
    $creditCount = collect($sums)->filter(fn($value) => (float) $value >= 0)->count();
    $debitCount = collect($sums)->filter(fn($value) => (float) $value < 0)->count();
@endphp

<div class="balance-page">
    <div class="balance-head">
        <div>
            <h1>Balance prestataires</h1>
            <small>Soldes calculés depuis les mouvements enregistrés.</small>
        </div>
        <a href="{{ route('acentes.index') }}" class="btn btn-outline-secondary btn-sm">Prestataires</a>
    </div>

    <form method="get" name="tarih" class="balance-filter">
        <label class="mb-0 font-weight-bold">Type prestataire</label>
        {{ Form::select('firma', ['0' => 'Tous les types'] + $firma, $selectedFirma ?? '0', ['class' => 'form-control form-control-sm', 'onchange' => 'this.form.submit()', 'style' => 'max-width:260px']) }}
        @if(($selectedFirma ?? '0') !== '0')
            <a href="{{ route('balanceprovider') }}" class="btn btn-outline-dark btn-sm">Réinitialiser</a>
        @endif
    </form>

    <div class="balance-stats">
        <div class="balance-stat">
            <small>Solde net</small>
            <strong>{{ number_format($total, 2, ',', ' ') }} €</strong>
        </div>
        <div class="balance-stat">
            <small>Créditeurs</small>
            <strong class="text-success">{{ number_format($totalCredit ?? 0, 2, ',', ' ') }} €</strong>
        </div>
        <div class="balance-stat">
            <small>Débiteurs</small>
            <strong class="text-danger">{{ number_format(abs($totalDebit ?? 0), 2, ',', ' ') }} €</strong>
        </div>
        <div class="balance-stat">
            <small>Prestataires</small>
            <strong>{{ count($sums) }}</strong>
            <div class="text-muted small">{{ $creditCount }} créditeur(s), {{ $debitCount }} débiteur(s)</div>
        </div>
    </div>

    <div class="balance-card">
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
            <strong>Soldes prestataires</strong>
            <span class="text-muted small">Trié par montant décroissant</span>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 balance-table">
                <thead>
                    <tr>
                        <th>Prestataire</th>
                        <th>Solde</th>
                        <th>Situation</th>
                        <th>Info</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sums as $acenteId => $sum)
                        <tr>
                            <td>
                                @if(isset($acente[$acenteId]))
                                    <a href="{{ route('acentes.show', $acenteId) }}?src=balance">
                                        <strong>{{ $acente[$acenteId] }}</strong>
                                    </a>
                                    <div class="text-muted small">#{{ $acenteId }}</div>
                                @else
                                    <span class="text-muted">Prestataire supprimé #{{ $acenteId }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="balance-amount">{{ number_format(abs($sum), 2, ',', ' ') }} €</span>
                            </td>
                            <td>
                                @if($sum >= 0)
                                    <span class="balance-pill credit">Créditeur</span>
                                @else
                                    <span class="balance-pill debit">Débiteur</span>
                                @endif
                            </td>
                            <td>
                                <div class="balance-info">{{ $msg[$acenteId] ?? '-' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Aucun solde à afficher.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
