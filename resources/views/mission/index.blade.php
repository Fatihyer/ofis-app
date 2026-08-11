@extends(Auth::user()->hasAnyRole(['Superadmin', 'Admin', 'ofis', 'transport']) || Auth::user()->hasAnyPermission(['transfers.operations', 'ofis', 'transport']) ? 'layouts.app' : 'layouts.kaptan')

@section('content')
@php
    $selected = \Carbon\Carbon::parse($selectedDate ?? now()->format('Y-m-d'));
    $notStartedTransfers = $notStartedTransfers ?? collect();
    $notStarted = $notStartedTransfers->count();
    $total = $missions->count() + $notStarted;
    $active = $missions->filter(fn($mission) => $mission->hareket && !$mission->finish_depot)->count();
    $finished = $missions->filter(fn($mission) => $mission->finish_depot)->count();
    $unfinished = $missions->filter(fn($mission) => $mission->hareket && !$mission->finish)->count();
    $totalKm = $missions->sum(function ($mission) {
        return ($mission->finish_km !== null && $mission->depart_km !== null) ? max(0, $mission->finish_km - $mission->depart_km) : 0;
    });
    $formatTime = function ($value) {
        return $value ? \Carbon\Carbon::parse($value)->format('H:i') : '-';
    };
@endphp

<style>
.missions-page {
    max-width: 1440px;
    margin: 0 auto;
    color: #1f2937;
}
.missions-hero {
    border: 1px solid #dbe4ef;
    border-radius: 8px;
    background: #fff;
    padding: 16px;
    box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
}
.missions-hero h1 {
    margin: 0;
    font-size: 24px;
    font-weight: 850;
    letter-spacing: 0;
}
.missions-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: flex-end;
}
.stat-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 10px;
    margin-top: 14px;
}
.stat-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f8fafc;
    padding: 10px 12px;
}
.stat-card span {
    display: block;
    color: #64748b;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
}
.stat-card strong {
    display: block;
    color: #0f172a;
    font-size: 22px;
    line-height: 1.15;
    font-weight: 850;
}
.filter-panel, .missions-table-panel {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
    margin-top: 14px;
    overflow: hidden;
}
.panel-head {
    padding: 11px 14px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 850;
}
.panel-body { padding: 14px; }
.missions-table-wrap {
    width: 100%;
    overflow-x: auto;
}
.missions-table {
    width: 100%;
    min-width: 1180px;
    table-layout: fixed;
    margin-bottom: 0;
    font-size: 13px;
}
.missions-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #f1f5f9;
    border-bottom: 1px solid #cbd5e1;
    color: #334155;
    white-space: nowrap;
    font-weight: 850;
}
.missions-table td {
    vertical-align: middle;
    max-width: 220px;
    overflow-wrap: anywhere;
    word-break: break-word;
}
.missions-table th:nth-child(5),
.missions-table td:nth-child(5) {
    width: 250px;
    max-width: 250px;
    white-space: normal;
}
.mission-main-link {
    font-weight: 850;
    text-decoration: none;
}
.muted-line {
    display: block;
    color: #64748b;
    font-size: 12px;
}
.stage-pills {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 5px;
    min-width: 0;
    max-width: 100%;
}
.stage-pill {
    border: 1px solid #e2e8f0;
    border-radius: 7px;
    background: #f8fafc;
    color: #64748b;
    padding: 5px 6px;
    text-align: center;
    font-size: 11px;
    font-weight: 850;
    line-height: 1.25;
    white-space: normal;
    overflow-wrap: anywhere;
}
.stage-pill.done {
    border-color: #86efac;
    background: #dcfce7;
    color: #166534;
}
.stage-pill.late {
    border-color: #fecaca;
    background: #fef2f2;
    color: #b91c1c;
}
.km-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border-radius: 999px;
    padding: 4px 8px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 850;
}
.confirm-block {
    min-width: 165px;
    font-size: 12px;
}
.confirm-block .btn {
    padding: 2px 8px;
    font-size: 12px;
}
.mission-office-note {
    margin-top: 6px;
    padding: 6px 8px;
    border-left: 3px solid #0d6efd;
    background: #eff6ff;
    color: #1e3a8a;
    border-radius: 6px;
    font-size: 12px;
    overflow-wrap: anywhere;
}
.mission-edit-box {
    min-width: 260px;
    max-width: 100%;
}
.mission-edit-box summary {
    cursor: pointer;
    list-style: none;
}
.mission-edit-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 6px;
    margin-top: 8px;
}
.mission-edit-grid label, .mission-note-field label {
    display: block;
    margin-bottom: 0;
    font-size: 11px;
    font-weight: 800;
    color: #475569;
}
.mission-edit-grid input,
.mission-note-field textarea {
    width: 100%;
    font-size: 12px;
}
@media (max-width: 991px) {
    .stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .missions-actions { justify-content: flex-start; margin-top: 10px; }
}
@media (max-width: 560px) {
    .stat-grid { grid-template-columns: 1fr; }
    .missions-hero h1 { font-size: 20px; }
}
</style>

<div class="missions-page">
    <div class="missions-hero">
        <div class="row align-items-start">
            <div class="col-lg-7">
                <h1>Missions chauffeurs</h1>
                <div class="text-muted mt-1">{{ $selected->format('d/m/Y') }} - suivi des départs, arrivées et retours dépôt</div>
            </div>
            <div class="col-lg-5">
                <div class="missions-actions">
                    <button class="btn btn-outline-secondary btn-sm js-shift-day" data-days="-1">Hier</button>
                    <a href="{{ route('missionlist') }}" class="btn btn-primary btn-sm">Aujourd'hui</a>
                    <button class="btn btn-outline-secondary btn-sm js-shift-day" data-days="1">Demain</button>
                </div>
            </div>
        </div>
        <div class="stat-grid">
            <div class="stat-card"><span>Missions</span><strong>{{ $total }}</strong></div>
            <div class="stat-card"><span>À démarrer</span><strong>{{ $notStarted }}</strong></div>
            <div class="stat-card"><span>En cours</span><strong>{{ $active }}</strong></div>
            <div class="stat-card"><span>Déposes non faites</span><strong>{{ $unfinished }}</strong></div>
            <div class="stat-card"><span>Terminées dépôt</span><strong>{{ $finished }}</strong></div>
            <div class="stat-card"><span>Kilomètres</span><strong>{{ $totalKm }}</strong></div>
        </div>
    </div>

    <div class="filter-panel">
        <div class="panel-head">Filtres</div>
        <div class="panel-body">
            <form method="POST" action="{{ route('missions.byDate') }}" id="dateForm" class="form-inline">
                @csrf
                <label for="datepicker" class="mr-2 font-weight-bold">Date</label>
                <input type="date" class="form-control mr-2" id="datepicker" name="selected_date" value="{{ $selected->format('Y-m-d') }}">
                <button type="submit" class="btn btn-primary">Afficher</button>
            </form>
        </div>
    </div>

    <div class="missions-table-panel">
        <div class="panel-head">Liste des missions</div>
        <div class="missions-table-wrap">
            <table class="table table-bordered table-hover missions-table">
                <thead>
                    <tr>
                        <th>Dossier</th>
                        <th>Chauffeur / Véhicule</th>
                        <th>Service</th>
                        <th>Horaires prévus</th>
                        <th>Étapes réelles</th>
                        <th>Durée</th>
                        <th>Km</th>
                        <th>Permanence</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($notStartedTransfers as $transfer)
                    @php
                        $start = \Carbon\Carbon::parse($transfer->start_date);
                        $end = \Carbon\Carbon::parse($transfer->end_date);
                        $ofis = $transfer->ofis_start ? \Carbon\Carbon::parse($transfer->ofis_start) : null;
                    @endphp
                    <tr class="table-warning">
                        <td>
                            <a class="mission-main-link" href="{{ route('transfers.show', $transfer->id) }}">Transfert #{{ $transfer->id }}</a>
                            <span class="muted-line">Dossier <a href="{{ route('posts.show', $transfer->post_id) }}">#{{ $transfer->post_id }}</a></span>
                            <span class="muted-line"><span class="badge bg-warning text-dark">Mission non démarrée</span></span>
                            <span class="muted-line">{{ optional(optional($transfer->post)->acente)->name }}</span>
                        </td>
                        <td>
                            <strong>{{ optional($transfer->driver)->name ?? '-' }}</strong>
                            <span class="muted-line">{{ optional($transfer->vehicule)->name ?? '-' }}</span>
                            <span class="muted-line">{{ optional($transfer->vehicule)->plaka ?? '' }}</span>
                        </td>
                        <td>
                            <strong>{{ optional($transfer->servicetype)->name ?? '-' }}</strong>
                            <span class="muted-line">{{ $transfer->from }} -> {{ $transfer->target }}</span>
                        </td>
                        <td>
                            <span class="muted-line">En route: <strong>{{ $ofis ? $ofis->format('H:i') : '-' }}</strong></span>
                            <span class="muted-line">PEC: <strong>{{ $start->format('H:i') }}</strong></span>
                            <span class="muted-line">Fin: <strong>{{ $end->format($end->isSameDay($start) ? 'H:i' : 'd/m H:i') }}</strong></span>
                        </td>
                        <td>
                            <div class="stage-pills">
                                <span class="stage-pill late">En route -</span>
                                <span class="stage-pill">Sur place -</span>
                                <span class="stage-pill">À bord -</span>
                                <span class="stage-pill">Dépose -</span>
                            </div>
                        </td>
                        <td><strong>-</strong></td>
                        <td><span class="text-muted">-</span></td>
                        <td><span class="text-danger">Mission pas encore démarrée</span></td>
                        <td>
                            <a href="{{ route('mission', $transfer->id) }}" class="btn btn-sm btn-warning">Démarrer</a>
                            <a href="{{ route('transfers.show', $transfer->id) }}" class="btn btn-sm btn-outline-secondary">Transfert</a>
                        </td>
                    </tr>
                @endforeach
                @forelse ($missions as $mission)
                    @php
                        $transfer = $mission->transfer;
                        $start = $transfer ? \Carbon\Carbon::parse($transfer->start_date) : null;
                        $end = $transfer ? \Carbon\Carbon::parse($transfer->end_date) : null;
                        $ofis = $transfer && $transfer->ofis_start ? \Carbon\Carbon::parse($transfer->ofis_start) : null;
                        $missionStart = $mission->hareket ? \Carbon\Carbon::parse($mission->hareket) : null;
                        $missionFinish = $mission->finish ? \Carbon\Carbon::parse($mission->finish) : null;
                        $duration = null;
                        if ($missionFinish) {
                            $durationStart = $missionStart ?: $ofis ?: $start;
                            $duration = $durationStart ? $durationStart->diff($missionFinish)->format('%H:%I') : null;
                        }
                        $kmDone = ($mission->finish_km !== null && $mission->depart_km !== null) ? max(0, $mission->finish_km - $mission->depart_km) : null;
                        $isLateStart = $ofis && $missionStart && $missionStart->gt($ofis->copy()->addMinutes(10));
                        $isLateFinish = $end && $missionFinish && $missionFinish->gt($end->copy()->addMinutes(10));
                    @endphp
                    <tr>
                        <td>
                            <a class="mission-main-link" href="{{ route('transfers.show', $mission->transfer_id) }}">Transfert #{{ $mission->transfer_id }}</a>
                            @if($transfer)
                                <span class="muted-line">Dossier <a href="{{ route('posts.show', $transfer->post_id) }}">#{{ $transfer->post_id }}</a></span>
                                <span class="muted-line">Mission <a href="{{ route('mission', $mission->transfer_id) }}">#{{ $mission->id }}</a></span>
                                <span class="muted-line">{{ optional(optional($transfer->post)->acente)->name }}</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ optional(optional($transfer)->driver)->name ?? '-' }}</strong>
                            <span class="muted-line">{{ optional(optional($transfer)->vehicule)->name ?? '-' }}</span>
                            <span class="muted-line">{{ optional(optional($transfer)->vehicule)->plaka ?? '' }}</span>
                        </td>
                        <td>
                            <strong>{{ optional(optional($transfer)->servicetype)->name ?? '-' }}</strong>
                            @if($transfer)<span class="muted-line">{{ $transfer->from }} -> {{ $transfer->target }}</span>@endif
                        </td>
                        <td>
                            @if($transfer)
                                <span class="muted-line">En route: <strong>{{ $ofis ? $ofis->format('H:i') : '-' }}</strong></span>
                                <span class="muted-line">PEC: <strong>{{ $start ? $start->format('H:i') : '-' }}</strong></span>
                                <span class="muted-line">Fin: <strong>{{ $end ? $end->format($end->isSameDay($start) ? 'H:i' : 'd/m H:i') : '-' }}</strong></span>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <div class="stage-pills">
                                <span class="stage-pill {{ $mission->hareket ? 'done' : '' }} {{ $isLateStart ? 'late' : '' }}">En route {{ $formatTime($mission->hareket) }}</span>
                                <span class="stage-pill {{ $mission->surplace ? 'done' : '' }}">Sur place {{ $formatTime($mission->surplace) }}</span>
                                <span class="stage-pill {{ $mission->taked ? 'done' : '' }}">À bord {{ $formatTime($mission->taked) }}</span>
                                <span class="stage-pill {{ $mission->finish ? 'done' : '' }} {{ $isLateFinish ? 'late' : '' }}">Dépose {{ $formatTime($mission->finish) }}</span>
                            </div>
                            @if($mission->office_note)
                                <div class="mission-office-note">
                                    <strong>Note bureau:</strong> {{ $mission->office_note }}
                                    @if($mission->office_note_updated_at)
                                        <span class="muted-line">{{ optional($mission->officeNoteUser)->name ?? 'Bureau' }} · {{ $mission->office_note_updated_at->format('d/m H:i') }}</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $duration ?? '-' }}</strong>
                            @if($mission->finish_depot)<span class="muted-line">Dépôt: {{ $formatTime($mission->finish_depot) }}</span>@endif
                        </td>
                        <td>
                            @if($mission->depart_km !== null)<span class="muted-line">Départ: {{ $mission->depart_km }}</span>@endif
                            @if($mission->finish_km !== null)<span class="muted-line">Retour: {{ $mission->finish_km }}</span>@endif
                            @if($kmDone !== null)<span class="km-chip">{{ $kmDone }} km</span>@else<span class="text-muted">-</span>@endif
                        </td>
                        <td>
                            <div class="confirm-block">
                                @if ($mission->start_user_id)
                                    <strong>Start:</strong> {{ optional($mission->startuser)->name ?? '-' }}
                                    @if (!$mission->confirmed_at)
                                        <button class="btn btn-primary confirmButton ml-1" data-mission-id="{{ $mission->id }}" data-button-type="start">Confirmer</button>
                                    @else
                                        <span class="muted-line">{{ $formatTime($mission->confirmed_at) }}</span>
                                    @endif
                                @endif
                                @if ($mission->user_id)
                                    <div class="mt-1"><strong>Finish:</strong> {{ optional($mission->user)->name ?? '-' }}
                                    @if (!$mission->finish_confirmed_at)
                                        <button class="btn btn-danger confirmButton ml-1" data-mission-id="{{ $mission->id }}" data-button-type="end">Confirmer</button>
                                    @else
                                        <span class="muted-line">{{ $formatTime($mission->finish_confirmed_at) }}</span>
                                    @endif</div>
                                @elseif($mission->start_user_id)
                                    <span class="text-danger d-block mt-1">Mission non terminée</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('mission', $mission->transfer_id) }}" class="btn btn-sm btn-outline-primary">Mission</a>
                            <a href="{{ route('transfers.show', $mission->transfer_id) }}" class="btn btn-sm btn-outline-secondary">Transfert</a>
                            @if($canEditMissions ?? false)
                                <details class="mission-edit-box mt-2">
                                    <summary class="btn btn-sm btn-outline-dark">Modifier</summary>
                                    <form method="POST" action="{{ route('update_times') }}" class="mt-2">
                                        @csrf
                                        <input type="hidden" name="transfers_id" value="{{ $mission->transfer_id }}">
                                        <div class="mission-edit-grid">
                                            <label>En route
                                                <input type="datetime-local" name="new_hareket" class="form-control form-control-sm" value="{{ $mission->hareket ? \Carbon\Carbon::parse($mission->hareket)->format('Y-m-d\TH:i') : '' }}">
                                            </label>
                                            <label>Sur place
                                                <input type="datetime-local" name="new_surplace" class="form-control form-control-sm" value="{{ $mission->surplace ? \Carbon\Carbon::parse($mission->surplace)->format('Y-m-d\TH:i') : '' }}">
                                            </label>
                                            <label>À bord
                                                <input type="datetime-local" name="new_taked" class="form-control form-control-sm" value="{{ $mission->taked ? \Carbon\Carbon::parse($mission->taked)->format('Y-m-d\TH:i') : '' }}">
                                            </label>
                                            <label>Dépose
                                                <input type="datetime-local" name="new_finish" class="form-control form-control-sm" value="{{ $mission->finish ? \Carbon\Carbon::parse($mission->finish)->format('Y-m-d\TH:i') : '' }}">
                                            </label>
                                        </div>
                                        <div class="mission-note-field mt-2">
                                            <label>Note bureau</label>
                                            <textarea name="office_note" class="form-control form-control-sm" rows="2">{{ $mission->office_note }}</textarea>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-success mt-2">Enregistrer</button>
                                    </form>
                                </details>
                            @endif
                        </td>
                    </tr>
                @empty
                    @if($notStartedTransfers->isEmpty())
                        <tr><td colspan="9" class="text-center text-muted py-4">Aucune mission pour cette date.</td></tr>
                    @endif
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('footer')
<script>
document.querySelectorAll('.js-shift-day').forEach(function(button) {
    button.addEventListener('click', function() {
        var dateInput = document.getElementById('datepicker');
        var selectedDate = new Date(dateInput.value + 'T12:00:00');
        selectedDate.setDate(selectedDate.getDate() + parseInt(button.dataset.days, 10));
        dateInput.value = selectedDate.toISOString().split('T')[0];
        document.getElementById('dateForm').submit();
    });
});

$(document).ready(function() {
    $('.confirmButton').click(function() {
        var button = $(this);
        var missionId = button.data('mission-id');
        var buttonType = button.data('button-type');
        $.ajax({
            url: buttonType === 'start' ? '{{ route("registerTime") }}' : '{{ route("registerTimeend") }}',
            type: 'POST',
            data: {
                mission_id: missionId,
                user_id: '{{ auth()->id() }}',
                _token: '{{ csrf_token() }}'
            },
            success: function() {
                button.replaceWith('<span class="text-success">Confirmé</span>');
            },
            error: function(xhr) {
                var errorMessage = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'La confirmation a échoué.';
                alert(errorMessage);
            }
        });
    });
});
</script>
@endsection
