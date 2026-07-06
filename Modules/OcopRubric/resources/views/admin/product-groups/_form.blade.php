@csrf
@if(isset($group)) @method('PUT') @endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="form-control">
        <label class="label label-text text-xs">Mã (code) <span class="text-error">*</span></label>
        <input type="text" name="code" value="{{ old('code', $group->code ?? '') }}"
               placeholder="rau-cu-qua-hat-tuoi" class="input input-bordered input-sm font-mono" required>
        <span class="text-xs text-base-content/40 mt-1">Chữ thường, số, gạch ngang — dùng làm khóa định danh nội bộ.</span>
    </div>

    <div class="form-control">
        <label class="label label-text text-xs">Tên bộ sản phẩm <span class="text-error">*</span></label>
        <input type="text" name="name" value="{{ old('name', $group->name ?? '') }}"
               placeholder="Rau, củ, quả, hạt tươi" class="input input-bordered input-sm" required>
    </div>

    <div class="form-control">
        <label class="label label-text text-xs">Mã ngành <span class="text-error">*</span></label>
        <select name="industry_code" class="select select-bordered select-sm" required>
            @foreach(['I','II','III','IV','V','VI'] as $code)
            <option value="{{ $code }}" @selected(old('industry_code', $group->industry_code ?? '') === $code)>{{ $code }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-control">
        <label class="label label-text text-xs">Tên ngành <span class="text-error">*</span></label>
        <input type="text" name="industry_name" value="{{ old('industry_name', $group->industry_name ?? '') }}"
               placeholder="SẢN PHẨM THỰC PHẨM" class="input input-bordered input-sm" required>
    </div>

    <div class="form-control">
        <label class="label label-text text-xs">Nhóm (group_label)</label>
        <input type="text" name="group_label" value="{{ old('group_label', $group->group_label ?? '') }}"
               placeholder="Nhóm: Thực phẩm tươi sống" class="input input-bordered input-sm">
    </div>

    <div class="form-control">
        <label class="label label-text text-xs">Cơ quan chủ trì quản lý</label>
        <input type="text" name="managing_agency" value="{{ old('managing_agency', $group->managing_agency ?? '') }}"
               placeholder="Bộ Nông nghiệp và Môi trường" class="input input-bordered input-sm">
    </div>

    <div class="form-control">
        <label class="label label-text text-xs">Thứ tự hiển thị</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $group->sort_order ?? 0) }}"
               min="0" class="input input-bordered input-sm">
    </div>

    <div class="flex items-center gap-6 mt-6">
        <label class="label cursor-pointer gap-2">
            <input type="hidden" name="requires_sample_product" value="0">
            <input type="checkbox" name="requires_sample_product" value="1" class="checkbox checkbox-sm"
                   @checked(old('requires_sample_product', $group->requires_sample_product ?? true))>
            <span class="label-text text-sm">Yêu cầu 05 sản phẩm mẫu (Điều 6.2.d)</span>
        </label>

        <label class="label cursor-pointer gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="checkbox checkbox-sm"
                   @checked(old('is_active', $group->is_active ?? true))>
            <span class="label-text text-sm">Đang áp dụng (is_active)</span>
        </label>
    </div>
</div>

<div class="flex justify-end gap-2 mt-6">
    <a href="{{ route('ocop_rubric.admin.product-groups.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
    <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
</div>
