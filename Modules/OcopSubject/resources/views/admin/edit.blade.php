@extends('layouts.backend')
@section('title', 'Sửa chủ thể OCOP')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa chủ thể OCOP</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $ocopSubject->name }}</p>
    </div>
    <div class="flex items-center gap-2">
        @can('create', \Modules\Ocop\Models\OcopProduct::class)
        @if($ocopSubject->is_active)
        <a href="{{ route('backend.ocop.products.create', ['subject_id' => $ocopSubject->id]) }}" class="btn btn-primary btn-sm">+ Thêm sản phẩm cho chủ thể này</a>
        @endif
        @endcan
        <a href="{{ route('backend.ocop-subjects.index') }}" class="btn btn-ghost btn-sm">← Quay lại</a>
    </div>
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

<form method="POST" action="{{ route('backend.ocop-subjects.update', $ocopSubject) }}" enctype="multipart/form-data" novalidate data-ocop-subject-form>
    @csrf
    @method('PUT')
    @include('ocopsubject::admin._form', ['ocopSubject' => $ocopSubject])
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
