@extends('layouts.app')

@section('style')
@endsection
@section('content')

<div class="container">
    <div class="row justify-content-md-center mt-6">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Add Multi Offset</div>
                <div class="card-body">
     {{ Form::open(array('route' => 'multioffsetsstore')) }}
    
        <div  class="form-row">
            <div class="col">
       {{ Form::label('tarih',__('app.date') , array('for' => 'validationDefault01')) }}
       {{ Form::date('tarih', null, array('class' => 'form-control')) }}
          </div>
           <div class="col">
              {{ Form::label('time','Time ', array('for' => 'validationDefault01')) }}
       {{ Form::time('time', null, array('class' => 'form-control')) }}
          </div>
            
          
     </div>            
                
     <div  class="form-row">
      {{ Form::label('from', 'Kimden') }}
      {{ Form::select('a_acente_id', $acentes, null, ['class' => 'form-control js-example-basic-single']) }}
    </div>  
     <div  class="form-row">
       {{ Form::label('kur',__('app.exchange_name')) }}
       {{ Form::select('kur_id',$kurs," ", array('class' => 'form-control')) }}
     </div>  
                  
                  
      <div  class="form-row">
         <div class="col">
      {{ Form::label('to', 'Kime') }}
      {{ Form::select('b_acente_id[]', $acentes, null, ['class' => 'form-control js-example-basic-single']) }}
         </div>
        <div class="col">
       {{ Form::label('aciklama', 'Acıklama', array('for' => 'validationDefault01')) }}
       {{ Form::text('aciklama[]', null, array('class' => 'form-control')) }}
        </div>
      
         <div class="col">
       {{ Form::label('amount',__('app.amount'), array('for' => 'validationDefault01')) }}
       {{ Form::number('amount[]', null, array('class' => 'form-control topla','step'=>'.01', 'id'=>'rakam0')) }}
         </div> 
        
          <div class="col-md-1">
       {{ Form::label('b/a', 'b/a') }}
        
       {{ Form::select('ab[]', [1=>'ODEME',2=>'TAHSILAT'],null, array('class' => 'form-control','onchange'=>'getval(this,0)')) }}
        </div>
            <div class="col-md-1">
            <a class="btn btn-success btn-sm" href="#" role="button" id="addRow">
                       <i class="fa fa-plus" aria-hidden="true"></i>Row
                      </a> 

        </div>
     </div>
      <div id="cariekleme">
            
      </div>
                 
          <div id="result"> </div>        
                  
   
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
  
$(function(){  
var sayiekleme=1;  
 $('#addRow').click(function(){  

    var div = document.createElement('div');
    sayi=sayiekleme;
    div.className = '';
   
    strtext='<div class="form-row" id="sayi'+sayi+'">'+
                
                       ' <div class="col">{{ Form::select("b_acente_id[]",$acentes,null, array("class" => "form-control js-example-basic-single")) }} '+
                      '</div>'+
                     ' <div class="col">{{ Form::text("aciklama[]","", array("class" => "form-control","required" => "required")) }}</div>'+
                    '  <div class="col"><input type="number" class="form-control topla" id="rakam'+sayi+'" name="amount[]" required="required" step=".01"/></div>'+
                       '<div class="col-md-1"><select name="ab[]" class="form-control" onchange=getval(this,'+sayi+')><option value=1>ODEME</option><option value=2>TAHSILAT</option></select></div>'+
                   ' <div  class="col-md-1" ><a class="btn btn-danger btn-sm" href="#" role="button" onclick="removeRow('+sayi+')"> <i class="fa fa-trash" aria-hidden="true"></i></a></div></div>'
                      ;
        
      div.innerHTML =strtext;     
    
        
      
    document.getElementById('cariekleme').appendChild(div);
   sayiekleme++;
    $('.js-example-basic-single').select2();
});
});  

function removeRow(div) {
    document.getElementById('sayi'+div).remove();
}
  
$(document).on("change", ".topla", function()
              {
              toplama();
}
              );
  
function toplama() {
    var sum = 0;
     $(".topla").each(function(){
        sum += +$(this).val();
    });
    $("#result").html(sum);
}  
  
  
function getval(deger,sayi)
  {
    
    fatur=$("#rakam"+sayi).val();
      if((fatur > 0) && (deger.value==2)) {
         $("#rakam"+sayi).val((0 - fatur));
    }
      if ((fatur < 0) && (deger.value==1)) {
         $("#rakam"+sayi).val(( fatur* -1));
        
        
    }
   toplama();
  }


</script>
<script>
$(document).ready(function() {
    $('.js-example-basic-single').select2();
});
</script>






@endsection
