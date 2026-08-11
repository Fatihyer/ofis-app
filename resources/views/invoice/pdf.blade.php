<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{$invoices->sirket->name}}</title>

<style type="text/css">
    * {
        font-family: Verdana, Arial, sans-serif;
    }
  pre,p {padding: 0;
  margin: 0;}
    table{
        font-size: 11px;
    }
    tfoot tr td{
        font-weight: bold;
        font-size: small;
    }
    .gray {
        background-color: lightgray
    }
  h2 {padding: 0;
  margin: 0;}
    h1 {padding: 0;
  margin:0;}
  .siret {font-size: 10px;}  
  .red {color:red; font-weight:bold; }
  .blue {color:blue; font-weight:bold; }
  .detail{font-size: 11px;}  
</style>

</head>
<body>
<?php
///prepare language
$lang[0]['to']='To';  
$lang[1]['to']='Facturé à ';
$lang[2]['to']='Kime ';
  
$lang[0]['invoice']='Invoice';  
$lang[1]['invoice']='Facture';
$lang[2]['invoice']='Fatura '; 
  
$lang[0]['proforma']='Proforma';  
$lang[1]['proforma']='Proforma';
$lang[2]['proforma']='Proforma';   
  
$lang[0]['no']='No';  
$lang[1]['no']='No';
$lang[2]['no']='No ';  
  
$lang[0]['date']='Date';  
$lang[1]['date']='Date ';
$lang[2]['date']='Tarih';   

$lang[0]['description']='DESCRIPTION';  
$lang[1]['description']='DESCRIPTION';
$lang[2]['description']='ACIKLAMA';   

$lang[0]['VAT']='VAT';  
$lang[1]['VAT']='TVA';
$lang[2]['VAT']='KDV';  

$lang[0]['UNITPRICE']='UNIT PRICE';  
$lang[1]['UNITPRICE']='PRIX HT';
$lang[2]['UNITPRICE']='KDV\'siz'; 

$lang[0]['TOTAL']='TOTAL';  
$lang[1]['TOTAL']='TOTAL';
$lang[2]['TOTAL']='TOPLAM';  
  
$lang[0]['TOTALAMOUNT']='TOTAL AMOUNT';  
$lang[1]['TOTALAMOUNT']='TOTAL TTC';
$lang[2]['TOTALAMOUNT']='TOPLAM ';   

$officialInvoiceNumber = trim((string)($invoices->resmi ?? ''));
$hasOfficialInvoiceNumber = $officialInvoiceNumber !== '';
  
?>  
  <table width="100%">
    <tr>
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
        <td valign="top"><img src="{{asset($stroreFile.'/'.$invoices->sirket->logo)}}" alt="" width="150"/></td>
        <td align="right">
            <h3 class="blue">{{strtoupper($invoices->sirket->name)}}</h3>
           
                <pre>{{$invoices->sirket->info}}
                </pre>
       
        </td>
      
    </tr>

  </table>
<hr/>
  <table width="100%">
    <tr>
        <td>
      <p> {{$lang[$invoices->lang]['to']}}:  </p> 
         <h2> {{$invoices->detail['tittle']}}</h2>  
          <pre>{{$invoices->detail['address']}}</pre>
           {{$invoices->detail['city']}}/{{$invoices->detail['country_name']}}<br/>
            {{$invoices->detail['vd']}}/{{$invoices->detail['vdno']}}
      
      <td>
       <td>
     <h1 class="red">
      @if (isset($invoices->avoir)) 
      AVOIR </h1>
      <small class="small">Avoir de la facture {{$invoices->avoir}}</small>
      @else
      {{strtoupper($hasOfficialInvoiceNumber ? $lang[$invoices->lang]['invoice'] : $lang[$invoices->lang]['proforma'])}}
      </h1>
      @endif
       <br/>
    {{ $lang[$invoices->lang]['no']}}:<span class="red">{{$hasOfficialInvoiceNumber ? $officialInvoiceNumber : $invoices->id}}</span><br/>
      {{ $lang[$invoices->lang]['date']}}:{{date('d-m-Y',strtotime($invoices->tarih))}} <br/>  
      </td>   
    </tr>

  </table>

 

  <table width="100%">
    <thead style="background-color: lightgray;">
      <tr>
      
        <th width="50%"> {{ $lang[$invoices->lang]['description']}}</th>
        <th width="5%">{{$lang[$invoices->lang]['VAT']}} %</th>
        <th width="10%">{{$lang[$invoices->lang]['VAT']}} </th>
        <th width="10%">{{$lang[$invoices->lang]['UNITPRICE']}} </th>
        <th width="25%">{{$lang[$invoices->lang]['TOTAL']}}</th>
        
      </tr>
    </thead>
    <tbody>
    <?php $kdv=array();?>
      @foreach($invoices->invoicedetail as $dokum)
      <tr>
        
        <td>{{$dokum->comments}}</td>
        <td align="right">{{(isset($dokum->kdv->name)?$dokum->kdv->name:"")}}</td>
      
        <td align="right">{{((isset($dokum->amount)&&(isset($dokum->kdv->percent)))?number_format(($dokum->amount-$dokum->amount/(100+$dokum->kdv->percent)*100),2):"")}}</td>
         <td align="right">{{((isset($dokum->amount)&&(isset($dokum->kdv->percent)))?number_format(($dokum->amount/(100+$dokum->kdv->percent)*100),2):"")}}</td>
       
        
        <td align="right">{{isset($dokum->amount)?number_format($dokum->amount, 2):""}} {{(isset($dokum->amount)?$invoices->kur->short_name:"")}}</td>
      </tr>
        <?php 
        if (isset($dokum->kdv->percent))
        {
           if (!isset($kdv[$dokum->kdv->percent])) {$kdv[$dokum->kdv->percent]=0;}
          
          $kdv[$dokum->kdv->percent]+=$dokum->amount;
        }  
      
      ?> 
     @endforeach
      @for ($i =count($invoices->invoicedetail); $i <=25 ; $i++)
                              <tr> 
                              <td>.</td>
                              <td></td>
                              <td></td>  
                                </tr>                     
       @endfor                     
      
    </tbody>

    <tfoot>
        <?php foreach ($kdv as $key=>$value) { ?>
        
        <tr>
            <td ></td>
          <td ></td>
          <td ></td>
            <td align="right">{{$lang[$invoices->lang]['VAT']}} % {{$key}}</td>
            <td align="right">{{number_format($value-($value/(100+$key)*100),2)}} {{$invoices->kur->short_name}}</td>
        </tr>
        <?php }?> 
      
        <tr>
            <td ></td>
           <td ></td>
          
            <td colspan="2" align="right">{{$lang[$invoices->lang]['TOTALAMOUNT']}}</td>
            <td align="right" class="gray">{{number_format($invoices->amount, 2)}} {{$invoices->kur->short_name}}</td>
        </tr>
    </tfoot>
  </table>
   <pre class="siret">{{$invoices->sirket->info2}}</pre>

  
  @if ($invoices->account_id>0)
<p class="siret">
  
      
Bank:{{$invoices->account->name}} <br/>
SWIFT:{{$invoices->account->swift}} <br/>  
IBAN:{{$invoices->account->iban}}  <br/> 
   </p>
<pre class="siret">
{{$invoices->account->other}} 
</pre>
  @endif
 <hr/> 
<strong class="detail">{{$invoices->detail['not']}}</strong>
</body>
</html>
