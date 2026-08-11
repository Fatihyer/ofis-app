<!-- sample laravel layout -->
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>My application</title>
    <!-- font awesome (required) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" />
    <!-- progress bar (not required, but cool) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.css" />
    <!-- bootstrap (required) -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" />
    <!-- date picker (required if you need date picker & date range filters) -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css"/>
    <!-- grid's css (required) - add href as => asset('vendor/leantony/grid/css/grid.css') -->
    <link rel="stylesheet" type="text/css" href="" />
</head>
<body>

<nav class="navbar navbar-expand-sm bg-primary navbar-dark">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="navbar-brand" href="/">My appliation</a>
        </li>
    </ul>
</nav>

<div class="container" style="margin-bottom: 100px;">
    <div class="row">
        @yield('content')
    </div>
</div>

<!-- modal container (required if you need to render dynamic bootstrap modals) -->
@include('leantony::modal.container')

<!-- progress bar js (not required, but cool) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js"></script>
<!-- moment js (required by datepicker library) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.1/moment.min.js"></script>
<!-- jquery (required) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<!-- popper js (required by bootstrap) -->
<script src="https://unpkg.com/popper.js/dist/umd/popper.min.js"></script>
<!-- bootstrap js (required) -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
<!-- pjax js (required) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.pjax/2.0.1/jquery.pjax.min.js"></script>
<!-- datepicker js (required for datepickers) -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<!-- required to supply js functionality for the grid - add href as => asset('vendor/leantony/grid/js/grid.js') -->
<script src="{{ asset('vendor/leantony/grid/js/grid.js') }}"></script>
<script>
    // send csrf token (see https://laravel.com/docs/5.6/csrf#csrf-x-csrf-token) - this is required
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // for the progress bar (required for progress bar functionality)
    $(document).on('pjax:start', function () {
        NProgress.start();
    });
    $(document).on('pjax:end', function () {
        NProgress.done();
    });
</script>
<!-- entry point for all scripts injected by the generated grids (required) -->
@stack('grid_js')
</body>
</html>
