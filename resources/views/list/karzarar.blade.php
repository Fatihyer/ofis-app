@extends('layouts.app')
@section('style')
  <link href="{{ asset('css/daterangepicker.css') }}" rel="stylesheet">
  <style>
    .daily-ca-wrap { max-width: 1200px; margin: 0 auto; }
    .daily-ca-head { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
    .daily-ca-nav { display:flex; align-items:center; gap:8px; }
    .daily-ca-nav .btn-icon {
        width: 36px;
        height: 36px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
    .daily-ca-nav .btn-icon span {
        display: block;
        font-size: 26px;
        line-height: 20px;
        margin-top: -2px;
    }
    .daily-ca-total { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
    .daily-ca-pill { border:1px solid #dee2e6; border-radius:8px; padding:8px 12px; background:#fff; min-width:150px; }
    .daily-ca-pill small { display:block; color:#6c757d; font-size:11px; text-transform:uppercase; }
    .daily-ca-pill strong { font-size:18px; }
    .daily-ca-table { width:100%; background:#fff; }
    .daily-ca-table th { white-space:nowrap; background:#f8f9fa; }
    .daily-ca-table td { vertical-align:middle; }
    .daily-ca-money { text-align:right; white-space:nowrap; }
  </style>
@endsection

@section('content')
@php
    $alis = 0;
    $satis = 0;
    foreach ($hareket as $line) {
        $alis += (float) ($line->amount ?? 0);
        $satis += (float) ($line->default_price ?? 0);
    }
@endphp

<div class="daily-ca-wrap">
    <div class="daily-ca-head">
        <div>
            <h3 class="mb-1">CA transferts</h3>
            <div class="text-muted">{{ \Carbon\Carbon::parse($tarih)->format('d.m.Y') }}</div>
        </div>
        <div class="daily-ca-nav">
            <a class="btn btn-light border btn-icon" href="{{ route('listegunluk', \Carbon\Carbon::parse($yesterday)->format('Y-m-d')) }}" title="Jour précédent"><span aria-hidden="true">‹</span></a>
            <a href="{{ route('listegunluk', \Carbon\Carbon::parse($today)->format('Y-m-d')) }}" class="btn btn-danger">Aujourd'hui</a>
            <a class="btn btn-light border btn-icon" href="{{ route('listegunluk', \Carbon\Carbon::parse($tomorrow)->format('Y-m-d')) }}" title="Jour suivant"><span aria-hidden="true">›</span></a>
        </div>
    </div>

    <div class="daily-ca-total">
        <div class="daily-ca-pill"><small>Achats</small><strong>{{ number_format($alis, 2, ',', ' ') }} €</strong></div>
        <div class="daily-ca-pill"><small>Ventes</small><strong>{{ number_format($satis, 2, ',', ' ') }} €</strong></div>
        <div class="daily-ca-pill"><small>Total</small><strong>{{ number_format($satis + $alis, 2, ',', ' ') }} €</strong></div>
        <div class="daily-ca-pill"><small>Lignes</small><strong>{{ $hareket->total() }}</strong></div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-striped table-hover daily-ca-table">
            <thead>
                <tr>
                    <th>Heure</th>
                    <th>Dossier</th>
                    <th>Transfert</th>
                    <th>Chauffeur / fournisseur</th>
                    <th>Trajet</th>
                    <th class="daily-ca-money">Achat</th>
                    <th class="daily-ca-money">Vente</th>
                    <th class="daily-ca-money">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($hareket as $item)
                    @php
                        $transfer = $item->hareketable;
                        $achat = (float) ($item->amount ?? 0);
                        $vente = (float) ($item->default_price ?? 0);
                    @endphp
                    <tr>
                        <td>{{ $item->tarih ? \Carbon\Carbon::parse($item->tarih)->format('H:i') : '-' }}</td>
                        <td>
                            @if($item->post_id)
                                <a href="{{ route('posts.show', $item->post_id) }}">#{{ $item->post_id }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($transfer)
                                <a href="{{ route('transfers.show', $transfer->id) }}">#{{ $transfer->id }}</a>
                            @else
                                <span class="text-muted">Transfert supprimé</span>
                            @endif
                        </td>
                        <td>{{ optional(optional($transfer)->driver)->name ?? '-' }}</td>
                        <td>
                            @if($transfer)
                                <span>{{ $transfer->from ?? '-' }}</span>
                                <span class="text-muted">→</span>
                                <span>{{ $transfer->target ?? '-' }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="daily-ca-money">{{ number_format($achat, 2, ',', ' ') }} €</td>
                        <td class="daily-ca-money">{{ number_format($vente, 2, ',', ' ') }} €</td>
                        <td class="daily-ca-money">{{ number_format($achat + $vente, 2, ',', ' ') }} €</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Aucun mouvement transfert pour cette date.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5">Total page</th>
                    <th class="daily-ca-money">{{ number_format($alis, 2, ',', ' ') }} €</th>
                    <th class="daily-ca-money">{{ number_format($satis, 2, ',', ' ') }} €</th>
                    <th class="daily-ca-money">{{ number_format($satis + $alis, 2, ',', ' ') }} €</th>
                </tr>
            </tfoot>
        </table>
    </div>

    {{ $hareket->links() }}
</div>
@endsection
