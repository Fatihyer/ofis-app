@extends('layouts.app')

@section('content')
<style>
  .driver-planning-page {
    font-size: 0.92rem;
  }
  .driver-planning-toolbar {
    background: #fff;
    border: 1px solid #e6e8ee;
    border-radius: 8px;
    padding: 14px;
    margin-bottom: 16px;
  }
  .driver-planning-table {
    background: #fff;
    border: 1px solid #e6e8ee;
    border-radius: 8px;
    overflow: hidden;
  }
  .driver-planning-table table {
    margin-bottom: 0;
  }
  .driver-planning-table th {
    background: #f7f8fb;
    color: #4b5563;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    white-space: nowrap;
  }
  .driver-planning-table td {
    vertical-align: top;
  }
  .driver-planning-name {
    min-width: 220px;
  }
  .driver-planning-vehicles {
    min-width: 260px;
  }
  .driver-planning-notes {
    min-width: 220px;
  }
  .driver-planning-select {
    min-height: 96px;
  }
  .driver-planning-badge {
    display: inline-block;
    border-radius: 999px;
    padding: 2px 8px;
    margin: 2px 4px 2px 0;
    background: #edf2f7;
    color: #2d3748;
    font-size: 0.78rem;
  }
  .driver-planning-badge-priority {
    background: #0f766e;
    color: #fff;
  }
  .driver-planning-badge-soft {
    background: #eef4ff;
    color: #1d4ed8;
  }
  .driver-planning-small {
    color: #6b7280;
    font-size: 0.82rem;
  }
  @media (max-width: 992px) {
    .driver-planning-table {
      border-radius: 0;
      border-left: 0;
      border-right: 0;
    }
  }
</style>

<div class="container-fluid driver-planning-page">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1">Planning chauffeurs</h1>
      <div class="driver-planning-small">Profils issus des prestataires, avec véhicules autorisés et priorité planning.</div>
      @if(isset($configuredDriverIds) && $configuredDriverIds->isNotEmpty())
        <div class="driver-planning-small">Option driverIds: {{ $configuredDriverIds->implode(', ') }}</div>
      @endif
    </div>
    <a href="{{ route('planning.futur') }}" class="btn btn-outline-secondary btn-sm">Planning véhicules</a>
  </div>

  @if(session('flash_message'))
    <div class="alert alert-success">{{ session('flash_message') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  <form method="GET" action="{{ route('driver-planning.index') }}" class="driver-planning-toolbar">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label mb-1">Recherche</label>
        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Nom, téléphone, titre">
      </div>
      <div class="col-md-2">
        <label class="form-label mb-1">Type</label>
        <select name="employment_type" class="form-select form-select-sm">
          <option value="">Tous</option>
          @foreach($employmentTypes as $key => $label)
            <option value="{{ $key }}" {{ request('employment_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label mb-1">Groupe</label>
        <select name="firma" class="form-select form-select-sm">
          <option value="">Tous</option>
          @foreach($firmas as $id => $name)
            <option value="{{ $id }}" {{ (string) request('firma') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label mb-1">Véhicule</label>
        <select name="vehicule_id" class="form-select form-select-sm">
          <option value="">Tous</option>
          @foreach($vehicules as $vehicule)
            <option value="{{ $vehicule->id }}" {{ (string) request('vehicule_id') === (string) $vehicule->id ? 'selected' : '' }}>
              {{ $vehicule->name }} @if($vehicule->plaka) - {{ $vehicule->plaka }} @endif
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <div class="form-check mb-1">
          <input type="checkbox" name="priority" value="1" class="form-check-input" id="priorityFilter" {{ request('priority') === '1' ? 'checked' : '' }}>
          <label class="form-check-label" for="priorityFilter">Prioritaires</label>
        </div>
        <div class="form-check">
          <input type="checkbox" name="all" value="1" class="form-check-input" id="allFilter" {{ request('all') === '1' ? 'checked' : '' }}>
          <label class="form-check-label" for="allFilter">Tous les prestataires</label>
        </div>
      </div>
      <div class="col-md-1 d-grid">
        <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
      </div>
    </div>
  </form>

  <div class="driver-planning-table table-responsive">
    <table class="table table-sm table-hover align-middle">
      <thead>
        <tr>
          <th>Priorité</th>
          <th>Chauffeur</th>
          <th>Type</th>
          <th>Véhicules autorisés</th>
          <th>Préférés</th>
          <th>Notes</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($drivers as $driver)
          @php
            $profile = $driver->driverPlanningProfile;
            $formId = 'driver-planning-' . $driver->id;
            $capableIds = $driver->driverVehicleCapabilities->pluck('id')->map(function ($id) {
                return (string) $id;
            })->all();
            $preferredIds = $driver->driverVehicleCapabilities->filter(function ($vehicule) {
                return (bool) $vehicule->pivot->preferred;
            })->pluck('id')->map(function ($id) {
                return (string) $id;
            })->all();
            $canUpdatePlanning = auth()->check() && auth()->user()->can('vehicules.update');
            $isPriority = $profile ? (bool) $profile->is_priority : false;
            $priorityLevel = $profile ? (int) $profile->priority_level : 5;
            $employmentType = $profile ? $profile->employment_type : 'per_job';
            $notes = $profile ? $profile->notes : null;
          @endphp
          <tr>
            <td style="width: 130px;">
              <form id="{{ $formId }}" method="POST" action="{{ route('driver-planning.update', array_merge(['driver' => $driver->id], request()->query())) }}">
                @csrf
                @method('PUT')
              </form>
              <input type="hidden" name="is_priority" value="0" form="{{ $formId }}">
              <div class="form-check mb-2">
                <input type="checkbox" name="is_priority" value="1" form="{{ $formId }}" class="form-check-input" id="priority-{{ $driver->id }}" {{ $isPriority ? 'checked' : '' }} {{ !$canUpdatePlanning ? 'disabled' : '' }}>
                <label class="form-check-label" for="priority-{{ $driver->id }}">Prioritaire</label>
              </div>
              <select name="priority_level" form="{{ $formId }}" class="form-select form-select-sm" {{ !$canUpdatePlanning ? 'disabled' : '' }}>
                @for($level = 1; $level <= 9; $level++)
                  <option value="{{ $level }}" {{ $priorityLevel === $level ? 'selected' : '' }}>Niveau {{ $level }}</option>
                @endfor
              </select>
            </td>
            <td class="driver-planning-name">
              <div class="fw-semibold">
                {{ $driver->name }}
                @if($isPriority)
                  <span class="driver-planning-badge driver-planning-badge-priority">Prioritaire</span>
                @endif
              </div>
              @if($driver->tittle)
                <div class="driver-planning-small">{{ $driver->tittle }}</div>
              @endif
              @if($driver->tel)
                <div class="driver-planning-small">{{ $driver->tel }}</div>
              @endif
              <div class="mt-1">
                @foreach($driver->firmas as $firma)
                  <span class="driver-planning-badge">{{ $firma->name }}</span>
                @endforeach
              </div>
            </td>
            <td style="min-width: 155px;">
              <select name="employment_type" form="{{ $formId }}" class="form-select form-select-sm" {{ !$canUpdatePlanning ? 'disabled' : '' }}>
                @foreach($employmentTypes as $key => $label)
                  <option value="{{ $key }}" {{ $employmentType === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
            </td>
            <td class="driver-planning-vehicles">
              <select name="vehicules[]" form="{{ $formId }}" class="form-select form-select-sm driver-planning-select" multiple {{ !$canUpdatePlanning ? 'disabled' : '' }}>
                @foreach($vehicules as $vehicule)
                  <option value="{{ $vehicule->id }}" {{ in_array((string) $vehicule->id, $capableIds, true) ? 'selected' : '' }}>
                    {{ $vehicule->name }} @if($vehicule->plaka) - {{ $vehicule->plaka }} @endif @if($vehicule->capacity) ({{ $vehicule->capacity }} pax) @endif
                  </option>
                @endforeach
              </select>
              <div class="mt-1">
                @foreach($driver->driverVehicleCapabilities as $vehicule)
                  <span class="driver-planning-badge {{ $vehicule->pivot->preferred ? 'driver-planning-badge-soft' : '' }}">
                    {{ $vehicule->name }}@if($vehicule->pivot->preferred) · préféré @endif
                  </span>
                @endforeach
              </div>
            </td>
            <td style="min-width: 220px;">
              <select name="preferred_vehicules[]" form="{{ $formId }}" class="form-select form-select-sm driver-planning-select" multiple {{ !$canUpdatePlanning ? 'disabled' : '' }}>
                @foreach($vehicules as $vehicule)
                  <option value="{{ $vehicule->id }}" {{ in_array((string) $vehicule->id, $preferredIds, true) ? 'selected' : '' }}>
                    {{ $vehicule->name }} @if($vehicule->plaka) - {{ $vehicule->plaka }} @endif
                  </option>
                @endforeach
              </select>
              <div class="driver-planning-small mt-1">Un véhicule préféré doit aussi être autorisé.</div>
            </td>
            <td class="driver-planning-notes">
              <textarea name="notes" form="{{ $formId }}" rows="4" class="form-control form-control-sm" {{ !$canUpdatePlanning ? 'disabled' : '' }}>{{ old('notes', $notes) }}</textarea>
            </td>
            <td class="text-end" style="width: 110px;">
              @can('vehicules.update')
                <button type="submit" form="{{ $formId }}" class="btn btn-success btn-sm">Enregistrer</button>
              @else
                <span class="driver-planning-small">Lecture</span>
              @endcan
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center text-muted py-4">Aucun chauffeur trouvé.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
