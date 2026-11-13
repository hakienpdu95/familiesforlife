@extends('layouts.backend')
@section('title', __('Sửa sản phẩm OCOP'))

@section('content')

<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div class="min-w-0">
        <p class="text-xs font-medium text-base-content/50">
            <a href="{{ route('backend.ocop.products.index') }}" class="link link-hover">{{ __('Sản phẩm OCOP') }}</a>
            <span class="mx-1 text-base-content/30">/</span>{{ __('Sửa sản phẩm') }}
        </p>
        <h1 class="mt-1 text-2xl font-bold text-base-content truncate">{{ $product->name }}</h1>
        <div class="mt-2 flex flex-wrap items-center gap-1.5">
            <span class="badge badge-sm badge-soft badge-warning">{{ str_repeat('★', (int) $product->star_rating) }}</span>
            <span class="badge badge-sm badge-ghost">{{ $product->status->label() }}</span>
        </div>
    </div>
    <a href="{{ route('backend.ocop.products.index') }}" class="btn btn-ghost btn-sm gap-1.5 shrink-0">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        {{ __('Quay lại') }}
    </a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <div>
        <p class="font-semibold">{{ __('Có :count lỗi cần kiểm tra:', ['count' => $errors->count()]) }}</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('backend.ocop.products.update', $product) }}" enctype="multipart/form-data" novalidate data-ocop-product-form>
    @csrf
    @method('PUT')
    @include('ocop::admin.products._form', ['product' => $product])
</form>

@endsection

@push('styles')
    @vite(['Modules/Ocop/resources/assets/sass/ocop.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/filepond.js',
        'resources/js/modules/jodit.js',
        'Modules/Ocop/resources/assets/js/ocop.js',
    ], 'build/backend')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const galleryEl = document.getElementById('gallery-filepond');
        if (window.initFilePondUpload && galleryEl) {
            initFilePondUpload(galleryEl, {
                collection: 'ocop_gallery',
                contextType: galleryEl.dataset.contextType,
                contextId: galleryEl.dataset.contextId,
                maxFiles: Number(galleryEl.dataset.maxFiles),
                allowRevert: true,
            });
        }

        const storyEl = document.getElementById('ocop-story');
        if (window.initJodit && storyEl) {
            initJodit(storyEl, { showWordsCounter: true, showCharsCounter: true });
        }
        if (window.initJodit) {
            ['ocop-description', 'ocop-ingredients', 'ocop-usage-instructions', 'ocop-storage-instructions'].forEach((id) => {
                const el = document.getElementById(id);
                if (el) initJodit(el, { showWordsCounter: true, showCharsCounter: true, removeButtons: ['about', 'classSpan', 'image', 'video', 'file'] });
            });
        }
    });
    </script>
@endpush
