<div class="space-y-5">

    {{-- ── Basic info ───────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold text-sm uppercase tracking-wide opacity-60 mb-3">Thông tin cơ bản</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Listing type --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Loại tin <span class="text-error">*</span></span></label>
                    <select name="listing_type" x-model="listingType"
                            class="select select-bordered select-sm @error('listing_type') select-error @enderror">
                        @foreach($listingTypes as $type)
                            <option value="{{ $type->value }}"
                                {{ old('listing_type', $listing->listing_type?->value ?? 'job') === $type->value ? 'selected' : '' }}>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('listing_type')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Visibility --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Hiển thị <span class="text-error">*</span></span></label>
                    <select name="visibility" class="select select-bordered select-sm @error('visibility') select-error @enderror">
                        @foreach($visibilities as $v)
                            <option value="{{ $v->value }}"
                                {{ old('visibility', $listing->visibility?->value ?? 'public') === $v->value ? 'selected' : '' }}>
                                {{ $v->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('visibility')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Headcount --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Số lượng tuyển</span></label>
                    <input type="number" name="headcount" min="1" max="999"
                           value="{{ old('headcount', $listing->headcount ?? 1) }}"
                           class="input input-bordered input-sm @error('headcount') input-error @enderror">
                    @error('headcount')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Title --}}
            <div class="form-control mt-3">
                <label class="label"><span class="label-text font-medium">Tiêu đề <span class="text-error">*</span></span></label>
                <input type="text" name="title" maxlength="300"
                       value="{{ old('title', $listing->title ?? '') }}"
                       placeholder="Vd: Senior Laravel Developer — Remote"
                       class="input input-bordered input-sm @error('title') input-error @enderror">
                @error('title')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Description --}}
            <div class="form-control mt-3">
                <label class="label"><span class="label-text font-medium">Mô tả công việc <span class="text-error">*</span></span></label>
                <textarea name="description" rows="6"
                          placeholder="Mô tả chi tiết về vị trí / dự án..."
                          class="textarea textarea-bordered text-sm @error('description') textarea-error @enderror">{{ old('description', $listing->description ?? '') }}</textarea>
                @error('description')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Requirements --}}
            <div class="form-control mt-3">
                <label class="label"><span class="label-text font-medium">Yêu cầu ứng viên</span></label>
                <textarea name="requirements" rows="4"
                          placeholder="Kinh nghiệm, kỹ năng, bằng cấp yêu cầu..."
                          class="textarea textarea-bordered text-sm">{{ old('requirements', $listing->requirements ?? '') }}</textarea>
            </div>

            {{-- Benefits --}}
            <div class="form-control mt-3">
                <label class="label"><span class="label-text font-medium">Quyền lợi</span></label>
                <textarea name="benefits" rows="3"
                          placeholder="Lương thưởng, bảo hiểm, team building..."
                          class="textarea textarea-bordered text-sm">{{ old('benefits', $listing->benefits ?? '') }}</textarea>
            </div>
        </div>
    </div>

    {{-- ── Job details ──────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold text-sm uppercase tracking-wide opacity-60 mb-3">Chi tiết công việc</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

                {{-- Work type --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Hình thức làm việc</span></label>
                    <select name="work_type" class="select select-bordered select-sm">
                        @foreach($workTypes as $wt)
                            <option value="{{ $wt->value }}"
                                {{ old('work_type', $listing->work_type?->value ?? 'flexible') === $wt->value ? 'selected' : '' }}>
                                {{ $wt->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Employment type (job only) --}}
                <div class="form-control" x-show="listingType === 'job'">
                    <label class="label"><span class="label-text font-medium">Loại hợp đồng</span></label>
                    <select name="employment_type" class="select select-bordered select-sm">
                        <option value="">— Không chọn —</option>
                        @foreach($employmentTypes as $et)
                            <option value="{{ $et->value }}"
                                {{ old('employment_type', $listing->employment_type?->value) === $et->value ? 'selected' : '' }}>
                                {{ $et->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Experience level --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Cấp độ kinh nghiệm</span></label>
                    <select name="experience_level" class="select select-bordered select-sm">
                        @foreach($experienceLevels as $el)
                            <option value="{{ $el->value }}"
                                {{ old('experience_level', $listing->experience_level?->value ?? 'any') === $el->value ? 'selected' : '' }}>
                                {{ $el->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Location --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Địa điểm</span></label>
                    <input type="text" name="location" maxlength="200"
                           value="{{ old('location', $listing->location ?? '') }}"
                           placeholder="Hà Nội, TP.HCM, Remote..."
                           class="input input-bordered input-sm">
                </div>

                {{-- Expire at --}}
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Hạn nộp hồ sơ</span></label>
                    <input type="date" name="expire_at"
                           value="{{ old('expire_at', $listing->expire_at?->format('Y-m-d') ?? '') }}"
                           class="input input-bordered input-sm">
                </div>

            </div>
        </div>
    </div>

    {{-- ── Salary / Budget ──────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold text-sm uppercase tracking-wide opacity-60 mb-3">Mức lương / Ngân sách</h3>

            {{-- Job salary --}}
            <div x-show="listingType !== 'project'">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text text-xs font-medium">Lương tối thiểu</span></label>
                        <input type="number" name="salary_min" min="0" step="500000"
                               value="{{ old('salary_min', $listing->salary_min ?? '') }}"
                               placeholder="0"
                               class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text text-xs font-medium">Lương tối đa</span></label>
                        <input type="number" name="salary_max" min="0" step="500000"
                               value="{{ old('salary_max', $listing->salary_max ?? '') }}"
                               placeholder="0"
                               class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text text-xs font-medium">Đơn vị tiền tệ</span></label>
                        <input type="text" name="salary_currency" maxlength="3"
                               value="{{ old('salary_currency', $listing->salary_currency ?? 'VND') }}"
                               class="input input-bordered input-sm">
                    </div>
                    <div class="form-control justify-end pb-1">
                        <label class="label cursor-pointer gap-2">
                            <input type="checkbox" name="salary_is_negotiable" value="1" class="checkbox checkbox-sm"
                                   {{ old('salary_is_negotiable', $listing->salary_is_negotiable ?? false) ? 'checked' : '' }}>
                            <span class="label-text text-sm">Thỏa thuận</span>
                        </label>
                        <label class="label cursor-pointer gap-2">
                            <input type="checkbox" name="salary_is_visible" value="1" class="checkbox checkbox-sm"
                                   {{ old('salary_is_visible', $listing->salary_is_visible ?? true) ? 'checked' : '' }}>
                            <span class="label-text text-sm">Hiển thị mức lương</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Project budget --}}
            <div x-show="listingType === 'project'">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text text-xs font-medium">Ngân sách tối thiểu</span></label>
                        <input type="number" name="budget_min" min="0"
                               value="{{ old('budget_min', $listing->budget_min ?? '') }}"
                               class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text text-xs font-medium">Ngân sách tối đa</span></label>
                        <input type="number" name="budget_max" min="0"
                               value="{{ old('budget_max', $listing->budget_max ?? '') }}"
                               class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text text-xs font-medium">Thời gian (ngày)</span></label>
                        <input type="number" name="duration_days" min="1"
                               value="{{ old('duration_days', $listing->duration_days ?? '') }}"
                               class="input input-bordered input-sm">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tags ─────────────────────────────────────────────────── --}}
    @if($tags->isNotEmpty())
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold text-sm uppercase tracking-wide opacity-60 mb-3">Tags kỹ năng</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($tags as $tag)
                @php $checked = in_array($tag->id, old('tag_ids', $listing->tags?->pluck('id')->toArray() ?? [])); @endphp
                <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}"
                           class="checkbox checkbox-xs" {{ $checked ? 'checked' : '' }}>
                    <span class="text-sm">{{ $tag->name }}</span>
                </label>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Validation errors summary --}}
    @if($errors->any())
    <div class="alert alert-error">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <ul class="text-sm list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

</div>
