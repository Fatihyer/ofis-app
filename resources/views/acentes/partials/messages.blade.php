{{ Form::open(['route' => 'acentemsgs.store']) }}
    {{ Form::textarea('message', $acente->msg->message ?? '', ['class' => 'form-control']) }}
    {{ Form::hidden('acente_id', $acente->id) }}
    <button class="btn btn-primary" type="submit">@lang('app.save')</button>
{{ Form::close() }}
