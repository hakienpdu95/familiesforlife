@extends('layouts.frontend')

@section('title', $site->name)
@section('meta_description', \Illuminate\Support\Str::limit($site->description ?: $site->name, 160))

@section('content')
<div class="container py-10">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('post.public.home') }}">Trang Chủ</a></li>
            <li><a href="{{ route('heritage.public.index') }}">Di sản & Văn hóa</a></li>
            <li>{{ $site->name }}</li>
        </ul>
    </div>

    {{-- ── Hero ─────────────────────────────────────────────────────────── --}}
    <div class="aspect-[21/9] rounded-xl overflow-hidden bg-base-200 mb-6">
        <img src="{{ $site->getFirstMediaUrl('cover') ? $site->getFirstMediaUrl('cover', 'preview') : asset('images/post-cover-placeholder.svg') }}"
             alt="{{ $site->name }}" class="h-full w-full object-cover">
    </div>

    <div class="flex flex-wrap items-center gap-2 mb-3">
        <span class="badge badge-primary">{{ $site->heritage_type->label() }}</span>
        <span class="badge badge-ghost">{{ $site->rank->label() }}</span>
        <span class="badge badge-outline">{{ $site->visiting_status->label() }}</span>
    </div>

    <h1 class="text-3xl font-extrabold text-base-content">{{ $site->name }}</h1>

    <p class="mt-2 text-sm text-base-content/60">
        {{ trim(collect([$site->address, $site->ward_name, $site->province_name])->filter()->implode(', '), ', ') ?: 'Chưa cập nhật địa chỉ' }}
        @if($site->era) &middot; Niên đại: {{ $site->era }} @endif
    </p>

    @if($site->description)
    <p class="mt-4 text-base-content/80 leading-relaxed">{!! nl2br(e($site->description)) !!}</p>
    @endif

    @if($site->latitude && $site->longitude)
    <div class="mt-4 card bg-base-200/50 border border-base-300 inline-block">
        <div class="card-body p-4">
            <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-1">Toạ độ</p>
            <a href="https://www.google.com/maps?q={{ $site->latitude }},{{ $site->longitude }}" target="_blank" rel="noopener nofollow" class="link link-primary text-sm">
                {{ $site->latitude }}, {{ $site->longitude }}
            </a>
        </div>
    </div>
    @endif

    {{-- ── Bài viết liên quan ───────────────────────────────────────────── --}}
    @if($articles->isNotEmpty())
    <section class="mt-10 pt-8 border-t border-base-200">
        <h2 class="text-xl font-bold text-base-content mb-4">Bài viết liên quan</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($articles as $article)
            @php($t = $article->mainTranslation())
            @if($t)
            <a href="{{ route('post.public.article', ['slug' => $t->slug, 'id' => $t->id]) }}" class="group flex flex-col gap-2">
                <div class="aspect-[16/9] overflow-hidden bg-base-200 rounded-lg">
                    <img src="{{ $article->cover_image_url ?: asset('images/post-cover-placeholder.svg') }}"
                         alt="{{ $t->title }}" class="h-full w-full object-cover" loading="lazy">
                </div>
                <h3 class="font-bold text-sm leading-snug group-hover:text-primary line-clamp-2">{{ $t->title }}</h3>
            </a>
            @endif
            @endforeach
        </div>
    </section>
    @endif

    {{-- ── Lễ hội sắp diễn ra ───────────────────────────────────────────── --}}
    @if($events->isNotEmpty())
    <section class="mt-10 pt-8 border-t border-base-200">
        <h2 class="text-xl font-bold text-base-content mb-4">Lễ hội sắp diễn ra</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($events as $event)
            <a href="{{ route('event.public.show', ['slug' => $event->slug, 'id' => $event->id]) }}" class="group flex flex-col gap-2">
                @if($event->poster_path)
                <div class="aspect-[16/9] overflow-hidden bg-base-200 rounded-lg">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($event->poster_path) }}" alt="{{ $event->title }}" class="h-full w-full object-cover" loading="lazy">
                </div>
                @endif
                <h3 class="font-bold text-sm leading-snug group-hover:text-primary line-clamp-2">{{ $event->title }}</h3>
                <p class="text-xs text-base-content/50">{{ $event->start_date?->format('d/m/Y') }}</p>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ── Sản phẩm OCOP của làng nghề này ─────────────────────────────── --}}
    @if($products->isNotEmpty())
    <section class="mt-10 pt-8 border-t border-base-200">
        <h2 class="text-xl font-bold text-base-content mb-4">Sản phẩm OCOP của làng nghề này</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-5">
            @foreach($products as $product)
            <a href="{{ route('ocop.public.show', ['slug' => $product->slug, 'id' => $product->id]) }}" class="group flex flex-col gap-2">
                <div class="aspect-square rounded-xl overflow-hidden bg-base-200">
                    <img src="{{ $product->getFirstMediaUrl('cover') ?: asset('images/post-cover-placeholder.svg') }}"
                         alt="{{ $product->name }}" class="h-full w-full object-cover" loading="lazy">
                </div>
                <h3 class="text-sm font-bold leading-snug group-hover:text-primary line-clamp-2">{{ $product->name }}</h3>
            </a>
            @endforeach
        </div>
    </section>
    @endif

</div>
@endsection
