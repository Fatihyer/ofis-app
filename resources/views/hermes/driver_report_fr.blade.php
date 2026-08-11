@extends('layouts.app')

@section('content')
<div class="container py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h3 class="mb-0">HERMES – Rapport de conduite du chauffeur</h3>
        <small class="text-muted">Source : transfers + (si disponible) Hermes track-info</small>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">ID Chauffeur</label>
                    <input type="number" class="form-control" id="driverId" placeholder="ex : 946" value="946">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Date (heure de Paris)</label>
                    <input type="date" class="form-control" id="dateInput"
                           value="{{ \Carbon\Carbon::yesterday('Europe/Paris')->toDateString() }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Endpoint API</label>
                    <input type="text" class="form-control" id="apiBase"
                           value="{{ url('/api/hermes/driver') }}"
                           readonly>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary w-100" id="fetchBtn">Afficher</button>
                    <button class="btn btn-outline-secondary w-100" id="clearBtn">Effacer</button>
                </div>
            </div>

            <div class="mt-3">
                <div id="statusLine" class="small text-muted">Prêt.</div>
            </div>
        </div>
    </div>

    <div id="errorBox" class="alert alert-danger d-none"></div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle" id="resultTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Transfert</th>
                            <th>Horaire</th>
                            <th>Véhicule</th>
                            <th>Hermes</th>
                            <th>Statut</th>
                            <th>Détails</th>
                        </tr>
                    </thead>
                    <tbody id="resultBody">
                        <tr>
                            <td colspan="7" class="text-muted">Aucune donnée pour l’instant.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="summaryBox" class="mt-2 small text-muted"></div>
        </div>
    </div>
</div>

<script>
(function () {
    const driverIdEl = document.getElementById('driverId');
    const dateEl = document.getElementById('dateInput');
    const apiBaseEl = document.getElementById('apiBase');
    const fetchBtn = document.getElementById('fetchBtn');
    const clearBtn = document.getElementById('clearBtn');
    const statusLine = document.getElementById('statusLine');
    const errorBox = document.getElementById('errorBox');
    const resultBody = document.getElementById('resultBody');
    const summaryBox = document.getElementById('summaryBox');

    function badge(text, cls) {
        return `<span class="badge ${cls}">${text}</span>`;
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function formatHermesCell(row) {
        if (row.data_unavailable) {
            return badge("Données Hermes indisponibles", "text-bg-secondary");
        }
        return badge("OK (track-info)", "text-bg-success");
    }

    function formatStatusCell(row) {
        if (row.data_unavailable) {
            return badge("Retention / historique non dispo", "text-bg-secondary");
        }
        if (row.suspect) {
            return badge("À vérifier", "text-bg-danger");
        }
        return badge("Cohérent", "text-bg-success");
    }

    function renderDetails(row, idx) {
        const detailId = `detail_${idx}`;

        const reason = row.suspect_reason ? `<div><strong>Raison :</strong> ${escapeHtml(row.suspect_reason)}</div>` : '';
        const win = row.window
            ? `<div><strong>Fenêtre :</strong> ${escapeHtml(row.window.start)} → ${escapeHtml(row.window.end)} (±${escapeHtml(row.window.buffer_minutes)} min)</div>`
            : '';

        let track = '';
        if (row.track_day_summary) {
            const t = row.track_day_summary;
            track = `
                <div class="mt-2">
                    <strong>Résumé Hermes (jour) :</strong>
                    <div>Événements : ${escapeHtml(t.event_count)}</div>
                    <div>Premier : ${escapeHtml(t.first_time)}</div>
                    <div>Dernier : ${escapeHtml(t.last_time)}</div>
                    <div>En mouvement : ${escapeHtml(t.moving_events)}</div>
                    <div>Vitesse max : ${escapeHtml(t.max_speed)}</div>
                    <div>Event dans la fenêtre : ${row.has_event_in_transfer_window ? badge('Oui','text-bg-success') : badge('Non','text-bg-warning')}</div>
                </div>
            `;
        }

        return `
            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#${detailId}">
                Voir
            </button>
            <div class="collapse mt-2" id="${detailId}">
                <div class="card card-body">
                    ${win}
                    ${reason}
                    ${track}
                </div>
            </div>
        `;
    }

    function renderRows(payload) {
        const rows = payload.results || [];
        if (!rows.length) {
            resultBody.innerHTML = `<tr><td colspan="7" class="text-muted">Aucun transfert trouvé.</td></tr>`;
            summaryBox.textContent = '';
            return;
        }

        resultBody.innerHTML = rows.map((row, idx) => {
            const tr = row.transfer || {};
            const vh = row.vehicule || {};

            const transferTxt = `#${escapeHtml(tr.id)} – ${escapeHtml(tr.from)} → ${escapeHtml(tr.target)} (${escapeHtml(tr.km)} km)`;
            const timeTxt = `${escapeHtml(tr.start_date)} → ${escapeHtml(tr.end_date)}`;
            const vehTxt = vh && vh.id
                ? `${escapeHtml(vh.name)} (${escapeHtml(vh.plaka)})`
                : `—`;

            return `
                <tr>
                    <td>${idx + 1}</td>
                    <td>${transferTxt}</td>
                    <td>${timeTxt}</td>
                    <td>${vehTxt}</td>
                    <td>${formatHermesCell(row)}</td>
                    <td>${formatStatusCell(row)}</td>
                    <td>${renderDetails(row, idx)}</td>
                </tr>
            `;
        }).join('');

        summaryBox.textContent = `Date : ${payload.date} — Chauffeur : ${payload.driver_id} — Transferts : ${payload.transfer_count}`;
    }

    async function fetchReport() {
        const driverId = driverIdEl.value;
        const date = dateEl.value;

        if (!driverId || !date) {
            errorBox.classList.remove('d-none');
            errorBox.textContent = "Veuillez renseigner l’ID chauffeur et la date.";
            return;
        }

        errorBox.classList.add('d-none');
        statusLine.textContent = "Chargement…";

        const url = `${apiBaseEl.value}/${encodeURIComponent(driverId)}/driving?date=${encodeURIComponent(date)}`;

        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();

            if (!res.ok) {
                throw new Error(data.error || "Erreur API");
            }

            renderRows(data);
            statusLine.textContent = "Terminé.";
        } catch (e) {
            resultBody.innerHTML = `<tr><td colspan="7" class="text-muted">Aucune donnée.</td></tr>`;
            summaryBox.textContent = '';
            errorBox.classList.remove('d-none');
            errorBox.textContent = e.message || "Une erreur est survenue.";
            statusLine.textContent = "Erreur.";
        }
    }

    function clearUI() {
        errorBox.classList.add('d-none');
        resultBody.innerHTML = `<tr><td colspan="7" class="text-muted">Aucune donnée pour l’instant.</td></tr>`;
        summaryBox.textContent = '';
        statusLine.textContent = "Prêt.";
    }

    fetchBtn.addEventListener('click', fetchReport);
    clearBtn.addEventListener('click', clearUI);

    // Auto-load (optionnel)
    // fetchReport();
})();
</script>
@endsection
