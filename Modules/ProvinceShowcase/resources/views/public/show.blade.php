@extends('layouts.frontend')

@section('title', $province->name . ' — ' . $showcase['tagline'])
@section('meta_description', $showcase['tagline'])

@section('content')

{{-- Hero — tagline + accent_color từ config (spec §7.2). Tỉnh cần phong cách riêng (font/màu/
     bố cục theo văn hóa vùng miền) → tạo resources/views/public/custom/{slug}.blade.php, kế
     thừa nguyên các section component bên dưới, chỉ đổi khối hero/bọc ngoài này. --}}
<div class="py-16" style="background: linear-gradient(135deg, {{ $showcase['accent_color'] }} 0%, color-mix(in srgb, {{ $showcase['accent_color'] }} 60%, black) 100%);">
    <div class="container text-center">
        <span class="text-xs font-black uppercase tracking-[0.3em] text-white/70">Chuyên đề địa phương</span>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white mt-3">{{ $province->name }}</h1>
        <p class="text-white/85 mt-2 max-w-xl mx-auto">{{ $showcase['tagline'] }}</p>
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
