@extends('layouts.app')
@section('content')
<div class="row">
          <div class="col-lg-12 grid-margin stretch-card">
                <div class="card ">
                    <div class="card-body">
                      
                  <h3 class="card-title">Value added Tax</h3>
                                          
                <p> Page {{ $kdvs->currentPage() }} of {{ $kdvs->lastPage() }}</p>
                <div class="table-responsive">    
                    <table class="table table-striped">
  
                              <thead>
                                <tr>
                                  <th scope="col">Name</th>
                                  <th scope="col">Percent
                                   
                                
                                </tr>
                              </thead>
                              <tbody>
                    @foreach ($kdvs as $kdv)
                                 <tr>
                                   
                                    <td>{{ $kdv->name}}</td>
                                    <td>{{ $kdv->percent}}</td>
                                    
                                    <td>
                                              <button 
                                               type="button" 
                                               class="btn btn-primary" 
                                               data-bs-toggle="modal"
                                               data-id="{{ $kdv->id }}"
                                               data-title="{{ $kdv->name}}"
                                               data-percent="{{ $kdv->percent}}"
                                                
                                               data-bs-target="#editModal">
                                              Edit 
                                            </button>
                                </td>  
                                </tr>
                       
                    @endforeach
                    </tbody>
                    </table>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModalLong">
  Add NewValue-added Tax
</button> 
                    <div class="text-center">
                        {!! $kdvs->links() !!}
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
        <h5 class="modal-title" id="exampleModalLongTitle">New Value-added Tax</h5>
        
        
       
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>{{ Form::open(array('route' => 'kdvs.store')) }}
      <div class="modal-body">
        {{ Form::label('name', 'Valueadded Tax Name', array('for' => 'validationDefault01')) }}
            {{ Form::text('name', null, array('class' => 'form-control','required'=>'required')) }}
             {{ Form::label('percent', 'Percent%', array('for' => 'validationDefault00')) }}
               {{Form::text('percent','', ['class'=>'form-control'])}}
            
            
        
           </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         {{Form::submit('Add Value-added Tax',array('class'=>'btn btn-primary'))}}
       
      </div>
       {{form::close()}}
    </div>
    
  </div>
</div>
   
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Edit Value-added Tax</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>    {{ Form::open(array('route' => 'kdv.guncel', 'class' => 'form','name' => 'edit',)) }}
      <div class="modal-body">
    
          <div class="form-group">
            <label for="recipient-name" class="col-form-label">Name:</label>
            {{Form::text('name', '', ['id' => 'data-title','class'=>'form-control'])}}
            {{Form::hidden('id', '', ['id' => 'data-id','class'=>'form-control'])}}
            
             {{ Form::label('percent', 'Short Name', array('for' => 'validationDefault00')) }}
               {{Form::text('percent', '', ['id' => 'data-percent','class'=>'form-control'])}}
            
             
            </div>
           
             
          
       
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
           {{Form::submit('Edit Value-added Tax',array('class'=>'btn btn-primary'))}}
        
        
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
   var recippercent = button.data('percent')
   
  
  // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
  // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.
  var modal = $(this)
  modal.find('.modal-title').text('Edit  ' + recipmname)
  modal.find('.modal-body #data-id').val(recipient)
  modal.find('.modal-body #data-title').val(recipmname)
  modal.find('.modal-body #data-percent').val(recippercent) 
      
   
})
    </script>
@endsection