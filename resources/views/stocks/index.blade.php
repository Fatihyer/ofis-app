@extends('layouts.app')

@section('style')
<style>
.stock-page{background:#f8fafc;min-height:calc(100vh - 90px);padding:16px}.stock-header{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;margin-bottom:14px}.stock-header h1{margin:0;font-size:25px;font-weight:850;color:#0f172a}.stock-header small{color:#64748b;font-weight:750}.stock-stats{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin-bottom:14px}.stock-stat{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:12px}.stock-stat small{display:block;color:#64748b;text-transform:uppercase;font-size:11px;font-weight:850}.stock-stat strong{display:block;color:#0f172a;font-size:22px;margin-top:6px}.stock-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 22px rgba(15,23,42,.06);overflow:hidden;margin-bottom:14px}.stock-card-header{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:12px 14px;border-bottom:1px solid #e5e7eb}.stock-card-header strong{color:#0f172a;font-size:15px}.mode-badge,.movement-badge{display:inline-flex;align-items:center;border-radius:999px;padding:4px 8px;font-size:12px;font-weight:850;white-space:nowrap}.mode-bulk,.move-in{background:#dcfce7;color:#166534}.mode-demand{background:#e0f2fe;color:#075985}.mode-other{background:#f1f5f9;color:#475569}.move-out{background:#fee2e2;color:#991b1b}.move-adjust{background:#fef3c7;color:#92400e}.stock-ok{color:#166534;font-weight:900}.stock-low{color:#b91c1c;font-weight:900}.filter-row{display:flex;flex-wrap:wrap;gap:8px;align-items:center}.stock-table th{font-size:12px;text-transform:uppercase;color:#475569;white-space:nowrap}.stock-table td{vertical-align:middle}.money-pos{color:#166534;font-weight:850}.money-neg{color:#b91c1c;font-weight:850}@media(max-width:1199.98px){.stock-stats{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:991.98px){.stock-header{display:block}.stock-header .btn{margin-top:10px}.stock-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:575.98px){.stock-page{padding:10px}.stock-stats{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
@php
$money=fn($v)=>number_format((float)$v,2,',',' ').' €';
$modeLabel=function($stock)use($bulkProductIds,$onDemandProductIds){$id=(int)$stock->urun_id;if(in_array($id,$bulkProductIds,true))return['class'=>'mode-bulk','label'=>'Stock suivi'];if(in_array($id,$onDemandProductIds,true))return['class'=>'mode-demand','label'=>'À la demande'];return['class'=>'mode-other','label'=>'Autre'];};
$movementLabel=fn($type)=>match($type){'in'=>['class'=>'move-in','label'=>'Entrée'],'adjust'=>['class'=>'move-adjust','label'=>'Ajustement'],default=>['class'=>'move-out','label'=>'Sortie']};
@endphp

<div class="stock-page">
  <div class="stock-header">
    <div><h1>Billetterie & stock</h1><small>Bateaux Mouches et Bateaux Parisiens en stock réel · Disneyland à la demande</small></div>
    <div class="d-flex flex-wrap" style="gap:8px;">
      <a href="{{ route('stocks.export', request()->query()) }}" class="btn btn-outline-success btn-sm"><i class="fas fa-file-excel"></i> Export Excel</a>
      <a href="{{ route('stocks.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Nouveau mouvement</a>
    </div>
  </div>

  <div class="stock-stats">
    <div class="stock-stat"><small>Stock réel</small><strong class="{{ $summary['stock_remaining'] >= 0 ? 'stock-ok' : 'stock-low' }}">{{ $summary['stock_remaining'] }}</strong></div>
    <div class="stock-stat"><small>Entrées</small><strong>{{ $summary['stock_in'] }}</strong></div>
    <div class="stock-stat"><small>Sorties</small><strong>{{ $summary['stock_out'] }}</strong></div>
    <div class="stock-stat"><small>Ajustements</small><strong>{{ $summary['stock_adjust'] }}</strong></div>
    <div class="stock-stat"><small>Marge</small><strong class="{{ $summary['margin'] >= 0 ? 'money-pos' : 'money-neg' }}">{{ $money($summary['margin']) }}</strong></div>
  </div>

  <div class="stock-card">
    <div class="stock-card-header">
      <strong>Stock par produit</strong>
      <form method="GET" action="{{ route('stocks.index') }}" class="filter-row">
        <select name="type" class="form-control form-control-sm" onchange="this.form.submit()" style="width:170px"><option value="">Tous les modes</option><option value="bulk" {{ $type==='bulk'?'selected':'' }}>Stock suivi</option><option value="ondemand" {{ $type==='ondemand'?'selected':'' }}>À la demande</option></select>
        <select name="movement_type" class="form-control form-control-sm" onchange="this.form.submit()" style="width:170px"><option value="">Tous mouvements</option><option value="in" {{ $movementType==='in'?'selected':'' }}>Entrées</option><option value="out" {{ $movementType==='out'?'selected':'' }}>Sorties</option><option value="adjust" {{ $movementType==='adjust'?'selected':'' }}>Ajustements</option></select>
        <select name="product_id" class="form-control form-control-sm" onchange="this.form.submit()" style="width:220px"><option value="">Tous les produits</option>@foreach($products as $id=>$name)<option value="{{ $id }}" {{ (string)$productId===(string)$id?'selected':'' }}>{{ $name }}</option>@endforeach</select>
        <select name="client_agency_id" class="form-control form-control-sm" onchange="this.form.submit()" style="width:240px"><option value="">Tous clients / agences</option>@foreach($clientAgencies as $id=>$name)<option value="{{ $id }}" {{ (string)$clientAgencyId===(string)$id?'selected':'' }}>{{ $name }}</option>@endforeach</select>
        @if($type||$productId||$movementType||$clientAgencyId)<a href="{{ route('stocks.index') }}" class="btn btn-outline-secondary btn-sm">Réinitialiser</a>@endif
      </form>
    </div>
    <div class="table-responsive"><table class="table table-sm table-hover mb-0 stock-table"><thead class="table-light"><tr><th>Produit</th><th>Mode</th><th class="text-center">Entrées</th><th class="text-center">Sorties</th><th class="text-center">Ajust.</th><th class="text-center">Stock réel</th><th class="text-center">Mouvements</th><th class="text-right">Marge</th></tr></thead><tbody>
      @forelse($productStats as $stat)
        @php $mode=$stat->mode==='bulk'?['class'=>'mode-bulk','label'=>'Stock suivi']:($stat->mode==='ondemand'?['class'=>'mode-demand','label'=>'À la demande']:['class'=>'mode-other','label'=>'Autre']);$tracked=$stat->mode==='bulk'; @endphp
        <tr><td><strong>{{ optional($stat->urun)->name ?: 'Produit #'.$stat->urun_id }}</strong></td><td><span class="mode-badge {{ $mode['class'] }}">{{ $mode['label'] }}</span></td><td class="text-center">{{ $tracked?(int)$stat->stock_in:'-' }}</td><td class="text-center">{{ $tracked?(int)$stat->stock_out:'-' }}</td><td class="text-center">{{ $tracked?(int)$stat->stock_adjust:'-' }}</td><td class="text-center {{ $stat->stock_remaining >= 0 ? 'stock-ok' : 'stock-low' }}">{{ $tracked?(int)$stat->stock_remaining:'À la demande' }}</td><td class="text-center">{{ $stat->movement_count }}</td><td class="text-right {{ $stat->margin >= 0 ? 'money-pos' : 'money-neg' }}">{{ $money($stat->margin) }}</td></tr>
      @empty<tr><td colspan="8" class="text-center text-muted py-4">Aucun produit suivi.</td></tr>@endforelse
    </tbody></table></div>
  </div>

  <div class="stock-card">
    <div class="stock-card-header"><strong>Mouvements de billetterie</strong><span class="text-muted small">Entrée augmente le stock, sortie le diminue, ajustement corrige le comptage.</span></div>
    <div class="table-responsive"><table class="table table-sm table-hover mb-0 stock-table"><thead class="table-light"><tr><th>#</th><th>Date</th><th>Produit</th><th>Mouvement</th><th>Mode</th><th class="text-center">Qté</th><th class="text-center">Stock</th><th>Fournisseur</th><th>Client / agence</th><th class="text-right">Achat</th><th class="text-right">Vente</th><th class="text-right">Marge</th><th>Dossier</th></tr></thead><tbody>
      @forelse($stocks as $stock)
        @php $mode=$modeLabel($stock);$movement=$movementLabel($stock->movement_type??'out');$buyTotal=(float)$stock->buy_price;$sellTotal=(float)$stock->sell_price;$margin=$sellTotal-$buyTotal; @endphp
        <tr><td><a href="{{ route('stocks.edit',$stock->id) }}">#{{ $stock->id }}</a></td><td>{{ $stock->tarih ? \Carbon\Carbon::parse($stock->tarih)->format('d/m/Y') : '-' }}</td><td><strong>{{ optional($stock->urun)->name }}</strong>@if($stock->aciklama)<div class="text-muted small">{{ $stock->aciklama }}</div>@endif</td><td><span class="movement-badge {{ $movement['class'] }}">{{ $movement['label'] }}</span></td><td><span class="mode-badge {{ $mode['class'] }}">{{ $mode['label'] }}</span></td><td class="text-center"><strong>{{ $stock->adet }}</strong></td><td class="text-center">{{ $stock->affects_stock ? 'Oui' : 'Non' }}</td><td>@if($stock->a_acente_id)<a href="{{ route('acentes.show',$stock->a_acente_id) }}">{{ optional($stock->aAcente)->name }}</a>@else - @endif</td><td>@if($stock->b_acente_id)<a href="{{ route('acentes.show',$stock->b_acente_id) }}">{{ optional($stock->bAcente)->name }}</a>@else - @endif</td><td class="text-right">{{ $money($buyTotal) }}</td><td class="text-right">{{ $money($sellTotal) }}</td><td class="text-right {{ $margin >= 0 ? 'money-pos' : 'money-neg' }}">{{ $money($margin) }}</td><td>@if($stock->post_id)<a href="{{ route('posts.show',$stock->post_id) }}" class="btn btn-sm btn-outline-secondary">#{{ $stock->post_id }}</a>@else <span class="text-muted">-</span>@endif</td></tr>
      @empty<tr><td colspan="13" class="text-center text-muted py-4">Aucun mouvement.</td></tr>@endforelse
    </tbody></table></div>
    <div class="p-3 d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
      <div class="font-weight-bold">Quantité totale: {{ number_format((int) $movementQuantityTotal, 0, ',', ' ') }}</div>
      <div>{{ $stocks->links() }}</div>
    </div>
  </div>
</div>
@endsection
