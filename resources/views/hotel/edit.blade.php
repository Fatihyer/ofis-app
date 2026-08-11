@extends('layouts.app')
@section('content')
   
          <div class="container">
    <div class="row justify-content-md-center mt-5">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Hotel Reservations</div>
                <div class="card-body">
                  
                   {{ Form::model($hotel, array('route' => array('hotels.update', $hotel->id), 'method' => 'PUT','class'=>'form-sample')) }}
                  
                   <div class="form-row">
                  {{ Form::label('acente_id', 'Hotel') }}
                  {{ Form::select('acente_id',$otels, null, array('class' => 'form-control')) }}
                  </div>
                       <div class="form-row">
                <div class="form-group col-md-6">
           {{ Form::label('from', 'From') }}
            {{ Form::date('from', date("Y-m-d", strtotime($hotel->from)), array('class' => 'form-control')) }}
            </div>
               <div class="form-group col-md-6">
          {{ Form::label('to', 'To') }}
            {{ Form::date('to',  date("Y-m-d", strtotime($hotel->to)), array('class' => 'form-control')) }}
             </div>
               </div>
               
                               <div class="form-row">
                <div class="form-group col-md-2">
           {{ Form::label('sng', 'Sng') }}
            {{ Form::number('sng', null, array('class' => 'form-control')) }}
            </div>
               <div class="form-group col-md-2">
          {{ Form::label('dbl', 'Dbl') }}
            {{ Form::number('dbl', null, array('class' => 'form-control')) }}
             </div>
              
                   <div class="form-group col-md-2">
          {{ Form::label('trp', 'Trp') }}
            {{ Form::number('trp', null, array('class' => 'form-control')) }}
             </div>
               
               <div class="form-group col-md-2">
          {{ Form::label('qtr', 'Qtr') }}
            {{ Form::number('qtr', null, array('class' => 'form-control')) }}
             </div>
               <div class="form-group col-md-2">
          {{ Form::label('chd', 'familial/Suite') }}
            {{ Form::number('fam', null, array('class' => 'form-control')) }}
             </div>
              
               </div>   
                  
                  <div class="form-row"> 
                  <div class="form-group col-md-3">
          {{ Form::label('chd', 'Accomodation') }}
            {{ Form::select('servicetype_id',$servicetype,"", array('class' => 'form-control')) }}
             </div>
                    <div class="form-group col-md-3">
          {{ Form::label('chd', 'Status') }}
            {{ Form::select('status_id', $status,"", array('class' => 'form-control')) }}
             </div>
                      <div class="form-group col-md-2">
          {{ Form::label('chd', 'chd') }}
            {{ Form::number('Chd', null, array('class' => 'form-control')) }}
             </div>
                                     <div class="form-group col-md-2">
          {{ Form::label('chd', 'chd Age') }}
            {{ Form::text('chdyears', null, array('class' => 'form-control')) }}
             </div>
                  </div>  
                  
                  
                  <div class="form-row">
                <div class="form-group col-md-12">  
                 {{ Form::label('comment', 'Comment') }}   
               {{Form::text('comment', null, array('class' => 'form-control')) }}   
                  </div>
                  </div>
                  
                    <div class="form-row">
                   {{Form::hidden('post_id',Request::get('file'))}}   
                  {{Form::Submit('Save', array('class' => 'btn btn-secondary'))}}    
                  </div>
                  
                 {{ Form::close() }}
            </div>
</div>
      </div></div></div>
 

@endsection