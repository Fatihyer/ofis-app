@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>Mesai Saatlerini Güncelle</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('office_hours.update', $officeHour->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="date" class="form-label">Tarih</label>
            <input type="text" class="form-control" id="date" name="date" value="{{ $officeHour->date }}" disabled>
        </div>

        <div class="mb-3">
        <label for="start_time" class="form-label">Başlangıç Saati</label>
        <input type="time" class="form-control" id="start_time" name="start_time" 
        value="{{ old('start_time', \Carbon\Carbon::parse($officeHour->start_time)->format('H:i')) }}" required>
    </div>

    <div class="mb-3">
        <label for="end_time" class="form-label">Bitiş Saati</label>
        <input type="time" class="form-control" id="end_time" name="end_time" 
        value="{{ old('end_time', $officeHour->end_time ? \Carbon\Carbon::parse($officeHour->end_time)->format('H:i') : '') }}">
    </div>

        <button type="submit" class="btn btn-primary">Güncelle</button>
        <a href="{{ route('office_hours.index') }}" class="btn btn-secondary">Geri</a>
    </form>
</div>
@endsection
