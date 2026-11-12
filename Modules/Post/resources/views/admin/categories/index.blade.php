@extends('layouts.backend')
@section('title', __('Danh mục bài viết'))

@section('content')
@php
    $stats = $tree['stats'];
    $localeLabel = collect($locales)->map(fn ($l) => \App\Support\I18n\PortalLocale::label($l))->implode(', ');
    $coverage = $stats['total'] ? round($stats['translated'] / $stats['total'] * 100) : 0;
    $tabs = [
        '' => [__('Tất cả'), $stats['total']],
        'inactive' => [__('Đang ẩn'), $stats['inactive']],
        'untranslated' => [__('Chưa dịch :language', ['language' => $localeLabel]), $stats['untranslated']],
    ];
@endphp
<div x-data="categoryTreePage(@js(collect($locales)->mapWithKeys(fn ($l) => [$l => \App\Support\I18n\PortalLocale::label($l)])))">
    <script type="application/json" id="category-tree-data">@json($tree['data'])</script>

    @foreach(['success','error'] as $type)
        @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition.opacity.duration.500ms
             class="alert alert-{{ $type }} mb-4 text-sm">
            <span>{{ session($type) }}</span>
            <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
        </div>
        @endif
    @endforeach

    @if($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="flex flex-wrap items-end justify-between gap-3 mb-5">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-base-content">{{ __('Danh mục bài viết') }}</h1>
            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-base-content/60">
                <span>{{ __(':total danh mục, :roots nhóm chính', ['total' => $stats['total'], 'roots' => $stats['roots']]) }}</span>
                <span class="hidden sm:inline text-base-content/20">|</span>
                <span class="inline-flex items-center gap-2">
                    {{ __('Đã dịch :language: :done/:total', ['language' => $localeLabel, 'done' => $stats['translated'], 'total' => $stats['total']]) }}
                    <span class="w-20 h-1.5 rounded-full bg-base-300 overflow-hidden" aria-hidden="true">
                        <span class="block h-full rounded-full {{ $coverage === 100 ? 'bg-success' : 'bg-warning' }}" style="width: {{ $coverage }}%"></span>
                    </span>
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('backend.post.articles.index') }}" class="btn btn-ghost btn-sm">{{ __('← Bài viết') }}</a>
            @can('create', \Modules\Post\Models\PostCategory::class)
            <a href="{{ route('backend.post.categories.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('Thêm danh mục') }}
            </a>
            @endcan
        </div>
    </div>

    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="flex flex-wrap items-center gap-3 px-4 py-3 border-b border-base-200">
            <label class="input input-sm input-bordered flex items-center gap-2 w-full md:w-80">
                <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" x-ref="search" x-model="search" @input.debounce.150ms="apply()" @keydown.escape="search = ''; apply()"
                       placeholder="{{ __('Tìm danh mục...') }}" title="{{ __('Tìm theo tên, đường dẫn hoặc tên tiếng Anh') }}" class="grow bg-transparent outline-none text-sm">
                <kbd class="kbd kbd-xs hidden md:inline-flex" x-show="!search">/</kbd>
            </label>

            <div role="tablist" class="tabs tabs-box tabs-sm bg-base-200/60">
                @foreach($tabs as $value => [$label, $count])
                <button type="button" role="tab" class="tab gap-1.5" :class="status === @js($value) && 'tab-active'" @click="setStatus(@js($value))">
                    {{ $label }}<span class="text-xs tabular-nums opacity-60">{{ $count }}</span>
                </button>
                @endforeach
            </div>

            <div class="ml-auto flex items-center gap-1">
                <button type="button" class="btn btn-ghost btn-xs" @click="setAll(true)">{{ __('Mở rộng tất cả') }}</button>
                <button type="button" class="btn btn-ghost btn-xs" @click="setAll(false)">{{ __('Thu gọn tất cả') }}</button>
            </div>
        </div>

        <div class="tabulator-daisy category-tree-table">
            <div id="category-table"></div>
        </div>

        <div x-show="filtering" x-cloak class="flex items-center justify-between gap-3 px-4 py-2.5 border-t border-base-200 text-xs text-base-content/60">
            <span x-text="@js(__(':count danh mục khớp')).replace(':count', matched)"></span>
            <button type="button" class="btn btn-ghost btn-xs" @click="clearFilters()">{{ __('Xoá bộ lọc') }}</button>
        </div>

    </div>

</div>

<ul id="categoryRowMenu" class="menu menu-sm fixed z-50 hidden w-60 rounded-box border border-base-200 bg-base-100 p-1 shadow-lg" role="menu"></ul>

{{-- ── Delete confirm modal ─────────────────────────────────────────────── --}}
<dialog id="categoryDeleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">{{ __('Xác nhận xoá') }}</h3>
        <p class="py-3 text-sm text-base-content/70">
            {{ __('Bạn có chắc muốn xoá danh mục') }}
            <strong id="categoryDeleteItemName" class="text-base-content"></strong>?
        </p>
        <div class="modal-action mt-4">
            <button id="categoryConfirmDeleteBtn" class="btn btn-error btn-sm">{{ __('Xoá') }}</button>
            <button class="btn btn-ghost btn-sm" onclick="categoryDeleteModal.close()">{{ __('Hủy') }}</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/Post/resources/assets/sass/post.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'Modules/Post/resources/assets/js/post.js',
    ], 'build/backend')
@endpush
