@extends('layouts.app')

@section('title', '| Nouveau découcher')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">Nouveau découcher</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('driver-vehicle-overnights.store') }}">
                @csrf
                @include('driver_vehicle_overnights.form')
            </form>
        </div>
    </div>
</div>
@endsection
