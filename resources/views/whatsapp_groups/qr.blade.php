@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h4 mb-1">WhatsApp QR</h1>
      <div class="text-muted">
        Gateway: {{ strtoupper($status['status'] ?? 'offline') }} · WhatsApp: {{ strtoupper($status['whatsapp'] ?? 'unknown') }}
      </div>
    </div>
    <a href="{{ route('whatsapp-groups.index') }}" class="btn btn-outline-secondary btn-sm">Groupes</a>
  </div>

  @if($error)
    <div class="alert alert-warning">{{ $error }}</div>
  @endif

  <div class="card">
    <div class="card-body text-center">
      @if(isset($qr['qr']['dataUrl']))
        <img src="{{ $qr['qr']['dataUrl'] }}" alt="WhatsApp QR" style="max-width: 360px; width: 100%; height: auto;">
        <p class="text-muted mt-3 mb-0">WhatsApp uygulamasından bu QR kodunu okut.</p>
      @else
        <p class="text-muted mb-0">QR şu anda hazır değil. WhatsApp zaten bağlı olabilir veya gateway henüz hazırlanıyor olabilir.</p>
      @endif
    </div>
  </div>
</div>
@endsection
