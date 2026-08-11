@extends('layouts.app')
@section('content')

        <div class="row">
          <div class="col-lg-12 grid-margin stretch-card">
                <div class="card ">
                    <div class="card-body">
                      
                  <h3 class="card-title">Provider Type</h3>
                                          
                <p> Page  {{ $firmas->currentPage() }} of {{ $firmas->lastPage() }}</p>
                <div class="table-responsive">    
                    <table class="table table-striped">

                              <thead>
                                <tr>
                                  <th scope="col">Type</th>
                                 
                                  <th scope="col">Color</th>
                                </tr>
                              </thead>
                              <tbody>
                    @foreach ($firmas as $firma)
                                 <tr>
                                   
                                   <td>{{ $firma->name}} <small>id:{{ $firma->id}}</small><d>
                                   <td>{{ $firma->color->name}}</td>
                                    <td>
                                              <button 
                                               type="button" 
                                               class="btn btn-primary" 
                                               data-bs-toggle="modal"
                                               data-id="{{ $firma->id }}"
                                               data-title="{{ $firma->name}}"
                                               data-color="{{ $firma->color_id}}"       
                                               data-bs-target="#editModal">
                                              Edit 
                                            </button>
                                </td>  
                                </tr>
                       
                    @endforeach
                    </tbody>
                    </table>
                     <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModalLong">
                        Add New Provider Type
                      </button> 
                    <div class="text-center">
                        {!! $firmas->links() !!}
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
        <h5 class="modal-title" id="exampleModalLongTitle">New Provider Type</h5>
        
        
       
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>{{ Form::open(array('route' => 'firmas.store')) }}
      <div class="modal-body">
        {{ Form::label('name', 'Statue Name', array('for' => 'validationDefault01')) }}
            {{ Form::text('name', null, array('class' => 'form-control','required'=>'required','placeholder'=>'New Provider Type Name')) }}
        {{ Form::label('color', 'Color', array('for' => 'validationDefault00')) }}
            {{ Form::select('color_id', $colors,null, array('class' => 'form-control','required'=>'required',)) }}
          
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         {{Form::submit('Add Type',array('class'=>'btn btn-primary'))}}
       
      </div>
       {{form::close()}}
    </div>
    
  </div>
</div>
   
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Edit Provider Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>    {{ Form::open(array('route' => 'firmas.guncel', 'class' => 'form','name' => 'edit',)) }}
      <div class="modal-body">
    
          <div class="form-group">
            <label for="recipient-name" class="col-form-label">Name:</label>
            {{Form::text('name', '', ['id' => 'data-title','class'=>'form-control'])}}
            {{Form::hidden('id', '', ['id' => 'data-id','class'=>'form-control'])}}
            
            {{ Form::label('color', 'Color', array('for' => 'validationDefault00')) }}
            {{ Form::select('color_id', $colors,null, array('class' => 'form-control','id'=>'data-color',)) }}
          </div>
           
             
          
       
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
           {{Form::submit('Edit Type',array('class'=>'btn btn-primary'))}}
        
        
      </div>
      {{Form::close()}}
    </div>
  </div>
</div>



</div>
</div></div>

@endsection
@section('footer')
  <script>  $('#editModal').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget) // Button that triggered the modal
  var recipient = button.data('id') // Extract info from data-* attributes
    var recipmname = button.data('title') 
     var recipcolor = button.data('color') 
  // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
  // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.
  var modal = $(this)
  modal.find('.modal-title').text('Edit  ' + recipmname)
  modal.find('.modal-body #data-id').val(recipient)
  modal.find('.modal-body #data-title').val(recipmname)
  modal.find('.modal-body #data-color').val(recipcolor)     
})
    </script>
@endsection