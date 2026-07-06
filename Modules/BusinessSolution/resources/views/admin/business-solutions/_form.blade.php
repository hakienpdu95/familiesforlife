@csrf
@if(isset($solution)) @method('PUT') @endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="form-control">
        <label class="label label-text text-xs">Mã (code) <span class="text-error">*</span></label>
        <input type="text" name="code" value="{{ old('code', $solution->code ?? '') }}"
               placeholder="AI-TXNG" class="input input-bordered input-sm font-mono uppercase" required>
        <span class="text-xs text-base-content/40 mt-1">Chữ hoa, số, gạch ngang — dùng làm khóa định danh nội bộ.</span>
    </div>

    <div class="form-control">
        <label class="label label-text text-xs">Tên Solution <span class="text-error">*</span></label>
        <input type="text" name="name" value="{{ old('name', $solution->name ?? '') }}"
               placeholder="AI Truy xuất nguồn gốc" class="input input-bordered input-sm" required>
    </div>

    <div class="form-control">
        <label class="label label-text text-xs">Vertical <span class="text-error">*</span></label>
        <select name="vertical_id" class="select select-bordered select-sm" required>
            <option value="">— Chọn vertical —</option>
            @foreach($verticals as $vertical)
            <option value="{{ $vertical->id }}" @selected(old('vertical_id', $solution->vertical_id ?? '') == $vertical->id)>
                {{ $vertical->name }}
            </option>
            @endforeach
        </select>
    </div>

    <div class="form-control">
        <label class="label label-text text-xs">Visibility <span class="text-error">*</span></label>
        <select name="visibility" class="select select-bordered select-sm" required>
            @foreach(\Modules\BusinessSolution\Enums\BusinessSolutionVisibility::cases() as $visibility)
            <option value="{{ $visibility->value }}" @selected(old('visibility', $solution->visibility ?? 'private') === $visibility->value)>
                {{ $visibility->label() }}
            </option>
            @endforeach
        </select>
    </div>

    <div class="form-control md:col-span-2">
        <label class="label label-text text-xs">Mô tả ngắn</label>
        <input type="text" name="short_description" value="{{ old('short_description', $solution->short_description ?? '') }}"
               class="input input-bordered input-sm">
    </div>

    <div class="form-control md:col-span-2">
        <label class="label label-text text-xs">Mô tả chi tiết</label>
        <textarea name="description" rows="4" class="textarea textarea-bordered textarea-sm">{{ old('description', $solution->description ?? '') }}</textarea>
    </div>

    <div class="form-control md:col-span-2">
        <label class="label label-text text-xs">Đối tượng phù hợp (target_customers)</label>
        <input type="text" name="target_customers_raw"
               value="{{ old('target_customers_raw', isset($solution) ? implode(', ', $solution->target_customers ?? []) : '') }}"
               placeholder="htx, sme" class="input input-bordered input-sm">
        <span class="text-xs text-base-content/40 mt-1">Cách nhau bởi dấu phẩy.</span>
        <input type="hidden" name="target_customers_hint" value="1">
    </div>

    <div class="form-control md:col-span-2">
        <label class="label label-text text-xs">Thumbnail URL</label>
        <input type="text" name="thumbnail_url" value="{{ old('thumbnail_url', $solution->thumbnail_url ?? '') }}"
               class="input input-bordered input-sm">
    </div>
</div>

<div class="flex justify-end gap-2 mt-6">
    <a href="{{ route('business_solutions.admin.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
    <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
</div>
