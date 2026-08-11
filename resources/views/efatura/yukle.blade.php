@extends('layouts.app')
@section('content')
   
<div class="container">
    <div class="card bg-light mt-3">
        <div class="card-header">
           E Fatura Dokum Al
        </div>
        <div class="card-body">
            <form action="{{ route('importefatura') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" class="form-control">
                 <br>
                <button class="btn btn-success">Dokum Indir</button>
               
            </form>
        </div>
    </div>
</div>
   
@endsection     