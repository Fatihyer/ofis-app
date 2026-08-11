<table>
  <thead>
   <tr>
                                   <th scope="col">@lang('app.date') </th>
                                    <th>@lang('app.file') </th>
                                  <th scope="col">@lang('app.tittle')</th>
                                 
                                  <th scope="col">@lang('app.payment')</th>
                                      <th scope="col">TL</th>
                                     <th scope="col">EUR</th>
                                    <th scope="col">USD</th>
                                   <th scope="col">@lang('app.amount') </th>
                                  <th scope="col"><i class="fa fa-money" aria-hidden="true"></i> </th>
                                    <th scope="col">@lang('app.service')</th>
                                  <th>from</th>
                                  <th>to</th>
                                   <th scope="col">@lang('app.detail')</th>
                                </tr>
   
    <tbody>
     @foreach ($harekets as $hareket)
                     <tr>
                                     <td>{{ date("d-m-Y   H:i", strtotime($hareket->tarih))}}</td>
                                   
                                    
                       <td>@if ($hareket->post_id>0)
                         {{ $hareket->post->id}}
                         {{$hareket->post->acente->name}}
                         @else 
                              
                          <?php
                         $dizi=substr($hareket->hareketable_type,4);
                         switch($dizi) {
                           case "Offset":
                            ?>
                             
                            <?php  
                          break;
                           case "Stock" :
                            ?>
                             
                            <?php    
                                break;  
                                }
                         ?>
                                  
                                    {{substr($hareket->hareketable_type,4)}}                  
                         @endif </td>
                                      <td>@if ($hareket->acente_id>0){{$hareket->acente->name}}
                                       @elseif($hareket->hareketable->alacakli->name) 
                                   {{$hareket->hareketable->alacakli->name}}
                                    ->
                                 <?php if (isset($hareket->hareketable->borclu->id)) { ?> 
 {{$hareket->hareketable->borclu->name}}
                              <?php } ?>
          
                                     @endif</td>
                                    
                                       <td>{{(($hareket->payment_id>0)?$hareket->payment->name:"")}}</td>
                                       <td>{{(($hareket->kur->id==2)?($hareket->amount):"00")}}</td>
                                      <td> {{(( $hareket->kur->id==1)?($hareket->amount):"00")}}</td>
                                         <td> {{(($hareket->kur->id==3)?($hareket->amount):"00")}}</td> 
                                      <td>{{$bakiye+=$hareket->amount}}</td>
                                     <td>{{$hareket->kur->name}}</td>
                       
                            
                                    <td>@if ($hareket->hareketable->servicetype_id>0){{ $hareket->hareketable->servicetype->name}}@endif </td>
                                    <td>@if ($hareket->hareketable->servicetype_id>0){{ $hareket->hareketable->from}}@endif </td>
                                    <td>@if ($hareket->hareketable->servicetype_id>0){{ $hareket->hareketable->target}}@endif </td>
                                    <td>{{ $hareket->aciklama}}</td>
                       
                                      
                               </tr>
                       
                       
                    @endforeach
      <tr><td><?php $query = DB::getQueryLog();
print_r($query);?></td></tr>
                           </tbody>
  
</table>