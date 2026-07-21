@extends('layouts.backend')
@section('title', 'Thêm sản phẩm OCOP')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm sản phẩm OCOP</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Sản phẩm đặc trưng đã được chứng nhận OCOP từ 3 sao trở lên</p>
    </div>
    <a href="{{ route('backend.ocop.products.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.ocop.products.store') }}" enctype="multipart/form-data" novalidate data-ocop-product-form>
    @csrf
    @include('ocop::admin.products._form', ['product' => null])
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
        'Modules/Ocop/resources/assets/js/ocop.js',
    ], 'build/backend')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.initFilePondUpload) {
            initFilePondUpload('#cover-filepond', {
                collection: 'cover',
                bindTo: '#cover-media-uuid',
            });
        }
    });
    </script>
@endpush
