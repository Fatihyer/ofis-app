@extends('layouts.app')
@section('style')
  <link href="{{ asset('css/daterangepicker.css') }}" rel="stylesheet">

@endsection
@section('content')
 
        <div class="row">
             <div class="card">
                <div class="card-body">
                   <h4 class="card-title">Files  <a class="btn btn-success" href="{{ route('posts.create') }}">New File</a> </h4>
                 
                     <div>
                    <form method="get" name='tarih' class="form-inline">
                    <label >@lang('app.start_date'): </label>
                    <input type="text" name="daterange" class="form-control" value="{{ app('request')->input('daterange') }}"/> 
                    <input type="hidden" name="start_date" id="hiddenStartDate"/>
                    <input type="hidden" name="end_date" id="hiddenEndDate"/>
                  </form>  
               </div>
                    
                 
                     {!! $posts->appends(\Request::except('page'))->links('vendor/pagination/bootstrap-4') !!} </div>
             
                     <div class="table-responsive">
                       <table class="table table-bordered">
                              <thead>
                                <tr>
                                  <th scope="col">@sortablelink('id',__('app.file'))</th>
                                  <th scope="col">@sortablelink('acente_id',__('app.acente'))</th>
                                  <th scope="col">Name</th>
                                  <th scope="col">@sortablelink('start_date',__('app.from'))</th>
                                    <th scope="col">To</th>
                                  <th scope="col">Balance</th>
                                 
                                  <th scope="col">#</th>
                                </tr>
                              </thead>
                              <tbody>
                          <?php $total=0;?>      
                    @foreach ($posts as $post)
                     <tr>
                                    <th scope="row"><a href="{{ route('posts.show', $post->id ) }}"><b>FP{{ $post->id}} </b></a></th>
                                     <td><a href="{{ route('acentes.show', $post->acente_id ) }}"><b>{{ $post->acente->name}}</b></a></td>
                                    <td>{{ \Illuminate\Support\Str::limit($post->title,20)}}</td>
                                     <td>{{ date("d/m/Y", strtotime($post->start_date))}}</td>
                                    <td>{{date("d/m/Y", strtotime($post->end_date))}}</td>  
                                    @php
                                        $ab1 = $post->total_ab1 ?? 0;
                                        $ab2 = $post->total_ab2 ?? 0;
                                        $sum = $ab1 - $ab2;
                                    @endphp
                                    <td class="{{ $sum >= 0 ? 'bg-success' : 'bg-danger' }} text-white">
    {{ number_format($sum, 2, ',', ' ') }}
</td>              <td>
  <select class="form-select form-select-sm js-status"
          data-post-id="{{ $post->id }}"
          style="min-width:180px;">
    @foreach($statuses as $st)
      <option value="{{ $st->id }}" {{ (int)$post->status_id === (int)$st->id ? 'selected' : '' }}>
        {{ $st->name }}
      </option>
    @endforeach
  </select>

  {{-- İstersen altta renkli küçük label --}}
  <small class="d-block mt-1 text-{{ $post->status->color->name ?? 'secondary' }}">
    {{ $post->status->name ?? '' }}
  </small>
</td>

                                   <th scope="col">
                                    <a href="{{ route('posts.show', $post->id) }}" class="btn btn-primary btn-block">Show</a>
                                </th>
                                  
                                  </tr>
                       
                       @php $total += $sum; @endphp
                    @endforeach
                           </tbody>
                  </table>
                    </div>
                    <div class="text-center">
                     {!! $posts->appends(\Request::except('page'))->links('vendor/pagination/bootstrap-4') !!}
                      {{$total}}
                    </div>
                </div>
            </div>
          
@endsection
@section('footer')
<script src="{{ asset('/js/moment.min.js') }}"></script>
  <script src="{{ asset('/js/daterangepicker.js') }}"></script>
<script>

  $('input[name="daterange"]').daterangepicker({
 
    locale: {
      format: 'DD/MM/YYYY'
    }
  });

  $('input[name="daterange"]').on('apply.daterangepicker', function(ev, picker) {
 
    $('#hiddenStartDate').val(picker.startDate.format('YYYY-MM-DD'));
  $('#hiddenEndDate').val(picker.endDate.format('YYYY-MM-DD'));
    var x = document.getElementsByName('tarih');
    x[0].submit(); // Form submission
});
</script>
@endsection
@section('scripts')
<script>
document.addEventListener('change', async (e) => {
  if (!e.target.classList.contains('js-status')) return;

  const postId = e.target.dataset.postId;
  const statusId = e.target.value;

  const res = await fetch(`{{ url('/posts') }}/${postId}/status`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ status_id: statusId })
  });

  if (!res.ok) {
    alert('Status güncellenemedi.');
  }
});
</script>

@endsection