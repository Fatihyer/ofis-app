@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Nouvelle demande</h2>
        <a href="{{ route('talepler.index') }}" class="btn btn-secondary">
            Retour à la liste
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Veuillez corriger les erreurs suivantes :</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('talepler.store') }}" method="POST" id="talepForm">
        @csrf

        @include('talepler.partials.form')

    </form>
</div>

@include('talepler.partials.demande-completeness-warning')
@endsection