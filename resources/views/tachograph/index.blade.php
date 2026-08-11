@extends('layouts.app')

@section('content')
<style>
  .tachograph-page { padding: 18px 22px; }
  .tachograph-toolbar,
  .tachograph-panel {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 16px;
    padding: 16px;
  }
  .tachograph-stats {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(4, minmax(130px, 1fr));
    margin-bottom: 16px;
  }
  .tachograph-stat {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px;
  }
  .tachograph-stat strong { display: block; font-size: 22px; }
  .tachograph-table {
    table-layout: fixed;
    width: 100%;
  }
  .tachograph-table th,
  .tachograph-table td {
    vertical-align: top;
    overflow-wrap: anywhere;
  }
  .tachograph-page .pagination {
    margin-bottom: 0;
  }
  .tachograph-page .pagination svg {
    width: 14px;
    height: 14px;
  }
  .tachograph-muted { color: #6b7280; font-size: 12px; }
  .tachograph-actions { display: flex; gap: 8px; align-items: end; flex-wrap: wrap; }
  @media (max-width: 900px) {
    .tachograph-stats { grid-template-columns: repeat(2, minmax(130px, 1fr)); }
  }
</style>

<div class="tachograph-page">
  @php
    $formatMinutes = function ($minutes) {
      $minutes = (int) $minutes;
      return intdiv($minutes, 60) . 'h' . str_pad($minutes % 60, 2, '0', STR_PAD_LEFT);
    };
  @endphp

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h4 mb-1">Tachograph</h1>
      <div class="tachograph-muted">Import C1B chauffeur, controle doublon et rapprochement transfert.</div>
    </div>
  </div>

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  @if (session('tachograph_summary'))
    @php($summary = session('tachograph_summary'))
    <div class="alert alert-info">
      Fichiers importes: {{ $summary['imported_files'] ?? 0 }},
      doublons ignores: {{ $summary['duplicate_files'] ?? 0 }},
      conduites ajoutees: {{ $summary['created_drives'] ?? 0 }},
      transferts rapproches: {{ $summary['matched_drives'] ?? 0 }},
      anciennes conduites ignorees: {{ $summary['ignored_drives'] ?? 0 }}.
      @if (!empty($summary['errors']))
        <div class="mt-2 text-danger">{{ implode(' | ', $summary['errors']) }}</div>
      @endif
    </div>
  @endif

  <div class="tachograph-stats">
    <div class="tachograph-stat"><span>Cartes</span><strong>{{ $stats['cards'] }}</strong></div>
    <div class="tachograph-stat"><span>Conduites</span><strong>{{ $stats['drives'] }}</strong></div>
    <div class="tachograph-stat"><span>Associees</span><strong>{{ $stats['matched'] }}</strong></div>
    <div class="tachograph-stat"><span>A verifier</span><strong>{{ $stats['unmatched'] }}</strong></div>
  </div>

  <div class="tachograph-toolbar">
    <div class="alert alert-light border mb-3">
      Les conduites avant le 01/06/2026 ne sont pas importees.
    </div>
    <div class="mb-3">
      <h2 class="h5 mb-2">Google Drive</h2>
      @if(!$driveSettings['configured'])
        <div class="alert alert-warning mb-2">
          Google Drive n'est pas encore connecte sur le serveur.
        </div>
      @else
        <div class="alert alert-success mb-2">
          Google Drive connecte: {{ $driveSettings['mode'] }}
          @if($driveSettings['connected_at'])
            <span class="tachograph-muted">depuis {{ $driveSettings['connected_at'] }}</span>
          @endif
        </div>
      @endif
      <form action="{{ route('tachograph.drive.settings') }}" method="post" class="tachograph-actions mb-2">
        @csrf
        <div class="form-group mb-0" style="min-width: 360px;">
          <label>Folder ID Google Drive</label>
          <input type="text" name="folder_id" value="{{ $driveSettings['folder_id'] }}" class="form-control" placeholder="Ex: 1AbC...">
        </div>
        <div class="form-group mb-0" style="min-width: 360px;">
          <label>OAuth Client ID</label>
          <input type="text" name="google_drive_client_id" value="{{ $driveSettings['oauth_client_id'] }}" class="form-control" placeholder="Google Drive OAuth client ID">
        </div>
        <div class="form-group mb-0" style="min-width: 280px;">
          <label>OAuth Client Secret</label>
          <input type="password" name="google_drive_client_secret" value="" class="form-control" placeholder="{{ $driveSettings['oauth_secret_set'] ? 'Secret deja enregistre' : 'Client secret' }}">
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" name="auto_sync" value="1" id="drive-auto-sync" {{ $driveSettings['auto_sync'] ? 'checked' : '' }}>
          <label class="form-check-label" for="drive-auto-sync">Auto sync</label>
        </div>
        <button class="btn btn-outline-primary" type="submit">Enregistrer Drive</button>
      </form>
      <div class="tachograph-muted mb-2">
        Redirect URI Google Cloud: https://ofis.parisvia.com/hermes/tachograph/google/callback
      </div>
      <div class="tachograph-actions mb-2">
        <a class="btn btn-outline-success" href="{{ route('tachograph.drive.redirect') }}">Connecter le compte Google</a>
        <form action="{{ route('tachograph.drive.disconnect') }}" method="post" class="mb-0">
          @csrf
          <button class="btn btn-outline-danger" type="submit">Deconnecter</button>
        </form>
      </div>
      <form action="{{ route('tachograph.drive.sync') }}" method="post" class="tachograph-actions">
        @csrf
        <input type="hidden" name="limit" value="50">
        <button class="btn btn-success" type="submit" {{ empty($driveSettings['folder_id']) || !$driveSettings['configured'] ? 'disabled' : '' }}>Synchroniser Drive</button>
        <span class="tachograph-muted">
          Derniere sync: {{ $driveSettings['last_sync_at'] ?: '-' }}
        </span>
      </form>
      <div class="tachograph-muted mt-2">
        Apres connexion, Laravel lit directement le dossier Google Drive depuis le serveur.
      </div>
    </div>
    <form action="{{ route('tachograph.import') }}" method="post" enctype="multipart/form-data" class="tachograph-actions">
      @csrf
      <div class="form-group mb-0">
        <label for="tachograph-files">Fichiers C1B</label>
        <input id="tachograph-files" type="file" name="files[]" class="form-control" multiple required>
      </div>
      <button class="btn btn-primary" type="submit">Importer</button>
    </form>
  </div>

  <div class="tachograph-panel">
    <form method="get" action="{{ route('tachograph.index') }}" class="tachograph-actions">
      <div class="form-group mb-0">
        <label>Date debut</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
      </div>
      <div class="form-group mb-0">
        <label>Date fin</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
      </div>
      <div class="form-group mb-0">
        <label>Chauffeur</label>
        <select name="acente_id" class="form-control">
          <option value="">Tous</option>
          @foreach($drivers as $driver)
            <option value="{{ $driver->id }}" {{ (string)request('acente_id') === (string)$driver->id ? 'selected' : '' }}>{{ $driver->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group mb-0">
        <label>Etat</label>
        <select name="match_status" class="form-control">
          <option value="">Tous</option>
          <option value="matched" {{ request('match_status') === 'matched' ? 'selected' : '' }}>Associe</option>
          <option value="unmatched" {{ request('match_status') === 'unmatched' ? 'selected' : '' }}>A verifier</option>
          <option value="driver_not_mapped" {{ request('match_status') === 'driver_not_mapped' ? 'selected' : '' }}>Chauffeur non lie</option>
        </select>
      </div>
      <button class="btn btn-secondary" type="submit">Filtrer</button>
      <a class="btn btn-light" href="{{ route('tachograph.index') }}">Reset</a>
    </form>
  </div>

  <div class="tachograph-panel">
    <h2 class="h5 mb-3">Temps chauffeur - hebdomadaire</h2>
    <div class="table-responsive">
      <table class="table table-sm table-striped table-bordered tachograph-table">
        <thead>
          <tr>
            <th style="width: 160px;">Semaine</th>
            <th>Chauffeur</th>
            <th style="width: 130px;">Conduite</th>
            <th style="width: 130px;">Travail</th>
            <th style="width: 130px;">Total actif</th>
            <th style="width: 120px;">Disponibilite</th>
            <th style="width: 100px;">Km</th>
          </tr>
        </thead>
        <tbody>
          @forelse($weeklySummaries as $row)
            <tr>
              <td>
                {{ \Carbon\Carbon::parse($row->period_start)->format('d/m/Y') }}
                -
                {{ \Carbon\Carbon::parse($row->period_end)->format('d/m/Y') }}
              </td>
              <td>{{ optional($row->acente)->name ?: 'Chauffeur non lie' }}</td>
              <td>{{ $formatMinutes($row->driving_minutes) }}</td>
              <td>{{ $formatMinutes($row->work_minutes) }}</td>
              <td><strong>{{ $formatMinutes($row->driving_minutes + $row->work_minutes) }}</strong></td>
              <td>{{ $formatMinutes($row->availability_minutes) }}</td>
              <td>{{ number_format((float)$row->distance_km, 0, ',', ' ') }}</td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted">Aucun resume hebdomadaire.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="tachograph-panel">
    <h2 class="h5 mb-3">Temps chauffeur - mensuel</h2>
    <div class="table-responsive">
      <table class="table table-sm table-striped table-bordered tachograph-table">
        <thead>
          <tr>
            <th style="width: 120px;">Mois</th>
            <th>Chauffeur</th>
            <th style="width: 130px;">Conduite</th>
            <th style="width: 130px;">Travail</th>
            <th style="width: 130px;">Total actif</th>
            <th style="width: 120px;">Disponibilite</th>
            <th style="width: 100px;">Km</th>
          </tr>
        </thead>
        <tbody>
          @forelse($monthlySummaries as $row)
            <tr>
              <td>{{ \Carbon\Carbon::parse($row->period_start)->format('m/Y') }}</td>
              <td>{{ optional($row->acente)->name ?: 'Chauffeur non lie' }}</td>
              <td>{{ $formatMinutes($row->driving_minutes) }}</td>
              <td>{{ $formatMinutes($row->work_minutes) }}</td>
              <td><strong>{{ $formatMinutes($row->driving_minutes + $row->work_minutes) }}</strong></td>
              <td>{{ $formatMinutes($row->availability_minutes) }}</td>
              <td>{{ number_format((float)$row->distance_km, 0, ',', ' ') }}</td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted">Aucun resume mensuel.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="tachograph-panel">
    <h2 class="h5 mb-3">Cartes chauffeur</h2>
    <div class="table-responsive">
      <table class="table table-sm table-bordered tachograph-table">
        <thead>
          <tr>
            <th style="width: 170px;">Carte</th>
            <th>Nom C1B</th>
            <th>Chauffeur systeme</th>
            <th style="width: 360px;">Lier / modifier</th>
          </tr>
        </thead>
        <tbody>
          @forelse($cards as $card)
            <tr>
              <td>{{ $card->card_number }}</td>
              <td>{{ trim($card->driver_first_name . ' ' . $card->driver_last_name) ?: '-' }}</td>
              <td>
                @if($card->acente)
                  <span class="badge badge-success">{{ $card->acente->name }}</span>
                @else
                  <span class="badge badge-warning">A lier</span>
                @endif
              </td>
              <td>
                <form method="post" action="{{ route('tachograph.cards.map', $card) }}" class="d-flex">
                  @csrf
                  <select name="acente_id" class="form-control form-control-sm mr-2">
                    <option value="">Non lie</option>
                    @foreach($drivers as $driver)
                      <option value="{{ $driver->id }}" {{ (string)$card->acente_id === (string)$driver->id ? 'selected' : '' }}>{{ $driver->name }}</option>
                    @endforeach
                  </select>
                  <button class="btn btn-sm btn-outline-primary" type="submit">OK</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="text-center text-muted">Aucune carte importee.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="tachograph-panel">
    <h2 class="h5 mb-3">Dernieres conduites</h2>
    <div class="table-responsive">
      <table class="table table-sm table-striped table-bordered tachograph-table">
        <thead>
          <tr>
            <th style="width: 150px;">Debut</th>
            <th style="width: 150px;">Fin</th>
            <th style="width: 80px;">Duree</th>
            <th>Chauffeur</th>
            <th style="width: 90px;">Km jour</th>
            <th>Transfert associe</th>
            <th style="width: 130px;">Etat</th>
          </tr>
        </thead>
        <tbody>
          @forelse($drives as $drive)
            <tr>
              <td>{{ optional($drive->started_at)->format('d/m/Y H:i') }}</td>
              <td>{{ optional($drive->ended_at)->format('d/m/Y H:i') }}</td>
              <td>{{ $drive->duration_minutes }} min</td>
              <td>
                {{ optional($drive->acente)->name ?: trim($drive->driver_first_name ?? '') ?: optional($drive->card)->driver_first_name }}
                <div class="tachograph-muted">{{ $drive->card_number }}</div>
              </td>
              <td>{{ $drive->daily_distance_km !== null ? number_format((float)$drive->daily_distance_km, 0, ',', ' ') : '-' }}</td>
              <td>
                @if($drive->transfer_id)
                  <a href="{{ url('/transfers/' . $drive->transfer_id) }}" target="_blank">Transfer #{{ $drive->transfer_id }}</a>
                  @if($drive->transfer && $drive->transfer->post_id)
                    <span class="tachograph-muted">Dossier #{{ $drive->transfer->post_id }}</span>
                  @endif
                  <div class="tachograph-muted">{{ $drive->match_reason }}</div>
                @else
                  -
                @endif
              </td>
              <td>
                @if($drive->match_status === 'matched')
                  <span class="badge badge-success">Associe</span>
                @elseif($drive->match_status === 'driver_not_mapped')
                  <span class="badge badge-warning">Chauffeur non lie</span>
                @else
                  <span class="badge badge-secondary">A verifier</span>
                @endif
                @if($drive->match_score)
                  <div class="tachograph-muted">Score {{ $drive->match_score }}</div>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted">Aucune conduite.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    {{ $drives->links('pagination::bootstrap-4') }}
  </div>

  <div class="tachograph-panel">
    <h2 class="h5 mb-3">Derniers imports</h2>
    <div class="table-responsive">
      <table class="table table-sm table-bordered tachograph-table">
        <thead>
          <tr>
            <th>Fichier</th>
            <th>Carte</th>
            <th>Chauffeur</th>
            <th>Vehicules</th>
            <th>Periode</th>
            <th>Total</th>
            <th>Conduites</th>
            <th>Associees</th>
            <th>Ignorees</th>
            <th>Importe le</th>
          </tr>
        </thead>
        <tbody>
          @forelse($imports as $import)
            <tr>
              <td>{{ $import->original_filename }}</td>
              <td>{{ $import->card_number }}</td>
              <td>{{ optional($import->acente)->name ?: trim($import->driver_first_name . ' ' . $import->driver_last_name) }}</td>
              <td>
                @if(!empty($import->vehicle_plates))
                  {{ implode(', ', array_slice((array) $import->vehicle_plates, 0, 6)) }}
                  @if(count((array) $import->vehicle_plates) > 6)
                    <span class="tachograph-muted">+{{ count((array) $import->vehicle_plates) - 6 }}</span>
                  @endif
                @else
                  -
                @endif
                @if($import->issuing_authority)
                  <div class="tachograph-muted">{{ $import->issuing_authority }}</div>
                @endif
              </td>
              <td>
                {{ optional($import->activity_from)->format('d/m/Y') ?: '-' }}
                -
                {{ optional($import->activity_to)->format('d/m/Y') ?: '-' }}
              </td>
              <td>
                {{ intdiv((int) $import->total_driving_minutes, 60) }}h{{ str_pad((int) $import->total_driving_minutes % 60, 2, '0', STR_PAD_LEFT) }}
                <div class="tachograph-muted">{{ $import->total_distance_km !== null ? number_format((float)$import->total_distance_km, 0, ',', ' ') . ' km' : '-' }}</div>
              </td>
              <td>{{ $import->drives_count }}</td>
              <td>{{ $import->matched_count }}</td>
              <td>{{ $import->ignored_drives_count ?? 0 }}</td>
              <td>{{ optional($import->imported_at)->format('d/m/Y H:i') }}</td>
            </tr>
          @empty
            <tr><td colspan="10" class="text-center text-muted">Aucun import.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
