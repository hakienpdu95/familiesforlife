@extends('layouts.auth')

@section('title', $title ?? config('app.name'))

@section('content')
    {{ $slot }}
@endsection
