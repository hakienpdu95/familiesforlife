@extends('layouts.backend')
@section('title', 'Sửa bước lộ trình: ' . $step->title)

@push('styles')
    @vite(['Modules/Assessment/resources/assets/sass/assessment.scss'], 'build/backend')
@endpush

@section('content')
<div x-data="{
    tab: 'basic',
    tabFields: {
        basic:      ['title', 'from_level', 'to_level'],
        detail:     ['step_order', 'estimated_weeks'],
        conditions: ['required_cert_code', 'recommended_sandbox_env_code'],
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) {
        return this.tabFields[t].filter(f => this.errs.includes(f)).length;
    },
    init() {
        const order = ['basic', 'detail', 'conditions'];
        for (const t of order) {
            if (this.errCount(t) > 0) { this.tab = t; break; }
        }
    }
}">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa bước lộ trình</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            {{ $step->title }}
            @if($step->organization_id === null)
                <span class="badge badge-info badge-xs ml-1">Hệ thống — dùng chung</span>
            @else
                <span class="badge badge-ghost badge-xs ml-1">Riêng: {{ $stepOrgName }}</span>
            @endif
        </p>
    </div>
    <a href="{{ route('backend.career-pathway-admin.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
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

<form method="POST" action="{{ route('backend.career-pathway-admin.update', $step) }}" novalidate data-career-pathway-admin-form>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính: tab nav + panels ──────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">

            {{-- Tab navigation --}}
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

                    <button type="button" role="tab" :aria-selected="tab === 'detail'"
                            @click="tab = 'detail'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'detail'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Thứ tự & Thời gian
                        <span x-show="errCount('detail') > 0" x-text="errCount('detail')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'conditions'"
                            @click="tab = 'conditions'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'conditions'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Điều kiện thăng cấp
                        <span x-show="errCount('conditions') > 0" x-text="errCount('conditions')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                </nav>
            </div>

            <div class="p-6">

                {{-- ── Tab: Thông tin cơ bản ───────────────────────────── --}}
                <div x-show="tab === 'basic'" data-tab-label="Thông tin cơ bản" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tiêu đề bước <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="title" value="{{ old('title', $step->title) }}"
                                   data-req="Vui lòng nhập tiêu đề bước"
                                   class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                                   placeholder="VD: Thực hành và đạt chứng nhận Foundation">
                            @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Từ cấp độ <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-from_level" name="from_level"
                                    class="select select-bordered select-sm w-full ts-init @error('from_level') select-error @enderror"
                                    data-ts-placeholder="— Chọn cấp độ —"
                                    data-req="Vui lòng chọn cấp độ bắt đầu">
                                <option value="">— Chọn cấp độ —</option>
                                @foreach($levels as $lvl)
                                <option value="{{ $lvl }}" {{ old('from_level', $step->from_level) === $lvl ? 'selected' : '' }}>
                                    {{ Str::title(Str::lower(str_replace('_', ' ', $lvl))) }}
                                </option>
                                @endforeach
                            </select>
                            @error('from_level')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Lên cấp độ <span class="text-error">*</span></span>
                                <span class="label-text-alt text-xs text-base-content/40">Bằng nhau = bước duy trì</span>
                            </label>
                            <select id="ts-to_level" name="to_level"
                                    class="select select-bordered select-sm w-full ts-init @error('to_level') select-error @enderror"
                                    data-ts-placeholder="— Chọn cấp độ —"
                                    data-req="Vui lòng chọn cấp độ đích">
                                <option value="">— Chọn cấp độ —</option>
                                @foreach($levels as $lvl)
                                <option value="{{ $lvl }}" {{ old('to_level', $step->to_level) === $lvl ? 'selected' : '' }}>
                                    {{ Str::title(Str::lower(str_replace('_', ' ', $lvl))) }}
                                </option>
                                @endforeach
                            </select>
                            @error('to_level')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = 'detail'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Thứ tự & Thời gian
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- ── Tab: Thứ tự & Thời gian ─────────────────────────── --}}
                <div x-show="tab === 'detail'" data-tab-label="Thứ tự & Thời gian" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Thứ tự bước</span>
                                <span class="label-text-alt text-xs text-base-content/40">Mặc định: 0</span>
                            </label>
                            <input type="number" name="step_order" min="0"
                                   value="{{ old('step_order', $step->step_order) }}"
                                   class="input input-bordered input-sm w-full @error('step_order') input-error @enderror">
                            @error('step_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Thời gian ước tính</span>
                                <span class="label-text-alt text-xs text-base-content/40">Tuần (1–52)</span>
                            </label>
                            <input type="number" name="estimated_weeks" min="1" max="52"
                                   value="{{ old('estimated_weeks', $step->estimated_weeks) }}"
                                   class="input input-bordered input-sm w-full @error('estimated_weeks') input-error @enderror"
                                   placeholder="VD: 8">
                            @error('estimated_weeks')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mô tả</span>
                            <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                        </label>
                        <textarea name="description" rows="4"
                                  class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                                  placeholder="Mô tả những gì nhân viên cần làm ở bước này...">{{ old('description', $step->description) }}</textarea>
                        @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'basic'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Thông tin cơ bản
                        </button>
                        <button type="button" @click="tab = 'conditions'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Điều kiện thăng cấp
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- ── Tab: Điều kiện thăng cấp ────────────────────────── --}}
                <div x-show="tab === 'conditions'" data-tab-label="Điều kiện thăng cấp" class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Chứng nhận yêu cầu</span>
                            <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                        </label>
                        <select id="ts-required_cert_code" name="required_cert_code"
                                class="select select-bordered select-sm w-full ts-init @error('required_cert_code') select-error @enderror"
                                data-ts-placeholder="— Không yêu cầu —">
                            <option value="">— Không yêu cầu —</option>
                            @foreach($certCodes as $code => $name)
                            <option value="{{ $code }}" {{ old('required_cert_code', $step->required_cert_code) === $code ? 'selected' : '' }}>
                                {{ $code }} — {{ $name }}
                            </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-base-content/40">Nhân viên phải có cert đang active với mã này.</p>
                        @error('required_cert_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Môi trường Sandbox gợi ý</span>
                            <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                        </label>
                        <select id="ts-recommended_sandbox_env_code" name="recommended_sandbox_env_code"
                                class="select select-bordered select-sm w-full ts-init @error('recommended_sandbox_env_code') select-error @enderror"
                                data-ts-placeholder="— Không chỉ định —">
                            <option value="">— Không chỉ định —</option>
                            @foreach($envCodes as $code => $name)
                            <option value="{{ $code }}" {{ old('recommended_sandbox_env_code', $step->recommended_sandbox_env_code) === $code ? 'selected' : '' }}>
                                {{ $code }} — {{ $name }}
                            </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-base-content/40">Nhân viên phải có ít nhất 1 phiên đạt điểm Pass trong môi trường này.</p>
                        @error('recommended_sandbox_env_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tag nội dung học (KC)</span>
                            <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                        </label>
                        <input type="text" name="recommended_kc_tag"
                               value="{{ old('recommended_kc_tag', $step->recommended_kc_tag) }}"
                               class="input input-bordered input-sm w-full font-mono @error('recommended_kc_tag') input-error @enderror"
                               placeholder="VD: ai-foundation,digital-skills">
                        <p class="mt-1 text-xs text-base-content/40">Phân cách bằng dấu phẩy.</p>
                        @error('recommended_kc_tag')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'detail'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Thứ tự & Thời gian
                        </button>
                        <span class="text-xs text-base-content/40">Nhấn <strong>Lưu lại</strong> ở bên phải khi xong</span>
                    </div>

                </div>

            </div>
        </div>

        {{-- ── Sidebar sticky: Xuất bản ───────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                        Xuất bản
                    </p>

                    <div class="form-control mb-3">
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="checkbox" name="is_active" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('is_active', $step->is_active ? '1' : '') == '1' ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Kích hoạt</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Hiện bước này cho nhân viên</p>
                            </div>
                        </label>
                    </div>

                    <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
                        <span>Tạo {{ $step->created_at->format('d/m/Y') }}</span>
                        <span>Sửa {{ $step->updated_at->diffForHumans() }}</span>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.career-pathway-admin.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Lưu lại
                        </button>
                    </div>

                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>

                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Thông tin</p>
                    <div class="space-y-1.5 text-xs text-base-content/60">
                        <div class="flex justify-between">
                            <span>Phạm vi</span>
                            <span class="font-medium text-base-content">
                                {{ $step->organization_id === null ? 'Hệ thống' : $stepOrgName }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span>Thứ tự</span>
                            <span class="font-mono font-medium text-base-content">{{ $step->step_order }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Lộ trình</span>
                            <span class="text-base-content">
                                {{ Str::upper(str_replace('_', ' ', $step->from_level)) }}
                                →
                                {{ Str::upper(str_replace('_', ' ', $step->to_level)) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>
</div>
@endsection

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'Modules/Assessment/resources/assets/js/assessment.js',
    ], 'build/backend')
@endpush
