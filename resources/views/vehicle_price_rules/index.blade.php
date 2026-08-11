@extends('layouts.app')

@section('content')
<style>
.tarif-page { max-width: 1500px; }
.tarif-card { border:1px solid #e5e7eb; border-radius:8px; background:#fff; }
.tarif-table th { white-space:nowrap; font-size:12px; text-transform:uppercase; color:#64748b; background:#f8fafc; }
.tarif-table td { vertical-align:middle; }
.tarif-table input, .tarif-table select { min-width:90px; }
.tarif-code { font-weight:700; color:#111827; }
</style>

<div class="container-fluid tarif-page">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Tarifs véhicules</h3>
            <div class="text-muted">Règles utilisées pour le calcul automatique des demandes.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('vehicle-price-rules.export') }}" class="btn btn-success">
                <i class="fa fa-file-excel-o"></i> Excel aktar
            </a>
            <a href="{{ route('talepler.index') }}" class="btn btn-outline-secondary">Demandes</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="tarif-card mb-4">
        <div class="card-header bg-white">
            <strong>Nouveau tarif</strong>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('vehicle-price-rules.store') }}">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Véhicule</label>
                        <select name="vehicle_type" class="form-control" required>
                            @foreach($vehicleTypes as $vehicleType)
                                <option value="{{ $vehicleType }}">{{ $vehicleType }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Service</label>
                        <select name="service_type" class="form-control">
                            @foreach($serviceTypes as $serviceType)
                                <option value="{{ $serviceType }}">{{ $serviceType }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1"><label class="form-label small text-muted">Base</label><input type="number" step="0.01" name="base_rate" class="form-control" value="0" required></div>
                    <div class="col-md-1"><label class="form-label small text-muted">Km inclus</label><input type="number" name="included_km" class="form-control" value="0" required></div>
                    <div class="col-md-1"><label class="form-label small text-muted">H incluses</label><input type="number" step="0.25" name="included_hours" class="form-control" value="0" required></div>
                    <div class="col-md-1"><label class="form-label small text-muted">€/km</label><input type="number" step="0.01" name="extra_km_rate" class="form-control" value="0" required></div>
                    <div class="col-md-1"><label class="form-label small text-muted">€/h</label><input type="number" step="0.01" name="extra_hour_rate" class="form-control" value="0" required></div>
                    <div class="col-md-1"><label class="form-label small text-muted">Nuit €/h</label><input type="number" step="0.01" name="night_extra_hour_rate" class="form-control" value="0" required></div>
                    <div class="col-md-1"><label class="form-label small text-muted">Minimum</label><input type="number" step="0.01" name="minimum_charge" class="form-control" value="0" required></div>
                    <div class="col-md-1">
                        <input type="hidden" name="driver_meal_cost" value="25">
                        <input type="hidden" name="driver_hotel_cost" value="120">
                        <input type="hidden" name="default_margin_percent" value="15">
                        <input type="hidden" name="vat_rate" value="10">
                        <input type="hidden" name="active" value="1">
                        <button type="submit" class="btn btn-primary w-100">Ajouter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="tarif-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 tarif-table">
                <thead>
                    <tr>
                        <th>Véhicule</th>
                        <th>Service</th>
                        <th>Base</th>
                        <th>Km inclus</th>
                        <th>H incluses</th>
                        <th>€/km</th>
                        <th>€/h</th>
                        <th>Nuit €/h</th>
                        <th>Minimum</th>
                        <th>Repas</th>
                        <th>Hôtel</th>
                        <th>Marge %</th>
                        <th>TVA %</th>
                        <th>Actif</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rules as $rule)
                        <tr>
                            <form method="POST" action="{{ route('vehicle-price-rules.update', $rule->id) }}">
                                @csrf
                                @method('PUT')
                                <td>
                                    <select name="vehicle_type" class="form-control form-control-sm">
                                        @foreach($vehicleTypes as $vehicleType)
                                            <option value="{{ $vehicleType }}" @selected($rule->vehicle_type === $vehicleType)>{{ $vehicleType }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="service_type" class="form-control form-control-sm">
                                        @foreach($serviceTypes as $serviceType)
                                            <option value="{{ $serviceType }}" @selected($rule->service_type === $serviceType)>{{ $serviceType }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" name="base_rate" class="form-control form-control-sm" value="{{ $rule->base_rate }}"></td>
                                <td><input type="number" name="included_km" class="form-control form-control-sm" value="{{ $rule->included_km }}"></td>
                                <td><input type="number" step="0.25" name="included_hours" class="form-control form-control-sm" value="{{ $rule->included_hours }}"></td>
                                <td><input type="number" step="0.01" name="extra_km_rate" class="form-control form-control-sm" value="{{ $rule->extra_km_rate }}"></td>
                                <td><input type="number" step="0.01" name="extra_hour_rate" class="form-control form-control-sm" value="{{ $rule->extra_hour_rate }}"></td>
                                <td><input type="number" step="0.01" name="night_extra_hour_rate" class="form-control form-control-sm" value="{{ $rule->night_extra_hour_rate }}"></td>
                                <td><input type="number" step="0.01" name="minimum_charge" class="form-control form-control-sm" value="{{ $rule->minimum_charge }}"></td>
                                <td><input type="number" step="0.01" name="driver_meal_cost" class="form-control form-control-sm" value="{{ $rule->driver_meal_cost }}"></td>
                                <td><input type="number" step="0.01" name="driver_hotel_cost" class="form-control form-control-sm" value="{{ $rule->driver_hotel_cost }}"></td>
                                <td><input type="number" step="0.01" name="default_margin_percent" class="form-control form-control-sm" value="{{ $rule->default_margin_percent }}"></td>
                                <td><input type="number" step="0.01" name="vat_rate" class="form-control form-control-sm" value="{{ $rule->vat_rate }}"></td>
                                <td>
                                    <input type="hidden" name="active" value="0">
                                    <input type="checkbox" name="active" value="1" @checked($rule->active)>
                                </td>
                                <td class="text-nowrap">
                                    <button type="submit" class="btn btn-sm btn-primary">Enregistrer</button>
                            </form>
                            <form method="POST" action="{{ route('vehicle-price-rules.destroy', $rule->id) }}" class="d-inline" onsubmit="return confirm('Supprimer ce tarif ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                            </form>
                                </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="tarif-card mt-4">
        <div class="card-header bg-white">
            <strong>Majorations / remises par dates</strong>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('vehicle-price-rules.date-adjustments.store') }}" class="mb-3">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Libellé</label>
                        <input type="text" name="label" class="form-control" placeholder="Salon, haute saison..." required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Véhicule</label>
                        <select name="vehicle_type" class="form-control">
                            <option value="">Tous</option>
                            @foreach($vehicleTypes as $vehicleType)
                                <option value="{{ $vehicleType }}">{{ $vehicleType }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small text-muted">Service</label>
                        <select name="service_type" class="form-control">
                            <option value="">Tous</option>
                            @foreach($serviceTypes as $serviceType)
                                <option value="{{ $serviceType }}">{{ $serviceType }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label small text-muted">Début</label><input type="date" name="start_date" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label small text-muted">Fin</label><input type="date" name="end_date" class="form-control" required></div>
                    <div class="col-md-1">
                        <label class="form-label small text-muted">Sens</label>
                        <select name="direction" class="form-control">
                            <option value="increase">Majoration</option>
                            <option value="discount">Remise</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small text-muted">Type</label>
                        <select name="adjustment_type" class="form-control">
                            <option value="percent">%</option>
                            <option value="fixed">€</option>
                        </select>
                    </div>
                    <div class="col-md-1"><label class="form-label small text-muted">Valeur</label><input type="number" step="0.01" name="adjustment_value" class="form-control" value="0" required></div>
                    <div class="col-md-1">
                        <input type="hidden" name="active" value="1">
                        <button type="submit" class="btn btn-primary w-100">Ajouter</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 tarif-table">
                    <thead>
                        <tr>
                            <th>Libellé</th>
                            <th>Véhicule</th>
                            <th>Service</th>
                            <th>Début</th>
                            <th>Fin</th>
                            <th>Sens</th>
                            <th>Type</th>
                            <th>Valeur</th>
                            <th>Actif</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dateAdjustments as $adjustment)
                            <tr>
                                <form method="POST" action="{{ route('vehicle-price-rules.date-adjustments.update', $adjustment->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <td><input type="text" name="label" class="form-control form-control-sm" value="{{ $adjustment->label }}" required></td>
                                    <td>
                                        <select name="vehicle_type" class="form-control form-control-sm">
                                            <option value="">Tous</option>
                                            @foreach($vehicleTypes as $vehicleType)
                                                <option value="{{ $vehicleType }}" @selected($adjustment->vehicle_type === $vehicleType)>{{ $vehicleType }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="service_type" class="form-control form-control-sm">
                                            <option value="">Tous</option>
                                            @foreach($serviceTypes as $serviceType)
                                                <option value="{{ $serviceType }}" @selected($adjustment->service_type === $serviceType)>{{ $serviceType }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="date" name="start_date" class="form-control form-control-sm" value="{{ $adjustment->start_date }}" required></td>
                                    <td><input type="date" name="end_date" class="form-control form-control-sm" value="{{ $adjustment->end_date }}" required></td>
                                    <td>
                                        <select name="direction" class="form-control form-control-sm">
                                            <option value="increase" @selected(($adjustment->direction ?? 'increase') === 'increase')>Majoration</option>
                                            <option value="discount" @selected(($adjustment->direction ?? 'increase') === 'discount')>Remise</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="adjustment_type" class="form-control form-control-sm">
                                            <option value="percent" @selected($adjustment->adjustment_type === 'percent')>%</option>
                                            <option value="fixed" @selected($adjustment->adjustment_type === 'fixed')>€</option>
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.01" name="adjustment_value" class="form-control form-control-sm" value="{{ $adjustment->adjustment_value }}" required></td>
                                    <td>
                                        <input type="hidden" name="active" value="0">
                                        <input type="checkbox" name="active" value="1" @checked($adjustment->active)>
                                    </td>
                                    <td class="text-nowrap">
                                        <button type="submit" class="btn btn-sm btn-primary">Enregistrer</button>
                                </form>
                                <form method="POST" action="{{ route('vehicle-price-rules.date-adjustments.destroy', $adjustment->id) }}" class="d-inline" onsubmit="return confirm('Supprimer cette majoration ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                </form>
                                    </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-muted">Aucune règle par dates enregistrée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="small text-muted mt-2">
                Si véhicule ou service vaut “Tous”, la règle s’applique à toutes les catégories correspondantes.
                “Majoration” ajoute au prix, “Remise” retire du prix. Le type “%” applique un pourcentage au prix HT de la ligne; le type “€” applique un montant fixe par opération.
            </div>
        </div>
    </div>

    <div class="tarif-card mt-4">
        <div class="card-header bg-white">
            <strong>Mode d’emploi</strong>
        </div>
        <div class="card-body">
            <p class="mb-2">
                Ces tarifs sont utilisés pour calculer automatiquement le <strong>Prix Equipe</strong> d’une demande.
                Le calcul se lance après la création depuis un mail/XML ou avec le bouton <strong>Recalculer le prix</strong> dans la fiche demande.
            </p>
            <div class="row">
                <div class="col-lg-6">
                    <ul class="mb-3">
                        <li><strong>Véhicule</strong>: catégorie tarifaire reconnue par le moteur.</li>
                        <li><strong>Service</strong>: <code>transfer</code> pour un transfert simple, <code>dispo</code> pour mise à disposition, journée, tour ou panoramic.</li>
                        <li><strong>Base</strong>: prix de départ avant suppléments.</li>
                        <li><strong>Km inclus</strong> et <strong>H incluses</strong>: distance et durée comprises dans le prix de base.</li>
                        <li><strong>€/km</strong> et <strong>€/h</strong>: supplément appliqué au-delà des inclus.</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <ul class="mb-3">
                        <li><strong>Nuit €/h</strong>: supplément si le service commence avant 07:00 ou après 21:00.</li>
                        <li><strong>Minimum</strong>: prix minimum garanti pour cette règle.</li>
                        <li><strong>Repas</strong> et <strong>Hôtel</strong>: coûts chauffeur disponibles pour les calculs détaillés.</li>
                        <li><strong>Marge %</strong> et <strong>TVA %</strong>: ajoutées au devis système global.</li>
                        <li><strong>Actif</strong>: décocher pour conserver une règle sans l’utiliser.</li>
                        <li><strong>Majorations / remises par dates</strong>: permettent d’augmenter ou de réduire les prix sur une période précise, pour tous les véhicules ou seulement une catégorie.</li>
                    </ul>
                </div>
            </div>
            <div class="alert alert-info mb-0">
                Correspondances automatiques: Sedan/Class E/Class S → <code>SEDAN_4</code>,
                Class V → <code>CLASS_V</code>, Vito/Van → <code>VAN_8</code>,
                Sprinter 16-22 → <code>SPRINTER_19</code>, Iveco/29 places → <code>MINIBUS_30</code>,
                Otokar/Bova 43-45 → <code>COACH_45</code>, Tourismo/Temsa 53-56 → <code>COACH_55</code>,
                59 places → <code>COACH_60</code>.
            </div>
        </div>
    </div>
</div>
@endsection
