@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Excel'den Yüklenen Yakıt Verileri</h2>

    @if(session('success'))
        <div style="color: green;">{{ session('success') }}</div>
    @endif

    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>Tarih</th>
                <th>Plaka</th>
                <th>Kart No</th>
                <th>Litres</th>
                <th>Fiyat</th>
                <th>Yakıt Türü</th>
                <th>km</th>
                <th>Lokasyon</th>
                <th>Acenta ID</th>
                <th>Araç ID</th>
                <th>Kaydet</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr>
                <form action="{{ route('fuel.import.save', $row->id) }}" method="POST">
                    @csrf
                    <td>{{ $row->authorized_at }}</td>
                    <td>{{ $row->vehicule_raw }}</td>
                    <td>{{ $row->card_raw }}</td>
                    <td>{{ $row->volume }}</td>
                    <td>{{ $row->amount }}</td>
                    <td>{{ $row->fuel_type }}</td>
                    <td> <input type='number' name='kilometrage' required value="{{ $row->kilometrage }}"></td>
                    <td>{{ $row->location }}</td>
                    <td><input type="number" name="acente_id" required></td>
                    <td><input type="number" name="vehicule_id" required></td>
                    <td><button type="submit">Kaydet</button></td>
                </form>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
