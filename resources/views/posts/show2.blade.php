




@extends('layouts.app')

@section('title','| View Post')

@section('style')
@include('posts.partials.styles')
@endsection

@section('content')

@include('posts.partials.header')
@include('posts.partials.stats')
@include('posts.partials.comments')
@include('posts.partials.clients')
@include('posts.partials.transfers')
@include('posts.partials.stocks')
@include('posts.partials.hotels')
@include('posts.partials.others')

@hasanyrole('Admin|ofis')
@include('posts.partials.expenses')
@include('posts.partials.invoices')
@include('posts.partials.balance')
@endhasanyrole

@include('posts.partials.modals')

@endsection

@section('footer')
@include('posts.partials.scripts')
@endsection
