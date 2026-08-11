@extends('layouts.app')

@section('style')
<style>
  .cansu-requests-page .cansu-pagination svg {
    width: 16px;
    height: 16px;
    vertical-align: middle;
  }

  .cansu-requests-page .cansu-pagination nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
  }

  .cansu-requests-page .cansu-pagination .pagination {
    margin-bottom: 0;
  }
</style>
@endsection

@section('content')
<div class="cansu-requests-page">
<h1>Cansu Requests</h1>

<form method="get" class="mb-4" style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;align-items:end;">
  <div>
    <label>From</label>
    <input type="date" name="from" value="{{ request('from') }}" class="form-control">
  </div>
  <div>
    <label>To</label>
    <input type="date" name="to" value="{{ request('to') }}" class="form-control">
  </div>
  <div>
    <label>Lang</label>
    <input type="text" name="lang" value="{{ request('lang') }}" class="form-control" placeholder="fr | en | tr">
  </div>
  <div>
    <label>Email Status</label>
    <select name="email_status" class="form-control">
      <option value="">—</option>
      @foreach(['not_tried','sent','failed'] as $st)
        <option value="{{ $st }}" @selected(request('email_status')===$st)>{{ $st }}</option>
      @endforeach
    </select>
  </div>
  <div>
    <label>Search</label>
    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="name/phone/email/address">
  </div>
  <div>
    <button class="btn btn-primary">Filter</button>
    <a class="btn btn-outline-secondary" href="{{ route('cansu.csv', request()->query()) }}">Export CSV</a>
  </div>
</form>

<div class="table-responsive">
<table class="table table-sm table-striped">
  <thead>
    <tr>
      <th>ID</th>
      <th>Date</th>
      <th>Lang</th>
      <th>From</th>
      <th>To</th>
      <th>Time</th>
      <th>Pax</th>
      <th>Return</th>
      <th>OnSite</th>
      <th>Client</th>
      <th>Phone</th>
      <th>Email</th>
      <th>Status</th>
      <th>…</th>
    </tr>
  </thead>
  <tbody>
    @forelse($data as $r)
      <tr>
        <td>{{ $r->id }}</td>
        <td>{{ $r->created_at }}</td>
        <td>{{ $r->lang }}</td>
        <td style="max-width:220px">{{ $r->cansu_start }}</td>
        <td style="max-width:220px">{{ $r->cansu_end }}</td>
        <td>{{ $r->cansu_time }}</td>
        <td>{{ $r->cansu_passengers }}</td>
        <td>{{ $r->retour }} @if($r->retour_datetime) ({{ $r->retour_datetime }}) @endif</td>
        <td>{{ $r->car_sur_place }}</td>
        <td>{{ $r->client_name }}</td>
        <td>{{ $r->client_phone }}</td>
        <td>{{ $r->client_email }}</td>
        <td>{{ $r->email_status }}</td>
        <td><a href="{{ route('cansu.show',$r->id) }}">View</a></td>
      </tr>
    @empty
      <tr><td colspan="14">No records</td></tr>
    @endforelse
  </tbody>
</table>
</div>

<div class="cansu-pagination mt-3">
  {{ $data->withQueryString()->links() }}
</div>
</div>
@endsection
