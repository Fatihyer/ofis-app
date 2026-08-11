@extends('layouts.kaptan')
@section('style')
  <link href="{{ asset('css/daterangepicker.css') }}" rel="stylesheet">

@endsection
@section('content')

<table class="table table-bordered">
        <tr>
            <th width="80px">@sortablelink('post_id',__('app.file'))</th>
            <th>@sortablelink('start_date',__('app.start_date'))</th>
            <th>@sortablelink('servicetype_id',__('app.service'))</th>
            <th>@sortablelink('driver_id',__('app.driver'))</th>
            <th>Cash</th>
            <th>@lang('app.amount')</th>
            <th>@lang('app.from') @lang('app.to')</th>
           <th>#</th>
            
        </tr>
     
            @foreach($harekets as $key => $hareket)
                <tr>
                  <td>
                  @if ($hareket->hareketable_type=="App\Model\Transfer")
                    <a href="{{ route('transfers.show', $hareket->hareketable->id ) }}"><i class="fa fa-eye" aria-hidden="true"></i></a>
                    {{$hareket->post_id}}</a>
                  @endif
                  </td>
                    <td>{{date('d-m-Y H:m', strtotime($hareket->tarih)) }}</td>
                    <td>{{isset($hareket->hareketable->servicetype_id)?$hareket->hareketable->servicetype->name:"Tahsilat"}}</td>
                 <td> {{$hareket->acente->name}}</td>
                     <td bgcolor="{{($hareket->amount>0)?"red":"" }}">{{ ($hareket->amount>0)?$hareket->amount:0 }}</td>
                    <td>{{ ($hareket->amount<0)?$hareket->amount:0  }}</td>
                    <td>{{ $hareket->hareketable->from }}->{{ $hareket->hareketable->target }}</td>
                    <td>{{isset($hareket->status->name)?$hareket->status->name:"" }}</td>
                  
                  
                  
                </tr>
            @endforeach
    
    </table>
   
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