@extends('layouts.app')
@section('content')

<div class="row">
          <div class="col-lg-12 grid-margin stretch-card">
                <div class="card ">
                    <div class="card-body">
                      
                  <h3 class="card-title">Company Info</h3>
                                          
                <p>Page {{ $sirkets->currentPage() }} of {{ $sirkets->lastPage() }}</p>
                <div class="table-responsive">    
                    <table class="table table-striped">

                              <thead>
                                <tr>
                                  <th scope="col">Name</th>
                                  <th scope="col">Tel</th>
                                  <th scope="col">email</th>
                                  <th scope="col">info</th>
                                   <th scope="col">info2</th>
                                    <th scope="col">logo</th>
                                
                                </tr>
                              </thead>
                              <tbody>
                    @foreach ($sirkets as $sirket)
                                 <tr>
                                   
                                    <td>{{ $sirket->name}}</td>
                                    <td>{{ $sirket->tel}}</td>
                                    <td>{{ $sirket->email}}</td>
                                    <td>{{ $sirket->info}}</td>
                                      <td>{{ $sirket->info2}}</td>
                                     <td>{{ $sirket->logo}}</td>
                                    <td>
                                              <button 
                                               type="button" 
                                               class="btn btn-primary" 
                                               data-bs-toggle="modal"
                                               data-id="{{ $sirket->id }}"
                                               data-name="{{ $sirket->name}}"
                                               data-tel="{{ $sirket->tel}}"
                                               data-email="{{ $sirket->email}}"
                                               data-info="{{ $sirket->info}}"
                                               data-info2="{{ $sirket->info2}}"
                                               data-logo="{{ $sirket->logo}}"
                                               data-bs-target="#editModal">
                                              Edit 
                                            </button>
                                </td>  
                                </tr>
                       
                    @endforeach
                    </tbody>
                    </table>
                     <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModalLong">
  Add Company
</button> 
                    <div class="text-center">
                        {!! $sirkets->links() !!}
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
        <h5 class="modal-title" id="exampleModalLongTitle">New Company</h5>
        
        
       
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>{{ Form::open(array('route' => 'sirkets.store')) }}
      <div class="modal-body">
        {{ Form::label('name', 'Name', array('for' => 'validationDefault01')) }}
            {{ Form::text('name', null, array('class' => 'form-control','required'=>'required')) }}
            {{ Form::label('tel', 'Tel', array('for' => 'validationDefault01')) }}
            {{ Form::text('tel', null, array('class' => 'form-control')) }}
            {{ Form::label('email', 'Email', array('for' => 'validationDefault01')) }}
            {{ Form::text('email', null, array('class' => 'form-control')) }}
             {{ Form::label('info', 'Info', array('for' => 'validationDefault00')) }}
               {{Form::textarea('info','', ['class'=>'form-control'])}}
             {{ Form::label('info2', 'Info2', array('for' => 'validationDefault00')) }}
               {{Form::textarea('info2','', ['class'=>'form-control'])}}
         {{ Form::label('logo', 'Logo', array('for' => 'validationDefault01')) }}
             {{ Form::text('logo', null, array('class' => 'form-control')) }}
      
        
        
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
        <h5 class="modal-title" id="exampleModalLabel">Edit Company</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>    {{ Form::open(array('route' => 'sirket.guncel', 'class' => 'form','name' => 'edit',)) }}
      <div class="modal-body">
    
          <div class="form-group">
            <label for="recipient-name" class="col-form-label">Name:</label>
            {{Form::text('name', '', ['id' => 'data-name','class'=>'form-control', 'required'=>'required' ])}}
            {{Form::hidden('id', '', ['id' => 'data-id','class'=>'form-control'])}}
            {{ Form::label('tel', 'Tel', array('for' => 'validationDefault01')) }}
            {{Form::text('tel', '', ['id' => 'data-tel','class'=>'form-control' ])}}
            {{ Form::label('email', 'Email', array('for' => 'validationDefault01')) }}
            {{Form::text('email', '', ['id' => 'data-email','class'=>'form-control'])}}


             {{ Form::label('info', 'Info', array('for' => 'validationDefault00')) }}
               {{Form::textarea('info', '', ['id' => 'data-info','class'=>'form-control'])}}
            {{ Form::label('info2', 'Info2', array('for' => 'validationDefault00')) }}
               {{Form::textarea('info2', '', ['id' => 'data-info2','class'=>'form-control'])}}
             {{ Form::label('logo', 'Logo', array('for' => 'validationDefault00')) }}
               {{Form::textarea('logo', '', ['id' => 'data-logo','class'=>'form-control'])}}
             
            </div>
           
             
          
       
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
           {{Form::submit('Edit Company',array('class'=>'btn btn-primary'))}}
        
        
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
  var recipname = button.data('name') 
  var reciptel = button.data('tel') 
  var recipemail = button.data('email') 
   var recipinfo = button.data('info') 
  var recipinfo2 = button.data('info2')
   var reciplogo = button.data('logo')

   
  
  // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
  // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.
  var modal = $(this)
  modal.find('.modal-title').text('Edit  ' + recipname)
  modal.find('.modal-body #data-id').val(recipient)
  modal.find('.modal-body #data-name').val(recipname)
  modal.find('.modal-body #data-tel').val(reciptel)
  modal.find('.modal-body #data-email').val(recipemail)
  modal.find('.modal-body #data-info').val(recipinfo) 
  modal.find('.modal-body #data-info2').val(recipinfo2) 
  modal.find('.modal-body #data-logo').val(reciplogo) 
    
   
})
    </script>
@endsection