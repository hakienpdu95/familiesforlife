{{-- $entityType nullable — null ở create, model ở edit. Mirror Modules/Heritage/resources/views/admin/sites/_form.blade.php. --}}
<div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-6 items-start">

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body space-y-4">

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Tên loại đối tượng <span class="text-error">*</span></span></label>
                <input type="text" name="name" value="{{ old('name', $entityType?->name) }}"
                       class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                       placeholder="VD: Trường học" maxlength="150" autofocus>
                @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Icon</span>
                    <span class="label-text-alt text-xs text-base-content/40">Tên icon (tuỳ chọn)</span></label>
                <input type="text" name="icon" value="{{ old('icon', $entityType?->icon) }}"
                       class="input input-bordered input-sm w-full @error('icon') input-error @enderror" maxlength="100">
                @error('icon')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Mô tả</span></label>
                <textarea name="description" rows="4"
                          class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror">{{ old('description', $entityType?->description) }}</textarea>
                @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ảnh đại diện</span></label>
                @if($entityType?->getFirstMediaUrl('cover'))
                <img src="{{ $entityType->getFirstMediaUrl('cover', 'thumb') }}" alt=""
                     class="h-20 w-auto rounded border border-base-300 mb-2 object-cover">
                @endif
                <input type="file" name="cover" accept="image/*"
                       class="file-input file-input-bordered file-input-sm w-full @error('cover') file-input-error @enderror">
                @if($entityType)
                <p class="text-xs text-base-content/40 mt-1.5">Tải ảnh mới sẽ tự động thay ảnh hiện tại.</p>
                @endif
                @error('cover')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

        </div>
    </div>

    <div class="xl:sticky xl:top-4 space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-3">

                <label class="flex items-start gap-2.5 cursor-pointer select-none group mb-4">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                           {{ old('is_active', $entityType?->is_active ?? true) ? 'checked' : '' }}>
                    <span class="text-sm font-medium group-hover:text-primary transition-colors">Đang hoạt động</span>
                </label>

                <div class="form-control mb-3">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Thứ tự hiển thị</span></label>
                    <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $entityType?->sort_order ?? 0) }}"
                           class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror">
                    @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('backend.entity_comparison.entity_types.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-1">{{ $entityType ? 'Lưu thay đổi' : 'Tạo mới' }}</button>
                </div>

                <p class="text-center text-xs text-base-content/30 mt-2.5"><span class="text-error">*</span> là trường bắt buộc</p>

            </div>
        </div>
    </div>

</div>
