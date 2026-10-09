@extends('layouts.frontend')

@section('title', $provinceName ? "Sản phẩm OCOP {$provinceName}" : 'Sản phẩm OCOP')
@section('meta_description', 'Sản phẩm đặc trưng OCOP các địa phương — hạng sao, nhà sản xuất, thông tin liên hệ mua hàng.')

@php
    $search = trim((string) request('q')) ?: null;
    // Tham số lọc hiện tại — dùng để dựng link gỡ từng chip (bỏ đúng 1 điều kiện, giữ phần còn lại).
    $params = array_filter([
        'province'    => $provinceCode,
        'q'           => $search,
        'category_id' => $categoryId,
        'ward'        => $wardCodes ?: null,
        'stars'       => $stars,
    ]);
    $params['ward'] = $wardCodes[0] ?? null;
    $params = array_filter($params);
    $without = fn (string $key) => route('ocop.public.index', array_diff_key($params, [$key => true]));
    $wardNames = $facets['wards']->pluck('ward_name', 'ward_code');
    $selectedWard = $wardCodes[0] ?? null;
    $activeCount = ($selectedWard ? 1 : 0) + ($stars ? 1 : 0) + ($categoryId ? 1 : 0) + ($search ? 1 : 0);
    $categoryName = $categoryId ? collect($categoryOptions)->firstWhere('id', $categoryId)['name'] ?? null : null;
@endphp

@section('content')
<div class="container">

    <div class="flex flex-wrap items-end justify-between gap-2 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">
                Sản phẩm OCOP @if($provinceName)<span class="text-primary">· {{ $provinceName }}</span>@endif
            </h1>
            <p class="text-sm text-base-content/60 mt-1">{{ number_format($products->total(), 0, ',', '.') }} sản phẩm</p>
        </div>
    </div>

    <form method="GET" action="{{ route('ocop.public.index') }}" id="ocop-filter"
          x-data="{ open: false }"
          class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 items-start">

        @if($provinceCode)
        <input type="hidden" name="province" value="{{ $provinceCode }}">
        @endif

        {{-- ── Bộ lọc ──────────────────────────────────────────────────────── --}}
        <aside class="lg:sticky lg:top-4">
            <button type="button" @click="open = !open" :aria-expanded="open"
                    class="btn btn-sm btn-outline w-full justify-between lg:hidden mb-3">
                <span>Bộ lọc @if($activeCount)<span class="badge badge-primary badge-sm">{{ $activeCount }}</span>@endif</span>
                <span x-text="open ? '▲' : '▼'">▼</span>
            </button>

            <div class="space-y-5 hidden lg:block" :class="{ 'hidden': !open }">

                <div class="space-y-2">
                    <label for="ocop-q" class="text-sm font-semibold text-base-content">Tìm kiếm</label>
                    <div class="join w-full">
                        <input id="ocop-q" type="search" name="q" value="{{ $search }}" placeholder="Tên sản phẩm..."
                               class="input input-bordered input-sm join-item flex-1 min-w-0">
                        <button class="btn btn-sm btn-primary join-item" aria-label="Tìm">Tìm</button>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="ts-category" class="text-sm font-semibold text-base-content">Danh mục</label>
                    {{-- Phân cấp Sản phẩm → Nhóm → Phân nhóm; chọn cấp cha = lấy cả cấp con.
                         Thụt lề bằng NBSP để vẫn đọc được khi TomSelect chưa nạp (fallback select thường). --}}
                    <select id="ts-category" name="category_id" onchange="this.form.submit()"
                            data-ts-placeholder="Tất cả danh mục"
                            class="select select-bordered select-sm w-full">
                        <option value="">Tất cả danh mục</option>
                        @foreach($categoryOptions as $c)
                        <option value="{{ $c['id'] }}" data-depth="{{ $c['depth'] }}" @selected($categoryId === $c['id'])>{{ str_repeat("\u{00A0}\u{00A0}\u{00A0}", $c['depth']) }}{{ $c['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                @if($provinceCode && $facets['wards']->isNotEmpty())
                <fieldset class="space-y-2"
                          x-data="{ term: '', norm(s) { return s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd'); } }">
                    <legend class="text-sm font-semibold text-base-content mb-2">Địa phương</legend>
                    @if($facets['wards']->count() > 6)
                    <input type="search" x-model="term" placeholder="Tìm phường/xã..." aria-label="Tìm phường/xã"
                           class="input input-bordered input-sm w-full">
                    @endif
                    <div class="max-h-72 overflow-y-auto space-y-0.5 pr-1">
                        <label class="flex items-center gap-2 cursor-pointer rounded px-1 py-1 hover:bg-base-200" x-show="!term">
                            <input type="radio" name="ward" value="" class="radio radio-sm radio-primary"
                                   onchange="this.form.submit()" @checked(! $selectedWard)>
                            <span class="text-sm flex-1">Tất cả địa phương</span>
                            <span class="text-xs text-base-content/50">{{ $facets['wards_total'] }}</span>
                        </label>
                        @foreach($facets['wards'] as $w)
                        <label class="flex items-center gap-2 cursor-pointer rounded px-1 py-1 hover:bg-base-200"
                               data-name="{{ $w->ward_name }}"
                               x-show="!term || norm($el.dataset.name).includes(norm(term))">
                            <input type="radio" name="ward" value="{{ $w->ward_code }}"
                                   class="radio radio-sm radio-primary"
                                   onchange="this.form.submit()" @checked($selectedWard === $w->ward_code)>
                            <span class="text-sm flex-1">{{ $w->ward_name }}</span>
                            <span class="text-xs text-base-content/50">{{ $w->total }}</span>
                        </label>
                        @endforeach
                    </div>
                </fieldset>
                @endif

                <fieldset class="space-y-1">
                    <legend class="text-sm font-semibold text-base-content mb-2">Xếp hạng sao</legend>
                    <label class="flex items-center gap-2 cursor-pointer rounded px-1 py-1 hover:bg-base-200">
                        <input type="radio" name="stars" value="" class="radio radio-sm radio-primary"
                               onchange="this.form.submit()" @checked(! $stars)>
                        <span class="text-sm flex-1">Tất cả</span>
                    </label>
                    @foreach($facets['stars'] as $level => $total)
                    @php $checked = $stars === $level; $empty = $total === 0 && ! $checked; @endphp
                    <label class="flex items-center gap-2 rounded px-1 py-1 {{ $empty ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer hover:bg-base-200' }}">
                        <input type="radio" name="stars" value="{{ $level }}" class="radio radio-sm radio-primary"
                               onchange="this.form.submit()" @checked($checked) @disabled($empty)>
                        <span class="flex-1 text-sm" aria-label="{{ $level }} sao">
                            <span class="text-warning tracking-tight">{{ str_repeat('★', $level) }}</span><span class="text-base-content/20 tracking-tight">{{ str_repeat('★', max(0, 5 - $level)) }}</span>
                        </span>
                        <span class="text-xs text-base-content/50">{{ $total }}</span>
                    </label>
                    @endforeach
                </fieldset>

                @if($activeCount)
                <a href="{{ route('ocop.public.index', array_filter(['province' => $provinceCode])) }}"
                   class="btn btn-ghost btn-sm w-full text-error">Xoá tất cả bộ lọc</a>
                @endif
            </div>
        </aside>

        {{-- ── Kết quả ─────────────────────────────────────────────────────── --}}
        <div class="min-w-0">

            @if($activeCount)
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <span class="text-xs text-base-content/50">Đang lọc:</span>
                @if($search)
                <a href="{{ $without('q') }}" class="badge badge-outline gap-1 hover:badge-error">“{{ $search }}” ✕</a>
                @endif
                @if($categoryName)
                <a href="{{ $without('category_id') }}" class="badge badge-outline gap-1 hover:badge-error">{{ $categoryName }} ✕</a>
                @endif
                @if($selectedWard)
                <a href="{{ $without('ward') }}" class="badge badge-outline gap-1 hover:badge-error">{{ $wardNames[$selectedWard] ?? $selectedWard }} ✕</a>
                @endif
                @if($stars)
                <a href="{{ $without('stars') }}" class="badge badge-outline gap-1 hover:badge-error">{{ $stars }} sao ✕</a>
                @endif
            </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                @forelse($products as $product)
                <a href="{{ route('ocop.public.show', ['slug' => $product->slug, 'id' => $product->id]) }}" class="group flex flex-col gap-3">
                    <div class="aspect-square rounded-sm overflow-hidden bg-base-200">
                        <img src="{{ $product->imageUrl('medium') ?: asset('images/post-cover-placeholder.svg') }}"
                             alt="{{ $product->name }}" class="h-full w-full object-cover" loading="lazy">
                    </div>
                    <div>
                        @if($product->category)
                        <span class="text-xs font-black uppercase tracking-wide text-primary">{{ $product->category?->name }}</span>
                        @endif
                        <h3 class="font-bold leading-snug group-hover:text-primary mt-1">{{ $product->name }}</h3>
                        <p class="mt-1 text-xs text-base-content/60">
                            <span class="text-warning" aria-label="{{ $product->star_rating }} sao">{{ str_repeat('★', (int) $product->star_rating) }}</span>
                            · {{ collect([$product->ward_name, $provinceCode ? null : $product->province_name])->filter()->implode(', ') ?: 'Chưa rõ địa phương' }}
                        </p>
                    </div>
                </a>
                @empty
                <div class="col-span-full text-center py-12 text-base-content/50">
                    <p>Không có sản phẩm phù hợp với bộ lọc.</p>
                    @if($activeCount)
                    <a href="{{ route('ocop.public.index', array_filter(['province' => $provinceCode])) }}" class="link link-primary text-sm mt-2 inline-block">Xoá bộ lọc</a>
                    @endif
                </div>
                @endforelse
            </div>

            @if($products->hasPages())
            <div class="mt-8">{{ $products->onEachSide(1)->links() }}</div>
            @endif
        </div>

    </form>

</div>
@endsection

@push('styles')
    @vite(['resources/scss/tom-select-frontend.scss'], 'build/frontend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'Modules/Ocop/resources/assets/js/ocop-public.js',
    ], 'build/frontend')
@endpush
