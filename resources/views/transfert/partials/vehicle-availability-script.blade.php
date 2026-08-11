<style>
.vehicle-availability-hint {
    display: block;
    margin-top: 6px;
    padding: 7px 9px;
    border-radius: 6px;
    border: 1px solid #d9e2ec;
    background: #f8fafc;
    color: #334155;
    font-size: 12px;
    line-height: 1.35;
}
.vehicle-availability-hint.is-ok {
    border-color: #86efac;
    background: #f0fdf4;
    color: #166534;
}
.vehicle-availability-hint.is-busy {
    border-color: #fca5a5;
    background: #fff1f2;
    color: #991b1b;
}
.vehicle-availability-hint.is-muted {
    border-color: #e5e7eb;
    background: #f9fafb;
    color: #64748b;
}
.vehicle-availability-hint .va-title { font-weight: 700; }
.vehicle-availability-hint ul { margin: 5px 0 0 16px; padding: 0; }
.vehicle-availability-hint li { margin: 2px 0; }
.vehicle-availability-hint a { color: inherit; font-weight: 700; text-decoration: underline; }
</style>
<script>
(function () {
    if (window.parisViaVehicleAvailabilityLoaded) return;
    window.parisViaVehicleAvailabilityLoaded = true;

    const endpoint = @json(route('transfers.vehicleAvailability'));
    const timers = new WeakMap();

    function vehicleSelects(root) {
        return Array.from((root || document).querySelectorAll('select[name="vehicule_id"], select[name$="[vehicule_id]"]'));
    }

    function scopeFor(element) {
        return element.closest('.transfer-card') || element.closest('form') || element.closest('.modal') || document;
    }

    function ensureHint(select) {
        if (select.nextElementSibling && select.nextElementSibling.classList.contains('vehicle-availability-hint')) {
            return select.nextElementSibling;
        }
        const hint = document.createElement('div');
        hint.className = 'vehicle-availability-hint is-muted';
        hint.textContent = 'Sélectionnez un véhicule et les horaires pour contrôler la disponibilité.';
        select.insertAdjacentElement('afterend', hint);
        return hint;
    }

    function setHint(select, state, title, conflicts) {
        const hint = ensureHint(select);
        hint.className = 'vehicle-availability-hint ' + state;
        hint.innerHTML = '';
        const titleNode = document.createElement('div');
        titleNode.className = 'va-title';
        titleNode.textContent = title;
        hint.appendChild(titleNode);

        if (conflicts && conflicts.length) {
            const list = document.createElement('ul');
            conflicts.forEach(function (conflict) {
                const item = document.createElement('li');
                const link = document.createElement('a');
                link.href = conflict.url;
                link.target = '_blank';
                link.textContent = '#' + conflict.id;
                item.appendChild(link);
                item.appendChild(document.createTextNode(' · ' + (conflict.start || '-') + ' - ' + (conflict.end || '-') + ' · ' + (conflict.agency || conflict.post_title || 'Dossier') + (conflict.driver ? ' · ' + conflict.driver : '')));
                list.appendChild(item);
            });
            hint.appendChild(list);
        }
    }

    function addOneDay(dateValue) {
        const date = new Date(dateValue + 'T00:00:00');
        date.setDate(date.getDate() + 1);
        return date.toISOString().slice(0, 10);
    }

    function combineDateTime(dateValue, timeValue) {
        if (!dateValue || !timeValue) return null;
        return dateValue + ' ' + timeValue;
    }

    function getWindow(scope) {
        const quickDate = scope.querySelector('input[name$="[date]"]');
        const quickStart = scope.querySelector('input[name$="[start_time]"]');
        const quickEnd = scope.querySelector('input[name$="[end_time]"]');
        if (quickDate && quickStart && quickEnd) {
            if (!quickDate.value || !quickStart.value || !quickEnd.value) return null;
            let endDate = quickDate.value;
            if (quickEnd.value <= quickStart.value) endDate = addOneDay(quickDate.value);
            return { start: combineDateTime(quickDate.value, quickStart.value), end: combineDateTime(endDate, quickEnd.value) };
        }

        const dateInput = scope.querySelector('input[name="start_date"]');
        const startTimeInput = scope.querySelector('input[name="start_time"]');
        const endDateInput = scope.querySelector('input[name="end_date"]');
        const endTimeInput = scope.querySelector('input[name="end_time"]');
        if (dateInput && startTimeInput && endTimeInput) {
            if (!dateInput.value || !startTimeInput.value || !endTimeInput.value) return null;
            let endDate = endDateInput && endDateInput.value ? endDateInput.value : dateInput.value;
            if (endDate === dateInput.value && endTimeInput.value <= startTimeInput.value) endDate = addOneDay(dateInput.value);
            return { start: combineDateTime(dateInput.value, startTimeInput.value), end: combineDateTime(endDate, endTimeInput.value) };
        }

        const trajetTimes = Array.from(scope.querySelectorAll('input[name^="trajets"][name$="[datetime]"]'))
            .map(function (input) { return input.value; })
            .filter(Boolean)
            .sort();
        if (trajetTimes.length >= 2) {
            return { start: trajetTimes[0].replace('T', ' '), end: trajetTimes[trajetTimes.length - 1].replace('T', ' ') };
        }

        return null;
    }

    function currentTransferId(select, scope) {
        if (select.dataset.currentTransferId) return select.dataset.currentTransferId;
        const idInput = scope.querySelector('input[name="id"], input#data-id');
        return idInput && idInput.value ? idInput.value : '';
    }

    function check(select) {
        const scope = scopeFor(select);
        const vehiculeId = select.value;
        const windowData = getWindow(scope);

        ensureHint(select);
        if (!vehiculeId) {
            setHint(select, 'is-muted', 'Sélectionnez un véhicule.', []);
            return;
        }
        if (!windowData || !windowData.start || !windowData.end) {
            setHint(select, 'is-muted', 'Renseignez le début et la fin pour contrôler la disponibilité.', []);
            return;
        }

        setHint(select, 'is-muted', 'Contrôle de disponibilité...', []);
        const params = new URLSearchParams({
            vehicule_id: vehiculeId,
            start: windowData.start,
            end: windowData.end
        });
        const transferId = currentTransferId(select, scope);
        if (transferId) params.set('transfer_id', transferId);

        fetch(endpoint + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(function (response) {
                if (!response.ok) throw new Error('Erreur ' + response.status);
                return response.json();
            })
            .then(function (data) {
                if (data.available) {
                    setHint(select, 'is-ok', 'Disponible: ' + (data.vehicule || 'véhicule') + ' du ' + data.period.start + ' au ' + data.period.end + '.', []);
                } else {
                    setHint(select, 'is-busy', 'Occupé: conflit sur ' + (data.vehicule || 'ce véhicule') + ' du ' + data.period.start + ' au ' + data.period.end + '.', data.conflicts || []);
                }
            })
            .catch(function () {
                setHint(select, 'is-muted', 'Disponibilité non vérifiée pour le moment.', []);
            });
    }

    function schedule(select) {
        if (!select) return;
        clearTimeout(timers.get(select));
        timers.set(select, setTimeout(function () { check(select); }, 350));
    }

    function init(root) {
        vehicleSelects(root).forEach(function (select) {
            ensureHint(select);
            if (select.value) schedule(select);
        });
    }

    document.addEventListener('change', function (event) {
        const scope = scopeFor(event.target);
        if (event.target.matches && event.target.matches('select[name="vehicule_id"], select[name$="[vehicule_id]"]')) {
            schedule(event.target);
            return;
        }
        if (event.target.matches && event.target.matches('input[name="start_date"], input[name="end_date"], input[name="start_time"], input[name="end_time"], input[name^="trajets"][name$="[datetime]"], input[name$="[date]"], input[name$="[start_time]"], input[name$="[end_time]"]')) {
            vehicleSelects(scope).forEach(schedule);
        }
    }, true);

    document.addEventListener('input', function (event) {
        const scope = scopeFor(event.target);
        if (event.target.matches && event.target.matches('input[name="start_date"], input[name="end_date"], input[name="start_time"], input[name="end_time"], input[name^="trajets"][name$="[datetime]"], input[name$="[date]"], input[name$="[start_time]"], input[name$="[end_time]"]')) {
            vehicleSelects(scope).forEach(schedule);
        }
    }, true);

    document.addEventListener('shown.bs.modal', function (event) { init(event.target); });
    document.addEventListener('DOMContentLoaded', function () { init(document); });
    setTimeout(function () { init(document); }, 500);
})();
</script>
