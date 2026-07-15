@extends('layouts.frontend')

@section('title', 'Chuyên đề địa phương')
@section('meta_description', 'Di sản, văn hóa, ẩm thực và sản phẩm OCOP đặc trưng theo từng tỉnh/thành.')

@section('content')
<div class="container py-10">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('post.public.home') }}">Trang Chủ</a></li>
            <li>Chuyên đề địa phương</li>
        </ul>
    </div>

    <h1 class="text-2xl font-bold text-base-content mb-2">Chuyên đề địa phương</h1>
    <p class="text-sm text-base-content/60 mb-8">Di sản, văn hóa, ẩm thực và sản phẩm OCOP đặc trưng theo từng tỉnh/thành.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        @foreach($provinces as $item)
        @php($p = $item['province'])
        <a href="{{ route('province.public.show', ['type' => $p->place_type, 'slug' => $p->slug]) }}"
           class="group flex flex-col overflow-hidden rounded-xl border border-base-300"
           style="background: linear-gradient(135deg, {{ $item['config']['accent_color'] }} 0%, color-mix(in srgb, {{ $item['config']['accent_color'] }} 60%, black) 100%);">
            <div class="p-8 text-white">
                <span class="text-xs font-black uppercase tracking-[0.3em] text-white/70">Chuyên đề</span>
                <h2 class="text-2xl font-extrabold mt-2 group-hover:underline">{{ $p->name }}</h2>
                <p class="text-white/85 mt-1.5 text-sm">{{ $item['config']['tagline'] }}</p>
            </div>
        </a>
        @endforeach
    </div>

    @if($provinces->isEmpty())
    <p class="text-center py-10 text-base-content/40">Chưa có tỉnh/thành nào có chuyên đề.</p>
    @endif

</div>
@endsection
