@extends('layouts.traceability')

@section('content')
    <div class="px-5 pt-6">
        <h1 class="tx-serif text-2xl font-bold leading-tight">{{ $title }}</h1>
    </div>
    <div class="px-5 mt-6">
        @include('traceability.partials.spec', ['rows' => $facts])
    </div>
@endsection
