@can('transfers.view')
<a href="{{ url('/ev') }}/?daterange={{ urlencode(session('daterange', '')) }}&start_date={{ urlencode(session('start_date', '')) }}&end_date={{ urlencode(session('end_date', '')) }}&driver={{ urlencode(session('driver', '')) }}&vehicule={{ urlencode(session('vehicule', '')) }}" 
   class="list-group-item list-group-item-action" 
   data-bs-toggle="tooltip" title="Accueil"> 
   <i class="fas fa-home"></i>
</a>
@endcan

@can('transfers.operations')
<a href="{{ route('planning.demain') }}" class="list-group-item list-group-item-action"
   data-bs-toggle="tooltip" title="Planning demain">
   <i class="fas fa-calendar-check"></i>
</a>

<a href="{{ route('table-driverUsage.index') }}" class="list-group-item list-group-item-action" 
   data-bs-toggle="tooltip" title="Utilisation chauffeurs"> 
   <i class="fas fa-user-tie"></i>
</a>
@endcan

@can('vehicules.view')
<a href="{{ route('vehiculescontrol') }}" class="list-group-item list-group-item-action" 
   data-bs-toggle="tooltip" title="Contrôle véhicules"> 
   <i class="fas fa-bus"></i>
</a>

<a href="{{ route('planning.futur') }}" class="list-group-item list-group-item-action"
   data-bs-toggle="tooltip" title="Planning futur véhicules">
   <i class="fas fa-calendar-days"></i>
</a>
@endcan

@can('transfers.operations')
<a href="{{ route('heuredetravail') }}" class="list-group-item list-group-item-action" 
   data-bs-toggle="tooltip" title="Temps de travail"> 
   <i class="fas fa-chart-line"></i>
</a>
<a href="{{ route('driver-vehicle-overnights.index') }}" class="list-group-item list-group-item-action"
   data-bs-toggle="tooltip" title="Découchers">
   <i class="fas fa-bed"></i>
</a>
<a href="{{ url('/sticky-notes') }}" class="list-group-item list-group-item-action"
   data-bs-toggle="tooltip" title="Notes">
   <i class="fas fa-sticky-note"></i>
</a>
@endcan

@role('Superadmin')
<a href="{{ route('ai-bot') }}" class="list-group-item list-group-item-action"
   data-bs-toggle="tooltip" title="AI Bot">
   <i class="fas fa-robot"></i>
</a>
<a href="{{ route('ai-bot.quick-transfer') }}" class="list-group-item list-group-item-action"
   data-bs-toggle="tooltip" title="Création rapide AI">
   <i class="fas fa-bolt"></i>
</a>
@endrole

@can('transfers.operations')
<a href="{{ route('transferstable.index') }}" class="list-group-item list-group-item-action" 
   data-bs-toggle="tooltip" title="Tableau transferts"> 
   <i class="fas fa-route"></i>
</a>

@endcan

