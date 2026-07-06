@csrf
@if(isset($blueprint)) @method('PUT') @endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="form-control">
        <label class="label label-text text-xs">Mã (code) <span class="text-error">*</span></label>
        <input type="text" name="code" value="{{ old('code', $blueprint->code ?? '') }}"
               placeholder="BP-TXNG-01" class="input input-bordered input-sm font-mono uppercase" required>
    </div>

    <div class="form-control">
        <label class="label label-text text-xs">Tên Blueprint <span class="text-error">*</span></label>
        <input type="text" name="name" value="{{ old('name', $blueprint->name ?? '') }}"
               class="input input-bordered input-sm" required>
    </div>

    <div class="form-control md:col-span-2">
        <label class="label label-text text-xs">Business Solution <span class="text-error">*</span></label>
        <select name="business_solution_id" class="select select-bordered select-sm" required>
            <option value="">— Chọn Business Solution —</option>
            @foreach($businessSolutions as $solution)
            <option value="{{ $solution->id }}" @selected(old('business_solution_id', $blueprint->business_solution_id ?? '') == $solution->id)>
                {{ $solution->name }} ({{ $solution->code }})
            </option>
            @endforeach
        </select>
    </div>

    <div class="form-control md:col-span-2">
        <label class="label label-text text-xs">Mô tả</label>
        <textarea name="description" rows="4" class="textarea textarea-bordered textarea-sm">{{ old('description', $blueprint->description ?? '') }}</textarea>
    </div>
</div>

<div class="flex justify-end gap-2 mt-6">
    <a href="{{ route('business_blueprint.admin.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
    <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
</div>
