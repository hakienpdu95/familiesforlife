@extends('layouts.backend')
@section('title', 'Thêm chủ thể OCOP')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm chủ thể OCOP</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Doanh nghiệp, HTX hoặc hộ kinh doanh có sản phẩm OCOP</p>
    </div>
    <a href="{{ route('backend.ocop-subjects.index') }}" class="btn btn-ghost btn-sm">← Quay lại</a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 text-sm">
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('backend.ocop-subjects.store') }}" enctype="multipart/form-data" novalidate data-ocop-subject-form>
    @csrf
    @include('ocopsubject::admin._form', ['ocopSubject' => null])
</form>

@endsection

@push('styles')
    @vite(['Modules/OcopSubject/resources/assets/sass/ocop-subject.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/filepond.js',
        'Modules/OcopSubject/resources/assets/js/ocop-subject.js',
    ], 'build/backend')
@endpush
