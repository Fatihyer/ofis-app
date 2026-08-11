
<div class="row"> 
  <div class="col-md-12 grid-margin"> 
<div class="card">
   <div class="card-body">
    
        <a class="btn btn-success btn-sm card-title" href="{{route('stocks.create')}}?file={{$post->id}}&acente={{$post->acente_id}}" >Ajouter un produit</a>
    
             <div class="table-responsive post-table-wrap">
      
  
          <table class="table table-sm table-hover align-middle post-detail-table">
                      <thead>
                        <tr>
                          <th width="8%">Catégorie</th>
                          <th width="13%">Produit</th>
                          <th width="13%">Date</th>
                          <th width="10%">Quantité</th>
                          <th width="10%">Prestataire</th>
                          <th width="7%">#</th>
                        </tr>
            </thead>
                  <tbody>
                   @foreach ($post->stock as $stocks)
                    <tr>
                   <td>{{$stocks->urun->name}}</td> 
                   <td>{{$stocks->urun->name}}</td> 
                   <td>{{$stocks->tarih}}</td> 
                   <td>{{$stocks->adet}}</td> 
                   <td>{{$stocks->alacakli->name}}</td> 
                      <td><a class="btn btn-success" href="{{route('stocks.edit',$stocks->id)}}">Modifier</a></td>    
                    </tr>
                  @endforeach
            </tbody>
               </table>
               </div>
               
               </div>
            </div> 
    </div>
</div> 

