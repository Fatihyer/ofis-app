@extends('layouts.app')

@section('style')
 
@endsection
@section('content')

<div class="container">
    <div class="row justify-content-md-center mt-5">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Add Offset</div>
                <div class="card-body">
     {{ Form::open(array('route' => 'offsets.store','onsubmit'=>'setFormSubmitting()')) }}
    
        <div  class="form-row">
            <div class="col">
       {{ Form::label('tarih',__('app.date') , array('for' => 'validationDefault01')) }}
       {{ Form::date('tarih', date('Y-m-d'), array('class' => 'form-control')) }}
          </div>
           <div class="col">
              {{ Form::label('time','Time ', array('for' => 'validationDefault01')) }}
       {{ Form::time('time', null, array('class' => 'form-control')) }}
          </div>
            
          
     </div>            
                
     <div  class="form-row">
      {{ Form::label('from', 'From') }}
      {{ Form::select('a_acente_id', $acentes, isset($_GET['acente'])?$_GET['acente']:null, ['class' => 'form-control js-example-basic-single']) }}
    </div>   
      <div  class="form-row">
      {{ Form::label('to', 'To') }}
      {{ Form::select('b_acente_id', $acentes, isset($_GET['acente'])?$_GET['acente']:null, ['class' => 'form-control js-example-basic-single']) }}
    </div>
      <div  class="form-row">
       {{ Form::label('aciklama', 'Acıklama', array('for' => 'validationDefault01')) }}
       {{ Form::text('aciklama', null, array('class' => 'form-control')) }}
     </div> 
      <div  class="form-row">
       {{ Form::label('amount',__('app.amount'), array('for' => 'validationDefault01')) }}
       {{ Form::number('amount', isset($_GET['rakam'])?$_GET['rakam']:null, array('class' => 'form-control','step'=>'.01')) }}
     </div>
      <div  class="form-row">
       {{ Form::label('kur',__('app.exchange_name')) }}
       {{ Form::select('kur_id',$kurs," ", array('class' => 'form-control')) }}
     </div>            
                  
                  
   
  <button class="btn btn-primary" type="submit">@lang('app.save')</button>
{{form::close()}}

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
  
var formSubmitting = false;
var setFormSubmitting = function() { formSubmitting = true; };

window.onload = function() {
    window.addEventListener("beforeunload", function (e) {
        if (formSubmitting) {
            return undefined;
        }

        var confirmationMessage = 'It looks like you have been editing something. '
                                + 'If you leave before saving, your changes will be lost.';

        (e || window.event).returnValue = confirmationMessage; //Gecko + IE
        return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
    });
};  
  
</script>





@endsection
