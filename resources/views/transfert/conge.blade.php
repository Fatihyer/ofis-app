@extends('layouts.app')

@section('style')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
@endsection

@section('content')

<div class="container">
    <h2>Add Conge ot Event</h2>
    <form action="{{ route('saveconge',$id ) }}" method="POST">
        @csrf
       
        <!-- Transfer Details -->
        <h4>Conge /Event Details</h4>
        <div class="form-group">
            <label for="start_date">Start Date</label>
            <input type="date" name="start_date" class="form-control" 
                   value="{{ old('start_date')}}" required>
        </div>
        <div class="form-group">
            <label for="end_date">Duration (day)</label>
            <input type="number" name="end_date" class="form-control" 
                   value="{{ old('end_date', 1)}}" required>
        </div>  
        <div class="form-group">
            <label for="servicetype_id">Service Type</label>
            <select name="servicetype_id" class="form-control" required>
                <option value="" disabled selected>Select a Service Type</option>
                @foreach ($servicetype as $id => $name)
                    <option value="{{ $id }}" {{ old('servicetype_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
       
      
        <div class="form-group">
            <label for="driver_id">Driver</label>
            <select name="driver_id" id="driver_id" class="form-control" required>
            @foreach ($drivers as $id =>$name)
                    <option value="{{ $id }}" {{ old('id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="comments">Comments</label>
            <textarea name="comments"  class="form-control"  id=""></textarea>
            
        </div>

       
         <button type="submit" class="btn btn-success">Save</button>
    </form>
</div>
@endsection

