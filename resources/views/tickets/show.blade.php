@extends('layouts.app')

@section('content')
<style>
  .tk-page { font-size: .92rem; }
  .tk-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px; margin-bottom: 14px; }
  .tk-src { color: #6b7280; font-size: .78rem; }
  .tk-num { text-align: right; white-space: nowrap; }
</style>

@php
  $totalAmount = $sales->sum('amount');
  $totalTickets = $sales->sum(fn ($s) => $s->qty_adult + $s->qty_child);
  $invoiceTotal = $invoices->sum('amount');
@endphp

<div class="container-fluid tk-page">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h4 mb-1">{{ $sales->first()->customer_raw }}</h1>
      <div class="text-muted" style="font-size:.84rem">
        {{ $totalTickets }} bilet · {{ number_format($totalAmount, 0, ',', '.') }} €
        @if($invoices->count())
          · {{ $invoices->count() }} fatura {{ number_format($invoiceTotal, 0, ',', '.') }} €
          · fark <strong>{{ number_format($totalAmount - $invoiceTotal, 0, ',', '.') }} €</strong>
        @else
          · <span class="text-danger">dönemde fatura yok</span>
        @endif
      </div>
    </div>
    <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-secondary">Listeye dön</a>
  </div>

  <div class="tk-card">
    <h2 class="h6">Bilet teslimleri</h2>
    <table class="table table-sm mb-0">
      <thead class="thead-light">
        <tr>
          <th>Tarih</th><th>Ürün</th><th class="tk-num">Yetişkin</th><th class="tk-num">Çocuk</th>
          <th class="tk-num">Tutar</th><th>Kaynak mesaj</th>
        </tr>
      </thead>
      <tbody>
        @foreach($sales as $sale)
          <tr>
            <td>{{ $sale->sale_date->format('d.m.Y') }}</td>
            <td>{{ ucfirst($sale->product) }}</td>
            <td class="tk-num">{{ $sale->qty_adult }}</td>
            <td class="tk-num">{{ $sale->qty_child ?: '—' }}</td>
            <td class="tk-num">{{ number_format((float) $sale->amount, 0, ',', '.') }} €</td>
            <td class="tk-src">{{ $sale->source_line }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="tk-card">
    <h2 class="h6">Dönem faturaları</h2>
    @if($invoices->count())
      <table class="table table-sm mb-0">
        <thead class="thead-light"><tr><th>Tarih</th><th>Fatura</th><th class="tk-num">Tutar</th></tr></thead>
        <tbody>
          @foreach($invoices as $invoice)
            <tr>
              <td>{{ \Illuminate\Support\Carbon::parse($invoice->tarih)->format('d.m.Y') }}</td>
              <td><a href="{{ url('/invoice/' . $invoice->id) }}">#{{ $invoice->id }}</a></td>
              <td class="tk-num">{{ number_format((float) $invoice->amount, 0, ',', '.') }} €</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <div class="text-muted">Bu dönemde acenteye kesilmiş fatura bulunamadı.</div>
    @endif
  </div>

  <div class="tk-card">
    <h2 class="h6">Tahsilat grubunda geçen mesajlar</h2>
    @forelse($collections as $message)
      <div class="mb-2">
        <div class="tk-src">
          {{ $message->sent_at ? \Illuminate\Support\Carbon::parse($message->sent_at)->format('d.m.Y H:i') : '' }}
          · {{ $message->sender_name ?: 'bilinmiyor' }}
        </div>
        <div>{{ $message->body }}</div>
      </div>
    @empty
      <div class="text-muted">Tahsilat grubunda bu isim geçmiyor.</div>
    @endforelse
  </div>
</div>
@endsection
