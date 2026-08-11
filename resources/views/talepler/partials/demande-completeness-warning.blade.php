@once
<style>
.demande-check-overlay{display:none;position:fixed;inset:0;z-index:20000;background:rgba(15,23,42,.62);padding:20px;align-items:center;justify-content:center}
.demande-check-overlay.is-visible{display:flex}
.demande-check-dialog{width:min(620px,100%);max-height:90vh;overflow:auto;background:#fff;border-radius:14px;box-shadow:0 24px 70px rgba(15,23,42,.32)}
.demande-check-head{padding:18px 20px;border-bottom:1px solid #e2e8f0;background:#fff7ed;border-radius:14px 14px 0 0}
.demande-check-head h3{margin:0;color:#9a3412;font-size:20px;font-weight:900}
.demande-check-head p{margin:5px 0 0;color:#7c2d12;font-weight:700}
.demande-check-body{padding:18px 20px}
.demande-check-list{margin:0;padding-left:22px;color:#334155;font-weight:750}
.demande-check-list li{margin-bottom:7px}
.demande-check-actions{display:flex;gap:9px;justify-content:flex-end;flex-wrap:wrap;padding:14px 20px;border-top:1px solid #e2e8f0}
.demande-field-missing{border-color:#ef4444!important;box-shadow:0 0 0 2px rgba(239,68,68,.12)!important}
</style>

<div class="demande-check-overlay" id="demandeCompletenessOverlay" aria-hidden="true">
    <div class="demande-check-dialog" role="dialog" aria-modal="true" aria-labelledby="demandeCompletenessTitle">
        <div class="demande-check-head">
            <h3 id="demandeCompletenessTitle"><i class="fa fa-exclamation-triangle"></i> Informations manquantes</h3>
            <p>Veuillez vérifier la demande avant de l’enregistrer.</p>
        </div>
        <div class="demande-check-body">
            <ul class="demande-check-list" id="demandeCompletenessList"></ul>
        </div>
        <div class="demande-check-actions">
            <button type="button" class="btn btn-primary" id="demandeCompletenessCorrect">Corriger les informations</button>
            <button type="button" class="btn btn-outline-secondary" id="demandeCompletenessContinue">Enregistrer malgré les informations manquantes</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const saveButtons = Array.from(document.querySelectorAll('button[type="submit"], input[type="submit"]'));
    const saveButton = saveButtons.find(button => {
        const text = String(button.textContent || button.value || '').trim().toLowerCase();
        return text.includes('enregistrer');
    });
    const form = saveButton ? saveButton.closest('form') : null;
    const overlay = document.getElementById('demandeCompletenessOverlay');
    const list = document.getElementById('demandeCompletenessList');
    const correctButton = document.getElementById('demandeCompletenessCorrect');
    const continueButton = document.getElementById('demandeCompletenessContinue');

    if (!form || !overlay || !list || !correctButton || !continueButton) return;

    // Let the French completeness dialog handle missing fields before the
    // browser's native validation bubble can interrupt the submit event.
    form.noValidate = true;

    const value = name => {
        const field = form.querySelector(`[name="${name}"]`);
        return field ? String(field.value || '').trim() : '';
    };
    const field = name => form.querySelector(`[name="${name}"]`);
    const addMissing = (items, label, element) => {
        items.push({label, element});
        if (element) element.classList.add('demande-field-missing');
    };

    function collectMissing() {
        form.querySelectorAll('.demande-field-missing').forEach(element => element.classList.remove('demande-field-missing'));
        const missing = [];

        if (!value('talep_tarihi')) addMissing(missing, 'Date de la demande', field('talep_tarihi'));
        if (!value('talep_kanali')) addMissing(missing, 'Canal de la demande', field('talep_kanali'));
        if (!value('country')) addMissing(missing, 'Pays', field('country'));
        if (!value('service_type_id')) addMissing(missing, 'Type de service', field('service_type_id'));
        if (!value('vehicule_id')) addMissing(missing, 'Véhicule demandé', field('vehicule_id'));
        if (!value('acente_id')) addMissing(missing, 'Agence', field('acente_id'));
        if (!value('user_id')) addMissing(missing, 'Responsable', field('user_id'));
        if (!value('customer_name')) addMissing(missing, 'Nom du client', field('customer_name'));
        if (!value('customer_phone') && !value('customer_email')) {
            addMissing(missing, 'Téléphone ou e-mail du client', field('customer_phone') || field('customer_email'));
        }
        if (!value('total_pax')) addMissing(missing, 'Nombre de passagers', field('total_pax'));

        const operationFields = Array.from(form.querySelectorAll('[name^="operations["]'));
        const operationIndexes = [...new Set(operationFields.map(element => {
            const match = element.name.match(/^operations\[(\d+)\]/);
            return match ? match[1] : null;
        }).filter(index => index !== null))];

        if (!operationIndexes.length) {
            addMissing(missing, 'Au moins une opération', form.querySelector('.add-operation, #addOperation, [data-action="add-operation"]'));
        }

        operationIndexes.forEach((index, position) => {
            const prefix = `operations[${index}]`;
            const operationLabel = `Opération ${position + 1}`;
            const required = [
                ['service_date', 'date'],
                ['start_time', 'heure de début'],
                ['pickup_location', 'adresse de départ'],
                ['dropoff_location', 'adresse d’arrivée'],
                ['service_type', 'type de service'],
                ['vehicle_type', 'type de véhicule'],
                ['pax', 'nombre de passagers']
            ];

            required.forEach(([suffix, label]) => {
                const operationField = form.querySelector(`[name="${prefix}[${suffix}]"]`);
                if (!operationField || !String(operationField.value || '').trim()) {
                    addMissing(missing, `${operationLabel} : ${label}`, operationField);
                }
            });
        });

        return missing;
    }

    form.addEventListener('submit', function (event) {
        if (form.dataset.completenessConfirmed === '1') {
            delete form.dataset.completenessConfirmed;
            return;
        }

        const missing = collectMissing();
        if (!missing.length) return;

        event.preventDefault();
        list.textContent = '';
        missing.forEach(item => {
            const li = document.createElement('li');
            li.textContent = item.label;
            list.appendChild(li);
        });
        overlay.classList.add('is-visible');
        overlay.setAttribute('aria-hidden', 'false');
        overlay.dataset.firstMissingName = missing[0].element?.name || '';
    });

    correctButton.addEventListener('click', function () {
        overlay.classList.remove('is-visible');
        overlay.setAttribute('aria-hidden', 'true');
        const name = overlay.dataset.firstMissingName;
        const firstMissing = name ? form.querySelector(`[name="${name}"]`) : form.querySelector('.demande-field-missing');
        if (firstMissing) {
            firstMissing.scrollIntoView({behavior: 'smooth', block: 'center'});
            firstMissing.focus({preventScroll: true});
        }
    });

    continueButton.addEventListener('click', function () {
        overlay.classList.remove('is-visible');
        overlay.setAttribute('aria-hidden', 'true');
        form.dataset.completenessConfirmed = '1';
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit(saveButton);
        } else {
            form.submit();
        }
    });
});
</script>
@endonce
