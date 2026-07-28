@extends('layouts.frontend')

@php
    $isSale = $listingType->value === 'sale';
    $pageTitle = $isSale ? 'Nhà đất bán' : 'Nhà đất thuê';
    $routePrefix = 'real-estate.public.' . $listingType->value;
@endphp

@section('title', $pageTitle)
@section('meta_description', $pageTitle . ' — tin đăng đã qua kiểm duyệt')

@section('content')
<div class="container py-10">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('post.public.home') }}">Trang Chủ</a></li>
            <li>{{ $pageTitle }}</li>
        </ul>
    </div>

    <h1 class="text-2xl font-bold text-base-content mb-6">{{ $pageTitle }}</h1>

    {{-- ── Bộ lọc — filter trực tiếp trên cột SQL thật, không cần Meilisearch (§7.2 spec Bán) --}}
    <form method="GET" class="card bg-base-100 border border-base-200 shadow-sm mb-6">
        <div class="card-body grid sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <select name="property_type" class="select select-bordered select-sm">
                <option value="">-- Loại hình --</option>
                @foreach(\Modules\RealEstate\Enums\PropertyType::validFor($listingType) as $type)
                <option value="{{ $type->value }}" @selected(request('property_type') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
            <input type="number" name="price_min" value="{{ request('price_min') }}" placeholder="Giá từ (VNĐ)" class="input input-bordered input-sm">
            <input type="number" name="price_max" value="{{ request('price_max') }}" placeholder="Giá đến (VNĐ)" class="input input-bordered input-sm">
            <input type="number" name="bedrooms" value="{{ request('bedrooms') }}" placeholder="Số phòng ngủ tối thiểu" class="input input-bordered input-sm">
            <button type="submit" class="btn btn-primary btn-sm">Lọc</button>
        </div>
    </form>

    <section class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($listings as $listing)
        @php $content = $listing->publicContent(); @endphp
        <a href="{{ route("{$routePrefix}.show", ['slug' => $listing->slug, 'id' => $listing->id]) }}"
           class="card bg-base-100 border border-base-200 shadow-sm hover:shadow-md transition-shadow overflow-hidden">
            <figure class="aspect-video bg-base-200">
                @php $firstImage = $listing->galleryUrls('medium')[0] ?? null; @endphp
                @if($firstImage)
                <img src="{{ $firstImage }}" alt="{{ $content['title'] }}" class="w-full h-full object-cover">
                @else
                <div class="w-full h-full flex items-center justify-center text-base-content/30">Chưa có ảnh</div>
                @endif
            </figure>
            <div class="card-body p-3">
                @if($listing->is_urgent)
                <span class="badge badge-error badge-sm mb-1">Bán gấp</span>
                @endif
                <h2 class="font-semibold text-base-content truncate">{{ $content['title'] }}</h2>
                <p class="text-primary font-bold">{{ $listing->display_price }}</p>
                <p class="text-xs text-base-content/50">
                    {{ $listing->area ? number_format((float) $listing->area, 0) . ' m² · ' : '' }}
                    @if($listing->bedrooms) {{ $listing->bedrooms }} PN @endif
                </p>
            </div>
        </a>
        @empty
        <p class="col-span-full text-center text-base-content/40 py-10">Chưa có tin nào.</p>
        @endforelse
    </section>

    @if($listings->hasPages())
    <div class="pt-10 flex justify-center">
        {{ $listings->onEachSide(1)->links() }}
    </div>
    @endif

</div>
@endsection
