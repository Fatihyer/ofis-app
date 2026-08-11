
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
  <title>VOUCHER</title>
  <style>
  @media print{@page {size: landscape}};
  body {
    font-family:Verdana, Geneva, Tahoma, sans-serif;
}
h1 {
    font-family:Verdana, Geneva, Tahoma, sans-serif;
}
 table, th, td {
    font-family:Verdana, Geneva, Tahoma, sans-serif;
  border: 4px solid grey;
  border-collapse: collapse;
  padding: 5px;

}
span{
color: green;

font-style: oblique;

}
.yazi{
    color: gray;
}

img {
        vertical-align: middle;
      }

      .column {
  float: left;
  width: 50%;
}

/* Clear floats after the columns */
.row:after {
  content: "";
  display: table;
  clear: both;
}      

  </style>
</head>
<body>


    <?php  
    switch ($_SERVER['HTTP_HOST']) {
      case 'ofis.tittravel.com':
      $stroreFile ='imagestit'; 
        break;
        case 'ofis.gabaytravel.com':
      $stroreFile ='imagesgabay'; 
        break;
      default:
      $stroreFile ='images';
        break;
    }
?>
<div class="row">
    <div class="column"><h1>HOTEL VOUCHER </h1>   </div>
    <div class="column"><img src="{{asset($stroreFile.'/'.$sirket->logo)}}" alt="" width="150"/></div>
  </div>



<table>
  
<tr>
<td colspan="3">
    <span class="yazi">Booking no:</span> {{$hotelrez->id}}    
<h2>{{$hotel->name}}</h2>

{{$hotel->address}}
{{$hotel->city}}
{{$hotel->ulke->country_name}}
<br>
Tel:
{{$hotel->tel}}
</td>
<td rowspan="2">
  
{{$sirket->name}} <br>
{{$sirket->info}} <br>
Tel: {{$sirket->tel}} <br>
Email:{{$sirket->email}}<br>


</td>
</tr>
<tr>
<td>
<span class="yazi">Check in </span> <br>
{{date('d-m-Y', strtotime($hotelrez->from))}}
</td>
<td>
<span class="yazi">Check Out</span><br>
{{date('d-m-Y', strtotime($hotelrez->to))}}

</td>
<td>
    <span class="yazi">Booking Detail </span> <br>
Rez Date:{{date('d-m-Y', strtotime($hotelrez->updated_at))}}<br>
Type: 
@if ($hotelrez->sng) 
{{$hotel->sng}} Single,
 @endif
@if ($hotelrez->dbl) 
:{{$hotelrez->dbl}} Double ,
 @endif
@if ($hotelrez->trp)
{{$hotelrez->trp}} Triple,
 @endif
@if ($hotelrez->qtr) 
{{$hotelrez->qtr}} Quatruple ,
@endif
@if ($hotelrez->fam)
 {{$hotelrez->fam}} Family ,
 @endif
 <br>
 {{$hotelrez->servicetype->name}} 

</td>

</tr>
<tr>
<td colspan="3">
    <span class="yazi">  Guest Information : </span> <br>
    @foreach ($hotelrez->post->client as $clients )
    {{$clients->title}}
    {{$clients->name}}
    {{$clients->surname}}
    <br>
    @endforeach

</td>
<td><span>CONFIRMED</span></td>
</tr>

</table>


</body>
</html>