@extends('layouts.app')

@section('content')
<style>
  .wgl-detail { font-size: .94rem; }
  .wgl-box { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 14px; }
  .wgl-body { white-space: pre-wrap; background: #f9fafb; border-radius: 8px; padding: 12px; }
  .wgl-kv dt { color: #6b7280; font-weight: 600; }
  .wgl-kv dd { margin-bottom: 8px; }
</style>

<div class="container-fluid wgl-detail">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">WhatsApp İş Talebi #{{ $lead->id }}</h1>
    <a href="{{ route('whatsapp-group-leads.index') }}" class="btn btn-outline-secondary btn-sm">Liste</a>
  </div>

  @if(session('flash_message'))
    <div class="alert alert-success">{{ session('flash_message') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  <div class="row">
    <div class="col-lg-7">
      <div class="wgl-box">
        <h2 class="h5">{{ $lead->group?->name }}</h2>
        <div class="text-muted mb-2">
          {{ $lead->sender_name ?: 'Inconnu' }}
          @if($lead->sender_phone) · {{ $lead->sender_phone }} @endif
          · {{ optional($lead->message?->sent_at)->format('d/m/Y H:i') }}
        </div>
        <div class="wgl-body">{{ $lead->message?->body }}</div>
      </div>

      <div class="wgl-box">
        <h2 class="h5">Répondre au groupe</h2>
        <form method="POST" action="{{ route('whatsapp-groups.send', $lead->group) }}" id="whatsappGroupReplyForm">
          @csrf
          <input type="hidden" name="idempotency_key" value="{{ uniqid('reply_', true) }}">
          <div class="mb-2">
            <label class="form-label">Hazır cevap</label>
            <select class="form-select form-select-sm" id="quickReplySelect">
              <option value="">Choisir...</option>
              @foreach($quickReplies as $reply)
                <option value="{{ e($reply->body) }}">{{ $reply->title }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Message</label>
            <textarea name="message" id="replyMessage" rows="7" class="form-control" required>Bonjour,

Nous sommes disponibles pour cette prestation.

Pouvez-vous nous communiquer les informations complémentaires ?</textarea>
          </div>
          <button class="btn btn-primary" id="sendReplyButton">WhatsApp grubuna gönder</button>
        </form>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="wgl-box">
        <h2 class="h5">Analyse</h2>
        <dl class="row wgl-kv">
          <dt class="col-sm-5">Score</dt><dd class="col-sm-7">{{ $lead->score }} / 100</dd>
          <dt class="col-sm-5">Statut</dt><dd class="col-sm-7">{{ $lead->status }}</dd>
          <dt class="col-sm-5">Araç</dt><dd class="col-sm-7">{{ $lead->vehicle_type ?: '-' }}</dd>
          <dt class="col-sm-5">Pax</dt><dd class="col-sm-7">{{ $lead->passenger_count ?: '-' }}</dd>
          <dt class="col-sm-5">Tarih</dt><dd class="col-sm-7">{{ $lead->service_date ? $lead->service_date->format('d/m/Y') : '-' }}</dd>
          <dt class="col-sm-5">Saat</dt><dd class="col-sm-7">{{ $lead->service_time ? substr($lead->service_time, 0, 5) : '-' }}</dd>
          <dt class="col-sm-5">Départ</dt><dd class="col-sm-7">{{ $lead->pickup_location ?: '-' }}</dd>
          <dt class="col-sm-5">Arrivée</dt><dd class="col-sm-7">{{ $lead->dropoff_location ?: '-' }}</dd>
          <dt class="col-sm-5">Langue</dt><dd class="col-sm-7">{{ $lead->language ?: '-' }}</dd>
          <dt class="col-sm-5">Résumé</dt><dd class="col-sm-7">{{ $lead->summary ?: '-' }}</dd>
        </dl>
      </div>

      @if($lead->possible_duplicate_of_id)
        <div class="alert alert-warning">
          Muhtemel tekrar:
          <a href="{{ route('whatsapp-group-leads.show', $lead->duplicateOf) }}">benzer talep #{{ $lead->possible_duplicate_of_id }}</a>
        </div>
      @endif
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const select = document.getElementById('quickReplySelect');
  const textarea = document.getElementById('replyMessage');
  const form = document.getElementById('whatsappGroupReplyForm');
  const button = document.getElementById('sendReplyButton');

  if (select && textarea) {
    select.addEventListener('change', function () {
      if (this.value) textarea.value = this.value;
    });
  }

  if (form && button) {
    form.addEventListener('submit', function () {
      button.disabled = true;
      button.textContent = 'Envoi...';
    });
  }
});
</script>
@endsection
