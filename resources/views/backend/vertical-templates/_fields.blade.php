@php($tpl = $template ?? null)

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="form-control sm:col-span-1">
        <label class="label py-0 pb-1.5">
            <span class="label-text font-medium">Mã vertical (code) <span class="text-error">*</span></span>
            @if(! $tpl)
            <span class="label-text-alt text-xs text-base-content/40">Tự động tạo từ tên nếu để trống</span>
            @endif
        </label>
        <input type="text" name="code" value="{{ old('code', $tpl?->code) }}"
               {{ $tpl ? 'readonly' : '' }}
               data-req="Vui lòng nhập mã vertical"
               @if(! $tpl)
               data-val-pattern="^[a-z0-9]+(-[a-z0-9]+)*$"
               data-val-pattern-msg="Chỉ dùng chữ thường, số và dấu gạch ngang (vd: truy-xuat-nguon-goc)"
               @endif
               class="input input-bordered input-sm w-full font-mono @error('code') input-error @enderror {{ $tpl ? 'field-readonly' : '' }}"
               placeholder="truy-xuat-nguon-goc">
        @error('code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        @if($tpl)
        <label class="label"><span class="label-text-alt text-base-content/40">Không thể đổi sau khi tạo</span></label>
        @endif
    </div>
    <div class="form-control sm:col-span-1">
        <label class="label py-0 pb-1.5">
            <span class="label-text font-medium">Tên hiển thị (label) <span class="text-error">*</span></span>
        </label>
        <input type="text" name="label" value="{{ old('label', $tpl?->label) }}"
               data-req="Vui lòng nhập tên hiển thị"
               class="input input-bordered input-sm w-full @error('label') input-error @enderror"
               placeholder="Truy xuất nguồn gốc Nông sản" autofocus>
        @error('label')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-control sm:col-span-1">
        <label class="label py-0 pb-1.5">
            <span class="label-text font-medium">Nhãn đối tượng triển khai <span class="text-error">*</span></span>
            <span class="label-text-alt text-xs text-base-content/40">target_label</span>
        </label>
        <input type="text" name="target_label" value="{{ old('target_label', $tpl?->target_label ?? 'Tổ chức') }}"
               data-req="Vui lòng nhập nhãn đối tượng triển khai"
               class="input input-bordered input-sm w-full @error('target_label') input-error @enderror"
               placeholder="Tổ chức / HTX">
        @error('target_label')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>
    <div class="form-control sm:col-span-1">
        <label class="label py-0 pb-1.5">
            <span class="label-text font-medium">Nhóm đối tượng <span class="text-error">*</span></span>
            <span class="label-text-alt text-xs text-base-content/40">target_org_category</span>
        </label>
        <input type="text" name="target_org_category" value="{{ old('target_org_category', $tpl?->target_org_category ?? 'organization') }}"
               data-req="Vui lòng nhập nhóm đối tượng"
               class="input input-bordered input-sm w-full @error('target_org_category') input-error @enderror"
               placeholder="cooperative">
        @error('target_org_category')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-control sm:col-span-1">
        <label class="label py-0 pb-1.5" for="ts-readiness-slug">
            <span class="label-text font-medium">Slug khảo sát sẵn sàng</span>
            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
        </label>
        <select id="ts-readiness-slug" name="readiness_template_slug"
                class="select select-bordered select-sm w-full ts-init @error('readiness_template_slug') select-error @enderror"
                data-ts-placeholder="— Chọn khảo sát —"
                data-survey-options-api="{{ route('backend.vertical-templates.survey-options') }}"
                data-selected-value="{{ old('readiness_template_slug', $tpl?->readiness_template_slug) }}">
            @if($tpl?->readiness_template_slug || old('readiness_template_slug'))
            <option value="{{ old('readiness_template_slug', $tpl?->readiness_template_slug) }}" selected>
                {{ old('readiness_template_slug', $tpl?->readiness_template_slug) }}
            </option>
            @endif
        </select>
        <p class="mt-1 text-xs text-base-content/40">Danh sách nạp theo tổ chức đã chọn ở trên (hoặc khảo sát dùng chung).</p>
        @error('readiness_template_slug')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>
    <div class="form-control sm:col-span-1">
        <label class="label py-0 pb-1.5" for="ts-data-collection-slug">
            <span class="label-text font-medium">Slug khảo sát thu thập dữ liệu</span>
            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
        </label>
        <select id="ts-data-collection-slug" name="data_collection_template_slug"
                class="select select-bordered select-sm w-full ts-init @error('data_collection_template_slug') select-error @enderror"
                data-ts-placeholder="— Chọn khảo sát —"
                data-survey-options-api="{{ route('backend.vertical-templates.survey-options') }}"
                data-selected-value="{{ old('data_collection_template_slug', $tpl?->data_collection_template_slug) }}">
            @if($tpl?->data_collection_template_slug || old('data_collection_template_slug'))
            <option value="{{ old('data_collection_template_slug', $tpl?->data_collection_template_slug) }}" selected>
                {{ old('data_collection_template_slug', $tpl?->data_collection_template_slug) }}
            </option>
            @endif
        </select>
        <p class="mt-1 text-xs text-base-content/40">Danh sách nạp theo tổ chức đã chọn ở trên (hoặc khảo sát dùng chung).</p>
        @error('data_collection_template_slug')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-control sm:col-span-2">
        <label class="label py-0 pb-1.5">
            <span class="label-text font-medium">Vai trò mặc định</span>
            <span class="label-text-alt text-xs text-base-content/40">default_roles — gõ rồi Enter để thêm từng vai trò</span>
        </label>
        <select id="ts-default-roles" name="default_roles[]" multiple
                class="select select-bordered select-sm w-full @error('default_roles') select-error @enderror">
            @foreach(old('default_roles', $tpl?->default_roles ?? []) as $role)
            <option value="{{ $role }}" selected>{{ $role }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-base-content/40">VD: <code class="bg-base-200 px-1 rounded">pm</code>, <code class="bg-base-200 px-1 rounded">surveyor</code>, <code class="bg-base-200 px-1 rounded">data_ops</code> — mỗi vai trò tạo 1 role dạng <code class="bg-base-200 px-1 rounded">{code}_{vai trò}</code></p>
        @error('default_roles')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        @error('default_roles.*')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>

    <div class="form-control sm:col-span-1">
        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
            <input type="checkbox" name="has_physical_assets" value="1"
                   {{ old('has_physical_assets', $tpl?->has_physical_assets ?? true) ? 'checked' : '' }}
                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0">
            <div>
                <span class="text-sm font-medium group-hover:text-primary transition-colors">Có quản lý tài sản vật lý</span>
                <p class="text-xs text-base-content/50 mt-0.5">Khu / lô / cây — bật nếu vertical này theo dõi thực địa</p>
            </div>
        </label>
    </div>
    <div class="form-control sm:col-span-1">
        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
            <input type="checkbox" name="is_active" value="1"
                   {{ old('is_active', $tpl?->is_active ?? true) ? 'checked' : '' }}
                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0">
            <div>
                <span class="text-sm font-medium group-hover:text-primary transition-colors">Đang hoạt động</span>
                <p class="text-xs text-base-content/50 mt-0.5">Hiện trong danh sách nhân bản của tổ chức</p>
            </div>
        </label>
    </div>
</div>
