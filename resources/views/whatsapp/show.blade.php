@extends('layouts.app')

@section('content')
<style>
.whatsapp-show { max-width: 1400px; margin:0 auto; }
.whatsapp-grid { display:grid; grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); gap:14px; }
.whatsapp-panel { border:1px solid #e5e7eb; border-radius:8px; background:#fff; padding:14px; }
.whatsapp-panel h2 { font-size:18px; font-weight:800; margin:0 0 10px; }
.whatsapp-raw { white-space:pre-wrap; background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:12px; }
.whatsapp-json { white-space:pre-wrap; background:#111827; color:#e5e7eb; border-radius:6px; padding:12px; max-height:520px; overflow:auto; }
.whatsapp-actions { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
.whatsapp-kv { display:grid; grid-template-columns:160px minmax(0,1fr); gap:6px 10px; }
.whatsapp-kv strong { color:#374151; }
@media (max-width: 992px) { .whatsapp-grid { grid-template-columns:1fr; } }
</style>

@php
    $draft = $message->draft;
    $json = $draft?->parsed_json ?? [];
@endphp

<div class="whatsapp-show">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Message WhatsApp #{{ $message->id }}</h1>
            <div class="text-muted">{{ optional($message->created_at)->format('d/m/Y H:i') }} - {{ $message->from_number }}</div>
        </div>
        <a href="{{ route('whatsapp.inbox') }}" class="btn btn-outline-secondary">Retour</a>
    </div>

    <div class="whatsapp-actions">
        <form method="POST" action="{{ route('whatsapp.inbox.reparse', $message) }}">
            @csrf
            <button class="btn btn-outline-primary">Reanalyser</button>
        </form>
        <form method="POST" action="{{ route('whatsapp.inbox.approve', $message) }}" onsubmit="return confirm('Approuver ce brouillon ? Aucune creation definitive ne sera faite sans l etape suivante.');">
            @csrf
            <button class="btn btn-success" {{ !$draft ? 'disabled' : '' }}>Approuver le brouillon</button>
        </form>
        <form method="POST" action="{{ route('whatsapp.inbox.ignore', $message) }}" onsubmit="return confirm('Ignorer ce message ?');">
            @csrf
            <button class="btn btn-outline-danger">Ignorer</button>
        </form>
    </div>

    <div class="whatsapp-grid">
        <div class="whatsapp-panel">
            <h2>Message recu</h2>
            <div class="whatsapp-kv mb-3">
                <strong>Twilio SID</strong><span>{{ $message->twilio_sid }}</span>
                <strong>De</strong><span>{{ $message->from_number }}</span>
                <strong>A</strong><span>{{ $message->to_number }}</span>
                <strong>Statut</strong><span>{{ $message->status }}</span>
            </div>
            <div class="whatsapp-raw">{{ $message->body }}</div>
        </div>

        <div class="whatsapp-panel">
            <h2>Brouillon analyse</h2>
            @if($draft)
                <div class="whatsapp-kv mb-3">
                    <strong>Action</strong><span>{{ data_get($json, 'action', '-') }}</span>
                    <strong>Confiance</strong><span>{{ $draft->confidence !== null ? number_format((float) $draft->confidence, 0) . '%' : '-' }}</span>
                    <strong>Statut</strong><span>{{ $draft->status }}</span>
                    <strong>Question avant edit</strong><span>{{ data_get($json, 'question_before_edit', '-') }}</span>
                </div>
                @if(data_get($json, 'error'))
                    <div class="alert alert-warning">{{ data_get($json, 'error') }}</div>
                @endif
                <div class="whatsapp-json">{{ json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</div>
                @if($draft->parsed_xml)
                    <h2 class="mt-3">XML</h2>
                    <div class="whatsapp-raw">{{ $draft->parsed_xml }}</div>
                @endif
            @else
                <div class="alert alert-warning">Aucun brouillon trouve.</div>
            @endif
        </div>
    </div>
</div>
@endsection
