@extends('layouts.app')

@section('title', '| Dossiers sans facture')

@section('content')
<style>
.uninv-page { max-width: 1320px; margin: 0 auto; color: #172033; }
.uninv-head { display: flex; justify-content: space-between; gap: 12px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 14px; }
.uninv-head h1 { font-size: 24px; margin: 0; font-weight: 850; }
.uninv-card { border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; box-shadow: 0 1px 2px rgba(15,23,42,.05); }
.uninv-filter { display: flex; gap: 8px; flex-wrap: wrap; align-items: end; padding: 12px; }
.uninv-filter label { font-size: 12px; color: #64748b; font-weight: 750; margin-bottom: 3px; }
.uninv-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 10px; margin: 14px 0; }
.uninv-tile { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; background: #f8fafc; }
.uninv-tile small { display: block; color: #64748b; font-weight: 750; margin-bottom: 5px; }
.uninv-tile strong { font-size: 24px; line-height: 1; }
.uninv-table th { background: #f1f5f9; color: #334155; font-weight: 800; white-space: nowrap; }
.uninv-table td, .uninv-table th { vertical-align: middle; }
.file-link { font-weight: 850; text-decoration: none; }
.service-pills { display: flex; gap: 5px; flex-wrap: wrap; justify-content: flex-end; }
.service-pill { border-radius: 999px; padding: 3px 8px; font-size: 12px; font-weight: 800; background: #eef2ff; color: #3730a3; }
.service-pill.muted { background: #f1f5f9; color: #64748b; }
.excluded-badge { display: inline-flex; align-items: center; gap: 4px; border-radius: 999px; padding: 3px 8px; background: #fff7ed; color: #9a3412; font-size: 12px; font-weight: 800; }
.actions { display: flex; gap: 6px; justify-content: flex-end; flex-wrap: wrap; }
.actions form { margin: 0; }
.bulk-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; }
.bulk-toolbar .selected-count { font-weight: 800; color: #334155; }
.bulk-check { width: 18px; height: 18px; cursor: pointer; }
@media (max-width: 991.98px) { .uninv-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 767.98px) { .uninv-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .uninv-filter .form-control, .uninv-filter .form-select { min-width: 150px; } }
</style>

<div class="uninv-page">
    <div class="uninv-head">
        <div>
            <h1>Dossiers sans facture</h1>
            <div class="text-muted">Dossiers non facturés avec services actifs, hors congés.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($showExcluded)
                <a href="{{ route('posts.uninvoiced') }}" class="btn btn-outline-primary btn-sm"><i class="fa fa-list"></i> Dossiers à facturer</a>
            @else
                <a href="{{ route('posts.uninvoiced', ['show_excluded' => 1]) }}" class="btn btn-outline-warning btn-sm"><i class="fa fa-ban"></i> Afficher exclus ({{ count($excludedPostIds) }})</a>
            @endif
            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-file-text-o"></i> Factures
            </a>
        </div>
    </div>

    <div class="uninv-card mb-3">
        <form method="GET" action="{{ route('posts.uninvoiced') }}" class="uninv-filter">
            @if($showExcluded)
                <input type="hidden" name="show_excluded" value="1">
            @endif
            <div>
                <label>Début</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="form-control">
            </div>
            <div>
                <label>Fin</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="form-control">
            </div>
            <div>
                <label>Trier par</label>
                <select name="sort" class="form-control form-select">
                    <option value="agence" @selected($sort === 'agence')>Agence</option>
                    <option value="date" @selected($sort === 'date')>Date</option>
                    <option value="dossier" @selected($sort === 'dossier')>Dossier</option>
                </select>
            </div>
            <div>
                <label>Affichage</label>
                <select name="per_page" class="form-control form-select">
                    @foreach([50, 100, 200] as $size)
                        <option value="{{ $size }}" @selected((int) $perPage === $size)>{{ $size }} lignes</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit"><i class="fa fa-filter"></i> Filtrer</button>
            <a class="btn btn-outline-secondary" href="{{ route('posts.uninvoiced') }}"><i class="fa fa-refresh"></i> Réinitialiser</a>
        </form>
    </div>

    <div class="uninv-grid">
        <div class="uninv-tile"><small>{{ $showExcluded ? 'Dossiers exclus' : 'Dossiers' }}</small><strong>{{ $totals['file_count'] }}</strong></div>
        <div class="uninv-tile"><small>Services</small><strong>{{ $totals['service_count'] }}</strong></div>
        <div class="uninv-tile"><small>Transferts</small><strong>{{ $totals['transfer_count'] }}</strong></div>
        <div class="uninv-tile"><small>Hôtels</small><strong>{{ $totals['hotel_count'] }}</strong></div>
        <div class="uninv-tile"><small>Autres</small><strong>{{ $totals['other_count'] }}</strong></div>
        <div class="uninv-tile"><small>Stocks</small><strong>{{ $totals['stock_count'] }}</strong></div>
    </div>

    <div class="uninv-card table-responsive">
        @if($showExcluded)
            <div class="alert alert-warning m-3 mb-0"><strong>Dossiers non facturables</strong> - Ces dossiers sont masqués de la liste principale.</div>
        @endif
        @if(!$showExcluded)
            <form id="bulkUninvoicedForm" method="POST" action="{{ route('posts.uninvoiced.bulk-exclude') }}" onsubmit="return confirm('Marquer les dossiers sélectionnés comme non facturables ?');">
                @csrf
                <div class="bulk-toolbar">
                    <div>
                        <span class="selected-count"><span id="bulkSelectedCount">0</span> sélectionné(s)</span>
                        <span class="text-muted small ml-2">Cochez les dossiers à exclure de la facturation.</span>
                    </div>
                    <button id="bulkExcludeButton" class="btn btn-sm btn-outline-warning" type="submit" disabled><i class="fa fa-ban"></i> Ne pas facturer la sélection</button>
                </div>
            </form>
        @endif
        <table class="table table-hover mb-0 uninv-table">
            <thead>
                <tr>
                    @if(!$showExcluded)
                        <th style="width:42px;"><input type="checkbox" id="bulkSelectAll" class="bulk-check" title="Tout sélectionner"></th>
                    @endif
                    <th>Dossier</th>
                    <th>Agence @if($sort === 'agence')<i class="fa fa-sort-alpha-asc text-muted"></i>@endif</th>
                    <th>
                        <a href="{{ route('posts.uninvoiced', array_merge(request()->query(), ['sort' => 'date'])) }}" class="text-decoration-none text-dark">
                            Période @if($sort === 'date')<i class="fa fa-sort-amount-desc text-muted"></i>@endif
                        </a>
                    </th>
                    <th class="text-center">Pax</th>
                    <th>Ouvert par</th>
                    <th class="text-end">Services</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($posts as $row)
                    @php
                        $serviceTotal = (int) $row->transfer_count + (int) $row->hotel_count + (int) $row->other_count + (int) $row->stock_count;
                        $periodStart = $row->first_service_date ?: $row->start_date;
                        $periodEnd = $row->last_service_date ?: $row->end_date;
                    @endphp
                    <tr>
                        @if(!$showExcluded)
                            <td><input form="bulkUninvoicedForm" type="checkbox" name="post_ids[]" value="{{ $row->id }}" class="bulk-check bulk-row-check"></td>
                        @endif
                        <td>
                            <a class="file-link" href="{{ route('posts.show', $row->id) }}">#{{ $row->id }}</a>
                            @if(($row->billing_status ?? null) === 'do_not_invoice' || in_array((int) $row->id, $excludedPostIds, true))
                                <span class="excluded-badge"><i class="fa fa-ban"></i> Non facturable</span>
                            @elseif(($row->billing_status ?? null) === 'invoiced')
                                <span class="badge badge-success">Facturé</span>
                            @else
                                <span class="badge badge-warning">À facturer</span>
                            @endif
                            @if(!empty($row->payment_destination) || !empty($row->payment_status))
                                <div class="small text-muted">Paiement: {{ $row->payment_destination ?: 'non défini' }} / {{ $row->payment_status ?: 'non encaissé' }}</div>
                            @endif
                            <div class="small text-muted">{{ $row->title ?: 'Sans titre' }}</div>
                        </td>
                        <td>
                            @if($row->acente_id)
                                <a href="{{ route('acentes.show', $row->acente_id) }}">{{ $row->acente_name }}</a>
                            @else
                                <span class="text-muted">{{ $row->acente_name }}</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $periodStart ? \Carbon\Carbon::parse($periodStart)->format('d/m/Y') : '-' }}</strong>
                            <div class="small text-muted">au {{ $periodEnd ? \Carbon\Carbon::parse($periodEnd)->format('d/m/Y') : '-' }}</div>
                        </td>
                        <td class="text-center">{{ $row->pax ?: '-' }}</td>
                        <td>{{ $row->user_name }}</td>
                        <td class="text-end">
                            <div><strong>{{ $serviceTotal }}</strong></div>
                            <div class="service-pills">
                                <span class="service-pill {{ (int) $row->transfer_count ? '' : 'muted' }}">T {{ (int) $row->transfer_count }}</span>
                                <span class="service-pill {{ (int) $row->hotel_count ? '' : 'muted' }}">H {{ (int) $row->hotel_count }}</span>
                                <span class="service-pill {{ (int) $row->other_count ? '' : 'muted' }}">A {{ (int) $row->other_count }}</span>
                                <span class="service-pill {{ (int) $row->stock_count ? '' : 'muted' }}">S {{ (int) $row->stock_count }}</span>
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="actions">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('posts.show', $row->id) }}"><i class="fa fa-eye"></i> Voir</a>
                                @if($showExcluded)
                                    <form method="POST" action="{{ route('posts.uninvoiced.restore', $row->id) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success" type="submit"><i class="fa fa-undo"></i> Réactiver</button>
                                    </form>
                                @else
                                    <a class="btn btn-sm btn-outline-success" href="{{ route('invoices.create', ['post_id' => $row->id]) }}"><i class="fa fa-plus"></i> Facturer</a>
                                    <form method="POST" action="{{ route('posts.uninvoiced.exclude', $row->id) }}" onsubmit="return confirm('Marquer ce dossier comme non facturable ?');">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-warning" type="submit"><i class="fa fa-ban"></i> Ne pas facturer</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $showExcluded ? 7 : 8 }}" class="text-center text-muted py-4">Aucun dossier sans facture pour cette sélection.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
        <div class="text-muted small">Services congé exclus: {{ implode(', ', $congeIds) ?: '-' }}</div>
        {!! $posts->links('vendor/pagination/bootstrap-4') !!}
    </div>
</div>
@endsection


@section('scripts')
<script>
(function () {
    function refreshBulkSelection() {
        var checked = $('.bulk-row-check:checked').length;
        var total = $('.bulk-row-check').length;
        $('#bulkSelectedCount').text(checked);
        $('#bulkExcludeButton').prop('disabled', checked === 0);
        $('#bulkSelectAll').prop('checked', total > 0 && checked === total);
    }

    $(document).on('change', '#bulkSelectAll', function () {
        $('.bulk-row-check').prop('checked', $(this).is(':checked'));
        refreshBulkSelection();
    });

    $(document).on('change', '.bulk-row-check', refreshBulkSelection);
    refreshBulkSelection();
})();
</script>
@endsection
