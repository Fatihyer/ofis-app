
<div class="row"> 
          <div class="col-xl-8 col-lg-8 col-md-8 col-sm-8 grid-margin stretch-card">
            <div class="card border-primary" id="whatever">
               <div class="card-body">                          
              <h3 class="card-title">Factures</h3>
            

     
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addinvoice">Ajouter une facture</button>
             <form action="{{ route('autofacture',$post->id) }}" method="GET" class="d-inline-flex align-items-center gap-1 ml-1">
                <select name="sirket_id" class="form-control form-control-sm" required style="width:170px; display:inline-block;">
                  <option value="">Société</option>
                  @foreach($sirkets as $id => $name)
                    <option value="{{ $id }}" {{ (int)$id === 2 ? 'selected' : '' }}>{{ $name }}</option>
                  @endforeach
                </select>
                <button class="btn btn-warning btn-sm" type="submit">Proforma automatique</button>
             </form>
                 
               <div class="table-responsive post-table-wrap">
                 <table class="table table-sm table-hover align-middle post-detail-table">
                 <thead>
                    <tr>
                      <th scope="col">Société</th>
                      <th scope="col">Date</th>
                      <th scope="col">No / Groupe</th>
                      <th scope="col">Agence</th>
                      <th scope="col">Titre</th>
                           <th scope="col">Prix</th>
                       <th scope="col">++</th>
                    </tr>
                  </thead>
                  <tbody>
                    
                 @foreach ($post->invoice as $invoices)
                <tr>
                <td>{{ optional($invoices->sirket)->name }}</td>
                <td> {{date('d-m-Y',strtotime($invoices->tarih))}}   </td>
                <td>{{$invoices->id}} {{(isset($invoices->groupinvoice[0]->id))?$invoices->groupinvoice[0]->id:"Yok"}} 
                  {{$invoices->resmi}} </td>
                 <td> 
                   <a href="{{route('acentes.show',$invoices->acente->id)}}">{{$invoices->acente->name}} </a></td> 
                  <td>  <?php $detail=unserialize($invoices->detail); ?> {{$detail['tittle']}} </td>
                <td>  {{$invoices->amount}} <i class="fas fa-{{$invoices->kur->icon}}-sign" aria-hidden="true"> </i>
              
  
       <a  title="Bakiye" data-poload="{{ route('hareket.bakiye',$invoices->acente_id) }}">???</a>

                  
                  </td>   
                <td>  <a  href="{{route('invoices.edit',$invoices->id)}}" class="btn btn-primary btn-sm"><i class="fas fa-pencil-alt" aria-hidden="true"></i></a>
                                  
                  <a  class="btn btn-danger btn-sm" href="{{ route('invoicetopdf',[$invoices->id,'pdf']) }}"><i class="fas fa-file-pdf"></i></a>
                  <a  class="btn btn-primary btn-sm" href="{{ route('invoicetopdf',[$invoices->id,'pdf2']) }}"><i class="fas fa-file-pdf"></i></a>
               
                 
                  <a  class="btn btn-success btn-sm" href="{{ route('invoicetopdf',[$invoices->id,'html']) }}"><i class="fa fa-eye" aria-hidden="true"></i></a>
                  </td> 
                    </tr>
                  @endforeach 
                 </table>
                 </div>
                 
                </div>
         </div>
      </div>