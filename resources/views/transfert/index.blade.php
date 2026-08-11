@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-3">Transfer Listesi</h4>

    <form method="GET" id="filterForm" class="form-inline mb-3">
        <label for="start_date" class="mr-2">Tarih:</label>
        <input type="text" name="start_date" id="datepicker" class="form-control mr-2"
               value="{{ request('start_date', now()->toDateString()) }}">

        <a href="{{ route('transfers.index', ['start_date' => now()->subDay()->toDateString()]) }}"
           class="btn btn-sm btn-outline-secondary mr-1">Dün</a>

        <a href="{{ route('transfers.index', ['start_date' => now()->toDateString()]) }}"
           class="btn btn-sm btn-outline-primary mr-1">Bugün</a>

        <a href="{{ route('transfers.index', ['start_date' => now()->addDay()->toDateString()]) }}"
           class="btn btn-sm btn-outline-success mr-1">Yarın</a>
         <!-- ✅ Yeni: Airport Shuttle Filtresi -->
    <select id="airportShuttleFilter" class="form-control ml-2">
        <option value="">Airport Shuttle (Hepsi)</option>
        <option value="yes">Evet</option>
        <option value="no">Hayır</option>
    </select>
        <button type="submit" class="btn btn-sm btn-primary">Filtrele</button>
    </form>

    <table id="transfersTable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Post</th>
                <th>Acente</th>
                <th>Service Type</th>
                <th>Ofis Start</th>
                <th>Date</th>
                <th>From</th>
              
                <th>Pax</th>
               
               
      
                <th>Vehicule</th>
                <th>Driver</th>
            </tr>
        </thead>
    </table>
</div>

<div class="modal fade" id="trajetModal" tabindex="-1" role="dialog" aria-labelledby="trajetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Güzergah Detayları</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="trajetModalBody">Yükleniyor...</div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<!-- jQuery ve jQuery UI DatePicker -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
$(function () {
    $("#datepicker").datepicker({ dateFormat: "yy-mm-dd" });

    const table = $('#transfersTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 50,
        ajax: {
            url: '{{ route("transferstable.list") }}',
            data: function (d) {
                d.start_date = $('#datepicker').val();
                d.airportshuttle = $('#airportShuttleFilter').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'post_id' },
            { data: 'acente_name', name: 'post.acente.name' },
            { data: 'service_type_name', name:'servicetype_id' },
            { data: 'ofis_start_input', orderable: false, searchable: false },
            { data: 'date_range', name: 'start_date' },
            { data: 'from_to', name: 'from'},
            { data: 'pax' },
            { data: 'vehicule_select', orderable: false, searchable: true },
            { data: 'driver_select', orderable: false, searchable: false },
        ]
    });

    $('#airportShuttleFilter').on('change', function () {
        table.ajax.reload();
    });

    // Inline update sadece araç ve şöför için
    $(document).on('change', '.vehicule-select, .driver-select', function () {
    const id = $(this).data('id');
    const value = $(this).val();
    const selectedText = $(this).find("option:selected").text(); // 👈 Seçilenin adı
    const field = $(this).hasClass('vehicule-select') ? 'vehicule_id' : 'driver_id';
    const fieldName = field === 'vehicule_id' ? 'Araç' : 'Şoför';

    Swal.fire({
        title: `${fieldName} Değiştirilsin mi?`,
        text: `Yeni ${fieldName}: ${selectedText}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Evet, kaydet',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('{{ route("transferstable.update") }}', {
                _token: '{{ csrf_token() }}',
                id: id,
                field: field,
                value: value
            }).done(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Güncellendi',
                    text: `${fieldName} başarıyla güncellendi`,
                    timer: 1500,
                    showConfirmButton: false
                });
            }).fail((xhr) => {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata',
                    text: xhr.responseJSON?.message || 'Güncelleme başarısız oldu. Lütfen tekrar deneyin.'
                });
            });
        } else {
            $('#transfersTable').DataTable().ajax.reload(null, false);
        }
    });
});


    // Ofis saat güncellemesi için onaylı modal
    $(document).on('blur', '.update-ofis-start', function () {
        const id = $(this).data('id');
        const value = $(this).val();

        if (!value) return;

        Swal.fire({
    title: 'Ofis Saati Değiştirilsin mi?',
    text: "Yeni ofis saati: " + value,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Evet, kaydet',
    cancelButtonText: 'İptal'
}).then((result) => {
    if (result.isConfirmed) {
        $.post('{{ route("transferstable.update") }}', {
            _token: '{{ csrf_token() }}',
            id: id,
            field: 'ofis_start',
            value: value
        }).done((response) => {
            Swal.fire({
                icon: 'success',
                title: 'Güncellendi',
                text: response.message || 'Başarıyla kaydedildi',
                timer: 1500,
                showConfirmButton: false
            });
        }).fail((xhr) => {
            const message = xhr.responseJSON?.message || 'Güncelleme başarısız oldu. Lütfen tekrar deneyin.';
            Swal.fire({
                icon: 'error',
                title: 'Hata',
                text: message
            });
        });
    } else {
        $('#transfersTable').DataTable().ajax.reload(null, false);
    }
});
    });
});



$(document).on('click', '.show-trajets', function () {
    const transferId = $(this).data('id');
    $('#trajetModal').modal('show');
    $('#trajetModalBody').html('Yükleniyor...');

    $.get(`/transferstable/${transferId}/trajets`, function (data) {
        $('#trajetModalBody').html(data);
    }).fail(function () {
        $('#trajetModalBody').html('<div class="alert alert-danger">Trajet bilgileri alınamadı.</div>');
    });
});

$('#transfersTable').on('draw.dt', function () {
    $('[data-bs-toggle="tooltip"]').tooltip();
});

</script>

@endsection
