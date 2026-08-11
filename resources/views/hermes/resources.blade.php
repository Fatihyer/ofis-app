@extends('layouts.app')

@section('style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection
@section('content')
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Hermes Resources (Drivers)</h3>
        <span class="badge bg-primary">{{ count($resources) }}</span>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nom (Hermes)</th>
                        <th>UID</th>
                        <th>Téléphone</th>
                        <th>Email</th>
                        <th>Lié à (Acente)</th>
                        <th style="min-width: 340px;">Associer</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($resources as $r)
                 @php
  $uid = $r['uid'] ?? null;
  $linkedAcenteId = $uid ? ($uidToAcenteId[$uid] ?? null) : null;
  $linkedAcenteLabel = $uid ? ($uidToAcenteLabel[$uid] ?? null) : null;
@endphp
                    <tr>
                        <td class="fw-semibold">{{ $r['name'] ?? '-' }}</td>
                        <td><code>{{ $uid ?? '-' }}</code></td>
                        <td>{{ $r['phone_number'] ?? '-' }}</td>
                        <td>{{ $r['email'] ?? '-' }}</td>

                        <td>
                            @if($linkedAcenteId)
  <span class="badge bg-success">OK</span>
  <div class="small text-muted mt-1">
      {{ $linkedAcenteLabel }}
  </div>
@else
  <span class="badge bg-secondary">Non</span>
@endif

                        </td>

                        <td>
                            @if(!$uid)
                                <span class="text-muted">UID yok</span>
                            @else
                                <div class="d-flex gap-2">
                                    <form method="POST" action="{{ route('hermes.resources.link') }}" class="d-flex gap-2 w-100">
                                        @csrf
                                        <input type="hidden" name="hermes_resource_uid" value="{{ $uid }}">

   @php
    $uid = $r['uid'] ?? ($r['resUid'] ?? null);
    $linkedAcenteId = $uid ? ($uidToAcenteId[$uid] ?? null) : null;
    $linkedAcenteLabel = $uid ? ($uidToAcenteLabel[$uid] ?? null) : null;
@endphp

<select name="acente_id"
        class="form-select form-select-sm acente-select"
        data-search-url="{{ route('hermes.acentes.search') }}"
        required
        style="width: 100%;">
    <option value="">Acente ara...</option>

    {{-- sadece bağlıysa 1 option bas (devasa liste yok!) --}}
    @if($linkedAcenteId)
  <option value="{{ $linkedAcenteId }}" selected>{{ $linkedAcenteLabel }}</option>
@endif

</select>

                                        <button class="btn btn-sm btn-primary" type="submit">
                                            link
                                        </button>
                                    </form>

                                    @if($linkedAcenteId)
                                        <form method="POST" action="{{ route('hermes.resources.unlink') }}">
                                            @csrf
                                            <input type="hidden" name="acente_id" value="{{ $linkedAcenteId }}">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">
                                              unlink
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(function () {
  $('.acente-select').each(function () {
    const $el = $(this);
    const url = $el.data('search-url');

    $el.select2({
      placeholder: "Acente ara...",
      allowClear: true,
      width: '100%',
      minimumInputLength: 2,
      ajax: {
        url: url,
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return { q: params.term };
        },
        processResults: function (data) {
          return data; // {results:[{id,text}]}
        },
        cache: true
      }
    });
  });
});
</script>
@endsection


