@extends('layouts.app')

@section('content')
<div class="container-fluid" style="max-width: 980px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Ajouter un frais véhicule</h1>
        <a href="{{ route('vehicle_maintenance.index') }}" class="btn btn-outline-secondary btn-sm">Retour</a>
    </div>

    <form action="{{ route('vehicle_maintenance.store') }}" method="POST" class="card card-body">
        @csrf
        @include('vehicle_maintenance.form')
        <div class="text-right mt-3"><button type="submit" class="btn btn-primary">Enregistrer</button></div>
    </form>
</div>
@endsection
