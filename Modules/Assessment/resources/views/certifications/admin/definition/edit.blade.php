@extends('layouts.backend')
@section('title', 'Sửa định nghĩa: ' . $def->name)

@push('styles')
    @vite(['Modules/Assessment/resources/assets/sass/assessment.scss'], 'build/backend')
@endpush

@section('content')

<div x-data="{
    tab: 'basic',
    tabFields: {
        basic:        ['name', 'validity_months'],
        requirements: [],
    },
    errs: {{ \Illuminate\Support\Js::from($errors->keys()) }},
    errCount(t) { return this.tabFields[t].filter(f => this.errs.includes(f)).length; },
    init() {
        const order = Object.keys(this.tabFields);
        for (const t of order) { if (this.errCount(t) > 0) { this.tab = t; break; } }
    },
}">

{{-- Page header --}}
<div class="flex items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa định nghĩa chứng nhận</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            @if($defOrgName)
                <span class="font-medium text-base-content/70">{{ $defOrgName }}</span>
                <span class="mx-1.5 opacity-30">·</span>
            @endif
            <span class="font-mono text-xs">{{ $def->cert_code }}</span>
        </p>
    </div>
    <a href="{{ route('backend.certs-admin.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Danh sách
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

<form method="POST" action="{{ route('backend.certs-admin.def.update', $def) }}"
      novalidate data-cert-def-form>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Main card (tab nav + panels) ────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">

            {{-- Tab nav --}}
            <div class="border-b border-base-200 px-6">
                <nav class="flex -mb-px" role="tablist" aria-label="Form sections">

                    <button type="button" role="tab" :aria-selected="tab === 'basic'"
                            @click="tab = 'basic'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'basic'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Thông tin cơ bản
                        <span x-show="errCount('basic') > 0" x-text="errCount('basic')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'requirements'"
                            @click="tab = 'requirements'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'requirements'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Điều kiện cấp
                        <span x-show="errCount('requirements') > 0" x-text="errCount('requirements')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                </nav>
            </div>

            {{-- Tab panels --}}
            <div class="p-6">

                {{-- Panel: Thông tin cơ bản --}}
                <div x-show="tab === 'basic'" data-tab-label="Thông tin cơ bản" class="space-y-4">

                    {{-- Readonly identity block --}}
                    <div class="bg-base-200/60 border border-base-200 rounded-lg p-4 space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-base-content/40 mb-2">Thông tin cố định (không thể thay đổi)</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <p class="text-xs text-base-content/40">Cert code</p>
                                <p class="text-sm font-mono font-semibold">{{ $def->cert_code }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-base-content/40">Type code</p>
                                <p class="text-sm font-mono">{{ $def->cert_type_code }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-base-content/40">Cấp độ</p>
                                <p class="text-sm font-semibold">{{ $def->level_code }}</p>
                            </div>
                        </div>
                        @if($defOrgName)
                        <div class="pt-1 border-t border-base-200">
                            <p class="text-xs text-base-content/40">Tổ chức</p>
                            <p class="text-sm">{{ $defOrgName }}</p>
                        </div>
                        @else
                        <div class="pt-1 border-t border-base-200">
                            <p class="text-xs text-base-content/40">Phạm vi</p>
                            <p class="text-sm font-medium text-info">Toàn hệ thống</p>
                        </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Tên --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5" for="name">
                                <span class="label-text font-medium">Tên chứng nhận <span class="text-error">*</span></span>
                            </label>
                            <input type="text" id="name" name="name"
                                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                                   value="{{ old('name', $def->name) }}"
                                   placeholder="VD: AI Administrative Officer — Foundation"
                                   data-req="Vui lòng nhập tên chứng nhận">
                            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Validity months --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5" for="validity_months">
                                <span class="label-text font-medium">Hiệu lực (tháng) <span class="text-error">*</span></span>
                            </label>
                            <input type="number" id="validity_months" name="validity_months" min="1" max="120"
                                   class="input input-bordered input-sm w-full @error('validity_months') input-error @enderror"
                                   value="{{ old('validity_months', $def->validity_months) }}"
                                   data-req="Vui lòng nhập thời hạn hiệu lực">
                            @error('validity_months')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Description --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5" for="description">
                                <span class="label-text font-medium">Mô tả</span>
                            </label>
                            <textarea id="description" name="description" rows="2"
                                      class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                                      placeholder="Mô tả chứng nhận này...">{{ old('description', $def->description) }}</textarea>
                            @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    {{-- Tab footer nav --}}
                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = 'requirements'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Điều kiện cấp
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Panel: Điều kiện cấp --}}
                <div x-show="tab === 'requirements'" data-tab-label="Điều kiện cấp" class="space-y-4">

                    <p class="text-sm text-base-content/50">Để trống nếu không áp dụng điều kiện đó.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5" for="min_workforce_score">
                                <span class="label-text font-medium">TDWCF score tối thiểu</span>
                                <span class="label-text-alt text-xs text-base-content/40">0–100</span>
                            </label>
                            <input type="number" id="min_workforce_score" name="min_workforce_score" min="0" max="100" step="0.01"
                                   class="input input-bordered input-sm w-full"
                                   value="{{ old('min_workforce_score', $def->min_workforce_score) }}"
                                   placeholder="VD: 61">
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5" for="min_kpi_achievement_pct">
                                <span class="label-text font-medium">KPI tối thiểu (%)</span>
                                <span class="label-text-alt text-xs text-base-content/40">0–100</span>
                            </label>
                            <input type="number" id="min_kpi_achievement_pct" name="min_kpi_achievement_pct" min="0" max="100" step="0.01"
                                   class="input input-bordered input-sm w-full"
                                   value="{{ old('min_kpi_achievement_pct', $def->min_kpi_achievement_pct) }}"
                                   placeholder="VD: 70">
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5" for="min_sandbox_hours">
                                <span class="label-text font-medium">Sandbox tối thiểu (giờ)</span>
                            </label>
                            <input type="number" id="min_sandbox_hours" name="min_sandbox_hours" min="0"
                                   class="input input-bordered input-sm w-full"
                                   value="{{ old('min_sandbox_hours', $def->min_sandbox_hours) }}"
                                   placeholder="VD: 20">
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5" for="min_sandbox_score">
                                <span class="label-text font-medium">Điểm sandbox tối thiểu</span>
                                <span class="label-text-alt text-xs text-base-content/40">0–100</span>
                            </label>
                            <input type="number" id="min_sandbox_score" name="min_sandbox_score" min="0" max="100" step="0.01"
                                   class="input input-bordered input-sm w-full"
                                   value="{{ old('min_sandbox_score', $def->min_sandbox_score) }}"
                                   placeholder="VD: 70">
                        </div>

                    </div>

                    <div class="space-y-3 pt-2">
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="checkbox" name="requires_impact_score" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('requires_impact_score', $def->requires_impact_score ? '1' : '') == '1' ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Yêu cầu AI Impact Score</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Nhân viên phải có ít nhất 1 bản ghi tác động AI</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="checkbox" name="requires_portfolio_approval" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('requires_portfolio_approval', $def->requires_portfolio_approval ? '1' : '') == '1' ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Yêu cầu Portfolio được duyệt</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Cần có portfolio được người quản lý phê duyệt</p>
                            </div>
                        </label>
                    </div>

                    {{-- Tab footer nav --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'basic'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Thông tin cơ bản
                        </button>
                        <span class="text-xs text-base-content/40">Nhấn <strong>Lưu thay đổi</strong> ở bên phải khi xong</span>
                    </div>
                </div>

            </div>
        </div>{{-- end main card --}}

        {{-- ── Sidebar ─────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    <div class="form-control mb-4">
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="checkbox" name="is_active" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('is_active', $def->is_active ? '1' : '') == '1' ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Kích hoạt</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Nhân viên có thể nhận chứng nhận này</p>
                            </div>
                        </label>
                    </div>

                    <div class="border-t border-base-200 pt-3 mb-3 space-y-1 text-xs text-base-content/40">
                        <p>Tạo: {{ $def->created_at?->format('d/m/Y H:i') }}</p>
                        <p>Cập nhật: {{ $def->updated_at?->format('d/m/Y H:i') }}</p>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.certs-admin.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Lưu thay đổi
                        </button>
                    </div>

                </div>
            </div>
        </div>{{-- end sidebar --}}

    </div>
</form>

</div>{{-- end x-data --}}
@endsection

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'Modules/Assessment/resources/assets/js/assessment.js',
    ], 'build/backend')
@endpush
