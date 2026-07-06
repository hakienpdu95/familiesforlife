@extends('layouts.backend')
@section('title', 'Cấp chứng nhận thủ công')

@push('styles')
    @vite(['Modules/Assessment/resources/assets/sass/assessment.scss'], 'build/backend')
@endpush

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Cấp chứng nhận thủ công</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Cấp chứng nhận AI trực tiếp cho nhân viên, bỏ qua quy trình tự động</p>
    </div>
    <a href="{{ route('backend.certs-admin.issued') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Danh sách đã cấp
    </a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
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

<form method="POST" action="{{ route('backend.certs-admin.issue') }}"
      novalidate data-cert-issue-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Main card ────────────────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-6 space-y-5">

                {{-- Employee select --}}
                <div class="form-control">
                    <label class="label py-0 pb-1.5" for="ts-workforce_profile_id">
                        <span class="label-text font-medium">Nhân viên <span class="text-error">*</span></span>
                    </label>
                    <select id="ts-workforce_profile_id" name="workforce_profile_id"
                            class="select select-bordered select-sm w-full ts-init @error('workforce_profile_id') select-error @enderror"
                            data-ts-placeholder="— Chọn nhân viên —"
                            data-req="Vui lòng chọn nhân viên">
                        <option value="">— Chọn nhân viên —</option>
                        @foreach($profiles as $profile)
                        <option value="{{ $profile->id }}" {{ old('workforce_profile_id') == $profile->id ? 'selected' : '' }}>
                            {{ $profile->employee?->full_name ?? 'Profile #'.$profile->id }}
                            @if($profile->employee?->employee_code)
                                ({{ $profile->employee->employee_code }})
                            @endif
                        </option>
                        @endforeach
                    </select>
                    @error('workforce_profile_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    @if($profiles->isEmpty())
                    <p class="mt-1 text-xs text-warning">Chưa có profile nhân viên nào trong tổ chức.</p>
                    @endif
                </div>

                {{-- Cert definition select --}}
                <div class="form-control">
                    <label class="label py-0 pb-1.5" for="ts-cert_definition_id">
                        <span class="label-text font-medium">Định nghĩa chứng nhận <span class="text-error">*</span></span>
                    </label>
                    <select id="ts-cert_definition_id" name="cert_definition_id"
                            class="select select-bordered select-sm w-full ts-init @error('cert_definition_id') select-error @enderror"
                            data-ts-placeholder="— Chọn loại chứng nhận —"
                            data-req="Vui lòng chọn định nghĩa chứng nhận">
                        <option value="">— Chọn loại chứng nhận —</option>
                        @php $lastType = null; @endphp
                        @foreach($definitions as $def)
                            @if($def->cert_type_code !== $lastType)
                                @if($lastType !== null)</optgroup>@endif
                                <optgroup label="{{ $def->cert_type_code }}">
                                @php $lastType = $def->cert_type_code; @endphp
                            @endif
                            <option value="{{ $def->id }}" {{ old('cert_definition_id') == $def->id ? 'selected' : '' }}>
                                {{ $def->name }} — {{ $def->level_code }}
                            </option>
                        @endforeach
                        @if($lastType !== null)</optgroup>@endif
                    </select>
                    @error('cert_definition_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    @if($definitions->isEmpty())
                    <p class="mt-1 text-xs text-warning">Chưa có định nghĩa chứng nhận nào được kích hoạt.</p>
                    @endif
                </div>

                {{-- Notes --}}
                <div class="form-control">
                    <label class="label py-0 pb-1.5" for="notes">
                        <span class="label-text font-medium">Ghi chú</span>
                        <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                    </label>
                    <textarea id="notes" name="notes" rows="3"
                              class="textarea textarea-bordered textarea-sm w-full @error('notes') textarea-error @enderror"
                              placeholder="Lý do cấp thủ công, hoàn cảnh đặc biệt...">{{ old('notes') }}</textarea>
                    @error('notes')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

            </div>
        </div>

        {{-- ── Sidebar ─────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            {{-- Info note --}}
            <div class="alert alert-info py-3 px-4 text-sm gap-2.5">
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p>Sau khi cấp, hệ thống sẽ tự động cập nhật profile nhân viên và kiểm tra thăng cấp nghề nghiệp.</p>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Hành động</p>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.certs-admin.issued') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                            Cấp chứng nhận
                        </button>
                    </div>

                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>
                </div>
            </div>
        </div>

    </div>
</form>

@endsection

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'Modules/Assessment/resources/assets/js/assessment.js',
    ], 'build/backend')
@endpush
