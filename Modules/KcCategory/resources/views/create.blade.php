@extends('layouts.backend')
@section('title', 'Thêm danh mục tài liệu')


@section('content')
<div class="p-6">

    <div class="mb-5">
        <h1 class="text-xl font-bold">Thêm danh mục mới</h1>
        <p class="text-sm opacity-60 mt-0.5">Tạo danh mục tổ chức tài liệu kho tri thức</p>
    </div>

    @if($errors->any())
    <div class="alert alert-error mb-5">
        <ul class="list-disc pl-4 text-sm space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('backend.kc-categories.store') }}" novalidate
          data-kc-category-form
          x-data="kcCategoryForm()">
        @csrf

        <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

            {{-- ── Card chính ──────────────────────────────────────────────── --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-5 space-y-4">

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

                    {{-- Tên danh mục --}}
                    <div class="form-control">
                        <label class="label" for="name">
                            <span class="label-text font-medium">Tên danh mục <span class="text-error">*</span></span>
                        </label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}"
                               data-req="Vui lòng nhập tên danh mục"
                               @input="onNameInput($event.target.value)"
                               class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                               placeholder="VD: Quy trình vận hành, Chính sách nhân sự...">
                        @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Slug --}}
                    <div class="form-control">
                        <label class="label" for="slug">
                            <span class="label-text font-medium">Slug <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs opacity-40">Tự động tạo từ tên</span>
                        </label>
                        <input id="slug" type="text" name="slug" x-model="slug"
                               class="input input-bordered input-sm w-full font-mono @error('slug') input-error @enderror"
                               placeholder="vd-quy-trinh-van-hanh">
                        <div class="label">
                            <span class="label-text-alt opacity-40">Chỉ dùng chữ thường, số và dấu <code class="bg-base-200 px-1 rounded">-</code></span>
                        </div>
                        @error('slug')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Danh mục cha --}}
                    <div class="form-control">
                        <label class="label" for="ts-parent">
                            <span class="label-text font-medium">Danh mục cha</span>
                            <span class="label-text-alt text-xs opacity-40">Để trống = danh mục gốc</span>
                        </label>
                        <select id="ts-parent" name="parent_id"
                                class="select select-bordered select-sm w-full ts-init"
                                data-ts-placeholder="— Danh mục gốc (cấp 1) —">
                            <option value="">— Danh mục gốc (cấp 1) —</option>
                            @foreach($parentOptions as $opt)
                            <option value="{{ $opt['value'] }}" {{ old('parent_id') == $opt['value'] ? 'selected' : '' }}>
                                {{ $opt['text'] }}
                            </option>
                            @endforeach
                        </select>
                        @error('parent_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Mô tả --}}
                    <div class="form-control">
                        <label class="label" for="description">
                            <span class="label-text font-medium">Mô tả</span>
                        </label>
                        <textarea id="description" name="description"
                                  class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                                  data-jodit-preset="compact"
                                  rows="3"
                                  placeholder="Mô tả ngắn về nội dung danh mục...">{{ old('description') }}</textarea>
                        @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Icon + Màu --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label" for="icon">
                                <span class="label-text font-medium">Icon</span>
                                <span class="label-text-alt text-xs opacity-40">Tabler Icon</span>
                            </label>
                            <input id="icon" type="text" name="icon" value="{{ old('icon') }}"
                                   class="input input-bordered input-sm w-full font-mono @error('icon') input-error @enderror"
                                   placeholder="ti-folder">
                            <div class="label">
                                <span class="label-text-alt opacity-40">VD: ti-folder, ti-book, ti-file-text</span>
                            </div>
                            @error('icon')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label" for="color_hex">
                                <span class="label-text font-medium">Màu sắc</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="colorHex"
                                       class="w-9 h-9 rounded cursor-pointer border border-base-300 p-0.5 bg-base-100 shrink-0">
                                <input id="color_hex" type="text" name="color_hex" x-model="colorHex"
                                       class="input input-bordered input-sm flex-1 font-mono @error('color_hex') input-error @enderror"
                                       placeholder="#534AB7">
                            </div>
                            @error('color_hex')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    {{-- Thứ tự --}}
                    <div class="form-control">
                        <label class="label" for="sort_order">
                            <span class="label-text font-medium">Thứ tự hiển thị</span>
                            <span class="label-text-alt text-xs opacity-40">Số nhỏ hiển thị trước</span>
                        </label>
                        <input id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                               min="0"
                               class="input input-bordered input-sm w-32 @error('sort_order') input-error @enderror">
                        @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>
            </div>

            {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
            <aside class="xl:sticky xl:top-4 space-y-4">

                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4 space-y-4">

                        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Xuất bản</p>

                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full bg-success inline-block"></span>
                            <span class="text-sm font-medium">Hiển thị sau khi tạo</span>
                        </div>

                        <div class="form-control">
                            <input type="hidden" name="is_active" value="0">
                            <label class="flex items-start gap-2.5 cursor-pointer select-none">
                                <input type="checkbox" name="is_active" value="1"
                                       class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                       {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                                <div>
                                    <span class="text-sm font-medium">Kích hoạt ngay</span>
                                    <p class="text-xs opacity-50 mt-0.5">Hiển thị trong danh sách tài liệu</p>
                                </div>
                            </label>
                        </div>

                        <div class="flex flex-col gap-2 pt-1 border-t border-base-200">
                            <button type="submit" class="btn btn-primary btn-sm w-full">Tạo danh mục</button>
                            <a href="{{ route('backend.kc-categories.index') }}"
                               class="btn btn-ghost btn-sm w-full">Hủy</a>
                        </div>

                        <p class="text-center text-xs opacity-30">
                            <span class="text-error">*</span> là trường bắt buộc
                        </p>

                    </div>
                </div>

                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4">
                        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Hướng dẫn</p>
                        <ul class="text-xs text-base-content/60 space-y-1.5 list-disc list-inside">
                            <li>Cây danh mục tối đa <strong>3 cấp</strong></li>
                            <li>Slug phải duy nhất trong tổ chức</li>
                            <li>Không xóa được khi còn tài liệu hoặc danh mục con</li>
                            <li>Dùng "Ẩn" thay vì xóa nếu muốn tạm thời không hiển thị</li>
                        </ul>
                    </div>
                </div>

            </aside>

        </div>
    </form>

</div>
@endsection

@push('scripts')
@vite([
    'resources/js/modules/toastify.js',
    'resources/js/modules/tom-select.js',
    'resources/js/modules/jodit.js',
    'Modules/KcCategory/resources/assets/sass/kc-category.scss',
    'Modules/KcCategory/resources/assets/js/kc-category.js',
], 'build/backend')
@endpush
