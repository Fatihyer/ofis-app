<!DOCTYPE html>
<html>
<head>
    <title>Make a Call</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Make a Call</h2>
    @if (session('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @elseif (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <form action="/make-call" method="POST">
        @csrf
        <div class="form-group">
            <label for="phone-number">Phone Number</label>
            <input type="text" class="form-control" id="phone-number" name="phone_number" placeholder="Enter phone number" required>
        </div>
        <button type="submit" class="btn btn-primary">Call</button>
    </form>
    <h2 class="mt-5">Call Logs</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>To</th>
                <th>From</th>
                <th>Status</th>
                <th>Call SID</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($calls as $call)
                <tr>
                    <td>{{ $call->id }}</td>
                    <td>{{ $call->to }}</td>
                    <td>{{ $call->from }}</td>
                    <td>{{ $call->status }}</td>
                    <td>{{ $call->call_sid }}</td>
                    <td>{{ $call->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
</body>
</html>
