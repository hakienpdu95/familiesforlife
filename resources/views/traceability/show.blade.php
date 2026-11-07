@extends('layouts.traceability')

@section('content')
    <div class="tx-hero px-4 pt-5 pb-5">
        <p class="tx-eyebrow">{{ $kind }}</p>
        <h1 class="tx-display text-[26px] mt-1.5">{{ $title }}</h1>
    </div>
    @if (array_filter($facts))
        <section class="tx-section">
            @include('traceability.partials.spec', ['rows' => $facts])
        </section>
    @endif
@endsection
