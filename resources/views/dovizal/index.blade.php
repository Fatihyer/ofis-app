@extends('layouts.app')
@section('style')
  <link href="{{ asset('css/daterangepicker.css') }}" rel="stylesheet">

@endsection
@section('content')
   
        <div class="row">
            <div class="col-md-12 grid-margin">
              
                   <div class="card">
                     
                       <form method="get" name='tarih' class="form-inline">
                   <a href="{{route('dovizs.index')}}?start_date={{date('Y-m-d', strtotime($yesterday))}}"><</a>
                         <input type="text" name="daterange" class="form-control" value="{{date('d-m-Y',strtotime($today)) }}"/> 
                      <a href="{{route('dovizs.index')}}?start_date={{date('Y-m-d', strtotime($tomorrow))}}">></a>  
                    <input type="hidden" name="start_date" id="hiddenStartDate"/>
                   
                  </form>  
                           <table border="0" cellpadding="10" cellspacing="0" width="800">
                      <tr>
                          <td></td>
                          <td>USD</td>
                          <td>EURO</td>
                      </tr>
                      <tr>
                          <td>TCMB ALIŞ</td>
                          <td>{{$usdal}}</td>
                          <td>{{$euroal}}</td>
                      </tr>
                              <tr>
                          <td>OFIS ALIŞ</td>
                      
              
                          <td>@if (isset($usdkur->value)) 
                           {{ Form::model($usdkur, array('route' => array('dovizs.update', $usdkur->id),'method' => 'PUT'))}}
                           
                            {{Form::hidden('kur_id',$usdid->value)}} 
                            {{Form::hidden('tarih',$today)}} 
                            {{Form::text('value',$usdkur->value)}} 
                            {{Form::submit('edit')}}
                             {{Form::close()}}
                            @else
                            {{ Form::open(array('route' => 'dovizs.store','class'=>'form-sample')) }} 
                            {{Form::hidden('kur_id',$usdid->value)}} 
                            {{Form::hidden('tarih',$today)}} 
                            {{Form::hidden('value',$usdal)}} 
                            {{Form::submit('+')}}
                            {{Form::close()}}
                            @endif
                                </td>
                          <td>@if (isset($eurkur->value)) 
                             {{ Form::model($eurkur, array('route' => array('dovizs.update', $eurkur->id),'method' => 'PUT'))}}
                           
                            {{Form::hidden('kur_id',$eurid->value)}} 
                            {{Form::hidden('tarih',$today)}} 
                            {{Form::text('value',$eurkur->value)}} 
                            {{Form::submit('edit')}}
                             {{Form::close()}}
                            
                            
                            @else
                            {{ Form::open(array('route' => 'dovizs.store','class'=>'form-sample')) }} 
                            {{Form::hidden('kur_id',$eurid->value)}} 
                            {{Form::hidden('tarih',$today)}} 
                            {{Form::hidden('value',$euroal)}} 
                            {{Form::submit('+')}}
                            {{Form::close()}}
                            @endif</td>
                      </tr>
                    
                  </table>
                   
                    
                     
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
      format: 'DD-MM-YYYY'
    },
   singleDatePicker: true,
    showDropdowns: true,
    minYear: 2018,
  });

    $('input[name="daterange"]').on('apply.daterangepicker', function(ev, picker) {
 
    $('#hiddenStartDate').val(picker.startDate.format('YYYY-MM-DD'));
    var x = document.getElementsByName('tarih');
    x[0].submit(); // Form submission
});  
   
    
</script>

@endsection    