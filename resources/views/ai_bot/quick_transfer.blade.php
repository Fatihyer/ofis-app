@extends('layouts.app')

@section('content')
<style>
.quick-ai-page { max-width: 1280px; margin: 0 auto; }
.quick-ai-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; margin-bottom:14px; flex-wrap:wrap; }
.quick-ai-head h1 { margin:0; font-size:24px; font-weight:850; }
.quick-ai-grid { display:grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap:14px; }
.quick-ai-card { border:1px solid #e5e7eb; border-radius:8px; background:#fff; overflow:hidden; }
.quick-ai-card-header { padding:12px 14px; background:#f8fafc; border-bottom:1px solid #e5e7eb; font-weight:800; display:flex; justify-content:space-between; gap:8px; align-items:center; }
.quick-ai-card-body { padding:14px; }
.quick-ai-textarea { min-height:360px; resize:vertical; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:13px; }
.quick-ai-xml { min-height:430px; }
.quick-ai-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
.quick-ai-preview { border:1px solid #e5e7eb; border-radius:8px; background:#f9fafb; padding:12px; font-size:13px; }
.quick-ai-preview dl { display:grid; grid-template-columns: 130px minmax(0, 1fr); gap:6px 10px; margin:0; }
.quick-ai-preview dt { color:#64748b; }
.quick-ai-preview dd { margin:0; font-weight:700; overflow-wrap:anywhere; }
.quick-ai-ops { margin-top:10px; display:grid; gap:8px; }
.quick-ai-op { border:1px solid #e5e7eb; border-radius:7px; background:#fff; padding:9px; }
.quick-ai-op strong { display:block; }
.quick-ai-op span { color:#64748b; font-size:12px; }
.quick-ai-alert { display:none; margin-top:10px; }
@media (max-width: 991.98px) { .quick-ai-grid { grid-template-columns: 1fr; } }
</style>

<div class="quick-ai-page">
    <div class="quick-ai-head">
        <div>
            <h1><i class="fas fa-bolt"></i> Création rapide AI</h1>
            <div class="text-muted">Collez un bon de commande, un email ou un WhatsApp. Vérifiez le XML avant création.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('ai-bot') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-robot"></i> AI Bot</a>
            <a href="{{ route('ev') }}" class="btn btn-outline-dark btn-sm">Opérations</a>
        </div>
    </div>

    <div id="quickAiAlert" class="alert quick-ai-alert"></div>

    <div class="quick-ai-grid">
        <div class="quick-ai-card">
            <div class="quick-ai-card-header">
                <span>Texte reçu</span>
                <button type="button" id="quickAiSample" class="btn btn-sm btn-outline-secondary">Exemple</button>
            </div>
            <div class="quick-ai-card-body">
                <textarea id="quickAiMessage" class="form-control quick-ai-textarea" placeholder="Collez ici le message client..."></textarea>
                <div class="quick-ai-actions">
                    <button id="quickAiGenerate" type="button" class="btn btn-primary">
                        <i class="fas fa-magic"></i> Transformer en XML
                    </button>
                    <button id="quickAiClear" type="button" class="btn btn-outline-secondary">Effacer</button>
                </div>
            </div>
        </div>

        <div class="quick-ai-card">
            <div class="quick-ai-card-header">
                <span>XML à vérifier</span>
                <span class="badge bg-warning text-dark">modifiable</span>
            </div>
            <div class="quick-ai-card-body">
                <label class="font-weight-bold">Agence si non reconnue</label>
                <select id="quickAiAcente" class="form-control mb-2">
                    <option value="">Utiliser l’agence du XML</option>
                    @foreach($acentes as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <textarea id="quickAiXml" class="form-control quick-ai-textarea quick-ai-xml" placeholder="<demande>...</demande>"></textarea>
                <div class="quick-ai-actions">
                    <button id="quickAiPreview" type="button" class="btn btn-outline-primary">
                        <i class="fas fa-eye"></i> Prévisualiser
                    </button>
                    <button id="quickAiCreate" type="button" class="btn btn-success">
                        <i class="fas fa-folder-plus"></i> Créer dossier + transfert
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="quick-ai-card mt-3">
        <div class="quick-ai-card-header">
            <span>Prévisualisation</span>
            <span id="quickAiPreviewStatus" class="text-muted small">En attente</span>
        </div>
        <div class="quick-ai-card-body">
            <div id="quickAiPreviewBox" class="quick-ai-preview text-muted">Le résumé apparaîtra ici après la conversion XML.</div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    var routes = {
        xml: '{{ route('ai-bot.quick-transfer.xml') }}',
        store: '{{ route('ai-bot.quick-transfer.store') }}'
    };
    var token = '{{ csrf_token() }}';

    function setBusy(button, busy, html) {
        if (busy) {
            button.data('old-html', button.html()).prop('disabled', true).html(html || '<i class="fas fa-spinner fa-spin"></i>');
        } else {
            button.prop('disabled', false).html(button.data('old-html'));
        }
    }

    function showAlert(type, message) {
        $('#quickAiAlert')
            .removeClass('alert-success alert-danger alert-warning alert-info')
            .addClass('alert-' + type)
            .text(message || '')
            .show();
    }

    function clearAlert() {
        $('#quickAiAlert').hide().text('');
    }

    function renderPreview(preview) {
        if (!preview || preview.error) {
            $('#quickAiPreviewStatus').text('Erreur');
            $('#quickAiPreviewBox').html($('<div>').addClass('text-danger').text(preview && preview.error ? preview.error : 'Prévisualisation impossible.'));
            return;
        }

        var box = $('<div>');
        var dl = $('<dl>');
        [
            ['Agence', (preview.agence || '-') + (preview.agence_trouvee ? ' ✓' : ' - à sélectionner')],
            ['Client', preview.client || '-'],
            ['Véhicule', preview.vehicule || '-'],
            ['À vérifier', preview.points_a_verifier || '-']
        ].forEach(function (row) {
            dl.append($('<dt>').text(row[0]));
            dl.append($('<dd>').text(row[1]));
        });
        box.append(dl);

        var ops = $('<div>').addClass('quick-ai-ops');
        (preview.operations || []).forEach(function (op, index) {
            ops.append(
                $('<div>').addClass('quick-ai-op')
                    .append($('<strong>').text('#' + (index + 1) + ' · ' + (op.date || '-') + ' ' + (op.heure || '-')))
                    .append($('<span>').text((op.depart || '-') + ' → ' + (op.arrivee || '-')))
                    .append($('<span>').addClass('d-block').text((op.service || '-') + (op.vehicule_id ? ' · véhicule/type reconnu' : ' · véhicule à définir')))
            );
        });
        box.append(ops);

        $('#quickAiPreviewStatus').text((preview.operations || []).length + ' opération(s)');
        $('#quickAiPreviewBox').removeClass('text-muted').html(box);
    }

    $('#quickAiSample').on('click', function () {
        $('#quickAiMessage').val('BON DE COMMANDE – MISE À DISPO\\n\\n• Date : 20/05/2026\\n• Pick-up Time : 18:30\\n• Pick Up Address : Hôtel Sax – 55 avenue de Saxe, 75007 Paris\\n\\n• Stop : Valvert – 8 rue Marie Laurencin, 91220 Le Plessis-Pâté\\n\\n• Drop Off : Standby retour presse : horaires à confirmer Madeleines Paris\\n• Name of the Passenger : autocar\\n\\nacente: Vip transfer');
    });

    $('#quickAiClear').on('click', function () {
        $('#quickAiMessage, #quickAiXml').val('');
        $('#quickAiPreviewBox').addClass('text-muted').text('Le résumé apparaîtra ici après la conversion XML.');
        $('#quickAiPreviewStatus').text('En attente');
        clearAlert();
    });

    $('#quickAiGenerate').on('click', function () {
        var button = $(this);
        var message = $('#quickAiMessage').val().trim();
        if (!message) {
            showAlert('warning', 'Collez un texte avant de lancer la conversion.');
            return;
        }

        clearAlert();
        setBusy(button, true, '<i class="fas fa-spinner fa-spin"></i> Conversion...');

        $.ajax({
            url: routes.xml,
            method: 'POST',
            data: {_token: token, message: message},
            success: function (response) {
                $('#quickAiXml').val(response.xml || '');
                renderPreview(response.preview);
                showAlert('success', 'XML généré. Vérifiez les champs avant de créer le dossier.');
            },
            error: function (xhr) {
                showAlert('danger', (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.answer)) || 'La conversion XML a échoué.');
            },
            complete: function () {
                setBusy(button, false);
            }
        });
    });

    $('#quickAiPreview').on('click', function () {
        var xml = $('#quickAiXml').val().trim();
        if (!xml) {
            showAlert('warning', 'Collez ou générez un XML avant la prévisualisation.');
            return;
        }
        showAlert('info', 'La prévisualisation complète sera recalculée au moment de la création. Vérifiez surtout agence, date, heure, départ et arrivée.');
    });

    $('#quickAiCreate').on('click', function () {
        var button = $(this);
        var xml = $('#quickAiXml').val().trim();
        if (!xml) {
            showAlert('warning', 'XML manquant.');
            return;
        }
        if (!confirm('Créer le dossier et les transferts depuis ce XML ?')) {
            return;
        }

        clearAlert();
        setBusy(button, true, '<i class="fas fa-spinner fa-spin"></i> Création...');

        $.ajax({
            url: routes.store,
            method: 'POST',
            data: {
                _token: token,
                xml: xml,
                acente_id: $('#quickAiAcente').val()
            },
            success: function (response) {
                showAlert('success', response.message || 'Dossier créé.');
                if (response.post_url) {
                    window.location.href = response.post_url;
                }
            },
            error: function (xhr) {
                var json = xhr.responseJSON || {};
                var message = json.message || 'La création a échoué.';
                if (json.errors) {
                    var firstKey = Object.keys(json.errors)[0];
                    if (firstKey) {
                        message = json.errors[firstKey][0];
                    }
                }
                showAlert('danger', message);
            },
            complete: function () {
                setBusy(button, false);
            }
        });
    });
})();
</script>
@endsection
