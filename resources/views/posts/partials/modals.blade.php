
<!-- Modal ajouter message -->
<div class="modal fade" id="exampleModalLong" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
     
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">Nouveau message</h5>
        
        
       
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>{{ Form::open(array('route' => 'message.kayit')) }}
      <div class="modal-body">
        {{ Form::label('tittle', 'Titre', array('for' => 'validationDefault01')) }}
            {{ Form::text('tittle', null, array('class' => 'form-control','required'=>'required','placeholder'=>'Nom')) }}
         {{ Form::label('tarih', 'Date', array('for' => 'validationDefault01')) }}
            {{ Form::date('tarih', \Carbon\Carbon::now(), array('class' => 'form-control','required'=>'required')) }}
       {{ Form::checkbox('user_id', Auth::id())}} Moi seul peux modifier
      
        {{ Form::label('body', 'Message', array('for' => 'validationDefault00')) }}
            {{ Form::textarea('body',null, array('class' => 'form-control description')) }}
           {{Form::hidden('post_id',$post->id)}}
        
      
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
         {{Form::submit('Ajouter le message',array('class'=>'btn btn-primary'))}}
       
      </div>
       {{form::close()}}
    </div>
    
  </div>
</div>

<!-- Modal modifier message -->
<div class="modal fade" id="editmessage" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
     
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">Modifier le message</h5>
        
        
       
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>{{ Form::open(array('route' => 'message.edit')) }}
      <div class="modal-body">
        {{ Form::label('tittle', 'Titre', array('for' => 'validationDefault01')) }}
            {{ Form::text('tittle', null, ['id' => 'data-title','class'=>'form-control']) }}
        
        {{ Form::label('tarih', 'Date', array('for' => 'validationDefault01')) }}
            {{ Form::date('tarih',null, ['id' => 'data-tarih','class'=>'form-control']) }}
       <span id="gizle2">    {{ Form::checkbox('user_id', Auth::id(),null,['id' => 'data-user'])}} Moi seul peux modifier</span> 
          
        {{ Form::label('body', 'Message', array('for' => 'validationDefault00')) }}
            {{ Form::textarea('body',null,['id' => 'data-body','class'=>'form-control description','rows'=>'25']) }}
        
        {{Form::hidden('id',"",['id' => 'data-id'])}}
      </div>
      <div class="modal-footer">
         <span id="sadeceuser"></span>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
         {{Form::submit('Modifier le message',array('class'=>'btn btn-primary','id'=>"gizle"))}}
        
      </div>
       {{form::close()}}
    </div>
    
  </div>
</div>
@include ('transfert.create') {{-- Including create blade file --}}
@include ('transfert.edit') {{-- Including create blade file --}}
@include ('invoice.addinvoice') {{-- Including create blade file --}}  
@include ('clients.clients') {{-- Including create blade file --}}  
@include ('posts.partials.route-map-modal')

<!-- Modal fichier facture -->
<div class="modal fade" id="uploadInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="invoiceUploadForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une facture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="file" name="file" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button class="btn btn-success">Importer</button>
                </div>
            </form>
        </div>
    </div>
</div>
