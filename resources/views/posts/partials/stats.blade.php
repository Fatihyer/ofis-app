<div class="row align-items-stretch">
    {{-- STATUS --}}
    {{-- FROM / TO --}}
    <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6 grid-margin stretch-card">
       <div class="card card-statistics h-100 d-flex flex-column">

            <div class="card-body d-flex flex-column justify-content-between">

                <div class="clearfix">
                    <div class="float-left">
                        <h4 class="font-weight-medium text-right mb-0">@lang('app.fromdate')</h4>
                        <p class="mb-0 text-right">{{ date("d-m-Y", strtotime($post->start_date))}}</p>
                    </div>
                    <div class="float-right">
                        <h4 class="font-weight-medium text-right mb-0">@lang('app.todate')</h4>
                        <p class="mb-0 text-right">{{date("d-m-Y", strtotime($post->end_date))}}</p>
                    </div>
                </div>
                <p class="mt-3 mb-0">
                    <i class="mdi mdi-calendar mr-1"></i> Adultes : {{ $post->pax }} <br/>
                    <i class="mdi mdi-calendar mr-1"></i> Enfants : {{ $post->child }}
                </p>
            </div>
        </div>
    </div>

    {{-- MESSAGES --}}
    <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6 grid-margin stretch-card">
       <div class="card card-statistics h-100 d-flex flex-column">

            <div class="card-body d-flex flex-column justify-content-between">

                <button type="button" class="btn btn-danger btn-sm mb-2"
                        data-bs-toggle="modal" data-bs-target="#exampleModalLong">
                    Messages <span class="badge badge-info">{{count($messages)}}</span>
                </button>

                <p class="text-muted mb-0">Messages :
                    @foreach ($messages as $message)
                        <a href="#" data-bs-toggle="modal" 
                           data-id="{{ $message->id }}"
                           data-title="{{ $message->tittle}}"
                           data-body="{{ $message->body}}"  
                           data-user_id="{{$message->user_id}}"   
                           data-user_name="{{$message->user_id?$message->user->name:""}}"   
                           data-tarih="{{date("Y-m-d",strtotime($message->tarih))}}"  
                           data-bs-target="#editmessage">
                           {{$message->tittle}}
                        </a>,
                    @endforeach
                </p>
            </div>
        </div>
    </div>

    {{-- DOCUMENTS --}}
    <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6 grid-margin stretch-card">
       <div class="card card-statistics h-100 d-flex flex-column">

            <div class="card-body d-flex flex-column justify-content-between">

                <button type="button" class="btn btn-info btn-sm mb-2"
                        data-bs-toggle="collapse" data-bs-target="#documents">
                    Documents <span class="badge badge-warning">{{count($files)}}</span>
                </button>

                <p class="text-muted mb-0">
                    @foreach ($files as $file)
                        <a href="/{{$stroreFile}}/{{$post->id}}/{{basename($file)}}">
                            {{basename($file)}}
                        </a>,
                    @endforeach
                </p>
            </div>
        </div>
    </div>
</div>

{{-- DOCUMENT UPLOAD --}}
<div id="documents" class="collapse mt-2">
    {{ Form::open(array('route' => 'image.upload.post','files'=>true)) }}
        {{ Form::file('image') }}
        {{ Form::submit('Importer') }}
        {{ Form::hidden('id',$post->id) }}
    {{ Form::close() }}
</div>
