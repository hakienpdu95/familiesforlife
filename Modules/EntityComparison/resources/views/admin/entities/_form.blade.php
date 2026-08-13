{{--
    $entity nullable — null ở create, model ở edit.
    $entityTypes — Collection<EntityType> đã eager-load criteria.options (kèm pivot.is_required).
    $currentValues — mảng keyed by criterion_id, chỉ có ở edit (§7.1 mục 4 — form chỉ hiện tiêu
    chí đã gán cho entity_type_id đang chọn; đổi type sẽ đổi bộ field hiện ra, xử lý client-side
    qua Alpine để không cần reload trang).
--}}
@php
    $entityTypesJson = $entityTypes->map(fn ($et) => [
        'id' => $et->id,
        'name' => $et->name,
        'criteria' => $et->criteria->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'type' => $c->type->value,
            'unit' => $c->unit,
            'is_required' => (bool) $c->pivot->is_required,
            'options' => $c->options->map(fn ($o) => ['id' => $o->id, 'label' => $o->label])->values()->all(),
        ])->values()->all(),
    ])->values()->all();
@endphp

<div
    x-data="entityForm({{ Js::from([
        'entityTypes' => $entityTypesJson,
        'selectedTypeId' => old('entity_type_id', $entity?->entity_type_id),
        'currentValues' => old('criterion_values', $currentValues ?? []),
    ]) }})"
    class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-6 items-start"
>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body space-y-4">

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Loại đối tượng <span class="text-error">*</span></span></label>
                <select name="entity_type_id" x-model.number="selectedTypeId"
                        class="select select-bordered select-sm w-full @error('entity_type_id') select-error @enderror">
                    <option value="">— Chọn loại đối tượng —</option>
                    <template x-for="et in entityTypes" :key="et.id">
                        <option :value="et.id" x-text="et.name" :selected="et.id === selectedTypeId"></option>
                    </template>
                </select>
                @error('entity_type_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Tên đối tượng <span class="text-error">*</span></span></label>
                <input type="text" name="name" value="{{ old('name', $entity?->name) }}"
                       class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                       placeholder="VD: Trường Mầm non ABC" maxlength="150">
                @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Mô tả</span></label>
                <textarea name="description" rows="3"
                          class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror">{{ old('description', $entity?->description) }}</textarea>
                @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ảnh đại diện</span></label>
                @if($entity?->getFirstMediaUrl('cover'))
                <img src="{{ $entity->getFirstMediaUrl('cover', 'thumb') }}" alt=""
                     class="h-20 w-auto rounded border border-base-300 mb-2 object-cover">
                @endif
                <input type="file" name="cover" accept="image/*"
                       class="file-input file-input-bordered file-input-sm w-full @error('cover') file-input-error @enderror">
                @error('cover')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            {{-- §7.1 mục 4 — chỉ hiện tiêu chí đã gán cho entity_type_id đang chọn. --}}
            <div x-show="criteria.length > 0" x-cloak class="pt-2 border-t border-base-200">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3 mt-3">Giá trị tiêu chí</p>

                <template x-for="criterion in criteria" :key="criterion.id">
                    <div class="form-control mb-3">
                        <label class="label py-0 pb-1">
                            <span class="label-text font-medium" x-text="criterion.name + (criterion.unit ? ' (' + criterion.unit + ')' : '')"></span>
                            <span x-show="criterion.is_required" class="text-error">*</span>
                        </label>

                        <template x-if="criterion.type === 'text'">
                            <input type="text" :name="'criterion_values[' + criterion.id + ']'"
                                   x-model="values[criterion.id]" class="input input-bordered input-sm w-full">
                        </template>

                        <template x-if="criterion.type === 'number'">
                            <input type="number" step="any" :name="'criterion_values[' + criterion.id + ']'"
                                   x-model="values[criterion.id]" class="input input-bordered input-sm w-full">
                        </template>

                        <template x-if="criterion.type === 'boolean'">
                            <select :name="'criterion_values[' + criterion.id + ']'" x-model="values[criterion.id]"
                                    class="select select-bordered select-sm w-full">
                                <option value="">— Chưa xác định —</option>
                                <option value="1">Có</option>
                                <option value="0">Không</option>
                            </select>
                        </template>

                        <template x-if="criterion.type === 'date'">
                            <input type="date" :name="'criterion_values[' + criterion.id + ']'"
                                   x-model="values[criterion.id]" class="input input-bordered input-sm w-full">
                        </template>

                        <template x-if="criterion.type === 'select'">
                            <select :name="'criterion_values[' + criterion.id + ']'" x-model.number="values[criterion.id]"
                                    class="select select-bordered select-sm w-full">
                                <option value="">— Chọn —</option>
                                <template x-for="opt in criterion.options" :key="opt.id">
                                    <option :value="opt.id" x-text="opt.label"></option>
                                </template>
                            </select>
                        </template>

                        <template x-if="criterion.type === 'multi_select'">
                            <div class="flex flex-wrap gap-3">
                                <template x-for="opt in criterion.options" :key="opt.id">
                                    <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                                        <input type="checkbox" :name="'criterion_values[' + criterion.id + '][]'" :value="opt.id"
                                               :checked="isChecked(criterion.id, opt.id)"
                                               @change="toggleMultiSelect(criterion.id, opt.id, $event.target.checked)"
                                               class="checkbox checkbox-xs">
                                        <span x-text="opt.label"></span>
                                    </label>
                                </template>
                            </div>
                        </template>

                        <template x-if="criterion.type === 'range'">
                            <div class="flex gap-2 items-center">
                                <input type="number" step="any" placeholder="Từ"
                                       :name="'criterion_values[' + criterion.id + '][min]'"
                                       x-model="values[criterion.id].min" class="input input-bordered input-sm w-full">
                                <span class="text-base-content/40">–</span>
                                <input type="number" step="any" placeholder="Đến"
                                       :name="'criterion_values[' + criterion.id + '][max]'"
                                       x-model="values[criterion.id].max" class="input input-bordered input-sm w-full">
                            </div>
                        </template>

                        <p class="mt-1 text-xs text-error" x-show="errors['criterion_values.' + criterion.id]" x-text="errors['criterion_values.' + criterion.id]"></p>
                    </div>
                </template>
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
                           {{ old('is_active', $entity?->is_active ?? true) ? 'checked' : '' }}>
                    <span class="text-sm font-medium group-hover:text-primary transition-colors">Đang hoạt động</span>
                </label>

                <div class="form-control mb-3">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Thứ tự hiển thị</span></label>
                    <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $entity?->sort_order ?? 0) }}"
                           class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror">
                    @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('backend.entity_comparison.entities.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-1">{{ $entity ? 'Lưu thay đổi' : 'Tạo mới' }}</button>
                </div>

                <p class="text-center text-xs text-base-content/30 mt-2.5"><span class="text-error">*</span> là trường bắt buộc</p>

            </div>
        </div>
    </div>

</div>

@push('scripts')
@once
<script>
function entityForm(config) {
    return {
        entityTypes: config.entityTypes,
        selectedTypeId: config.selectedTypeId,
        values: config.currentValues || {},
        errors: @json($errors->messages() ? collect($errors->messages())->map(fn ($m) => $m[0]) : []),
        get currentType() {
            return this.entityTypes.find(et => et.id === this.selectedTypeId) || null;
        },
        get criteria() {
            return this.currentType ? this.currentType.criteria : [];
        },
        init() {
            this.criteria.forEach(c => {
                if (c.type === 'range' && (!this.values[c.id] || typeof this.values[c.id] !== 'object')) {
                    this.values[c.id] = { min: '', max: '' };
                }
                if (c.type === 'multi_select' && !Array.isArray(this.values[c.id])) {
                    this.values[c.id] = [];
                }
            });
        },
        isChecked(criterionId, optionId) {
            return Array.isArray(this.values[criterionId]) && this.values[criterionId].includes(optionId);
        },
        toggleMultiSelect(criterionId, optionId, checked) {
            if (!Array.isArray(this.values[criterionId])) this.values[criterionId] = [];
            if (checked) {
                if (!this.values[criterionId].includes(optionId)) this.values[criterionId].push(optionId);
            } else {
                this.values[criterionId] = this.values[criterionId].filter(id => id !== optionId);
            }
        },
    };
}
</script>
@endonce
@endpush
