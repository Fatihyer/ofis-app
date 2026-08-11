@extends('layouts.app')

@section('title', '| Modifier découcher')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">Modifier le découcher #{{ $overnight->id }}</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('driver-vehicle-overnights.update', $overnight->id) }}">
                @csrf
                @method('PUT')
                @include('driver_vehicle_overnights.form')
            </form>
        </div>
    </div>
</div>
@endsection
