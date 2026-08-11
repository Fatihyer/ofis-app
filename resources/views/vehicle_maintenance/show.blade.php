@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 900px;">
    <div class="card card-body">
        <h1 class="h4">Frais véhicule #{{ $maintenance->id }}</h1>
        <p><strong>Véhicule:</strong> {{ optional($maintenance->vehicule)->name }}</p>
        <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($maintenance->service_date)->format('d/m/Y') }}</p>
        <p><strong>Montant:</strong> {{ number_format((float)$maintenance->amount, 2, ',', ' ') }} €</p>
        <p><strong>Fournisseur:</strong> {{ optional($maintenance->acente)->name ?: '-' }}</p>
        <p><strong>N° facture:</strong> {{ $maintenance->invoiceno ?: '-' }}</p>
        <p><strong>Mouvement cari:</strong> {{ $maintenance->hareket_id ? '#'.$maintenance->hareket_id : '-' }}</p>
        <p><strong>Description:</strong><br>{!! nl2br(e($maintenance->description)) !!}</p>
        <a href="{{ route('vehicle_maintenance.index') }}" class="btn btn-secondary">Retour</a>
    </div>
</div>
@endsection
