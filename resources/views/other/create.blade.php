@extends('layouts.app')
@section('content')
   
          <div class="container">
    <div class="row justify-content-md-center mt-5">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Other Services</div>
                <div class="card-body">
                  
                  
                     {{ Form::open(array('route' => 'others.store','class'=>'form-sample')) }}
                   <div class="form-row">
                  {{ Form::label('acente_id', 'Provider') }}
                  {{ Form::select('acente_id',$others, null, array('class' => 'form-control js-example-basic-single')) }}
                  </div>
                       <div class="form-row">
                <div class="form-group col-md-6">
           {{ Form::label('start_date', 'From') }}
            {{ Form::date('from', null, array('class' => 'form-control')) }}
            </div>
               <div class="form-group col-md-6">
          {{ Form::label('end_date_date', 'To') }}
            {{ Form::date('to', null, array('class' => 'form-control')) }}
             </div>
               </div>
               
                                    <div class="form-row">
                <div class="form-group col-md-12">  
                 {{ Form::label('pax', 'Pax') }}   
               {{Form::number('pax', null, array('class' => 'form-control')) }}   
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
@section('footer')

<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>


<script>
$(document).ready(function() {
    $('.js-example-basic-single').select2();
});
</script>





@endsection
