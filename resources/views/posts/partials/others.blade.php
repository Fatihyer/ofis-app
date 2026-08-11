
<div class="row"> 
  <div class="col-md-12 grid-margin"> 
<div class="card">
   <div class="card-body">
    
        <a class="btn btn-success btn-sm card-title" href="{{route('others.create')}}?file={{$post->id}}&acente={{$post->acente_id}}" >Ajouter un autre service</a>
    
             <div class="table-responsive post-table-wrap">
      
  
          <table class="table table-sm table-hover align-middle post-detail-table">
                      <thead>
                        <tr>
                          <th width="8%">Prestataire</th>
                          <th width="10%">Du</th>
                          <th width="10%">Au</th>
                          <th width="5%">Pax</th>
                          <th width="5%">Commentaire</th>
                          <th width="5%">#</th>
                        </tr>
            </thead>
                  <tbody>
                   @foreach ($post->others as $other)
                    <tr>
                   <td>{{$other->acente->name}}</td> 
                   <td>{{date("d-m-Y", strtotime($other->from)) }}</td> 
                   <td>{{date("d-m-Y", strtotime($other->to)) }}</td> 
                   <td>{{$other->pax}}</td>
                   <td>{{$other->comment}}</td>
                   <td> <form action="{{route('others.destroy',$other->id)}}" method="POST" class="form-inline">
                                            {{ method_field('DELETE') }}
                                            {{ csrf_field() }}
                     <a href="{{route('others.edit',$other->id)}}" class="btn btn-success">Modifier</a>
                     <a href="{{route('others.clone',$other->id)}}" class="btn btn-danger">Dupliquer</a>
                                            <button class="btn btn-warning btn-sm"><i class="fas fa-trash-alt"></i></button>
                                        </form></td>    
                    </tr>
                  @endforeach
            </tbody>
               </table>
               </div>
               
               </div>
            </div> 
    </div>
</div> 
