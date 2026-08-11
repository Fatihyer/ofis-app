@extends('layouts.app')

@section('title', '| Créer dossier + transferts')

@section('style')
<style>
    .quick-transfer-wrap { max-width: 1240px; margin: 0 auto; }
    .quick-transfer-header { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
    .quick-panel { background:#fff; border:1px solid #dfe3e8; border-radius:8px; padding:16px; margin-bottom:14px; }
    .quick-panel-title { font-size:15px; font-weight:700; color:#2f3a45; margin-bottom:12px; }
    .transfer-card { border:1px solid #cfd8e3; border-radius:8px; background:#fff; margin-bottom:14px; overflow:hidden; }
    .transfer-card-header { display:flex; align-items:center; justify-content:space-between; gap:10px; background:#f7f9fb; border-bottom:1px solid #e1e6ee; padding:10px 12px; }
    .transfer-card-title { font-weight:700; color:#24313d; }
    .transfer-grid { padding:14px; }
    .external-box { border:1px dashed #d7b55d; border-radius:8px; padding:12px; background:#fffdf5; margin-top:8px; }
    .form-text-soft { color:#6b7280; font-size:12px; }
    .btn-icon-sm { width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; }
</style>
@endsection

@section('content')
<div class="quick-transfer-wrap">
    <div class="quick-transfer-header">
        <div>
            <h3 class="mb-1">Nouveau dossier depuis transferts</h3>
            <div class="text-muted">Créer un dossier et ajouter les transferts en une seule étape.</div>
        </div>
        <a href="{{ route('posts.index') }}" class="btn btn-light border">Retour</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Vérifiez le formulaire.</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('posts.storefromtransfert') }}" method="POST">
        @csrf

        <div class="quick-panel">
            <div class="quick-panel-title">Dossier</div>
            <div class="form-row">
                <div class="form-group col-md-5">
                    <label>Agence / client</label>
                    <select name="acente_id" class="form-control" required>
                        @foreach($acente as $id => $name)
                            <option value="{{ $id }}" {{ old('acente_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Titre du dossier</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" maxlength="100" required>
                </div>
                <div class="form-group col-md-3">
                    <label>Statut dossier</label>
                    <select name="status_id" class="form-control">
                        @foreach($status as $id => $name)
                            <option value="{{ $id }}" {{ old('status_id', 3) == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-2">
                    <label>Pax dossier</label>
                    <input type="number" name="pax" class="form-control" value="{{ old('pax', 1) }}" min="0">
                </div>
                <div class="form-group col-md-2">
                    <label>Enfants</label>
                    <input type="number" name="child" class="form-control" value="{{ old('child', 0) }}" min="0">
                </div>
                <div class="form-group col-md-8">
                    <label>Description</label>
                    <textarea name="body" class="form-control" rows="2" maxlength="1000">{{ old('body') }}</textarea>
                </div>
            </div>
        </div>

        <div class="quick-panel">
            <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
                <div class="quick-panel-title mb-0">Transferts</div>
                <button type="button" class="btn btn-success btn-sm" id="addTransfer"><i class="fa fa-plus"></i> Ajouter un transfert</button>
            </div>
            <div id="transfersContainer">
                @php $oldTransfers = old('transfers', [0 => []]); @endphp
                @foreach($oldTransfers as $i => $oldTransfer)
                    @include('posts.partials.quick-transfer-row', ['index' => $i, 'row' => $oldTransfer])
                @endforeach
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block">Créer le dossier et les transferts</button>
    </form>
</div>

<template id="transferTemplate">
    @include('posts.partials.quick-transfer-row', ['index' => '__INDEX__', 'row' => []])
</template>
@endsection

@section('footer')
@include('transfert.partials.vehicle-availability-script')
<script>
(function () {
    let transferIndex = {{ count(old('transfers', [0 => []])) }};
    const container = document.getElementById('transfersContainer');
    const template = document.getElementById('transferTemplate');

    function refreshTitles() {
        container.querySelectorAll('.transfer-card').forEach(function (card, index) {
            card.querySelector('.transfer-number').textContent = index + 1;
            const removeButton = card.querySelector('.remove-transfer');
            if (removeButton) {
                removeButton.style.display = container.querySelectorAll('.transfer-card').length > 1 ? 'inline-flex' : 'none';
            }
        });
    }

    document.getElementById('addTransfer').addEventListener('click', function () {
        const html = template.innerHTML.replaceAll('__INDEX__', transferIndex);
        container.insertAdjacentHTML('beforeend', html);
        transferIndex++;
        refreshTitles();
    });

    container.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.remove-transfer');
        if (!removeButton) return;
        event.preventDefault();
        const cards = container.querySelectorAll('.transfer-card');
        if (cards.length <= 1) return;
        removeButton.closest('.transfer-card').remove();
        refreshTitles();
    });

    container.addEventListener('change', function (event) {
        if (!event.target.classList.contains('quick-start-time')) return;
        const card = event.target.closest('.transfer-card');
        const endInput = card.querySelector('.quick-end-time');
        if (endInput && !endInput.value) {
            endInput.value = event.target.value;
        }
    });

    refreshTitles();
})();
</script>
@endsection
