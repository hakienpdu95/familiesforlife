@extends('layouts.frontend')

@php
    $tagline = $province->slogan ?: $showcase['tagline'];
    $cover = $province->coverImageUrl();
@endphp

@section('title', $province->name . ' — ' . $tagline)
@section('meta_description', \Illuminate\Support\Str::limit($province->description ?: $tagline, 160))

@section('content')

{{-- Hero — tagline + accent_color từ config (spec §7.2). Tỉnh cần phong cách riêng (font/màu/
     bố cục theo văn hóa vùng miền) → tạo resources/views/public/custom/{slug}.blade.php, kế
     thừa nguyên các section component bên dưới, chỉ đổi khối hero/bọc ngoài này. --}}
<div class="relative overflow-hidden py-16 lg:py-24" style="background: linear-gradient(135deg, {{ $showcase['accent_color'] }} 0%, color-mix(in srgb, {{ $showcase['accent_color'] }} 60%, black) 100%);">
    @if($cover)
    <img src="{{ $cover }}" alt="" class="absolute inset-0 h-full w-full object-cover">
    <div class="absolute inset-0" style="background: linear-gradient(135deg, color-mix(in srgb, {{ $showcase['accent_color'] }} 70%, transparent) 0%, rgb(0 0 0 / 0.65) 100%);"></div>
    @endif
    <div class="container relative text-center">
        @if($province->logo)
        <img src="{{ $province->logoUrl() }}" alt="Logo {{ $province->name }}"
             class="mx-auto mb-5 h-20 w-20 rounded-full bg-white object-contain p-2 shadow-lg sm:h-24 sm:w-24">
        @endif
        <span class="text-xs font-black uppercase tracking-[0.3em] text-white/70">Chuyên đề địa phương</span>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white mt-3">{{ $province->name }}</h1>
        <p class="text-white/90 mt-2 max-w-xl mx-auto text-lg">{{ $tagline }}</p>

        @if($province->highlight_tags)
        <ul class="mt-5 flex flex-wrap justify-center gap-2">
            @foreach($province->highlight_tags as $tag)
            <li class="border border-white/40 bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-white">{{ $tag }}</li>
            @endforeach
        </ul>
        @endif

        @if($province->description)
        <p class="mx-auto mt-6 max-w-3xl text-sm leading-relaxed text-white/85">{!! nl2br(e($province->description)) !!}</p>
        @endif

        @if($province->tvc_video_url || $province->vr360_map_url)
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            @if($province->tvc_video_url)
            <a href="{{ $province->tvc_video_url }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 bg-white px-6 py-3 text-xs font-bold uppercase tracking-widest text-gray-900 transition hover:bg-white/90">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                Xem video quảng bá
            </a>
            @endif
            @if($province->vr360_map_url)
            <a href="{{ $province->vr360_map_url }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 border border-white px-6 py-3 text-xs font-bold uppercase tracking-widest text-white transition hover:bg-white hover:text-gray-900">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Trải nghiệm VR 360
            </a>
            @endif
        </div>
        @endif
    </div>
</div>

<div class="container">
    <div class="py-6">
        <x-frontend.banner-slot placement="province_top" :context="['province_code' => $province->province_code]" />
    </div>
</div>

<x-province.section-heritage :province="$province" />
<x-province.section-cuisine :province="$province" />
<x-province.section-ocop :province="$province" />
<x-province.section-events :province="$province" />

@endsection
