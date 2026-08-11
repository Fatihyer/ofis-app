@php
    $emailRows = old('emails');

    if ($emailRows === null && isset($acente)) {
        $emailRows = $acente->emails->pluck('email')->all();
    }

    $emailRows = collect($emailRows ?: [''])->values();
@endphp

<div class="col-sm-9" data-acente-email-list>
    @foreach($emailRows as $email)
        <div class="input-group mb-2" data-acente-email-row>
            <input type="email" name="emails[]" value="{{ $email }}" class="form-control" placeholder="email@example.com" autocomplete="email">
            <div class="input-group-append">
                <button type="button" class="btn btn-outline-danger" data-acente-email-remove title="Sil">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    @endforeach
</div>
<div class="col-sm-9 offset-sm-3">
    <button type="button" class="btn btn-secondary btn-sm" data-acente-email-add>Yeni Email Ekle</button>
</div>

@once
<script>
(function () {
    if (window.acenteEmailFieldsReady) return;
    window.acenteEmailFieldsReady = true;

    function addEmailRow(list) {
        if (!list) return;

        var row = document.createElement('div');
        row.className = 'input-group mb-2';
        row.setAttribute('data-acente-email-row', '');
        row.innerHTML = '<input type="email" name="emails[]" class="form-control" placeholder="email@example.com" autocomplete="email">' +
            '<div class="input-group-append">' +
            '<button type="button" class="btn btn-outline-danger" data-acente-email-remove title="Sil"><i class="fa fa-times"></i></button>' +
            '</div>';
        list.appendChild(row);
        row.querySelector('input').focus();
    }

    document.addEventListener('click', function (event) {
        var addButton = event.target.closest('[data-acente-email-add]');
        if (addButton) {
            addEmailRow(addButton.closest('.form-group').querySelector('[data-acente-email-list]'));
            return;
        }

        var removeButton = event.target.closest('[data-acente-email-remove]');
        if (!removeButton) return;

        var list = removeButton.closest('[data-acente-email-list]');
        var row = removeButton.closest('[data-acente-email-row]');
        var rows = list ? list.querySelectorAll('[data-acente-email-row]') : [];

        if (rows.length > 1) {
            row.remove();
        } else if (row) {
            row.querySelector('input').value = '';
        }
    });
})();
</script>
@endonce
