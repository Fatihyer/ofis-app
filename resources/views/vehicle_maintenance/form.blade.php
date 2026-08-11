@php($maintenance = $maintenance ?? null)
<div class="row">
    <div class="col-md-6 form-group">
        <label>Véhicule</label>
        <select name="vehicule_id" class="form-control" required>
            <option value="">Sélectionner</option>
            @foreach($vehicules as $vehicule)
                <option value="{{ $vehicule->id }}" @selected(old('vehicule_id', $maintenance->vehicule_id ?? '') == $vehicule->id)>{{ trim(($vehicule->plaka ? $vehicule->plaka . ' - ' : '') . $vehicule->name) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 form-group">
        <label>Catégorie</label>
        <select name="category" class="form-control">
            <option value="">Autre / non défini</option>
            @foreach($categories as $key => $label)
                <option value="{{ $key }}" @selected(old('category', $maintenance->category ?? '') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 form-group">
        <label>Fournisseur / garage</label>
        <select name="acente_id" class="form-control">
            <option value="">Sans mouvement cari</option>
            @foreach($acentes as $acente)
                <option value="{{ $acente->id }}" @selected(old('acente_id', $maintenance->acente_id ?? '') == $acente->id)>{{ $acente->name }}</option>
            @endforeach
        </select>
        <small class="text-muted">Si renseigné, un mouvement cari sera créé automatiquement.</small>
    </div>
    <div class="col-md-3 form-group">
        <label>Date facture/service</label>
        <input type="date" name="service_date" class="form-control" required value="{{ old('service_date', optional($maintenance)->service_date ? \Carbon\Carbon::parse($maintenance->service_date)->format('Y-m-d') : now('Europe/Paris')->toDateString()) }}">
    </div>
    <div class="col-md-3 form-group">
        <label>Montant</label>
        <input type="number" name="amount" class="form-control" step="0.01" min="0" required value="{{ old('amount', $maintenance->amount ?? '') }}">
    </div>
    <div class="col-md-4 form-group">
        <label>Mode paiement</label>
        <select name="payment_id" class="form-control">
            <option value="">Non défini</option>
            @foreach($payments as $payment)
                <option value="{{ $payment->id }}" @selected(old('payment_id', $maintenance->payment_id ?? '') == $payment->id)>{{ $payment->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 form-group">
        <label>Devise</label>
        <select name="kur_id" class="form-control">
            @foreach($kurs as $kur)
                <option value="{{ $kur->id }}" @selected(old('kur_id', $maintenance->kur_id ?? 1) == $kur->id)>{{ $kur->short_name ?? $kur->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 form-group">
        <label>N° facture</label>
        <input type="text" name="invoiceno" class="form-control" value="{{ old('invoiceno', $maintenance->invoiceno ?? '') }}">
    </div>
    <div class="col-12 form-group">
        <label>Description</label>
        <textarea name="description" class="form-control" rows="4" required>{{ old('description', $maintenance->description ?? '') }}</textarea>
    </div>
</div>
