@extends('layouts.app')
@section('content')
  
        <div class="row">
          
                <div class="col-lg-12 grid-margin">
                    <div class="card">
                      <div class="card-body">
                        <h4 class="card-title">Factures groupées</h4> Page {{ $groupinvoices->currentPage() }} / {{ $groupinvoices->lastPage() }}
                        <form action="{{ route('groupinvoices.index') }}" method="GET" class="mb-3">
                          <div class="row">
                            <div class="col-md-4">
                              <label>Société</label>
                              <select name="sirket_id" class="form-control" onchange="this.form.submit()">
                                <option value="">Toutes les sociétés</option>
                                @foreach($sirkets as $id => $name)
                                  <option value="{{ $id }}" {{ (string)$selectedSirketId === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                              </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                              <a href="{{ route('groupinvoices.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
                            </div>
                          </div>
                        </form>
                        <div class="table-responsive">
                          <table class="table table-bordered">
                            <thead>
           
                                <tr>
                                  <th scope="col">N° groupe </th>
                                  <th scope="col">Dossier </th>
                                  <th scope="col">Agence </th>
                                  <th scope="col">Date</th>
                                  <th scope="col">Montant</th>
                                  <th scope="col">Factures</th>
                                
                                  <th scope="col">#</th>
                                </tr>
                              </thead>
                              <tbody>
                    @foreach ($groupinvoices as $groupinvoice)
                     <tr>
                                    <th scope="row"><a href="{{ route('grinvoicetopdf',[$groupinvoice->id,'html']) }}"><b>{{$groupinvoice->id}}</b></a>/
                       {{$groupinvoice->resmi}} <br/>
                       {{$groupinvoice->sirket->name}}
                       @if($groupinvoice->pennylane_customer_invoice_id)
                         <br/><span class="badge badge-success">Pennylane #{{ $groupinvoice->pennylane_customer_invoice_id }}</span>
                       @endif
                       
                       </th>
                                      <td>
                                         @foreach($groupinvoice->invoices as $invoice)
                                     <a href="{{ route('posts.show', $invoice->post_id ) }}"> {{$invoice->post_id}}</a>
                                    @endforeach  
                                        
                                       </td> 
                                     <td>
                                      @if(isset($groupinvoice->invoices[0]))
                                      <a href="{{ route('acentes.show', $groupinvoice->invoices[0]->acente_id ) }}"><b>{{$groupinvoice->invoices[0]->acente->name}}</b></a>
                                    @endif
                                    
                                    </td>
                                 
                                     <td>{{ date("d-m-Y", strtotime($groupinvoice->tarih))}}</td>
                                    <td>
                                     {{$groupinvoice->invoices->sum('amount')}}
                                  </td>
                                    <td> @foreach($groupinvoice->invoices as $invoice)
                                     <a href="{{ route('invoices.edit', $invoice->id ) }}"> {{$invoice->id}}</a>
                                    @endforeach  </td>
                                    <td>
                                     <div class="btn-group btn-group-toggle">
                                         <a  href="{{route('groupinvoices.edit',$groupinvoice->id)}}" class="btn btn-primary"><i class="fas fa-edit"></i></a>
                                       <a  class="btn btn-success" href="{{ route('grinvoicetopdf',[$groupinvoice->id,'pdf']) }}"><i class="fas fa-file-pdf"></i></a>
                                       <a  class="btn btn-secondary" href="{{ route('invoicetopdf',[$groupinvoice->id,'xls']) }}"><i class="fas fa-file-excel"></i></a>
                                       @if(isset($groupinvoice->invoices[0]))
                                       <a  class="btn btn-primary" href="{{ route('acentes.show', $groupinvoice->invoices[0]->acente_id ) }}"><i class="fab fa-internet-explorer"></i></a>
                                       @endif
                                       @role('Superadmin')
                                       @if($groupinvoice->pennylane_customer_invoice_id)
                                       <button class="btn btn-outline-success" disabled title="Pennylane #{{ $groupinvoice->pennylane_customer_invoice_id }}"><i class="fas fa-check"></i></button>
                                       @else
                                       <form class="group-pennylane-draft d-inline" action="{{ route('groupinvoices.pennylane.draft', $groupinvoice->id) }}" method="POST">
                                          {{ csrf_field() }}
                                          <button class="btn btn-outline-dark" title="Pennylane brouillon groupé"><i class="fas fa-paper-plane"></i></button>
                                       </form>
                                       @endif
                                       @endrole
                                      </li>
        </ul> 
                                       
                                       <form  class="deleteinvoice" action="{{ route('groupinvoices.destroy', $groupinvoice->id) }}" method="POST">
                                          {{ method_field('DELETE') }}
                                          {{ csrf_field() }}
                                          <button class="btn btn-danger" ><i class="fas fa-trash-alt"></i></button>
                                          
                                        </form>
                                      </div>
                                      
                       </td>      
                              
                     
                                   
                                  </tr>
                       
                       
                    @endforeach
                      </tbody></table>
                    </div>
                    <div class="text-center">
                        {!! $groupinvoices->links() !!}
                    </div>
                </div>
            </div>
</div>
</div>

@endsection
@section('footer')
<script>
jQuery(document).ready(function($){
     $('.deleteinvoice').on('submit',function(e){
        if(!confirm('Supprimer cette facture groupée ?')){
              e.preventDefault();
        }
	      });
        $('.group-pennylane-draft').on('submit',function(e){
        if(!confirm('Créer un brouillon Pennylane pour cette facture groupée ?')){
              e.preventDefault();
        }
      });
})
</script>
@endsection
