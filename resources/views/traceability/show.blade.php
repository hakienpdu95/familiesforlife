@extends('layouts.traceability')

@section('content')
    <div class="px-4 pt-5 space-y-5">
        @if ($image)
            <img src="{{ $image }}" alt="{{ $title }}" width="400" height="400" class="w-40 h-40 mx-auto object-contain">
        @endif

        <h1 class="text-xl font-bold leading-snug">{{ $title }}</h1>

        @include('traceability.partials.facts', ['facts' => $facts])
        @include('traceability.partials.sections', ['sections' => $sections])
    </div>
@endsection
