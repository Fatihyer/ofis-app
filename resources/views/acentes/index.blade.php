@extends('layouts.app')
@section('content')
   
        <div class="row">
            <div class="col-md-12 grid-margin">
              
                   <div class="card">
               

                      <div class="card-body">
                           <h4 class="card-title">Providers</h4>
                   
                      
                      <form method="get" name='tarih'>
                         <div class="form-row align-items-center">
                            <div class="col-auto">
                           
                              <a href="{{ route('acentes.index') }}" class="btn btn-secondary">All</a>
                            </div>
                            
                          <div class="col-auto">
                        <label>@lang('app.providers_type'):  </label>
                           </div>
                                <div class="col-auto">
                            
                       {{Form::select('firma',['0'=>'sec']+$firma,'',['class'=>'form-control','onchange'=>'this.form.submit()'])}}
                           </div>
                       
                       <div class="col-auto">    
                        {{Form::text('s',"",array('class'=>'form-control'))}}
                           </div>
                         <div class="col-auto">  
                           <button class="input-group-text" type="submit"><i class="fa fa-search" aria-hidden="true"></i></button>
                          </div>
                       
                           <div class="col-auto">
                            <a href="{{ route('acentes.create') }}" class="btn btn-danger">Add Provider</a>
                          </div>
                            <div class="col-auto">
                        
                    
                          {!! $acentes->appends(\Request::except('page'))->links('vendor/pagination/bootstrap-4') !!}
                            </div>
                        </div>   
                           </form>
                     
                  <div class="table-responsive">
                    <table class="table table-striped">
                              <thead>
                                <tr>
                                  <th scope="col">Details </th>
                                  <th scope="col">@sortablelink('name',__('app.name'))</th>
                                  
                               
                                  <th scope="col">City</th>
                                  <th scope="col">Country</th>
                                  <th scope="col">Tel</th>
                                  <th scope="col">#</th>
                                </tr>
                              </thead>
                              <tbody>
                    @foreach ($acentes as $acente)
                                 <tr>
                                   <td scope="row"><a href="{{ route('acentes.show', $acente->id ) }}"><b>{{ $acente->id}} </b></a>
                                     @foreach ($acente->firmas as $firma)
                                     {{$firma->name}}
                                      @endforeach
                                </td>
                                   <td><a href="{{ route('acentes.show', $acente->id ) }}">{{ $acente->name}}</a></td>
                                                
                                  
                                    <td>{{ $acente->city}}</td>
                                    <td>{{ $acente->ulke->country_name}}</td>
                                    <td>{{ $acente->tel}}</td>
                                   <th scope="col">    <form  class="deleteinvoice" action="{{ route('acentes.destroy', $acente->id) }}" method="POST">
                                          {{ method_field('DELETE') }}
                                          {{ csrf_field() }}
                                     <a class="btn btn-primary" href="{{route('acentes.edit',$acente->id)}}"><i class="fa fa-edit"></i></a>
                               
                                          <button class="btn btn-danger" ><i class="fas fa-trash"></i></button>
                                          
                                        </form>
                                      </th>
                                  </tr>
                       
                    @endforeach
                    </tbody>
                    </table>
                    <div class="text-center">
                       {!! $acentes->appends(\Request::except('page'))->links('vendor/pagination/bootstrap-4') !!}
                    </div>
                </div>
                
               </div>

       

          </div></div>
        
          <a href="{{ route('acentes.create') }}" class="btn btn-danger">Add Provider</a>
      
</div>


@endsection
