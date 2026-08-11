@extends('layouts.app')

@section('title', '| Edit User')

@section('content')

<div class="container mt-5">
    <h2>Permanence Saatleri</h2>

    <!-- Filtreleme Formu -->
    <form method="GET" action="{{ route('userattendances') }}" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <label for="user_id" class="form-label">User</label>
                <select name="user_id" id="user_id" class="form-control">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="{{ request('start_date') }}">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="{{ request('end_date') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </div>
    </form>

    <!-- Tablo -->
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>User ID</th>
                <th>Permanence Start</th>
                <th>Permanence Finish</th>
                <th>Permanence Name</th>
                <th>Total Permanence Time</th>
            </tr>
        </thead>
        <tbody>
            @php
            $finish = now();
            @endphp

            @foreach($userAttendances as $attendance)
                <tr>
                    <td>{{ $attendance->id }}</td>
                    <td>{{ optional($attendance->user)->name ?? 'Unknown User' }}</td>
                    <td>{{ date('d-m-Y H:i', strtotime($attendance->created_at)) }}</td>
                    <td>{{ date('d-m-Y H:i', strtotime($finish)) }}</td>
                    <td>
                        @if ($attendance->operation_user_id > 0) 
                            Operation
                        @elseif ($attendance->parisgezgini_user_id > 0) 
                            Paris Gezgini
                        @else
                            Demande
                        @endif
                    </td>
                    <td>
                        @php
                            $start = new DateTime($attendance->created_at);
                            $end = new DateTime($finish);
                            $difference = $start->diff($end);
                            echo $difference->format('%h hours %i minutes');
                        @endphp
                    </td>
                    @php
                        $finish = $attendance->created_at;
                    @endphp
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
