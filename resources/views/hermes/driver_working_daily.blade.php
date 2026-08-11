@extends('layouts.app')

@section('content')

<div class="container-fluid">

<h3>Şoför Günlük Çalışma Raporu</h3>

<form class="mb-3">
<input type="date" name="day" value="{{ $date }}">
<button class="btn btn-primary">Göster</button>
</form>

<table class="table table-striped">

<thead>
<tr>
<th>ID</th>
<th>Şoför</th>
<th>Çalışma</th>
<th>Sürüş</th>
<th>Dinlenme</th>
<th>Başlangıç</th>
<th>Bitiş</th>
<th>EU Durum</th>
</tr>
</thead>

<tbody>

@foreach($rows as $r)

@php
$eu_ok = $r->driving_sec <= 9*3600;
@endphp

<tr>

<td>#{{ $r->acente_id }}</td>

<td>
{{ $r->driver_name ?? '—' }}
</td>

<td>
{{ $secToHHMM($r->working_sec) }}
</td>

<td>
<strong>{{ $secToHHMM($r->driving_sec) }}</strong>
</td>

<td>
{{ $secToHHMM($r->rest_sec) }}
</td>

<td>
{{ $minToTime($r->begin_minute) }}
</td>

<td>
{{ $minToTime($r->end_minute) }}
</td>

<td>
@if($eu_ok)
<span class="badge bg-success">OK</span>
@else
<span class="badge bg-danger">LIMIT</span>
@endif
</td>

</tr>

@endforeach

</tbody>

</table>

</div>

@endsection
