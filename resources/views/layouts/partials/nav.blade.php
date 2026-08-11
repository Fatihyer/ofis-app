<nav class="navbar navbar-expand-xl navbar-dark bg-dark via-topbar">

  <a class="navbar-brand via-brand" href="{{ url('/') }} ">
    Via
  </a>

  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

 <div class="collapse navbar-collapse" id="navbarNavDropdown">


 <ul class="navbar-nav me-auto via-main-nav">


     <li class="nav-item via-quick-search">
    <button class="btn btn-sm p-0">
        {{ Form::text('git', '', [
            'onchange' => "
                if(!isNaN(this.value) && this.value.trim() !== '') {
                    location='/posts/' + this.value;
                } else {
                    location='/acentes?s=' + encodeURIComponent(this.value);
                }
            "
        ]) }}
    </button>
</li>
      <li class="nav-item"><a href="{{ url('/ev') }}/?daterange={{ urlencode(session('daterange', '')) }}&start_date={{ urlencode(session('start_date', '')) }}&end_date={{ urlencode(session('end_date', '')) }}&driver={{ urlencode(session('driver', '')) }}&vehicule={{ urlencode(session('vehicule', '')) }}" class="nav-link" data-bs-toggle="tooltip" title="Accueil opérations"><i class="fas fa-home"></i></a></li>

      @role('Superadmin')
      <li class="nav-item"><a class="nav-link" href="{{ route('ai-bot') }}" data-bs-toggle="tooltip" title="AI Bot"><i class="fas fa-robot"></i></a></li>
      <li class="nav-item"><a class="nav-link" href="{{ route('ai-bot.quick-transfer') }}" data-bs-toggle="tooltip" title="Création rapide AI"><i class="fas fa-bolt"></i></a></li>
      @endrole

      @if(Auth::user() && (Auth::user()->can('charts.view') || Auth::user()->can('transfers.view') || Auth::user()->can('vehicules.view')))
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="chartDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">@lang('app.chart')</a>
        <div class="dropdown-menu" aria-labelledby="chartDropdown">
          @can('charts.view')
          <a class="dropdown-item" href="{{ route('charts') }}">@lang('app.file')</a>
          @endcan
          @can('transfers.view')
          <a class="dropdown-item" href="{{ route('transfersday') }}">@lang('app.transfers')</a>
          @endcan
          @can('vehicules.view')
          <a class="dropdown-item" href="{{ route('planning.futur') }}">@lang('app.vehicules')</a>
          @endcan
        </div>
      </li>
      @endif
      @can('transfers.view')
      <li class="nav-item"><a class="nav-link" href="{{ route('day') }}?start_date={{ urlencode(session('start_date', '')) }}">@lang('app.navettes')</a></li>
      @endcan
      @can('missions.view')
      <li class="nav-item"><a class="nav-link" href="{{ route('missionlist') }}">@lang('app.missions')</a></li>
      @endcan

      <li class="nav-item"><a class="nav-link" href="{{ route('tasks.index') }}">Tâches</a></li>

      @can('vehicules.view')
      <!-- Vehicule Dropdown -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="vehiculeDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">@lang('app.vehicules')</a>
        <div class="dropdown-menu" aria-labelledby="vehiculeDropdown">
          <a class="dropdown-item" href="{{ route('vehicules.index') }}">@lang('app.vehicules')</a>
          <a class="dropdown-item" href="{{ route('vehicle_maintenance.index') }}">Vehicule Maintenance</a>
          <a class="dropdown-item" href="{{ route('planning.demain') }}">Planning demain</a>
          <a class="dropdown-item" href="{{ route('planning.futur') }}">Planning futur véhicules</a>
          <a class="dropdown-item" href="{{ route('driver-planning.index') }}">Planning chauffeurs</a>
          <a class="dropdown-item" href="{{ route('vehicules.subcontracted') }}">Véhicules sous-traités</a>
          <a class="dropdown-item" href="{{ route('vehiculescontrol') }}">Utilisation véhicule</a>
          <a class="dropdown-item" href="{{ route('vehiculesusage') }}">Utilisation véhicules</a>
          <a class="dropdown-item" href="{{ route('vehiculesusage', ['mode' => 'chart']) }}">Graphique mensuel véhicules</a>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item" href="{{ route('vehicules.controle-docs') }}">Contrôle documents</a>
         </div>
      </li>
      @endcan

      @can('acentes.view')
      <!-- Provider Dropdown -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="providerDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Prestataire</a>
        <div class="dropdown-menu" aria-labelledby="providerDropdown">
          <a class="dropdown-item" href="{{ route('acentes.index') }}">Prestataire</a>
          <a class="dropdown-item" href="{{ route('acentes.create') }}">Ajouter un prestataire</a>
          <a class="dropdown-item" href="{{ route('vehicules.subcontracted') }}">Sous-traitance véhicules</a>
        </div>
      </li>
      @endcan

      <!-- Data Dropdown -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="dataDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">@lang('app.data')</a>
        <div class="dropdown-menu" aria-labelledby="dataDropdown">
          <a class="dropdown-item" href="{{ route('acentes.index') }}">Prestataires @lang('app.list')</a>
          <a class="dropdown-item" href="{{ url('/manuale') }}">@lang('app.manual')</a>
          @can('statuses.manage')
          <a class="dropdown-item" href="{{ route('statuss.index') }}">@lang('app.status')</a>
          @endcan
          <a class="dropdown-item" href="{{ route('firmas.index') }}">@lang('app.providers_type')</a>
          @can('service-types.manage')
          <a class="dropdown-item" href="{{ route('servicetype.index') }}">@lang('app.services_type')</a>
          @endcan
          @can('rates.manage')
          <a class="dropdown-item" href="{{ route('vehicle-price-rules.index') }}">Tarifs véhicules</a>
          <a class="dropdown-item" href="{{ route('depots.index') }}">Dépôts</a>
          @endcan
          @can('rates.manage')
          <a class="dropdown-item" href="{{ route('kurs.index') }}">@lang('app.exchange_name')</a>
          @endcan
          @can('rates.manage')
          <a class="dropdown-item" href="{{ route('dovizs.index') }}">@lang('app.exchange_rates')</a>
          @endcan
          @can('rates.manage')
          <a class="dropdown-item" href="{{ route('kdvs.index') }}">@lang('app.tva')</a>
          @endcan
          @can('accounts.manage')
          <a class="dropdown-item" href="{{ route('accounts.index') }}">@lang('app.bankaccount')</a>
          @endcan
          @can('payments.view')
          <a class="dropdown-item" href="{{ route('payments.index') }}">@lang('app.payment_type')</a>
          @endcan
          @can('options.view')
          <a class="dropdown-item" href="{{ route('options.index') }}">@lang('app.options')</a>
          @endcan
          @can('posts.stats')
          <a class="dropdown-item" href="{{ route('posts.userFileStats') }}">Statistiques dossiers</a>
          @endcan
          <a class="dropdown-item" href="{{ route('sirkets.index') }}">Société</a>
          <a class="dropdown-item" href="{{ route('listexcel') }}">Import Garanti Bank</a>
          <a class="dropdown-item" href="{{ route('bank.import.form') }}">Import bancaire</a>
          <a class="dropdown-item" href="{{ route('google-ads.index') }}">Google Ads</a>
          <a class="dropdown-item" href="{{ route('stripe.payments.index') }}">Paiements Stripe</a>
        </div>
      </li>

      <!-- List Dropdown -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="listDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">@lang('app.list')</a>
        <div class="dropdown-menu" aria-labelledby="listDropdown">
          @can('invoices.view')
          <a class="dropdown-item" href="{{ route('invoices.index') }}">@lang('app.invoice')</a>
          @endcan
          @can('invoices.view')
          <a class="dropdown-item" href="{{ route('groupinvoices.index') }}">Factures groupées</a>
          <a class="dropdown-item" href="{{ route('invoices.pennylane.compare') }}">Contrôle Pennylane</a>
          <a class="dropdown-item" href="{{ route('invoices.provider-pennylane.compare') }}">Contrôle fournisseurs Pennylane</a>
          @endcan
          @can('posts.view')
          <a class="dropdown-item" href="{{ route('posts.index') }}">@lang('app.filelist')</a>
          @endcan
          @if(auth()->user()?->hasAnyRole(['Superadmin', 'Comptabilité', 'Compta']))
            <a class="dropdown-item" href="{{ route('posts.uninvoiced') }}">Dossiers sans facture</a>
          @endif
          @can('balances.view')
          <a class="dropdown-item" href="{{ route('balancefile') }}">Solde dossier</a>
          @endcan
          @can('balances.view')
          <a class="dropdown-item" href="{{ route('balanceprovider') }}">Balance Prestataire</a>
          <a class="dropdown-item" href="{{ route('vehicules.subcontracted') }}">Véhicules sous-traités</a>
          @endcan
          <a class="dropdown-item" href="{{ route('acentes.index') }}">@lang('app.providers')</a>
          @can('transfers.view')
          <a class="dropdown-item" href="{{ route('transfers.index') }}">Liste transferts</a>
          @endcan
          <a class="dropdown-item" href="{{ route('offsets.index') }}">@lang('app.offsets')</a>
          <a class="dropdown-item" href="{{ route('stocks.index') }}">@lang('app.stocks')</a>
          @can('attendance.manage')
          <a class="dropdown-item" href="{{ route('userattendances') }}">Permanence horaires</a>
          @endcan
          @can('documents.manage')
          <a class="dropdown-item" href="{{ route('fichier.index') }}"><i class="fa fa-file"></i> Documents</a>
          @endcan
          @can('transfers.operations')
          <a class="dropdown-item" href="{{ route('mismatchTransfers') }}">Transferts incohérents</a>
          @endcan
        </div>
      </li>

      @can('fuel.manage')
      <!-- Fuel Dropdown -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="fuelDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Carburant</a>
        <div class="dropdown-menu" aria-labelledby="fuelDropdown">
          <a class="dropdown-item" href="{{ route('fuel.list') }}">Liste carburant</a>
          <a class="dropdown-item" href="{{ route('fuel.index') }}">Carburant</a>
          <a class="dropdown-item" href="{{ route('fuel.chart') }}">Graphique carburant</a>
          <a class="dropdown-item" href="{{ route('fuel.import.form') }}">Import carburant Excel</a>
        </div>
      </li>
      @endcan

      @can('talep.view')
       <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="demandeDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Demandes</a>
        <div class="dropdown-menu" aria-labelledby="demandeDropdown">
          <a class="dropdown-item" href="{{route('talepler.index') }}">Paris Via</a>
          <a class="dropdown-item" href="{{ route('cansu.index') }}">Internet</a>
   
        </div>
      </li>
      @endcan
      
      @can('hermes.view')
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="HermesDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Hermes</a>
        <div class="dropdown-menu" aria-labelledby="HermesDropdown">
          <a class="dropdown-item" href="{{ route('hermes.index') }}">Hermes véhicules</a>
          <a class="dropdown-item" href="{{ route('hermes.resources') }}">Hermes chauffeurs</a>
        <a class="dropdown-item" href="{{ route('hermes.fleet.day') }}">Flotte du jour</a>
        <a class="dropdown-item" href="{{ route('hermes.fleet.yesterday') }}">Flotte hier</a>
        <a  class="dropdown-item" href="{{ route('hermes.fleet.db') }}">Base flotte véhicules</a>
     
        <a  class="dropdown-item" href="{{ route('hermes.driver_vehicle_daily') }}">Chauffeur-véhicule journalier</a>
        <a class="dropdown-item" href="{{ route('hermes.driver_working_daily') }}">Temps de travail chauffeur</a>
        <a class="dropdown-item" href="{{ route('tachograph.index') }}">Tachograph</a>
        @can('whatsapp-leads.view')
        <a class="dropdown-item" href="{{ route('whatsapp-group-leads.index') }}">WhatsApp iş talepleri</a>
        @endcan
        @can('whatsapp-groups.view')
        <a class="dropdown-item" href="{{ route('whatsapp-groups.index') }}">WhatsApp groupes</a>
        @endcan

       
       
                <a class="dropdown-item" href="{{ route('hermes.drivers.working.daily') }}">Chauffeurs — Journalier</a>
                <a class="dropdown-item" href="{{ route('hermes.drivers.working.weekly') }}">Chauffeurs — Hebdo</a>
                <a class="dropdown-item" href="{{ route('hermes.drivers.working.monthly') }}">Chauffeurs — Mensuel</a>
     

        </div>
      </li>
      @endcan
      
    </ul>

    <!-- Sağdaki özel alanlar (Paris Gezgini, Operation, Attendance) -->
<ul class="navbar-nav ms-auto via-status-nav">


      <!-- Notifications -->
      <li class="nav-item dropdown" onclick="markNotificationAsRead()">
        <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i class="fas fa-exclamation-triangle"></i>
          <span class="badge badge-danger">{{ count(auth()->user()->unreadNotifications) }}</span>
        </a>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="notificationDropdown">
          @foreach (auth()->user()->unreadNotifications as $notification)
            @php
              $notificationData = $notification->data ?? [];
              $transferId = data_get($notificationData, 'transfer.id') ?: data_get($notificationData, 'transfer_id');
              $transferDate = data_get($notificationData, 'transfer.start_date') ?: data_get($notificationData, 'start_date');
              $notificationUrl = data_get($notificationData, 'url');

              if (!$notificationUrl && $transferId) {
                  $notificationUrl = route('transfers.show', $transferId);
              }

              $notificationText = data_get($notificationData, 'message')
                  ?: data_get($notificationData, 'title')
                  ?: (data_get($notificationData, 'user')
                      ? data_get($notificationData, 'user') . ' a confirmé le transfert'
                      : 'Nouvelle notification');

              if ($transferId && $transferDate && !data_get($notificationData, 'message')) {
                  $notificationText .= ' du ' . $transferDate . ' (ID : ' . $transferId . ')';
              } elseif ($transferId && !str_contains($notificationText, (string) $transferId)) {
                  $notificationText .= ' (transfert #' . $transferId . ')';
              }
            @endphp
            <a class="dropdown-item" href="{{ $notificationUrl ?: '#' }}">
              {{ $notificationText }}
            </a>
          @endforeach
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item text-center"
                onclick="event.preventDefault(); deleteNotification();">
                Tout supprimer
              </a>
        </div>
      </li>

      <!-- Paris Gezgini -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="parisgezginiDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <span class="text-danger">Turquie :</span> <span class="profile-text" id="parisgezgini-name"></span>
        </a>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="parisgezginiDropdown">
          <form action="{{ route('parisgezgini') }}" method="POST">@csrf
            <button class="dropdown-item btn btn-secondary">Je prends</button>
          </form>
        </div>
      </li>

      <!-- Operation -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="operationDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <span class="text-danger">Transport :</span> <span class="profile-text" id="operation-name"></span>
        </a>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="operationDropdown">
          <form action="{{ route('operation') }}" method="POST">@csrf
            <button class="dropdown-item btn btn-secondary">Operation Je prends</button>
          </form>
        </div>
      </li>

      <!-- Attendance -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="attendanceDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <span class="text-danger">Demande :</span> <span class="profile-text" id="attendance-name"></span>
        </a>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="attendanceDropdown">
          <form action="{{ route('permanence') }}" method="POST">@csrf
            <button class="dropdown-item btn btn-danger">Demande Je prends</button>
          </form>
          <a class="dropdown-item" href="https://chat.whatsapp.com/IycDQzbJIK0BkdN5eSqIGt" target="_blank">
            <i class="fab fa-whatsapp"></i> Groupe permanence
          </a>
          @if(auth()->user()?->hasAnyRole(['Superadmin', 'Admin', 'ofis']))
          <a class="dropdown-item" href="{{ route('users.online') }}">
            <i class="fas fa-user-clock"></i> Utilisateurs en ligne
          </a>
          <a class="dropdown-item" href="{{ route('users.index') }}">
            <i class="fas fa-users-cog"></i> Utilisateurs / chauffeurs
          </a>
          @endif
          @role('Superadmin')
          <a class="dropdown-item" href="{{ route('roles.index') }}">Rôles</a>
          <a class="dropdown-item" href="{{ route('permissions.index') }}">Permissions</a>
          <a class="dropdown-item" href="{{ route('notification-groups.index') }}">Groupes notifications</a>
          @endrole
          <a class="dropdown-item" href="#">Changer le mot de passe</a>
          <a class="dropdown-item" href="#">Boîte de réception</a>
          <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();">Déconnexion</a>
          <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
        </div>
      </li>

      <li class="nav-item">
  <form class="inline-block">
    @csrf {{-- _token için --}}
    @php
        $supported = config('app.supported_locales', ['tr','en','fr']);
        $labels = ['tr'=>'Türkçe','en'=>'English','fr'=>'Français'];
        $current = app()->getLocale();
    @endphp

    <select id="languageswicher" name="locale" class="form-control form-control-sm">
        @foreach($supported as $loc)
            <option value="{{ $loc }}" @if($current === $loc) selected @endif>
                {{ $labels[$loc] ?? strtoupper($loc) }}
            </option>
        @endforeach
    </select>
  </form>
</li>

    </ul>
  </div>
</nav>
