@extends('layouts.frontend')

@section('title', $search ? "Tìm sự kiện: {$search}" : 'Sự Kiện')
@section('meta_description', 'Sự kiện, hoạt động vui chơi và trải nghiệm cho gia đình — cập nhật liên tục.')

@push('meta')
<link rel="canonical" href="{{ route('event.public.home') }}">
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

@if($eventCategories->isNotEmpty())
<div class="flex text-white text-sm font-black uppercase tracking-wide text-center">
    @php $palette = ['bg-secondary', 'bg-primary', 'bg-warning']; @endphp
    @foreach($eventCategories->take(3) as $i => $cat)
    <a href="{{ route('event.public.category', ['category' => $cat->slug]) }}"
       class="flex-1 py-4 {{ $palette[$i % count($palette)] }} hover:opacity-90">{{ $cat->name }}</a>
    @endforeach
</div>
@endif

<div class="container">
    <div class="py-10">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <h1 class="text-2xl font-bold text-base-content">
                {{ $search ? "Kết quả tìm kiếm: “{$search}”" : 'Sự Kiện Sắp Diễn Ra' }}
            </h1>
            <a href="{{ route('event.public.submit.form') }}" class="btn btn-sm border-none bg-secondary text-white hover:bg-secondary/90">Gửi Sự Kiện Của Bạn</a>
        </div>

        <form method="GET" class="mb-6">
            <input type="text" name="q" value="{{ $search }}" placeholder="Tìm sự kiện..."
                   class="input input-bordered input-sm w-full sm:w-72">
        </form>

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
</div>
@endsection
