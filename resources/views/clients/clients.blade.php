<!-- Modal add transfert-->
<div class="modal fade" id="addclient" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
  <div class="modal-dialog  modal-lg" role="document">
     
    <div class="modal-content ">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">Add Client</h5>
        
        
       
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>{{ Form::open(array('route' => 'clients.store')) }}
      <div class="modal-body">
           
              <div class="row">
                      <div class=" col-sm-2">
                       
                        {{ Form::label('tittle', 'Title') }}
                      
                          
                      </div>
                      <div class="col-md-2">
                        {{ Form::label('name', 'Name') }}
                       
                      </div>
                       <div class="col-md-2">
                        {{ Form::label('surname', 'Surname') }}
                       
                      </div>
                  
                        <div class="col-md-2">
                        {{ Form::label('tel', 'Tel') }}
                     
                      </div>
                      <div class="col-md-2">
                        {{ Form::label('email', 'Email') }}
                    
                      </div>
                     <div class="col-md-2">
                      
                     <a class="btn btn-success btn-sm" href="#" role="button" id="addRow">
                       <i class="fa fa-plus" aria-hidden="true"></i>Row
                      </a> 
                      </div>
                  </div>
              <div class="row">
                    <!--üst vbiter -->
                  
                       <div class="form-group col-sm-2">
                                          
                        {{ Form::select('title[]',['Mr'=>'Mr','Mrs'=>'Mrs','Chld'=>'Chld','BB'=>'BB','Dr'=>'Dr','Prof'=>'Prof'],"", array('class' => 'form-control')) }}
                     
                         {{Form::hidden('post_id',$post->id)}} 
                        
                      </div>
                      <div class="col-md-2">
                     
                        {{ Form::text('name[]','', array('class' => 'form-control','required' => 'required')) }}
                      </div>
                       <div class="col-md-2">
                       
                        {{ Form::text('surname[]','', array('class' => 'form-control')) }}
                      </div>
                  
                        <div class="col-md-2">
                       
                        {{ Form::text('tel[]','', array('class' => 'form-control')) }}
                      </div>
                      <div class="col-md-2">
                      
                        {{ Form::text('email[]','', array('class' => 'form-control')) }}
                      </div>
                     
                      <div  class="col-md-2" >
                        
                        <a class="btn btn-primary btn-sm" data-bs-toggle="collapse" href="#collapseExample" role="button" aria-expanded="false" aria-controls="collapseExample">
                       <i class="fa fa-commenting-o" aria-hidden="true"></i>
                      </a>
                      
                      
                        </div>
                
             </div>
            <div class="collapse" id="collapseExample">
                    {{ Form::text('comments[]','#', array('class' => 'form-control')) }} 
                <br/>
              </div>
    
        
          <div id="isimekleme">
            
        </div>
        
        
        
      </div>        
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          {{Form::hidden('post_id[]',$post->id)}}
         {{Form::submit('Add Name',array('class'=>'btn btn-primary'))}}
       
      </div>
       {{form::close()}}
    </div>
    
  </div>
</div>


