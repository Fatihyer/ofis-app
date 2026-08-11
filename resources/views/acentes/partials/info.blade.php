<div class="row">
    <div class="col-md-12">
        <h4 class="card-title">{{ $acente->tittle }}</h4>
        @if ($acente->suivi)
            <span class="text-danger">Firma Personeli</span>
        @endif

        <p class="card-description">Personal info</p>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    {{ Form::label('firma', 'Provider', ['class' => 'col-sm-3 col-form-label']) }}
                    <div class="col-sm-9">
                        <input class="form-control" disabled value="{{ $acente->firmas->pluck('name')->implode(', ') }}">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    {{ Form::label('color', 'Color', ['class' => 'col-sm-3 col-form-label']) }}
                    <div class="col-sm-9">
                        <input type="color" class="form-control" disabled value="{{ $acente->color }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    {{ Form::label('name', 'Agency Name', ['class' => 'col-sm-3 col-form-label']) }}
                    <div class="col-sm-9">
                        {{ Form::text('name', $acente->name, ['class' => 'form-control', 'disabled' => true]) }}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    {{ Form::label('title', 'Title', ['class' => 'col-sm-3 col-form-label']) }}
                    <div class="col-sm-9">
                        {{ Form::text('title', $acente->tittle, ['class' => 'form-control', 'disabled' => true]) }}
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
                        {{ Form::text('city', $acente->city, ['class' => 'form-control', 'disabled' => true]) }}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    {{ Form::label('country', 'Country', ['class' => 'col-sm-3 col-form-label']) }}
                    <div class="col-sm-9">
                        {{ Form::text('country', $acente->ulke->country_name, ['class' => 'form-control', 'disabled' => true]) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    {{ Form::label('email', 'E-mail', ['class' => 'col-sm-3 col-form-label']) }}
                    <div class="col-sm-9">
                        @forelse($acente->emails as $email)
                            <span class="badge badge-light border text-dark mb-1">{{ $email->email }}</span>
                        @empty
                            <input type="email" class="form-control mb-2" disabled>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    {{ Form::label('tel', 'Phone Number', ['class' => 'col-sm-3 col-form-label']) }}
                    <div class="col-sm-9">
                        {{ Form::text('tel', $acente->tel, ['class' => 'form-control', 'disabled' => true]) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    {{ Form::label('address', 'Address', ['class' => 'col-sm-3 col-form-label']) }}
                    <div class="col-sm-9">
                        <textarea class="form-control" disabled>{{ $acente->address }}</textarea>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    {{ Form::label('postal', 'Post Code', ['class' => 'col-sm-3 col-form-label']) }}
                    <div class="col-sm-9">
                        <input class="form-control" disabled value="{{ $acente->postal }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    {{ Form::label('vd', 'Tax Area', ['class' => 'col-sm-3 col-form-label']) }}
                    <div class="col-sm-9">
                        <input class="form-control" disabled value="{{ $acente->vd }}">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    {{ Form::label('vdno', 'Tax Number', ['class' => 'col-sm-3 col-form-label']) }}
                    <div class="col-sm-9">
                        <input class="form-control" disabled value="{{ $acente->vdno }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Yeni row: whatsapp ve suivi --}}
        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    {{ Form::label('whatsapp', 'Whatsapp', ['class' => 'col-sm-3 col-form-label']) }}
                    <div class="col-sm-9">
                        <input class="form-control" disabled value="{{ $acente->whatsapp }}">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    {{ Form::label('suivi', 'Driver Suivi', ['class' => 'col-sm-3 col-form-label']) }}
                    <div class="col-sm-9">
                        <input class="form-control" disabled value="{{ $acente->suivi ? 'Evet' : 'Hayır' }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Airport Shuttle durumu (eklemek istersen) --}}
        <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    {{ Form::label('airportshuttle', 'Airport Shuttle', ['class' => 'col-sm-3 col-form-label']) }}
                    <div class="col-sm-9">
                        <input class="form-control" disabled value="{{ $acente->airportshuttle ? 'Evet' : 'Hayır' }}">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    {{ Form::label('Responsable', 'responsable', ['class' => 'col-sm-3 col-form-label']) }}
                    <div class="col-sm-9">
                        <input class="form-control" disabled value="{{ $acente->responsable->name ?? 'N/A' }}">
                    </div>
                </div>
            </div>


        </div>
          <div class="row">
            <div class="col-md-6">
                <div class="form-group row">
                    {{ Form::label('hermescle', 'Hermes id', ['class' => 'col-sm-3 col-form-label']) }}
                    <div class="col-sm-9">
                        <input class="form-control" disabled value="{{ $acente->hermescle}}">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group row">
                    
                </div>
            </div>


        </div>   




        <a href="{{ route('acentes.edit', $acente->id) }}" class="btn btn-primary">Edit</a>
    </div>
</div>

<hr>

<div>
    <h4>File List</h4>
    Documents:
    @foreach ($files as $file)
        <a href="/{{ $storeFile }}/acente/{{ $acente->id }}/{{ basename($file) }}">{{ basename($file) }}</a>@if (!$loop->last), @endif
    @endforeach
</div>

<hr>

<div id="documents">
    {{ Form::open(['route' => 'image.upload.acente', 'files' => true]) }}
    {{ Form::file('image') }}
    {{ Form::hidden('id', $acente->id) }}
    {{ Form::submit('Upload', ['class' => 'btn btn-success mt-2']) }}
    {{ Form::close() }}
</div>
