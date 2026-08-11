@extends('layouts.app')

@section('style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.bank-page{padding:14px;background:#f8fafc;min-height:calc(100vh - 90px)}.bank-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 20px rgba(15,23,42,.06);margin-bottom:14px;overflow:hidden}.bank-card-h{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:12px 14px;border-bottom:1px solid #e5e7eb}.bank-card-h h3{margin:0;font-size:20px;font-weight:850;color:#0f172a}.bank-form-grid{display:grid;grid-template-columns:repeat(5,minmax(150px,1fr));gap:10px;align-items:end}.bank-table th{font-size:12px;text-transform:uppercase;white-space:nowrap}.bank-table td{vertical-align:middle}.amount-input{min-width:105px}.manual-import{display:none}.manual-import.open{display:block}.bank-pagination{display:flex;justify-content:flex-end}.bank-pagination nav{display:flex;align-items:center;gap:8px}.bank-pagination svg{width:16px!important;height:16px!important}.bank-pagination .pagination{margin:0;gap:4px}.bank-pagination .page-link{padding:4px 8px;font-size:12px;line-height:1.2}@media(max-width:992px){.bank-form-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:576px){.bank-form-grid{grid-template-columns:1fr}.bank-page{padding:8px}}
</style>
@endsection

@section('content')
<div class="bank-page">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="bank-card">
        <div class="bank-card-h">
            <div>
                <h3>Import bancaire Pennylane</h3>
                <small class="text-muted">Les mouvements sont recuperes automatiquement depuis Pennylane. Aucun fichier n'est necessaire.</small>
            </div>
        </div>
        <div class="p-3">
            <form action="{{ route('bank.import.pennylane') }}" method="POST">
                @csrf
                <div class="bank-form-grid">
                    <div class="form-group mb-0">
                        <label>Société</label>
                        <select name="sirket_id" class="form-control" required onchange="window.location='{{ route('bank.import.form') }}?sirket_id='+this.value">
                            @foreach($sirkets as $id => $name)
                                <option value="{{ $id }}" {{ (string)$selectedSirketId === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Compte Pennylane</label>
                        <select name="pennylane_bank_account_id" id="pennylaneBankAccountSelect" class="form-control" required>
                            <option value="">Choisir</option>
                            @foreach($pennylaneBankAccounts as $account)
                                @php $mapKey = ($selectedPennylaneAccount ?: '').':'.($account['id'] ?? ''); @endphp
                                <option value="{{ $account['id'] ?? '' }}" data-acente="{{ $pennylaneMappings[$mapKey] ?? '' }}">{{ $account['name'] ?? 'Compte' }} @if(isset($account['balance'])) · {{ $account['balance'] }} {{ $account['currency'] ?? 'EUR' }} @endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Compte interne</label>
                        <select name="acente_id" id="internalBankAccountSelect" class="form-control">
                            <option value="">Choisir</option>
                            @foreach($acenteler as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Du</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date', now()->subDays(30)->toDateString()) }}" required>
                    </div>
                    <div class="form-group mb-0">
                        <label>Au</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date', now()->toDateString()) }}" required>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap" style="gap:8px">
                    <span class="text-muted small">Société Pennylane: {{ $selectedPennylaneAccount ? ucfirst($selectedPennylaneAccount) : 'non associee' }}</span>
                    <button type="submit" class="btn btn-primary">Récupérer depuis Pennylane</button>
                </div>
            </form>
        </div>
    </div>

    <div class="bank-card">
        <div class="bank-card-h">
            <div>
                <strong>Jumelage des comptes</strong><br>
                <small class="text-muted">Associez chaque compte Pennylane a son compte interne une seule fois.</small>
            </div>
        </div>
        <div class="p-3">
            <form action="{{ route('bank.import.pennylane-mappings') }}" method="POST">
                @csrf
                <input type="hidden" name="sirket_id" value="{{ $selectedSirketId }}">
                <input type="hidden" name="account_key" value="{{ $selectedPennylaneAccount }}">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light"><tr><th>Compte Pennylane</th><th>Solde</th><th>Compte interne</th></tr></thead>
                        <tbody>
                        @forelse($pennylaneBankAccounts as $account)
                            @php
                                $bankId = $account['id'] ?? null;
                                $mapKey = ($selectedPennylaneAccount ?: '').':'.$bankId;
                                $mappedAcenteId = $pennylaneMappings[$mapKey] ?? null;
                            @endphp
                            <tr>
                                <td><strong>{{ $account['name'] ?? 'Compte' }}</strong><br><small class="text-muted">Pennylane ID: {{ $bankId }}</small></td>
                                <td>{{ $account['balance'] ?? '-' }} {{ $account['currency'] ?? 'EUR' }}</td>
                                <td>
                                    <select name="mappings[{{ $bankId }}]" class="form-control form-control-sm select-acente-map">
                                        <option value="">Non jumelé</option>
                                        @foreach($acenteler as $id => $name)
                                            <option value="{{ $id }}" {{ (string)$mappedAcenteId === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">Aucun compte Pennylane disponible.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="text-right mt-3">
                    <button class="btn btn-outline-primary">Enregistrer le jumelage</button>
                </div>
            </form>
        </div>
    </div>

    <div class="bank-card">
        <div class="bank-card-h">
            <strong>Import manuel CSV</strong>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleManualImport">Afficher / masquer</button>
        </div>
        <div class="p-3 manual-import" id="manualImportBox">
            <form action="{{ route('bank.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="bank-form-grid">
                    <div class="form-group mb-0">
                        <label>Société</label>
                        <select name="sirket_id" class="form-control" required>
                            <option value="">Choisir</option>
                            @foreach($sirkets as $id => $name)
                                <option value="{{ $id }}" {{ (string)$selectedSirketId === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Compte bancaire interne</label>
                        <select name="acente_id" class="form-control" required>
                            <option value="">Choisir</option>
                            @foreach($acenteler as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Fichier CSV</label>
                        <input type="file" class="form-control" name="file" required>
                    </div>
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-outline-primary">Importer le fichier</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="bank-card">
        <div class="bank-card-h">
            <h3>Liste des mouvements bancaires</h3>
            <form method="GET" action="{{ route('bank.import.form') }}" class="form-inline mb-0">
                <label class="mr-2">Société</label>
                <select name="sirket_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">Toutes les sociétés</option>
                    @foreach($sirkets as $id => $name)
                        <option value="{{ $id }}" {{ (string)$selectedSirketId === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
                @if($selectedSirketId)
                    <a href="{{ route('bank.import.form') }}" class="btn btn-sm btn-outline-secondary">Réinitialiser</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm mb-0 bank-table">
                <thead class="thead-dark">
                    <tr>
                        <th>Société</th>
                        <th>Date</th>
                        <th>Libellé</th>
                        <th>Débit (€)</th>
                        <th>Crédit (€)</th>
                        <th>Montant</th>
                        <th>Devise</th>
                        <th>Libellé interbancaire</th>
                        <th>Tiers</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($veriler as $row)
                        @php
                            $found = null;
                            foreach($acentes as $acente) {
                                if (stripos($row->operation, $acente->name) !== false) {
                                    $found = $acente->id;
                                    break;
                                }
                            }
                            $amountValue = $row->debit ?? $row->credit ?? 0;
                        @endphp
                        <tr>
                            <td>{{ $sirkets[$row->sirket_id] ?? '-' }}</td>
                            <td>{{ \Carbon\Carbon::parse($row->date)->format('d.m.Y') }}</td>
                            <td>
                                <form method="POST" action="{{ route('bank.import.addoffset', $row->id) }}" id="bank-offset-{{ $row->id }}">
                                    @csrf
                                    <textarea name="aciklama" class="form-control" rows="4" required>{{ $row->operation }}</textarea>
                                    <input type="hidden" name="sirket_id" value="{{ $row->sirket_id }}">
                                    <input type="hidden" name="a_acente_id" value="{{ $row->acente_id }}">
                                    <input type="hidden" name="tarih" value="{{ $row->date }}">
                                    <input type="hidden" name="kur_id" value="1">
                                </form>
                            </td>
                            <td class="text-danger">{{ $row->debit }}</td>
                            <td class="text-success">{{ $row->credit }}</td>
                            <td><input type="text" name="amount" form="bank-offset-{{ $row->id }}" class="form-control amount-input" value="{{ $amountValue }}" required></td>
                            <td>{{ $row->currency }}</td>
                            <td>{{ $row->interbank_label }}</td>
                            <td>
                                <select name="b_acente_id" form="bank-offset-{{ $row->id }}" class="form-control form-control-sm select-acente" required>
                                    @foreach($acentes as $acente)
                                        <option value="{{ $acente->id }}" @if($found == $acente->id) selected @endif>{{ $acente->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-nowrap">
                                <button type="submit" form="bank-offset-{{ $row->id }}" class="btn btn-sm btn-success">Créer écriture</button>
                                <form action="{{ route('bank.import.delete', $row->id) }}" method="POST" onsubmit="return confirm('Supprimer cette ligne ?');" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">✘</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">Aucune donnée disponible</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 bank-pagination">{{ $veriler->links() }}</div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select-acente').select2({
            placeholder: "Choisir le tiers",
            allowClear: true,
            width: '100%'
        });
        $('.select-acente-map').select2({
            placeholder: "Choisir le compte interne",
            allowClear: true,
            width: '100%'
        });
        function applyPennylaneMapping() {
            var mapped = $('#pennylaneBankAccountSelect option:selected').data('acente');
            if (mapped) {
                $('#internalBankAccountSelect').val(String(mapped));
            }
        }
        $('#pennylaneBankAccountSelect').on('change', applyPennylaneMapping);
        applyPennylaneMapping();
        $('#toggleManualImport').on('click', function() {
            $('#manualImportBox').toggleClass('open');
        });
    });
</script>
@endsection