@extends('layouts.traceability')

@section('content')
    @include('traceability.partials.hero')
    @include('traceability.partials.fact-grid', ['facts' => $facts])
    @include('traceability.partials.sections')
@endsection
