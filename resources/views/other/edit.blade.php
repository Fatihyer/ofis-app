@extends('layouts.app')
@section('content')
   
          <div class="container">
    <div class="row justify-content-md-center mt-5">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Other Services Edit</div>
                <div class="card-body">
                  
                  
                   
                    {{ Form::model($other, array('route' => array('others.update', $other->id), 'method' => 'PUT','class'=>'form-sample')) }}
                   <div class="form-row">
                  {{ Form::label('acente_id', 'Provider') }}
                  {{ Form::select('acente_id',$others, null, array('class' => 'form-control')) }}
                  </div>
                       <div class="form-row">
                <div class="form-group col-md-6">
           {{ Form::label('start_date', 'From') }}
            {{ Form::date('from', date("Y-m-d", strtotime($other->from)), array('class' => 'form-control')) }}
            </div>
               <div class="form-group col-md-6">
          {{ Form::label('end_date_date', 'To') }}
            {{ Form::date('to', date("Y-m-d", strtotime($other->to)), array('class' => 'form-control')) }}
             </div>
               </div>
               
                                    <div class="form-row">
                <div class="form-group col-md-12">  
                 {{ Form::label('pax', 'Pax') }}   
               {{Form::text('pax', $other->pax, array('class' => 'form-control')) }}   
                  </div>
                  </div>           
                  
                 
                  
                  
                  <div class="form-row">
                <div class="form-group col-md-12">  
                 {{ Form::label('comment', 'Comment') }}   
               {{Form::text('comment',$other->comment, array('class' => 'form-control')) }}   
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