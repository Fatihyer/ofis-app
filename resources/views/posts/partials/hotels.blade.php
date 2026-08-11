
<div class="row"> 
  <div class="col-md-12 grid-margin"> 
<div class="card">
   <div class="card-body">
    
        <a class="btn btn-success btn-sm card-title" href="{{route('hotels.create')}}?file={{$post->id}}&acente={{$post->acente_id}}" >Ajouter un hôtel</a>
    
             <div class="table-responsive post-table-wrap">
      
  
          <table class="table table-sm table-hover align-middle post-detail-table">
                      <thead>
                        <tr>
                          <th width="8%">Hôtel</th>
                          <th width="10%">Du</th>
                          <th width="10%">Au</th>
                          <th width="5%">Acc</th>
                          <th width="5%">Sng</th>
                          <th width="5%">Dbl</th>
                          <th width="5%">Trp</th>
                           <th width="5%">Qtp</th>
                           <th width="5%">Fam</th>
                          <th width="5%">chd</th>
                          <th width="5%">Chd Age</th>
                          <th width="5%">Pax</th>
                           <th width="5%">Commentaire</th>
                           <th width="5%">#</th>
                        </tr>
            </thead>
                  <tbody>
                   @foreach ($post->hotels as $hotel)
                    <tr>
                   <td>{{$hotel->acente->name}}</td> 
                   <td>{{date("d-m-Y", strtotime($hotel->from)) }}</td> 
                   <td>{{date("d-m-Y", strtotime($hotel->to)) }}</td> 
                   <td>{{$hotel->servicetype->name}}</td>   
                   <td>{{$hotel->sng}}</td> 
                      <td>{{$hotel->dbl}}</td>
                       <td>{{$hotel->trp}}</td>
                       <td>{{$hotel->qtr}}</td>
                      <td>{{$hotel->fam}}</td>
                      <td>{{$hotel->chd}}</td>
                       <td>{{$hotel->chdyears}}</td>
                      <td>{{$hotel->pax}}</td>
                      <td>{{$hotel->comment}}</td>
                   <td> <form action="{{ route('hotels.destroy',$hotel->id) }}" method="POST" class="form-inline">
                                            {{ method_field('DELETE') }}
                                            {{ csrf_field() }}
                     <a href="{{route('hotels.edit',$hotel->id)}}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a>
                     <a href="mailto:{{$hotel->acente->email}}?subject={{$post->title}} Ref:{{$post->id}}/{{$hotel->id}}&body=Aşağıda detayları olan rezervasyonumuzu konfirme edilmesini rica ederiz,%0D%0A
Hotel: {{$hotel->acente->name}}%0D%0A
Dossier : {{$post->id}}%0D%0A
Res No:{{$hotel->id}}%0D%0A
In:    {{date("d-m-Y", strtotime($hotel->from)) }}%0D%0A
Out:   {{date("d-m-Y", strtotime($hotel->to)) }}%0D%0A
Room(s):{{$hotel->sng?$hotel->sng.'sng,':""}}{{$hotel->dbl?$hotel->dbl.'dbl,':""}}{{$hotel->trp?$hotel->trp.'trp,':""}}{{$hotel->fam?$hotel->fam.'fam,':""}}{{$hotel->chd?$hotel->chd.'chd,':""}}{{$hotel->chdyears?$hotel->chdyears.'chdyears,':""}}%0D%0A
Pax:   {{$hotel->pax }}%0D%0A
Accomodation:   {{$hotel->servicetype->name }}%0D%0A
Noms : @foreach ($post->client as $clients ){{$clients->title }} {{$clients->name }} {{$clients->surname }} , @endforeach%0D%0A
{{$hotel->comment}}%0D%0A
" 
                      
                      
                      
                      class="btn btn-success btn-sm"><i class="fas fa-envelope-open"></i></a>
                    <button class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i></button>
                    
                                        </form>
                                        <a  class="btn btn-success btn-sm" href="{{ route('vouchertopdf',[$hotel->id,$hotel->acente->id,'html']) }}"><i class="fa fa-eye" aria-hidden="true"></i></a>
            
                                      </td>    
                    </tr>
                  @endforeach
            </tbody>
               </table>
               </div>
               
               </div>
            </div> 
    </div>
</div> 
