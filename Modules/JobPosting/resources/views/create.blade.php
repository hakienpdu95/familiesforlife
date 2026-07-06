@extends('layouts.backend')
@section('title', 'Tạo tin tuyển dụng')


@push('styles')
    @vite(['Modules/JobPosting/resources/assets/sass/job-posting.scss'], 'build/backend')
@endpush

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Tạo tin tuyển dụng</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Soạn thảo và lưu nháp tin tuyển dụng mới</p>
    </div>
    <a href="{{ route('backend.job-posts.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.job-posts.store') }}" novalidate data-job-post-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Main content ────────────────────────────────────────────────── --}}
        <div class="space-y-5">

            {{-- Card: Nội dung tin tuyển dụng --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Nội dung tin tuyển dụng
                    </h2>

                    <div class="space-y-4">

                        {{-- Tổ chức --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tổ chức <span class="text-error">*</span></span>
                            </label>
                            @if($orgLocked)
                                <input type="hidden" name="organization_id" value="{{ $organizations->first()->id }}">
                                <input type="text" value="{{ $organizations->first()->name }}" readonly
                                       class="input input-bordered input-sm w-full bg-base-200 cursor-not-allowed">
                                <p class="mt-1 text-xs text-base-content/40">Xác định từ tài khoản của bạn.</p>
                            @else
                                <select id="ts-organization" name="organization_id"
                                        class="select select-bordered select-sm w-full ts-init @error('organization_id') select-error @enderror"
                                        data-ts-placeholder="— Chọn tổ chức —"
                                        data-req="Vui lòng chọn tổ chức">
                                    <option value="">— Chọn tổ chức —</option>
                                    @foreach($organizations as $org)
                                    <option value="{{ $org->id }}" {{ old('organization_id', $defaultOrgId ?? '') == $org->id ? 'selected' : '' }}>
                                        {{ $org->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            @endif
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tên vị trí <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="title" value="{{ old('title') }}"
                                   data-req="Vui lòng nhập tên vị trí"
                                   class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                                   placeholder="VD: Senior Backend Engineer (PHP / Laravel)" autofocus/>
                            @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mô tả ngắn</span>
                                <span class="label-text-alt text-xs text-base-content/40">Tối đa 500 ký tự, hiển thị trên listing</span>
                            </label>
                            <textarea name="summary" rows="2"
                                      class="textarea textarea-bordered textarea-sm w-full @error('summary') textarea-error @enderror"
                                      placeholder="Tóm tắt ngắn hiển thị trên listing card...">{{ old('summary') }}</textarea>
                            @error('summary')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mô tả công việc <span class="text-error">*</span></span>
                            </label>
                            <textarea name="description"
                                      class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                                      data-jodit-preset="full"
                                      data-req="Vui lòng nhập mô tả công việc">{{ old('description') }}</textarea>
                            @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Trách nhiệm / Nhiệm vụ</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <textarea name="responsibilities"
                                      class="jodit-editor textarea textarea-bordered textarea-sm w-full"
                                      data-jodit-preset="standard">{{ old('responsibilities') }}</textarea>
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Yêu cầu ứng viên <span class="text-error">*</span></span>
                            </label>
                            <textarea name="requirements"
                                      class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('requirements') textarea-error @enderror"
                                      data-jodit-preset="standard"
                                      data-req="Vui lòng nhập yêu cầu ứng viên">{{ old('requirements') }}</textarea>
                            @error('requirements')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Yêu cầu phụ (nice-to-have)</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <textarea name="nice_to_have"
                                      class="jodit-editor textarea textarea-bordered textarea-sm w-full"
                                      data-jodit-preset="compact">{{ old('nice_to_have') }}</textarea>
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Bạn sẽ học được gì</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <textarea name="what_you_will_learn"
                                      class="jodit-editor textarea textarea-bordered textarea-sm w-full"
                                      data-jodit-preset="compact">{{ old('what_you_will_learn') }}</textarea>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Card: Lương & Phúc lợi --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Lương & Phúc lợi
                    </h2>

                    <div class="space-y-4">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Loại lương</span>
                                </label>
                                <select id="ts-salary-type" name="salary_type"
                                        class="select select-bordered select-sm w-full ts-init"
                                        data-ts-placeholder="— Chọn loại lương —">
                                    @foreach($salaryTypes as $type)
                                    <option value="{{ $type['value'] }}" {{ old('salary_type', 'monthly') === $type['value'] ? 'selected' : '' }}>{{ $type['text'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Đơn vị tiền tệ</span>
                                </label>
                                <input type="text" name="salary_currency" value="{{ old('salary_currency', 'VND') }}"
                                       class="input input-bordered input-sm w-full font-mono" maxlength="3" placeholder="VND"/>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Lương tối thiểu</span>
                                </label>
                                <input type="number" name="salary_min" value="{{ old('salary_min') }}"
                                       class="input input-bordered input-sm w-full" placeholder="0" step="0.01" min="0"/>
                            </div>
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Lương tối đa</span>
                                </label>
                                <input type="number" name="salary_max" value="{{ old('salary_max') }}"
                                       class="input input-bordered input-sm w-full" placeholder="0" step="0.01" min="0"/>
                            </div>
                        </div>

                        <div class="flex gap-5 flex-wrap">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="salary_is_negotiable" value="1"
                                       class="checkbox checkbox-sm checkbox-primary"
                                       {{ old('salary_is_negotiable') ? 'checked' : '' }}/>
                                <span class="text-sm font-medium">Thỏa thuận</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="salary_is_visible" value="1"
                                       class="checkbox checkbox-sm checkbox-primary"
                                       {{ old('salary_is_visible', true) ? 'checked' : '' }}/>
                                <span class="text-sm font-medium">Hiển thị lương</span>
                            </label>
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ghi chú lương</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <input type="text" name="salary_note" value="{{ old('salary_note') }}"
                                   class="input input-bordered input-sm w-full" maxlength="300"
                                   placeholder="VD: 4–5.5 LPA fixed + 3 LPA variable"/>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Card: Học vấn & Kinh nghiệm --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                        </svg>
                        Học vấn & Kinh nghiệm
                    </h2>

                    <div class="space-y-4">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Kinh nghiệm tối thiểu (năm)</span>
                                </label>
                                <input type="number" name="min_experience_years" value="{{ old('min_experience_years') }}"
                                       class="input input-bordered input-sm w-full" min="0" placeholder="0"/>
                            </div>
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Kinh nghiệm tối đa (năm)</span>
                                    <span class="label-text-alt text-xs text-base-content/40">Để trống = không giới hạn</span>
                                </label>
                                <input type="number" name="max_experience_years" value="{{ old('max_experience_years') }}"
                                       class="input input-bordered input-sm w-full" min="0" placeholder="Không giới hạn"/>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Trình độ học vấn</span>
                                </label>
                                <select id="ts-education-level" name="education_level"
                                        class="select select-bordered select-sm w-full ts-init"
                                        data-ts-placeholder="— Không yêu cầu —">
                                    <option value="">Không yêu cầu</option>
                                    <option value="high_school" {{ old('education_level') === 'high_school' ? 'selected' : '' }}>THPT</option>
                                    <option value="associate" {{ old('education_level') === 'associate' ? 'selected' : '' }}>Cao đẳng</option>
                                    <option value="bachelor" {{ old('education_level') === 'bachelor' ? 'selected' : '' }}>Đại học</option>
                                    <option value="master" {{ old('education_level') === 'master' ? 'selected' : '' }}>Thạc sĩ</option>
                                    <option value="phd" {{ old('education_level') === 'phd' ? 'selected' : '' }}>Tiến sĩ</option>
                                    <option value="any" {{ old('education_level') === 'any' ? 'selected' : '' }}>Không giới hạn</option>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Ngành học yêu cầu</span>
                                </label>
                                <input type="text" name="education_field" value="{{ old('education_field') }}"
                                       class="input input-bordered input-sm w-full" placeholder="VD: Computer Science, IT"/>
                            </div>
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Chứng chỉ bắt buộc</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <textarea name="certifications_required" rows="2"
                                      class="textarea textarea-bordered textarea-sm w-full">{{ old('certifications_required') }}</textarea>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Card: Cấu hình ứng tuyển --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Cấu hình ứng tuyển
                    </h2>

                    <div class="space-y-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Email nhận CV</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <input type="email" name="application_email" value="{{ old('application_email') }}"
                                   class="input input-bordered input-sm w-full @error('application_email') input-error @enderror"
                                   placeholder="hr@company.com"/>
                            @error('application_email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex flex-wrap gap-5">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="allow_direct_apply" value="1"
                                       class="checkbox checkbox-sm checkbox-primary"
                                       {{ old('allow_direct_apply', true) ? 'checked' : '' }}/>
                                <span class="text-sm font-medium">Cho phép apply trực tiếp</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="require_cover_letter" value="1"
                                       class="checkbox checkbox-sm checkbox-primary"
                                       {{ old('require_cover_letter') ? 'checked' : '' }}/>
                                <span class="text-sm font-medium">Bắt buộc cover letter</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="require_portfolio" value="1"
                                       class="checkbox checkbox-sm checkbox-primary"
                                       {{ old('require_portfolio') ? 'checked' : '' }}/>
                                <span class="text-sm font-medium">Bắt buộc portfolio</span>
                            </label>
                        </div>

                    </div>
                </div>
            </div>

        </div>{{-- /main content --}}

        {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            {{-- Publish --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Lưu tin</p>
                    <p class="text-xs text-base-content/50 mb-4">
                        Tin sẽ được lưu ở trạng thái <span class="badge badge-ghost badge-sm">Nháp</span>.
                        Bạn có thể xuất bản sau.
                    </p>
                    <div class="flex gap-2">
                        <a href="{{ route('backend.job-posts.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Lưu nháp
                        </button>
                    </div>
                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>
                </div>
            </div>

            {{-- Phân loại --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Phân loại</p>
                    <div class="space-y-3">

                        <div class="form-control">
                            <label class="label py-0 pb-1">
                                <span class="label-text text-xs font-medium">Phòng ban</span>
                            </label>
                            <select id="ts-department" name="department_id"
                                    class="select select-bordered select-sm w-full ts-init"
                                    data-ts-placeholder="— Chọn phòng ban —">
                                <option value="">— Chọn phòng ban —</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1">
                                <span class="label-text text-xs font-medium">Chức danh tham chiếu</span>
                            </label>
                            <select id="ts-job-title" name="job_title_id"
                                    class="select select-bordered select-sm w-full ts-init"
                                    data-ts-placeholder="— Chọn chức danh —">
                                <option value="">— Chọn chức danh —</option>
                                @foreach($jobTitles as $jt)
                                <option value="{{ $jt->id }}" {{ old('job_title_id') == $jt->id ? 'selected' : '' }}>{{ $jt->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1">
                                <span class="label-text text-xs font-medium">Loại hình <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-employment-type" name="employment_type"
                                    class="select select-bordered select-sm w-full ts-init @error('employment_type') select-error @enderror"
                                    data-ts-placeholder="— Chọn loại hình —"
                                    data-req="Vui lòng chọn loại hình">
                                @foreach($employmentTypes as $type)
                                <option value="{{ $type['value'] }}" {{ old('employment_type', 'full_time') === $type['value'] ? 'selected' : '' }}>{{ $type['text'] }}</option>
                                @endforeach
                            </select>
                            @error('employment_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1">
                                <span class="label-text text-xs font-medium">Hình thức <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-work-arrangement" name="work_arrangement"
                                    class="select select-bordered select-sm w-full ts-init @error('work_arrangement') select-error @enderror"
                                    data-ts-placeholder="— Chọn hình thức —"
                                    data-req="Vui lòng chọn hình thức làm việc">
                                @foreach($workArrangements as $wa)
                                <option value="{{ $wa['value'] }}" {{ old('work_arrangement', 'onsite') === $wa['value'] ? 'selected' : '' }}>{{ $wa['text'] }}</option>
                                @endforeach
                            </select>
                            @error('work_arrangement')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1">
                                <span class="label-text text-xs font-medium">Cấp độ <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-experience-level" name="experience_level"
                                    class="select select-bordered select-sm w-full ts-init @error('experience_level') select-error @enderror"
                                    data-ts-placeholder="— Chọn cấp độ —"
                                    data-req="Vui lòng chọn cấp độ kinh nghiệm">
                                @foreach($experienceLevels as $el)
                                <option value="{{ $el['value'] }}" {{ old('experience_level', 'junior') === $el['value'] ? 'selected' : '' }}>{{ $el['text'] }}</option>
                                @endforeach
                            </select>
                            @error('experience_level')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1">
                                <span class="label-text text-xs font-medium">Ngành nghề <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-industry" name="industry"
                                    class="select select-bordered select-sm w-full ts-init @error('industry') select-error @enderror"
                                    data-ts-placeholder="— Chọn ngành —"
                                    data-req="Vui lòng chọn ngành nghề">
                                @foreach($industries as $ind)
                                <option value="{{ $ind['value'] }}" {{ old('industry', 'other') === $ind['value'] ? 'selected' : '' }}>{{ $ind['text'] }}</option>
                                @endforeach
                            </select>
                            @error('industry')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1">
                                <span class="label-text text-xs font-medium">Hiển thị</span>
                            </label>
                            <select id="ts-visibility" name="visibility"
                                    class="select select-bordered select-sm w-full ts-init"
                                    data-ts-placeholder="— Chọn hiển thị —">
                                @foreach($visibilities as $vis)
                                <option value="{{ $vis['value'] }}" {{ old('visibility', 'public') === $vis['value'] ? 'selected' : '' }}>{{ $vis['text'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1">
                                <span class="label-text text-xs font-medium">Số lượng tuyển <span class="text-error">*</span></span>
                            </label>
                            <input type="number" name="headcount" value="{{ old('headcount', 1) }}"
                                   data-req="Vui lòng nhập số lượng cần tuyển"
                                   class="input input-bordered input-sm w-full @error('headcount') input-error @enderror" min="1"/>
                            @error('headcount')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- Địa điểm --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Địa điểm</p>
                    <div class="space-y-3">

                        <div class="form-control">
                            <label class="label py-0 pb-1">
                                <span class="label-text text-xs font-medium">Thành phố</span>
                            </label>
                            <input type="text" name="city" value="{{ old('city') }}"
                                   class="input input-bordered input-sm w-full" placeholder="VD: Hồ Chí Minh"/>
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1">
                                <span class="label-text text-xs font-medium">Tỉnh / Tỉnh thành</span>
                            </label>
                            <input type="text" name="province" value="{{ old('province') }}"
                                   class="input input-bordered input-sm w-full"/>
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1">
                                <span class="label-text text-xs font-medium">Quốc gia <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="country" value="{{ old('country', 'VN') }}"
                                   data-req="Vui lòng nhập mã quốc gia"
                                   class="input input-bordered input-sm w-full font-mono @error('country') input-error @enderror"
                                   maxlength="2" placeholder="VN"/>
                            @error('country')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="is_remote_allowed" value="1"
                                   class="checkbox checkbox-sm checkbox-primary"
                                   {{ old('is_remote_allowed') ? 'checked' : '' }}/>
                            <span class="text-sm font-medium">Cho phép remote</span>
                        </label>

                    </div>
                </div>
            </div>

            {{-- Thời hạn --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Thời hạn</p>
                    <div class="form-control">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Hạn nộp hồ sơ</span>
                        </label>
                        <input type="datetime-local" name="expire_at" value="{{ old('expire_at') }}"
                               class="input input-bordered input-sm w-full"/>
                    </div>
                </div>
            </div>

            {{-- Phân phối --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Phân phối kênh</p>
                    <div class="space-y-2.5">
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="checkbox" name="publish_to_career_page" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('publish_to_career_page', true) ? 'checked' : '' }}/>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Career page</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Trang tuyển dụng tổ chức</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="checkbox" name="publish_to_marketplace" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('publish_to_marketplace') ? 'checked' : '' }}/>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Marketplace</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Cổng công khai</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

        </div>{{-- /sidebar --}}

    </div>{{-- /grid --}}
</form>

@endsection

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/jodit.js',
        'Modules/JobPosting/resources/assets/js/job-posting.js',
    ], 'build/backend')
@endpush
