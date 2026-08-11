@extends('layouts.app')
@section('content')
   
        <div class="row">
            <div class="col-md-12 grid-margin">
              
                   <div class="card">
                        <a href="{{route('importExportView')}}" class="btn-success btn">Hareket Dökümü ekle</a>
                        <a href="{{ route('listexcelcard') }}" class="btn-danger btn"> Kredi kartı doküm al</a>
             
                     <table class="table">
                    <thead class="thead-dark">
                      <tr>
                     
                        <th scope="col">Tarih</th>
                        <th scope="col-6">Aciklama</th>
                          <th scope="col-6">Mahsup</th>
                          <th scope="col-6">Mahsup</th>
                        <th scope="col">Etiket</th>
                         <th scope="col">Banka hesabı</th>
                       
                         <th scope="col">Tutar</th>
                        <th width="%10">#</th>
                          <th scope="col">#</th>
                      </tr>
                    </thead>
                     <tbody>
            @foreach ($bankalist as $banka)
                    
                     <tr>   
                       {{Form::open(['route'=>array('addexceloffset',$banka->id), 'method' => 'post'] )}}
                     
                         
                       <td>{{Form::date('tarih',$banka->tarih,['class'=>'form-control form-control-sm','size'=>1])}}   </td>   
                       <td>{{Form::text('aciklama',$banka->aciklama,['class'=>'form-control form-control-sm','size'=>50])}}  </td> 
                   <td>  
                     <?php
                     $varsilan=149;
                     $result = substr($banka->aciklama, 0, 7); 
                     echo  substr($result,0,30);
                     switch ($result) {
                          case "Gelen S":
                              $varsilan=94;
                              break;
                         case "KESİNT":
                              $varsilan=94;
                              break;
                         
                          case "INT DÖ":
                               $varsilan=93;
                              break;
                          case "INT ARB":
                               $varsilan=93;
                              break;
                         case "GUILIN ":
                               $varsilan=121;
                              break;
                         case "8327-10":
                               $varsilan=135;
                              break;
                         case "SGK BOR":
                               $varsilan=199;
                              break;
                         case "VODAFO ":
                               $varsilan=152;
                              break;
                         case "KREDİL":
                               $varsilan=94;
                              break;
                         case "TTLKOM ":
                               $varsilan=151;
                              break;
                         case "-TTNET ":
                               $varsilan=151;
                              break;
                          case "K.Kart� ":
                               $varsilan=135;
                              break;
                         case "İGDAŞ":
                               $varsilan=150;
                              break;
                        case "BEDAŞE":
                               $varsilan=146;
                              break;   
                        case "3014-10":
                          $varsilan=395;
                        break; 

                        case "Gelen F":
                          $varsilan=94;
                        break;            

                        case "EKSTRE ":
                          $varsilan=93;
                        break;  
                        case "İntern":
                          $varsilan=93;
                        break; 
                        case "THY- ON":
                          $varsilan=735;
                        break; 
                        
                        
                        
                        case "CARIA N":
                          $varsilan=1999;
                        break;
                       
                        case "TRAVELL":
                          $varsilan=34;
                        break;
                        case "Cep Şu":
                          $varsilan=93;
                        break;
                        
                        case "S/DEM M":
                          $varsilan=85;
                        break;

                        
                        default:
                        $varsilan=205;
                          }

                     
                       
                     ?>
                     
                     {{Form::select('b_acente_id',$acentes,$varsilan,['class'=>' js-example-basic-single'])}}
                       </td>
                      <td>
                         <button type="button" class="btn btn-info" data-bs-toggle="collapse" data-bs-target="#demo{{$banka->id}}">+ Provider</button>
                                                      
  <div id="demo{{$banka->id}}" class="collapse ">
   {{Form::select('firma',$firmas)}} {{Form::text('acente_id_a')}}
  </div>
                         </td> 
                       <td>   {{$banka->etiket}}  </td>   
                     <td>   {{$banka->acente->name}}  
                       {{Form::hidden('a_acente_id',$banka->acente->id)}}
                       </td>   
                      
                       <td> {{Form::text('amount',$banka->tutar,['class'=>'form-control','size'=>10])}} {{$banka->kur->name}} 
                        {{Form::hidden('kur_id',$banka->kur_id)}}
                       
                       </td>  
                     <td>  
                       {{Form::submit('offsetle')}}</td>
                       {{Form::close()}}
                       <td>
                         <form  class="deleteinvoice" action="{{ route('exceldestroy', $banka->id) }}" method="POST">
                                          {{ method_field('DELETE') }}
                                          {{ csrf_field() }}
                                  
                               
                                          <button class="btn btn-danger" ><i class="fas fa-trash"></i></button>
                                          
                                        </form>
                       
                   
                       </td>  
                     
                     </tr> 
                    
                          @endforeach
                       </tbody>
                     </table>    
                       
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





@endsection

