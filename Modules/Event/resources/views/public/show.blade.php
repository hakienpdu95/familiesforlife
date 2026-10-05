@extends('layouts.frontend')

@section('title', $event->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($event->description), 160))

@push('meta')
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta property="og:type" content="event">
<meta property="og:title" content="{{ $event->title }}">
<meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($event->description), 160) }}">
@if($event->poster_path)
<meta property="og:image" content="{{ url(\Illuminate\Support\Facades\Storage::url($event->poster_path)) }}">
@endif

@if(!empty($structuredData))
{{-- GEO/AEO (2026-08-11) — Event JSON-LD, xem Modules\Event\Support\EventStructuredDataBuilder. --}}
<script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@endpush

@section('content')
@php
    $isOnline = $event->location_type === \Modules\Event\Enums\EventLocationType::Online;
    $address = $event->full_address ?: trim($event->venue_address.', '.$event->ward?->name.', '.$event->province?->name, ', ');
    $ctaUrl = $event->website_url ?: $event->online_url;
    $placeholder = asset(\Modules\Event\Models\Event::DEFAULT_POSTER);
    $posterUrl = $event->posterUrl();

    $dateLabel = $event->start_date?->format('d/m/Y')
        .(! $event->start_date?->isSameDay($event->end_date) ? ' – '.$event->end_date?->format('d/m/Y') : '');
    $timeLabel = $event->start_time
        ? \Illuminate\Support\Carbon::parse($event->start_time)->format('H:i').($event->end_time ? ' – '.\Illuminate\Support\Carbon::parse($event->end_time)->format('H:i') : '')
        : null;
    $venueLabel = $isOnline ? 'Trực tuyến' : collect([$event->venue_name, $address])->filter()->implode(', ');

    $navItems = [
        ['Tất cả sự kiện', route('event.public.home'), 'bg-neutral-900'],
        ['Tuần này', route('event.public.home', ['thoi-gian' => 'tuan-nay']), 'bg-blue-800'],
        ['Tháng này', route('event.public.home', ['thoi-gian' => 'thang-nay']), 'bg-pink-600'],
        ['Gửi sự kiện', route('event.public.submit.form'), 'bg-orange-500'],
        ['Tìm sự kiện', route('event.public.home').'#tim-su-kien', 'bg-emerald-400'],
    ];

    $details = array_filter([
        'Thời gian' => trim($dateLabel.($timeLabel ? ', '.$timeLabel : '')),
        'Địa điểm' => $venueLabel,
        'Giá vé' => $event->priceLabel(),
        'Danh mục' => $event->category?->name,
    ]);
@endphp

<section class="container mb-4 grid grid-cols-1 items-center gap-8 px-4 md:grid-cols-2 lg:gap-12">

    <div>
        <span class="text-xs font-bold uppercase tracking-widest text-pink-600">
            {{ $event->category?->name ?? 'Sự kiện' }}
        </span>

        <h1 class="mt-4 font-serif text-3xl font-bold leading-tight text-black">{{ $event->title }}</h1>

        <div class="mt-8 grid max-w-md grid-cols-2 gap-x-8 gap-y-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-pink-600">Thời gian</p>
                <p class="mt-1.5 text-sm leading-relaxed text-black">
                    {{ $dateLabel }}
                    @if($timeLabel)<br>{{ $timeLabel }}@endif
                </p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-pink-600">Giá vé</p>
                <p class="mt-1.5 text-sm leading-relaxed text-black">{{ $event->priceLabel() }}</p>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-pink-600">Địa điểm</p>
                <p class="mt-1.5 text-sm leading-relaxed text-black">
                    @if($isOnline)
                    Trực tuyến
                    @else
                    {{ $event->venue_name }}
                    @if($address)<br><span class="text-neutral-500">{{ $address }}</span>@endif
                    @endif
                </p>
                @if($heritageSite)
                <a href="{{ route('heritage.public.show', ['slug' => $heritageSite->slug, 'id' => $heritageSite->id]) }}" class="mt-1 inline-block text-sm text-blue-800 hover:underline">
                    Di tích: {{ $heritageSite->name }}
                </a>
                @endif
            </div>
            <div class="self-end">
                @if($ctaUrl)
                <a href="{{ $ctaUrl }}" target="_blank" rel="noopener"
                   class="inline-block rounded-sm bg-blue-800 px-6 py-3 text-xs font-bold uppercase tracking-widest text-white transition hover:bg-blue-900">
                    Tìm hiểu thêm
                </a>
                @endif
            </div>
        </div>
    </div>

    <div>
        <img src="{{ $posterUrl }}" alt="{{ $event->poster_alt ?? $event->title }}"
             onerror="this.onerror=null;this.src='{{ $placeholder }}'"
             class="aspect-[3/2] h-auto w-full bg-neutral-100 object-cover">
    </div>

</section>

<nav class="container mb-4" aria-label="Điều hướng sự kiện">
    <div class="grid grid-cols-2 sm:grid-cols-5">
        @foreach($navItems as [$label, $url, $color])
        <a href="{{ $url }}"
           class="{{ $color }} {{ $loop->last ? 'col-span-2 sm:col-span-1' : '' }} px-3 py-4 text-center text-xs font-bold uppercase tracking-widest text-white transition hover:opacity-90">
            {{ $label }}
        </a>
        @endforeach
    </div>
</nav>

<article class="container mb-10 px-4">
    <div class="float-left mr-6 mb-4 flex h-32 w-32 rotate-[-10deg] items-center justify-center rounded-full bg-pink-600 text-center text-lg font-bold leading-tight text-white">
        Bạn được<br>mời!
    </div>

    <p class="text-lg leading-relaxed text-neutral-800">
        Trân trọng mời bạn và gia đình tham gia <strong class="text-black">{{ $event->title }}</strong>.
        Sự kiện diễn ra {{ $event->start_date?->isSameDay($event->end_date) ? 'ngày' : 'từ' }} {{ $dateLabel }}{{ $timeLabel ? ', '.$timeLabel : '' }}{{ $isOnline ? ' theo hình thức trực tuyến' : ($venueLabel ? ' tại '.$venueLabel : '') }}.
        Giá vé: {{ mb_strtolower($event->priceLabel()) }}.
        @if($ctaUrl)Thông tin đăng ký và chương trình chi tiết được cập nhật tại trang của ban tổ chức — đừng bỏ lỡ nhé!@else Hãy lưu lại lịch và rủ thêm bạn bè, người thân cùng tham gia nhé!@endif
    </p>

    <div class="clear-both"></div>

    <div class="prose prose-lg mt-8 max-w-none prose-p:text-neutral-800">
        <p>{!! nl2br(e($event->description)) !!}</p>

        <h2>Thông tin chi tiết</h2>
        <ol>
            @foreach($details as $label => $value)
            <li><strong>{{ $label }}:</strong> {{ $value }}</li>
            @endforeach
            @if($ctaUrl)
            <li><strong>Đăng ký / tham gia:</strong> <a href="{{ $ctaUrl }}" target="_blank" rel="noopener" class="break-all">{{ $ctaUrl }}</a></li>
            @endif
        </ol>
    </div>
</article>
@endsection
