<!-- Modal add transfert-->
<div class="modal fade" id="addtransfert" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
    <div class="modal-dialog " role="document">
       
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLongTitle">Add Transferts</h5>
          
          
         
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>{{ Form::open(array('route' => 'transfers.store', 'id' => 'transferForm')) }}
        <div class="modal-body">
                <div class="form-row">
                  <div class="form-group col-md-8">
                    {{ Form::label('servicetype_id', 'Service Types') }}
                    {{Form::select('servicetype_id',$servicetypes['transfer'],"", array('class' => 'form-control')) }}
                  </div> 
                  <div class="form-group col-md-4">
                    <a class="" href="{{ route('servicetype.index') }}">+ Service Type</a>
                  </div> 
                </div>
                   <div class="form-row">
                  <div class="form-group col-md-6">
                   {{ Form::label('start_date', 'Date') }}
                   {{ Form::date('start_date', date("Y-m-d", strtotime($post->start_date)), array('class' => 'form-control')) }}
                  </div>
                  <div class="form-group col-md-3">
                    {{ Form::label('start_date', 'Start Time') }}
                    {{ Form::time('start_time',"", array('class' => 'form-control', 'id' => 'start_time')) }}
                  </div>
                    
  
                  <div class="form-group col-md-3">
                    {{ Form::label('start_date', 'Finish Time') }}
                    {{ Form::time('end_time',"", array('class' => 'form-control','id' => 'end_time')) }}
                  </div>
                     </div>
                  <div class="form-row">
                        <div class="form-group col-md-6">
                          {{ Form::label('pax', 'Pax') }}
                          {{ Form::input('number','pax',$post->pax, array('class' => 'form-control', 'required'=>'required')) }}
                        </div>
                        <div class="form-group col-md-6">
                          {{ Form::label('vehicule_id', 'Vehicule')}}
                          {{ Form::select('vehicule_id',$vehicules,'', array('class' => 'form-control')) }}
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-12 border rounded p-3 mt-3 mb-2 external-vehicle-panel">
                        <button class="btn btn-sm btn-outline-warning w-100 text-left mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#externalVehicleCollapsecreate_blade1" aria-expanded="false" aria-controls="externalVehicleCollapsecreate_blade1">
                          <i class="fas fa-truck-loading"></i> Véhicule extérieur / location
                        </button>
                        <div id="externalVehicleCollapsecreate_blade1" class="collapse ">
                          <div class="form-row mt-2">
                            <div class="form-group col-md-12">
                              {{ Form::label('vehicle_provider_acente_id', 'Fournisseur véhicule') }}
                              {{ Form::select('vehicle_provider_acente_id', ['' => 'Aucun fournisseur véhicule'] + ($acentes ?? \App\Models\Acente::orderBy('name')->pluck('name', 'id'))->toArray(), '', ['class' => 'form-control']) }}
                            </div>
                            <div class="form-group col-md-4">
                              {{ Form::label('external_vehicle_price', 'Prix fournisseur') }}
                              {{ Form::number('external_vehicle_price', '', ['class' => 'form-control', 'step' => '0.01', 'min' => '0']) }}
                            </div>
                            <div class="form-group col-md-8">
                              {{ Form::label('external_vehicle_note', 'Note / plaque extérieure') }}
                              {{ Form::text('external_vehicle_note', '', ['class' => 'form-control', 'placeholder' => 'Plaque, modèle, référence fournisseur...']) }}
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
              <div class="form-row"> 
                            <div class="form-group col-md-6">
                           {{Form::label('pax', 'Provider Type') }}
                           @php
                           $firmasArray = $firmas->toArray();
                       @endphp
                           {{Form::select('provider_id', ['0'=>'Sec']+$firmasArray,'', array('class' => 'form-control')) }}
                        </div>
                           <div class="form-group col-md-6">
                           {{Form::label('pax', 'Provider Name') }}
                           {{Form::select('driver_id',['0'=>'Type Sec'],'', array('class' => 'form-control')) }}
                        </div>
                        <div class="form-group col-md-12">
                          {{ Form::label('mission', 'Suivi Mission') }}
                          {{ Form::checkbox('mission',"1", array('class' => 'form-control')) }}
                                  </div>
                       
                             <div class="form-group col-md-12">
                       {{ Form::label('from', 'Fromdee') }}
                       {{ Form::text('from', "", ['class' => 'form-control', 'id' => 'from']) }}
                              </div>
                     
                    
                          <div class="form-group col-md-12">
                    {{ Form::label('target', 'To') }}
                    {{ Form::text('target', "", ['class' => 'form-control', 'id' => 'target']) }}
                            </div>
              
                 <div class="form-group col-md-12">
                    {{ Form::label('comments', 'Comments') }}
                    {{ Form::textarea('comments',"", array('class' => 'form-control')) }}
                </div>
            
          
          
         
               </div>
        </div>        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
             {{Form::hidden('hizmet',1)}}
          {{Form::hidden('post_id',$post->id)}}
          {{Form::submit('Add Transfert',array('class'=>'btn btn-primary'))}}
         
        </div>
         {{form::close()}}
      </div>
      
    </div>
  </div>
  
  
 

<!-- Modal add disposal-->
<div class="modal fade" id="adddisposal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
    <div class="modal-dialog " role="document">
       
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLongTitle">Add Dispo</h5>
          
          
         
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        {{ Form::open(array('route' => 'transfers.guncel')) }}
          <div class="modal-body">
            <div class="form-row">
              <div class="form-group col-md-8">
                {{ Form::label('servicetype_id', 'Service Types') }}
                {{ Form::select('servicetype_id', $servicetypes['dispo'], "", ['class' => 'form-control', 'id' => 'data-servicetype']) }}
              </div> 
              <div class="form-group col-md-4">
                <a class="" href="{{ route('servicetype.index') }}">+ Service Type</a>
              </div> 
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                {{ Form::label('data-date', 'Date') }}
                {{ Form::date('start_date', "", ['class' => 'form-control', 'id' => 'data-date']) }}
              </div>
              <div class="form-group col-md-6">
                {{ Form::label('start_date', 'Start Time') }}
                {{ Form::time('start_time', "", ['class' => 'form-control', 'id' => 'data-time']) }}
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                {{ Form::label('start_date', 'Date') }}
                {{ Form::date('end_date', "", ['class' => 'form-control', 'id' => 'data-dateend']) }}
              </div>
              <div class="form-group col-md-3">
                {{ Form::label('start_date', 'Finish Time') }}
                {{ Form::time('end_time', "", ['class' => 'form-control', 'id' => 'data-endtime']) }}
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                {{ Form::label('pax', 'Pax') }}
                {{ Form::input('number', 'pax', $post->pax, ['class' => 'form-control', 'id' => 'data-pax']) }}
              </div>
              <div class="form-group col-md-6">
                {{ Form::label('vehicule_id', 'Vehicule') }}
                {{ Form::select('vehicule_id', $vehicules, '', ['class' => 'form-control', 'id' => 'data-vehicule']) }}
              </div>
            </div>
            <div class="form-row">
                      <div class="form-group col-md-12 border rounded p-3 mt-3 mb-2 external-vehicle-panel">
                        <button class="btn btn-sm btn-outline-warning w-100 text-left mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#externalVehicleCollapsecreate_blade2" aria-expanded="false" aria-controls="externalVehicleCollapsecreate_blade2">
                          <i class="fas fa-truck-loading"></i> Véhicule extérieur / location
                        </button>
                        <div id="externalVehicleCollapsecreate_blade2" class="collapse ">
                        <div class="form-row mt-2">
                          <div class="form-group col-md-12">
                            {{ Form::label('data-vehicle-provider', 'Fournisseur véhicule') }}
                            {{ Form::select('vehicle_provider_acente_id', ['' => 'Aucun fournisseur véhicule'] + ($acentes ?? \App\Models\Acente::orderBy('name')->pluck('name', 'id'))->toArray(), '', ['class' => 'form-control', 'id' => 'data-vehicle-provider']) }}
                          </div>
                          <div class="form-group col-md-4">
                            {{ Form::label('data-external-vehicle-price', 'Prix fournisseur') }}
                            {{ Form::number('external_vehicle_price', '', ['class' => 'form-control', 'id' => 'data-external-vehicle-price', 'step' => '0.01', 'min' => '0']) }}
                          </div>
                          <div class="form-group col-md-8">
                            {{ Form::label('data-external-vehicle-note', 'Note / plaque extérieure') }}
                            {{ Form::text('external_vehicle_note', '', ['class' => 'form-control', 'id' => 'data-external-vehicle-note', 'placeholder' => 'Plaque, modèle, référence fournisseur...']) }}
                          </div>
                        </div>
                      </div>
                      </div>
            </div>
            <div class="form-row"> 
              <div class="form-group col-md-6">
                {{ Form::label('data-firma', 'Provider Type') }} 
                {{ Form::select('provider_id', ['0' => 'Sec'] + $firmasArray, '', ['class' => 'form-control', 'id' => 'data-firma']) }}     
              </div>
              <div class="form-group col-md-6">
                {{ Form::label('data-driver', 'Provider Name') }}
                {{ Form::select('driver_id', ['0' => 'Type Sec'], '', ['class' => 'form-control', 'id' => 'data-driver']) }}
              </div>
              <div class="form-group col-md-12">
                {{ Form::label('mission', 'Suivi Mission') }} 
                {{ Form::checkbox('mission', 1, false, ['class' => 'form-control', 'id' => 'data-mission']) }}
              </div>
              <div class="form-group col-md-12">
                {{ Form::label('from', 'From') }}
                {{ Form::text('from', "", ['class' => 'form-control', 'id' => 'data-from']) }}
              </div>
              <div class="form-group col-md-12">
                {{ Form::label('target', 'To') }}
                {{ Form::text('target', "", ['class' => 'form-control', 'id' => 'data-to']) }}
              </div>
              <div class="form-group col-md-12">
                {{ Form::label('comments', 'Comments') }}
                {{ Form::textarea('comments', "", ['class' => 'form-control', 'id' => 'data-comments']) }}
              </div>
              {{ Form::hidden('id', "", ['id' => 'data-id']) }}
            </div>
          </div>        
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            {{ Form::hidden('post_id', $post->id) }}
            {{ Form::submit('Edit Service', ['class' => 'btn btn-primary']) }}
          </div>
        {{ Form::close() }}
      </div>
      
    </div>
  </div> 