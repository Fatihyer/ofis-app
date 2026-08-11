<div class="card mb-3">
  <div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
          <h4 class="mb-0">{{ $post->title }}</h4>
          <span class="badge bg-{{ $post->status->color->name }}">{{ $post->status->name }}</span>
          @if($post->resmi)
            <span class="badge bg-success">Légal</span>
          @else
            <span class="badge bg-light text-dark border">Non légal</span>
          @endif
        </div>
        <div class="text-muted">
          Dossier #{{ $post->id }} |
          <a href="{{ route('acentes.show',$post->acente_id) }}">{{ $post->acente->name }}</a>
          @if ($post->user)
            | <span class="text-warning">{{ $post->user->name }}</span>
          @endif
          | <a href="/logActivity/{{ $post->id }}">Historique</a>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2">
        {!! Form::open(['method' => 'DELETE', 'route' => ['posts.destroy', $post->id], 'class' => 'd-flex flex-wrap gap-2']) !!}
          <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">Retour</a>
          <a href="{{ route('posts.edit', $post->id) }}" class="btn btn-success btn-sm">Modifier</a>
          @if($previous)
            <a class="btn btn-primary btn-sm" href="{{ URL::to('posts/' . $previous) }}"><i class="fas fa-arrow-left"></i></a>
          @endif
          @if($next)
            <a class="btn btn-primary btn-sm" href="{{ URL::to('posts/' . $next) }}"><i class="fas fa-arrow-right"></i></a>
          @endif
          @can('Delete Post')
            {!! Form::submit('Supprimer', ['class' => 'btn btn-danger btn-sm', 'onclick' => "return confirm('Supprimer ce dossier ?')"]) !!}
          @endcan
        {!! Form::close() !!}
      </div>
    </div>
  </div>
</div>
