@extends('layouts.app')

@section('style')
<link href="{{ asset('css/daterangepicker.css') }}" rel="stylesheet">
<style>
    .files-page{background:#f8fafc;min-height:calc(100vh - 90px);padding:16px}
    .files-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:14px}
    .files-head h1{margin:0;font-size:25px;font-weight:850;color:#0f172a}
    .files-head small{color:#64748b;font-weight:750}
    .files-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 22px rgba(15,23,42,.06);overflow:hidden}
    .files-filter{display:flex;gap:8px;align-items:center;flex-wrap:wrap;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:10px;margin-bottom:12px}
    .files-table th{font-size:12px;text-transform:uppercase;color:#475569;white-space:nowrap;background:#f8fafc}
    .files-table td{vertical-align:middle;font-size:13px}
    .files-table a.sort-link{display:inline-flex;align-items:center;gap:5px;color:#334155;font-weight:850;text-decoration:none}
    .sort-mark{font-size:10px;color:#0f172a}
    .file-link{font-weight:900;color:#0f172a}
    .file-title{font-weight:850;color:#0f172a}
    .file-comment{max-width:260px;color:#64748b}
    .provider-tags{display:flex;flex-wrap:wrap;gap:4px}
    .provider-tags span{background:#eef2ff;color:#3730a3;border-radius:999px;padding:3px 7px;font-size:11px;font-weight:800}
    .status-pill{display:inline-flex;border-radius:999px;padding:4px 8px;background:#f1f5f9;font-weight:850}
    .service-counts{display:flex;gap:4px;flex-wrap:wrap}
    .service-counts span{border:1px solid #e5e7eb;border-radius:999px;padding:3px 7px;background:#fff;font-size:11px;font-weight:800;color:#475569}
    .files-page .pagination{margin:0;gap:4px;align-items:center}
    .files-page .page-link{min-width:32px;height:32px;padding:6px 10px;display:inline-flex;align-items:center;justify-content:center;font-size:13px;line-height:1}
    .files-page nav[role="navigation"] svg{width:14px!important;height:14px!important;max-width:14px!important;max-height:14px!important}
    @media(max-width:768px){.files-page{padding:10px}.files-head{display:block}.files-head .btn{margin-top:8px}.file-comment{max-width:none}}
</style>
@endsection

@section('content')
@php
    $sortLink = function ($column, $label) use ($sort, $direction) {
        $nextDirection = ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';
        $params = array_merge(request()->except(['page', 'sort', 'direction']), [
            'sort' => $column,
            'direction' => $nextDirection,
        ]);
        $mark = $sort === $column ? ($direction === 'asc' ? '▲' : '▼') : '↕';
        return '<a class="sort-link" href="'.route('posts.index', $params).'"><span>'.$label.'</span><span class="sort-mark">'.$mark.'</span></a>';
    };
    $displayRange = request('daterange') ?: (\Carbon\Carbon::parse($start_date)->format('d/m/Y').' - '.\Carbon\Carbon::parse($end_date)->format('d/m/Y'));
@endphp

<div class="files-page">
    <div class="files-head">
        <div>
            <h1>Liste des dossiers</h1>
            <small>{{ $posts->total() }} dossier(s) · tri: {{ $sort }} {{ strtoupper($direction) }}</small>
        </div>
        <a class="btn btn-success" href="{{ route('posts.create') }}">Nouveau dossier</a>
    </div>

    <form method="get" name="tarih" class="files-filter">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">
        <label class="mb-0 font-weight-bold">Lignes</label>
        <select name="per_page" id="per_page" class="form-control form-control-sm" onchange="this.form.submit()" style="width:90px">
            @foreach([25, 50, 100, 200] as $n)
                <option value="{{ $n }}" {{ $per_page == $n ? 'selected' : '' }}>{{ $n }}</option>
            @endforeach
        </select>
        <label class="mb-0 font-weight-bold">Période</label>
        <input type="text" name="daterange" class="form-control form-control-sm" value="{{ $displayRange }}" style="width:210px">
        <input type="hidden" name="start_date" id="hiddenStartDate" value="{{ request('start_date', $start_date) }}">
        <input type="hidden" name="end_date" id="hiddenEndDate" value="{{ request('end_date', $end_date) }}">
        <button type="submit" class="btn btn-outline-primary btn-sm">Filtrer</button>
    </form>

    <div class="files-card">
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
            <strong>Dossiers</strong>
            <div>{{ $posts->appends(request()->except('page'))->links('vendor/pagination/bootstrap-4') }}</div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 files-table">
                <thead>
                    <tr>
                        <th>{!! $sortLink('post_id', 'Dossier') !!}</th>
                        <th>Type prestataire</th>
                        <th>{!! $sortLink('acente_id', 'Agence') !!}</th>
                        <th>Nom</th>
                        <th>{!! $sortLink('start_date', 'Du') !!}</th>
                        <th>{!! $sortLink('end_date', 'Au') !!}</th>
                        <th>Commentaire</th>
                        <th>{!! $sortLink('status_id', 'Statut') !!}</th>
                        <th>Facture</th>
                        <th>{!! $sortLink('pax', 'Pax') !!}</th>
                        <th>Services</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posts as $post)
                        @php
                            $statusColor = optional(optional($post->status)->color)->name ?: 'secondary';
                            $serviceTotal = (int) $post->transfer_count + (int) $post->hotels_count + (int) $post->others_count + (int) $post->stock_count;
                        @endphp
                        <tr>
                            <td><a class="file-link" href="{{ route('posts.show', $post->id) }}">FP{{ $post->id }}</a></td>
                            <td>
                                <div class="provider-tags">
                                    @foreach (optional($post->acente)->firmas ?? [] as $firma)
                                        <span>{{ $firma->name }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                @if($post->acente)
                                    <a href="{{ route('acentes.show', $post->acente_id) }}"><strong>{{ $post->acente->name }}</strong></a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <div class="file-title">{{ $post->title ?: '-' }}</div>
                                @if($post->user)<div class="text-muted small">{{ $post->user->name }}</div>@endif
                            </td>
                            <td>{{ $post->start_date ? date('d/m/Y', strtotime($post->start_date)) : '-' }}</td>
                            <td>{{ $post->end_date ? date('d/m/Y', strtotime($post->end_date)) : '-' }}</td>
                            <td><div class="file-comment">{{ \Illuminate\Support\Str::limit($post->body, 90) }}</div></td>
                            <td><span class="status-pill text-{{ $statusColor }}">{{ optional($post->status)->name ?: '-' }}</span></td>
                            <td>{{ $post->resmi ?: '-' }}</td>
                            <td>{{ $post->pax ?: 0 }}@if($post->child) <span class="text-muted small">+ {{ $post->child }} enf.</span>@endif</td>
                            <td>
                                <div class="service-counts" title="{{ $serviceTotal }} service(s)">
                                    <span>T {{ $post->transfer_count }}</span>
                                    <span>H {{ $post->hotels_count }}</span>
                                    <span>O {{ $post->others_count }}</span>
                                    <span>S {{ $post->stock_count }}</span>
                                </div>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('posts.edit', $post->id) }}" class="btn btn-outline-primary btn-sm">Modifier</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="text-center text-muted py-4">Aucun dossier trouvé.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 d-flex justify-content-center">
            {{ $posts->appends(request()->except('page'))->links('vendor/pagination/bootstrap-4') }}
        </div>
    </div>
</div>
@endsection

@section('footer')
<script src="{{ asset('/js/moment.min.js') }}"></script>
<script src="{{ asset('/js/daterangepicker.js') }}"></script>
<script>
  $('input[name="daterange"]').daterangepicker({
    locale: { format: 'DD/MM/YYYY' }
  });

  $('input[name="daterange"]').on('apply.daterangepicker', function(ev, picker) {
    $('#hiddenStartDate').val(picker.startDate.format('YYYY-MM-DD'));
    $('#hiddenEndDate').val(picker.endDate.format('YYYY-MM-DD'));
    document.forms['tarih'].submit();
  });
</script>
@endsection
