@extends('layouts.backend')
@section('title', __('Sửa chủ thể OCOP'))

@section('content')

<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div class="min-w-0">
        <p class="text-xs font-medium text-base-content/50">
            <a href="{{ route('backend.ocop-subjects.index') }}" class="link link-hover">{{ __('Chủ thể OCOP') }}</a>
            <span class="mx-1 text-base-content/30">/</span>{{ __('Sửa chủ thể') }}
        </p>
        <h1 class="mt-1 text-2xl font-bold text-base-content truncate">{{ $ocopSubject->name }}</h1>
        <div class="mt-2 flex flex-wrap items-center gap-1.5">
            @if($ocopSubject->organization_type)<span class="badge badge-sm badge-ghost">{{ $ocopSubject->organization_type->label() }}</span>@endif
            @if($ocopSubject->ocop_star)<span class="badge badge-sm badge-soft badge-warning">{{ str_repeat('★', (int) $ocopSubject->ocop_star) }}</span>@endif
            <span class="badge badge-sm badge-soft {{ $ocopSubject->is_active ? 'badge-success' : 'badge-warning' }}">{{ $ocopSubject->is_active ? __('Đang hoạt động') : __('Ngừng hoạt động') }}</span>
        </div>
    </div>
    <div class="flex items-center gap-2 shrink-0">
        @can('create', \Modules\Ocop\Models\OcopProduct::class)
        @if($ocopSubject->is_active)
        <a href="{{ route('backend.ocop.products.create', ['subject_id' => $ocopSubject->id]) }}" class="btn btn-primary btn-sm">{{ __('+ Thêm sản phẩm cho chủ thể này') }}</a>
        @endif
        @endcan
        <a href="{{ route('backend.ocop-subjects.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            {{ __('Quay lại') }}
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 text-sm">
    <div>
        <p class="font-semibold">{{ __('Có :count lỗi cần kiểm tra:', ['count' => $errors->count()]) }}</p>
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
        'resources/js/modules/jodit.js',
        'Modules/OcopSubject/resources/assets/js/ocop-subject.js',
    ], 'build/backend')
@endpush
