@extends('layouts.backend')
@section('title', 'Tin nóng')

@section('content')
<div x-data="breakingNewsListPage({{ Js::from([
    'apiUrl' => route('backend.api.breaking-news.items'),
]) }})">

    @foreach(['success','error'] as $type)
        @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition.opacity.duration.500ms
             class="alert alert-{{ $type }} mb-4 text-sm">
            <span>{{ session($type) }}</span>
            <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
        </div>
        @endif
    @endforeach

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Tin nóng</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Dải ticker "tin nóng" ghim đầu trang chủ</p>
        </div>
        <div class="flex items-center gap-2">
            @can('create', \Modules\Post\Models\PostBreakingNews::class)
            <a href="{{ route('backend.post.breaking-news.items.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Đánh dấu tin nóng
            </a>
            @endcan
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-hidden tabulator-daisy">
            <div id="breaking-news-table"></div>
        </div>
    </div>

</div>

<dialog id="breakingNewsDeleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận gỡ</h3>
        <p class="py-3 text-sm text-base-content/70">Bạn có chắc muốn gỡ tin nóng này?</p>
        <div class="modal-action mt-4">
            <button id="breakingNewsConfirmDeleteBtn" class="btn btn-error btn-sm">Xoá</button>
            <button class="btn btn-ghost btn-sm" onclick="breakingNewsDeleteModal.close()">Hủy</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('styles')
    <x-tabulator-theme />
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'Modules/Post/resources/assets/js/post.js',
    ], 'build/backend')
@endpush
