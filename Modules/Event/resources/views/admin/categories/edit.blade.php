@extends('layouts.backend')
@section('title', __('Sửa danh mục sự kiện'))

@section('content')

<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div class="min-w-0">
        <p class="text-xs font-medium text-base-content/50">
            <a href="{{ route('backend.event.categories.index') }}" class="link link-hover">{{ __('Danh mục sự kiện') }}</a>
            <span class="mx-1 text-base-content/30">/</span>{{ __('Sửa danh mục') }}
        </p>
        <h1 class="mt-1 text-2xl font-bold text-base-content truncate">{{ $category->name }}</h1>
        <div class="mt-2 flex flex-wrap items-center gap-1.5">
            <span class="badge badge-sm badge-soft {{ $category->is_active ? 'badge-success' : 'badge-warning' }}">{{ $category->is_active ? __('Đang hiển thị') : __('Đang ẩn') }}</span>
            <span class="badge badge-sm badge-ghost">{{ __(':count sự kiện', ['count' => number_format($category->events_count)]) }}</span>
        </div>
    </div>
    <a href="{{ route('backend.event.categories.index') }}" class="btn btn-ghost btn-sm gap-1.5 shrink-0">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        {{ __('Quay lại') }}
    </a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    <div>
        <p class="font-semibold">{{ __('Có :count lỗi cần kiểm tra:', ['count' => $errors->count()]) }}</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('backend.event.categories.update', $category) }}" novalidate data-event-category-form>
    @csrf
    @method('PUT')
    @include('event::admin.categories._form', ['category' => $category])
</form>

@endsection

@push('styles')
    @vite(['Modules/Event/resources/assets/sass/event.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'Modules/Event/resources/assets/js/event.js',
    ], 'build/backend')
@endpush
