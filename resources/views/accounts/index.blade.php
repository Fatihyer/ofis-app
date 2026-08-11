@extends('layouts.app')
@section('content')
 <div class="row">
          <div class="col-lg-12 grid-margin stretch-card">
                <div class="card ">
                    <div class="card-body">
                      
                  <h3 class="card-title">@lang('app.bankaccount')</h3>
                                          
                <p>Page {{ $accounts->currentPage() }} of {{ $accounts->lastPage() }}</p>
                <div class="table-responsive">    
                    <table class="table table-striped">
                      
       
                              <thead>
                                <tr>
                                  <th scope="col">Name</th>
                                  <th scope="col">iban</th>
                                  <th scope="col">Exchange</th>
                                  <th scope="col">Swift</th>
                                  <th scope="col">Account number</th>
                              
                                 
                                
                                </tr>
                              </thead>
                              <tbody>
                    @foreach ($accounts as $account)
                                 <tr>
                                   
                                    <td>{{ $account->name}}</td>
                                    <td>{{ $account->iban}}</td>
                                    <td>{{ $account->kur->name}}</td>
                                    <td>{{ $account->swift}}</td>
                                    <td>{{ $account->hesapno}}</td>
                                 
                                    <td>
                                              <button 
                                               type="button" 
                                               class="btn btn-primary" 
                                               data-bs-toggle="modal"
                                               data-id="{{ $account->id }}"
                                               data-title="{{ $account->name}}"
                                               data-kur="{{ $account->kur_id}}"
                                               data-swift="{{ $account->swift}}" 
                                               data-hesapno="{{ $account->hesapno}}"   
                                                data-iban="{{ $account->iban}}"          
                                                data-other="{{ $account->other}}" 
                                               data-bs-target="#editModal">
                                              Edit 
                                            </button>
                                </td>  
                                </tr>
                       
                    @endforeach
                    </tbody>
                    </table>
                      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModalLong">
  Add New Bank Account
</button>
                    <div class="text-center">
                        {!! $accounts->links() !!}
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
        <h5 class="modal-title" id="exampleModalLongTitle">New Bank Account</h5>
        
        
       
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>{{ Form::open(array('route' => 'accounts.store')) }}
      <div class="modal-body">
        {{ Form::label('name', 'Bank Account Name', array('for' => 'validationDefault01')) }}
            {{ Form::text('name', null, array('class' => 'form-control','required'=>'required')) }}
             {{ Form::label('kur_id', 'Exchange Name', array('for' => 'validationDefault00')) }}
               {{Form::select('kur_id',$kurs,'', ['class'=>'form-control'])}}
            
             {{ Form::label('Iban', 'Iban', array('for' => 'validationDefault00')) }}
               {{Form::text('iban', '', ['class'=>'form-control'])}}
            
              {{ Form::label('swift', 'Swift', array('for' => 'validationDefault00')) }}
               {{Form::text('swift', '', ['class'=>'form-control'])}}
        
             {{ Form::label('hesapno', 'Account No', array('for' => 'validationDefault00')) }}
               {{Form::text('hesapno', '', ['class'=>'form-control'])}}
        
        {{ Form::label('other', 'Other', array('for' => 'validationDefault00')) }}
               {{Form::textarea('other', '', ['class'=>'form-control'])}}
        
           </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
         {{Form::submit('Add Bank Account',array('class'=>'btn btn-primary'))}}
       
      </div>
       {{form::close()}}
    </div>
    
  </div>
</div>
   
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Edit Bank Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>    {{ Form::open(array('route' => 'accounts.guncel', 'class' => 'form','name' => 'edit',)) }}
      <div class="modal-body">
    
          <div class="form-group">
            <label for="recipient-name" class="col-form-label">Name:</label>
             {{ Form::text('name', null, array('class' => 'form-control','required'=>'required','id'=>'data-name')) }}
            
            {{ Form::label('kur_id', 'Exchange Name', array('for' => 'validationDefault00')) }}
               {{Form::select('kur_id',$kurs,'', ['class'=>'form-control','id'=>'data-kurid'])}}
            
             {{Form::hidden('id', '', ['id' => 'data-id','class'=>'form-control'])}}
            
             {{ Form::label('Iban', 'Iban', array('for' => 'validationDefault00')) }}
               {{Form::text('iban', '', ['class'=>'form-control','id'=>'data-iban'])}}
            
              {{ Form::label('swift', 'Swift', array('for' => 'validationDefault00')) }}
               {{Form::text('swift', '', ['class'=>'form-control','id'=>'data-swift'])}}
        
             {{ Form::label('hesapno', 'Account No', array('for' => 'validationDefault00')) }}
               {{Form::text('hesapno', '', ['class'=>'form-control','id'=>'data-hesapno'])}}
            
             {{ Form::label('other', 'Other', array('for' => 'validationDefault00')) }}
               {{Form::textarea('other', '', ['class'=>'form-control','id'=>'data-other'])}}
        
        
            </div>
           
             
          
       
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
           {{Form::submit('Edit Bank Account',array('class'=>'btn btn-primary'))}}
        
        
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
   var recipkur = button.data('kur')
   var recipswift= button.data('swift')
     var reciphesapno= button.data('hesapno')
      var recipiban= button.data('iban')
       var recipother= button.data('other')
  
  // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
  // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.
  var modal = $(this)
  modal.find('.modal-name').text('Edit  ' + recipmname)
  modal.find('.modal-body #data-id').val(recipient)
  modal.find('.modal-body #data-name').val(recipmname)
  modal.find('.modal-body #data-kurid').val(recipkur) 
  modal.find('.modal-body #data-swift').val(recipswift) 
   modal.find('.modal-body #data-iban').val(recipiban)       
  modal.find('.modal-body #data-hesapno').val(reciphesapno)  
     modal.find('.modal-body #data-other').val(recipother)     
      
   
})
    </script>
@endsection