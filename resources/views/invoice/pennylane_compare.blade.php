@extends('layouts.app')

@section('style')
<style>
.penny-compare{background:#f8fafc;min-height:calc(100vh - 90px);padding:14px}.pc-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-end;margin-bottom:14px}.pc-head h1{font-size:24px;font-weight:850;margin:0;color:#0f172a}.pc-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 20px rgba(15,23,42,.06);margin-bottom:14px;overflow:hidden}.pc-card-h{padding:12px 14px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;gap:10px;align-items:center}.pc-filters{display:grid;grid-template-columns:repeat(5,minmax(140px,1fr));gap:10px;align-items:end}.pc-stats{display:grid;grid-template-columns:repeat(5,minmax(120px,1fr));gap:10px}.pc-stat{border:1px solid #e5e7eb;border-radius:8px;padding:10px;background:#fff}.pc-stat strong{display:block;font-size:22px;color:#0f172a}.pc-stat span{font-size:12px;color:#64748b;font-weight:700}.pc-table th{font-size:12px;text-transform:uppercase;white-space:nowrap;color:#475569}.pc-table td{vertical-align:top}.pc-badge{display:inline-flex;border-radius:999px;padding:4px 8px;font-size:11px;font-weight:850}.pc-ok{background:#ecfdf5;color:#166534}.pc-missing{background:#fef2f2;color:#991b1b}.pc-amount{background:#fff7ed;color:#9a3412}.pc-date{background:#eff6ff;color:#1d4ed8}.pc-extra{background:#f5f3ff;color:#6d28d9}.pc-money{text-align:right;white-space:nowrap}.pc-muted{color:#64748b;font-size:12px}@media(max-width:992px){.pc-filters,.pc-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.pc-head{display:block}}@media(max-width:576px){.penny-compare{padding:8px}.pc-filters,.pc-stats{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
@php
    $labels = [
        'ok' => ['OK', 'pc-ok'],
        'missing_pennylane' => ['Absent Pennylane', 'pc-missing'],
        'amount_mismatch' => ['Montant différent', 'pc-amount'],
        'date_mismatch' => ['Date différente', 'pc-date'],
        'extra_pennylane' => ['Absent Laravel', 'pc-extra'],
    ];
@endphp
<div class="penny-compare">
    <div class="pc-head">
        <div>
            <h1>Contrôle Pennylane</h1>
            <div class="pc-muted">Comparaison lecture seule entre les factures Laravel et Pennylane.</div>
        </div>
        <a href="{{ route('invoices.index', ['sirket_id' => $selectedSirketId]) }}" class="btn btn-sm btn-outline-secondary">Retour aux factures</a>
    </div>

    <div class="pc-card">
        <div class="pc-card-h"><strong>Filtres</strong><span class="pc-muted">Compte Pennylane: {{ $accountKey }}</span></div>
        <div class="p-3">
            <form method="GET" action="{{ route('invoices.pennylane.compare') }}" class="pc-filters">
                <div class="form-group mb-0"><label>Société</label><select name="sirket_id" class="form-control">@foreach($sirkets as $id => $name)<option value="{{ $id }}" {{ (int)$selectedSirketId === (int)$id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div>
                <div class="form-group mb-0"><label>Du</label><input type="date" name="start_date" class="form-control" value="{{ $startDate }}"></div>
                <div class="form-group mb-0"><label>Au</label><input type="date" name="end_date" class="form-control" value="{{ $endDate }}"></div>
                <div class="form-group mb-0"><label>Contrôle</label><select name="status" class="form-control"><option value="all">Tous</option>@foreach($labels as $key => $meta)<option value="{{ $key }}" {{ $statusFilter === $key ? 'selected' : '' }}>{{ $meta[0] }}</option>@endforeach</select></div>
                <div class="form-group mb-0"><button class="btn btn-primary btn-block">Filtrer</button></div>
            </form>
        </div>
    </div>

    <div class="pc-stats mb-3">
        @foreach($labels as $key => $meta)
            <a class="pc-stat" href="{{ route('invoices.pennylane.compare', ['sirket_id'=>$selectedSirketId,'start_date'=>$startDate,'end_date'=>$endDate,'status'=>$key]) }}"><strong>{{ $stats[$key] ?? 0 }}</strong><span>{{ $meta[0] }}</span></a>
        @endforeach
    </div>

    <div class="pc-card">
        <div class="pc-card-h"><strong>Résultats</strong><span class="pc-muted">{{ count($rows) }} ligne(s) affichée(s) · Laravel: {{ $laravelInvoices->count() }} · Pennylane: {{ count($pennylaneInvoices) }}</span></div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 pc-table">
                <thead class="thead-light"><tr><th>Contrôle</th><th>N° facture</th><th>Laravel</th><th>Pennylane</th><th class="text-right">Écart</th><th>Liens</th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    @php
                        $meta = $labels[$row['status']] ?? ['-', 'pc-missing'];
                        $laravel = $row['laravel'];
                        $penny = $row['pennylane'];
                    @endphp
                    <tr>
                        <td><span class="pc-badge {{ $meta[1] }}">{{ $meta[0] }}</span></td>
                        <td>
                            <strong>{{ $row['number'] }}</strong>
                            @if(!empty($row['matched_number']) && $row['matched_number'] !== \Illuminate\Support\Str::upper(str_replace(' ', '', rtrim((string) $row['number'], '/'))))
                                <div class="pc-muted">Correspondance: {{ $row['matched_number'] }}</div>
                            @endif
                        </td>
                        <td>
                            @if($laravel)
                                <div>Date: {{ substr((string)$laravel->tarih, 0, 10) }}</div>
                                <div class="pc-money">{{ number_format((float)$laravel->amount, 2, ',', ' ') }} €</div>
                                <div class="pc-muted">
                                    @if($laravel->post_id)
                                        <a href="{{ route('posts.show', $laravel->post_id) }}">Dossier #{{ $laravel->post_id }}</a>
                                    @else
                                        Dossier -
                                    @endif
                                    · {{ optional($laravel->acente)->name }}
                                </div>
                            @else
                                <span class="text-muted">Absent</span>
                            @endif
                        </td>
                        <td>
                            @if($penny)
                                <div>Date: {{ $penny['date'] ?? '-' }}</div>
                                <div class="pc-money">{{ number_format((float)($penny['amount'] ?? 0), 2, ',', ' ') }} €</div>
                                <div class="pc-muted">{{ $penny['status'] ?? '-' }} · ID {{ $penny['id'] ?? '-' }}</div>
                            @else
                                <span class="text-muted">Absent</span>
                            @endif
                        </td>
                        <td class="pc-money">@if($row['amount_diff'] !== null){{ number_format((float)$row['amount_diff'], 2, ',', ' ') }} €@else - @endif</td>
                        <td>
                            @if($laravel)
                                <a class="btn btn-xs btn-outline-primary" href="{{ route('invoices.show', $laravel->id) }}">Ouvrir</a>
                                <a class="btn btn-xs btn-outline-secondary" target="_blank" href="{{ route('invoicetopdf', [$laravel->id, 'html']) }}">Voir facture</a>
                            @endif
                            @if($penny && !empty($penny['public_file_url']))
                                <a class="btn btn-xs btn-outline-secondary" target="_blank" href="{{ $penny['public_file_url'] }}">PDF Pennylane</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun écart à afficher.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
