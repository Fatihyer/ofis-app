<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ strtoupper($invoices->sirket->name) }}</title>

<style type="text/css">
    @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');

    body {
        font-family: 'Roboto',  Helvetica, sans-serif;
    }
    td, small {
        font-family: 'Roboto', Helvetica,  sans-serif;
        font-size: 11px;
    }
    .gray {
        background-color: lightgray;
    }
    .bold {
        font-weight: bold;
    }
    .header, .footer {
        text-align: center;
        margin-top: 15px;
    }

    .padded-header th {
        padding: 10px;
    }
   
    table {
        width: 100%;
    }

    .styled-table  {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        border: 1px solid lightgray;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .styled-table th, .styled-table td {
        border: 1px solid lightgray;
        padding: 5px;
       


    }
    .styled-table th, .sagla {
    text-align: right;
    }    
    .styled-table th {
        background-color: lightgray;
        text-align: center;
        font-weight: 700;
    }

    .styled-table .total-row td {
        font-weight: bold;
        border-top: 2px solid black;
    }

    .empty-cell {
        border: none;
    }

    .styled-table td:first-child {
        text-align: left;
    }

    .styled-table th:first-child {
        border-top-left-radius: 10px;
    }

    .styled-table th:last-child {
        border-top-right-radius: 10px;
    }

    .styled-table td:last-child {
        border-bottom-right-radius: 10px;
    }

    .styled-table td:first-child {
        border-bottom-left-radius: 10px;
    }

</style>

</head>
<body>
    <?php
    $lang = [
        ['to' => 'To', 'invoice' => 'Invoice', 'no' => 'No', 'date' => 'Date', 'duedate' => 'Due Date', 'description' => 'DESCRIPTION', 'VAT' => 'VAT', 'UNITPRICE' => 'UNIT PRICE', 'TOTAL' => 'TOTAL', 'bank_details' => 'Bank details', 'TOTALAMOUNT' => 'TOTAL AMOUNT'],
        ['to' => 'Facturé à ', 'invoice' => 'Facture', 'no' => 'Numéro', 'date' => 'Date ', 'duedate' => 'Date échéance', 'description' => 'DESCRIPTION', 'VAT' => 'TVA', 'UNITPRICE' => 'PRIX HT', 'TOTAL' => 'TOTAL', 'bank_details' => 'Coordonnées bancaires société :', 'TOTALAMOUNT' => 'TOTAL TTC'],
        ['to' => 'Kime', 'invoice' => 'Fatura', 'no' => 'No', 'date' => 'Tarih', 'duedate' => 'Ödeme Tarihi', 'description' => 'ACIKLAMA', 'VAT' => 'KDV', 'UNITPRICE' => 'KDV\'siz', 'TOTAL' => 'TOPLAM', 'bank_details' => 'Banka Bilgileri', 'TOTALAMOUNT' => 'TOPLAM']
    ];
    switch ($_SERVER['HTTP_HOST']) {
      case 'ofis.tittravel.com':
      $stroreFile ='imagestit'; 
        break;
      default:
      $stroreFile ='images';
        break;
    }

    $officialInvoiceNumber = trim((string)($invoices->resmi ?? ''));
    $hasOfficialInvoiceNumber = $officialInvoiceNumber !== '';
    ?>
<table>
    <tr>
        <td style="border: 0px;">
            <h1>{{ strtoupper($invoices->sirket->name) }}</h1>
            <p>
            {{ $invoices->sirket->info }}<br/>
            {!! nl2br($invoices->sirket->info2) !!}<br/>
            {{ $invoices->sirket->vat }}<br/>
            Email:  {{ $invoices->sirket->email }}<br/>
            Tel: {{ $invoices->sirket->tel }}<br/>
            
             
              
            </p>
        </td>
        <td style="text-align: right; vertical-align: top; border: 0px;">
            <img src="{{ asset($stroreFile.'/'.$invoices->sirket->logo) }}" alt="Logo" style="max-width: 100%; height: auto;"/> 
        </td>
    </tr>

    <tr>
        <td>
            <h1>
         
      @if (isset($invoices->avoir)) 
      AVOIR </h1>
      <small class="small">Avoir de la facture {{$hasOfficialInvoiceNumber ? $officialInvoiceNumber : $invoices->id}}</small>
      @else
      {{ strtoupper($hasOfficialInvoiceNumber ? $lang[$invoices->lang]['invoice'] : 'Proforma') }}
      </h1>
      @endif




        </td>
        <td>
            <strong>{{ $lang[$invoices->lang]['to'] }}:</strong><br/>
            <h2>{{ $invoices->detail['tittle'] }}</h2>
            <p>{{ $invoices->detail['address'] }}<br/>
            {{ $invoices->detail['city'] }}/{{ $invoices->detail['country_name'] }}</p>
        </td>
    </tr>
</table>

<table class="styled-table">
    <thead class="padded-header">
        <tr class="gray">
            <th style="width: 10%;">{{ $lang[$invoices->lang]['no'] }}</th>
            <th style="width: 20%;">{{ $lang[$invoices->lang]['date'] }}</th>
            <th style="width: auto;">Code Client</th>
            <th style="width: 20%;">{{ $lang[$invoices->lang]['duedate'] }}</th>
            <th style="width: auto;">Mode de Reglement</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="border-bottom: 1px solid lightgray;"> @if (isset($invoices->avoir))
                {{$invoices->avoir}}
                 @else 
                 {{$hasOfficialInvoiceNumber ? $officialInvoiceNumber : $invoices->id}}
                  @endif</td>
            <td style="border-bottom: 1px solid lightgray;">{{ date('d-m-Y', strtotime($invoices->tarih)) }}</td>
            <td style="border-bottom: 1px solid lightgray;">{{ $invoices->acente->id }}</td>
            <td style="border-bottom: 1px solid lightgray;">{{ date('d/m/Y', strtotime($invoices->tarih)) }}</td>
            <td style="border-bottom: 1px solid lightgray;"></td>
        </tr>
    </tbody>
</table>

<br><br>

@php
$remise = 0;
$remisetva = 0;
@endphp

<table class="styled-table">
    <thead class="padded-header">
        <tr class="gray">
            <th>{{ $lang[$invoices->lang]['description'] }}</th>
            <th>{{ $lang[$invoices->lang]['UNITPRICE'] }}</th>
            <th>{{ $lang[$invoices->lang]['VAT'] }} %</th>
            <th>{{ $lang[$invoices->lang]['TOTAL'] }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoices->invoicedetail as $item)
        <tr>
            <td>{{ $item->comments }}</td>
            <td>{{ number_format($item->amount / ((1 + (isset($item->kdv->percent) ? $item->kdv->percent : 0) / 100)), 2) }}</td>
            <td>{{ isset($item->kdv->percent) ? $item->kdv->percent : '0' }}</td>
            <td>{{ number_format($item->amount, 2) }}</td>
            
            @if (isset($invoices->avoir)) 
            @php
            if ($item->amount > 0) {
                $remise += $item->amount;
                $remisetva += isset($item->kdv->percent) ? $item->kdv->amount - ($item->amount / (1 + ($item->kdv->percent / 100))) : 0;
            }
            @endphp
            @else
            @php
            if ($item->amount < 0) {
                

          
                $remise += $item->amount;
                $remisetva += isset($item->kdv->percent) ? $item->kdv->amount - ($item->amount / (1 + ($item->kdv->percent / 100))) : 0;
              
            }
            @endphp
            @endif
          
        </tr>
        @endforeach
        @for($i = count($invoices->invoicedetail); $i < 9; $i++)
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
        @endfor
    </tbody>
</table>

<hr/>
<small style="font-weight: bold; font-style: italic; text-decoration: underline;">
    Récapitulatif des échéances: 
</small>

<table class="styled-table">
    <thead class="padded-header">
        <tr class="gray">
            <th style="width: 10%;">{{ $lang[$invoices->lang]['duedate'] }}</th>
            <th style="width: auto;">Mode de Paiment</th>
            <th style="width: 20%;">{{ $lang[$invoices->lang]['TOTALAMOUNT'] }}</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ date('d/m/Y', strtotime($invoices->tarih)) }}</td>
            <td>{{ $invoices->detail['not'] }}</td>
            <td class="bold">{{ number_format($invoices->amount, 2) }} {{ $invoices->kur->short_name }}</td>
        </tr>
    </tbody>
</table>

<br>
<small style="font-size: 10px;">
    En cas de retard de paiement, une pénalité égale à 3 fois le taux d'intérêt légal sera exigible (Décret 2009-138 du 9 février 2009).
Pour les professionnels, une indemnité minimum forfaitaire de 40 euros pour frais de recouvrement sera exigible (Décret 2012-1115 du 9 octobre 2012)
</small>

<br>
<table>
    <tr>
        <td style="width: 30%;">
            <table class="styled-table">
                <thead class="padded-header">
                    <tr>
                        <th>Taux</th>
                        <th>Base Hors Taxe</th>
                        <th>Montant TVA</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $groupedItems = $invoices->invoicedetail->groupBy(function($item) {
                        return isset($item->kdv->percent) ? $item->kdv->percent : 0;
                    });
                    $totalWithVAT = 0;
                    $toplamkdvsiz=0;
                    $oranlar = array();
                    @endphp

                    @foreach($groupedItems as $percent => $items)
                    @php
                        $totalVAT = 0;
                        $totalWithoutVAT = 0;
                    @endphp

                    @foreach($items as $item)
                    @php
                        $amountWithoutVAT = $item->amount / (1 + ($percent / 100));
                        $totalVAT += $item->amount - $amountWithoutVAT;
                        $totalWithoutVAT += $amountWithoutVAT;
                        
                    @endphp
                    @endforeach

                    <tr>
                        <td>{{ $percent }}%</td>
                        <td>{{ number_format($totalWithoutVAT, 2) }} </td>
                        <td>{{ number_format($totalVAT, 2) }}</td>
                    </tr>
                    @php
                     $toplamkdvsiz+=$totalWithoutVAT;
                     $oranlar[$percent]=$totalVAT;
                    @endphp
                    @endforeach
                </tbody>
            </table>
        </td>
        <td style="width: 40%;"></td>
        <td style="width: 30%;">
            <table class="styled-table">
                <tr>
                    <th class="gray">TOTAL HT</th>
               
                    @if ($remise!=0)
                         @if ($remise<0)

                    <td class="sagla">{{ number_format($toplamkdvsiz+($remisetva), 2) }}
           
                       @else
                       <td class="sagla">{{ number_format($toplamkdvsiz+($remisetva), 2) }}
               
                    @endif

                 </td>
                    @else
                    <td class="sagla" align="right" >{{ number_format($toplamkdvsiz, 2) }}</td>  
                    @endif

                </tr>
                
                @if ($remise!=0)
                <tr>
                    <th class="gray">Remise {{ number_format($remise/(($invoices->amount-$remise)), 2)*100 }}%

                  

                    </th>
                    <td class="sagla">{{ number_format($remise, 2) }}</td>
                </tr>

                <tr>
                    <th class="gray">Remise HT</th>
                    <td class="sagla">
                    @if (isset($invoices->avoir))
                    {{ number_format($remisetva, 2)*-1 }}
                    
                    @else
                            @if ($remise<0)        
                        -{{ number_format($remisetva, 2) }}
                            @else
                            {{ number_format($remisetva, 2) }}
                            @endif

                    @endif
                    </td>
                </tr>
                @endif

                <tr>
                    <th class="gray">Total HT Net</th>
                    <td class="sagla">{{ number_format($toplamkdvsiz, 2) }}</td>
                </tr>
                @foreach ($oranlar as $key => $item)
                    
               
                <tr>
                    <th class="gray">Total TVA {{$key}} % </th>
                    <td class="sagla">{{ number_format($item, 2) }}</td>
                </tr>

                @endforeach
                <tr>
                    <th class="gray">Total TTC</th>
                    <td class="sagla">{{ number_format($invoices->amount, 2) }} {{ $invoices->kur->short_name }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

@if ($invoices->account_id > 0)
<small>
    <strong>{{ $lang[$invoices->lang]['bank_details'] }}</strong><br/>
    {{ $invoices->account->name }}<br/>
    IBAN: {{ $invoices->account->iban }}<br/>
    SWIFT: {{ $invoices->account->swift }}<br/>
</small>
@endif

<div class="footer">
    <small style="font-size: 10px;">{{ $invoices->sirket->info2 }}</small>
</div>

</body>
</html>
