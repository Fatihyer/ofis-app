@extends('layouts.app')
@section('content')
<div class="row">
          <div class="col-lg-12 grid-margin stretch-card">
                <div class="card ">
                    <div class="card-body">
                      
                  <h3 class="card-title">Service Type</h3>
                                          
                {!! $servicetypes->appends(\Request::except('page'))->links('vendor/pagination/bootstrap-4') !!} 
                <div class="table-responsive">    
                    <table class="table table-striped">
  
                              <thead>
                                <tr>
                                  <th>id</th>
                                  <th scope="col">@sortablelink('name','Name')</th>
                                         
                                  <th scope="col">@sortablelink('firma_id','Provider')</th>
                                   <th scope="col">Color</th>
                                   <th scope="col">#</th>
                                     <th scope="col">#</th>
                                </tr>
                              </thead>
                              <tbody>
                    @foreach ($servicetypes as $servicetype)
                                 <tr>
                                   <td>{{$servicetype->id}}</td>
                                    <td>{{ $servicetype->name}}</td>
                                 
                                          <td>{{$servicetype->firma->name}}</td>
                                     <td> {{ $servicetype->color->name}}</td>
                                    <td>
                                              <button 
                                               type="button" 
                                               class="btn btn-primary" 
                                               data-bs-toggle="modal"
                                               data-id="{{ $servicetype->id }}"
                                               data-title="{{ $servicetype->name}}"
                                               data-color="{{ $servicetype->color_id}}"  
                                                data-firma="{{ $servicetype->firma_id}}"            
                                               data-bs-target="#editModal">
                                              Edit 
                                            </button>
                                </td>  
                                    <th scope="col">    <form  class="deleteinvoice" action="{{ route('servicetype.destroy', $servicetype->id) }}" method="POST">
                                          {{ method_field('DELETE') }}
                                          {{ csrf_field() }}
                                                                   
                                          <button class="btn btn-danger" ><i class="fas fa-trash"></i></button>
                                          
                                        </form>
                                      </th> 
                                </tr>
                       
                    @endforeach
                    </tbody>
                    </table>
                   <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModalLong">
  Add New Service Type
</button>
                    <div class="text-center">
                        {!! $servicetypes->links() !!}
                    </div>
                </div>
            </div>
          
        </div>
     
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="exampleModalLong" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
  <div class="modal-dialog" role="document">
     
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">New Service Type</h5>
        
        
       
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>{{ Form::open(array('route' => 'servicetype.store')) }}
      <div class="modal-body">
        {{ Form::label('name', 'Service Type Name', array('for' => 'validationDefault01')) }}
            {{ Form::text('name', null, array('class' => 'form-control','required'=>'required','placeholder'=>'NewStatue Name')) }}
        {{ Form::label('color', 'Color', array('for' => 'validationDefault00')) }}
            {{ Form::select('color_id', $colors,null, array('class' => 'form-control','required'=>'required',)) }}
        
        {{ Form::label('service', 'Service', array('for' => 'validationDefault00')) }}
        
         {{ Form::select('firma_id', $firmas,null, array('class' => 'form-control','required'=>'required',)) }}
          
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         {{Form::submit('Add Service Type',array('class'=>'btn btn-primary'))}}
       
      </div>
       {{form::close()}}
    </div>
    
  </div>
</div>
   
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Edit Service Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>    {{ Form::open(array('route' => 'servicetype.guncel', 'class' => 'form','name' => 'edit',)) }}
      <div class="modal-body">
    
          <div class="form-group">
            <label for="recipient-name" class="col-form-label">Name:</label>
            {{Form::text('name', '', ['id' => 'data-title','class'=>'form-control'])}}
            {{Form::hidden('id', '', ['id' => 'data-id','class'=>'form-control'])}}
            
            {{ Form::label('color', 'Color', array('for' => 'validationDefault00')) }}
            {{ Form::select('color_id', $colors,null, array('class' => 'form-control','id'=>'data-color',)) }}
            
            
          
            
                 {{ Form::label('firma_id', 'Service', array('for' => 'validationDefault00')) }}
            {{ Form::select('firma_id', $firmas,null, array('class' => 'form-control','id'=>'data-firma')) }}
          </div>
           
             
          
       
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
           {{Form::submit('Edit Service Type',array('class'=>'btn btn-primary'))}}
        
        
      </div>
      {{Form::close()}}
    </div>
  </div>
</div>

@endsection
@section('footer')
  <script>  $('#editModal').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget) // Button that triggered the modal
  var recipient = button.data('id') // Extract info from data-* attributes
    var recipmname = button.data('title') 
     var recipcolor = button.data('color') 
 
        var recipfirma = button.data('firma')
  // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
  // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.
  var modal = $(this)
  modal.find('.modal-title').text('Edit  ' + recipmname)
  modal.find('.modal-body #data-id').val(recipient)
  modal.find('.modal-body #data-title').val(recipmname)
  modal.find('.modal-body #data-color').val(recipcolor)     

        modal.find('.modal-body #data-firma').val(recipfirma) 
})
    </script>
@endsection