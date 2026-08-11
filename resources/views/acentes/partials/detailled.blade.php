@php
    $totalFiles = $posts->total();
    $visibleTransfers = $posts->getCollection()->sum(fn ($post) => $post->transfer->count());
    $visibleAmount = $posts->getCollection()->sum(function ($post) {
        return $post->transfer->sum(function ($transfer) {
            return $transfer->harekets->sum('amount');
        });
    });
@endphp

<style>
.detail-page { color:#172033; }
.detail-summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-bottom:12px; }
.detail-tile { border:1px solid #e5e7eb; border-radius:8px; padding:12px; background:#f8fafc; }
.detail-tile small { display:block; color:#64748b; font-size:11px; font-weight:850; text-transform:uppercase; }
.detail-tile strong { display:block; margin-top:5px; font-size:23px; line-height:1.1; color:#0f172a; }
.detail-file { border:1px solid #e5e7eb; border-radius:8px; background:#fff; overflow:hidden; margin-bottom:12px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
.detail-file-head { display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; padding:12px 14px; background:#f8fafc; border-bottom:1px solid #e5e7eb; }
.detail-file-title { font-weight:850; color:#0f172a; text-decoration:none; }
.detail-file-meta { color:#64748b; font-size:12px; margin-top:3px; }
.detail-badge { display:inline-flex; align-items:center; border-radius:999px; padding:4px 8px; font-size:12px; font-weight:850; background:#eef2ff; color:#3730a3; }
.detail-table { margin:0; }
.detail-table th { background:#fff; color:#64748b; font-size:11px; text-transform:uppercase; white-space:nowrap; border-top:0; }
.detail-table td { font-size:13px; vertical-align:top; }
.detail-route { min-width:260px; max-width:420px; }
.detail-route strong { color:#111827; }
.detail-muted { color:#64748b; font-size:12px; }
.detail-money { font-weight:900; color:#0f172a; white-space:nowrap; }
.detail-actions { white-space:nowrap; }
@media(max-width:900px){ .detail-summary{grid-template-columns:1fr}.detail-table{min-width:920px} }
</style>

<div class="detail-page">
    <div class="d-flex justify-content-end mb-2">
        <a class="btn btn-sm btn-success" href="{{ route('acentes.detailled.export', array_merge(['id' => $acente->id], request()->only(['daterange', 'start_date', 'end_date']))) }}">
            <i class="fa fa-file-excel-o"></i> Export Excel client
        </a>
    </div>

    <div class="detail-summary">
        <div class="detail-tile">
            <small>Dossiers</small>
            <strong>{{ $totalFiles }}</strong>
        </div>
        <div class="detail-tile">
            <small>Transferts affichés</small>
            <strong>{{ $visibleTransfers }}</strong>
        </div>
        <div class="detail-tile">
            <small>Total mouvements affichés</small>
            <strong>{{ number_format($visibleAmount, 2, ',', ' ') }} €</strong>
        </div>
    </div>

    @forelse ($posts as $post)
        @php
            $fileAmount = $post->transfer->sum(fn ($transfer) => $transfer->harekets->sum('amount'));
        @endphp
        <div class="detail-file">
            <div class="detail-file-head">
                <div>
                    <a class="detail-file-title" href="{{ route('posts.show', $post->id) }}">FP{{ $post->id }} · {{ $post->title ?: 'Sans titre' }}</a>
                    <div class="detail-file-meta">
                        {{ $post->start_date ? \Carbon\Carbon::parse($post->start_date)->format('d/m/Y') : '-' }}
                        - {{ $post->end_date ? \Carbon\Carbon::parse($post->end_date)->format('d/m/Y') : '-' }}
                        @if($post->pax)
                            · {{ $post->pax }} pax
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <span class="detail-badge">{{ $post->transfer->count() }} transfert(s)</span>
                    <div class="detail-money mt-1">{{ number_format($fileAmount, 2, ',', ' ') }} €</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover detail-table">
                    <thead>
                        <tr>
                            <th>Transfert</th>
                            <th>Service</th>
                            <th>Horaires</th>
                            <th>Itinéraire</th>
                            <th>Véhicule</th>
                            <th>Chauffeur</th>
                            <th class="text-right">Montant</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($post->transfer as $transfert)
                            @php
                                $amount = $transfert->harekets->sum('amount');
                                $currency = optional(optional($transfert->harekets->first())->kur)->short_name ?: 'EUR';
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('transfers.show', $transfert->id) }}"><strong>#{{ $transfert->id }}</strong></a>
                                    <div class="detail-muted">Mission {{ $transfert->mission ? 'oui' : 'non' }}</div>
                                </td>
                                <td>
                                    <strong>{{ optional($transfert->servicetype)->name ?: '-' }}</strong>
                                </td>
                                <td>
                                    <strong>{{ $transfert->start_date ? \Carbon\Carbon::parse($transfert->start_date)->format('d/m/Y H:i') : '-' }}</strong>
                                    <div class="detail-muted">Fin: {{ $transfert->end_date ? \Carbon\Carbon::parse($transfert->end_date)->format('d/m/Y H:i') : '-' }}</div>
                                </td>
                                <td class="detail-route">
                                    <strong>{{ $transfert->from ?: '-' }}</strong>
                                    <div class="detail-muted">→ {{ $transfert->target ?: '-' }}</div>
                                </td>
                                <td>{{ optional($transfert->vehicule)->name ?: 'Sans véhicule' }}</td>
                                <td>{{ optional($transfert->driver)->name ?: 'Sans chauffeur' }}</td>
                                <td class="text-right">
                                    <span class="detail-money">{{ number_format($amount, 2, ',', ' ') }} {{ $currency }}</span>
                                </td>
                                <td class="detail-actions text-right">
                                    <a href="{{ route('transfers.show', $transfert->id) }}" class="btn btn-sm btn-outline-primary">Voir</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">Aucun transfert dans ce dossier.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="alert alert-light border text-center text-muted">Aucun dossier sur cette période.</div>
    @endforelse

    <div class="d-flex justify-content-between align-items-center flex-wrap mt-3">
        <div class="text-muted small">Page {{ $posts->currentPage() }} / {{ $posts->lastPage() }}</div>
        {!! $posts->appends(request()->query())->links('vendor/pagination/bootstrap-4') !!}
    </div>
</div>
