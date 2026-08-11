<div class="row">
    <div class="col-md-12 grid-margin"> 
              <div class="card">
                <div class="card-body">

                      <div class="d-flex flex-wrap align-items-center mb-3" style="gap:8px;">
                          <a class="btn btn-success btn-sm card-title mb-0" href="{{route('createWithTrajets',$post->id)}}" >Ajouter un transfert</a>

                          <button id="exportButton" class="btn btn-primary btn-sm" type="button"><i class="fas fa-file-excel"></i></button>

                          <a class="btn btn-warning btn-sm" href="{{route('addconge',$post->id)}}">Ajouter un congé</a>

                          @can('posts.update')
                              <form method="POST"
                                    action="{{ route('posts.vehicules.updateAll', $post->id) }}"
                                    class="d-flex flex-wrap align-items-center mb-0"
                                    style="gap:6px;"
                                    onsubmit="return confirm('Appliquer ce véhicule à tous les transferts du dossier ?');">
                                  @csrf
                                  @method('PATCH')
                                  <select name="vehicule_id" class="form-control form-control-sm" style="min-width:220px;" required>
                                      <option value="">Véhicule pour aujourd’hui et futur</option>
                                      @foreach($vehicules as $vehiculeId => $vehiculeName)
                                          <option value="{{ $vehiculeId }}">{{ $vehiculeName }}</option>
                                      @endforeach
                                  </select>
                                  <button class="btn btn-outline-dark btn-sm" type="submit">
                                      <i class="fas fa-check"></i> Appliquer
                                  </button>
                                  <small class="text-muted">Les dates passées ne changent pas.</small>
                              </form>
                          @endcan
                      </div>
      <div class="table-responsive post-table-wrap">
      
   
        <table id="transfersTable" class="table table-sm table-hover align-middle post-detail-table">
                      <thead>
                        <tr>
                          <th width="8%">A/R</th>
                          <th width="13%">Début</th>
                          <th width="10%">Fin</th>
                          <th width="23%">Trajet</th>
                          <th width="7%">Véhicule</th>
                          <th width="9%">Chauffeur</th>
                          <th width="5%">Pax</th>
                          <th width="5%"><i class="fas fa-comment-dots"></i></th>
                           <th width="12%">#</th>
                           <th width="3%"><i class="fa fa-trash" aria-hidden="true"></i></th>
                        </tr>
                      </thead>
                      <tbody>
                         @foreach ($post->transfer as $transfert)
                        <tr>
                          <td class="text-{{$transfert->servicetype->color->name}}">
                          @php
                              $missionRow = $transfert->missionr;
                              $missionParts = [];
                              if ($missionRow && $missionRow->hareket) $missionParts[] = 'Départ dépôt: ' . date('H:i', strtotime($missionRow->hareket));
                              if ($missionRow && $missionRow->surplace) $missionParts[] = 'Sur place: ' . date('H:i', strtotime($missionRow->surplace));
                              if ($missionRow && $missionRow->taked) $missionParts[] = 'Client à bord: ' . date('H:i', strtotime($missionRow->taked));
                              if ($missionRow && $missionRow->finish) $missionParts[] = 'Client déposé: ' . date('H:i', strtotime($missionRow->finish));
                              if ($missionRow && $missionRow->finish_depot) $missionParts[] = 'Retour dépôt: ' . date('H:i', strtotime($missionRow->finish_depot));
                              $missionTitle = count($missionParts) ? implode(' / ', $missionParts) : 'Mission non démarrée';
                          @endphp
                          <a href="{{ route('transfers.show', $transfert->id ) }}" title="Voir transfert"><i class="fa fa-eye" aria-hidden="true"></i></a>
                          <a href="{{ route('mission', $transfert->id) }}" target="_blank" rel="noopener" class="btn btn-outline-info btn-sm py-0 px-1 ml-1" title="{{ $missionTitle }}">
                              <i class="fas fa-route"></i> Mission
                          </a>
                            {{$transfert->servicetype->name}}
                              </td>
                          <td class="table-{{(isset($transfert->status->color->name)?$transfert->status->color->name:"")}}">
                            <?php $datestart = \Carbon\Carbon::parse($transfert->start_date);
                                $dateend = \Carbon\Carbon::parse($transfert->end_date);
                                $ofisstart = $transfert->ofis_start ? \Carbon\Carbon::parse($transfert->ofis_start) : NULL;    ?>
                            {{$datestart->format('d-m-Y')}} {{$datestart->format('H:i')}}
                           
                               </td>
                           <td class="table-{{(isset($transfert->status->color->name)?$transfert->status->color->name:"")}}">
                            @if ($transfert->servicetype->hizmet)  {{$dateend->format('d-m-Y')}}@endif {{$dateend->format('H:i')}} </td>
                           <td class="table-{{(isset($transfert->status->color->name)?$transfert->status->color->name:"")}}">{{\Illuminate\Support\Str::limit($transfert->from, 50) }}->{{\Illuminate\Support\Str::limit($transfert->target,50) }}
                            @if ($transfert->trajets->count() == 0)
                            <div class="alert alert-danger">
                                <strong>Attention !</strong> Aucun trajet trouvé. Veuillez ajouter les informations nécessaires.
                            </div>
                        @else
                          
                            <ul class="list-group">
                                @foreach ($transfert->trajets as $index => $trajet)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong class="text-primary">#{{ $index + 1 }}</strong>
                                            <span class="ml-2">{{ $trajet->datetime->format('d-m-Y H:i') }}</span>
                                    
                                            <span class="ml-2 text-muted">[{{ $trajet->type }}]</span>
                                            <span class="ml-2"> {{ $trajet->from }}</span>
                                            @if($trajet->google_address)
                                                <span class="ml-2"><strong>Adresse :</strong> {{ $trajet->google_address }}</span>
                                            @endif
                                             </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @php
                            $routePoints = $transfert->trajets
                                ->sortBy('datetime')
                                ->map(function ($trajet) {
                                    return trim((string) ($trajet->google_address ?: $trajet->from));
                                })
                                ->filter()
                                ->values();

                            $routePayload = null;
                            if ($routePoints->count() >= 2) {
                                $routeWaypoints = $routePoints->slice(1, max($routePoints->count() - 2, 0))->values();
                                $routeMapsUrl = 'https://www.google.com/maps/dir/?api=1'
                                    . '&origin=' . urlencode($routePoints->first())
                                    . '&destination=' . urlencode($routePoints->last());

                                if ($routeWaypoints->count() > 0) {
                                    $routeMapsUrl .= '&waypoints=' . urlencode($routeWaypoints->implode('|'));
                                }

                                $routePayload = [
                                    'id' => (int) $transfert->id,
                                    'title' => 'Transfert #' . $transfert->id,
                                    'origin' => $routePoints->first(),
                                    'destination' => $routePoints->last(),
                                    'waypoints' => $routeWaypoints->all(),
                                    'points' => $routePoints->all(),
                                    'maps_url' => $routeMapsUrl,
                                ];
                            }
                        @endphp
                        <div class="mt-2 d-flex flex-wrap align-items-center gap-2">
                            <span>Total km : {{$transfert->km}}</span>
                            @if($routePayload)
                                <button type="button"
                                        class="btn btn-outline-info btn-sm post-transfer-map-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#postTransferRouteModal"
                                        data-route='@json($routePayload)'>
                                    <i class="fas fa-map-marked-alt"></i> Carte
                                </button>
                            @endif
                        </div>
                        


                           </td>
                           <td>
                            {{$transfert->vehicule?$transfert->vehicule->name:'' }}
                            @if($transfert->vehicle_locked)
                                <div class="mt-1"><span class="badge bg-warning text-dark">Véhicule bloqué</span></div>
                            @endif
                            @if($transfert->vehicle_provider_acente_id || $transfert->external_vehicle_note || $transfert->external_vehicle_price)
                                <div class="small mt-1">
                                    @if($transfert->externalVehicleProvider)
                                        <span class="badge bg-warning text-dark">Extérieur: {{ $transfert->externalVehicleProvider->name }}</span>
                                    @endif
                                    @if($transfert->external_vehicle_price)
                                        <div class="text-muted">Prix fournisseur: {{ number_format((float)$transfert->external_vehicle_price, 2, ',', ' ') }} €</div>
                                    @endif
                                    @if($transfert->external_vehicle_note)
                                        <div class="text-muted">{{ $transfert->external_vehicle_note }}</div>
                                    @endif
                                </div>
                            @endif
                           </td>
                          <td> @if (isset($transfert->driver->name)) {{$transfert->driver->name}} @else <p class="text-danger"><i class="fas fa-exclamation-triangle"></i>Aucun chauffeur</p> @endif</td> 
                           <td>{{$transfert->pax }}</td>
                           <td>@if ($transfert->comments)  <a tabindex="0"
   class="btn btn-sm btn-light"
   data-bs-toggle="popover"
   data-bs-trigger="hover focus"
   data-bs-title="Commentaires"
   data-bs-content="{{ $transfert->comments }}">
   <i class="fas fa-comment-dots"></i>
</a>
                             @else <i class="fal fa-comment"></i> @endif</td>
                           
                           <td>
                             
                
                             @if ($transfert->servicetype->hizmet!=0)
                             <a class="btn btn-primary btn-sm" href="#"  data-bs-toggle="modal" 
                                               data-id="{{$transfert->id}}"
                                               data-servicetype="{{$transfert->servicetype->id}}"

                                               data-date="{{$datestart->format('Y-m-d')}}"
                                               data-dateend="{{$dateend->format('Y-m-d')}}"
                                               
                                               data-time="{{$datestart->format('H:i')}}"
                                               data-endtime="{{$dateend->format('H:i')}}"

                                               data-ofisdate="{{$ofisstart ->format('Y-m-d')}}"
                                               data-ofistime="{{$ofisstart ->format('H:i')}}"

                                               data-from="{{$transfert->from}}"
                                               data-to="{{$transfert->target}}"
                                               data-vehicule="{{$transfert->vehicule?$transfert->vehicule->id:''}}"
                                               data-vehicle-locked="{{$transfert->vehicle_locked ? 1 : 0}}"
                                               data-vehicle-provider="{{$transfert->vehicle_provider_acente_id}}"
                                               data-external-vehicle-price="{{$transfert->external_vehicle_price}}"
                                               data-external-vehicle-note="{{$transfert->external_vehicle_note}}"
                                               data-driver="{{isset($transfert->driver->id)?$transfert->driver->id:""}}" 
                                               data-pax="{{$transfert->pax}}"
                                               data-comments="{{$transfert->comments}}"
                                               data-mission="{{$transfert->mission}}"
                                                
                                               data-firma="{{ optional($transfert->servicetype)->firma_id ?: (isset($transfert->driver->firmas[0])?$transfert->driver->firmas[0]->id:0) }}"
                                               data-status="{{$transfert->status_id}}"
                                               data-bs-target="#editdispo">Modifier la dispo</a>
                      
                             @else 
                             @hasanyrole('Admin|ofis')  
                             <a class="btn btn-primary btn-sm" href="{{route('transfers.edit',$transfert->id)}}" >Modifier le transfert</a>
                             @endhasanyrole
                             <a class="btn btn-primary btn-sm" href="#"  data-bs-toggle="modal" 
                             data-id="{{$transfert->id}}"
                             data-servicetype="{{$transfert->servicetype->id}}"
                             
                             
                             data-vehicule="{{$transfert->vehicule?$transfert->vehicule->id:''}}"
                             data-vehicle-locked="{{$transfert->vehicle_locked ? 1 : 0}}"
                             data-vehicle-provider="{{$transfert->vehicle_provider_acente_id}}"
                             data-external-vehicle-price="{{$transfert->external_vehicle_price}}"
                             data-external-vehicle-note="{{$transfert->external_vehicle_note}}"
                             data-driver="{{isset($transfert->driver->id)?$transfert->driver->id:""}}" 
                             data-pax="{{$transfert->pax}}"
                             data-comments="{{$transfert->comments}}"
                             data-mission="{{$transfert->mission}}"
                             data-firma="{{ optional($transfert->servicetype)->firma_id ?: (isset($transfert->driver->firmas[0])?$transfert->driver->firmas[0]->id:0) }}"
                             data-status="{{$transfert->status_id}}"
                             data-bs-target="#edittransfert">Modifier</a>
                             
                          @endif
                              @if ($transfert->status_id!=3)
                              <a href="{{ route('transfers.clone', $transfert->id, false ) }}" class="btn btn-danger btn-sm">  <i class="far fa-clone"></i>Dupliquer</a>
                              
                              @endif
                             
                          </td>
                          <td>
                           <form action="{{ route('transfers.destroy',$transfert->id) }}" method="POST" class="form-inline">
                                            {{ method_field('DELETE') }}
                                            {{ csrf_field() }}
                                            <button class="btn btn-warning btn-sm"><i class="fas fa-trash-alt"></i></button>
                                        </form></td>
                         </tr>
                      
                        @endforeach
                        </tbody>
                </table> 
         </div>
      </div> </div>
                
                </div>
</div>       
  
