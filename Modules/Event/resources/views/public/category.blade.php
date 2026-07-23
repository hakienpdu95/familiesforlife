@extends('layouts.frontend')

@section('title', $category->name.' — Sự Kiện')
@section('meta_description', $category->name.' — sự kiện và hoạt động cho gia đình.')

@push('meta')
<link rel="canonical" href="{{ route('event.public.category', ['category' => $category->slug]) }}">
@endpush

@php
    // "Tin to" + "Xem thêm sự kiện" (load-more) chỉ áp dụng trang 1/không tìm kiếm — cùng
    // nguyên tắc danh-muc/{slug} của Post (public/category.blade.php).
    $isMagazine    = ! $search && $events->currentPage() === 1;
    $collection    = $events->getCollection();
    $shownEventIds = $isMagazine
        ? $collection->pluck('id')->when($lead, fn ($ids) => $ids->push($lead->id))->values()
        : collect();
    $lastEvent = $isMagazine ? $collection->last() : null;
@endphp

@section('content')
<div class="container py-10">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('event.public.home') }}">Sự Kiện</a></li>
            @if($category->parent)
            <li><a href="{{ route('event.public.category', ['category' => $category->parent->slug]) }}">{{ $category->parent->name }}</a></li>
            @endif
            <li><a href="{{ route('event.public.category', ['category' => $category->slug]) }}">{{ $category->name }}</a></li>
        </ul>
    </div>

    <h1 class="text-2xl font-bold text-base-content mb-6">
        {{ $search ? "Kết quả tìm kiếm trong “{$category->name}”: {$search}" : $category->name }}
    </h1>

    @if($lead)
    <div class="mb-8">
        <x-frontend.event-card :event="$lead" size="lg" />
    </div>
    @endif

    @if($isMagazine)
    <div x-data="loadMoreEvents({
             endpoint: '{{ route('event.public.load-more') }}',
             exclude: '{{ $shownEventIds->implode(',') }}',
             afterStartDate: {{ $lastEvent ? "'".$lastEvent->start_date->toDateString()."'" : 'null' }},
             afterId: {{ $lastEvent?->id ?? 'null' }},
             loaded: {{ $shownEventIds->count() }},
             maxTotal: {{ config('event.load_more_max_total') }},
             hasMore: {{ ($events->hasMorePages() && $shownEventIds->count() < config('event.load_more_max_total')) ? 'true' : 'false' }},
             categoryId: {{ $category->id }},
             limit: 12,
         })">
        <section class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6" x-ref="grid">
            @forelse($collection as $event)
            <x-frontend.event-card :event="$event" size="sm" />
            @empty
            <p class="col-span-full text-center text-base-content/40 py-10">Chưa có sự kiện nào sắp diễn ra.</p>
            @endforelse
        </section>

        <div class="pt-10 flex justify-center" x-show="hasMore" x-cloak>
            <button type="button" class="btn btn-primary" @click="loadMore()" :disabled="loading">
                <span x-show="!loading">Xem thêm sự kiện</span>
                <span x-show="loading" x-cloak>Đang tải...</span>
            </button>
        </div>
    </div>
    @else
    <x-frontend.event-grid :events="$events" />
    @endif

</div>
@endsection
