<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  @include('layouts.partials.head')
   @yield('style')
</head>
<body>
 <div id="app">
   @if (Auth::guest())
       @unless(request()->routeIs('mission.public*') || request()->attributes->get('public_mission'))
           Please login 
       @endunless
   @else
  
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <!-- Navbar content -->

  <a class="navbar-brand" href="{{ url('/ev') }}">{{env('APP_NAME')}}</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

 <div class="collapse navbar-collapse" id="navbarNavDropdown">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a href="{{ route('driver.app') }}" class="nav-link">App chauffeur</a>
        </li>
        <li class="nav-item">
            <a href="{{ url('/ev') }}" class="nav-link">Mes transferts</a>
        </li>
        <li class="nav-item">
            <a href="{{ route('missionlist') }}" class="nav-link">Mes missions</a>
        </li>
        <li class="nav-item">
            <a href="{{ route('kaptanshow') }}" class="nav-link">Mes comptes</a>
        </li>
    </ul> 

    <ul class="navbar-nav ml-auto align-items-lg-center">
        <li class="nav-item mr-lg-2 my-2 my-lg-0">
            @php
                $supported = config('app.supported_locales', ['tr','en','fr']);
                $labels = ['fr' => 'Français', 'en' => 'English', 'tr' => 'Türkçe'];
                $currentLocale = session('locale', app()->getLocale());
            @endphp
            <form method="POST" action="{{ route('language.change') }}" class="form-inline">
                @csrf
                <input type="hidden" name="redirect" value="{{ url()->current() }}">
                <select name="locale" class="form-control form-control-sm" onchange="this.form.submit()" aria-label="Langue">
                    @foreach($supported as $locale)
                        <option value="{{ $locale }}" {{ $currentLocale === $locale ? 'selected' : '' }}>{{ $labels[$locale] ?? strtoupper($locale) }}</option>
                    @endforeach
                </select>
            </form>
        </li>
        <li class="nav-item">
            <a href="{{ route('logout') }}" class="nav-link text-danger"
               onclick="event.preventDefault();document.getElementById('logout-form').submit();">
               Déconnexion
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                {{ csrf_field() }}
            </form>
        </li>
    </ul>
</div>
</nav>

@endif 

@if(Session::has('flash_message'))
<div class="container">      
    <div class="alert alert-success"><em> {!! session('flash_message') !!}</em></div>
</div>
@endif 

@include ('errors.list') {{-- Including error file --}}

@yield('content')

<!-- content-wrapper ends -->
<!-- partial:../../partials/_footer.html -->
@include('layouts.partials.footer')
<!-- partial -->

<!-- Scripts -->
 @vite(['resources/js/app.js'])


<script>
$(document).ready(function(){
 
 $.ajax({
               url: '/last-attendance',
               type: 'GET',
               headers: {
                  
               },
               success: function(response) {
                   if (response.data) {
                       $('#attendance-name').text( response.data.permanence_name);
                       $('#attendance-names').text( response.data.permanence_name);
                   }
               },
               error: function(error) {
                   console.log('Son yoklama alınırken hata oluştu');
               }
           });
          });  
                    
</script>           

</div>
@vite('resources/js/app.js')
@yield('footer')
@yield('scripts')
</body>
</html>
