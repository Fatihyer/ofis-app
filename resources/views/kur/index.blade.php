@extends('layouts.app')
@section('content')
<div class="row">
          <div class="col-lg-12 grid-margin stretch-card">
                <div class="card ">
                    <div class="card-body">
                      
                  <h3 class="card-title">Exchange</h3>
                                          
                <p> Page {{ $exchanges->currentPage() }} of {{ $exchanges->lastPage() }}</p>
                <div class="table-responsive">    
                    <table class="table table-striped">
   
                              <thead>
                                <tr>
                                     <th scope="col">id</th>
                                  <th scope="col">Name</th>
                                  <th scope="col">Short Name</th>
                                  <th scope="col">icon(https://fontawesome.com)</th>
                                 
                                
                                </tr>
                              </thead>
                              <tbody>
                    @foreach ($exchanges as $exchange)
                                 <tr>
                                    <td>{{ $exchange->id}}</td>
                                    <td>{{ $exchange->name}}</td>
                                    <td>{{ $exchange->short_name}}</td>
                                    <td>{{ $exchange->icon}}</td>
                                 
                                    <td>
                                              <button 
                                               type="button" 
                                               class="btn btn-primary" 
                                               data-bs-toggle="modal"
                                               data-id="{{ $exchange->id }}"
                                               data-title="{{ $exchange->name}}"
                                               data-short="{{ $exchange->short_name}}"
                                               data-icon="{{ $exchange->icon}}"       
                                              
                                               data-bs-target="#editModal">
                                              Edit 
                                            </button>
                                </td>  
                                </tr>
                       
                    @endforeach
                    </tbody>
                    </table>
                   <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModalLong">
  Add New Exchange
</button> 
                    <div class="text-center">
                        {!! $exchanges->links() !!}
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
        <h5 class="modal-title" id="exampleModalLongTitle">New Exchange</h5>
        
        
       
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>{{ Form::open(array('route' => 'kurs.store')) }}
      <div class="modal-body">
        {{ Form::label('name', 'Exchange Name', array('for' => 'validationDefault01')) }}
            {{ Form::text('name', null, array('class' => 'form-control','required'=>'required','placeholder'=>'NewExchange Name')) }}
             {{ Form::label('short_name', 'Short Name', array('for' => 'validationDefault00')) }}
               {{Form::text('short_name', '', ['class'=>'form-control'])}}
            
             {{ Form::label('icon', 'Icon', array('for' => 'validationDefault00')) }}
               {{Form::text('icon', '', ['class'=>'form-control'])}}
        
           </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         {{Form::submit('Add Exchange',array('class'=>'btn btn-primary'))}}
       
      </div>
       {{form::close()}}
    </div>
    
  </div>
</div>
   
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Edit Exchange</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>    {{ Form::open(array('route' => 'kur.guncel', 'class' => 'form','name' => 'edit',)) }}
      <div class="modal-body">
    
          <div class="form-group">
            <label for="recipient-name" class="col-form-label">Name:</label>
            {{Form::text('name', '', ['id' => 'data-title','class'=>'form-control'])}}
            {{Form::hidden('id', '', ['id' => 'data-id','class'=>'form-control'])}}
            
             {{ Form::label('short_name', 'Short Name', array('for' => 'validationDefault00')) }}
               {{Form::text('short_name', '', ['id' => 'data-short','class'=>'form-control'])}}
            
             {{ Form::label('icon', 'Icon', array('for' => 'validationDefault00')) }}
               {{Form::text('icon', '', ['id' => 'data-icon','class'=>'form-control'])}}
            </div>
           
             
          
       
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
           {{Form::submit('Edit Exchange',array('class'=>'btn btn-primary'))}}
        
        
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
   var recipshort = button.data('short')
   var recipicon= button.data('icon')
  
  // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
  // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.
  var modal = $(this)
  modal.find('.modal-title').text('Edit  ' + recipmname)
  modal.find('.modal-body #data-id').val(recipient)
  modal.find('.modal-body #data-title').val(recipmname)
  modal.find('.modal-body #data-short').val(recipshort) 
  modal.find('.modal-body #data-icon').val(recipicon)     
      
   
})
    </script>
@endsection