@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <form method="POST" action="{{ route('tasks.store') }}">
        @csrf
        @include('tasks._form')
    </form>
</div>
@endsection
