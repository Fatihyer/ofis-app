@extends('layouts.app')
@section('content')

<div class="row">
          <div class="col-lg-12 grid-margin stretch-card">
                <div class="card ">
                    <div class="card-body">
                      
                  <h3 class="card-title">Payments Type</h3>
                                          
                <p>Page {{ $payments->currentPage() }} of {{ $payments->lastPage() }}</p>
                <div class="table-responsive">    
                    <table class="table table-striped">
                      
 
                              <thead>
                                <tr>
                                  <th scope="col">Name</th>
                                  <th scope="col">Current(cari)</th>
                                   <th scope="col">Color</th>
                                
                                </tr>
                              </thead>
                              <tbody>
                    @foreach ($payments as $payment)
                                 <tr>
                                   
                                    <td>{{ $payment->name}}</td>
                                    <td>{{ $payment->cari}}</td>
                                   <td>{{ $payment->color->name}}</td>
                                
                                 
                                    <td>
                                              <button 
                                               type="button" 
                                               class="btn btn-primary" 
                                               data-bs-toggle="modal"
                                               data-id="{{ $payment->id }}"
                                               data-title="{{ $payment->name}}"
                                               data-cari="{{$payment->cari}}"
                                                data-color="{{ $payment->color->id}}"       
                                              
                                               data-bs-target="#editModal">
                                              Edit 
                                            </button>
                                </td>  
                                </tr>
                       
                    @endforeach
                    </tbody>
                    </table>
                  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModalLong">
  Add New Payment type
</button> 
                    <div class="text-center">
                        {!! $payments->links() !!}
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
        <h5 class="modal-title" id="exampleModalLongTitle">New Payment Type</h5>
        
        
       
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>{{ Form::open(array('route' => 'payments.store')) }}
      <div class="modal-body">
        {{ Form::label('name', 'Payment Type Name', array('for' => 'validationDefault01')) }}
            {{ Form::text('name', null, array('class' => 'form-control','required'=>'required')) }}
             {{ Form::label('cari', 'Current (cari hesaptan)', array('for' => 'validationDefault00')) }}
               {{Form::checkbox('cari',1,"", ['class'=>'form-control'])}}
            {{Form::select('color_id',$colors,"",['class'=>'form-control'])}}
           </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         {{Form::submit('Add New Payment Type',array('class'=>'btn btn-primary'))}}
       
      </div>
       {{form::close()}}
    </div>
    
  </div>
</div>
   
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Edit Payment Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>    {{ Form::open(array('route' => 'payments.guncel', 'class' => 'form','name' => 'edit',)) }}
      <div class="modal-body">
    
          <div class="form-group">
            <label for="recipient-name" class="col-form-label">Name:</label>
             {{ Form::text('name', null, array('class' => 'form-control','required'=>'required','id'=>'data-name')) }}
            
              {{Form::select('color_id',$colors,"",['class'=>'form-control','id'=>'data-color'])}}   
              {{Form::checkbox('cari',1,"", ['class'=>'form-control','id'=>'data-cari'])}}
            {{Form::hidden('id', '', ['id' => 'data-id','class'=>'form-control'])}}
            
                    </div>
           
             
          
       
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
           {{Form::submit('Edit Payment Type Name',array('class'=>'btn btn-primary'))}}
        
        
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
   var recipcari = button.data('cari')
   var recipcolor= button.data('color')
   
  
  // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
  // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.
  var modal = $(this)
  modal.find('.modal-name').text('Edit  ' + recipmname)
  modal.find('.modal-body #data-id').val(recipient)
  modal.find('.modal-body #data-name').val(recipmname)
  modal.find('.modal-body #data-cari').prop('checked',recipcari)
  modal.find('.modal-body #data-color').val(recipcolor) 
    
      
   
})
    </script>
@endsection