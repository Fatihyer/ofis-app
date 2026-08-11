@extends('layouts.app')

@section('content')
<style>
.whatsapp-page { max-width: 1500px; margin: 0 auto; }
.whatsapp-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; margin-bottom:14px; flex-wrap:wrap; }
.whatsapp-head h1 { font-size:24px; font-weight:850; margin:0; }
.whatsapp-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
.whatsapp-tabs a { border:1px solid #d1d5db; border-radius:6px; padding:7px 10px; color:#111827; background:#fff; text-decoration:none; }
.whatsapp-tabs a.active { background:#111827; color:#fff; border-color:#111827; }
.whatsapp-table-wrap { overflow:auto; border:1px solid #e5e7eb; border-radius:8px; background:#fff; }
.whatsapp-table { min-width:1150px; margin:0; }
.whatsapp-table th { background:#1f2937; color:#fff; white-space:nowrap; position:sticky; top:0; z-index:2; }
.whatsapp-message-preview { max-width:420px; white-space:normal; }
.whatsapp-badge { display:inline-flex; align-items:center; border-radius:999px; padding:3px 8px; font-size:12px; font-weight:700; }
.whatsapp-badge-pending { background:#fff7ed; color:#9a3412; }
.whatsapp-badge-error { background:#fee2e2; color:#991b1b; }
.whatsapp-badge-approved { background:#dcfce7; color:#166534; }
.whatsapp-badge-ignored { background:#f3f4f6; color:#374151; }
</style>

<div class="whatsapp-page">
    <div class="whatsapp-head">
        <div>
            <h1>Messages WhatsApp</h1>
            <div class="text-muted">Messages recus via Twilio, analyses par OpenAI avant creation ou modification.</div>
        </div>
        <div class="text-muted small">Webhook Twilio: <code>{{ url('/twilio/whatsapp/webhook') }}</code></div>
    </div>

    <div class="whatsapp-tabs">
        @foreach(['pending' => 'A traiter', 'error' => 'Erreur', 'approved' => 'Approuves', 'ignored' => 'Ignores', 'all' => 'Tous'] as $key => $label)
            <a href="{{ route('whatsapp.inbox', ['status' => $key]) }}" class="{{ $status === $key ? 'active' : '' }}">
                {{ $label }} <strong>{{ $counts[$key] ?? 0 }}</strong>
            </a>
        @endforeach
    </div>

    <div class="whatsapp-table-wrap">
        <table class="table table-striped table-hover whatsapp-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>De</th>
                    <th>Message</th>
                    <th>Action detectee</th>
                    <th>Statut</th>
                    <th>Confiance</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($messages as $message)
                    @php
                        $draft = $message->draft;
                        $json = $draft?->parsed_json ?? [];
                        $badge = 'whatsapp-badge-' . ($draft->status ?? $message->status ?? 'pending');
                    @endphp
                    <tr>
                        <td>{{ $message->id }}</td>
                        <td>{{ optional($message->created_at)->format('d/m/Y H:i') }}</td>
                        <td>{{ $message->from_number }}</td>
                        <td class="whatsapp-message-preview">{{ \Illuminate\Support\Str::limit($message->body, 220) }}</td>
                        <td>{{ data_get($json, 'action', '-') }}</td>
                        <td><span class="whatsapp-badge {{ $badge }}">{{ $draft->status ?? $message->status }}</span></td>
                        <td>{{ $draft?->confidence !== null ? number_format((float) $draft->confidence, 0) . '%' : '-' }}</td>
                        <td><a class="btn btn-sm btn-primary" href="{{ route('whatsapp.inbox.show', $message) }}">Voir</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucun message.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $messages->links() }}</div>
</div>
@endsection
