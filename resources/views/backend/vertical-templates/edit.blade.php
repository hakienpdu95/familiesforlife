@extends('layouts.backend')
@section('title', 'Sửa bản mẫu — ' . $template->label)

@section('content')
<div class="max-w-4xl space-y-6">

    <div>
        <div class="flex items-center gap-2 text-sm text-base-content/40 mb-1">
            <a href="{{ route('backend.vertical-templates.index') }}" class="hover:text-primary">Thư viện mẫu Vertical</a>
            <span>/</span>
            <span>{{ $template->label }}</span>
        </div>
        <h1 class="text-2xl font-bold text-base-content">{{ $template->label }}</h1>
    </div>

    @if(session('success'))
    <div class="alert alert-success py-2.5 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Error banner --}}
    @if($errors->any())
    <div class="alert alert-error py-3 px-4 flex items-start gap-3 text-sm">
        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
            <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
            <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Thông tin cơ bản --}}
    <details class="collapse collapse-arrow bg-base-100 border border-base-200 shadow-sm" {{ $errors->any() ? 'open' : '' }}>
        <summary class="collapse-title text-sm font-semibold">Thông tin cơ bản</summary>
        <div class="collapse-content">
            <form method="POST" action="{{ route('backend.vertical-templates.update', $template) }}"
                  novalidate data-vertical-template-form class="space-y-4"
                  data-locked-org-id="{{ $template->organization_id }}">
                @csrf
                @method('PUT')
                @include('backend.vertical-templates._fields')
                <div class="flex justify-end gap-2 pt-2 border-t border-base-200">
                    <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Lưu thông tin
                    </button>
                </div>
            </form>
        </div>
    </details>

    {{-- Builder phase/checklist --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            @include('backend.vertical-templates.builder')
        </div>
    </div>

</div>
@endsection

@push('styles')
    @vite(['Modules/Deployment/resources/assets/sass/deployment.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'Modules/Deployment/resources/assets/js/deployment.js',
    ], 'build/backend')
@endpush
