@extends('layouts.backend')
@section('title', 'Sửa di tích')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa di tích</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $site->name }}</p>
    </div>
    <a href="{{ route('backend.heritage.sites.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('backend.heritage.sites.update', $site) }}" enctype="multipart/form-data" novalidate data-heritage-site-form>
    @csrf
    @method('PUT')
    @include('heritage::admin.sites._form', ['site' => $site])
</form>

@endsection

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/filepond.js',
        'Modules/Heritage/resources/assets/js/heritage.js',
    ], 'build/backend')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const coverEl = document.getElementById('cover-filepond');
        if (window.initFilePondUpload && coverEl) {
            initFilePondUpload(coverEl, {
                collection: 'cover',
                contextType: coverEl.dataset.contextType,
                contextId: coverEl.dataset.contextId,
                allowRevert: true,
            });
        }
    });
    </script>
@endpush
