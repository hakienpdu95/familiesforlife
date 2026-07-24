@extends('layouts.frontend')

@php
    $isSale = $listing->isSale();
    $pageTitle = $content['title'];
@endphp

@section('title', $pageTitle)
@section('meta_description', $content['description'] ? \Illuminate\Support\Str::limit(strip_tags($content['description']), 160) : $pageTitle)

@section('content')
<div class="container py-10 max-w-4xl mx-auto">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('post.public.home') }}">Trang Chủ</a></li>
            <li><a href="{{ route($isSale ? 'real-estate.public.sale.index' : 'real-estate.public.rent.index') }}">{{ $isSale ? 'Nhà đất bán' : 'Nhà đất thuê' }}</a></li>
            <li>{{ $pageTitle }}</li>
        </ul>
    </div>

    {{-- ── Gallery ảnh (§7.3 spec Bán) ─────────────────────────────────────── --}}
    @php $images = $listing->galleryUrls('medium'); @endphp
    @if(!empty($images))
    <div class="grid sm:grid-cols-4 gap-2 mb-6">
        <img src="{{ $images[0] }}" alt="{{ $pageTitle }}" class="sm:col-span-4 w-full aspect-video object-cover rounded-xl">
        @foreach(array_slice($images, 1) as $img)
        <img src="{{ $img }}" alt="" class="w-full aspect-square object-cover rounded-lg">
        @endforeach
    </div>
    @endif

    <div class="flex items-center gap-2 mb-2">
        @if($listing->is_urgent)
        <span class="badge badge-error">Bán gấp</span>
        @endif
        <span class="badge badge-ghost">{{ $listing->property_type->label() }}</span>
    </div>

    <h1 class="text-2xl font-bold text-base-content mb-1">{{ $pageTitle }}</h1>
    <p class="text-2xl font-bold text-primary mb-4">{{ $listing->display_price }}</p>

    @if($listing->isRent() && $listing->total_monthly_cost)
    <p class="text-sm text-base-content/60 mb-4">Tổng chi phí/tháng (kèm phí quản lý): <strong>{{ number_format($listing->total_monthly_cost) }} đ</strong></p>
    @endif

    @if($content['address_detail'])
    <p class="text-sm text-base-content/70 mb-4">📍 {{ $content['address_detail'] }}</p>
    @endif

    {{-- ── Bảng thông tin chi tiết — chỉ hiện cột có giá trị (§7.3 spec Bán) ────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm mb-6">
        <div class="card-body">
            <h2 class="font-semibold mb-3">Thông tin chi tiết</h2>
            <dl class="grid sm:grid-cols-2 gap-3 text-sm">
                @if($listing->area)
                <div><dt class="text-base-content/50">Diện tích</dt><dd class="font-medium">{{ number_format((float) $listing->area, 1) }} m²</dd></div>
                @endif
                @if($listing->bedrooms)
                <div><dt class="text-base-content/50">Phòng ngủ</dt><dd class="font-medium">{{ $listing->bedrooms }}</dd></div>
                @endif
                @if($listing->bathrooms)
                <div><dt class="text-base-content/50">Phòng tắm</dt><dd class="font-medium">{{ $listing->bathrooms }}</dd></div>
                @endif
                @if($listing->floors)
                <div><dt class="text-base-content/50">Số tầng</dt><dd class="font-medium">{{ $listing->floors }}</dd></div>
                @endif
                @if($listing->interior_status)
                <div><dt class="text-base-content/50">Nội thất</dt><dd class="font-medium">{{ $listing->interior_status->label() }}</dd></div>
                @endif
                @if($content['direction'] ?? null)
                <div><dt class="text-base-content/50">Hướng</dt><dd class="font-medium">{{ \Modules\RealEstate\Enums\CompassDirection::from($content['direction'])->label() }}</dd></div>
                @endif
                @if($content['legal_status'] ?? null)
                <div><dt class="text-base-content/50">Pháp lý</dt><dd class="font-medium">{{ \Modules\RealEstate\Enums\LegalStatus::from($content['legal_status'])->label() }}</dd></div>
                @endif
                @if($isSale && $listing->house_subtype)
                <div><dt class="text-base-content/50">Loại nhà</dt><dd class="font-medium">{{ $listing->house_subtype->label() }}</dd></div>
                @endif
                @if($isSale && $listing->apartment_subtype)
                <div><dt class="text-base-content/50">Loại căn hộ</dt><dd class="font-medium">{{ $listing->apartment_subtype->label() }}</dd></div>
                @endif
                @if($content['project_name'] ?? null)
                <div><dt class="text-base-content/50">Dự án</dt><dd class="font-medium">{{ $content['project_name'] }}</dd></div>
                @endif
                @if($listing->usage_status)
                <div><dt class="text-base-content/50">Tình trạng</dt><dd class="font-medium">{{ $listing->usage_status->label() }}</dd></div>
                @endif
                @if($listing->isRent() && $listing->deposit)
                <div><dt class="text-base-content/50">Tiền cọc</dt><dd class="font-medium">{{ number_format((float) $listing->deposit) }} đ</dd></div>
                @endif
                @if($listing->isRent() && $listing->rental_period_months)
                <div><dt class="text-base-content/50">Thời hạn thuê</dt><dd class="font-medium">{{ $listing->rental_period_months }} tháng</dd></div>
                @endif
                @if($listing->isRent() && $listing->management_fee)
                <div><dt class="text-base-content/50">Phí quản lý/tháng</dt><dd class="font-medium">{{ number_format((float) $listing->management_fee) }} đ</dd></div>
                @endif
            </dl>
        </div>
    </div>

    @if($content['description'])
    <div class="card bg-base-100 border border-base-200 shadow-sm mb-6">
        <div class="card-body prose max-w-none">
            {!! nl2br(e($content['description'])) !!}
        </div>
    </div>
    @endif

    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h2 class="font-semibold mb-2">Liên hệ</h2>
            <p class="text-sm text-base-content/70">{{ $listing->organization?->name }}</p>
        </div>
    </div>

</div>
@endsection
