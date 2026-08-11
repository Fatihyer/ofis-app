@extends('layouts.app')

@section('title', '| View Post')
@section('style')
<link href="{{ asset('css/bootstrap-colorpicker.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="col-12 grid-margin">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Edit Provider</h4>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ Form::model($acente, ['route' => ['acentes.update', $acente->id], 'method' => 'PUT', 'class' => 'form-sample']) }}

            <p class="card-description">Personal info</p>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        {{ Form::label('firma', 'Provider', ['class' => 'col-sm-3 col-form-label']) }}
                        <div class="col-sm-9">
                            {{ Form::select('firma[]', $firma, $acente->firmas, ['class' => 'form-control', 'multiple' => 'multiple']) }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group row">
                        {{ Form::label('color', 'Color', ['class' => 'col-sm-3 col-form-label']) }}
                        <div class="col-sm-9">
                            {{ Form::text('color', null, ['id' => 'renk', 'autocomplete' => 'off']) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        {{ Form::label('name', 'Agency Name', ['class' => 'col-sm-3 col-form-label']) }}
                        <div class="col-sm-9">
                            {{ Form::text('name', null, ['class' => 'form-control', 'required' => 'required']) }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group row">
                        {{ Form::label('tittle', 'Title', ['class' => 'col-sm-3 col-form-label']) }}
                        <div class="col-sm-9">
                            {{ Form::text('tittle', null, ['class' => 'form-control', 'required' => 'required']) }}
                        </div>
                    </div>
                </div>
            </div>

            <p class="card-description">Address</p>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        {{ Form::label('city', 'City', ['class' => 'col-sm-3 col-form-label']) }}
                        <div class="col-sm-9">
                            {{ Form::text('city', null, ['class' => 'form-control']) }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group row">
                        {{ Form::label('ulke_id', 'Country', ['class' => 'col-sm-3 col-form-label']) }}
                        <div class="col-sm-9">
                            {{ Form::select('ulke_id', $ulke, $acente->ulke_id, ['class' => 'form-control']) }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
            <div class="col-md-6">
                                        <div class="form-group row">
                                        {{ Form::label('emails', 'E-mail', array('class' => 'col-sm-3 col-form-label')) }}
                                        @include('acentes.partials.email-fields', ['acente' => $acente])
                                        </div>
                                    </div>
                              <div class="col-md-6">
                                <div class="form-group row">
                                  {{ Form::label('web', 'Website', array('class' => 'col-sm-3 col-form-label')) }}
                                  <div class="col-sm-9">
                                    {{ Form::text('web', null, array('class' => 'form-control')) }}
                                  </div>
                                </div>
                              </div>
                            </div>

            <div class="row">
                
                <div class="col-md-6">
                    <div class="form-group row">
                        {{ Form::label('tel', 'Phone Number', ['class' => 'col-sm-3 col-form-label']) }}
                        <div class="col-sm-9">
                            {{ Form::text('tel', null, ['class' => 'form-control']) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        {{ Form::label('address', 'Address', ['class' => 'col-sm-3 col-form-label']) }}
                        <div class="col-sm-9">
                            {{ Form::textarea('address', null, ['class' => 'form-control']) }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group row">
                        {{ Form::label('whatsapp', 'Whatsapp Group', ['class' => 'col-sm-3 col-form-label']) }}
                        <div class="col-sm-9">
                            {{ Form::text('whatsapp', null, ['class' => 'form-control']) }}
                        </div>
                    </div>
                    <div class="form-group row">
                        {{ Form::label('postal', 'Post Code', ['class' => 'col-sm-3 col-form-label']) }}
                        <div class="col-sm-9">
                            {{ Form::text('postal', null, ['class' => 'form-control']) }}
                        </div>
                    <div class="form-group row">
                     
                        
                          {{ Form::label('Resposable', 'Responsable', ['class' => 'col-sm-3 col-form-label']) }}
                          <div class="col-sm-9">
                          <select name="responsable_id" id="" class="form-control" >
                            <option value="">Select Responsable</option>
                            {{-- Kullanıcı listesini döngü ile göster --}}
                           @foreach ($users as $userId => $userName)
                                <option value="{{ $userId }}" {{ $acente->responsable_id == $userId ? 'selected' : '' }}>{{ $userName }}</option>  
                            @endforeach 


                        </select>
                        </div>
                    </div>


                    

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        {{ Form::label('vd', 'Tax Area', ['class' => 'col-sm-3 col-form-label']) }}
                        <div class="col-sm-9">
                            {{ Form::text('vd', null, ['class' => 'form-control']) }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group row">
                        {{ Form::label('vdno', 'Tax Numberfff', ['class' => 'col-sm-3 col-form-label']) }}
                        <div class="col-sm-9">
                            {{ Form::text('vdno', null, ['class' => 'form-control']) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- ✅ Yeni Alan: Airport Shuttle --}}
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        {{ Form::label('airportshuttle', 'Airport Shuttle', ['class' => 'col-sm-3 col-form-label']) }}
                        <div class="col-sm-9">
                            {{ Form::hidden('airportshuttle', 0) }}
                            {{ Form::checkbox('airportshuttle', 1, $acente->airportshuttle) }}
                        </div>
                    </div>
                </div>

                {{-- Suivi alanı da burada --}}
                <div class="col-md-6">
                    <div class="form-group row">
                        {{ Form::label('suivi', 'Personnel/Driver', ['class' => 'col-sm-3 col-form-label']) }}
                        <div class="col-sm-9">
                            {{ Form::hidden('suivi', 0) }}
                            {{ Form::checkbox('suivi', 1, $acente->suivi) }}
                        </div>
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                     {{ Form::label('hermescle', 'Hermes id', ['class' => 'col-sm-3 col-form-label']) }}
                        <div class="col-sm-9">
                              {{ Form::text('hermescle', null, ['class' => 'form-control']) }}
                        </div>
                    </div>
                </div>

                {{-- Suivi alanı da burada --}}
                <div class="col-md-6">
                  
                </div>
            </div>

            <button class="btn btn-primary" type="submit">@lang('app.save')</button>
            <a href="{{ route('acentes.show', $acente->id) }}" class="btn">@lang('app.cancel')</a>

            {{ Form::close() }}
        </div>
    </div>
</div>
@endsection

@section('footer')
<script src="{{ asset('js/bootstrap-colorpicker.min.js') }}"></script>
<script>
    $(function () {
        $('#renk').colorpicker();
    });
</script>
@endsection
