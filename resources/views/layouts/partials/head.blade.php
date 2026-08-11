    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
  
    <title>{{ config('app.name', 'Laravel') }}</title>
@vite(['resources/js/app.js', 'resources/sass/app.scss'])

      
      

        <style>


.via-topbar {
    min-height: 52px;
    padding: 6px 12px;
    position: sticky;
    top: 0;
    z-index: 1030;
}
.via-brand {
    font-weight: 700;
    letter-spacing: .2px;
    padding-right: 10px;
}
.via-topbar .navbar-collapse {
    gap: 8px;
}
.via-main-nav,
.via-status-nav {
    align-items: center;
    gap: 2px;
}
.via-topbar .nav-link,
.via-topbar .list-group-item {
    border-radius: 6px;
    padding: 7px 9px;
    white-space: nowrap;
}
.via-topbar .nav-link:hover,
.via-topbar .list-group-item:hover {
    background: rgba(255,255,255,.08);
}
.via-quick-search input {
    width: 118px;
    max-width: 100%;
    height: 31px;
    border-radius: 6px;
    border: 1px solid rgba(255,255,255,.25);
    padding: 3px 8px;
}
.via-status-nav .profile-text {
    display: inline-block;
    max-width: 92px;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: bottom;
    white-space: nowrap;
}
.via-topbar .dropdown-menu {
    max-height: min(70vh, 520px);
    overflow-y: auto;
}
.sidebar-icon {
    position: sticky;
    top: 52px;
    height: calc(100vh - 52px);
    z-index: 1020;
}
.sidebar-icon a {
    width: 50px;
    min-height: 48px;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    border: 0;
    border-radius: 0;
    color: #334155;
}
.sidebar-icon a:hover,
.sidebar-icon a.active {
    background: #e8eef8;
    color: #0d6efd;
}
.sidebar-icon i {
    font-size: 1.1rem;
}
@media (min-width: 1200px) {
    .via-main-nav,
    .via-status-nav {
        flex-wrap: wrap;
    }
    .via-topbar .nav-link {
        font-size: 13px;
    }
}
@media (max-width: 1199.98px) {
    .via-topbar .navbar-collapse {
        max-height: calc(100vh - 58px);
        overflow-y: auto;
        padding-top: 10px;
    }
    .via-main-nav,
    .via-status-nav {
        align-items: stretch;
        gap: 4px;
    }
    .via-topbar .nav-link,
    .via-topbar .list-group-item {
        padding: 9px 10px;
        white-space: normal;
    }
    .via-quick-search input {
        width: 100%;
    }
    .via-status-nav .profile-text {
        max-width: none;
    }
}
@media (max-width: 767.98px) {
    .via-topbar {
        padding-left: 8px;
        padding-right: 8px;
    }
    .content-area {
        padding: 10px !important;
    }
}

  @page {
    size: A4 landscape;
    size: 287mm 210mm;
  }

.content-area {
    min-width: 0;
    max-width: calc(100vw - 50px);
    overflow-x: hidden;
}
.pagination svg,
nav[role="navigation"] svg {
    width: 1rem;
    height: 1rem;
}
nav[role="navigation"] .w-5 {
    width: 1.25rem !important;
}
nav[role="navigation"] .h-5 {
    height: 1.25rem !important;
}
nav[role="navigation"] .flex-1,
nav[role="navigation"] .sm\:flex-1 {
    min-width: 0;
}
@media (max-width: 767.98px) {
    .content-area {
        max-width: 100vw;
    }
}

</style> 
 
