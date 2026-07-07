@csrf
@if(isset($category)) @method('PUT') @endif

<div class="flex flex-col gap-4">
    <div class="form-control">
        <label class="label label-text text-xs">Tên danh mục <span class="text-error">*</span></label>
        <input type="text" name="name" value="{{ old('name', $category->name ?? '') }}"
               placeholder="VD: Trẻ sơ sinh" class="input input-bordered input-sm" required>
    </div>

    <div class="form-control">
        <label class="label label-text text-xs">Danh mục cha</label>
        <select name="parent_id" class="select select-bordered select-sm">
            <option value="">— Danh mục gốc —</option>
            @foreach($categories as $c)
                @continue(isset($category) && $c->id === $category->id)
                <option value="{{ $c->id }}" @selected(old('parent_id', $category->parent_id ?? '') == $c->id)>
                    {{ $c->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-control">
        <label class="label label-text text-xs">Mô tả</label>
        <textarea name="description" rows="3" class="textarea textarea-bordered textarea-sm"
                  placeholder="Mô tả ngắn về danh mục...">{{ old('description', $category->description ?? '') }}</textarea>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div class="form-control">
            <label class="label label-text text-xs">Icon</label>
            <input type="text" name="icon" value="{{ old('icon', $category->icon ?? '') }}"
                   placeholder="ti-baby-carriage" class="input input-bordered input-sm font-mono">
        </div>
        <div class="form-control">
            <label class="label label-text text-xs">Màu</label>
            <div class="flex items-center gap-2">
                <input type="color" value="{{ old('color_hex', $category->color_hex ?? '#94a3b8') }}"
                       onchange="this.nextElementSibling.value = this.value"
                       class="w-9 h-9 rounded cursor-pointer border border-base-300 p-0.5 bg-base-100 shrink-0">
                <input type="text" name="color_hex" value="{{ old('color_hex', $category->color_hex ?? '') }}"
                       placeholder="#94a3b8" class="input input-bordered input-sm flex-1 font-mono">
            </div>
        </div>
        <div class="form-control">
            <label class="label label-text text-xs">Thứ tự hiển thị</label>
            <input type="number" name="sort_order" min="0"
                   value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="input input-bordered input-sm">
        </div>
    </div>

    <div class="form-control">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="checkbox checkbox-sm"
                   @checked(old('is_active', $category->is_active ?? true))>
            <span class="text-sm">Hiển thị danh mục</span>
        </label>
    </div>
</div>

<div class="flex justify-end gap-2 mt-6">
    <a href="{{ route('backend.post.categories.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
    <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
</div>
