@extends('layouts.app')
@section('content')

<div class="container">
    <div class="row justify-content-md-center mt-5">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Edit Name</div>
                <div class="card-body">
                     {{ Form::model($clients, array('route' => array('clients.update', $clients->id), 'method' => 'PUT')) }} 
                      <div  class="form-row">
                        {{ Form::label('tittle', 'Title') }}
                        {{ Form::select('title',['Mr'=>'Mr','Mrs'=>'Mrs','Chld'=>'Chld','BB'=>'BB','Dr'=>'Dr','Prof'=>'Prof'],null, array('class' => 'form-control')) }}
                     
                    
                          
                      </div>
                       <div  class="form-row">
                        {{ Form::label('name', 'Name') }}
                        {{ Form::text('name',null, array('class' => 'form-control','required' => 'required')) }}
                  
                      </div>
                         <div  class="form-row">
                        {{ Form::label('surname', 'Surname') }}
                        {{ Form::text('surname',null, array('class' => 'form-control')) }}
            
                      </div>
                  
                        <div  class="form-row">
                        {{ Form::label('tel', 'Tel') }}
                        {{ Form::text('tel',null, array('class' => 'form-control')) }}
                 
                      </div>
                       <div  class="form-row">
                        {{ Form::label('email', 'Email') }}
                          
                        {{ Form::text('email',null, array('class' => 'form-control')) }}
               
                    
                      </div>
                    
                      <div  class="form-row">
                          {{ Form::label('comments', 'Comments') }}
                    {{ Form::text('comments',null, array('class' => 'form-control')) }} 
                  
                  </div>
         {{Form::submit('Edit Name',array('class'=>'btn btn-primary'))}}
       
   
       {{form::close()}}
 
          </div>
                        </div>
                    </div>
                </div>
            </div>

            @endsection


