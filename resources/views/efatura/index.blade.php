@extends('layouts.app')
@section('content')
   
<div class="container">
    <a href="{{route('efaturayukle')}}">yukle</a>

    <table class="table">
        <thead>
        <tr>
        <th>ID</th> 
        <th>Fatura NO</th>
        <th>Firma</th>
        <th>Acente</th>
        <th>VD</th>
        <th>Tarih</th>
        <th>Fiyat</th>
        <th>Senaryo</th>
        <th>Durum</th>
        <th>Tip</th>    
        
        </tr>    
        </thead> 
        <tbody>
          @foreach ($efaturalar as $efatura)
              
        
            <tr>
                <td>{{$efatura->id}}</td> 
                <td>{{$efatura->fatno}}</td>
                <td>{{$efatura->name}}</td>
                <td>@if($efatura->acente_id==0)  {{Form::select('acente_id',$acentes,$efatura->acente_id,['class'=>'form-control form-control-sm js-example-basic-single'])}}@endif</td>
                <td>{{$efatura->vd}}</td>
                <td>{{$efatura->date}}</td>
                <td>{{$efatura->price}}</td>
                <td>{{$efatura->senaryo}}</td>
                <td>{{$efatura->durum}}</td>
                <td>{{$efatura->tip }}</td>    
            </tr>
            @endforeach  
        </tbody>


    </table>

</div>
   
@endsection    