@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Modifier la demande #{{ $talep->id }}</h2>
        <div>
            <a href="{{ route('talepler.show', $talep->id) }}" class="btn btn-info">
                Voir le détail
            </a>
            <a href="{{ route('talepler.index') }}" class="btn btn-secondary">
                Retour à la liste
            </a>
        </div>
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

    <form action="{{ route('talepler.update', $talep->id) }}" method="POST" id="talepForm">
        @csrf
        @method('PUT')

        @include('talepler.partials.form', ['talep' => $talep])

    </form>
</div>

@include('talepler.partials.demande-completeness-warning')
@endsection