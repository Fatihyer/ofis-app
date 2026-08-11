@extends('layouts.app')

@section('title', '| Edit User')

@section('content')
  <div class="container">
<div class='col-lg-8 col-lg-offset-4'>

    <h1><i class='fa fa-user-plus'></i> Edit {{$user->name}}</h1>
    <hr>

    {{ Form::model($user, array('route' => array('users.update', $user->id), 'method' => 'PUT')) }}{{-- Form model binding to automatically populate our fields with user data --}}

    <div class="form-group">
        {{ Form::label('name', 'Name') }}
        {{ Form::text('name', null, array('class' => 'form-control')) }}
    </div>

    <div class="form-group">
        {{ Form::label('email', 'Email') }}
        {{ Form::email('email', null, array('class' => 'form-control')) }}
    </div>
      <div class="form-group">
        {{ Form::label('phone', 'Phone') }}
        {{ Form::text('phone', null, array('class' => 'form-control')) }}
    </div>

    <div class="card mb-3">
        <div class="card-header">
            Mot de passe
        </div>
        <div class="card-body">
            <p class="text-muted mb-2">Laissez vide si vous ne souhaitez pas changer le mot de passe.</p>
            <div class="form-group">
                {{ Form::label('password', 'Nouveau mot de passe') }}
                {{ Form::password('password', array('class' => 'form-control', 'autocomplete' => 'new-password')) }}
            </div>
            <div class="form-group">
                {{ Form::label('password_confirmation', 'Confirmer le mot de passe') }}
                {{ Form::password('password_confirmation', array('class' => 'form-control', 'autocomplete' => 'new-password')) }}
            </div>
        </div>
    </div>
  
      
    <h5><b>Give Role</b></h5>

    <div class='form-group'>
        @foreach ($roles as $role)
        {{ Form::checkbox('roles[]',  $role->id,$user->roles) }}
         
            {{ Form::label($role->name, ucfirst($role->name)) }}<br>

        @endforeach
    </div>
   <div class='form-group' id="driver">
       
         {{ Form::label('acente_id', 'Provider that the user can see ') }}<br>
        {{Form::select('acente_id[]',$acentes,$user->acentes,array('class' => 'form-control',"multiple"=>"multiple"))}}
          
       
    </div> 


    {{ Form::submit('Edit', array('class' => 'btn btn-primary')) }}

    {{ Form::close() }}

</div>
</div>
@endsection
