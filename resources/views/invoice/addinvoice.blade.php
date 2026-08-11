<!-- Modal add transfert-->
<div class="modal fade" id="addinvoice" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">Ajouter une facture</h5>
        
        
       
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer">
          <span aria-hidden="true">&times;</span>
        </button>
              </div>{{ Form::open(array('route' => 'invoices.store')) }}
      <div class="modal-body">
              <div class="form-row">
                <div class="form-group col-md-12"> 
                 {{Form::label('sirket_id','Société')}}   
                 {{Form::select('sirket_id',$sirkets,request('sirket_id', 2), array('class' => 'form-control selectInvoiceSirket', 'required' => true)) }}
                </div>
                  <div class="form-group col-md-12"> 
                 {{Form::label('acente_id','Agence')}}   
                 {{Form::select('acente_id',$acentes,$post->acente_id, array('class' => 'form-control selectInvoiceAgency')) }}
                </div>
                <div class="form-group col-md-4"> 
                 {{Form::label('date','Date')}}   
                 {{Form::date('tarih',date('Y-m-d'), array('class' => 'form-control', 'id'=>'invoicetarih')) }}
                </div>
                 <div class="form-group col-md-4"> 
                  {{Form::label('resmi','N° facture')}}
                 {{Form::text('resmi',"",array('class' => 'form-control'))}}
                </div>  
                 <div class="form-group col-md-4">
                   {{Form::label('detail','Nom société / client')}} 
                 {{Form::text('detail[tittle]',$post->acente->tittle, array('class' => 'form-control', 'id'=>'invoicetittle')) }}
                </div>
                 <div class="form-group col-md-12">
                    {{Form::label('address','Adresse')}} 
                 {{Form::textarea('detail[address]',$post->acente->address, array('class' => 'form-control', 'id'=>'invoiceaddress')) }}
                 </div> 
                 <div class="form-group col-md-4">
                    {{Form::label('postal','Code postal')}} 
                 {{Form::text('detail[postal]',$post->acente->postal, array('class' => 'form-control', 'id'=>'invoicepostal')) }}
                  </div>
                 <div class="form-group col-md-4">
                    {{Form::label('city','Ville')}} 
                 {{Form::text('detail[city]',$post->acente->city, array('class' => 'form-control', 'id'=>'invoicecity')) }}
                  </div> 
                 <div class="form-group col-md-4"> 
                    {{Form::label('country','Pays')}} 
                    {{ Form::text('detail[country_name]', optional($post->acente->ulke)->country_name, array('class' => 'form-control','id'=>'invoicecountry_id')) }}

                </div> 
                 <div class="form-group col-md-6">
                   {{Form::label('vd','Service fiscal')}} 
                   {{Form::text('detail[vd]',$post->acente->vd, array('class' => 'form-control','id'=>'invoicevd')) }}
                </div> 
                 <div class="form-group col-md-6">
                   {{Form::label('vd','N° TVA / fiscal')}}
                   {{Form::text('detail[vdno]',$post->acente->vdno, array('class' => 'form-control','id'=>'invoicevdno')) }}
                </div>
                
                  
                <table class="table" >
                            <thead>
                              <tr>
                              
                                <th scope="col" style="width: 50%">Description</th>
                               
                                <th scope="col" style="width: 20%">TVA</th>
                                <th scope="col" style="width: 15%">Montant</th>
                                <th style="width: 15%">  <a class="btn btn-success btn-sm addRow" href="#" role="button" id="addSatir">
                       <i class="fa fa-plus" aria-hidden="true"></i>Row
                      </a> </th>
                              </tr>
                            </thead>
                            <tbody >
                              <tr> 
                                <td >{{Form::text('comments[]',"", array('class' => 'form-control'))}}</td>
                                @php
                                $kdvsArray = $kdvs->toArray();
                            @endphp
                                <td>{{Form::select('kdv_id[]',['0'=>'Select']+$kdvsArray ,"", array('class' => 'form-control'))}}</td>
                                <td>{{Form::number('amount[]',"", array('class' => 'form-control','step' => '.01'))}}</td>
                                 <td><a class="btn btn-success btn-sm addRow" href="#" role="button" id="addSatir">
                                  <i class="fa fa-plus" aria-hidden="true"></i></a><a class="btn btn-danger btn-sm removeRow" href="#" role="button" id="addSatir">
                                 <i class="fa fa-trash" aria-hidden="true"></i></a> 
                                </td>
                              </tr>
                             
                         </tbody>
                </table>
                </div>
            
                <div class="form-check form-check-inline">
           
                 {{Form::checkbox('yazi',1,array('class' => 'form-check-input'))}}
                 {{Form::label('yazi','Montant en lettres',array('class' => 'form-check-label'))}}
           
               </div>
        
              <div class="form-row">
                <div class="form-group col-md-4">
                 {{Form::label('yazi','Devise')}}
                 {{Form::select('kur_id',$kurs,'',array('class' => 'form-control'))}}
                </div>
                <div class="form-group col-md-4">
                 {{Form::label('account_id','Compte')}}
                 {{Form::select('account_id',['0'=>'Select'],'',array('class' => 'form-control'))}}
                </div>
                <div class="form-group col-md-4">
                {{Form::label('language','Langue')}}
                {{Form::select('lang',['0'=>'English','1'=>'Français','2'=>'Türkçe'],'',array('class' => 'form-control'))}}
                </div>
                 <div class="form-group col-md-12">
                {{Form::label('Not','Note en bas de page')}}
                {{Form::text('detail[not]',"",array('class' => 'form-control'))}}
                </div>
            </div>     
            
      </div>        
      <div class="modal-footer">
        
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
           
           {{Form::hidden('post_id',$post->id)}}
           {{Form::submit('Enregistrer la facture',array('class'=>'btn btn-primary'))}}
       
      </div>
       {{form::close()}}
        <table id="prototype" style="display:none;">
                                          <tr> 
                                <td >{{Form::text('comments[]',"", array('class' => 'form-control'))}}</td>
                              
                                <td>{{Form::select('kdv_id[]',['0'=>'Select']+$kdvsArray,"", array('class' => 'form-control'))}}</td>
                                <td>{{Form::number('amount[]',"", array('class' => 'form-control','step' => '.01'))}}</td>
                                 <td>
                                   
                                <a class="btn btn-success btn-sm addRow" href="#" role="button" id="addSatir">
                                  <i class="fa fa-plus" aria-hidden="true"></i></a><a class="btn btn-danger btn-sm removeRow" href="#" role="button" id="addSatir">
                                 <i class="fa fa-trash" aria-hidden="true"></i></a> 
                                            </td>
                              </tr>
                                        </table>  
    </div>
    
  </div>
</div>
