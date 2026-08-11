@extends('layouts.app')
@section('content')
   
        <div class="row">
            <div class="col-md-12 grid-margin">
              
                   <div class="card">
                        <a href="{{route('fichier.create')}}" class="btn-success btn">Add Document</a>
             
            @foreach ($files as $file)
                        <div>
                        
           <a href="/{{$stroreFile}}/{{basename($file)}}">{{basename($file)}}</a>  </div>  
                          @endforeach
</div>
               </div>
          </div>
@endsection          