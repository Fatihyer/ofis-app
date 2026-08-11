@extends('layouts.app')

@section('title', '| Edit User')

@section('content')
  <div class="container">
<div class='col-lg-8 col-lg-offset-4'>

    <h1><i class='fa fa-user-plus'></i> Edit {{$user->name}}</h1>
    <hr>

    {{ Form::model($user, array('route' => array('users.passwordupdate', $user->id), 'method' => 'POST')) }}{{-- Form model binding to automatically populate our fields with user data --}}

    {{Form::hidden('id',$user->id)}}
    <div class="form-group">
        {{ Form::label('password', 'Password') }}<br>
        {{ Form::password('password', array('class' => 'form-control')) }}

    </div>

    <div class="form-group">
        {{ Form::label('password', 'Confirm Password') }}<br>
        {{ Form::password('password_confirmation', array('class' => 'form-control')) }}

    </div>

    {{ Form::submit('Edit', array('class' => 'btn btn-primary')) }}

    {{ Form::close() }}

</div>
</div>
@endsection

