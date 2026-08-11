@extends('layouts.app')

@section('content')
<style>
  .wgl-page { font-size: .94rem; }
  .wgl-toolbar, .wgl-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; }
  .wgl-toolbar { padding: 12px; margin-bottom: 14px; }
  .wgl-card { padding: 14px; margin-bottom: 12px; }
  .wgl-score { width: 64px; height: 64px; border-radius: 8px; display: grid; place-items: center; color: #fff; background: #0f766e; font-weight: 800; font-size: 1.2rem; }
  .wgl-score.high { background: #b91c1c; }
  .wgl-score.important { background: #c2410c; }
  .wgl-meta { color: #6b7280; font-size: .84rem; }
  .wgl-chip { display: inline-block; border-radius: 999px; padding: 2px 8px; margin: 2px 4px 2px 0; background: #eef2ff; color: #3730a3; font-size: .78rem; }
  .wgl-message { white-space: pre-wrap; }
</style>

<div class="container-fluid wgl-page">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h4 mb-1">WhatsApp İş Talepleri</h1>
      <div class="wgl-meta">Sadece WhatsApp grup mesajlarından çıkarılan potansiyel işler.</div>
    </div>
    <a href="{{ route('whatsapp-groups.index') }}" class="btn btn-outline-secondary btn-sm">Groupes</a>
  </div>

  @if(session('flash_message'))
    <div class="alert alert-success">{{ session('flash_message') }}</div>
  @endif

  <div class="wgl-toolbar">
    <a class="btn btn-sm {{ $status === 'new' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('whatsapp-group-leads.index', ['status' => 'new']) }}">Nouveaux ({{ $counts['new'] }})</a>
    <a class="btn btn-sm btn-outline-danger" href="{{ route('whatsapp-group-leads.index', ['status' => 'all', 'priority' => 'high']) }}">Haute priorité ({{ $counts['high'] }})</a>
    <a class="btn btn-sm btn-outline-secondary" href="{{ route('whatsapp-group-leads.index', ['status' => 'all', 'today' => 1]) }}">Aujourd'hui ({{ $counts['today'] }})</a>
    <a class="btn btn-sm btn-outline-secondary" href="{{ route('whatsapp-group-leads.index', ['status' => 'all', 'vehicle' => 'autocar']) }}">Autocar</a>
    <a class="btn btn-sm btn-outline-secondary" href="{{ route('whatsapp-group-leads.index', ['status' => 'all', 'vehicle' => 'sprinter']) }}">Sprinter</a>
    <a class="btn btn-sm {{ $status === 'all' ? 'btn-dark' : 'btn-outline-dark' }}" href="{{ route('whatsapp-group-leads.index', ['status' => 'all']) }}">Tous ({{ $counts['all'] }})</a>
  </div>

  @forelse($leads as $lead)
    @php
      $scoreClass = $lead->score >= 85 ? 'high' : ($lead->score >= 70 ? 'important' : '');
      $body = $lead->message ? $lead->message->body : '';
    @endphp
    <div class="wgl-card">
      <div class="d-flex gap-3">
        <div class="wgl-score {{ $scoreClass }}">{{ $lead->score }}</div>
        <div class="flex-grow-1">
          <div class="d-flex justify-content-between gap-2">
            <div>
              <strong>{{ $lead->group?->name ?: 'Groupe WhatsApp' }}</strong>
              @if($lead->possible_duplicate_of_id)
                <span class="badge bg-warning text-dark">Muhtemel tekrar</span>
              @endif
              <div class="wgl-meta">{{ $lead->sender_name ?: 'Inconnu' }} · {{ optional($lead->created_at)->format('d/m/Y H:i') }}</div>
            </div>
            <div>
              <a href="{{ route('whatsapp-group-leads.show', $lead) }}" class="btn btn-outline-primary btn-sm">Detay</a>
            </div>
          </div>

          <div class="wgl-message mt-2">{{ \Illuminate\Support\Str::limit($body, 280) }}</div>

          <div class="mt-2">
            @if($lead->vehicle_type)<span class="wgl-chip">{{ ucfirst($lead->vehicle_type) }}</span>@endif
            @if($lead->passenger_count)<span class="wgl-chip">{{ $lead->passenger_count }} pax</span>@endif
            @if($lead->number_of_vehicles)<span class="wgl-chip">{{ $lead->number_of_vehicles }} véhicule(s)</span>@endif
            @if($lead->service_date)<span class="wgl-chip">{{ $lead->service_date->format('d/m/Y') }}</span>@endif
            @if($lead->service_time)<span class="wgl-chip">{{ substr($lead->service_time, 0, 5) }}</span>@endif
            @if($lead->pickup_location || $lead->dropoff_location)
              <span class="wgl-chip">{{ $lead->pickup_location ?: '?' }} → {{ $lead->dropoff_location ?: '?' }}</span>
            @endif
          </div>

          <form method="POST" action="{{ route('whatsapp-group-leads.status', $lead) }}" class="mt-3 d-inline">
            @csrf
            <input type="hidden" name="status" value="interesting">
            <button class="btn btn-success btn-sm">İlgileniyorum</button>
          </form>
          <form method="POST" action="{{ route('whatsapp-group-leads.status', $lead) }}" class="mt-3 d-inline">
            @csrf
            <input type="hidden" name="status" value="not_interested">
            <button class="btn btn-outline-secondary btn-sm">İlgilenmiyorum</button>
          </form>
        </div>
      </div>
    </div>
  @empty
    <div class="alert alert-light border">Henüz WhatsApp grup iş talebi yok.</div>
  @endforelse

  {{ $leads->links() }}
</div>
@endsection
