@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h4 mb-1">WhatsApp Group Monitor</h1>
      <div class="text-muted">
        Gateway: {{ strtoupper($status['status'] ?? 'offline') }} · WhatsApp: {{ strtoupper($status['whatsapp'] ?? 'unknown') }}
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('whatsapp-groups.qr') }}" class="btn btn-outline-primary btn-sm">QR kodunu göster</a>
      <form method="POST" action="{{ route('whatsapp-groups.sync') }}">
        @csrf
        <button class="btn btn-primary btn-sm">Sync groupes</button>
      </form>
    </div>
  </div>

  @if(session('flash_message'))
    <div class="alert alert-success">{{ session('flash_message') }}</div>
  @endif

  <div class="card">
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead>
          <tr>
            <th>Groupe</th>
            <th>Participants</th>
            <th>Dernier message</th>
            <th>Lire</th>
            <th>Analyser</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($groups as $group)
            <tr>
              <td>
                <strong>{{ $group->name }}</strong>
                <div class="text-muted small">{{ $group->external_id }}</div>
              </td>
              <td>{{ $group->participants_count ?: '-' }}</td>
              <td>{{ $group->last_message_at ? $group->last_message_at->format('d/m/Y H:i') : '-' }}</td>
              <td colspan="3">
                <form method="POST" action="{{ route('whatsapp-groups.update', $group) }}" class="d-flex align-items-center gap-3">
                  @csrf
                  @method('PUT')
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="is_active" value="1" id="active-{{ $group->id }}" {{ $group->is_active ? 'checked' : '' }}>
                    <label class="form-check-label" for="active-{{ $group->id }}">Actif</label>
                  </div>
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="analysis_enabled" value="1" id="analysis-{{ $group->id }}" {{ $group->analysis_enabled ? 'checked' : '' }}>
                    <label class="form-check-label" for="analysis-{{ $group->id }}">Analyse</label>
                  </div>
                  <button class="btn btn-success btn-sm ms-auto">Enregistrer</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Aucun groupe synchronisé.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">{{ $groups->links() }}</div>
</div>
@endsection
