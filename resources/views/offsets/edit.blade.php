@extends('layouts.app')

@section('content')

@php
    $referenceHareket = $offset->harekets->first(function ($hareket) {
        return abs((float) $hareket->amount) > 0;
    }) ?: $offset->harekets->first();

    $offsetAmount = abs((float) optional($referenceHareket)->amount);
    $offsetKurId = optional($referenceHareket)->kur_id;
@endphp

<div class="container">
    <div class="row justify-content-md-center mt-5">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Modifier l'offset</div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($offsetAmount <= 0)
                        <div class="alert alert-info">
                            Cet offset est neutralisé avec un montant à 0. Le dossier reste conservé, sans impact comptable.
                        </div>
                    @endif
                    {{ Form::model($offset, array('route' => array('offsets.update', $offset->id), 'method' => 'PUT')) }}
                 <div  class="form-row">
                        <div class="col"> 
                           {{ Form::label('tarih',__('app.date') , array('for' => 'validationDefault01')) }}
                   {{ Form::date('tarih',date('Y-m-d',strtotime($offset->tarih)), array('class' => 'form-control')) }}
                         </div>
                            <div class="col"> 
                           {{ Form::label('time','Time' , array('for' => 'validationDefault01')) }}
                   {{ Form::time('time',date('H:i',strtotime($offset->tarih)), array('class' => 'form-control')) }}
                         </div>
                 
                  
                  </div>            

                 <div  class="form-row">
                  {{ Form::label('from', 'De') }}
                  {{ Form::select('a_acente_id', $acentes, null, ['class' => 'form-control js-example-basic-single']) }}
                </div>   
                  <div  class="form-row">
                  {{ Form::label('to', 'Vers') }}
                  {{ Form::select('b_acente_id', $acentes, null, ['class' => 'form-control js-example-basic-single']) }}
                </div>
                  <div  class="form-row">
                   {{ Form::label('aciklama', 'Description', array('for' => 'validationDefault01')) }}
                   {{ Form::text('aciklama', null, array('class' => 'form-control')) }}
                 </div> 
                  <div  class="form-row">
                   {{ Form::label('amount',__('app.amount'), array('for' => 'validationDefault01')) }}
                   {{ Form::number('amount', old('amount', $offsetAmount), array('class' => 'form-control','step'=>'.01','min'=>'0','required'=>true)) }}
                 </div>
                  <div  class="form-row">
                   {{ Form::label('kur',__('app.exchange_name')) }}
                   {{ Form::select('kur_id',$kurs, old('kur_id', $offsetKurId), array('class' => 'form-control')) }}
                 </div>            



              <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    <button class="btn btn-primary" type="submit">@lang('app.save')</button>
                    <a href="{{ url()->previous() }}" class="btn btn-link">Retour</a>
                </div>
              </div>
            {{form::close()}}

            @role('Superadmin')
                {{ Form::open(['route' => ['offsets.destroy', $offset->id], 'method' => 'DELETE', 'class' => 'mt-2', 'onsubmit' => "return confirm('Supprimer cet offset ?');"]) }}
                    <button type="submit" class="btn btn-outline-danger btn-sm">Supprimer l'offset</button>
                {{ Form::close() }}
            @endrole

               </div>
                        </div>
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
