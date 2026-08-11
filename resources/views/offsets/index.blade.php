@extends('layouts.app')
@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">@lang('app.offsets')</h3>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <form action="{{ route('offsets.index') }}" method="GET" class="form-inline">
                        <input type="text" name="search" class="form-control mr-2" placeholder="Search" value="{{ request()->get('search') }}">
                        <input type="date" name="start_date" class="form-control mr-2" value="{{ request()->get('start_date') }}">
                        <input type="date" name="end_date" class="form-control mr-2" value="{{ request()->get('end_date') }}">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                    <form action="{{ route('offsets.index') }}" method="GET" class="form-inline">
                        <select name="per_page" class="form-control mr-2" onchange="this.form.submit()">
                            <option value="10"{{ request()->get('per_page') == 10 ? ' selected' : '' }}>10</option>
                            <option value="20"{{ request()->get('per_page') == 20 ? ' selected' : '' }}>20</option>
                            <option value="50"{{ request()->get('per_page') == 50 ? ' selected' : '' }}>50</option>
                            <option value="100"{{ request()->get('per_page') == 100 ? ' selected' : '' }}>100</option>
                        </select>
                    </form>
                    <div>
                        <a href="{{ route('offsets.create') }}" class="btn btn-danger">Add Offset</a>
                        <a href="{{ route('multioffsets') }}" class="btn btn-secondary">MultiOffset</a>
                    </div>
                </div>
                <p>Page {{ $offsets->currentPage() }} of {{ $offsets->lastPage() }}</p>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th></th>
                                <th scope="col"><a href="{{ route('offsets.index', ['sort_by' => 'id', 'order' => request()->get('order') === 'asc' ? 'desc' : 'asc']) }}">#</a></th>
                                <th scope="col"><a href="{{ route('offsets.index', ['sort_by' => 'tarih', 'order' => request()->get('order') === 'asc' ? 'desc' : 'asc']) }}">Date</a></th>
                                <th scope="col"><a href="{{ route('offsets.index', ['sort_by' => 'a_acente_id', 'order' => request()->get('order') === 'asc' ? 'desc' : 'asc']) }}">From</a></th>
                                <th scope="col"><a href="{{ route('offsets.index', ['sort_by' => 'b_acente_id', 'order' => request()->get('order') === 'asc' ? 'desc' : 'asc']) }}">To</a></th>
                                <th scope="col">@lang('app.amount')</th>
                                <th scope="col">@lang('app.exchange_name')</th>
                                <th scope="col">@lang('app.detail')</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($offsets as $offset)
                            <tr>
                                <td>
                                    <a href="{{ route('offsets.edit', $offset->id) }}" class="btn btn-primary btn-block">Edit</a>
                                </td>
                                <td><a href="{{ route('offsets.show', $offset->id ) }}"><b>{{ $offset->id}}</b></a></td>
                                <td>{{ date('d/m/Y', strtotime($offset->tarih)) }}</td>
                               <td>
  <a href="{{ route('acentes.show', $offset->a_acente_id ) }}?src=balance">
        {{ isset($offset->alacakli->name) ? $offset->alacakli->name : "" }}
  </a>
</td>

<td>
  <a href="{{ route('acentes.show', $offset->b_acente_id ) }}?src=balance">
   
       {{ isset($offset->borclu->name) ? $offset->borclu->name : "" }}
  </a>
</td>
    @if (isset($offset->harekets->first()['amount']))
                                <td>{{ abs($offset->harekets->first()['amount']) }}</td>
                                <td>{{ Form::select('kur', $kurs, $offset->harekets->first()['kur_id']) }}</td>
                                @endif
                                <td>{{ $offset->aciklama }}</td>
                                <td>
                                    <form class="deleteinvoice" action="{{ route('offsets.destroy', $offset->id) }}" method="POST">
                                        {{ method_field('DELETE') }}
                                        {{ csrf_field() }}
                                        <button type="button" onclick="confirmDelete(this.form)" class="btn btn-danger">
    <i class="fas fa-trash-alt"></i>
</button>

                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-center">
                        {{ $offsets->appends(request()->query())->links() }}
                    </div>
                    <a href="{{ route('offsets.create') }}" class="btn btn-danger">Add Offset</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
function confirmDelete(form) {
    let pass = prompt("Please write password for delete:");
    if (pass === "225522") {
        form.submit();
    } else {
        alert("Hatalı şifre!");
    }
}
</script>
@endsection
