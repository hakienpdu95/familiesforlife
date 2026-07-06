@extends('layouts.backend')
@section('title', 'Chỉnh sửa cấu hình — ' . $orgChartConfig->name)


@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Chỉnh sửa cấu hình sơ đồ</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $orgChartConfig->name }}</p>
    </div>
    <a href="{{ route('backend.org-charts.show', $orgChartConfig) }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

{{-- Error banner --}}
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

<form method="POST" action="{{ route('backend.org-charts.update', $orgChartConfig) }}" novalidate data-org-chart-form>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Main content ────────────────────────────────────────────────── --}}
        <div class="space-y-5">

            {{-- Thông tin cơ bản --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Thông tin cơ bản
                    </h2>

                    <div class="space-y-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tên cấu hình <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $orgChartConfig->name) }}"
                                   data-req="Vui lòng nhập tên cấu hình"
                                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                                   placeholder="VD: Sơ đồ toàn công ty, View theo phòng ban...">
                            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Kiểu view <span class="text-error">*</span></span>
                                </label>
                                <select id="ts-view-type" name="view_type"
                                        class="select select-bordered select-sm w-full ts-init @error('view_type') select-error @enderror"
                                        data-ts-placeholder="— Chọn kiểu view —">
                                    @foreach($viewTypes as $vt)
                                    <option value="{{ $vt['value'] }}"
                                            {{ old('view_type', $orgChartConfig->view_type->value) === $vt['value'] ? 'selected' : '' }}>
                                        {{ $vt['label'] }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('view_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Nhóm theo <span class="text-error">*</span></span>
                                </label>
                                <select id="ts-group-by" name="group_by"
                                        class="select select-bordered select-sm w-full ts-init @error('group_by') select-error @enderror"
                                        data-ts-placeholder="— Chọn nhóm —">
                                    @foreach($groupBys as $gb)
                                    <option value="{{ $gb['value'] }}"
                                            {{ old('group_by', $orgChartConfig->group_by->value) === $gb['value'] ? 'selected' : '' }}>
                                        {{ $gb['label'] }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('group_by')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Phạm vi chi nhánh</span>
                                    <span class="label-text-alt text-xs text-base-content/40">Để trống = toàn tổ chức</span>
                                </label>
                                <select id="ts-scope-branch" name="scope_branch_id"
                                        class="select select-bordered select-sm w-full ts-init @error('scope_branch_id') select-error @enderror"
                                        data-ts-placeholder="— Toàn tổ chức —">
                                    <option value="">— Toàn tổ chức —</option>
                                    @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}"
                                            {{ old('scope_branch_id', $orgChartConfig->scope_branch_id) == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }} ({{ $branch->code }})
                                    </option>
                                    @endforeach
                                </select>
                                @error('scope_branch_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Số cấp tối đa</span>
                                    <span class="label-text-alt text-xs text-base-content/40">1–10</span>
                                </label>
                                <input type="number" name="max_depth"
                                       value="{{ old('max_depth', $orgChartConfig->max_depth) }}"
                                       min="1" max="10"
                                       class="input input-bordered input-sm w-full @error('max_depth') input-error @enderror"
                                       placeholder="5">
                                @error('max_depth')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                        </div>

                    </div>
                </div>
            </div>

            {{-- Tùy chọn hiển thị --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Tùy chọn hiển thị
                    </h2>

                    <p class="text-xs text-base-content/50 -mt-2 mb-4">Chọn thông tin hiển thị trên mỗi card nhân viên trong sơ đồ</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="checkbox" name="show_avatar" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('show_avatar', $orgChartConfig->show_avatar ? '1' : '') == '1' ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Ảnh đại diện</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Hiển thị avatar nhân viên</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="checkbox" name="show_job_title" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('show_job_title', $orgChartConfig->show_job_title ? '1' : '') == '1' ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Chức danh</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Hiển thị chức danh công việc</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="checkbox" name="show_department" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('show_department', $orgChartConfig->show_department ? '1' : '') == '1' ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Phòng ban</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Hiển thị tên phòng ban</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="checkbox" name="show_employee_code" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('show_employee_code', $orgChartConfig->show_employee_code ? '1' : '') == '1' ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Mã nhân viên</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Hiển thị mã định danh</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="checkbox" name="show_branch" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('show_branch', $orgChartConfig->show_branch ? '1' : '') == '1' ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Chi nhánh</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Hiển thị chi nhánh làm việc</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="checkbox" name="expand_by_default" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('expand_by_default', $orgChartConfig->expand_by_default ? '1' : '') == '1' ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Mở rộng khi load</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Tự động mở toàn bộ nhánh khi vào trang</p>
                            </div>
                        </label>

                    </div>

                    <input type="hidden" name="_checkbox_fallback" value="1">

                </div>
            </div>

        </div>{{-- /main content --}}

        {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                        Thiết lập
                    </p>

                    <label class="flex items-start gap-2.5 cursor-pointer select-none group mb-3 pb-3 border-b border-base-200">
                        <input type="checkbox" name="is_default" value="1"
                               class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                               {{ old('is_default', $orgChartConfig->is_default ? '1' : '') == '1' ? 'checked' : '' }}>
                        <div>
                            <span class="text-sm font-medium group-hover:text-primary transition-colors">Đặt làm mặc định</span>
                            <p class="text-xs text-base-content/50 mt-0.5">View xuất hiện đầu tiên khi mở trang sơ đồ</p>
                        </div>
                    </label>

                    <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
                        <span>Tạo {{ $orgChartConfig->created_at->format('d/m/Y') }}</span>
                        <span>Sửa {{ $orgChartConfig->updated_at->diffForHumans() }}</span>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.org-charts.show', $orgChartConfig) }}"
                           class="btn btn-ghost btn-sm flex-1">Hủy</a>
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

            {{-- Info note --}}
            <div class="card bg-base-200/50 border border-base-200">
                <div class="card-body py-3 px-4">
                    <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide mb-1">Lưu ý</p>
                    <p class="text-xs text-base-content/50">
                        Sơ đồ render động từ <code class="font-mono bg-base-200 px-1 rounded">employees.manager_id</code>
                        — không lưu nodes/edges riêng. Dữ liệu luôn phản ánh cấu trúc nhân sự hiện tại.
                    </p>
                </div>
            </div>

        </div>{{-- /sidebar --}}

    </div>{{-- /grid --}}
</form>

@endsection

@push('styles')
    @vite(['Modules/OrgChart/resources/assets/sass/org-chart.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'Modules/OrgChart/resources/assets/js/org-chart.js',
    ], 'build/backend')
@endpush
