@extends('layouts.app')
@section('content')
   
        <div class="row">
            <div class="col-md-12 grid-margin">
         
                    {{ Form::open(array('route' => 'fichier.upload.post','files'=>true)) }}
                        
                   
                       
                   <div class="card">
                        
                   <div class="form-group">
                    <label for="exampleFormControlFile1">Upload Document</label>
                    <input type="file" name="image" class="form-control-file" id="exampleFormControlFile1">
                  </div>               
                  </div>
             
             <button class="btn btn-primary" type="submit">Upload</button>
                 {{ Form::close() }}
              </div>
                       
          </div>
        
          
@endsection          