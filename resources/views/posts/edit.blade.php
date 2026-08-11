@extends('layouts.app')

@section('title', '| Edit Post')

@section('content')

<div class="container">
    <div class="row justify-content-md-center mt-5">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Edit File</div>
                <div class="card-body">

            {{ Form::model($post, array('route' => array('posts.update', $post->id), 'method' => 'PUT')) }}
            <div class="form-group">
            {{ Form::label('title', 'Title') }}
            {{ Form::text('title', null, array('class' => 'form-control')) }}<br>
          
            {{ Form::label('body', 'File Description') }}
            {{ Form::textarea('body', null, array('class' => 'form-control')) }}<br>
             </div>
          <div class="form-row">
            <div class="form-group col-md-6">
           {{ Form::label('start_date', 'From') }}
            {{ Form::date('start_date', date("Y-m-d", strtotime($post->start_date)), array('class' => 'form-control')) }}
              </div>
               <div class="form-group col-md-6">
          {{ Form::label('end_date_date', 'To') }}
            {{ Form::date('end_date', date("Y-m-d", strtotime($post->end_date)), array('class' => 'form-control')) }}
            </div>
             </div>
                     <div class="form-row">
                <div class="form-group col-md-6">
           {{ Form::label('pax', 'Pax') }}
            {{ Form::number('pax', null, array('class' => 'form-control')) }}
            </div>
               <div class="form-group col-md-6">
          {{ Form::label('child', 'Child') }}
            {{ Form::selectRange('child', 0,10,null, array('class' => 'form-control')) }}
             </div>
               </div>   
            <div class="form-group">
             {{ Form::label('Status', 'Status') }}
            {{ Form::select('status_id', $status, null,array('class' => 'form-control')) }}
             </div>   
             <div class="form-group">
              {{ Form::label('Dosya Sorumlusu', 'Dosya Sorumlusu') }}
             {{ Form::select('user_id', $users, null,array('class' => 'form-control')) }}
              </div>   
             
           <div class="form-group">
            {{ Form::label('Acente', 'Acente') }}
            {{ Form::select('acente_id', $acente , null, ['class' => 'form-control']) }}  
              
            <br>{{ Form::label('resmi', 'Resmi') }}
             <input name='resmi' type='hidden' value='0'>
             {{ Form::checkbox('resmi')  }} 
             <br>
            {{ Form::submit('Save', array('class' => 'btn btn-primary btn-lg btn-block')) }}
             </div>
            {{ Form::close() }}
          
                </div>
            </div>
        </div>
    </div>
</div>

@endsection