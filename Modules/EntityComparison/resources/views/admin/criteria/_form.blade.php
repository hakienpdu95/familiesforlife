{{-- $criterion nullable — null ở create, model (với options đã load) ở edit. --}}
<div
    x-data="{
        type: {{ Js::from(old('type', $criterion?->type?->value ?? 'text')) }},
        options: {{ Js::from(
            old('options', $criterion?->options?->map(fn ($o) => ['value' => $o->value, 'label' => $o->label])->all() ?? [['value' => '', 'label' => '']])
        ) }},
        errors: {{ Js::from(collect($errors->messages())->map(fn ($m) => $m[0])) }},
        get hasOptions() { return this.type === 'select' || this.type === 'multi_select'; },
        addOption() { this.options.push({ value: '', label: '' }); },
        removeOption(i) { this.options.splice(i, 1); },
    }"
    class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-6 items-start"
>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body space-y-4">

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Tên tiêu chí <span class="text-error">*</span></span></label>
                <input type="text" name="name" value="{{ old('name', $criterion?->name) }}"
                       class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                       placeholder="VD: Độ tuổi nhập học" maxlength="150" autofocus>
                @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Kiểu dữ liệu <span class="text-error">*</span></span></label>
                    <select name="type" x-model="type" class="select select-bordered select-sm w-full @error('type') select-error @enderror">
                        @foreach($types as $t)
                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                        @endforeach
                    </select>
                    @error('type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Đơn vị</span>
                        <span class="label-text-alt text-xs text-base-content/40">VD: tuổi, giờ, VNĐ/tháng</span></label>
                    <input type="text" name="unit" value="{{ old('unit', $criterion?->unit) }}"
                           class="input input-bordered input-sm w-full @error('unit') input-error @enderror" maxlength="50">
                    @error('unit')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Mô tả</span></label>
                <textarea name="description" rows="3"
                          class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror">{{ old('description', $criterion?->description) }}</textarea>
                @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            {{--
                §0 mục 8 — hàng thật ở criterion_options, chỉ hiện khi type ∈ {select, multi_select}.
                BẮT BUỘC dùng <template x-if> (gỡ hẳn khỏi DOM), KHÔNG dùng x-show (chỉ ẩn bằng
                CSS display:none — input vẫn còn trong DOM và vẫn bị submit cùng form). Với
                x-show, khi tạo criterion type=text (mặc định), 1 hàng option rỗng
                (options[0][value]='', options[0][label]='') vẫn được gửi lên vì bị ẩn chứ không
                bị xoá, khiến rule `required_with:options` trong StoreCriterionRequest luôn fail →
                form không bao giờ submit được cho các type không dùng options (bug thật, đã xác
                nhận qua báo cáo "dashboard/entity-comparison/criteria/create không thêm được data").
            --}}
            <template x-if="hasOptions">
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Danh sách lựa chọn</span></label>
                    <div class="space-y-2">
                        <template x-for="(option, i) in options" :key="i">
                            <div>
                                <div class="flex gap-2 items-center">
                                    <input type="text" :name="'options[' + i + '][value]'" x-model="option.value"
                                           placeholder="Giá trị (machine value)" class="input input-bordered input-sm w-1/3">
                                    <input type="text" :name="'options[' + i + '][label]'" x-model="option.label"
                                           placeholder="Nhãn hiển thị" class="input input-bordered input-sm flex-1">
                                    <button type="button" @click="removeOption(i)" class="btn btn-ghost btn-xs text-error">✕</button>
                                </div>
                                <p class="mt-0.5 text-xs text-error" x-show="errors['options.' + i + '.value']" x-text="errors['options.' + i + '.value']"></p>
                                <p class="mt-0.5 text-xs text-error" x-show="errors['options.' + i + '.label']" x-text="errors['options.' + i + '.label']"></p>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="addOption()" class="btn btn-ghost btn-xs mt-2 self-start">+ Thêm lựa chọn</button>
                    {{-- Lỗi từng dòng option render qua Alpine (errors['options.'+i+'.value']) ở
                         trên — @error("options.*.value") KHÔNG hoạt động vì Laravel lưu lỗi theo
                         index cụ thể (options.0.value), không theo literal wildcard '*'. --}}
                    @error('options')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </template>

            {{-- 1 criterion — 1 entity type, bắt buộc. --}}
            <div class="form-control pt-2 border-t border-base-200">
                <label class="label py-0 pb-1.5 pt-3"><span class="label-text font-medium">Loại đối tượng <span class="text-error">*</span></span></label>
                @if($entityTypes->isEmpty())
                <p class="text-xs text-base-content/40">
                    Chưa có loại đối tượng nào — <a href="{{ route('backend.entity_comparison.entity_types.create') }}" class="link">tạo loại đối tượng trước</a>.
                </p>
                @else
                <select name="entity_type_id" required
                        class="select select-bordered select-sm w-full @error('entity_type_id') select-error @enderror">
                    <option value="" disabled {{ old('entity_type_id', $currentEntityTypeId ?? '') === '' ? 'selected' : '' }}>— Chọn loại đối tượng —</option>
                    @foreach($entityTypes as $entityType)
                    <option value="{{ $entityType->id }}" {{ (string) old('entity_type_id', $currentEntityTypeId ?? '') === (string) $entityType->id ? 'selected' : '' }}>
                        {{ $entityType->name }}{{ ! $entityType->is_active ? ' (đã tắt)' : '' }}
                    </option>
                    @endforeach
                </select>
                @endif
                @error('entity_type_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

        </div>
    </div>

    <div class="xl:sticky xl:top-4 space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-3 space-y-3">

                <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                    <input type="hidden" name="is_filterable" value="0">
                    <input type="checkbox" name="is_filterable" value="1"
                           class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                           {{ old('is_filterable', $criterion?->is_filterable ?? true) ? 'checked' : '' }}>
                    <span class="text-sm font-medium group-hover:text-primary transition-colors">Hiện ở form lọc</span>
                </label>

                <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                    <input type="hidden" name="is_comparable" value="0">
                    <input type="checkbox" name="is_comparable" value="1"
                           class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                           {{ old('is_comparable', $criterion?->is_comparable ?? true) ? 'checked' : '' }}>
                    <span class="text-sm font-medium group-hover:text-primary transition-colors">Hiện ở bảng so sánh</span>
                </label>

                {{-- "Bắt buộc nhập" không nằm ở đây — nó là is_required trên pivot
                     entity_type_criterion (1 criterion có thể bắt buộc ở type này nhưng không ở
                     type khác nếu sau này mở lại multi-assign), chỉnh ở màn hình "Tiêu chí" của
                     từng loại đối tượng (entity-types/{slug}/criteria), không phải ở đây. --}}

                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Thứ tự hiển thị</span></label>
                    <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $criterion?->sort_order ?? 0) }}"
                           class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror">
                    @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex gap-2 pt-2">
                    <a href="{{ route('backend.entity_comparison.criteria.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-1">{{ $criterion ? 'Lưu thay đổi' : 'Tạo mới' }}</button>
                </div>

            </div>
        </div>
    </div>

</div>
