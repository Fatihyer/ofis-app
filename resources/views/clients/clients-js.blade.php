<script>
  
$(function(){  
var sayiekleme=1;  
 $('#addRow').click(function(){  

    var div = document.createElement('div');
    sayi=sayiekleme;
    div.className = '';
   
    strtext='<div class="row" id="sayi'+sayi+'">'+
                '<div class="form-group col-sm-2">'+
                       ' {{ Form::select("title[]",["Mr"=>"Mr","Mrs"=>"Mrs","Chld"=>"Chld","BB"=>"BB","Dr"=>"Dr","Prof"=>"Prof"],"", array("class" => "form-control")) }} {{Form::hidden("post_id[]",$post->id)}} '+
                      '</div>'+
                     ' <div class="col-md-2">{{ Form::text("name[]","", array("class" => "form-control","required" => "required")) }}</div>'+
                    '  <div class="col-md-2">{{ Form::text("surname[]","", array("class" => "form-control")) }}</div>'+
                     ' <div class="col-md-2">{{ Form::text("tel[]","", array("class" => "form-control")) }}</div>'+
                    '  <div class="col-md-2">{{ Form::text("email[]","", array("class" => "form-control")) }}</div>'+
                     ' <div  class="col-md-2" ><a class="btn btn-primary btn-sm" data-bs-toggle="collapse" href="#collapseExample'+sayi+'" role="button" aria-expanded="false" aria-controls="collapseExample">'+
                     '  <i class="fa fa-commenting-o" aria-hidden="true"></i></a>'+
                       '  <a class="btn btn-danger btn-sm" href="#" role="button" onclick="removeRow('+sayi+')"> <i class="fa fa-trash" aria-hidden="true"></i></a></div></div>'+
                   '<div class="collapse" id="collapseExample'+sayi+'">{{ Form::text("comments[]","#", array("class" => "form-control")) }} '+
                  ' <br/></div>';
        
      div.innerHTML =strtext;     
    
        
      
    document.getElementById('isimekleme').appendChild(div);
   sayiekleme++;
});
});  

function removeRow(div) {
    document.getElementById('sayi'+div).remove();
}




</script>