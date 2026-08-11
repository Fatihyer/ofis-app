@extends('layouts.app')

@section('content')
@php
    $bulkProductIds = array_map('intval', $bulkProductIds ?? []);
    $onDemandProductIds = array_map('intval', $onDemandProductIds ?? []);
@endphp

<style>
    .stock-page { max-width: 1180px; margin: 0 auto; }
    .stock-hero { background: #111827; color: #fff; border-radius: 10px; padding: 22px; margin: 24px 0 18px; }
    .stock-hero h1 { font-size: 24px; margin: 0 0 6px; font-weight: 700; letter-spacing: 0; }
    .stock-hero p { margin: 0; color: #cbd5e1; }
    .stock-card { border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; box-shadow: 0 8px 22px rgba(15,23,42,.06); }
    .stock-card .card-header { background: #f8fafc; border-bottom: 1px solid #e5e7eb; font-weight: 700; }
    .stock-mode-info { border: 1px solid #dbeafe; background: #eff6ff; color: #1e3a8a; border-radius: 8px; padding: 12px 14px; font-size: 13px; }
    .stock-mode-info.ondemand { border-color: #fde68a; background: #fffbeb; color: #92400e; }
    .stock-mode-info.other { border-color: #e5e7eb; background: #f8fafc; color: #475569; }
    .stock-impact { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; background: #f8fafc; height: 100%; }
    .stock-impact small { display:block; color:#64748b; line-height:1.35; margin-top:4px; }
    .form-section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; color: #64748b; margin: 18px 0 10px; }
    label { font-weight: 600; color: #334155; }
</style>

<div class="container-fluid stock-page">
    <div class="stock-hero d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h1>Nouveau mouvement de billetterie</h1>
            <p>Bateaux Mouches et Bateaux Parisiens en stock suivi · Disneyland à la demande.</p>
        </div>
        <a href="{{ route('stocks.index') }}" class="btn btn-light">Retour au stock</a>
    </div>

    <div class="stock-card mb-4">
        <div class="card-header">Informations du mouvement</div>
        <div class="card-body">
            {{ Form::open(['route' => 'stocks.store']) }}

            <div id="stockModeInfo" class="stock-mode-info mb-3">
                Sélectionnez un produit pour afficher le mode de gestion.
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    {{ Form::label('tarih', 'Date') }}
                    {{ Form::date('tarih', date('Y-m-d'), ['class' => 'form-control', 'required']) }}
                </div>
                <div class="col-md-4">
                    {{ Form::label('post_id', 'Dossier') }}
                    {{ Form::text('post_id', $file, ['class' => 'form-control', 'readonly', 'placeholder' => 'Optionnel']) }}
                </div>
                <div class="col-md-4">
                    {{ Form::label('urun_id', 'Produit') }}
                    {{ Form::select('urun_id', $uruns, null, ['class' => 'form-control', 'required', 'placeholder' => 'Choisir un produit']) }}
                </div>
            </div>

            <div class="form-section-title">Fournisseur et client</div>
            <div class="row g-3">
                <div class="col-md-6">
                    {{ Form::label('a_acente_id', 'Fournisseur') }}
                    {{ Form::select('a_acente_id', $acentes, null, ['class' => 'form-control', 'required', 'placeholder' => 'Choisir']) }}
                </div>
                <div class="col-md-6">
                    {{ Form::label('b_acente_id', 'Client / agence') }}
                    @if ($acente)
                        {{ Form::select('b_acente_id', $acentes, $acente, ['class' => 'form-control', 'disabled']) }}
                        {{ Form::hidden('b_acente_id', $acente) }}
                    @else
                        {{ Form::select('b_acente_id', $acentes, null, ['class' => 'form-control', 'required', 'placeholder' => 'Choisir']) }}
                    @endif
                </div>
            </div>

            <div class="form-section-title">Stock, quantité et prix</div>
            <div class="row g-3">
                <div class="col-md-3">
                    {{ Form::label('movement_type', 'Type de mouvement') }}
                    {{ Form::select('movement_type', ['in' => 'Entrée stock', 'out' => 'Sortie stock', 'adjust' => 'Ajustement'], 'out', ['class' => 'form-control', 'required']) }}
                </div>
                <div class="col-md-3">
                    {{ Form::label('adet', 'Quantité') }}
                    {{ Form::number('adet', 1, ['class' => 'form-control', 'min' => 1, 'required']) }}
                </div>
                <div class="col-md-3">
                    {{ Form::label('buy_price', "Montant achat") }}
                    {{ Form::number('buy_price', null, ['class' => 'form-control', 'step' => '0.01', 'required']) }}
                </div>
                <div class="col-md-3">
                    {{ Form::label('sell_price', 'Montant vente') }}
                    {{ Form::number('sell_price', null, ['class' => 'form-control', 'step' => '0.01', 'required']) }}
                </div>
                <div class="col-md-4 mt-3">
                    {{ Form::label('kur_id', 'Devise') }}
                    {{ Form::select('kur_id', $kurs, 1, ['class' => 'form-control']) }}
                </div>
                <div class="col-md-8 mt-3">
                    <div class="stock-impact">
                        {{ Form::hidden('affects_stock', 0) }}
                        <label class="mb-0">{{ Form::checkbox('affects_stock', 1, true, ['id' => 'affects_stock']) }} Impacte le stock réel</label>
                        <small id="stockImpactHelp">Cochez pour les billets suivis en stock. Décochez pour Disneyland ou une ligne purement financière.</small>
                    </div>
                </div>
            </div>

            <div class="form-section-title">Détails</div>
            <div class="row g-3">
                <div class="col-md-12">
                    {{ Form::label('aciklama', 'Commentaire') }}
                    {{ Form::text('aciklama', null, ['class' => 'form-control', 'placeholder' => 'Référence, lot, remarque...']) }}
                </div>
            </div>

            <div class="mt-3">
                <a class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" href="#collapsekomisyon">Ajouter une commission</a>
            </div>

            <div class="collapse mt-3" id="collapsekomisyon">
                <div class="row g-3">
                    <div class="col-md-4">
                        {{ Form::label('kom_acente_id', 'Bénéficiaire commission') }}
                        {{ Form::select('kom_acente_id', $acentes, null, ['class' => 'form-control', 'placeholder' => 'Choisir']) }}
                    </div>
                    <div class="col-md-4">
                        {{ Form::label('komprice', 'Montant commission') }}
                        {{ Form::number('komprice', null, ['class' => 'form-control', 'step'=>'0.01']) }}
                    </div>
                    <div class="col-md-4">
                        {{ Form::label('komkur_id', 'Devise commission') }}
                        {{ Form::select('komkur_id', $kurs, 1, ['class' => 'form-control']) }}
                    </div>
                </div>
            </div>

            <div class="form-section-title">Règlement</div>
            <div class="d-flex flex-wrap gap-4 mb-4">
                <label class="form-check-label">{{ Form::radio('credit', 1, true) }} Réglé immédiatement</label>
                <label class="form-check-label">{{ Form::radio('credit', 0) }} À mettre en compte</label>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('stocks.index') }}" class="btn btn-outline-secondary">Annuler</a>
                <button class="btn btn-primary" type="submit">Enregistrer</button>
            </div>

            {{ Form::close() }}
        </div>
    </div>
</div>

<script>
(function () {
    const bulkIds = @json($bulkProductIds);
    const onDemandIds = @json($onDemandProductIds);
    const select = document.getElementById('urun_id');
    const info = document.getElementById('stockModeInfo');
    const affectsStock = document.getElementById('affects_stock');
    const movementType = document.getElementById('movement_type');
    const impactHelp = document.getElementById('stockImpactHelp');

    function refreshMode() {
        const id = parseInt(select.value || '0', 10);
        info.className = 'stock-mode-info mb-3';
        if (bulkIds.includes(id)) {
            info.textContent = 'Stock suivi: Entrée augmente le stock, Sortie le diminue, Ajustement corrige le comptage.';
            if (affectsStock) {
                affectsStock.disabled = false;
                affectsStock.checked = true;
            }
            if (impactHelp) impactHelp.textContent = 'Ce produit est suivi en stock réel.';
        } else if (onDemandIds.includes(id)) {
            info.classList.add('ondemand');
            info.textContent = 'À la demande: Disneyland se saisit uniquement selon le besoin réel du dossier.';
            if (affectsStock) {
                affectsStock.checked = false;
                affectsStock.disabled = true;
            }
            if (movementType) movementType.value = 'out';
            if (impactHelp) impactHelp.textContent = 'Disneyland ne crée pas de stock permanent.';
        } else {
            info.classList.add('other');
            info.textContent = 'Produit hors suivi principal: vérifier le fournisseur, le client et les prix avant validation.';
            if (affectsStock) affectsStock.disabled = false;
            if (impactHelp) impactHelp.textContent = 'À cocher seulement si ce produit doit entrer dans le stock réel.';
        }
    }

    if (select) {
        select.addEventListener('change', refreshMode);
        refreshMode();
    }
})();
</script>
@endsection
