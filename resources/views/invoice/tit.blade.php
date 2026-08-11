<!doctype html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta charset="UTF-8">
<title>{{$invoices->sirket->name}}</title>

<style type="text/css">
    * {
        font-family: Verdana, Arial, sans-serif;
    }
  pre,p {padding: 0;
  margin: 0;}
    table{
        font-size: 14px;
    }
    tfoot tr td{
        font-weight: bold;
      /*  font-size: small;*/
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
$lang[1]['proforma']='Proformat';
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

$lang[0]['MATRAH']='MOUNTANT';  
$lang[1]['MATRAH']='MONTANT';
$lang[2]['MATRAH']='MATRAH';  
  
  
function sayiyiYaziyaCevir($sayi, $kurusbasamak, $parabirimi, $parakurus, $diyez, $bb1, $bb2, $bb3) {
// kurusbasamak virgülden sonra gösterilecek basamak sayısı
// parabirimi = TL gibi , parakurus = Kuruş gibi
// diyez başa ve sona kapatma işareti atar # gibi

$b1 = array("", "Bir ", "İki ", "Üç ", "Dört ", "Beş ", "Altı ", "Yedi ", "Sekiz ", "Dokuz ");
$b2 = array("", "On ", "Yirmi ", "Otuz ", "Kırk ", "Elli ", "Altmış ", "Yetmiş ", "Seksen ", "Doksan ");
$b3 = array("", "Yüz ", "Bin ", "Milyon ", "Milyar ", "Trilyon ", "Katrilyon ");

if ($bb1 != null) { // farklı dil kullanımı yada farklı yazım biçimi için
$b1 = $bb1;
}
if ($bb2 != null) { // farklı dil kullanımı
$b2 = $bb2;
}
if ($bb3 != null) { // farklı dil kullanımı
$b3 = $bb3;
}

$say1="";
$say2 = ""; // say1 virgül öncesi, say2 kuruş bölümü
$sonuc = "";

$sayi = str_replace(",", ".",$sayi); //virgül noktaya çevrilir

$nokta = strpos($sayi,"."); // nokta indeksi

if ($nokta>0) { // nokta varsa (kuruş)

$say1 = substr($sayi,0, $nokta); // virgül öncesi
$say2 = substr($sayi,$nokta, strlen($sayi)); // virgül sonrası, kuruş

} else {
$say1 = $sayi; // kuruş yoksa
}

$son;
$w = 1; // işlenen basamak
$sonaekle = 0; // binler on binler yüzbinler vs. için sona bin (milyon,trilyon...) eklenecek mi?
$kac = strlen($say1); // kaç rakam var?
$sonint; // işlenen basamağın rakamsal değeri
$uclubasamak = 0; // hangi basamakta (birler onlar yüzler gibi)
$artan = 0; // binler milyonlar milyarlar gibi artışları yapar
$gecici;

if ($kac > 0) { // virgül öncesinde rakam var mı?

for ($i = 0; $i < $kac; $i++) {

$son = $say1[$kac - 1 - $i]; // son karakterden başlayarak çözümleme yapılır.
$sonint = $son; // işlenen rakam Integer.parseInt(

if ($w == 1) { // birinci basamak bulunuyor

$sonuc = $b1[$sonint] . $sonuc;

} else if ($w == 2) { // ikinci basamak

$sonuc = $b2[$sonint] . $sonuc;

} else if ($w == 3) { // 3. basamak

if ($sonint == 1) {
$sonuc = $b3[1] . $sonuc;
} else if ($sonint > 1) {
$sonuc = $b1[$sonint] . $b3[1] . $sonuc;
}
$uclubasamak++;
}

if ($w > 3) { // 3. basamaktan sonraki işlemler

if ($uclubasamak == 1) {

if ($sonint > 0) {
$sonuc = $b1[$sonint] . $b3[2 + $artan] . $sonuc;
if ($artan == 0) { // birbin yazmasını engelle
$sonuc = str_replace($b1[1] . $b3[2], $b3[2],$sonuc);
}
$sonaekle = 1; // sona bin eklendi
} else {
$sonaekle = 0;
}
$uclubasamak++;

} else if ($uclubasamak == 2) {

if ($sonint > 0) {
if ($sonaekle > 0) {
$sonuc = $b2[$sonint] . $sonuc;
$sonaekle++;
} else {
$sonuc = $b2[$sonint] . $b3[2 + $artan] . $sonuc;
$sonaekle++;
}
}
$uclubasamak++;

} else if ($uclubasamak == 3) {

if ($sonint > 0) {
if ($sonint == 1) {
$gecici = $b3[1];
} else {
$gecici = $b1[$sonint] . $b3[1];
}
if ($sonaekle == 0) {
$gecici = $gecici . $b3[2 + $artan];
}
$sonuc = $gecici . $sonuc;
}
$uclubasamak = 1;
$artan++;
}

}

$w++; // işlenen basamak

}
} // if(kac>0)

if ($sonuc=="") { // virgül öncesi sayı yoksa para birimi yazma
$parabirimi = "";
}

$say2 = str_replace(".", "",$say2);
$kurus = "";

if ($say2!="") { // kuruş hanesi varsa

if ($kurusbasamak > 3) { // 3 basamakla sınırlı
$kurusbasamak = 3;
}
$kacc = strlen($say2);
if ($kacc == 1) { // 2 en az
$say2 = $say2."0"; // kuruşta tek basamak varsa sona sıfır ekler.
$kurusbasamak = 2;
}
if (strlen($say2) > $kurusbasamak) { // belirlenen basamak kadar rakam yazılır
$say2 = substr($say2,0, $kurusbasamak);
}

$kac = strlen($say2); // kaç rakam var?
$w = 1;

for ($i = 0; $i < $kac; $i++) { // kuruş hesabı

$son = $say2[$kac - 1 - $i]; // son karakterden başlayarak çözümleme yapılır.
$sonint = $son; // işlenen rakam Integer.parseInt(

if ($w == 1) { // birinci basamak

if ($kurusbasamak > 0) {
$kurus = $b1[$sonint] . $kurus;
}

} else if ($w == 2) { // ikinci basamak
if ($kurusbasamak > 1) {
$kurus = $b2[$sonint] . $kurus;
}

} else if ($w == 3) { // 3. basamak
if ($kurusbasamak > 2) {
if ($sonint == 1) { // 'biryüz' ü engeller
$kurus = $b3[1] . $kurus;
} else if ($sonint > 1) {
$kurus = $b1[$sonint] . $b3[1] . $kurus;
}
}
}
$w++;
}
if ($kurus=="") { // virgül öncesi sayı yoksa para birimi yazma
$parakurus = "";
} else {
$kurus = $kurus . " ";
}
$kurus = $kurus . $parakurus; // kuruş hanesine 'kuruş' kelimesi ekler
}

$sonuc = $diyez . $sonuc . " " . $parabirimi . " " . $kurus . $diyez;
return $sonuc;
}
  
?>  
 <table width="100%">
    <tr>
        <td>
        {{$invoices->detail['tittle']}}
      <td>
      </td> 
     <tr>  
      <td>
        {{$invoices->detail['address']}}
          </td>  
     </tr>  
   <tr>  
      <td>
        {{$invoices->detail['postal']}}  {{$invoices->detail['city']}} {{$invoices->detail['country_name']}}
          </td>  
     </tr> 
   <tr>  
      <td>
        VD{{$invoices->detail['vd']}} {{$invoices->detail['vdno']}} 
          </td>  
     </tr>  
   <tr><td><br/></td></tr>
     <tr><td><br/></td></tr>
     <tr><td><br/></td></tr>
     <tr><td><br/></td></tr>
     <tr><td><br/></td></tr>
     <tr><td><br/></td></tr>

  
  </table>
<table>
  <tr>
  <td></td>
    <td>  {{date('d-m-Y',strtotime($invoices->tarih))}}</td>
  </tr>
  </table>
 

  <table width="100%">
    <thead style="background-color: lightgray;">
      <tr>
      
        <th width="50%"> </th>
        <th width="5%"></th>
        <th width="10%"> </th>
        <th width="10%"> </th>
        <th width="10%"> </th>
        <th width="10%"> </th>
        <th width="10%"> </th>
        <th width="10%"> </th>
        <th width="25%"></th>
          <th width="25%"></th>
      </tr>
    </thead>
    <tbody>
    <?php $kdv=array();?>
      @foreach($invoices->invoicedetail as $dokum)
      <tr>
        
        
        <td align="right">{{$dokum->comments}}</td>
      
        <td align="right"></td>
         <td align="right"></td>
         <td align="right"></td>
         <td align="right"></td>
         <td align="right"></td>
         <td align="right"></td>
       
        
        <td align="right">{{isset($dokum->amount)?number_format($dokum->amount, 2):""}} 
        </td>  
        <td>  {{(isset($dokum->amount)?$invoices->kur->short_name:"")}}</td>
      </tr>
        <?php 
        if (isset($dokum->kdv->percent))
        {
           if (!isset($kdv[$dokum->kdv->percent])) {$kdv[$dokum->kdv->percent]=0;}
          
          $kdv[$dokum->kdv->percent]+=$dokum->amount;
        }  
      
      ?> 
     @endforeach
      @for ($i =count($invoices->invoicedetail); $i <=22 ; $i++)
                              <tr> 
                              <td>
                                . <?php 
                                if (($dovizal==true)&&($i==20))
                                {
                                  echo "kur:", $dovizal->value;
                                }
                                ?>
                                
                              </td>
                            
                              <td></td>  
                              <td></td>  
                              <td></td>  
                              <td></td>  
                              <td></td>  
                              <td></td>  
                              <td></td>  
      
                                </tr>                     
       @endfor                     
      
    </tbody>

    <tfoot>
        <?php foreach ($kdv as $key=>$value) { ?>
        <?php  if ($dovizal) { ?>
      <tr>
        
          <td colspan="2" >% {{$key}}</td>
        
             <td >{{number_format(($value/(100+$key)*100*$dovizal->value),2)}}</td>
            <td>{{$lang[$invoices->lang]['VAT']}} % {{$key}}</td>
          <td >{{number_format($value-($value/(100+$key)*100*$dovizal->value),2)}} </td>
         <td>    TL</td>
         <td></td>
          <td></td>
            <td></td>
        </tr>
      <?php } ?>
        <tr>
         <td></td>
          <td></td>
            <td></td>
           <td></td>
          <td ></td>
          <td colspan="2" >{{$lang[$invoices->lang]['MATRAH']}} % {{$key}}</td>
        
             <td >{{number_format(($value/(100+$key)*100),2)}}</td>
           
         <td>     {{$invoices->kur->short_name}}</td>
        </tr>
      
        <tr>
         <td></td>
          <td></td>
            <td></td>
          <td colspan="2" ></td>
        
             <td ></td>
            <td>{{$lang[$invoices->lang]['VAT']}} % {{$key}}</td>
          <td >{{number_format($value-($value/(100+$key)*100),2)}} </td>
         <td>     {{$invoices->kur->short_name}}</td>
        </tr>
      
        <?php }?> 
      
        <tr>
            
           <td ></td>
          <td ></td>
            <td ></td>
          <td ></td>
          <td ></td>
             <td ></td>
          <td><strong>{{$lang[$invoices->lang]['TOTALAMOUNT']}}</strong></td>
            <td>{{number_format($invoices->amount, 2)}}</td>
             <td> {{$invoices->kur->short_name}}</td>
        </tr>
      <tr><td>
        {{sayiyiYaziyaCevir($invoices->amount,2,$invoices->kur->name,'kurus', '#','','','') }}

        </td>
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

<strong class="detail">{{$invoices->detail['not']}}</strong>
</body>
</html>