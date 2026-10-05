@extends('layouts.frontend')

@section('title', 'Chuyên đề địa phương')
@section('meta_description', 'Di sản, văn hóa, ẩm thực và sản phẩm OCOP đặc trưng theo từng tỉnh/thành.')

@section('content')
<div class="container">

    <h1 class="text-2xl font-bold text-base-content mb-2">Chuyên đề địa phương</h1>
    <p class="text-sm text-base-content/60 mb-8">Di sản, văn hóa, ẩm thực và sản phẩm OCOP đặc trưng theo từng tỉnh/thành.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        @foreach($provinces as $item)
        @php($p = $item['province'])
        <a href="{{ route('province.public.show', ['type' => $p->place_type, 'slug' => $p->slug]) }}"
           class="group flex flex-col overflow-hidden rounded-xl border border-base-300"
           style="background: linear-gradient(135deg, {{ $item['config']['accent_color'] }} 0%, color-mix(in srgb, {{ $item['config']['accent_color'] }} 60%, black) 100%);">
            <div class="flex items-center gap-5 p-8 text-white">
                @if($p->logo)
                <img src="{{ $p->logoUrl() }}" alt="Logo {{ $p->name }}" class="h-16 w-16 shrink-0 rounded-full bg-white object-contain p-1.5" loading="lazy">
                @endif
                <div class="min-w-0">
                    <span class="text-xs font-black uppercase tracking-[0.3em] text-white/70">Chuyên đề</span>
                    <h2 class="text-2xl font-extrabold mt-2 group-hover:underline">{{ $p->name }}</h2>
                    <p class="text-white/85 mt-1.5 text-sm">{{ $p->slogan ?: $item['config']['tagline'] }}</p>
                </div>
            </div>
        </a>
        @endforeach
    </div>

    @if($provinces->isEmpty())
    <p class="text-center py-10 text-base-content/40">Chưa có tỉnh/thành nào có chuyên đề.</p>
    @endif

</div>
@endsection
