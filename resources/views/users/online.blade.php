@extends('layouts.app')

@section('title', '| Utilisateurs en ligne')

@section('content')
@php
    $now = now();
    $formatDuration = function ($seconds) {
        $seconds = max(0, (int) $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return $hours . 'h ' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT);
        }

        return $minutes . ' min';
    };
    $onlineCount = $users->filter(function ($user) use ($now) {
        return $user->last_seen_at && \Carbon\Carbon::parse($user->last_seen_at)->diffInMinutes($now) <= 5;
    })->count();
    $recentCount = $users->filter(function ($user) use ($now) {
        if (!$user->last_seen_at) return false;
        $minutes = \Carbon\Carbon::parse($user->last_seen_at)->diffInMinutes($now);
        return $minutes > 5 && $minutes <= 30;
    })->count();
    $offlineCount = max(0, $users->count() - $onlineCount - $recentCount);
@endphp
<style>
    .online-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
    }
    .online-status::before {
        content: "";
        width: 7px;
        height: 7px;
        border-radius: 999px;
        background: currentColor;
    }
    .online-status.is-online {
        color: #166534;
        background: #dcfce7;
        border: 1px solid #86efac;
    }
    .online-status.is-recent {
        color: #854d0e;
        background: #fef9c3;
        border: 1px solid #fde68a;
    }
    .online-status.is-offline {
        color: #475569;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
    }
    .online-status.is-unknown {
        color: #6b7280;
        background: #f9fafb;
        border: 1px solid #d1d5db;
    }
    .online-metric {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 12px 14px;
        background: #fff;
        height: 100%;
    }
    .online-metric .label {
        display: block;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .online-metric .value {
        color: #0f172a;
        font-size: 24px;
        font-weight: 800;
        line-height: 1.2;
    }
    .activity-time {
        font-weight: 800;
        color: #111827;
    }
</style>

<div class="row">
    <div class="col-lg-10 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-0">
                        <i class="fa fa-user-clock"></i> Utilisateurs en ligne
                    </h3>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">Tous les utilisateurs</a>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4 mb-2">
                        <div class="online-metric">
                            <span class="label">En ligne</span>
                            <span class="value">{{ $onlineCount }}</span>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="online-metric">
                            <span class="label">Récemment actif</span>
                            <span class="value">{{ $recentCount }}</span>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="online-metric">
                            <span class="label">Hors ligne</span>
                            <span class="value">{{ $offlineCount }}</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Statut</th>
                                <th>Aujourd’hui</th>
                                <th>Cette semaine</th>
                                <th>Ce mois</th>
                                <th>Dernière activité</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                @php
                                    $lastSeen = $user->last_seen_at ? \Carbon\Carbon::parse($user->last_seen_at) : null;
                                    $minutesSinceSeen = $lastSeen ? $lastSeen->diffInMinutes($now) : null;
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $user->name }}</strong>
                                        <br><span class="text-muted small">{{ $user->email }}</span>
                                    </td>
                                    <td>
                                        @if($lastSeen && $minutesSinceSeen <= 5)
                                            <span class="online-status is-online">En ligne</span>
                                        @elseif($lastSeen && $minutesSinceSeen <= 30)
                                            <span class="online-status is-recent">Récemment actif</span>
                                        @elseif($lastSeen)
                                            <span class="online-status is-offline">Hors ligne</span>
                                        @else
                                            <span class="online-status is-unknown">Inconnu</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="activity-time">{{ $formatDuration($user->today_active_seconds ?? 0) }}</span>
                                    </td>
                                    <td>
                                        <span class="activity-time">{{ $formatDuration($user->week_active_seconds ?? 0) }}</span>
                                    </td>
                                    <td>
                                        <span class="activity-time">{{ $formatDuration($user->month_active_seconds ?? 0) }}</span>
                                    </td>
                                    <td>
                                        @if($lastSeen)
                                            <span class="text-muted small">{{ $lastSeen->format('d/m/Y H:i') }}</span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
