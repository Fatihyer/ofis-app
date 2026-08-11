@extends('layouts.app')
@section('style')

 <style>
#kds ul {height:300px;overflow-y:scroll;}
</style>

@endsection
@section('title', '| New Group Proforma')
@section('style')
<link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/css/bootstrap-multiselect.css" type="text/css"/>

@endsection
@section('content')
<div id="kds">
  @if($groupinvoices->pennylane_customer_invoice_id)
        <div class="alert alert-info">
          Facture groupée déjà envoyée à Pennylane #{{ $groupinvoices->pennylane_customer_invoice_id }}. Seul le N° facture peut être modifié ici.
        </div>

        {{ Form::model($groupinvoices, array('route' => array('groupinvoices.update', $groupinvoices->id), 'method' => 'PUT')) }}
                 {{Form::label('resmi','N° facture Pennylane')}}
                 {{Form::text('resmi',null, array('class' => 'form-control', 'placeholder' => 'Ex: F2026-07-001')) }}
                 {{Form::submit('Enregistrer le N° facture',array('class'=>'btn btn-primary mt-3'))}}
        {{form::close()}}

        <hr/>
        <strong>Factures dans le groupe</strong>
        <div class="mt-2">
          @foreach($groupinvoices->invoices as $invoice)
            <a class="badge badge-secondary" href="{{ route('invoices.edit', $invoice->id) }}">#{{ $invoice->id }}</a>
          @endforeach
        </div>
  @else
        {{ Form::model($groupinvoices, array('route' => array('groupinvoices.update', $groupinvoices->id), 'method' => 'PUT')) }}   
                   
                          
                      
                          
                 {{Form::label('date','Date')}}   
                 {{Form::date('tarih',date('Y-m-d', strtotime($groupinvoices->tarih)), array('class' => 'form-control', 'id'=>'invoicetarih')) }}
                 {{Form::label('resmi','N° facture')}}   
                 {{Form::text('resmi',null, array('class' => 'form-control')) }} 
                 {{Form::label('invoicelist','Factures de la même société')}}
                 {{Form::select('invoicelist[]',$invoices,$groupinvoices->invoices,array('multiple'=>'multiple','class' => 'form-control','id'=>'multiselect'))}}
                 {{Form::label('sirket_id','Société')}}
                 {{Form::select('sirket_id',$sirkets,$groupinvoices->sirket_id,array('class' => 'form-control', 'required' => true))}}
                 {{Form::submit('Enregistrer',array('class'=>'btn btn-primary mt-3'))}}
        
{{form::close()}}
  @endif
</div>
            
@endsection
@section('footer')
@if(!$groupinvoices->pennylane_customer_invoice_id)
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
  <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/js/bootstrap-multiselect.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#multiselect').multiselect({
            buttonWidth: '400px'
        });
    });
</script>
<style type="text/css">
    .multiselect-container {
        width: 100% !important;
    }
</style>
@endif
@endsection
