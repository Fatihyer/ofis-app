<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
  
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Styles -->
  
 <link rel="stylesheet" href="{{ asset('vendors/iconfonts/mdi/css/materialdesignicons.min.css')}}">
  <link rel="stylesheet" href="{{ asset('vendors/iconfonts/font-awesome/css/font-awesome.min.css')}}">
  <link rel="stylesheet" href="{{ asset('vendors/css/vendor.bundle.base.css')}}">
  <link rel="stylesheet" href="{{ asset('vendors/css/vendor.bundle.addons.css')}}">
  
  <link href="{{ asset('css/apps.css') }}" rel="stylesheet">
  <link href="{{ asset('css/style.css') }}" rel="stylesheet">
<style>
  
  @page {
    size: A4 landscape;
    size: 287mm 210mm;
  </style>   
  @yield('style') 
</head>
<body>
 <div class="container-scroller hidden-print">
   @if (Auth::guest())
   @else
    <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
        <div class="text-center navbar-brand-wrapper d-flex align-items-top justify-content-center">
            <a class="navbar-brand brand-logo" href="{{ url('/') }}">
              <img src="{{asset('images/'.env('LOGO'))}}" alt="logo" />
            </a>
              <a class="navbar-brand brand-logo-mini" href="{{ url('/') }}">
          <img src="{{asset('images/'.env('LOGOMINI'))}}" alt="logo" />
                </a>
            </div>
      <div class="navbar-menu-wrapper d-flex align-items-center">
        <ul class="navbar-nav navbar-nav-left header-links d-none d-md-flex">
          <li class="nav-item">
            <a href="{{ route('charts') }}" class="nav-link">@lang('app.chart')
              <span class="badge badge-primary ml-1">New</span>
            </a>
          </li> 
          <li class="nav-item active">
            <a href="{{ route('transfers') }}" class="nav-link">
              <i class="mdi mdi-elevation-rise"></i>@lang('app.transfers')</a>
          </li>
          <li class="nav-item">
            <a href="{{ route('day') }}" class="nav-link">
              <i class="mdi mdi-bookmark-plus-outline"></i>Shuttle</a>
          </li>
        </ul>
        <ul class="navbar-nav navbar-nav-right">
          <li class="nav-item dropdown">
            <a class="nav-link count-indicator dropdown-toggle" id="messageDropdown" href="/messages" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="mdi mdi-file-document-box"></i>
              <span class="count">@include('messenger.unread-count')</span>
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="messageDropdown">
              <div class="dropdown-item">
                <p class="mb-0 font-weight-normal float-left">You have @include('messenger.unread-count') unread mails
                </p>
                <span class="float-right"><a class="badge badge-primary" href="/messages">View all</a></span>
              </div>
              </a>
            </div>
          </li>
          
          <li class="nav-item dropdown d-none d-xl-inline-block">
            <a class="nav-link dropdown-toggle" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
              <span class="profile-text">Hello,  {{ Auth::user()->name }} !</span>
              <img class="img-xs rounded-circle" src="{{asset('images/faces/face1.jpg')}}" alt="Profile image">
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
              <a class="dropdown-item p-0">
                <div class="d-flex border-bottom">
                  <div class="py-3 px-4 d-flex align-items-center justify-content-center">
                    <i class="mdi mdi-bookmark-plus-outline mr-0 text-gray"></i>
                  </div>
                  <div class="py-3 px-4 d-flex align-items-center justify-content-center border-left border-right">
                    <i class="mdi mdi-account-outline mr-0 text-gray"></i>
                  </div>
                  <div class="py-3 px-4 d-flex align-items-center justify-content-center">
                    <i class="mdi mdi-alarm-check mr-0 text-gray"></i>
                  </div>
                </div>
              </a>
              <a href="{{route('permissions.index')}}" class="dropdown-item mt-2">
                Manage Accounts
              </a>
              <a href="/Messages" class="dropdown-item">
                Change Password
              </a>
              <a href="/messages" class="dropdown-item">
                Check Inbox
              </a>
             
               <a href="{{ route('logout') }}" class="dropdown-item"
                                   onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                                 Sign Out
                                </a>
               <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                      style="display: none;">
                                    {{ csrf_field() }}
                                </form>
            </div>
          </li>
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-bs-toggle="offcanvas">
          <span class="mdi mdi-menu"></span>
        </button>
      </div>
           
    </nav>
   
   <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:../../partials/_sidebar.html -->
      <nav class="sidebar sidebar-offcanvas d-print-none" id="sidebar">
        <ul class="nav">
          <li class="nav-item nav-profile">
            <div class="nav-link">
              <div class="user-wrapper">
                <div class="profile-image">
                  <img src="{{asset('images/faces/face1.jpg')}}" alt="profile image">
                </div>
                <div class="text-wrapper">
                  <p class="profile-name">{{ Auth::user()->name }}</p>
                  <div>
                    <small class="designation text-muted">M</small>
                    <span class="status-indicator online"></span>
                  </div>
                </div>
              </div> <a class="btn btn-success btn-block" href="{{ route('posts.create') }}">@lang('app.new_file') <i class="mdi mdi-plus"></i>
              
              </a
               </div>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="{{ url('/') }}">
              <i class="menu-icon mdi mdi-television"></i>
              <span class="menu-title">Dashboard</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#ui-basic" aria-expanded="false" aria-controls="ui-basic">
              <i class="menu-icon mdi mdi-content-copy"></i>
              <span class="menu-title"> @lang('app.providers')</span>
              <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="ui-basic">
              <ul class="nav flex-column sub-menu">
                <li class="nav-item">
                  <a class="nav-link" href="{{ route('acentes.index') }}">@lang('app.list')</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="{{ route('acentes.create') }}">@lang('app.new_providers')</a>
                </li>
              </ul>
            </div>
          </li>
           <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#ui-file" aria-expanded="false" aria-controls="ui-file">
              <i class="menu-icon mdi mdi-file-document"></i>
              <span class="menu-title"> @lang('app.file')</span>
              <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="ui-file">
              <ul class="nav flex-column sub-menu">
                <li class="nav-item">
                  <a class="nav-link" href="{{ route('posts.index') }}">@lang('app.filelist')</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="{{ route('posts.create') }}">@lang('app.new_file')</a>
                </li>
              </ul>
            </div>
          </li>
           <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#ui-data" aria-expanded="false" aria-controls="ui-data">
              <i class="menu-icon mdi mdi-database"></i>
              <span class="menu-title"> @lang('app.data')</span>
              <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="ui-data">
              <ul class="nav flex-column sub-menu">
                <li class="nav-item">
                  <a class="nav-link" href="{{ url('/manuale') }}">@lang('app.manual')</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="{{ route('statuss.index') }}">@lang('app.status')</a>
                </li>
                 <li class="nav-item">
                  <a class="nav-link" href="{{ route('firmas.index') }}">@lang('app.providers_type')</a>
                </li>
                 <li class="nav-item">
                  <a class="nav-link" href="{{ route('vehicules.index') }}">@lang('app.vehicules_type')</a>
                </li>
                 <li class="nav-item">
                  <a class="nav-link" href="{{ route('servicetype.index') }}">@lang('app.services_type')</a>
                </li>
                 <li class="nav-item">
                  <a class="nav-link" href="{{ route('kurs.index') }}">@lang('app.exchange_name')</a>
                </li>
                 <li class="nav-item">
                  <a class="nav-link" href="{{ route('kdvs.index') }}">@lang('app.tva')</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="{{ route('accounts.index') }}">@lang('app.bankaccount')</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="{{ route('payments.index') }}">@lang('app.payment_type')</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="{{ route('options.index') }}">@lang('app.options')</a>
                </li>
                
              </ul>
            </div>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#ui-list" aria-expanded="false" aria-controls="ui-list">
              <i class="menu-icon mdi mdi-format-line-spacing"></i>
              <span class="menu-title"> @lang('app.list')</span>
              <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="ui-list">
              <ul class="nav flex-column sub-menu">
                <li class="nav-item">
                  <a class="nav-link" href="{{ route('invoices.index') }}">@lang('app.invoice')</a>
                </li>
                 <li class="nav-item">
                  <a class="nav-link" href="{{ route('groupinvoices.index') }}">Group Invoice</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="{{ route('posts.index') }}">@lang('app.filelist')</a>
                </li>
                 <li class="nav-item">
                  <a class="nav-link" href="{{ route('balancefile') }}">Balance File</a>
                </li>
                  <li class="nav-item">
                  <a class="nav-link" href="{{ route('balanceprovider') }}">Balance Provider</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="{{ route('acentes.index') }}">@lang('app.providers') list</a>
                </li>
              </ul>
            </div>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="{{ route('offsets.index') }}">
              <i class="menu-icon mdi mdi-backup-restore"></i>
              <span class="menu-title">@lang('app.offsets')</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="{{ route('charts') }}">
              <i class="menu-icon mdi mdi-chart-line"></i>
              <span class="menu-title">Charts</span>
            </a>
          </li>
            <li class="nav-item">
            <a class="nav-link" href="{{ route('transfers') }}">
              <i class="menu-icon mdi mdi-chart-line"></i>
              <span class="menu-title">Transfers</span>
            </a>
          </li> 
              <li class="nav-item">
            <a class="nav-link" href="{{ route('day') }}">
              <i class="menu-icon mdi mdi-chart-line"></i>
              <span class="menu-title">Shuttle</span>
            </a>
          </li> 
            <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#ui-product" aria-expanded="false" aria-controls="ui-product">
              <i class="menu-icon mdi mdi-format-line-spacing"></i>
              <span class="menu-title"> @lang('app.products')</span>
              <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="ui-product">
              <ul class="nav flex-column sub-menu">
                <li class="nav-item">
                  <a class="nav-link" href="{{ route('uruns.index') }}">@lang('app.products')</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="{{ route('categories.index') }}">@lang('app.categories')</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="{{ route('stocks.index') }}">@lang('app.stocks')</a>
                </li>
                
              </ul>
            </div>
          </li>
                <li class="nav-item">
            <a class="nav-link" href="{{ route('fichier.index') }}">
              <i class="menu-icon mdi mdi-chart-line"></i>
              <span class="menu-title">Documents</span>
            </a>
          </li> 
           
          <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#auth" aria-expanded="false" aria-controls="auth">
              <i class="menu-icon mdi mdi-restart"></i>
              <span class="menu-title">User Pages</span>
              <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="auth">
              <ul class="nav flex-column sub-menu">
                <li class="nav-item">
                 <select id="languageswicher" class="form-control">
                          <option value="en" {{(\App::getLocale()=="en")?"selected='selected'":""}}>English</option>
                       <option value="tr" {{(\App::getLocale()=="tr")?"selected='selected'":""}}>Türkçe</option>
                       </select>
                </li>
                
              </ul>
            </div>
          </li>
        </ul>
      </nav>
           <div class="main-panel">
        <div class="content-wrapper">
          @endif 
                        @if(Session::has('flash_message'))
                       <div class="container">      
                       <div class="alert alert-success"><em> {!! session('flash_message') !!}</em>
                       </div>
                       </div>
                         @endif 
                          @include ('errors.list') {{-- Including error file --}}
         
            @yield('content')
        </div>
        <!-- content-wrapper ends -->
        <!-- partial:../../partials/_footer.html -->
        <footer class="footer">
          <div class="container-fluid clearfix">
            <span class="text-muted d-block text-center text-sm-left d-sm-inline-block">Copyright © 2018
              <a href="http://www.bootstrapdash.com/" target="_blank">France Panoramic</a>. All rights reserved.</span>
            <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">Hand-crafted & made with
              <i class="mdi mdi-heart text-danger"></i>
            </span>
          </div>
        </footer>
        <!-- partial -->
      </div>
   </div>
   
        


 
</div>
  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>

 <script>
function yazdir() {
    window.print();
}
$(document).ready(function(){
  $("#languageswicher").change(function(){
  var locale =$(this).val();
  var _token=$("input[name=_token]").val();
        $.ajax({
          url:"/language",
          type:"POST",
          data:{locale: locale, _token: _token},
          datatype:'json',
             success: function (data){},
             error: function (data){},     
             beforeSend: function (data){},     
             complete: function (data){
                window.location.reload(true);
             },     
      });
    });
});   
</script>
  
    <!-- container-scroller -->
  <!-- plugins:js -->
  <script src="{{ asset('vendors/js/vendor.bundle.base.js')}}"></script>
  <script src="{{ asset('vendors/js/vendor.bundle.addons.js')}}"></script>
  <!-- endinject -->
  <!-- Plugin js for this page-->
  <!-- End plugin js for this page-->
  <!-- inject:js -->
  <script src="{{ asset('js/off-canvas.js')}}"></script>
  <script src="{{ asset('js/misc.js')}}"></script>
  <!-- endinject -->
  <!-- Custom js for this page-->
  <!-- End custom js for this page-->
  <!--<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
-->

    @yield('footer')
</body>
</html>
