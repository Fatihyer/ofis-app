@extends('layouts.app')
@section('content')
   
<div class="container">
    <div class="card bg-light mt-3">
        <div class="card-header">
            garanti bankasi dokum alma
        </div>
        <div class="card-body">
            <form action="{{ route('import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" class="form-control">
              {{Form::select('acente_id',$acentes,"",['class'=>'form-control'])}}
               {{Form::select('kur_id',$kurs,"",['class'=>'form-control'])}}
                <br>
                <button class="btn btn-success">Garanti bankasi dokumuu al</button>
                <a class="btn btn-warning" href="{{ route('export') }}">Export User Data</a>
            </form>
        </div>
    </div>
</div>
   
@endsection     