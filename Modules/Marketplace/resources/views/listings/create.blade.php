@extends('layouts.backend')
@section('title', 'Tạo tin đăng mới — Marketplace')


@section('content')
<div x-data="{
    tab: 'basic',
    listingType: '{{ old('listing_type', 'job') }}',
    tabFields: {
        basic:   ['title', 'description', 'listing_type', 'visibility', 'organization_id'],
        details: ['work_type', 'experience_level'],
        salary:  [],
        content: ['requirements', 'benefits'],
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) {
        return this.tabFields[t].filter(f => this.errs.includes(f)).length;
    },
    init() {
        const order = ['basic', 'details', 'salary', 'content'];
        for (const t of order) {
            if (this.errCount(t) > 0) { this.tab = t; break; }
        }
    }
}">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Tạo tin đăng mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Đăng tin tuyển dụng hoặc dự án lên Marketplace</p>
    </div>
    <a href="{{ route('backend.marketplace.listings.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.marketplace.listings.store') }}" novalidate data-listing-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính với tab ────────────────────────────────────────── --}}
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

                    <button type="button" role="tab" :aria-selected="tab === 'details'"
                            @click="tab = 'details'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'details'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Chi tiết công việc
                        <span x-show="errCount('details') > 0" x-text="errCount('details')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'salary'"
                            @click="tab = 'salary'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'salary'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Lương & Ngân sách
                        <span x-show="errCount('salary') > 0" x-text="errCount('salary')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'content'"
                            @click="tab = 'content'"
                            class="flex items-center gap-1.5 px-1 py-4 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'content'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Nội dung & Tags
                        <span x-show="errCount('content') > 0" x-text="errCount('content')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                </nav>
            </div>

            {{-- Tab panels --}}
            <div class="p-6">

                {{-- ── Panel: Thông tin cơ bản ─────────────────────────────── --}}
                <div x-show="tab === 'basic'" data-tab-label="Thông tin cơ bản" class="space-y-4">

                    <div class="form-control">
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

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Loại tin <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-listing-type" name="listing_type"
                                    class="select select-bordered select-sm w-full @error('listing_type') select-error @enderror"
                                    data-ts-placeholder="— Chọn loại tin —">
                                @foreach($listingTypes as $type)
                                <option value="{{ $type->value }}"
                                        {{ old('listing_type', 'job') === $type->value ? 'selected' : '' }}>
                                    {{ $type->label() }}
                                </option>
                                @endforeach
                            </select>
                            @error('listing_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Hiển thị <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-visibility" name="visibility"
                                    class="select select-bordered select-sm w-full ts-init @error('visibility') select-error @enderror"
                                    data-ts-placeholder="— Chọn chế độ hiển thị —">
                                @foreach($visibilities as $v)
                                <option value="{{ $v->value }}"
                                        {{ old('visibility', 'public') === $v->value ? 'selected' : '' }}>
                                    {{ $v->label() }}
                                </option>
                                @endforeach
                            </select>
                            @error('visibility')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Số lượng tuyển</span>
                                <span class="label-text-alt text-xs text-base-content/40">Mặc định: 1</span>
                            </label>
                            <input type="number" name="headcount" min="1" max="999"
                                   value="{{ old('headcount', 1) }}"
                                   class="input input-bordered input-sm w-full @error('headcount') input-error @enderror">
                            @error('headcount')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tiêu đề <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="title" maxlength="300"
                               value="{{ old('title') }}"
                               data-req="Vui lòng nhập tiêu đề tin đăng"
                               class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                               placeholder="VD: Senior Laravel Developer — Remote" autofocus>
                        @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mô tả công việc <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs text-base-content/40">Bắt buộc</span>
                        </label>
                        <textarea name="description"
                                  class="jodit-editor textarea textarea-bordered textarea-sm w-full"
                                  data-jodit-preset="standard"
                                  placeholder="Mô tả chi tiết về vị trí / dự án...">{{ old('description') }}</textarea>
                        @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = 'details'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Chi tiết công việc
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- ── Panel: Chi tiết công việc ───────────────────────────── --}}
                <div x-show="tab === 'details'" data-tab-label="Chi tiết công việc" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Hình thức làm việc <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-work-type" name="work_type"
                                    class="select select-bordered select-sm w-full ts-init @error('work_type') select-error @enderror"
                                    data-ts-placeholder="— Chọn hình thức —">
                                @foreach($workTypes as $wt)
                                <option value="{{ $wt->value }}"
                                        {{ old('work_type', 'flexible') === $wt->value ? 'selected' : '' }}>
                                    {{ $wt->label() }}
                                </option>
                                @endforeach
                            </select>
                            @error('work_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control" x-show="listingType === 'job'">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Loại hợp đồng</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <select id="ts-employment-type" name="employment_type"
                                    class="select select-bordered select-sm w-full ts-init @error('employment_type') select-error @enderror"
                                    data-ts-placeholder="— Không chọn —">
                                <option value="">— Không chọn —</option>
                                @foreach($employmentTypes as $et)
                                <option value="{{ $et->value }}"
                                        {{ old('employment_type') === $et->value ? 'selected' : '' }}>
                                    {{ $et->label() }}
                                </option>
                                @endforeach
                            </select>
                            @error('employment_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Cấp độ kinh nghiệm <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-experience-level" name="experience_level"
                                    class="select select-bordered select-sm w-full ts-init @error('experience_level') select-error @enderror"
                                    data-ts-placeholder="— Chọn cấp độ —">
                                @foreach($experienceLevels as $el)
                                <option value="{{ $el->value }}"
                                        {{ old('experience_level', 'any') === $el->value ? 'selected' : '' }}>
                                    {{ $el->label() }}
                                </option>
                                @endforeach
                            </select>
                            @error('experience_level')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Địa điểm</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <input type="text" name="location" maxlength="200"
                                   value="{{ old('location') }}"
                                   class="input input-bordered input-sm w-full @error('location') input-error @enderror"
                                   placeholder="VD: Hà Nội, TP.HCM, Remote...">
                            @error('location')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Hạn nộp hồ sơ</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <input type="text" name="expire_at" id="fp-expire-at"
                                   value="{{ old('expire_at') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('expire_at') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('expire_at')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'basic'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Thông tin cơ bản
                        </button>
                        <button type="button" @click="tab = 'salary'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Lương & Ngân sách
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- ── Panel: Lương & Ngân sách ─────────────────────────────── --}}
                <div x-show="tab === 'salary'" data-tab-label="Lương & Ngân sách" class="space-y-4">

                    {{-- Việc làm / Freelancer: salary --}}
                    <div x-show="listingType !== 'project'" class="space-y-4">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Lương tối thiểu</span>
                                </label>
                                <input type="number" name="salary_min" min="0" step="500000"
                                       value="{{ old('salary_min') }}"
                                       class="input input-bordered input-sm w-full"
                                       placeholder="VD: 10000000">
                            </div>

                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Lương tối đa</span>
                                </label>
                                <input type="number" name="salary_max" min="0" step="500000"
                                       value="{{ old('salary_max') }}"
                                       class="input input-bordered input-sm w-full"
                                       placeholder="VD: 20000000">
                            </div>

                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Đơn vị tiền tệ</span>
                                </label>
                                <input type="text" name="salary_currency" maxlength="3"
                                       value="{{ old('salary_currency', 'VND') }}"
                                       class="input input-bordered input-sm w-full font-mono"
                                       placeholder="VND">
                            </div>

                        </div>

                        <div class="space-y-3">
                            <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                                <input type="checkbox" name="salary_is_negotiable" value="1"
                                       class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                       {{ old('salary_is_negotiable') ? 'checked' : '' }}>
                                <div>
                                    <span class="text-sm font-medium group-hover:text-primary transition-colors">Thỏa thuận</span>
                                    <p class="text-xs text-base-content/50 mt-0.5">Mức lương được thỏa thuận trực tiếp với ứng viên</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                                <input type="checkbox" name="salary_is_visible" value="1"
                                       class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                       {{ old('salary_is_visible', '1') == '1' ? 'checked' : '' }}>
                                <div>
                                    <span class="text-sm font-medium group-hover:text-primary transition-colors">Hiển thị mức lương</span>
                                    <p class="text-xs text-base-content/50 mt-0.5">Ứng viên có thể xem mức lương này trên Marketplace</p>
                                </div>
                            </label>
                        </div>

                    </div>

                    {{-- Dự án: budget --}}
                    <div x-show="listingType === 'project'" class="space-y-4">

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Ngân sách tối thiểu</span>
                                </label>
                                <input type="number" name="budget_min" min="0"
                                       value="{{ old('budget_min') }}"
                                       class="input input-bordered input-sm w-full"
                                       placeholder="0">
                            </div>

                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Ngân sách tối đa</span>
                                </label>
                                <input type="number" name="budget_max" min="0"
                                       value="{{ old('budget_max') }}"
                                       class="input input-bordered input-sm w-full"
                                       placeholder="0">
                            </div>

                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Thời gian (ngày)</span>
                                </label>
                                <input type="number" name="duration_days" min="1"
                                       value="{{ old('duration_days') }}"
                                       class="input input-bordered input-sm w-full"
                                       placeholder="VD: 30">
                            </div>

                        </div>

                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'details'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Chi tiết công việc
                        </button>
                        <button type="button" @click="tab = 'content'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Nội dung & Tags
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- ── Panel: Nội dung & Tags ───────────────────────────────── --}}
                <div x-show="tab === 'content'" data-tab-label="Nội dung & Tags" class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Yêu cầu ứng viên</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <textarea name="requirements"
                                  class="jodit-editor textarea textarea-bordered textarea-sm w-full"
                                  data-jodit-preset="compact"
                                  placeholder="Kinh nghiệm, kỹ năng, bằng cấp yêu cầu...">{{ old('requirements') }}</textarea>
                        @error('requirements')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Quyền lợi</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <textarea name="benefits"
                                  class="jodit-editor textarea textarea-bordered textarea-sm w-full"
                                  data-jodit-preset="compact"
                                  placeholder="Lương thưởng, bảo hiểm, môi trường làm việc...">{{ old('benefits') }}</textarea>
                        @error('benefits')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    @if($tags->isNotEmpty())
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tags kỹ năng</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <select id="ts-tag-ids" name="tag_ids[]" multiple
                                class="select select-bordered select-sm w-full ts-init"
                                data-ts-placeholder="— Chọn tags kỹ năng —">
                            @foreach($tags as $tag)
                            <option value="{{ $tag->id }}"
                                    {{ in_array($tag->id, old('tag_ids', [])) ? 'selected' : '' }}>
                                {{ $tag->name }}
                            </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-base-content/40">Chọn các kỹ năng liên quan để ứng viên tìm thấy dễ hơn</p>
                    </div>
                    @endif

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'salary'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Lương & Ngân sách
                        </button>
                        <span class="text-xs text-base-content/40">Nhấn <strong>Đăng tin</strong> ở bên phải khi xong</span>
                    </div>

                </div>

            </div>{{-- /tab panels --}}
        </div>{{-- /card chính --}}

        {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    <div class="rounded-lg bg-success/10 border border-success/20 px-3 py-2.5 mb-4 flex items-center gap-2">
                        <span class="badge badge-success badge-sm">Hiển thị ngay</span>
                        <span class="text-xs text-base-content/50">sau khi tạo</span>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.marketplace.listings.index') }}"
                           class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Đăng tin
                        </button>
                    </div>

                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>

                </div>
            </div>

        </div>{{-- /sidebar --}}

    </div>{{-- /grid --}}

</form>
</div>
@endsection

@push('styles')
    @vite(['Modules/Marketplace/resources/assets/sass/marketplace.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/jodit.js',
        'Modules/Marketplace/resources/assets/js/marketplace.js',
    ], 'build/backend')
@endpush
