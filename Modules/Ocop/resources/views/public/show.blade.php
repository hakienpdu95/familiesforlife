@extends('layouts.frontend')

@section('title', $product->name)
@section('meta_description', $product->description ?: $product->name)

@section('content')
<div class="container py-10">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('post.public.home') }}">Trang Chủ</a></li>
            <li><a href="{{ route('ocop.public.index') }}">Sản phẩm OCOP</a></li>
            <li>{{ $product->name }}</li>
        </ul>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
        <div class="aspect-square rounded-xl overflow-hidden bg-base-200">
            <img src="{{ $product->getFirstMediaUrl('cover') ? $product->getFirstMediaUrl('cover', 'preview') : asset('images/post-cover-placeholder.svg') }}"
                 alt="{{ $product->name }}" class="h-full w-full object-cover">
        </div>

        <div>
            @if($product->category)
            <span class="text-xs font-black uppercase tracking-wide text-primary">{{ $product->category->name }}</span>
            @endif
            <h1 class="text-3xl font-extrabold text-base-content mt-2">{{ $product->name }}</h1>

            <p class="mt-3 text-lg text-warning" title="Hạng {{ $product->star_rating }} sao">
                {{ str_repeat('★', $product->star_rating) }}<span class="text-base-content/20">{{ str_repeat('★', 5 - $product->star_rating) }}</span>
                <span class="text-sm text-base-content/60 align-middle">({{ $product->star_rating }} sao OCOP)</span>
            </p>

            @if($product->description)
            <p class="mt-4 text-base-content/80 leading-relaxed">{{ $product->description }}</p>
            @endif

            <div class="mt-6 card bg-base-200/50 border border-base-300">
                <div class="card-body p-4 space-y-1.5">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-1">Nhà sản xuất</p>
                    @if($product->producer_name)
                    <p class="text-sm font-medium">{{ $product->producer_name }}</p>
                    @endif
                    @if($product->producer_address || $product->ward_name || $product->province_name)
                    <p class="text-sm text-base-content/60">
                        {{ trim(collect([$product->producer_address, $product->ward_name, $product->province_name])->filter()->implode(', '), ', ') }}
                    </p>
                    @endif
                    @if(! $product->producer_name && ! $product->producer_address && ! $product->province_name)
                    <p class="text-sm text-base-content/40">Chưa cập nhật thông tin.</p>
                    @endif
                    @if($heritageSite)
                    <p class="text-sm mt-1">
                        <a href="{{ route('heritage.public.show', ['slug' => $heritageSite->slug, 'id' => $heritageSite->id]) }}" class="link link-primary">
                            Làng nghề/di tích: {{ $heritageSite->name }}
                        </a>
                    </p>
                    @endif
                </div>
            </div>

            @if($product->purchase_url)
            <a href="{{ $product->purchase_url }}" target="_blank" rel="noopener nofollow"
               class="btn btn-primary btn-sm mt-6 gap-1.5">
                Mua sản phẩm
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
            @endif
        </div>
    </div>

</div>
@endsection
