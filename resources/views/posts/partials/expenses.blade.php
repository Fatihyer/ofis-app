                      
<div class="row"> 
              <div class="col-md-12 grid-margin"> 
            <div class="card border-primary" id="whatever">
               <div class="card-body">                          
              <h3 class="card-title">Dépenses</h3>
                  <a href="{{ route('post.excellist', $post->id) }}" class="btn btn-success" role="button">Excel</a>
            <div class="table-responsive post-table-wrap">
                  <table class="table table-sm table-hover align-middle post-detail-table">
                    <thead>
                    <tr>
                      <th>Service</th>
                      <th>Prestataire</th>
                      <th>Date</th>
                      <th>Pax</th>  
                    
                      <th>Prix de vente</th>
                       <th>Commentaires</th>
                      <th>Paiement</th>
                      <th>Montant</th>
                     <th>Facture</th>
                      <th>#</th>
                      </tr>
                    </thead>
                    
                    @foreach ($post->hareket as $harekets)
                    @if( (isset($harekets->hareketable->servicetype_id))&&($harekets->hareketable->servicetype_id>0))
                             <tr>{{ Form::model($harekets, array('route' => array('harekets.update', $harekets->id), 'method' => 'PUT','class'=>'hareketupdate' )) }}
                              <td>{{$harekets->hareketable->servicetype->name }} </td>
                               <td>
                               @if(isset($harekets->acente->name))
                               <a href="{{ route('acentes.show', $harekets->acente->id ) }}">
                               {{$harekets->acente->name}}

                               </a>
                               @endif
                                 </td>
                               <td>{{date("d-m-Y", strtotime($harekets->tarih)) }}
                              <td>{{$harekets->hareketable->pax}}</td>  
                               
                               <td>
                               {{Form::text('default_price',$harekets->default_price, array())}}
                                
                               </td>
                               <td>{{Form::text('aciklama',$harekets->aciklama, array())}}
                                @php
                                  $paymentsArray = $payments->toArray();
                       
                              @endphp
                               </td><td>{{Form::select('payment_id',['0'=>'sec']+$paymentsArray,$harekets->payment_id,array('class'=>'payment','id'=>$harekets->id))}}
                               <label  style="{{$harekets->payment_id==2?"":"display:none;"}}" class="lcach{{$harekets->id}}"><i class="fas fa-money-bill-alt"></i>
                                 {{Form::text('cash',($harekets->offset_id>0)?abs($harekets->offset->harekets[0]->amount):"" )}}
                                  {{($harekets->offset_id>0)?$harekets->offset->borclu->name:""}} 
                                  </label>
                               </td><td>{{Form::number('amount',abs($harekets->amount), array('step' => '.01'))}}
                               {{Form::select('kur_id',$kurs,$harekets->kur_id, array())}}
                               {{Form::hidden('user',Auth::user()->name )}}
                          </td>
                            <td>{{ Form::text('invoiceno', $harekets->invoiceno) }}</td>
                               <td>
                                 @if(isset($harekets->acente->name)) 
                             <button type="submit"  data-hareket="{{$harekets->id}}" class="btn btn-light guncel" ><i class="fas fa-save" aria-hidden="true"></i></button>
                              <span class="result{{$harekets->id}}"></span>
                              <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadInvoiceModal" data-hareket-id="{{ $harekets->id }}">
                                  <i class="fas fa-paperclip"></i>
                              </button>
                              @foreach($harekets->files as $file)
                                  <a href="{{ asset('storage/'.$file->path) }}" target="_blank" class="text-decoration-none me-1">
                                      <i class="fas fa-paperclip text-success"></i>
                                  </a>
                              @endforeach
                                  @endif

                              </td>
                                  {{Form::close()}}
                              
                           
                    
                    </tr>
                      @if (($harekets->offset_id>0)&&($harekets->tarih!=$harekets->offset->harekets[0]->tarih)) 
                    <tr>
                      <td colspan="12"> <div class="alert alert-danger" role="alert">Les dates du transfert ont changé. Enregistrez pour corriger les dates.</div>
                      
                      </td>
                    </tr>
                    
                    @endif
                     @if (($harekets->offset_id>0)&&($harekets->offset->b_acente_id!=$harekets->acente_id))
                      <tr>
                      <td colspan="12"> <div class="alert alert-danger" role="alert">Le prestataire du transfert a changé. Enregistrez pour corriger la compensation.</div>
                      
                      </td>
                    </tr>
                    
                    @endif
             
                 
                 
                 
                    @endif
                    @if ( (isset($harekets->hareketable->urun_id)) &&($harekets->hareketable->urun_id>0)) 
                  <tr> 
                   <td>{{$harekets->hareketable->urun->name}}</td> 
                      <td> <a href="{{ route('acentes.show', $harekets->acente->id ) }}">
                        {{$harekets->acente->name}}</a></td>
                    <td> {{date("d-m-Y", strtotime($harekets->tarih)) }}</td>
                    <td></td>
                    <td> {{$harekets->adet }}</td>
                    <td> {{$harekets->default_price}}</td>
                    <td> {{$harekets->aciklama }}</td>
                    <td> {{($harekets->hareketable->credit>0)?"Cash":"Credit"}}</td>
                      <td>{{$harekets->amount }}</td>
                      <td>{{$harekets->kur->name }}</td>
                    <td>{{$harekets->invoiceno}}</td>
                  </tr>
                  
                   
                    
                   @endif
                   @if ($harekets->hareketable_type=="App\Models\Other")  
                      <tr> {{ Form::model($harekets, array('route' => array('harekets.update', $harekets->id), 'method' => 'PUT','class'=>'hareketupdate' )) }}
                         <td>Autre service</td>
                        <td> <a href="{{ route('acentes.show', $harekets->acente->id ) }}">{{$harekets->acente->name}}</a></td>
                        <td>{{ date("d-m-Y", strtotime($harekets->tarih)) }}</td>
<td>{{ $harekets->hareketable->pax }}</td>
<td>{{ Form::text('default_price', $harekets->default_price, []) }}</td>
<td>{{ Form::text('aciklama', $harekets->aciklama, []) }}</td>
<td>
    @php
        $paymentsArray = $payments->toArray();
    @endphp
    {{ Form::select('payment_id', ['0' => 'sec'] + $paymentsArray, $harekets->payment_id, ['class' => 'payment', 'id' => $harekets->id]) }}
    <label style="{{ $harekets->payment_id == 2 ? '' : 'display:none;' }}" class="lcach{{ $harekets->id }}">
        <i class="fas fa-money-bill-alt"></i>
        {{ Form::text('cash', ($harekets->offset_id > 0) ? abs($harekets->offset->harekets[0]->amount) : "") }}
        {{ ($harekets->offset_id > 0) ? $harekets->offset->borclu->name : "" }}
    </label>
</td>
<td>
    {{ Form::number('amount', abs($harekets->amount), ['step' => '.01']) }}
    {{ Form::select('kur_id', $kurs, $harekets->kur_id, []) }}
    {{ Form::hidden('user', Auth::user()->name) }}
</td>
<td>{{ Form::text('invoiceno', $harekets->invoiceno) }}</td>
<td>
    @if(isset($harekets->acente->name))
        <button type="submit" data-hareket="{{ $harekets->id }}" class="btn btn-light guncel">
            <i class="fas fa-save" aria-hidden="true"></i>
        </button>
        <span class="result{{ $harekets->id }}"></span>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadInvoiceModal" data-hareket-id="{{ $harekets->id }}">
            <i class="fas fa-paperclip"></i>
        </button>
        @foreach($harekets->files as $file)
            <a href="{{ asset('storage/'.$file->path) }}" target="_blank" class="text-decoration-none me-1">
                <i class="fas fa-paperclip text-success"></i>
            </a>
        @endforeach
    @endif
</td>

                                  {{Form::close()}}
                              
                      </tr>
                   @endif
                 
                    @endforeach
                  </table> 
                </div>
        </div>
                </div>
  </div>
</div>
 