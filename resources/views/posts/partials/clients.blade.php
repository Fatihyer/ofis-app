<div class="row">
   <div class="col-md-12 grid-margin"> 
                      <div class="card">
                           <div class="card-body">
                      
                         <a class="btn btn-success btn-sm card-title" href="#"  data-bs-toggle="modal" data-bs-target="#addclient">Ajouter un client</a>
                      
                    <div class="table-responsive post-table-wrap">
                     <table class="table table-sm table-hover align-middle post-detail-table">
                                          <thead>
                                            <tr>
                                              <th scope="col">Titre</th>
                                              <th scope="col">Nom</th>
                                              <th scope="col">Prénom</th>
                                              <th scope="col">Email</th>
                                              <th scope="col">Téléphone</th>
                                              <th scope="col">Commentaires</th>

                                              <th>#</th>
                                            </tr>
                                          </thead>
                                          <tbody>
                                             @foreach ($post->client as $clients )
                                            <tr>

                                              <td>{{$clients->title}}</td>
                                              <td>{{$clients->name}}</td>
                                              <td>{{$clients->surname}}</td>
                                              <td>{{$clients->email}}</td>
                                              <td>{{$clients->tel}}</td>
                                              <td>{{$clients->comments}}</td>
                                               <td>{{ Form::open(['route' => ['clients.destroy', $clients->id], 'method' => 'delete']) }}
                                                   <a href="{{route('clients.edit',$clients->id)}}"  class="btn btn-success">Modifier</a>
                                                   {{ Form::submit('Supprimer',array('class'=>'btn btn-danger')) }}
                                                   {{ Form::close() }}
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