@extends('layouts.app')

@section('title', 'Sticky Notes')

@section('style')
<style>
  html, body {
    height: 100%;
    margin: 0;
    padding: 0;
  }

  .note-card {
  position: relative;
  width: 220px;
  height: 160px; /* Sabit yükseklik verildi */
  padding: 15px;
  margin: 10px;
  border-radius: 10px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.2);
  color: #333;
  font-size: 14px;
  overflow: hidden; /* fazla metni gizle */
  background-color: #ffff88;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}


  .note-delete-btn {
    position: absolute;
    top: 5px;
    right: 8px;
    background: transparent;
    border: none;
    color: #555;
    font-size: 16px;
    cursor: pointer;
  }

  .note-delete-btn:hover {
    color: #d9534f;
  }

  #notes {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    height: calc(100vh - 300px); /* üstteki formdan geri kalan alan */
    overflow-y: auto;
  }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <h2 class="mt-4">Yeni Not Ekle</h2>

    <div class="row">
        <div class="col-md-6 col-lg-4 mb-3">
          <label for="note-content" class="form-label">Not İçeriği</label>
          <textarea id="note-content" class="form-control" rows="3"></textarea>
        </div>

        <div class="col-md-3 col-lg-2 mb-3">
          <label for="note-color" class="form-label">Renk Seç</label>
          <select id="note-color" class="form-select">
              <option value="#ffff88">Sarı</option>
              <option value="#a8e6cf">Yeşil</option>
              <option value="#ff8b94">Pembe</option>
              <option value="#ffd3b6">Turuncu</option>
          </select>
        </div>

        <div class="col-md-3 col-lg-2 mb-3 d-flex align-items-end">
          <button class="btn btn-primary w-100" onclick="addNote()">Ekle</button>
        </div>
    </div>

    <hr>
    <div id="notes" class="sortable-notes"></div>
</div>
@endsection

@section('footer')


<script>
function fetchNotes() {
    $.get('/sticky-notes/fetch', function(data) {
        $('#notes').html('');
        data.forEach(function(note) {
            $('#notes').append(`
                <div class="note-card" data-id="${note.id}" style="background-color:${note.color}">
                    <button class="note-delete-btn" onclick="deleteNote(${note.id})">&times;</button>
                    ${note.content}
                </div>
            `);
        });

        $('.sortable-notes').sortable({
            update: function () {
                let order = [];
                $('.note-card').each(function () {
                    order.push($(this).data('id'));
                });

                $.post('/sticky-notes/update-order', {
                    order: order,
                    _token: $('meta[name="csrf-token"]').attr('content')
                });
            }
        });
    });
}

function addNote() {
    $.post('/sticky-notes', {
        content: $('#note-content').val(),
        color: $('#note-color').val(),
        _token: $('meta[name="csrf-token"]').attr('content')
    }, function () {
        $('#note-content').val('');
        fetchNotes();
    });
}

function deleteNote(id) {
    $.ajax({
        url: '/sticky-notes/' + id,
        type: 'DELETE',
        data: { _token: $('meta[name="csrf-token"]').attr('content') },
        success: function () {
            fetchNotes();
        }
    });
}

fetchNotes();
</script>
@endsection
