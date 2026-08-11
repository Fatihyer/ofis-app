<div class="card mb-3">
  <div class="card-header d-flex justify-content-between">
    <span><i class="fas fa-comment"></i> Commentaires du dossier</span>
   
  </div>
  <div class="card-body">
    <textarea id="post-comment" class="form-control post-comment-box"
      data-id="{{ $post->id }}" rows="5">{{ $post->body }}</textarea>
     <span id="post-comment-status" class="badge bg-light text-success"></span>

  </div>
</div>

