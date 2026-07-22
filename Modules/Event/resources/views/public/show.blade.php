@extends('layouts.frontend')

@section('title', $event->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($event->description), 160))

@push('meta')
<link rel="canonical" href="{{ route('event.public.show', ['slug' => $event->slug, 'id' => $event->id]) }}">
<meta property="og:type" content="event">
<meta property="og:title" content="{{ $event->title }}">
<meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($event->description), 160) }}">
@if($event->poster_path)
<meta property="og:image" content="{{ url(\Illuminate\Support\Facades\Storage::url($event->poster_path)) }}">
@endif
@endpush

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('event.public.home') }}">Sự Kiện</a></li>
            @if($event->category)
            <li><a href="{{ route('event.public.category', ['category' => $event->category->slug]) }}">{{ $event->category->name }}</a></li>
            @endif
        </ul>
    </div>

    @if($event->poster_path)
    <div class="aspect-[16/9] overflow-hidden bg-base-200 mb-6">
        <img src="{{ \Illuminate\Support\Facades\Storage::url($event->poster_path) }}" alt="{{ $event->poster_alt ?? $event->title }}" class="h-full w-full object-cover">
    </div>
    @endif

    @if($event->category)
    <span class="text-xs font-black uppercase tracking-wide text-primary">{{ $event->category->name }}</span>
    @endif
    <h1 class="text-3xl font-bold text-base-content mt-1 mb-4">{{ $event->title }}</h1>

    <div class="grid sm:grid-cols-2 gap-4 mb-6">
        <div class="card bg-base-100 border border-base-300 p-4">
            <span class="text-xs font-black uppercase tracking-wide text-secondary">Thời gian</span>
            <p class="text-sm mt-1">
                {{ $event->start_date?->format('d/m/Y') }}
                @if(! $event->start_date?->isSameDay($event->end_date)) – {{ $event->end_date?->format('d/m/Y') }} @endif
                @if($event->start_time) &middot; {{ \Illuminate\Support\Carbon::parse($event->start_time)->format('H:i') }}@if($event->end_time) – {{ \Illuminate\Support\Carbon::parse($event->end_time)->format('H:i') }}@endif @endif
            </p>
        </div>
        <div class="card bg-base-100 border border-base-300 p-4">
            <span class="text-xs font-black uppercase tracking-wide text-secondary">Địa điểm</span>
            @if($event->location_type === \Modules\Event\Enums\EventLocationType::Online)
            <p class="text-sm mt-1">
                Trực tuyến
                @if($event->online_url)
                — <a href="{{ $event->online_url }}" target="_blank" rel="noopener" class="link link-primary">Tham gia</a>
                @endif
            </p>
            @else
            <p class="text-sm mt-1">
                {{ $event->venue_name }}<br>
                <span class="text-base-content/60">{{ $event->full_address ?: trim($event->venue_address.', '.$event->ward?->name.', '.$event->province?->name, ', ') }}</span>
            </p>
            @endif
        </div>
        <div class="card bg-base-100 border border-base-300 p-4">
            <span class="text-xs font-black uppercase tracking-wide text-secondary">Giá vé</span>
            <p class="text-sm mt-1">{{ $event->priceLabel() }}</p>
        </div>
        @if($event->website_url)
        <div class="card bg-base-100 border border-base-300 p-4">
            <span class="text-xs font-black uppercase tracking-wide text-secondary">Website</span>
            <p class="text-sm mt-1">
                <a href="{{ $event->website_url }}" target="_blank" rel="noopener" class="link link-primary break-all">{{ $event->website_url }}</a>
            </p>
        </div>
        @endif
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-300">
        <div class="card-body prose max-w-none">
            {!! nl2br(e($event->description)) !!}
        </div>
    </div>

</div>
@endsection
