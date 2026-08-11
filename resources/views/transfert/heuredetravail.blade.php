@extends('layouts.app')
@section('style')
  <link href="{{ asset('css/daterangepicker.css') }}" rel="stylesheet">
  <style>
    .worktime-page {
        background: #f8fafc;
        padding: 16px;
        min-height: calc(100vh - 90px);
    }
    .worktime-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .worktime-title h1 {
        margin: 0;
        font-size: 24px;
        font-weight: 800;
        color: #111827;
    }
    .worktime-title small {
        color: #64748b;
        font-weight: 700;
    }
    .worktime-table-wrap {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
    }
    .worktime-table {
        margin: 0;
        min-width: 1050px;
    }
    .worktime-table thead th {
        background: #111827;
        color: #fff;
        border-color: #111827;
        font-size: 12px;
        vertical-align: middle;
        text-align: center;
    }
    .worktime-table tbody td {
        vertical-align: middle;
        text-align: center;
        font-size: 13px;
    }
    .driver-cell {
        text-align: left !important;
        min-width: 210px;
    }
    .driver-name {
        display: block;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 6px;
    }
    .worked-time {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 74px;
        border-radius: 999px;
        padding: 5px 9px;
        background: #ecfdf5;
        color: #166534;
        font-weight: 900;
    }
    .not-working {
        display: inline-flex;
        border-radius: 999px;
        padding: 5px 9px;
        background: #fef2f2;
        color: #991b1b;
        font-weight: 800;
    }

    .work-detail {
        display: grid;
        gap: 4px;
        margin-top: 6px;
        font-size: 11px;
        line-height: 1.25;
    }
    .work-detail span {
        display: inline-flex;
        width: fit-content;
        border-radius: 999px;
        padding: 3px 7px;
        font-weight: 800;
    }
    .work-detail .van-badge {
        background: #fff7ed;
        color: #9a3412;
        border: 1px solid #fed7aa;
    }
    .work-detail .amp-badge {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }
  </style>
@endsection
@section('content')
@php
    $formatMinutes = function ($minutes) {
        $minutes = (int) $minutes;
        return intdiv($minutes, 60) . ' h ' . str_pad($minutes % 60, 2, '0', STR_PAD_LEFT);
    };
@endphp

<div class="worktime-page">
    <div class="worktime-toolbar">
        <div class="worktime-title">
            <h1>Temps de travail chauffeurs</h1>
            <small>Suivi des chauffeurs sur la période affichée</small>
        </div>
        <div class="d-flex flex-wrap" style="gap: 8px;">
            <button id="exportButton" class="btn btn-outline-secondary btn-sm">
              <i class="fas fa-file-excel"></i> Exporter Excel
            </button>
            <button id="previousWeekButton" class="btn btn-outline-secondary btn-sm">
              <i class="fas fa-calendar-week"></i> Semaine précédente
            </button>
            <button id="thisWeekButton" class="btn btn-primary btn-sm">
              <i class="fas fa-calendar-week"></i> Cette semaine
            </button>
            <a class="btn btn-outline-primary btn-sm" href="{{route('vehiculescontrol')}}">Véhicules</a>
            <a class="btn btn-outline-primary btn-sm" href="{{route('driverUsage.index')}}">Utilisation conducteur</a>
            <a href="{{ route('day') }}" class="btn btn-warning btn-sm">Shuttle</a>
            <a href="{{ route('driver-calendar') }}" class="btn btn-warning btn-sm">Calendrier chauffeurs</a>
        </div>
    </div>

    <div class="worktime-table-wrap table-responsive">
        <table class="table table-sm table-hover worktime-table" id="AcentesTable">
            <thead>
                <tr>
                    <th>Chauffeur</th>
                    @for($dayNumber = 0; $dayNumber <= $gecmisgun; $dayNumber++)
                        <th @if ($dayNumber == 6) class="table-secondary text-dark" @endif>
                            Jour {{ $dayNumber + 1 }}<br>
                            {{ $dayNames[$dayNumber] }}
                        </th>
                    @endfor
                    <th>Jours non travaillés</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($totalWorkingTime as $acenteId => $workedHoursPerDay)
                    @if (isset($drivername[$acenteId]))
                        <tr>
                            <td class="driver-cell">
                                <span class="driver-name">{{ $drivername[$acenteId] }}</span>
                            </td>
                            @for($dayNumber = 0; $dayNumber <= $gecmisgun; $dayNumber++)
                                @php $workedHour = $workedHoursPerDay[$dayNumber] ?? 0; @endphp
                                <td>
                                    @if ($workedHour > 0)
                                        <span class="worked-time">{{ $formatMinutes($workedHour) }}</span>
                                        @php $details = $workDetails[$acenteId][$dayNumber] ?? []; @endphp
                                        @if(!empty($details['vans']) || !empty($details['amplitudes']))
                                            <div class="work-detail">
                                                @foreach(($details['vans'] ?? []) as $vanLabel)
                                                    <span class="van-badge">Van / sans Hermes: {{ $vanLabel }}</span>
                                                @endforeach
                                                @foreach(($details['amplitudes'] ?? []) as $ampLabel)
                                                    <span class="amp-badge">Amplitude: {{ $ampLabel }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    @else
                                        <span class="not-working">Non travaillé</span>
                                    @endif
                                </td>
                            @endfor
                            <td><strong>{{ $notWorkedCounts[$acenteId] }}</strong></td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    <small class="d-block mt-3 text-muted">Pour suivre un chauffeur, activez le suivi dans sa fiche prestataire.</small>
</div>
@endsection

@section('footer')
<script>
    document.getElementById('exportButton').addEventListener('click', function() {
        var table = document.getElementById('AcentesTable');
        var html = table.outerHTML;
        var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
        var downloadLink = document.createElement("a");
        downloadLink.href = url;
        downloadLink.download = 'temps_de_travail_chauffeurs.xls';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    });

    document.getElementById('previousWeekButton').addEventListener('click', function() {
        var currentOffset = {{ (int) $weekOffset }};
        window.location.href = "{{ route('heuredetravail', '') }}/" + (currentOffset + 1);
    });

    document.getElementById('thisWeekButton').addEventListener('click', function() {
        window.location.href = "{{ route('heuredetravail', '') }}/0";
    });
</script>
@endsection
