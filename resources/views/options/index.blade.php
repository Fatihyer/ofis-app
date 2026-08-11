@extends('layouts.app')
@section('content')

<div class="row">
          <div class="col-lg-12 grid-margin stretch-card">
                <div class="card ">
                    <div class="card-body">
                      
                  <h3 class="card-title"> Options</h3>

<div class="alert alert-info d-flex justify-content-between align-items-center flex-wrap">
  <div>
    <strong>Hermes toast refresh</strong><br>
    <span>Option à utiliser: <code>hermesToastRefreshMinutes</code>. Valeur en minutes, par défaut 5.</span>
  </div>
  <button type="button" class="btn btn-sm btn-outline-primary mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#exampleModalLong">Ajouter / modifier une option</button>
</div>

                                          
                <p>Page {{ $options->currentPage() }} of {{ $options->lastPage() }}</p>
                <div class="table-responsive">    
                    <table class="table table-striped">

                              <thead>
                                <tr>
                                  <th scope="col">Name</th>
                                  <th scope="col">Value
                                   
                                
                                </tr>
                              </thead>
                              <tbody>
                    @foreach ($options as $option)
                                 <tr>
                                   
                                    <td>{{ $option->name}}</td>
                                    <td>{{ $option->value}}</td>
                                    
                                    <td>
                                              <button 
                                               type="button" 
                                               class="btn btn-primary" 
                                               data-bs-toggle="modal"
                                               data-id="{{ $option->id }}"
                                               data-title="{{ $option->name}}"
                                               data-value="{{ $option->value}}"
                                              
                                               data-bs-target="#editModal">
                                              Edit 
                                            </button>
                                </td>  
                                </tr>
                       
                    @endforeach
                    </tbody>
                    </table>
                     <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModalLong">
  Add Option
</button> 
                    <div class="text-center">
                        {!! $options->links() !!}
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
        <h5 class="modal-title" id="exampleModalLongTitle">New Option</h5>
        
        
       
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>{{ Form::open(array('route' => 'options.store')) }}
      <div class="modal-body">
        {{ Form::label('name', 'Name', array('for' => 'validationDefault01')) }}
            {{ Form::text('name', null, array('class' => 'form-control','required'=>'required')) }}
             {{ Form::label('value', 'Value', array('for' => 'validationDefault00')) }}
               {{Form::textarea('value','', ['class'=>'form-control'])}}
            
            
        
           </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         {{Form::submit('Add Option',array('class'=>'btn btn-primary'))}}
       
      </div>
       {{form::close()}}
    </div>
    
  </div>
</div>
   
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Edit Option</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>    {{ Form::open(array('route' => 'option.guncel', 'class' => 'form','name' => 'edit',)) }}
      <div class="modal-body">
    
          <div class="form-group">
            <label for="recipient-name" class="col-form-label">Name:</label>
            {{Form::text('name', '', ['id' => 'data-title','class'=>'form-control', 'required'=>'required' ])}}
            {{Form::hidden('id', '', ['id' => 'data-id','class'=>'form-control'])}}
            
             {{ Form::label('value', 'Value', array('for' => 'validationDefault00')) }}
               {{Form::textarea('value', '', ['id' => 'data-value','class'=>'form-control'])}}
            
             
            </div>
           
             
          
       
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
           {{Form::submit('Edit Option',array('class'=>'btn btn-primary'))}}
        
        
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
  var recipvalue = button.data('value')

   
  
  // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
  // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.
  var modal = $(this)
  modal.find('.modal-title').text('Edit  ' + recipmname)
  modal.find('.modal-body #data-id').val(recipient)
  modal.find('.modal-body #data-title').val(recipmname)
  modal.find('.modal-body #data-value').val(recipvalue) 
    
   
})
    </script>
@endsection