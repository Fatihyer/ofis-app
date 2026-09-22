@extends('layouts.app')

@section('content')
<style>
  .tk-page { font-size: .92rem; }
  .tk-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px; margin-bottom: 14px; }
  .tk-stat { display: inline-block; min-width: 150px; margin-right: 18px; }
  .tk-stat .v { font-size: 1.35rem; font-weight: 700; line-height: 1.1; }
  .tk-stat .l { color: #6b7280; font-size: .8rem; }
  .tk-open { background: #fef2f2; }
  .tk-badge { display: inline-block; border-radius: 999px; padding: 2px 9px; font-size: .75rem; }
  .tk-badge.ok { background: #dcfce7; color: #166534; }
  .tk-badge.warn { background: #fee2e2; color: #991b1b; }
  .tk-badge.soft { background: #f1f5f9; color: #334155; }
  .tk-table td, .tk-table th { vertical-align: middle; }
  .tk-num { text-align: right; white-space: nowrap; }
</style>

<div class="container-fluid tk-page">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h4 mb-1">Bilet Satışları ve Tahsilat Takibi</h1>
      <div class="text-muted" style="font-size:.84rem">
        WhatsApp bilet gruplarındaki teslim kayıtları, faturalar ve tahsilat grubu ile karşılaştırılır.
      </div>
    </div>
  </div>

  <div class="tk-card">
    <div class="tk-stat"><div class="v">{{ number_format($summary['tickets'], 0, ',', '.') }}</div><div class="l">Bilet</div></div>
    <div class="tk-stat"><div class="v">{{ number_format($summary['amount'], 0, ',', '.') }} €</div><div class="l">Toplam satış</div></div>
    <div class="tk-stat"><div class="v text-danger">{{ number_format($summary['open_amount'], 0, ',', '.') }} €</div><div class="l">Kaydı olmayan ({{ $summary['open_count'] }} müşteri)</div></div>
    <div class="tk-stat"><div class="v">{{ $summary['customers'] }}</div><div class="l">Müşteri</div></div>
  </div>

  <form method="GET" class="tk-card">
    <div class="form-row align-items-end">
      <div class="col-auto mb-2">
        <label class="mb-1" style="font-size:.8rem">Başlangıç</label>
        <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control form-control-sm">
      </div>
      <div class="col-auto mb-2">
        <label class="mb-1" style="font-size:.8rem">Bitiş</label>
        <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control form-control-sm">
      </div>
      <div class="col-auto mb-2">
        <label class="mb-1" style="font-size:.8rem">Ürün</label>
        <select name="product" class="form-control form-control-sm">
          <option value="">Hepsi</option>
          @foreach($products as $p)
            <option value="{{ $p }}" @selected($product === $p)>{{ ucfirst($p) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-auto mb-2">
        <div class="custom-control custom-checkbox">
          <input type="checkbox" class="custom-control-input" id="open" name="open" value="1" @checked($onlyOpen)>
          <label class="custom-control-label" for="open" style="font-size:.85rem">Sadece kaydı olmayanlar</label>
        </div>
      </div>
      <div class="col-auto mb-2">
        <button class="btn btn-sm btn-primary">Filtrele</button>
        <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-secondary">Sıfırla</a>
      </div>
    </div>
  </form>

  <div class="tk-card p-0">
    <table class="table table-sm table-hover mb-0 tk-table">
      <thead class="thead-light">
        <tr>
          <th>Müşteri / Rehber</th>
          <th>Acente kaydı</th>
          <th class="tk-num">Bilet</th>
          <th class="tk-num">Satış (€)</th>
          <th class="tk-num">Fatura (€)</th>
          <th class="tk-num">Fark (€)</th>
          <th class="text-center">Tahsilat grubu</th>
          <th>Dönem</th>
          <th class="text-center">Durum</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $row)
          @php $open = $row['invoice_count'] === 0 && $row['cash_hits'] === 0; @endphp
          <tr class="{{ $open ? 'tk-open' : '' }}">
            <td>
              <a href="{{ route('tickets.show', $row['customer_key']) }}">{{ $row['customer_raw'] }}</a>
              <div class="text-muted" style="font-size:.76rem">{{ $row['lines'] }} teslim kaydı</div>
            </td>
            <td>
              @if($row['acente_id'])
                <a href="{{ url('/acentes/' . $row['acente_id']) }}">{{ $row['acente_name'] }}</a>
              @else
                <span class="tk-badge soft">eşleşmedi</span>
              @endif
            </td>
            <td class="tk-num">{{ number_format($row['tickets'], 0, ',', '.') }}</td>
            <td class="tk-num">{{ number_format($row['amount'], 0, ',', '.') }}</td>
            <td class="tk-num">{{ $row['invoice_count'] ? number_format($row['invoiced'], 0, ',', '.') : '—' }}</td>
            <td class="tk-num {{ $row['invoice_count'] && $row['gap'] > 50 ? 'text-danger' : '' }}">
              {{ $row['invoice_count'] ? number_format($row['gap'], 0, ',', '.') : '—' }}
            </td>
            <td class="text-center">
              @if($row['cash_hits'])
                <span class="tk-badge ok">{{ $row['cash_hits'] }} kayıt</span>
              @else
                <span class="tk-badge soft">yok</span>
              @endif
            </td>
            <td style="font-size:.8rem">
              {{ \Illuminate\Support\Carbon::parse($row['first_date'])->format('d.m') }} –
              {{ \Illuminate\Support\Carbon::parse($row['last_date'])->format('d.m.Y') }}
            </td>
            <td class="text-center">
              @if($open)
                <span class="tk-badge warn">kayıt yok</span>
              @elseif($row['invoice_count'])
                <span class="tk-badge ok">faturalı</span>
              @else
                <span class="tk-badge soft">nakit</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="9" class="text-center text-muted py-4">Kayıt yok. <code>php artisan tickets:extract</code> çalıştırılmış mı?</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="text-muted" style="font-size:.78rem">
    <strong>Not:</strong> Tutarlar gruptaki fiyat bilgilerinden hesaplanır (mouches 10 €, parisiens 11 € / çocuk 8 €; Selçuk Büyükkök 9 €, Mehmet Genç 8 €).
    “Kayıt yok” = dönem içinde ne fatura ne de tahsilat grubunda isim geçiyor; kesin borç değil, kontrol edilmesi gereken kayıt demektir.
  </div>
</div>
@endsection
