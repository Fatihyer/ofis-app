@extends('layouts.app')

@section('title', '| Edit Proforma')

@section('content')


    <div class="row justify-content-md-center mt-5">
        <div class="col-md-8">
           {{ Form::model($invoices, array('route' => array('invoices.update', $invoices->id), 'method' => 'PUT')) }}   
                   
                          
            <div class="card">
                <div class="card-header"> <h2>
                  
                  {{$invoices->acente->name}}</h2> Edit Proforma</div>
                <div class="card-body">
                    <div class="form-row">
                       <div class="form-group col-md-12"> 
                {{Form::label('sirket_id','Company')}}   
                 {{Form::select('sirket_id',$sirkets,$invoices->sirket_id, array('class' => 'form-control selectInvoiceSirket')) }}
                      </div>
                  <div class="form-group col-md-12"> 
                {{Form::label('acente_id','Agency')}}   
                 {{Form::select('acente_id',$acentes,$invoices->acente_id, array('class' => 'form-control selectInvoiceAgency')) }}
                      </div>
                       
                <div class="form-group col-md-3"> 
                 {{Form::label('date','Proforma Date')}}   
                 {{Form::date('tarih',date('Y-m-d',strtotime($invoices->tarih)), array('class' => 'form-control', 'id'=>'invoicetarih')) }}
                </div>
                 <div class="form-group col-md-3"> 
                  {{Form::label('resmi','N° facture officiel')}}
                 {{Form::text('resmi',$invoices->resmi,array(
                    'class' => 'form-control',
                    'readonly' => !$canEditOfficialInvoiceNumber,
                    'style' => !$canEditOfficialInvoiceNumber ? 'background:#e9ecef; color:#6c757d; cursor:not-allowed;' : null,
                    'title' => !$canEditOfficialInvoiceNumber ? 'Réservé au Superadmin et à la Compta' : null,
                 ))}}
                 @if(!$canEditOfficialInvoiceNumber)
                    <small class="text-muted">Réservé au Superadmin et à la Compta.</small>
                 @endif
                </div>       
                 <div class="form-group col-md-3">
                   {{Form::label('avoir','Avoir N de Facture')}} 
                   
                 {{Form::text('avoir',$invoices->avoir, array('class' => 'form-control')) }}
                </div>
                <div class="form-group col-md-3">
                   {{Form::label('detail','Company Name')}} 
                   
                 {{Form::text('detail[tittle]',$detail['tittle'], array('class' => 'form-control', 'id'=>'invoicetittle')) }}
                </div>
                 <div class="form-group col-md-12">
                    {{Form::label('address','Address')}} 
                 {{Form::textarea('detail[address]',$detail['address'], array('class' => 'form-control', 'id'=>'invoiceaddress')) }}
                 </div>
                  <div class="form-group col-md-4">
                    {{Form::label('postal','Postal')}} 
                 {{Form::text('detail[postal]',$detail['postal'], array('class' => 'form-control', 'id'=>'invoicecity')) }}
                  </div>      
                 <div class="form-group col-md-4">
                    {{Form::label('city','City')}} 
                 {{Form::text('detail[city]',$detail['city'], array('class' => 'form-control', 'id'=>'invoicecity')) }}
                  </div> 
                 <div class="form-group col-md-4"> 
                    {{Form::label('country','Country')}} 
                 {{Form::text('detail[country_name]',$detail['country_name'], array('class' => 'form-control','id'=>'invoicecountry_id')) }}
                </div> 
                 <div class="form-group col-md-6">
                   {{Form::label('vd','Tax area')}} 
                   {{Form::text('detail[vd]',$detail['vd'], array('class' => 'form-control','id'=>'invoicevd')) }}
                </div> 
                 <div class="form-group col-md-6">
                   {{Form::label('vd','Tax Number')}}
                   {{Form::text('detail[vdno]',$detail["vdno"], array('class' => 'form-control','id'=>'invoicevdno')) }}
                </div>
                
                <table id="prototype" style="display:none;">
                                          <tr> 
                                <td >{{Form::text('comments[]',"", array('class' => 'form-control'))}}</td>
                                <td>{{Form::select('kdv_id[]',['0'=>'Select']+$kdvs,"", array('class' => 'form-control'))}}</td>
                                <td>{{Form::text('amount[]'," ", array('class' => 'form-control'))}}</td>
                                 <td>
                                   
                                <a class="btn btn-success btn-sm addRow" href="#" role="button" id="addSatir">
                                  <i class="fa fa-plus" aria-hidden="true"></i></a><a class="btn btn-danger btn-sm removeRow" href="#" role="button" id="addSatir">
                                 <i class="fa fa-trash" aria-hidden="true"></i></a> 
                                            </td>
                              </tr>
                                        </table>    
                <table class="table" >
                            <thead>
                              <tr>
                              
                                <th scope="col" style="width: 50%">Description</th>
                               
                                <th scope="col" style="width: 20%">Tax%</th>
                                <th scope="col" style="width: 15%">Amount</th>
                                <th style="width: 15%">  <a class="btn btn-success btn-sm addRow" href="#" role="button" id="addSatir">
                       <i class="fa fa-plus" aria-hidden="true"></i>Row
                      </a> </th>
                              </tr>
                            </thead>
                            <tbody>
                              
                              @forelse ($invoices->invoicedetail as $invoicedetails)
                              <tr> 
                              
                                <td >{{Form::text('comments[]',$invoicedetails->comments, array('class' => 'form-control'))}}</td>
                                <td>{{Form::select('kdv_id[]',['0'=>'Select']+$kdvs,$invoicedetails->kdv_id, array('class' => 'form-control'))}}</td>
                                <td>{{Form::text('amount[]',isset($invoicedetails->amount)?$invoicedetails->amount:"", array('class' => 'form-control','autocomplete'=>'off'))}}</td>
                                 <td><a class="btn btn-success btn-sm addRow" href="#" role="button" id="addSatir">
                                  <i class="fa fa-plus" aria-hidden="true"></i></a><a class="btn btn-danger btn-sm removeRow" href="#" role="button" id="addSatir">
                                 <i class="fa fa-trash" aria-hidden="true"></i></a> 
                                </td>
                              </tr>
                              @empty
                                  <tr> 
                                <td >{{Form::text('comments[]',"", array('class' => 'form-control'))}}</td>
                                <td>{{Form::select('kdv_id[]',['0'=>'Select']+$kdvs,"", array('class' => 'form-control'))}}</td>
                                <td>{{Form::text('amount[]',"dd ", array('class' => 'form-control'))}}</td>
                                 <td>
                                   
                                <a class="btn btn-success btn-sm addRow" href="#" role="button" id="addSatir">
                                  <i class="fa fa-plus" aria-hidden="true"></i></a><a class="btn btn-danger btn-sm removeRow" href="#" role="button" id="addSatir">
                                 <i class="fa fa-trash" aria-hidden="true"></i></a> 
                                            </td>
                              </tr>
                             @endforelse
                         </tbody>
                </table>
                </div>
               
                <div class="form-check form-check-inline">
           
                 {{Form::checkbox('yazi',1,$invoices->yazi,array('class' => 'form-check-input'))}}
                 {{Form::label('yazi','Write number',array('class' => 'form-check-label'))}}
           
               </div>
        
              <div class="form-row">
                <div class="form-group col-md-4">
                 {{Form::label('yazi','Exchange')}}
                 {{Form::select('kur_id',$kurs,$invoices->kur_id,array('class' => 'form-control'))}}
                </div>
                <div class="form-group col-md-4">
                 {{Form::label('account_id','Account')}}
                 {{Form::select('account_id',['0'=>'Select']+$accounts,$invoices->account_id,array('class' => 'form-control'))}}
                </div>
                <div class="form-group col-md-4">
                {{Form::label('language','Language')}}
                {{Form::select('lang',['0'=>'English','1'=>'Français','2'=>'Türkçe'],$invoices->language_id,array('class' => 'form-control'))}}
                </div>
                 <div class="form-group col-md-12">
                {{Form::label('Not','Note on footer')}}
                {{Form::text('detail[not]',$detail['not'],array('class' => 'form-control'))}}
                </div>
            </div>     
              
                </div>
            </div>
          <div class="modal-footer">
            <a  class="btn btn-secondary" href="{{ route('invoicetopdf',[$invoices->id,'html']) }}"><i class="fab fa-internet-explorer"></i></a>
             <a  class="btn btn-danger" href="{{ route('invoicetopdf',[$invoices->id,'pdf']) }}"><i class="fas fa-file-pdf"></i></a>
             <a  class="btn btn-primary" href="{{ route('invoicetopdf',[$invoices->id,'pdf2']) }}"><i class="fas fa-file-pdf"></i></a>
           
           {{Form::hidden('post_id',$invoices->post_id)}}
           {{Form::submit('Save',array('class'=>'btn btn-primary'))}}
           <a class="btn btn-success" href="{{ route('invoices.index') }}">Proforma List</a>
            <a class="btn btn-warning" href="{{ route('posts.show',$invoices->post_id) }}">Return To File</a>
             <a class="btn btn-info" href="{{ route('acentes.show',$invoices->acente_id) }}">Return To Provider</a>

         
          </div>
          {{form::close()}}
    </div>
</div>
    </div>
</div>

@endsection

@section('footer')
@include ('invoice.invoices-js') {{-- Including create blade file --}}

@endsection
