@extends('layouts.backend')
@section('title', 'Tạo tài liệu mới')


@section('content')
<div class="p-6">

    <div class="mb-5">
        <h1 class="text-xl font-bold">Tạo tài liệu mới</h1>
        <p class="text-sm opacity-60 mt-0.5">Tài liệu sẽ được lưu ở trạng thái Bản nháp</p>
    </div>

    @if($errors->any())
    <div class="alert alert-error mb-5">
        <ul class="list-disc pl-4 text-sm space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('backend.kc-items.store') }}" novalidate
          data-kc-item-form
          x-data="kcItemForm()">
        @csrf

        <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

            {{-- ── Nội dung chính ──────────────────────────────────────────── --}}
            <div class="space-y-5">

                {{-- Tiêu đề & Slug --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5 space-y-4">

                        <div class="form-control">
                            <label class="label" for="title">
                                <span class="label-text font-medium">Tiêu đề <span class="text-error">*</span></span>
                            </label>
                            <input id="title" type="text" name="title" value="{{ old('title') }}"
                                   @input="onTitleInput($event.target.value)"
                                   data-req="Tiêu đề"
                                   class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                                   placeholder="VD: Quy trình onboarding nhân viên mới...">
                            @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label" for="slug">
                                <span class="label-text font-medium">Slug <span class="text-error">*</span></span>
                                <span class="label-text-alt text-xs opacity-40">Tự động tạo từ tiêu đề</span>
                            </label>
                            <input id="slug" type="text" name="slug" x-model="slug"
                                   class="input input-bordered input-sm w-full font-mono @error('slug') input-error @enderror"
                                   placeholder="quy-trinh-onboarding-nhan-vien-moi">
                            @error('slug')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label" for="summary">
                                <span class="label-text font-medium">Tóm tắt</span>
                                <span class="label-text-alt text-xs opacity-40">Hiển thị trong danh sách</span>
                            </label>
                            <textarea id="summary" name="summary"
                                      class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('summary') textarea-error @enderror"
                                      data-jodit-preset="compact"
                                      rows="2"
                                      placeholder="Mô tả ngắn gọn về nội dung tài liệu...">{{ old('summary') }}</textarea>
                            @error('summary')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>
                </div>

                {{-- Nội dung --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5 space-y-3">
                        <p class="text-sm font-semibold">Nội dung</p>
                        <div class="form-control">
                            <textarea id="content" name="content"
                                      class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('content') textarea-error @enderror"
                                      data-jodit-preset="standard"
                                      rows="16"
                                      placeholder="Nhập nội dung tài liệu...">{{ old('content') }}</textarea>
                            @error('content')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

            </div>

            {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
            <aside class="xl:sticky xl:top-4 space-y-4">

                {{-- Xuất bản --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4 space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full bg-warning inline-block"></span>
                            <span class="text-sm font-medium">Lưu nháp</span>
                        </div>
                        <p class="text-xs opacity-50">Tài liệu sẽ ở trạng thái Bản nháp cho đến khi được duyệt.</p>
                        <div class="flex flex-col gap-2 pt-1 border-t border-base-200">
                            <button type="submit" class="btn btn-primary btn-sm w-full">Lưu nháp</button>
                            <a href="{{ route('backend.kc-items.index') }}"
                               class="btn btn-ghost btn-sm w-full">Hủy</a>
                        </div>
                        <p class="text-center text-xs opacity-30">
                            <span class="text-error">*</span> là trường bắt buộc
                        </p>
                    </div>
                </div>

                {{-- Phân loại --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4 space-y-3">
                        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Phân loại</p>

                        {{-- Tổ chức --}}
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

                        <div class="form-control">
                            <label class="label py-0 pb-1" for="ts-type">
                                <span class="label-text text-sm font-medium">Loại tài liệu <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-type" name="type"
                                    class="select select-bordered select-sm w-full">
                                <option value="">— Chọn loại —</option>
                                @foreach($types as $t)
                                <option value="{{ $t['value'] }}" {{ old('type') === $t['value'] ? 'selected' : '' }}>
                                    {{ $t['text'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1" for="ts-category">
                                <span class="label-text text-sm font-medium">Danh mục <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-category" name="category_id"
                                    class="select select-bordered select-sm w-full"
                                    @if(!$orgLocked)
                                        data-org-api="{{ route('api.kc-category.options') }}"
                                        data-selected-value="{{ old('category_id') }}"
                                    @endif>
                                <option value="">— Chọn danh mục —</option>
                                @if($orgLocked)
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat['value'] }}" {{ old('category_id') == $cat['value'] ? 'selected' : '' }}>
                                        {{ $cat['text'] }}
                                    </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('category_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1" for="ts-visibility">
                                <span class="label-text text-sm font-medium">Phạm vi hiển thị</span>
                            </label>
                            <select id="ts-visibility" name="visibility"
                                    class="select select-bordered select-sm w-full">
                                @foreach($visibilities as $v)
                                <option value="{{ $v['value'] }}" {{ old('visibility', 'internal') === $v['value'] ? 'selected' : '' }}>
                                    {{ $v['text'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('visibility')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Domain TDWCF + Mức độ --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div class="form-control">
                                <label class="label py-0 pb-1">
                                    <span class="label-text text-sm font-medium">Domain TDWCF</span>
                                </label>
                                <select name="domain_code" class="select select-bordered select-sm w-full">
                                    <option value="">— Không chọn —</option>
                                    @foreach(['D1'=>'D1 — Số cơ bản','D2'=>'D2 — Dữ liệu','D3'=>'D3 — AI','D4'=>'D4 — Quy trình','D5'=>'D5 — Đổi mới','D6'=>'D6 — Hiệu suất'] as $code => $label)
                                    <option value="{{ $code }}" {{ old('domain_code') === $code ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('domain_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-control">
                                <label class="label py-0 pb-1">
                                    <span class="label-text text-sm font-medium">Mức độ</span>
                                </label>
                                <select name="difficulty" class="select select-bordered select-sm w-full">
                                    <option value="">— Không chọn —</option>
                                    <option value="1" {{ old('difficulty') == 1 ? 'selected' : '' }}>1 — Cơ bản</option>
                                    <option value="2" {{ old('difficulty') == 2 ? 'selected' : '' }}>2 — Trung cấp</option>
                                    <option value="3" {{ old('difficulty') == 3 ? 'selected' : '' }}>3 — Nâng cao</option>
                                </select>
                                @error('difficulty')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Tags --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4 space-y-2">
                        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Tags</p>
                        <div class="form-control">
                            <select id="ts-tags" name="tags[]" multiple
                                    data-api-url="{{ $tagsApiUrl }}"
                                    class="select select-bordered select-sm w-full">
                                @foreach(old('tags', []) as $tagId)
                                <option value="{{ $tagId }}" selected>{{ $tagId }}</option>
                                @endforeach
                            </select>
                            @error('tags')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            <p class="text-xs opacity-40 mt-1.5">Nhập tên tag và Enter để tạo mới</p>
                        </div>
                    </div>
                </div>

                {{-- Tùy chọn --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4 space-y-3">
                        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Tùy chọn</p>

                        <label class="flex items-start gap-2.5 cursor-pointer select-none">
                            <input type="hidden" name="is_featured" value="0">
                            <input type="checkbox" name="is_featured" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('is_featured') ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium">Nổi bật</span>
                                <p class="text-xs opacity-50">Hiển thị trên trang chủ KC</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 cursor-pointer select-none">
                            <input type="hidden" name="is_pinned" value="0">
                            <input type="checkbox" name="is_pinned" value="1"
                                   class="checkbox checkbox-sm checkbox-secondary mt-0.5 shrink-0"
                                   {{ old('is_pinned') ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium">Ghim đầu</span>
                                <p class="text-xs opacity-50">Ghim đầu danh sách trong danh mục</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Hiệu lực --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4 space-y-3">
                        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Hiệu lực</p>

                        <div class="form-control">
                            <label class="label py-0 pb-1" for="fp-effective-date">
                                <span class="label-text text-xs">Ngày có hiệu lực</span>
                            </label>
                            <input id="fp-effective-date" name="effective_date"
                                   value="{{ old('effective_date') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('effective_date') input-error @enderror"
                                   placeholder="dd/mm/yyyy" autocomplete="off">
                            @error('effective_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1" for="fp-expired-date">
                                <span class="label-text text-xs">Ngày hết hiệu lực</span>
                            </label>
                            <input id="fp-expired-date" name="expired_date"
                                   value="{{ old('expired_date') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('expired_date') input-error @enderror"
                                   placeholder="dd/mm/yyyy" autocomplete="off">
                            @error('expired_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

            </aside>

        </div>
    </form>

</div>

{{-- Note: Upload file đính kèm chỉ khả dụng sau khi tài liệu được lưu (trang edit). --}}
@endsection

@push('scripts')
@vite([
    'resources/js/modules/toastify.js',
    'resources/js/modules/flatpickr.js',
    'resources/js/modules/tom-select.js',
    'resources/js/modules/jodit.js',
    'Modules/KcItem/resources/assets/sass/kc-item.scss',
    'Modules/KcItem/resources/assets/js/kc-item.js',
], 'build/backend')
@endpush
