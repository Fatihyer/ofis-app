<!-- Example usage in show-pdf.blade.php -->
<style>
    body {
        font-family: 'Calibri', sans-serif;
    }
</style>

@php
use Carbon\Carbon;
  
    $dayOfWeek = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    $monthNames = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
    ];

@endphp
<img src="{{asset('images'.'/'.$sirket->logo)}}"/>


<p>
    Cher partenaire,
    <br>
    <strong>Nous avons le plaisir de vous confirmer la mission (ci-dessous)</strong>
</p>


@if($transfers)
<table style="width: 100%;border: 1px solid black; border-collapse: collapse;"  >
    <tr  align="center" >
        <td  border="1" colspan="2" style="border: 1px solid black; border-collapse: collapse;">
            <strong>  {{
            $dayOfWeek[Carbon::parse($transfers->start_date)->dayOfWeek] . ' ' .
            Carbon::parse($transfers->start_date)->day . ' ' .
            $monthNames[Carbon::parse($transfers->start_date)->month] . ' ' .
            Carbon::parse($transfers->start_date)->year . ' ' 
            
        }}</strong>
        </td>
    </tr>
    <tr>   
    <td style="border: 1px solid black; border-collapse: collapse;"><i> Prise en charge:</i> <br> <strong> {{ date('H:i', strtotime($transfers->start_date))}} </strong></td>
    <td style="border: 1px solid black; border-collapse: collapse;"><i>Heure  de dépose:</i> <br> {{ date('H:i', strtotime($transfers->end_date))}}</td>
    </tr> 
    <tr>   
        <td style="border: 1px solid black; border-collapse: collapse;"><i> Depart de:</i> <br> <strong> {{$transfers->from}} <strong></td>
        <td style="border: 1px solid black; border-collapse: collapse;"><i>A:</i> <br> {{$transfers->target}}</td>
    </tr>  
    <tr>   
        <td style="border: 1px solid black; border-collapse: collapse;"><i> Vehicule:</i> <br> {{$transfers->vehicule->name}}</td>
        <td style="border: 1px solid black; border-collapse: collapse;"> <i>Chauffeur:</i> <br> {{$transfers->driver->name}}</td>
    </tr>  
    
    <tr>  
        <td style="border: 1px solid black; border-collapse: collapse;"><i> Pax:</i> <br> {{$transfers->pax}}</td> 
        <td style="border: 1px solid black; border-collapse: collapse;"><i>Clients:</i> <br>
            @foreach($transfers->post->client as $clients) 
           {{$misafir =$clients->name." ".$clients->surname." ".$clients->tel}}</p>
          @endforeach
            
            
           </td>
        
    </tr> 
    <tr>   
        <td colspan="2" style="border: 1px solid black; border-collapse: collapse;"><i> Details:</i> <br> {{$transfers->comments}}</td>
        
    </tr>  

</table>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
SERVICE DE TRANSPORT PUBLIC DE PERSONNES - BILLET COLLECTIF
(Arrêté du 14 février 1986 – Article.5) et ordre de mission (Arrêté du 6 janvier 1993 – Article 3)
 





@else
    <p>No transfer data available.</p>
@endif


{{$sirket->email}}
<br><br><br>
<i> <small>
{{$sirket->name}}
{{$sirket->info}}
{{$sirket->info2}}
</small>
</i>