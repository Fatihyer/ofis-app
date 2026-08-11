@extends('layouts.app')

@section('style')
<style>
.penny-page{background:#f8fafc;min-height:calc(100vh - 90px);padding:16px}.penny-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:14px}.penny-head h1{margin:0;font-size:25px;font-weight:850;color:#0f172a}.penny-head small{color:#64748b;font-weight:700}.penny-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 22px rgba(15,23,42,.06);margin-bottom:14px;overflow:hidden}.penny-card-h{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:12px 14px;border-bottom:1px solid #e5e7eb;background:#fff}.penny-card-h strong{color:#0f172a}.penny-tools{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.penny-tabs{display:flex;flex-wrap:wrap;gap:8px}.penny-tabs a{border:1px solid #cbd5e1;border-radius:999px;padding:6px 10px;color:#334155;background:#fff;font-weight:750;font-size:12px}.penny-tabs a.active{background:#0f172a;color:#fff;border-color:#0f172a}.penny-alert{border-radius:8px;padding:12px 14px;margin-bottom:14px}.penny-alert.warn{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412}.penny-alert.err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}.penny-alert.ok{background:#ecfdf5;border:1px solid #bbf7d0;color:#166534}.penny-badge{display:inline-flex;align-items:center;border-radius:999px;background:#e0f2fe;color:#075985;padding:4px 9px;font-size:12px;font-weight:800}.penny-badge.gray{background:#f1f5f9;color:#475569}.penny-badge.green{background:#dcfce7;color:#166534}.penny-table th{font-size:12px;text-transform:uppercase;color:#475569;white-space:nowrap}.penny-table td{vertical-align:top}.json-cell{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:12px;white-space:pre-wrap;max-width:620px}.key-list{display:flex;flex-wrap:wrap;gap:6px}.key-list span{background:#f1f5f9;color:#475569;border-radius:999px;padding:3px 7px;font-size:11px;font-weight:750}.penny-select{min-width:220px;max-width:300px}.customer-name{font-weight:850;color:#0f172a}.customer-meta{color:#64748b;font-size:12px;margin-top:3px}.mapping-cell{min-width:260px}.mapping-cell input{font-size:12px}.penny-id-hint{font-size:11px;color:#64748b;margin-top:4px}.penny-create-result{font-size:11px;margin-top:6px;font-weight:800}.penny-create-result.ok{color:#166534}.penny-create-result.err{color:#991b1b}@media(max-width:768px){.penny-head{display:block}.penny-head form{margin-top:10px}.penny-page{padding:10px}.penny-tools select{width:100%!important}.penny-select{min-width:180px;max-width:100%}}
</style>
@endsection

@section('content')
@php
    $preview = function ($value) {
        if (is_array($value)) {
            $flat = [];
            foreach ($value as $key => $entry) {
                if (is_array($entry)) {
                    if (isset($entry['id'])) {
                        $flat[$key] = ['id' => $entry['id']];
                    } elseif (isset($entry['url'])) {
                        $flat[$key] = ['url' => $entry['url']];
                    }
                    continue;
                }
                $flat[$key] = $entry;
            }
            return json_encode($flat, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        if (is_bool($value)) return $value ? 'true' : 'false';
        return (string) $value;
    };
    $mainFields = ['id','name','company_name','first_name','last_name','email','number','invoice_number','date','created_at','updated_at','status','amount','total_amount','currency','label'];
@endphp

<div class="penny-page">
    <div class="penny-head">
        <div>
            <h1>Pennylane</h1>
            <small>Lecture seule: recuperation des donnees Pennylane, aucune ecriture envoyee.</small>
        </div>
        <form method="GET" action="{{ route('pennylane.index') }}" class="penny-tools">
            <input type="hidden" name="resource" value="{{ $resourceKey }}">
            <select name="account" class="form-control form-control-sm" onchange="this.form.submit()" style="width:160px">
                @foreach($accounts as $key => $label)
                    <option value="{{ $key }}" {{ $accountKey === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="limit" class="form-control form-control-sm" onchange="this.form.submit()" style="width:110px">
                @foreach([10,25,50,100] as $n)<option value="{{ $n }}" {{ (int)$limit===$n?'selected':'' }}>{{ $n }} lignes</option>@endforeach
            </select>
        </form>
    </div>

    @if(!$configured)
        <div class="penny-alert warn"><strong>Configuration manquante.</strong> Ajoutez la cle Pennylane de <strong>{{ $accountLabel }}</strong> dans <code>.env</code>. France Via utilise <code>PENNYLANE_API_KEY</code> ou <code>PENNYLANE_FRANCEVIA_API_KEY</code>; Paris Via utilise <code>PENNYLANE_PARISVIA_API_KEY</code>.</div>
    @elseif(!$result['ok'])
        <div class="penny-alert err"><strong>Erreur Pennylane</strong> <span class="penny-badge">{{ $accountLabel }}</span> @if($result['status'])HTTP {{ $result['status'] }} · @endif{{ $result['error'] }}</div>
    @else
        <div class="penny-alert ok"><strong>Connexion OK.</strong> <span class="penny-badge">{{ $accountLabel }}</span> Ressource chargee: {{ $resource['label'] }}.</div>
    @endif
    @if(session('success'))
        <div class="penny-alert ok">{{ session('success') }}</div>
    @endif

    <div class="penny-card">
        <div class="penny-card-h"><strong>Ressources</strong></div>
        <div class="p-3 penny-tabs">
            @foreach($resources as $key => $meta)
                <a href="{{ route('pennylane.index', ['account' => $accountKey, 'resource' => $key, 'limit' => $limit ?: 25]) }}" class="{{ $resourceKey === $key ? 'active' : '' }}">{{ $meta['label'] }}</a>
            @endforeach
        </div>
    </div>

    @if($mappingType)
        <form method="POST" action="{{ $mappingType === 'supplier' ? route('pennylane.supplier-mappings') : route('pennylane.customer-mappings') }}">
            @csrf
            <input type="hidden" name="account" value="{{ $accountKey }}">
            <input type="hidden" name="limit" value="{{ $limit ?: 25 }}">
            <datalist id="penny-acente-list">
                @foreach($acentes as $acenteId => $acenteName)
                    <option value="{{ $acenteId }}">#{{ $acenteId }} - {{ $acenteName }}</option>
                @endforeach
            </datalist>
    @endif

    <div class="penny-card">
        <div class="penny-card-h">
            <div>
                <strong>{{ $resource['label'] }}</strong>
                <span class="text-muted small">{{ count($items) }} element(s)</span>
            </div>
            @if($mappingType)
                <button type="submit" class="btn btn-primary btn-sm">Enregistrer le jumelage {{ $mappingLabel }}</button>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 penny-table">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Champs principaux</th>
                        @if($mappingType)<th>Jumelage {{ $mappingLabel }}</th>@endif
                        @if(!$mappingType)
                            <th>Autres cles</th>
                            <th>Apercu JSON</th>
                        @else
                            <th>Infos Pennylane</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                @forelse($items as $idx => $item)
                    @php
                        $item = is_array($item) ? $item : ['value' => $item];
                        $pennyId = $item['id'] ?? null;
                        $mapKey = $pennyId ? $accountKey.':'.$pennyId : null;
                        $savedMapping = $mapKey ? ($tierMappings[$mapKey] ?? null) : null;
                        $suggestedMapping = $pennyId ? ($tierSuggestions[$pennyId] ?? null) : null;
                        $selectedMapping = $savedMapping ?: $suggestedMapping;
                        $displayName = $item['company_name'] ?? $item['name'] ?? trim(($item['first_name'] ?? '').' '.($item['last_name'] ?? ''));
                    @endphp
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            @if($mappingType && $displayName)
                                <div class="customer-name">{{ $displayName }}</div>
                                @if(!empty($item['email']))<div class="customer-meta">{{ $item['email'] }}</div>@endif
                                @if(!empty($item['vat_number']))<div class="customer-meta">TVA: {{ $item['vat_number'] }}</div>@endif
                                @if(!empty($item['reg_no']))<div class="customer-meta">SIREN: {{ $item['reg_no'] }}</div>@endif
                                @if(!empty($item['id']))<div class="customer-meta">ID Pennylane: {{ $item['id'] }}</div>@endif
                            @endif
                            @foreach($mainFields as $field)
                                @if(array_key_exists($field, $item) && $item[$field] !== null && $item[$field] !== '')
                                    <div><strong>{{ $field }}:</strong> {{ is_array($item[$field]) ? json_encode($item[$field], JSON_UNESCAPED_UNICODE) : $item[$field] }}</div>
                                @endif
                            @endforeach
                        </td>
                        @if($mappingType)
                            <td class="mapping-cell">
                                @if($pennyId)
                                    <input type="number" name="mappings[{{ $pennyId }}]" value="{{ $selectedMapping ?: '' }}" list="penny-acente-list" class="form-control form-control-sm penny-select" placeholder="ID prestataire">
                                    @if($selectedMapping && isset($acentes[$selectedMapping]))
                                        <div class="penny-id-hint">#{{ $selectedMapping }} - {{ $acentes[$selectedMapping] }}</div>
                                    @else
                                        <div class="penny-id-hint">Tapez l'ID ou choisissez dans la liste.</div>
                                    @endif
                                    <div class="mt-2">
                                        @if($savedMapping)
                                            <span class="penny-badge green">Jumelé</span>
                                        @elseif($suggestedMapping)
                                            <span class="penny-badge">Suggestion</span>
                                        @else
                                            <span class="penny-badge gray">Aucun lien</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">ID Pennylane manquant</span>
                                @endif
                            </td>
                        @endif
                        @if(!$mappingType)
                            <td><div class="key-list">@foreach(array_keys($item) as $key)<span>{{ $key }}</span>@endforeach</div></td>
                            <td class="json-cell">{{ Str::limit($preview($item), 900) }}</td>
                        @else
                            <td>
                                @if($resourceKey === 'suppliers' && $pennyId)
                                    <div class="mb-2">
                                        @if($savedMapping)
                                            <a class="btn btn-sm btn-outline-success" href="{{ route('acentes.show', $savedMapping) }}">Ouvrir Laravel #{{ $savedMapping }}</a>
                                        @else
                                            <button type="button" class="btn btn-sm btn-success penny-create-supplier" data-supplier-id="{{ $pennyId }}">
                                                Ajouter fournisseur Laravel
                                            </button>
                                            <div class="penny-create-result" data-result-for="{{ $pennyId }}"></div>
                                        @endif
                                    </div>
                                @endif
                                @if(!empty($item['postal_address']) && is_array($item['postal_address']))
                                    <div class="customer-meta">{{ trim(($item['postal_address']['address'] ?? '').' '.($item['postal_address']['postal_code'] ?? '').' '.($item['postal_address']['city'] ?? '')) }}</div>
                                @endif
                                @if(!empty($item['ledger_account']['id']))
                                    <div class="customer-meta">Compte: {{ $item['ledger_account']['id'] }}</div>
                                @endif
                                @if(!empty($item['updated_at']))
                                    <div class="customer-meta">MAJ: {{ substr($item['updated_at'], 0, 10) }}</div>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $mappingType ? 5 : 4 }}" class="text-center text-muted py-4">Aucune donnee a afficher.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($mappingType)
        </form>
    @endif

    @if(!empty($data['next_cursor']))
        <a class="btn btn-outline-primary btn-sm" href="{{ route('pennylane.index', ['account' => $accountKey, 'resource' => $resourceKey, 'limit' => $limit ?: 25, 'cursor' => $data['next_cursor']]) }}">Page suivante</a>
    @endif
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('click', function (event) {
    const button = event.target.closest('.penny-create-supplier');
    if (!button) return;

    const supplierId = button.dataset.supplierId;
    const result = document.querySelector('[data-result-for="' + supplierId + '"]');

    button.disabled = true;
    button.textContent = 'Création...';
    if (result) {
        result.className = 'penny-create-result';
        result.textContent = '';
    }

    fetch("{{ route('pennylane.suppliers.import') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            account: '{{ $accountKey }}',
            supplier_id: supplierId
        })
    })
    .then(async function (response) {
        const data = await response.json().catch(function () { return {}; });
        if (!response.ok || !data.ok) {
            throw new Error(data.message || 'Création impossible.');
        }
        return data;
    })
    .then(function (data) {
        button.textContent = 'Créé';
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-success');
        if (result) {
            result.className = 'penny-create-result ok';
            result.innerHTML = 'Jumelé: <a href="' + data.url + '">#' + data.acente_id + ' - ' + data.acente_name + '</a>';
        }
    })
    .catch(function (error) {
        button.disabled = false;
        button.textContent = 'Ajouter fournisseur Laravel';
        if (result) {
            result.className = 'penny-create-result err';
            result.textContent = error.message;
        }
    });
});
</script>
@endsection
