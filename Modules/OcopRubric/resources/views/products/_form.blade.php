@csrf
@if(isset($product)) @method('PUT') @endif

<div class="flex flex-col gap-4">
    <div class="form-control">
        <label class="label label-text text-xs">Bộ sản phẩm <span class="text-error">*</span></label>
        <select name="product_group_id" class="select select-bordered select-sm" required>
            <option value="">— Chọn bộ sản phẩm —</option>
            @foreach($groups as $g)
            <option value="{{ $g->id }}" @selected(old('product_group_id', $product->product_group_id ?? '') == $g->id)>
                {{ $g->name }} ({{ $g->industry_code }})
            </option>
            @endforeach
        </select>
    </div>

    <div class="form-control">
        <label class="label label-text text-xs">Tên sản phẩm <span class="text-error">*</span></label>
        <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}"
               placeholder="VD: Cam Cao Phong loại 1" class="input input-bordered input-sm" required>
    </div>

    <div class="form-control">
        <label class="label label-text text-xs">Mã số sản phẩm (nếu có)</label>
        <input type="text" name="product_code" value="{{ old('product_code', $product->product_code ?? '') }}"
               placeholder="(Mã tỉnh)-(Mã xã)-(STT)-(Năm)" class="input input-bordered input-sm">
    </div>
</div>

<div class="flex justify-end gap-2 mt-6">
    <a href="{{ route('ocop.products.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
    <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
</div>
