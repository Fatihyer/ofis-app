<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found</title>
    @vite(['resources/js/app.js', 'resources/sass/app.scss'])

</head>
<body>
    <div class="container text-center">
        <h1>404</h1>
        <h2>Page Not Found</h2>
        <p>Sorry, the page you are looking for could not be found.</p>
        <a href="{{ url('/') }}">Go Home</a>
    </div>
</body>
</html>
