@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <form method="POST" action="{{ route('tasks.update', $task) }}">
        @csrf
        @method('PUT')
        @include('tasks._form')
    </form>
</div>
@endsection
