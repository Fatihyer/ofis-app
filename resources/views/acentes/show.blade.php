@extends('layouts.app')

@section('style')
<link href="{{ asset('css/daterangepicker.css') }}" rel="stylesheet">
<style>
    .acente-page { background:#f8fafc; padding:14px; border-radius:8px; }
    .acente-header { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap; margin-bottom:12px; }
    .acente-title h1 { margin:0; font-size:24px; font-weight:850; color:#0f172a; }
    .acente-title .meta { color:#64748b; font-size:13px; margin-top:4px; }
    .acente-filter { display:flex; gap:8px; align-items:end; flex-wrap:wrap; padding:12px; border:1px solid #e5e7eb; background:#fff; border-radius:8px; margin-bottom:12px; }
    .acente-filter label { display:block; color:#64748b; font-size:12px; font-weight:800; margin-bottom:4px; }
    .acente-tabs { border:1px solid #e5e7eb; border-radius:8px; background:#fff; overflow:hidden; }
    .acente-tabs .card-header { background:#fff; border-bottom:1px solid #e5e7eb; padding-bottom:0; }
    .acente-tabs .nav-link { color:#475569; font-weight:750; }
    .acente-tabs .nav-link.active { color:#0f172a; border-bottom-color:#fff; }
    .acente-tabs .card-body { padding:14px; }
    table.auto { table-layout:auto; }
    .table th, .table td { padding:6px 8px; vertical-align:middle; }
    @media(max-width:767.98px){ .acente-page{padding:8px}.acente-filter .form-control{min-width:220px} }
</style>
@endsection

@section('title', '| Prestataire')

@section('content')
@php
    $startDate = $start_date ?? request('start_date');
    $endDate = $end_date ?? request('end_date');
    $displayRange = request('daterange') ?: (
        $startDate && $endDate
            ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y')
            : ''
    );
    $activeSrc = request('src');
    $tabUrl = function ($src = null) use ($acente, $displayRange, $startDate, $endDate) {
        $params = array_filter([
            'src' => $src,
            'daterange' => $displayRange,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ], fn ($value) => $value !== null && $value !== '');

        return route('acentes.show', $acente->id) . (count($params) ? '?' . http_build_query($params) : '');
    };
@endphp

<div class="acente-page">
    <div class="acente-header">
        <div class="acente-title">
            <h1>{{ $acente->name }}</h1>
            <div class="meta">
                Prestataire #{{ $acente->id }}
                @if($displayRange)
                    · Période: {{ $displayRange }}
                @endif
            </div>
        </div>
        <a href="{{ route('acentes.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-arrow-left"></i> Liste des prestataires
        </a>
    </div>

    <div class="col-12 grid-margin p-0">
    <div class="card acente-tabs">
        <form method="get" name="tarih" class="acente-filter">
            <div>
                <label>Période</label>
                <input type="text" name="daterange" class="form-control" value="{{ $displayRange }}" autocomplete="off" />
            </div>
            <input type="hidden" name="start_date" id="hiddenStartDate" value="{{ $startDate }}" />
            <input type="hidden" name="end_date" id="hiddenEndDate" value="{{ $endDate }}" />
            <input type="hidden" name="src" value="{{ $activeSrc }}" />
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-filter"></i> Filtrer
            </button>
            <a href="{{ route('acentes.show', $acente->id) }}?src={{ $activeSrc }}" class="btn btn-outline-secondary">
                Réinitialiser
            </a>
        </form>

        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link {{ $activeSrc == false ? 'active' : '' }}" href="{{ $tabUrl() }}">@lang('app.info')</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeSrc == 'file' ? 'active' : '' }}" href="{{ $tabUrl('file') }}">@lang('app.file')</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeSrc == 'invoice' ? 'active' : '' }}" href="{{ $tabUrl('invoice') }}">@lang('app.invoice')</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeSrc == 'service' ? 'active' : '' }}" href="{{ $tabUrl('service') }}">@lang('app.service')</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeSrc == 'offset' ? 'active' : '' }}" href="{{ $tabUrl('offset') }}">Offset</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeSrc == 'stock' ? 'active' : '' }}" href="{{ $tabUrl('stock') }}">Stock</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeSrc == 'balance' ? 'active' : '' }}" href="{{ $tabUrl('balance') }}">@lang('app.balance')</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeSrc == 'message' ? 'active' : '' }}" href="{{ $tabUrl('message') }}">@lang('app.message')</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeSrc == 'detailled' ? 'active' : '' }}" href="{{ $tabUrl('detailled') }}">Dossiers détaillés</a>
                </li>
            </ul>
        </div>

        <div class="card-body">
            @if (request('src') == 'file')
                @include('acentes.partials.files', ['posts' => $posts])
            @elseif (request('src') == 'invoice')
                @include('acentes.partials.invoices', ['invoices' => $invoices, 'acente' => $acente])
            @elseif (request('src') == 'service')
                @include('acentes.partials.services', ['transfers' => $transfers])
            @elseif (request('src') == 'balance')
                @include('acentes.partials.balance', ['harekets' => $harekets, 'sum' => $sum, 'acente' => $acente])
            @elseif (request('src') == 'message')
                @include('acentes.partials.messages', ['acente' => $acente])
            @elseif (request('src') == 'offset')
                @include('acentes.partials.offsets', ['offsets' => $offsets])
            @elseif (request('src') == 'stock')
                @include('acentes.partials.stocks', ['stocks' => $stocks])
            @elseif (request('src') == 'detailled')
                @include('acentes.partials.detailled', ['posts' => $posts])
            @else
                @include('acentes.partials.info', ['acente' => $acente, 'files' => $files, 'storeFile' => $storeFile])
            @endif
        </div>
    </div>
    </div>
</div>
@endsection

@section('footer')
<script src="{{ asset('/js/moment.min.js') }}"></script>
<script src="{{ asset('/js/daterangepicker.js') }}"></script>
<script>
    const startDate = "{{ $startDate }}";
    const endDate = "{{ $endDate }}";

    $('input[name="daterange"]').daterangepicker({
        locale: {
            format: 'DD/MM/YYYY',
            applyLabel: 'Appliquer',
            cancelLabel: 'Annuler',
            daysOfWeek: ['Di','Lu','Ma','Me','Je','Ve','Sa'],
            monthNames: ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre']
        },
        autoUpdateInput: true,
        startDate: startDate ? moment(startDate, 'YYYY-MM-DD') : moment().subtract(1, 'year'),
        endDate: endDate ? moment(endDate, 'YYYY-MM-DD') : moment().add(2, 'years')
    }).on('apply.daterangepicker', function(ev, picker) {
        $('#hiddenStartDate').val(picker.startDate.format('YYYY-MM-DD'));
        $('#hiddenEndDate').val(picker.endDate.format('YYYY-MM-DD'));
        document.forms['tarih'].submit();
    });

    $(function () {
        $('[data-bs-toggle="popover"]').popover();
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
</script>
<script>
    var exportButton = document.getElementById('exportButton');
    if (exportButton) {
        exportButton.addEventListener('click', function() {
            var table = document.getElementById('AcentesTable');
            if (!table) return;
            var html = table.outerHTML;
            var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            var downloadLink = document.createElement("a");
            downloadLink.href = url;
            downloadLink.download = 'acentes.xls';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        });
    }
  </script>
  <script>
var loadDrivingHoursButton = document.getElementById('load-driving-hours');
if (loadDrivingHoursButton) {
loadDrivingHoursButton.addEventListener('click', function () {
    const resultBox = document.getElementById('hermes-result');
    resultBox.innerHTML = 'Yükleniyor...';

    fetch('{{ route('acentes.hermes.hours', $acente->id) }}')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                resultBox.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
            } else {
                resultBox.innerHTML = `
                    <div class="alert alert-success">
                        <strong>${data.acente}</strong> için<br>
                        <strong>${data.period}</strong> tarihleri arasında<br>
                        <strong>${data.total_hours}</strong> saat sürüş yapıldı.
                    </div>
                `;
            }
        })
        .catch(error => {
            resultBox.innerHTML = `<div class="alert alert-danger">Hata: ${error.message}</div>`;
        });
});
}
</script>


@endsection
