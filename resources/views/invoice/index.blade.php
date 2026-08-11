@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-lg-12 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Factures</h4>
                    Page {{ $invoices->currentPage() }} of {{ $invoices->lastPage() }}
                    <div class="table-responsive">
                        <form action="{{ route('invoices.index') }}" method="GET" class="mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="sirket_id">Société</label>
                                    <select name="sirket_id" id="sirket_id" class="form-control" onchange="this.form.submit()">
                                        <option value="">Toutes les sociétés</option>
                                        @foreach($sirkets as $id => $name)
                                            <option value="{{ $id }}" {{ (string)$selectedSirketId === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label for="search">Recherche</label>
                                    <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="N° facture, dossier, montant...">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button class="btn btn-primary mr-2" type="submit">Filtrer</button>
                                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
                                </div>
                            </div>
                        </form>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th scope="col">@sortablelink('sirket_id','Société')</th>
                                    <th scope="col">@sortablelink('id','Proforma No')</th>
                                    <th scope="col">Dossier</th>
                                    <th scope="col">@sortablelink('acente_id','Client')</th>
                                    <th scope="col">@sortablelink('tarih','Date')</th>
                                    <th scope="col">Montant</th>
                                    <th scope="col">@sortablelink('resmi','Invoice #')</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoices as $invoice)
                                <tr>
                                    <td>{{ optional($invoice->sirket)->name }}</td>
                                    <th scope="row"><a href="{{ route('invoices.show', $invoice->id) }}"><b>{{ $invoice->id}}</b></a></th>
                                    <td><a href="{{ route('posts.show', $invoice->post_id ) }}"><b>{{ optional($invoice->post)->id }}</b></a></td>
                                    <td><a href="{{ route('acentes.show', $invoice->acente_id ) }}"><b>{{ isset($invoice->acente->name)?$invoice->acente->name:""}}</b></a></td>
                                    <td>{{ date("d-m-Y", strtotime($invoice->tarih))}}</td>
                                    <td>{{ $invoice->amount}} {{ $invoice->kur->short_name }}</td>
                                    <td>
                                    @if (isset($invoice->avoir)) 
                                    AVOIR {{ $invoice->avoir }} 
                                    @else
                                    FACTURE
                                    {{ $invoice->resmi }}
                                    @endif
                                </td>
                                    <td>
                                        <div class="btn-group btn-group-toggle">
                                            <a href="{{ route('invoices.edit',$invoice->id)}}" class="btn btn-primary"><i class="fas fa-edit"></i></a>
                                            <a class="btn btn-success" href="{{ route('invoicetopdf',[$invoice->id,'pdf']) }}"><i class="fas fa-file-pdf"></i></a>
	                                            <a class="btn btn-secondary" href="{{ route('invoicetopdf',[$invoice->id,'pdf2']) }}"><i class="fas fa-file-pdf"></i></a>
	                                            <a class="btn btn-primary" href="{{ route('invoicetopdf',[$invoice->id,'html']) }}"><i class="fab fa-internet-explorer"></i></a>
	                                            @role('Superadmin')
                                                @if($invoice->pennylane_customer_invoice_id)
                                                    <button class="btn btn-outline-success" disabled title="Pennylane #{{ $invoice->pennylane_customer_invoice_id }}"><i class="fas fa-check"></i></button>
                                                @else
                                                    <form class="pennylane-draft d-inline" action="{{ route('invoices.pennylane.draft', $invoice->id) }}" method="POST">
                                                        {{ csrf_field() }}
                                                        <button class="btn btn-outline-dark" title="Pennylane brouillon"><i class="fas fa-paper-plane"></i></button>
                                                    </form>
                                                @endif
	                                            <form class="deleteinvoice" action="{{ route('invoices.destroy', $invoice->id) }}" method="POST">
	                                                {{ method_field('DELETE') }}
	                                                {{ csrf_field() }}
                                                <button class="btn btn-danger"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                            @endrole
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center">
                        {!! $invoices->appends(request()->except('page'))->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer')
<script>
    jQuery(document).ready(function($){
        $('.deleteinvoice').on('submit', function(e){
            if(!confirm('Do you want to delete this item?')){
                e.preventDefault();
            }
        });
        $('.pennylane-draft').on('submit', function(e){
            if(!confirm('Créer un brouillon Pennylane pour cette facture ?')){
                e.preventDefault();
            }
        });
    })
</script>
@endsection
