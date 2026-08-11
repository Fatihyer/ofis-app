@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>Mesai Kayıtları</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Dinamik Buton -->
    <div class="mb-4">
        @if(!$currentOfficeHour || !$currentOfficeHour->start_time)
            <!-- Mesai Başlamamışsa Start Work -->
            <form action="{{ route('office_hours.start') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary">Start Work</button>
            </form>
        @elseif(!$currentOfficeHour->end_time)
            <!-- Mesai Başlamış ama Bitmemişse End Work -->
            <form action="{{ route('office_hours.end') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-danger">End Work</button>
            </form>
        @else
            <!-- Mesai Tamamlanmışsa Bilgi -->
            <div class="alert alert-info">
                Bugünkü mesai tamamlandı. Başlangıç: {{ $currentOfficeHour->start_time }}, Bitiş: {{ $currentOfficeHour->end_time }}
            </div>
        @endif
    </div>

    <!-- Mesai Kayıtları Tablosu -->
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Tarih</th>
                <th>Başlangıç Saati</th>
                <th>Bitiş Saati</th>
                <th>Toplam Süre</th>
                <th>Notlar</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            @foreach($officeHours as $hour)
                <tr>
                    <td>{{ $hour->date }}</td>
                    <td>{{ $hour->start_time ?? 'Henüz Başlamadı' }}</td>
                    <td>{{ $hour->end_time ?? 'Henüz Bitmedi' }}</td>
                    <td>
                        @if($hour->start_time && $hour->end_time)
                            @php
                                $start = new DateTime($hour->start_time);
                                $end = new DateTime($hour->end_time);
                                $difference = $start->diff($end);
                                echo $difference->format('%h saat %i dakika');
                            @endphp
                        @else
                            ---
                        @endif
                    </td>
                    <td>{{ $hour->notes ?? '---' }}</td>
                    <td>
                    <a href="{{ route('office_hours.edit', $hour->id) }}" class="btn btn-sm btn-warning">Düzenle</a>
                    <form action="{{ route('office_hours.destroy', $hour->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bu kaydı silmek istediğinizden emin misiniz?')">Sil</button>
                    </form>
                </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
