@php
    $operationRows = old('operations');

    if (is_null($operationRows) && isset($talep) && $talep->days) {
        $operationRows = $talep->days;
    }

    $operationRows = $operationRows ?? [];
    $operationSummaryDays = collect($operationRows)->count();
    $operationSummaryMeters = collect($operationRows)->sum(fn ($operation) => (int) data_get($operation, 'distance_meters', 0));
    $operationSummaryKm = $operationSummaryMeters > 0 ? round($operationSummaryMeters / 1000, 1) : 0;
@endphp



<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <button type="button" class="btn btn-link p-0 text-decoration-none" id="toggleTalepXmlPanel" aria-expanded="false">
            <strong>Assistant XML</strong> <span id="toggleTalepXmlIcon">+</span>
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="copyTalepXmlBtn">Copier XML</button>
    </div>
    <div class="card-body d-none" id="talepXmlPanelBody">
        <div class="row">
            <div class="col-lg-6 mb-3">
                <label class="form-label">Texte de la demande</label>
                <textarea id="aiTalepText" class="form-control" rows="8" placeholder="Collez ici le message client..."></textarea>
                <button type="button" class="btn btn-primary mt-2" id="generateTalepXmlBtn">
                    <span class="talep-xml-btn-ready">Générer XML</span>
                    <span class="talep-xml-btn-loading d-none">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        <i class="fa fa-hourglass-half me-1" aria-hidden="true"></i>
                        Génération...
                    </span>
                </button>
            </div>
            <div class="col-lg-6 mb-3">
                <label class="form-label">XML</label>
                <textarea id="aiTalepXml" class="form-control" rows="8" placeholder="Le XML généré apparaîtra ici..."></textarea>
                <button type="button" class="btn btn-success mt-2" id="importTalepXmlBtn">Importer</button>
                <small id="aiTalepXmlMessage" class="d-block text-muted mt-2"></small>
            </div>
        </div>
    </div>
</div>

{{-- ÜST: TEK ROW / GENEL BİLGİLER --}}
<div class="card mb-4">
    <div class="card-header">
        Informations générales
    </div>
    <div class="card-body">
        <div class="row">

            <div class="col-lg-2 col-md-3 mb-3">
                <label class="form-label">Date de la demande</label>
                <input
                    type="datetime-local"
                    name="talep_tarihi"
                    id="talep_tarihi"
                    class="form-control"
                    value="{{ old('talep_tarihi', isset($talep) && $talep->talep_tarihi ? \Carbon\Carbon::parse($talep->talep_tarihi)->format('Y-m-d\TH:i') : \Carbon\Carbon::now('Europe/Paris')->format('Y-m-d\TH:i')) }}"
                    required
                >
            </div>

            <div class="col-lg-2 col-md-3 mb-3">
                <label class="form-label">Canal</label>
                @php
                    $kanal = old('talep_kanali', $talep->talep_kanali ?? '');
                @endphp
                <select name="talep_kanali" id="talep_kanali" class="form-control" required>
                    <option value="">Sélectionner</option>
                    <option value="Mail" {{ $kanal == 'Mail' ? 'selected' : '' }}>Mail</option>
                    <option value="WhatsApp" {{ $kanal == 'WhatsApp' ? 'selected' : '' }}>WhatsApp</option>
                    <option value="Téléphone" {{ $kanal == 'Téléphone' ? 'selected' : '' }}>Téléphone</option>
                    <option value="Website" {{ $kanal == 'Website' ? 'selected' : '' }}>Website</option>
                    <option value="Partner" {{ $kanal == 'Partner' ? 'selected' : '' }}>Partner</option>
                    <option value="Paris Via Web" {{ $kanal == 'Paris Via Web' ? 'selected' : '' }}>Paris Via Web</option>
                </select>
            </div>

            <div class="col-lg-2 col-md-3 mb-3">
                <label class="form-label">Pays</label>
                <input
                    type="text"
                    name="country"
                    id="country"
                    class="form-control"
                    value="{{ old('country', $talep->country ?? '') }}"
                    placeholder="Ex: France, Turquie, Brésil..."
                >
            </div>

            <div class="col-lg-2 col-md-3 mb-3">
                <label class="form-label">Type de service</label>
                <select name="service_type_id" id="service_type_id" class="form-control">
                    <option value="">Sélectionner</option>
                    @foreach($serviceTypes as $st)
                        <option value="{{ $st->id }}" {{ old('service_type_id', $talep->service_type_id ?? '') == $st->id ? 'selected' : '' }}>
                            {{ $st->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-2 col-md-3 mb-3">
                <label class="form-label">Véhicule</label>
                <select name="vehicule_id" id="vehicule_id" class="form-control">
                    <option value="">Sélectionner</option>
                    @foreach($vehicules as $vehicule)
                        <option value="{{ $vehicule->id }}" {{ old('vehicule_id', $talep->vehicule_id ?? '') == $vehicule->id ? 'selected' : '' }}>
                            {{ $vehicule->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if(!empty($depots) && $depots->count())
                <div class="col-lg-2 col-md-3 mb-3">
                    <label class="form-label">Dépôt</label>
                    <select name="depot_id" id="depot_id" class="form-control">
                        <option value="">Automatique</option>
                        @foreach($depots as $depot)
                            <option value="{{ $depot->id }}" {{ old('depot_id', $talep->depot_id ?? '') == $depot->id ? 'selected' : '' }}>
                                {{ $depot->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Calcul km / prix.</small>
                </div>
            @endif

            <div class="col-lg-2 col-md-3 mb-3">
                <label class="form-label">Agence</label>
                <select name="acente_id" id="acente_id" class="form-control">
                    <option value="">Sélectionner</option>
                    @foreach($acenteler as $acente)
                        <option value="{{ $acente->id }}" {{ old('acente_id', $talep->acente_id ?? '') == $acente->id ? 'selected' : '' }}>
                            {{ $acente->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-2 col-md-3 mb-3">
                <label class="form-label">Responsable</label>
                <select name="user_id" id="user_id" class="form-control">
                    <option value="">Sélectionner</option>
                    @foreach($kullanicilar as $kullanici)
                        <option value="{{ $kullanici->id }}" {{ old('user_id', $talep->user_id ?? auth()->id()) == $kullanici->id ? 'selected' : '' }}>
                            {{ $kullanici->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-2 col-md-3 mb-3">
                <label class="form-label">Client</label>
                <input
                    type="text"
                    name="customer_name"
                    id="customer_name"
                    class="form-control"
                    value="{{ old('customer_name', $talep->customer_name ?? '') }}"
                >
            </div>

            <div class="col-lg-2 col-md-3 mb-3">
                <label class="form-label">Téléphone</label>
                <input
                    type="text"
                    name="customer_phone"
                    id="customer_phone"
                    class="form-control"
                    value="{{ old('customer_phone', $talep->customer_phone ?? '') }}"
                >
            </div>

            <div class="col-lg-3 col-md-4 mb-3">
                <label class="form-label">Email</label>
                <input
                    type="email"
                    name="customer_email"
                    id="customer_email"
                    class="form-control"
                    value="{{ old('customer_email', $talep->customer_email ?? '') }}"
                >
            </div>

            <div class="col-lg-2 col-md-2 mb-3">
                <label class="form-label">Passagers</label>
                <input
                    type="number"
                    name="total_pax"
                    id="total_pax"
                    class="form-control"
                    value="{{ old('total_pax', $talep->total_pax ?? '') }}"
                >
            </div>

            <div class="col-lg-2 col-md-2 mb-3">
                <label class="form-label">Prix Equipe</label>
                <input
                    type="number"
                    step="0.01"
                    name="system_total"
                    id="system_total"
                    class="form-control"
                    value="{{ old('system_total', $talep->system_total ?? '') }}"
                >
            </div>

            <div class="col-lg-2 col-md-2 mb-3">
                <label class="form-label">Prix remise</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="discount_price"
                    id="discount_price"
                    class="form-control"
                    value="{{ old('discount_price', $talep->discount_price ?? '') }}"
                >
            </div>

            <div class="col-lg-2 col-md-2 mb-3">
                <label class="form-label">2ème remise</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="second_discount_price"
                    id="second_discount_price"
                    class="form-control"
                    value="{{ old('second_discount_price', $talep->second_discount_price ?? '') }}"
                >
            </div>

            @php
                $canEditPrixAdmin = auth()->user()?->hasRole('Superadmin');
            @endphp
            <div class="col-lg-2 col-md-2 mb-3">
                <label class="form-label">Prix admin</label>
                <input
                    type="number"
                    step="0.01"
                    name="final_total"
                    id="final_total"
                    class="form-control {{ $canEditPrixAdmin ? '' : 'bg-light text-muted' }}"
                    value="{{ old('final_total', $talep->final_total ?? '') }}"
                    @unless($canEditPrixAdmin) readonly tabindex="-1" style="pointer-events: none;" @endunless
                >
            </div>

            <div class="col-lg-4 col-md-4 mb-3">
                <label class="form-label">Commentaire admin</label>
                <textarea
                    name="comment_admin"
                    id="comment_admin"
                    rows="2"
                    class="form-control {{ $canEditPrixAdmin ? '' : 'bg-light text-muted' }}"
                    @unless($canEditPrixAdmin) readonly tabindex="-1" style="pointer-events: none;" @endunless
                >{{ old('comment_admin', $talep->comment_admin ?? '') }}</textarea>
            </div>

            <div class="col-lg-2 col-md-2 mb-3">
                <label class="form-label">Prix communiqué</label>
                <input
                    type="number"
                    step="0.01"
                    name="verilen_fiyat"
                    id="verilen_fiyat"
                    class="form-control"
                    value="{{ old('verilen_fiyat', $talep->verilen_fiyat ?? '') }}"
                >
            </div>

            <div class="col-lg-2 col-md-2 mb-3">
                <label class="form-label">Prix confirmé</label>
                <input
                    type="number"
                    step="0.01"
                    name="confirmed_price"
                    id="confirmed_price"
                    class="form-control"
                    value="{{ old('confirmed_price', $talep->confirmed_price ?? '') }}"
                >
            </div>

            <div class="col-lg-1 col-md-2 mb-3">
                <label class="form-label">Devise</label>
                <input
                    type="text"
                    name="currency"
                    id="currency"
                    class="form-control"
                    value="{{ old('currency', $talep->currency ?? 'EUR') }}"
                >
            </div>

            <div class="col-lg-2 col-md-3 mb-3">
                <label class="form-label">Relance</label>
                @php
                    $relance = old('relance_yapildi', $talep->relance_yapildi ?? 0);
                @endphp
                <select name="relance_yapildi" id="relance_yapildi" class="form-control">
                    <option value="0" {{ $relance == 0 ? 'selected' : '' }}>Non</option>
                    <option value="1" {{ $relance == 1 ? 'selected' : '' }}>Oui</option>
                </select>
            </div>

            <div class="col-lg-2 col-md-3 mb-3">
                <label class="form-label">Statut</label>
                @php
                    $durum = old('konfirme_durumu', $talep->konfirme_durumu ?? 'En attente');
                @endphp
                <select name="konfirme_durumu" id="konfirme_durumu" class="form-control">
                    <option value="En attente" {{ $durum == 'En attente' ? 'selected' : '' }}>En attente</option>
                    <option value="Devis envoyé" {{ $durum == 'Devis envoyé' ? 'selected' : '' }}>Devis envoyé</option>
                    <option value="En suivi" {{ $durum == 'En suivi' ? 'selected' : '' }}>En suivi</option>
                    <option value="Confirmé" {{ $durum == 'Confirmé' ? 'selected' : '' }}>Confirmé</option>
                    <option value="Annulé" {{ $durum == 'Annulé' ? 'selected' : '' }}>Annulé</option>
                    <option value="Perdu" {{ $durum == 'Perdu' ? 'selected' : '' }}>Perdu</option>
                </select>
            </div>

            @if(isset($talep) && $talep->convertedTransfer && $talep->convertedTransfer->post)
                <div class="col-lg-4 col-md-6 mb-3">
                    <label class="form-label">Dossier créé</label>
                    <div>
                        <a href="{{ route('posts.show', $talep->convertedTransfer->post->id) }}" class="btn btn-sm btn-success">
                            Ouvrir le dossier #{{ $talep->convertedTransfer->post->id }}
                        </a>
                        <a href="{{ route('transfers.show', $talep->convertedTransfer->id) }}" class="btn btn-sm btn-outline-success">
                            Transfert #{{ $talep->convertedTransfer->id }}
                        </a>
                    </div>
                </div>
            @endif

            <div class="col-lg-6 mb-3">
                <label class="form-label">Message</label>
                <textarea
                    name="uzun_mesaj"
                    id="uzun_mesaj"
                    class="form-control"
                    rows="3"
                >{{ old('uzun_mesaj', $talep->uzun_mesaj ?? '') }}</textarea>
            </div>

            <div class="col-lg-6 mb-3">
                <label class="form-label">Notes internes</label>
                <textarea
                    name="internal_notes"
                    id="internal_notes"
                    class="form-control"
                    rows="3"
                >{{ old('internal_notes', $talep->internal_notes ?? '') }}</textarea>
            </div>

        </div>
    </div>
</div>

{{-- ALT: SOL OPERATION / SAĞ HARİTA --}}
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Opérations</span>
                <button type="button" class="btn btn-sm btn-primary" id="addOperationRow">
                    + Ajouter une opération
                </button>
            </div>

            <div class="card-body">
                <div class="row mb-3 talep-operation-summary talep-operation-summary-top">
                    <div class="col-md-6 mb-2">
                        <div class="border rounded bg-light p-2 h-100">
                            <small class="text-muted">Total jours</small>
                            <div class="fw-bold operation-total-days">{{ $operationSummaryDays ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <div class="border rounded bg-light p-2 h-100">
                            <small class="text-muted">Distance totale</small>
                            <div class="fw-bold operation-total-km">{{ $operationSummaryKm > 0 ? number_format($operationSummaryKm, 1, ',', ' ') . ' km' : '-' }}</div>
                        </div>
                    </div>
                </div>

                <div id="operationsWrapper">

                    @foreach($operationRows as $index => $operation)
                        <div class="card mb-3 operation-row" data-index="{{ $index }}">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                @php
                                    $operationServiceTitle = data_get($operation, 'service_type');
                                @endphp
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <strong>Opération #<span class="operation-number">{{ $index + 1 }}</span></strong>
                                    <span class="badge bg-info text-dark operation-service-badge {{ $operationServiceTitle ? '' : 'd-none' }}">{{ $operationServiceTitle }}</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-danger removeOperationRow">Supprimer</button>
                            </div>

                            <div class="card-body">
                                <input type="hidden" name="operations[{{ $index }}][id]" value="{{ $operation['id'] ?? '' }}">
                                <input type="hidden" name="operations[{{ $index }}][distance_meters]" value="{{ $operation['distance_meters'] ?? '' }}">
                                <input type="hidden" name="operations[{{ $index }}][duration_seconds]" value="{{ $operation['duration_seconds'] ?? '' }}">
                                <input type="hidden" name="operations[{{ $index }}][traffic_duration_seconds]" value="{{ $operation['traffic_duration_seconds'] ?? '' }}">
                                <input type="hidden" name="operations[{{ $index }}][polyline]" value="{{ $operation['polyline'] ?? '' }}">
                                <input type="hidden" name="operations[{{ $index }}][toll_currency]" value="{{ $operation['toll_currency'] ?? 'EUR' }}">

                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">Date</label>
                                        @php
                                                $serviceDate = old('operations.' . $index . '.service_date');

                                                if (is_null($serviceDate)) {
                                                    if (is_object($operation) && method_exists($operation, 'getRawOriginal')) {
                                                        $serviceDate = $operation->getRawOriginal('service_date');
                                                    } else {
                                                        $serviceDate = $operation['service_date'] ?? '';
                                                    }
                                                }

                                                $serviceDate = $serviceDate ? substr($serviceDate, 0, 10) : '';
                                            @endphp
                                        <input
    type="date"
    name="operations[{{ $index }}][service_date]"
    class="form-control"
    value="{{ $serviceDate ?? '' }}"
>

                                                                                </div>

                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">Début</label>
                                        <input type="time" name="operations[{{ $index }}][start_time]" class="form-control" value="{{ $operation['start_time'] ?? '' }}">
                                    </div>

                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">Fin</label>
                                        <input type="time" name="operations[{{ $index }}][end_time]" class="form-control" value="{{ $operation['end_time'] ?? '' }}">
                                    </div>

                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">Pax</label>
                                        <input type="number" name="operations[{{ $index }}][pax]" class="form-control" value="{{ $operation['pax'] ?? '' }}">
                                    </div>

                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Début</label>
                                        <input type="text" name="operations[{{ $index }}][pickup_location]" class="form-control operation-pickup google-address-input" value="{{ $operation['pickup_location'] ?? '' }}" autocomplete="off">
                                    </div>

                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Fin</label>
                                        <input type="text" name="operations[{{ $index }}][dropoff_location]" class="form-control operation-dropoff google-address-input" value="{{ $operation['dropoff_location'] ?? '' }}" autocomplete="off">
                                    </div>

                                    @php
                                        $waypoints = old('operations.' . $index . '.waypoints');

                                        if (is_null($waypoints)) {
                                            if (is_object($operation) && isset($operation->via_points_json)) {
                                                $waypoints = $operation->via_points_json;
                                            } else {
                                                $waypoints = $operation['via_points_json'] ?? [];
                                            }
                                        }

                                        if (is_string($waypoints)) {
                                            $decodedWaypoints = json_decode($waypoints, true);
                                            $waypoints = is_array($decodedWaypoints) ? $decodedWaypoints : [];
                                        }

                                        $waypoints = array_values(array_filter((array) $waypoints));
                                    @endphp

                                    <div class="col-md-12 mb-2 operation-waypoints">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label mb-0">Étapes intermédiaires</label>
                                            <button type="button" class="btn btn-sm btn-outline-secondary addOperationWaypoint">+ Ajouter une étape</button>
                                        </div>
                                        <div class="operation-waypoints-wrapper">
                                            @foreach($waypoints as $waypoint)
                                                <div class="input-group input-group-sm mb-2 operation-waypoint-row">
                                                    <input type="text" name="operations[{{ $index }}][waypoints][]" class="form-control operation-waypoint google-address-input" value="{{ $waypoint }}" placeholder="Adresse de l'étape" autocomplete="off">
                                                    <button type="button" class="btn btn-outline-danger removeOperationWaypoint">Supprimer</button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Type de service</label>
                                      <select name="operations[{{ $index }}][service_type]" class="form-control operation-service-type">
                                           <option value="">Sélectionner</option>
                                            @foreach($serviceTypes as $serviceType)
                                                <option value="{{ $serviceType->name }}" {{ ($operation['service_type'] ?? '') == $serviceType->name ? 'selected' : '' }}>
                                                        {{ $serviceType->name }}
                                                    </option>

                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Véhicule</label>
                                      <select name="operations[{{ $index }}][vehicle_type]" class="form-control operation-vehicle-type">

                                            <option value="">Sélectionner</option>
                                            @foreach($vehicules as $vehicule)
                                                <option value="{{ $vehicule->name }}" {{ ($operation['vehicle_type'] ?? '') == $vehicule->name ? 'selected' : '' }}>
                                                {{ $vehicule->name }}
                                            </option>

                                            @endforeach
                                        </select>
                                    </div>

                                    @if(!empty($depots) && $depots->count())
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Dépôt</label>
                                            <select name="operations[{{ $index }}][depot_id]" class="form-control">
                                                <option value="">Automatique</option>
                                                @foreach($depots as $depot)
                                                    <option value="{{ $depot->id }}" {{ ($operation['depot_id'] ?? '') == $depot->id ? 'selected' : '' }}>
                                                        {{ $depot->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif

                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Description</label>
                                        <input type="text" name="operations[{{ $index }}][route_description]" class="form-control" value="{{ $operation['route_description'] ?? '' }}">
                                    </div>

                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Péages</label>
                                        <input type="number" step="0.01" min="0" name="operations[{{ $index }}][toll_amount]" class="form-control operation-toll" value="{{ $operation['toll_amount'] ?? '' }}" placeholder="Montant des péages">
                                    </div>

                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Découcher</label>
                                        <input type="number" step="0.01" min="0" name="operations[{{ $index }}][decoucher]" class="form-control" value="{{ $operation['decoucher'] ?? '' }}" placeholder="Montant découcher">
                                    </div>

                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Parking</label>
                                        <input type="number" step="0.01" min="0" name="operations[{{ $index }}][parking]" class="form-control" value="{{ $operation['parking'] ?? '' }}" placeholder="Montant parking">
                                    </div>

                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Checkpoint</label>
                                        <input type="number" step="0.01" min="0" name="operations[{{ $index }}][checkpoint]" class="form-control" value="{{ $operation['checkpoint'] ?? '' }}" placeholder="Montant checkpoint">
                                    </div>

                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Carburant estimé</label>
                                        <input type="number" step="0.01" min="0" name="operations[{{ $index }}][fuel_amount]" class="form-control bg-light operation-fuel-amount" value="{{ $operation['fuel_amount'] ?? '' }}" readonly>
                                    </div>

                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Litres estimés</label>
                                        <input type="number" step="0.01" min="0" name="operations[{{ $index }}][fuel_liters]" class="form-control bg-light operation-fuel-liters" value="{{ $operation['fuel_liters'] ?? '' }}" readonly>
                                    </div>

                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Prix AI / jour</label>
                                        <input type="number" step="0.01" min="0" name="operations[{{ $index }}][system_price]" class="form-control bg-light operation-system-price" value="{{ old('operations.' . $index . '.system_price', $operation['system_price'] ?? '') }}" readonly>
                                    </div>

                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Prix équipe</label>
                                        <input type="number" step="0.01" min="0" name="operations[{{ $index }}][final_price]" class="form-control operation-final-price" value="{{ old('operations.' . $index . '.final_price', $operation['final_price'] ?? '') }}" placeholder="Prix équipe manuel">
                                    </div>

                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Devise</label>
                                        <input type="text" class="form-control bg-light" value="{{ old('currency', $talep->currency ?? 'EUR') }}" readonly>
                                    </div>

                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Notes générales</label>
                                        <textarea name="operations[{{ $index }}][notes]" class="form-control" rows="2">{{ $operation['notes'] ?? '' }}</textarea>
                                    </div>

                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Note équipe</label>
                                        <textarea name="operations[{{ $index }}][note_equipe]" class="form-control" rows="2">{{ old('operations.' . $index . '.note_equipe', $operation['note_equipe'] ?? '') }}</textarea>
                                    </div>

                                    @if(auth()->user()?->hasRole('Superadmin'))
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Note admin</label>
                                            <textarea name="operations[{{ $index }}][note_admin]" class="form-control" rows="2">{{ old('operations.' . $index . '.note_admin', $operation['note_admin'] ?? '') }}</textarea>
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm calculateOperationRoute">
                                        Calculer la distance
                                    </button>

                                    @php
                                        $operationDistanceMeters = data_get($operation, 'distance_meters');
                                        $operationDurationSeconds = data_get($operation, 'duration_seconds');
                                        $operationDistanceText = is_numeric($operationDistanceMeters) && (float) $operationDistanceMeters > 0
                                            ? round(((float) $operationDistanceMeters) / 1000, 1) . ' km'
                                            : '-';
                                        $operationDurationText = is_numeric($operationDurationSeconds) && (float) $operationDurationSeconds > 0
                                            ? floor(((float) $operationDurationSeconds) / 60) . ' min'
                                            : '-';
                                    @endphp
                                    <span class="ms-3">
                                        <strong>Distance:</strong> <span class="operation-distance">{{ $operationDistanceText }}</span>
                                    </span>

                                    <span class="ms-3">
                                        <strong>Durée:</strong> <span class="operation-duration">{{ $operationDurationText }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>

                <div class="row mt-3 talep-operation-summary talep-operation-summary-bottom">
                    <div class="col-md-6 mb-2">
                        <div class="border rounded bg-light p-2 h-100">
                            <small class="text-muted">Total jours</small>
                            <div class="fw-bold operation-total-days">{{ $operationSummaryDays ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <div class="border rounded bg-light p-2 h-100">
                            <small class="text-muted">Distance totale</small>
                            <div class="fw-bold operation-total-km">{{ $operationSummaryKm > 0 ? number_format($operationSummaryKm, 1, ',', ' ') . ' km' : '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-success">
                        Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        @include('talepler.partials.map')

        @if(isset($talep) && $talep->exists)
            @include('talepler.partials.mail-thread', ['talep' => $talep])
        @endif
    </div>
</div>


<script>
window.initTalepGooglePlaces = function () {
    window.talepGooglePlacesLoaded = true;
    if (typeof window.initGoogleAddressAutocomplete === 'function') {
        window.initGoogleAddressAutocomplete(document);
    }
};
window.gm_authFailure = function () {
    window.talepGoogleMapsAuthFailed = true;
    if (typeof window.showTalepGoogleMapsWarning === 'function') {
        window.showTalepGoogleMapsWarning('Google Maps ne peut pas charger les adresses. Verifier la cle API Google Maps.');
    }
};

document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('toggleTalepXmlPanel');
    const toggleIcon = document.getElementById('toggleTalepXmlIcon');
    const panelBody = document.getElementById('talepXmlPanelBody');
    const textInput = document.getElementById('aiTalepText');
    const xmlInput = document.getElementById('aiTalepXml');
    const message = document.getElementById('aiTalepXmlMessage');
    const generateBtn = document.getElementById('generateTalepXmlBtn');
    const copyBtn = document.getElementById('copyTalepXmlBtn');
    const importBtn = document.getElementById('importTalepXmlBtn');

    if (toggleBtn && panelBody) {
        toggleBtn.addEventListener('click', function () {
            const isClosed = panelBody.classList.contains('d-none');
            panelBody.classList.toggle('d-none', !isClosed);
            toggleBtn.setAttribute('aria-expanded', isClosed ? 'true' : 'false');
            if (toggleIcon) toggleIcon.innerText = isClosed ? '-' : '+';
        });
    }

    function setMessage(text) {
        if (message) message.innerText = text || '';
    }

    function setGenerateXmlLoading(isLoading) {
        if (!generateBtn) return;

        const readyLabel = generateBtn.querySelector('.talep-xml-btn-ready');
        const loadingLabel = generateBtn.querySelector('.talep-xml-btn-loading');

        generateBtn.disabled = isLoading;
        generateBtn.classList.toggle('disabled', isLoading);
        generateBtn.setAttribute('aria-busy', isLoading ? 'true' : 'false');

        if (readyLabel && loadingLabel) {
            readyLabel.classList.toggle('d-none', isLoading);
            loadingLabel.classList.toggle('d-none', !isLoading);
        }
    }

    function nodeText(parent, selector) {
        const node = parent.querySelector(selector);
        return node ? node.textContent.trim() : '';
    }

    function setField(id, value) {
        const field = document.getElementById(id);
        if (!field || value === '') return;
        field.value = value;
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function setSelectByValueOrText(id, value) {
        const select = document.getElementById(id);
        if (!select || value === '') return;

        const option = Array.from(select.options).find(item =>
            item.value === value || item.text.trim().toLowerCase() === value.toLowerCase()
        );

        if (option) {
            select.value = option.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function setScopedInput(row, namePart, value) {
        if (value === '') return;
        const input = row.querySelector(`[name$="[${namePart}]"]`);
        if (input) input.value = value;
    }

    function setScopedSelectByText(row, namePart, value) {
        if (value === '') return;
        const select = row.querySelector(`select[name$="[${namePart}]"]`);
        if (!select) return;

        const option = Array.from(select.options).find(item =>
            item.value === value || item.text.trim().toLowerCase() === value.toLowerCase()
        );

        if (option) select.value = option.value;
    }

    if (generateBtn) {
        generateBtn.addEventListener('click', function () {
            const rawText = textInput?.value.trim() || '';

            if (!rawText) {
                setMessage('Veuillez saisir le texte de la demande.');
                return;
            }

            setGenerateXmlLoading(true);
            setMessage('Conversion en cours...');

            fetch("{{ route('talepler.aiXml') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message: rawText })
            })
            .then(async response => {
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message || 'La conversion XML a échoué.');
                }

                xmlInput.value = data.xml || '';
                setMessage('XML généré.');
            })
            .catch(error => {
                setMessage(error.message);
            })
            .finally(() => {
                setGenerateXmlLoading(false);
            });
        });
    }

    if (copyBtn) {
        copyBtn.addEventListener('click', async function () {
            if (!xmlInput?.value.trim()) {
                setMessage('Aucun XML à copier.');
                return;
            }

            await navigator.clipboard.writeText(xmlInput.value);
            setMessage('XML copié.');
        });
    }

    if (importBtn) {
        importBtn.addEventListener('click', function () {
            const xml = xmlInput?.value.trim() || '';

            if (!xml) {
                setMessage('Aucun XML à importer.');
                return;
            }

            const doc = new DOMParser().parseFromString(xml, 'application/xml');

            if (doc.querySelector('parsererror')) {
                setMessage('XML invalide.');
                return;
            }

            const root = doc.querySelector('demande');

            if (!root) {
                setMessage('Balise <demande> introuvable.');
                return;
            }

            setField('talep_tarihi', nodeText(root, 'date_demande') || nodeText(root, 'talep_tarihi'));
            setSelectByValueOrText('talep_kanali', nodeText(root, 'talep_kanali'));
            setField('country', nodeText(root, 'country') || nodeText(root, 'pays'));
            setField('customer_name', nodeText(root, 'customer_name'));
            setField('customer_phone', nodeText(root, 'customer_phone'));
            setField('customer_email', nodeText(root, 'customer_email'));
            setField('total_pax', nodeText(root, 'total_pax'));
            setField('system_total', nodeText(root, 'prix_propose'));
            setField('verilen_fiyat', nodeText(root, 'prix_communique'));
            setField('currency', nodeText(root, 'currency') || 'EUR');
            setSelectByValueOrText('konfirme_durumu', nodeText(root, 'konfirme_durumu'));
            setField('uzun_mesaj', nodeText(root, 'uzun_mesaj'));
            setField('internal_notes', nodeText(root, 'internal_notes'));

            const wrapper = document.getElementById('operationsWrapper');
            const addButton = document.getElementById('addOperationRow');
            const operations = Array.from(root.querySelectorAll('operations > operation'));

            if (wrapper && addButton && operations.length) {
                wrapper.innerHTML = '';

                operations.forEach(operation => {
                    addButton.click();
                    const row = wrapper.querySelector('.operation-row:last-child');
                    if (!row) return;

                    setScopedInput(row, 'service_date', nodeText(operation, 'date_operation') || nodeText(operation, 'service_date'));
                    setScopedInput(row, 'start_time', nodeText(operation, 'start_time'));
                    setScopedInput(row, 'end_time', nodeText(operation, 'end_time'));
                    setScopedInput(row, 'pax', nodeText(operation, 'pax'));
                    setScopedInput(row, 'pickup_location', nodeText(operation, 'pickup_location'));
                    setScopedInput(row, 'dropoff_location', nodeText(operation, 'dropoff_location'));
                    setScopedSelectByText(row, 'service_type', nodeText(operation, 'service_type'));
                    updateOperationServiceLabel(row);
                    setScopedSelectByText(row, 'vehicle_type', nodeText(operation, 'vehicle_type'));
                    setScopedInput(row, 'route_description', nodeText(operation, 'route_description'));
                    setScopedInput(row, 'notes', nodeText(operation, 'notes'));

                    const waypointWrapper = row.querySelector('.operation-waypoints-wrapper');
                    const rowIndex = row.dataset.index;

                    Array.from(operation.querySelectorAll('waypoints > waypoint')).forEach(waypoint => {
                        const value = waypoint.textContent.trim();
                        if (!value || !waypointWrapper) return;

                        waypointWrapper.insertAdjacentHTML('beforeend', `
                            <div class="input-group input-group-sm mb-2 operation-waypoint-row">
                                <input type="text" name="operations[${rowIndex}][waypoints][]" class="form-control operation-waypoint google-address-input" value="${value.replace(/"/g, '&quot;')}" placeholder="Adresse de l'étape" autocomplete="off">
                                <button type="button" class="btn btn-outline-danger removeOperationWaypoint">Supprimer</button>
                            </div>
                        `);
                    });
                });
            }

            setMessage('XML importé dans le formulaire.');
        });
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const talepDepotOptionsHtml = @json((!empty($depots) && $depots->count()) ? '<div class="col-md-4 mb-2"><label class="form-label">Dépôt</label><select name="operations[__INDEX__][depot_id]" class="form-control"><option value="">Automatique</option>' . $depots->map(fn($depot) => '<option value="' . $depot->id . '">' . e($depot->name) . '</option>')->implode('') . '</select></div>' : '');
    let operationIndex = {{ count($operationRows) }};

    function formatOperationKm(totalMeters) {
        const totalKm = Number(totalMeters || 0) / 1000;

        return totalKm > 0
            ? totalKm.toLocaleString('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + ' km'
            : '-';
    }

    function updateTalepOperationTotals() {
        const rows = Array.from(document.querySelectorAll('#operationsWrapper .operation-row'));
        let totalMeters = 0;

        rows.forEach(row => {
            const distanceInput = row.querySelector('input[name$="[distance_meters]"]');
            totalMeters += Number(distanceInput?.value || row.dataset.distanceMeters || 0);
        });

        document.querySelectorAll('.operation-total-days').forEach(el => {
            el.innerText = rows.length || '-';
        });

        document.querySelectorAll('.operation-total-km').forEach(el => {
            el.innerText = formatOperationKm(totalMeters);
        });
    }

    window.updateTalepOperationTotals = updateTalepOperationTotals;

    function showGoogleMapsWarning(message) {
        if (document.getElementById('talep-google-maps-warning')) {
            return;
        }

        const wrapper = document.querySelector('#operationsWrapper') || document.querySelector('form') || document.body;
        if (!wrapper) {
            return;
        }

        const alert = document.createElement('div');
        alert.id = 'talep-google-maps-warning';
        alert.className = 'alert alert-warning mb-3';
        alert.textContent = message;
        wrapper.parentNode.insertBefore(alert, wrapper);
    }
    window.showTalepGoogleMapsWarning = showGoogleMapsWarning;

    function initGoogleAddressAutocomplete(context = document) {
        if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
            return;
        }

        context.querySelectorAll('.google-address-input').forEach(function (input) {
            if (input.dataset.googleAutocompleteReady === '1') {
                return;
            }

            const autocomplete = new google.maps.places.Autocomplete(input, {
                fields: ['formatted_address', 'name']
            });

            autocomplete.addListener('place_changed', function () {
                const place = autocomplete.getPlace();
                input.value = place.formatted_address || place.name || input.value;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });

            input.dataset.googleAutocompleteReady = '1';
        });
    }

    window.initGoogleAddressAutocomplete = initGoogleAddressAutocomplete;
    if (window.talepGooglePlacesLoaded) {
        initGoogleAddressAutocomplete(document);
    }
    window.gm_authFailure = function () {
        showGoogleMapsWarning('Google Maps ne peut pas charger les adresses. Verifier la cle API Google Maps.');
    };
    if (window.talepGoogleMapsAuthFailed) {
        window.gm_authFailure();
    }
    initGoogleAddressAutocomplete();

    function selectedGeneralVehicleName() {
        const select = document.getElementById('vehicule_id');
        if (!select || !select.value) return '';
        return (select.options[select.selectedIndex]?.text || '').trim();
    }

    function selectedOptionText(select) {
        return (select?.options[select.selectedIndex]?.text || '').trim();
    }

    function setSelectByText(select, value) {
        if (!select || !value) return false;

        const normalizedValue = value.trim().toLowerCase();
        const option = Array.from(select.options).find(item =>
            item.value.trim().toLowerCase() === normalizedValue ||
            item.text.trim().toLowerCase() === normalizedValue
        );

        if (!option) return false;
        select.value = option.value;
        return true;
    }

    function applyDefaultVehicleToOperationRow(row, force = false, previousDefault = '') {
        const vehicleName = selectedGeneralVehicleName();
        const select = row?.querySelector('.operation-vehicle-type');
        if (!vehicleName || !select) return;

        const currentText = selectedOptionText(select);
        const currentlyDefault = previousDefault && currentText.toLowerCase() === previousDefault.toLowerCase();

        if (force || !select.value || currentlyDefault) {
            setSelectByText(select, vehicleName);
        }
    }

    function applyDefaultVehicleToOperationRows(force = false, previousDefault = '') {
        document.querySelectorAll('.operation-row').forEach(row => applyDefaultVehicleToOperationRow(row, force, previousDefault));
    }

    let lastGeneralVehicleName = selectedGeneralVehicleName();
    applyDefaultVehicleToOperationRows(false);

    document.getElementById('vehicule_id')?.addEventListener('change', function () {
        applyDefaultVehicleToOperationRows(false, lastGeneralVehicleName);
        lastGeneralVehicleName = selectedGeneralVehicleName();
    });

    document.getElementById('addOperationRow').addEventListener('click', function () {
        const html = `
            <div class="card mb-3 operation-row" data-index="${operationIndex}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <strong>Opération #<span class="operation-number">${operationIndex + 1}</span></strong>
                        <span class="badge bg-info text-dark operation-service-badge d-none"></span>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger removeOperationRow">Supprimer</button>
                </div>

                <div class="card-body">
                    <input type="hidden" name="operations[${operationIndex}][id]" value="">
                    <input type="hidden" name="operations[${operationIndex}][distance_meters]" value="">
                    <input type="hidden" name="operations[${operationIndex}][duration_seconds]" value="">
                    <input type="hidden" name="operations[${operationIndex}][traffic_duration_seconds]" value="">
                    <input type="hidden" name="operations[${operationIndex}][polyline]" value="">
                    <input type="hidden" name="operations[${operationIndex}][toll_currency]" value="EUR">

                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Date</label>
                            <input type="date" name="operations[${operationIndex}][service_date]" class="form-control">
                        </div>

                        <div class="col-md-3 mb-2">
                            <label class="form-label">Début</label>
                            <input type="time" name="operations[${operationIndex}][start_time]" class="form-control">
                        </div>

                        <div class="col-md-3 mb-2">
                            <label class="form-label">Fin</label>
                            <input type="time" name="operations[${operationIndex}][end_time]" class="form-control">
                        </div>

                        <div class="col-md-3 mb-2">
                            <label class="form-label">Pax</label>
                            <input type="number" name="operations[${operationIndex}][pax]" class="form-control">
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="form-label">Début</label>
                            <input type="text" name="operations[${operationIndex}][pickup_location]" class="form-control operation-pickup google-address-input" autocomplete="off">
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="form-label">Fin</label>
                            <input type="text" name="operations[${operationIndex}][dropoff_location]" class="form-control operation-dropoff google-address-input" autocomplete="off">
                        </div>

                        <div class="col-md-12 mb-2 operation-waypoints">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0">Étapes intermédiaires</label>
                                <button type="button" class="btn btn-sm btn-outline-secondary addOperationWaypoint">+ Ajouter une étape</button>
                            </div>
                            <div class="operation-waypoints-wrapper"></div>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Type de service</label>
                           <select name="operations[${operationIndex}][service_type]" class="form-control operation-service-type">

                                <option value="">Sélectionner</option>
                                @foreach($serviceTypes as $serviceType)
                                  <option value="{{ $serviceType->name }}">{{ $serviceType->name }}</option>

                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Véhicule</label>
                          <select name="operations[${operationIndex}][vehicle_type]" class="form-control operation-vehicle-type">

                                <option value="">Sélectionner</option>
                                @foreach($vehicules as $vehicule)
                                   <option value="{{ $vehicule->name }}">{{ $vehicule->name }}</option>

                                @endforeach
                            </select>
                        </div>

                        ${talepDepotOptionsHtml.replaceAll('__INDEX__', operationIndex)}

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Description</label>
                            <input type="text" name="operations[${operationIndex}][route_description]" class="form-control">
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Péages</label>
                            <input type="number" step="0.01" min="0" name="operations[${operationIndex}][toll_amount]" class="form-control operation-toll" placeholder="Montant des péages">
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Découcher</label>
                            <input type="number" step="0.01" min="0" name="operations[${operationIndex}][decoucher]" class="form-control" placeholder="Montant découcher">
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Parking</label>
                            <input type="number" step="0.01" min="0" name="operations[${operationIndex}][parking]" class="form-control" placeholder="Montant parking">
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Checkpoint</label>
                            <input type="number" step="0.01" min="0" name="operations[${operationIndex}][checkpoint]" class="form-control" placeholder="Montant checkpoint">
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Carburant estimé</label>
                            <input type="number" step="0.01" min="0" name="operations[${operationIndex}][fuel_amount]" class="form-control bg-light operation-fuel-amount" readonly>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Litres estimés</label>
                            <input type="number" step="0.01" min="0" name="operations[${operationIndex}][fuel_liters]" class="form-control bg-light operation-fuel-liters" readonly>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Prix AI / jour</label>
                            <input type="number" step="0.01" min="0" name="operations[${operationIndex}][system_price]" class="form-control bg-light operation-system-price" readonly>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Prix équipe</label>
                            <input type="number" step="0.01" min="0" name="operations[${operationIndex}][final_price]" class="form-control operation-final-price" placeholder="Prix équipe manuel">
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Devise</label>
                            <input type="text" class="form-control bg-light" value="${document.getElementById('currency')?.value || 'EUR'}" readonly>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Notes générales</label>
                            <textarea name="operations[${operationIndex}][notes]" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="col-md-4 mb-2">
                            <label class="form-label">Note équipe</label>
                            <textarea name="operations[${operationIndex}][note_equipe]" class="form-control" rows="2"></textarea>
                        </div>

                        @if(auth()->user()?->hasRole('Superadmin'))
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Note admin</label>
                                <textarea name="operations[${operationIndex}][note_admin]" class="form-control" rows="2"></textarea>
                            </div>
                        @endif
                    </div>

                    <div class="mt-2">
                        <button type="button" class="btn btn-outline-primary btn-sm calculateOperationRoute">
                            Calculer la distance
                        </button>

                        <span class="ms-3">
                            <strong>Distance:</strong> <span class="operation-distance">-</span>
                        </span>

                        <span class="ms-3">
                            <strong>Durée:</strong> <span class="operation-duration">-</span>
                        </span>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('operationsWrapper').insertAdjacentHTML('beforeend', html);
        const newRow = document.querySelector('#operationsWrapper .operation-row:last-child');
        initGoogleAddressAutocomplete(newRow || document);
        applyDefaultVehicleToOperationRow(newRow, true);
        updateOperationServiceLabel(newRow);
        operationIndex++;
        updateTalepOperationTotals();
    });

    function updateOperationServiceLabel(row) {
        const badge = row?.querySelector('.operation-service-badge');
        const select = row?.querySelector('.operation-service-type');
        if (!badge || !select) return;

        const label = (select.options[select.selectedIndex]?.text || select.value || '').trim();
        const isEmpty = label === '' || label === 'Sélectionner';
        badge.textContent = isEmpty ? '' : label;
        badge.classList.toggle('d-none', isEmpty);
    }

    function updateOperationServiceLabels(context = document) {
        context.querySelectorAll('.operation-row').forEach(updateOperationServiceLabel);
    }

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('operation-service-type')) {
            updateOperationServiceLabel(e.target.closest('.operation-row'));
        }
    });

    updateOperationServiceLabels();

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('removeOperationRow')) {
            e.target.closest('.operation-row').remove();
            updateTalepOperationTotals();
        }

        if (e.target.classList.contains('addOperationWaypoint')) {
            const row = e.target.closest('.operation-row');
            const wrapper = row?.querySelector('.operation-waypoints-wrapper');
            const index = row?.dataset.index;

            if (!wrapper || typeof index === 'undefined') return;

            wrapper.insertAdjacentHTML('beforeend', `
                <div class="input-group input-group-sm mb-2 operation-waypoint-row">
                    <input type="text" name="operations[${index}][waypoints][]" class="form-control operation-waypoint google-address-input" placeholder="Adresse de l'étape" autocomplete="off">
                    <button type="button" class="btn btn-outline-danger removeOperationWaypoint">Supprimer</button>
                </div>
            `);
            initGoogleAddressAutocomplete(row);
        }

        if (e.target.classList.contains('removeOperationWaypoint')) {
            e.target.closest('.operation-waypoint-row')?.remove();
        }
    });

    updateTalepOperationTotals();
});
</script>
@if(config('services.google_maps.api_key'))
    <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places&callback=initTalepGooglePlaces"></script>
@else
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.gm_authFailure === 'function') {
                window.gm_authFailure();
            }
        });
    </script>
@endif
