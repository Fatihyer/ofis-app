@extends('layouts.app')

@section('style')
<style>
    .mismatch-page{background:#f8fafc;min-height:calc(100vh - 90px);padding:16px}
    .mismatch-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:14px}
    .mismatch-head h1{margin:0;font-size:25px;font-weight:850;color:#0f172a}
    .mismatch-head small{color:#64748b;font-weight:750}
    .mismatch-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 22px rgba(15,23,42,.06);overflow:hidden}
    .mismatch-table th{font-size:12px;text-transform:uppercase;color:#475569;white-space:nowrap;background:#f8fafc}
    .mismatch-table td{vertical-align:middle}
    .mismatch-badge{display:inline-flex;align-items:center;border-radius:999px;padding:4px 8px;font-size:12px;font-weight:850;background:#fee2e2;color:#991b1b;margin:2px}
    .mismatch-date{font-weight:850;color:#0f172a}
    .mismatch-date small{display:block;color:#64748b;font-weight:700}
    .mismatch-actions{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}
    .mismatch-page .pagination{margin:0;gap:4px;align-items:center}
    .mismatch-page .page-link{min-width:32px;height:32px;padding:6px 10px;display:inline-flex;align-items:center;justify-content:center;font-size:13px;line-height:1}
    .mismatch-page .page-link svg{width:14px!important;height:14px!important}
    .mismatch-page nav svg,
    .mismatch-page nav[role="navigation"] svg,
    .mismatch-page nav[role="navigation"] .w-5,
    .mismatch-page nav[role="navigation"] .h-5{
        width:14px!important;
        height:14px!important;
        max-width:14px!important;
        max-height:14px!important;
        display:inline-block!important;
        vertical-align:middle!important;
    }
    .mismatch-page nav[role="navigation"] a,
    .mismatch-page nav[role="navigation"] span{
        line-height:1!important;
        font-size:13px!important;
    }
    .mismatch-page nav[role="navigation"]>div:first-child{display:none}
    @media(max-width:768px){.mismatch-page{padding:10px}.mismatch-head{display:block}.mismatch-actions{justify-content:flex-start}}
</style>
@endsection

@section('content')
<div class="mismatch-page">
    <div class="mismatch-head">
        <div>
            <h1>Transferts incohérents</h1>
            <small>Dossiers dont les dates ne correspondent pas au premier et au dernier transfert.</small>
        </div>
        <a href="{{ route('posts.index') }}" class="btn btn-outline-secondary btn-sm">Liste dossiers</a>
    </div>

    <div class="mismatch-card">
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
            <strong>{{ $posts->total() }} dossier(s)</strong>
            <span class="text-muted small">Correction selon premier départ et dernière fin de transfert</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 mismatch-table">
                <thead>
                    <tr>
                        <th>Dossier</th>
                        <th>Agence</th>
                        <th>Début dossier</th>
                        <th>Premier transfert</th>
                        <th>Fin dossier</th>
                        <th>Dernier transfert</th>
                        <th>Écart</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                        @php
                            $firstTransfer = $firstTransfers->get($post->id);
                            $lastTransfer = $lastTransfers->get($post->id);
                            $postStart = $post->start_date ? \Carbon\Carbon::parse($post->start_date) : null;
                            $postEnd = $post->end_date ? \Carbon\Carbon::parse($post->end_date) : null;
                            $firstStart = $post->first_transfer_start ? \Carbon\Carbon::parse($post->first_transfer_start) : null;
                            $lastEnd = $post->last_transfer_end ? \Carbon\Carbon::parse($post->last_transfer_end) : null;
                            $startMismatch = $postStart && $firstStart && !$postStart->isSameDay($firstStart);
                            $endMismatch = $postEnd && $lastEnd && !$postEnd->isSameDay($lastEnd);
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('posts.show', $post->id) }}"><strong>#{{ $post->id }}</strong></a>
                                <div class="text-muted small">{{ $post->title }}</div>
                            </td>
                            <td>{{ optional($post->acente)->name ?: 'Agence #' . $post->acente_id }}</td>
                            <td class="mismatch-date">
                                {{ $postStart ? $postStart->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="mismatch-date">
                                @if($firstStart)
                                    <a href="{{ route('transfers.show', $firstTransfer->id) }}">#{{ $firstTransfer->id }}</a>
                                    {{ $firstStart->format('d/m/Y H:i') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="mismatch-date">
                                {{ $postEnd ? $postEnd->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="mismatch-date">
                                @if($lastEnd)
                                    <a href="{{ route('transfers.show', $lastTransfer->id) }}">#{{ $lastTransfer->id }}</a>
                                    {{ $lastEnd->format('d/m/Y H:i') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($startMismatch)
                                    <span class="mismatch-badge">Début différent</span>
                                @endif
                                @if($endMismatch)
                                    <span class="mismatch-badge">Fin différente</span>
                                @endif
                            </td>
                            <td>
                                <div class="mismatch-actions">
                                    <span class="text-muted small">{{ $post->transfer_count }} transfert(s)</span>
                                    <form method="POST" action="{{ route('posts.syncDatesFromTransfers', $post->id) }}" onsubmit="return confirm('Mettre a jour les dates du dossier selon les transferts ?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            Synchroniser dates
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Aucun dossier incohérent.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $posts->links() }}
        </div>
    </div>
</div>
@endsection
