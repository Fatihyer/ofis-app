@extends('layouts.app')

@section('title', '| Create New Post')

@section('content')
   <div class="container">
    <div class="row justify-content-md-center mt-5">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Create File</div>
                <div class="card-body">

    {{-- Using the Laravel HTML Form Collective to create our form --}}
        {{ Form::open(array('route' => 'posts.store')) }}

        <div class="form-group">
            {{ Form::label('title', 'Title') }}
            {{ Form::text('title', null, array('class' => 'form-control')) }}
            <br>
         
            {{ Form::label('body', 'File Description') }}
            {{ Form::textarea('body', null, array('class' => 'form-control')) }}
           </div>
             <div class="form-row">
                <div class="form-group col-md-6">
           {{ Form::label('start_date', 'From') }}
            {{ Form::date('start_date', null, array('class' => 'form-control', 'id' => 'start_date')) }}
            </div>
               <div class="form-group col-md-6">
          {{ Form::label('end_date_date', 'To') }}
            {{ Form::date('end_date', null, array('class' => 'form-control','id' => 'end_date')) }}
             </div>
               </div>
           <div class="form-row">
                <div class="form-group col-md-6">
           {{ Form::label('pax', 'Pax') }}
           {{ Form::number('pax', null,array('class' => 'form-control')) }}
            </div>
               <div class="form-group col-md-6">
          {{ Form::label('child', 'Child') }}
            {{ Form::selectRange('child', 0,40,null, array('class' => 'form-control')) }}
             </div>
               </div>       
                  
                  
            <div class="form-group">
             {{ Form::label('State', 'State') }}
            {{ Form::select('status_id', $status, null,array('class' => 'form-control')) }}
             </div>     
                     
          <div class="form-group">
            {{ Form::label('Acente', 'Acente') }}
            {{ Form::select('acente_id', $acente , null, ['class' => 'form-control js-example-basic-single']) }}
          
          
           
               
            
            </div>
             <div class="form-group">
               {{ Form::label('resmi', 'Resmi') }}
                {{ Form::checkbox('resmi', '1')  }} 
             <br>
             {{ Form::hidden('user_id', Auth::id())  }} 
            {{ Form::submit('Create File', array('class' => 'btn btn-success btn-lg btn-block')) }}
            {{ Form::close() }}
           
            </div>
        </div>
    </div>
</div>
     </div>

</div>      
 

@endsection
@section('footer')

<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>


<script>
$(document).ready(function() {
    $('.js-example-basic-single').select2();
});
</script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
      var startDateInput = document.getElementById('start_date');
      var endDateInput = document.getElementById('end_date');

      startDateInput.addEventListener('change', function () {
          var startDate = new Date(startDateInput.value);
          var endDate = new Date(startDate);
          endDate.setDate(startDate.getDate() + 1); // Adding one day

          // Format the date as "YYYY-MM-DD"
          var endDateFormatted = endDate.toISOString().split('T')[0];

          // Update the value of end_date input
          endDateInput.value = endDateFormatted;
      });
  });
</script>



@endsection