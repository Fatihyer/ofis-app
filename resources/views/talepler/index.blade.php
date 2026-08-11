@extends('layouts.app')

@section('style')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
<style>
.demande-page{background:#f8fafc;min-height:calc(100vh - 90px);padding:16px}.demande-responsable-filter{display:flex;align-items:center;gap:8px;padding:0 14px 12px}.demande-responsable-filter label{margin:0;color:#475569;font-size:12px;font-weight:900;white-space:nowrap}.demande-responsable-filter select{max-width:280px;border:1px solid #cbd5e1;border-radius:6px;padding:6px 8px;font-size:12px}.demande-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:14px}.demande-head h2{margin:0;color:#0f172a;font-size:25px;font-weight:850}.demande-head small{display:block;color:#64748b;font-weight:700;margin-top:3px}.demande-actions{display:flex;gap:8px;flex-wrap:wrap}.demande-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 22px rgba(15,23,42,.06);margin-bottom:14px;overflow:hidden}.demande-card-h{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:12px 14px;border-bottom:1px solid #e5e7eb}.demande-filters{display:flex;gap:8px;flex-wrap:wrap;padding:12px 14px}.filter-chip{border:1px solid #cbd5e1;background:#fff;color:#334155;border-radius:999px;padding:7px 11px;font-size:12px;font-weight:850;line-height:1}.filter-chip.active{background:#0f172a;color:#fff;border-color:#0f172a}.filter-chip.market-latam.active{background:#be123c;border-color:#be123c}.filter-chip.market-mena.active{background:#7c3aed;border-color:#7c3aed}.filter-chip.market-turkiye.active{background:#047857;border-color:#047857}.filter-chip.market-internet.active{background:#2563eb;border-color:#2563eb}.filter-chip.market-france.active{background:#b45309;border-color:#b45309}.filter-chip.pending.active{background:#dc2626;border-color:#dc2626}.market-chart-grid{display:grid;grid-template-columns:repeat(6,minmax(110px,1fr));gap:10px;padding:12px 14px}.market-chart-item{border:1px solid #e5e7eb;background:#fff;border-radius:8px;padding:10px;text-align:left;cursor:pointer}.market-chart-item:hover{border-color:#94a3b8}.market-chart-label{display:flex;justify-content:space-between;gap:8px;color:#334155;font-size:12px;font-weight:900}.market-chart-count{color:#0f172a}.market-chart-track{height:8px;border-radius:999px;background:#e5e7eb;overflow:hidden;margin-top:8px}.market-chart-bar{height:100%;border-radius:999px;background:#64748b}.market-chart-bar.france{background:#b45309}.market-chart-bar.turkiye{background:#047857}.market-chart-bar.mena{background:#7c3aed}.market-chart-bar.latam{background:#be123c}.market-chart-bar.internet{background:#2563eb}.market-chart-bar.pending{background:#dc2626}.demande-table-wrap{padding:0 10px 10px}.demande-table{border-collapse:separate!important;border-spacing:0 8px!important}.demande-table thead th{background:#f8fafc!important;color:#475569!important;border:0!important;font-size:11px;text-transform:uppercase;white-space:nowrap}.demande-table tbody tr{background:#fff;box-shadow:0 1px 4px rgba(15,23,42,.08)}.demande-table tbody td{border-top:1px solid #e5e7eb!important;border-bottom:1px solid #e5e7eb!important;vertical-align:top;font-size:12px}.demande-table tbody td:first-child{border-left:1px solid #e5e7eb!important;border-radius:8px 0 0 8px}.demande-table tbody td:last-child{border-right:1px solid #e5e7eb!important;border-radius:0 8px 8px 0}.market-badge{display:inline-flex;align-items:center;border-radius:999px;padding:4px 8px;font-size:11px;font-weight:900}.market-latam{background:#ffe4e6;color:#9f1239}.market-mena{background:#ede9fe;color:#5b21b6}.market-turkiye{background:#d1fae5;color:#065f46}.market-internet{background:#dbeafe;color:#1d4ed8}.market-france{background:#fef3c7;color:#92400e}.country-badge{display:inline-flex;align-items:center;gap:4px;border-radius:999px;background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe;padding:3px 8px;font-size:11px;font-weight:900;white-space:nowrap}.price-stack{display:grid;gap:2px;min-width:145px}.compact-price{min-width:132px}.price-stack span{display:flex;justify-content:space-between;gap:8px;color:#64748b}.price-stack strong{color:#0f172a;white-space:nowrap}.price-pending{display:inline-flex;border-radius:999px;background:#fee2e2;color:#991b1b;padding:2px 7px;font-weight:900}.admin-price-saisi{display:inline-flex;align-items:center;gap:4px;border-radius:999px;background:#dcfce7;color:#166534;border:1px solid #bbf7d0;padding:2px 7px;font-size:11px;font-weight:900;white-space:nowrap}.admin-price-saisi i{font-size:10px}.row-en-attente td{background:#fff7ed!important}.row-devis-envoye td{background:#eff6ff!important}.row-en-suivi td{background:#eef2ff!important}.row-confirme td{background:#ecfdf5!important}.row-annule td{background:#fef2f2!important}.row-perdu td{background:#f1f5f9!important}.demande-table .btn{border-radius:6px;font-weight:800}.dataTables_wrapper .dataTables_filter input,.dataTables_wrapper .dataTables_length select{border:1px solid #cbd5e1;border-radius:6px;padding:4px 8px}@media(max-width:1100px){.market-chart-grid{grid-template-columns:repeat(3,minmax(120px,1fr))}}@media(max-width:900px){.demande-page{padding:10px}.demande-head{display:block}.demande-actions{margin-top:10px}.demande-card-h{display:block}.demande-card-h .text-muted{display:block;margin-top:4px}.market-chart-grid{grid-template-columns:1fr 1fr}}@media(max-width:520px){.market-chart-grid{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
@php
    $marketStats = $marketStats ?? [];
    $chartItems = [
        ['key' => 'france', 'label' => 'France', 'count' => $marketStats['france'] ?? 0, 'type' => 'market'],
        ['key' => 'turkiye', 'label' => 'Turquie', 'count' => $marketStats['turkiye'] ?? 0, 'type' => 'market'],
        ['key' => 'mena', 'label' => 'MENA', 'count' => $marketStats['mena'] ?? 0, 'type' => 'market'],
        ['key' => 'latam', 'label' => 'LATAM', 'count' => $marketStats['latam'] ?? 0, 'type' => 'market'],
        ['key' => 'internet', 'label' => 'Internet', 'count' => $marketStats['internet'] ?? 0, 'type' => 'market'],
        ['key' => 'pending', 'label' => 'Prix admin', 'count' => $marketStats['pending_admin'] ?? 0, 'type' => 'pending'],
    ];
    $chartMax = max(1, max(array_column($chartItems, 'count')));
@endphp
<div class="demande-page">
    <div class="demande-head">
        <div>
            <h2>Demandes</h2>
            <small>Suivi rapide des demandes à traiter</small>
        </div>
        <div class="demande-actions">
            <a href="{{ route('talepler.options') }}" class="btn btn-outline-secondary btn-sm">Options</a>
            <a href="{{ url('/vehicle-price-rules') }}" class="btn btn-warning btn-sm">Vehicule Price Rules</a>
            <a href="{{ route('talepler.create') }}" class="btn btn-success btn-sm">Nouvelle demande</a>
            <a href="{{ route('gmail.mails') }}" class="btn btn-primary btn-sm">Créer depuis email</a>
        </div>
    </div>

    <div class="demande-card">
        <div class="demande-card-h">
            <strong>Répartition rapide</strong>
            <span class="text-muted small">Cliquez sur une barre pour filtrer.</span>
        </div>
        <div class="market-chart-grid">
            @foreach($chartItems as $item)
                @php
                    $width = max(4, round(($item['count'] / $chartMax) * 100));
                    $buttonClass = $item['type'] === 'pending' ? 'market-chart-item chart-pending' : 'market-chart-item chart-market';
                @endphp
                <button type="button" class="{{ $buttonClass }}" data-market="{{ $item['type'] === 'market' ? $item['key'] : '' }}" data-pending="{{ $item['type'] === 'pending' ? '1' : '0' }}">
                    <span class="market-chart-label">
                        <span>{{ $item['label'] }}</span>
                        <span class="market-chart-count">{{ $item['count'] }}</span>
                    </span>
                    <span class="market-chart-track">
                        <span class="market-chart-bar {{ $item['key'] }}" style="width:{{ $width }}%"></span>
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    <div class="demande-card">
        <div class="demande-card-h">
            <strong>Filtres rapides</strong>
            <span class="text-muted small">Les filtres se cumulent avec la recherche du tableau.</span>
        </div>
        <div class="demande-filters">
            <button class="filter-chip market-filter active" data-market="">Tous marchés</button>
            <button class="filter-chip market-filter market-latam" data-market="latam">LATAM</button>
            <button class="filter-chip market-filter market-mena" data-market="mena">MENA</button>
            <button class="filter-chip market-filter market-turkiye" data-market="turkiye">Turquie</button>
            <button class="filter-chip market-filter market-internet" data-market="internet">Internet</button>
            <button class="filter-chip market-filter market-france" data-market="france">France</button>
            <button class="filter-chip pending" id="prixAdminPending" type="button">Prix admin à traiter</button>
        </div>
        <div class="demande-filters pt-0">
            <button class="filter-chip status-filter active" data-status="">Tous statuts</button>
            <button class="filter-chip status-filter" data-status="En attente">En attente</button>
            <button class="filter-chip status-filter" data-status="Devis envoyé">Devis envoyé</button>
            <button class="filter-chip status-filter" data-status="En suivi">En suivi</button>
            <button class="filter-chip status-filter" data-status="Confirmé">Confirmé</button>
            <button class="filter-chip status-filter" data-status="Annulé">Annulé</button>
            <button class="filter-chip status-filter" data-status="Perdu">Perdu</button>
        </div>
        <div class="demande-responsable-filter">
            <label for="responsableFilter">Nom du responsable de la demande</label>
            <select id="responsableFilter" class="form-control form-control-sm">
                <option value="">Tous responsables</option>
                @foreach(($responsables ?? collect()) as $responsable)
                    <option value="{{ $responsable->id }}">{{ $responsable->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="demande-card">
        <div class="demande-card-h">
            <strong>Liste des demandes</strong>
            <span class="text-muted small">Vue compacte: dates, client, opération, prix et statut.</span>
        </div>
        <div class="table-responsive demande-table-wrap">
            <table class="table table-sm demande-table" id="taleplerTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Dates</th>
                        <th>Client</th>
                        <th>Agence / Resp.</th>
                        <th>Service / Véhicule</th>
                        <th>Itinéraire</th>
                        <th>Opérations</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script>
$(function () {
    let selectedMarket = '';
    let selectedStatus = '';
    let selectedResponsable = '';
    let prixAdminPending = false;

    const table = $('#taleplerTable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: {
            url: "{{ route('talepler.index') }}",
            data: function (d) {
                d.market = selectedMarket;
                d.status_filter = selectedStatus;
                d.responsable_id = selectedResponsable;
                d.prix_admin_pending = prixAdminPending ? 1 : 0;
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            {
                data: null,
                name: 'dates',
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return '<strong>Demande</strong> ' + (row.date_display ?? '-') + '<br><span class="text-muted"><strong>Opération</strong> ' + (row.operation_date_display ?? '-') + '</span>';
                }
            },
            { data: 'client_display', name: 'customer_name', defaultContent: '-' },
            {
                data: null,
                name: 'agency_user',
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return (row.acente_adi ?? '-') + '<br><span class="text-muted">' + (row.user_adi ?? '-') + '</span>';
                }
            },
            {
                data: null,
                name: 'service_vehicle',
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return (row.service_type_name ?? '-') + '<br><span class="text-muted">' + (row.vehicule_name ?? '-') + '</span>';
                }
            },
            { data: 'route_display', name: 'route_display', orderable: false, searchable: false, defaultContent: '-' },
            { data: 'operation_summary', name: 'operation_summary', orderable: false, searchable: false, defaultContent: '-' },
            { data: 'price_display', name: 'price_display', orderable: false, searchable: false, defaultContent: '-' },
            { data: 'status_display', name: 'konfirme_durumu', defaultContent: '-' },
            { data: 'action', name: 'action', orderable: false, searchable: false, defaultContent: '-' }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        createdRow: function (row, data) {
            const statusMap = {
                'En attente':  'row-en-attente',
                'Devis envoyé': 'row-devis-envoye',
                'En suivi':    'row-en-suivi',
                'Confirmé':    'row-confirme',
                'Annulé':      'row-annule',
                'Perdu':       'row-perdu',
            };
            const cls = statusMap[data.konfirme_durumu];
            if (cls) $(row).addClass(cls);
        },
        language: {
            processing: "Traitement en cours...",
            search: "Rechercher:",
            lengthMenu: "Afficher _MENU_ éléments",
            info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
            infoEmpty: "Aucun élément disponible",
            infoFiltered: "(filtré de _MAX_ éléments au total)",
            loadingRecords: "Chargement...",
            zeroRecords: "Aucun résultat trouvé",
            emptyTable: "Aucune donnée disponible",
            paginate: { first: "Premier", previous: "Précédent", next: "Suivant", last: "Dernier" }
        }
    });

    $(document).on('click', '.market-filter', function () {
        selectedMarket = $(this).data('market') || '';
        $('.market-filter').removeClass('active');
        $(this).addClass('active');
        table.ajax.reload();
    });

    $(document).on('click', '.chart-market', function () {
        selectedMarket = $(this).data('market') || '';
        $('.market-filter').removeClass('active');
        $('.market-filter[data-market="' + selectedMarket + '"]').addClass('active');
        table.ajax.reload();
    });

    $(document).on('click', '.chart-pending', function () {
        prixAdminPending = true;
        $('#prixAdminPending').addClass('active');
        table.ajax.reload();
    });

    $(document).on('click', '.status-filter', function () {
        selectedStatus = $(this).data('status') || '';
        $('.status-filter').removeClass('active');
        $(this).addClass('active');
        table.ajax.reload();
    });

    $('#prixAdminPending').on('click', function () {
        prixAdminPending = !prixAdminPending;
        $(this).toggleClass('active', prixAdminPending);
        table.ajax.reload();
    });

    $('#responsableFilter').on('change', function () {
        selectedResponsable = $(this).val() || '';
        table.ajax.reload();
    });

    $(document).on('click', '.deleteTalep', function () {
        if (!confirm('Voulez-vous vraiment supprimer cette demande ?')) return;

        let id = $(this).data('id');

        $.ajax({
            url: '/talepler/' + id,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', _method: 'DELETE' },
            success: function (response) {
                alert(response.message || 'Demande supprimée avec succès');
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                let msg = 'Erreur lors de la suppression';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                alert(msg);
            }
        });
    });
});
</script>
@endsection
