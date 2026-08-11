<!DOCTYPE html>
<html>
<head>
    <title>Driver Work Days</title>
</head>
<body>
    <h1>Driver Work Days</h1>
    <table border="1">
        <tr>
            <th>Driver ID</th>
            <th>Driver Name</th>
            <th>Days Worked</th>
        </tr>
        @foreach ($driverWorkDays as $workDay)
        <tr>
            <td>{{ $workDay['driver_id'] }}</td>
            <td>{{ $workDay['driver_name'] }}</td>
            <td>{{ $workDay['days_worked'] }}</td>
        </tr>
        @endforeach
    </table>
</body>
</html>
