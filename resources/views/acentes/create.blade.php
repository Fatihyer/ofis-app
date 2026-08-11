@extends('layouts.app')

@section('title', '| View Post')
@section('style')

  <link href="{{ asset('css/bootstrap-colorpicker.css') }}" rel="stylesheet">
@endsection
@section('content')

<div class="col-12 grid-margin">
      <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Add New Provider</h4>

                  @if($errors->any())
                    <div class="alert alert-danger">
                      <ul class="mb-0">
                        @foreach($errors->all() as $error)
                          <li>{{ $error }}</li>
                        @endforeach
                      </ul>
                    </div>
                  @endif

             {{ Form::open(array('route' => 'acentes.store','class'=>'form-sample')) }}
                               <p class="card-description">
                      Personal info
                    </p> <div class="row">
                      <div class="col-md-6">
                        <div class="form-group row">
                       {{ Form::label('firma', 'Provider',['class' => 'col-sm-3 col-form-label']) }}
                             <div class="col-sm-9">
                          {{ Form::select('firma[]', $firma, "", ['class' => 'form-control', 'multiple' => 'multiple']) }}
                           </div>
                        </div>
                      </div> 
                       <div class="col-md-6">
                        <div class="form-group row">
                           {{ Form::label('color', 'Color', array('class' => 'col-sm-3 col-form-label')) }}
                     
                          <div class="col-sm-9">
                           {{ Form::text('color', null, array('id'=>'renk','autocomplete'=>'off')) }}
                          </div>
                        </div>
                      </div>
                    </div>
                  
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group row">
                          
                           {{ Form::label('Provider Name', 'Provider Name', array('class' => 'col-sm-3 col-form-label')) }}
                          <div class="col-sm-9">
                           {{ Form::text('name', null, array('class' => 'form-control','required'=>'required')) }}
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group row">
                          <label class="col-sm-3 col-form-label">Title</label>
                          <div class="col-sm-9">
                             {{ Form::text('tittle', null, array('class' => 'form-control','required'=>'required')) }}
                          </div>
                        </div>
                      </div>
                    </div>
                  
                  <p class="card-description">
                      Address
                    </p>
                    <div class="row">
                              <div class="col-md-6">
                                <div class="form-group row">
                                  {{ Form::label('emails', 'E-mail', array('class' => 'col-sm-3 col-form-label')) }}
                                  @include('acentes.partials.email-fields')
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
                           {{ Form::label('City', 'City', array('class' => 'col-sm-3 col-form-label')) }}
                          <div class="col-sm-9">
                            {{  Form::text('city', null, array('class' => 'form-control')) }}
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group row">
                         {{ Form::label('Country', 'Country', array('class' => 'col-sm-3 col-form-label')) }}
                          <div class="col-sm-9">
                           {{ Form::select('ulke_id', $ulke,"", array('class' => 'form-control')) }}
                          </div>
                        </div>
                      </div>
                    </div>
                    
                   <div class="row">
                 
                              
                      <div class="col-md-6">
                        <div class="form-group row">
                         {{ Form::label('tel', 'Phone Number', array('class' => 'col-sm-3 col-form-label')) }}
                          <div class="col-sm-9">
                            {{ Form::text('tel', null, array('class' => 'form-control')) }}
                          </div>
                        </div>
                      </div>
                    </div>
                  
                   <div class="row">
                      <div class="col-md-6">
                        <div class="form-group row">
                         {{ Form::label('address', 'Address', array('class' => 'col-sm-3 col-form-label')) }}
                          <div class="col-sm-9">
                             {{ Form::textarea('address', null, array('class' => 'form-control')) }}<br>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group row">
                          {{ Form::label('postal', 'Post Code', array('class' => 'col-sm-3 col-form-label')) }}
                          <div class="col-sm-9">
                          {{ Form::text('postal', null, array('class' => 'form-control')) }}
                          </div>
                        </div>
                      </div>
                    </div>
                  
                   <div class="row">
                      <div class="col-md-6">
                        <div class="form-group row">
                         {{ Form::label('vd', 'Tax Area', array('class' => 'col-sm-3 col-form-label')) }}
                          <div class="col-sm-9">
                             {{ Form::text('vd', null, array('class' => 'form-control')) }}
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group row">
                         {{ Form::label('vdno', 'Tax Number', array('class' => 'col-sm-3 col-form-label')) }}
                          <div class="col-sm-9">
                          {{ Form::text('vdno', null, array('class' => 'form-control')) }}
                          </div>
                        </div>
                      </div>
                    </div>
                  
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group row">
                          
                          {{Form::hidden('suivi',0)}}
                         {{ Form::label('suivi', 'Personel/Driver', array('class' => 'col-sm-3 col-form-label')) }}
                          <div class="col-sm-9">
                             {{ Form::checkbox('suivi', true, false, array('class' => 'form-control')) }}
                          </div>
                        </div>
                      </div>
                     
                    </div>
 
    
      
    
      
   
    
   

  <button class="btn btn-primary" type="submit">@lang('app.save')</button>
                  <a href="{{ route('acentes.index') }}" class="btn">@lang('app.cancel')</a>
{{form::close()}}

 </div>
              </div>
            </div>
      

@endsection
@section('footer')
 <script src="{{ asset('js/bootstrap-colorpicker.min.js') }}"></script>

 <script>
    $(function() {
        $('#renk').colorpicker();
    });
</script>

@endsection
