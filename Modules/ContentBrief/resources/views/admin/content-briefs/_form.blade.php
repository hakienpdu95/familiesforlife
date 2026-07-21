{{-- Dùng chung create/edit — spec/ContentBrief_Technical_Specification.md §4.2. --}}
@php
    $snapshot = old() ?: ($brief?->currentVersion?->snapshot ?? []);
    $isApproved = $brief && $brief->currentVersion?->status?->value === 'approved';
@endphp

<div x-data="briefForm({
        outline: {{ json_encode(old('outline', $snapshot['outline'] ?? [['heading' => '', 'level' => 2, 'notes' => '']])) }},
        key_facts: {{ json_encode(old('key_facts', $snapshot['key_facts'] ?? [])) }},
        competitor_references: {{ json_encode(old('competitor_references', $snapshot['competitor_references'] ?? [])) }},
        related_references: {{ json_encode(old('related_references', $snapshot['related_references'] ?? [])) }},
     })"
     class="space-y-5">

    @if($isApproved)
    <div class="alert alert-warning text-sm">
        <span>Version hiện tại đã <strong>Duyệt</strong> — nội dung bên dưới chỉ xem, không sửa trực tiếp được. Dùng nút "Tạo bản nháp mới từ đây" ở trang danh sách phiên bản.</span>
    </div>
    @endif

    <fieldset {{ $isApproved ? 'disabled' : '' }} class="space-y-5">

    {{-- ── Thông tin định danh ─────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-base mb-4">Thông tin brief</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Tiêu đề brief <span class="text-error">*</span></span></label>
                    <input type="text" name="title" value="{{ old('title', $brief?->title) }}"
                           class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                           maxlength="200" placeholder="Vd: Bài viết chọn sữa công thức cho trẻ sơ sinh">
                    @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Người phụ trách</span></label>
                    <select name="assigned_to" class="select select-bordered select-sm w-full">
                        <option value="">— Chưa gán —</option>
                        @foreach(\App\Models\User::orderBy('name')->get(['id', 'name']) as $user)
                        <option value="{{ $user->id }}" {{ (string) old('assigned_to', $brief?->assigned_to) === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- ── SEO & từ khoá ────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-base mb-4">SEO & từ khoá</h2>

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Từ khoá mục tiêu <span class="text-error">*</span></span></label>
                        <input type="text" name="target_keyword" value="{{ old('target_keyword', $snapshot['target_keyword'] ?? '') }}"
                               class="input input-bordered input-sm w-full @error('target_keyword') input-error @enderror" maxlength="150">
                        @error('target_keyword')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Từ khoá phụ</span><span class="label-text-alt text-xs text-base-content/40">phân cách bằng dấu phẩy</span></label>
                        <input type="text" name="secondary_keywords_raw"
                               value="{{ old('secondary_keywords_raw', implode(', ', $snapshot['secondary_keywords'] ?? [])) }}"
                               class="input input-bordered input-sm w-full">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Gợi ý phân loại</span></label>
                        <input type="text" name="suggested_category" value="{{ old('suggested_category', $snapshot['suggested_category'] ?? '') }}"
                               class="input input-bordered input-sm w-full" maxlength="100" placeholder="Vd: Dinh dưỡng">
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Mục đích tìm kiếm</span></label>
                        <select name="search_intent" class="select select-bordered select-sm w-full">
                            @foreach(\Modules\ContentBrief\Enums\SearchIntent::cases() as $intent)
                            <option value="{{ $intent->value }}" {{ old('search_intent', $snapshot['search_intent'] ?? 'informational') === $intent->value ? 'selected' : '' }}>{{ $intent->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Số từ mục tiêu</span></label>
                        <div class="join">
                            <input type="number" name="word_count_min" min="0" placeholder="Min"
                                   value="{{ old('word_count_min', $snapshot['word_count_min'] ?? '') }}"
                                   class="input input-bordered input-sm join-item w-1/2">
                            <input type="number" name="word_count_max" min="0" placeholder="Max"
                                   value="{{ old('word_count_max', $snapshot['word_count_max'] ?? '') }}"
                                   class="input input-bordered input-sm join-item w-1/2 @error('word_count_max') input-error @enderror">
                        </div>
                        @error('word_count_max')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Đối tượng độc giả</span></label>
                    <textarea name="audience_persona" rows="2" class="textarea textarea-bordered textarea-sm w-full">{{ old('audience_persona', $snapshot['audience_persona'] ?? '') }}</textarea>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Tông giọng</span></label>
                    <textarea name="tone_of_voice" rows="2" class="textarea textarea-bordered textarea-sm w-full">{{ old('tone_of_voice', $snapshot['tone_of_voice'] ?? '') }}</textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Gợi ý SEO title</span></label>
                        <input type="text" name="seo_title_suggestion" value="{{ old('seo_title_suggestion', $snapshot['seo_title_suggestion'] ?? '') }}" class="input input-bordered input-sm w-full">
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Gợi ý SEO description</span></label>
                        <input type="text" name="seo_description_suggestion" value="{{ old('seo_description_suggestion', $snapshot['seo_description_suggestion'] ?? '') }}" class="input input-bordered input-sm w-full">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Outline ──────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <div class="flex items-center justify-between mb-4">
                <h2 class="card-title text-base">Outline</h2>
                <button type="button" class="btn btn-ghost btn-xs" @click="outline.push({heading: '', level: 2, notes: ''})">+ Thêm heading</button>
            </div>

            <div class="space-y-3">
                <template x-for="(item, index) in outline" :key="index">
                    <div class="flex gap-2 items-start border border-base-200 rounded-lg p-3">
                        <select :name="`outline[${index}][level]`" x-model.number="item.level" class="select select-bordered select-xs w-20">
                            <option value="2">H2</option>
                            <option value="3">H3</option>
                            <option value="4">H4</option>
                        </select>
                        <div class="flex-1 space-y-1.5">
                            <input type="text" :name="`outline[${index}][heading]`" x-model="item.heading" placeholder="Tiêu đề heading"
                                   class="input input-bordered input-sm w-full">
                            <input type="text" :name="`outline[${index}][notes]`" x-model="item.notes" placeholder="Ghi chú/gợi ý nội dung (không bắt buộc)"
                                   class="input input-bordered input-xs w-full">
                        </div>
                        <button type="button" class="btn btn-ghost btn-xs text-error" @click="outline.splice(index, 1)">Xoá</button>
                    </div>
                </template>
                <p class="text-xs text-base-content/40" x-show="outline.length === 0">Chưa có heading nào.</p>
            </div>
        </div>
    </div>

    {{-- ── Dữ kiện & nguồn tham khảo ────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <div class="flex items-center justify-between mb-4">
                <h2 class="card-title text-base">Dữ kiện & nguồn tham khảo</h2>
                <button type="button" class="btn btn-ghost btn-xs" @click="key_facts.push({fact: '', source_url: ''})">+ Thêm dữ kiện</button>
            </div>
            <div class="space-y-3">
                <template x-for="(item, index) in key_facts" :key="index">
                    <div class="flex gap-2 items-start border border-base-200 rounded-lg p-3">
                        <div class="flex-1 space-y-1.5">
                            <input type="text" :name="`key_facts[${index}][fact]`" x-model="item.fact" placeholder="Dữ kiện cần trích dẫn"
                                   class="input input-bordered input-sm w-full">
                            <input type="text" :name="`key_facts[${index}][source_url]`" x-model="item.source_url" placeholder="URL nguồn (không bắt buộc)"
                                   class="input input-bordered input-xs w-full">
                        </div>
                        <button type="button" class="btn btn-ghost btn-xs text-error" @click="key_facts.splice(index, 1)">Xoá</button>
                    </div>
                </template>
                <p class="text-xs text-base-content/40" x-show="key_facts.length === 0">Chưa có dữ kiện nào.</p>
            </div>
        </div>
    </div>

    {{-- ── Đối thủ cạnh tranh ───────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <div class="flex items-center justify-between mb-4">
                <h2 class="card-title text-base">Đối thủ cạnh tranh</h2>
                <button type="button" class="btn btn-ghost btn-xs" @click="competitor_references.push({url: '', notes: ''})">+ Thêm đối thủ</button>
            </div>
            <div class="space-y-3">
                <template x-for="(item, index) in competitor_references" :key="index">
                    <div class="flex gap-2 items-start border border-base-200 rounded-lg p-3">
                        <div class="flex-1 space-y-1.5">
                            <input type="text" :name="`competitor_references[${index}][url]`" x-model="item.url" placeholder="URL bài viết đối thủ"
                                   class="input input-bordered input-sm w-full">
                            <input type="text" :name="`competitor_references[${index}][notes]`" x-model="item.notes" placeholder="Ghi chú (không bắt buộc)"
                                   class="input input-bordered input-xs w-full">
                        </div>
                        <button type="button" class="btn btn-ghost btn-xs text-error" @click="competitor_references.splice(index, 1)">Xoá</button>
                    </div>
                </template>
                <p class="text-xs text-base-content/40" x-show="competitor_references.length === 0">Chưa có đối thủ nào.</p>
            </div>
        </div>
    </div>

    {{-- ── Tham chiếu liên quan (generic — không gắn module cụ thể, §0) ── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <div class="flex items-center justify-between mb-4">
                <h2 class="card-title text-base">Tham chiếu liên quan</h2>
                <button type="button" class="btn btn-ghost btn-xs" @click="related_references.push({type: '', id: '', label: ''})">+ Thêm tham chiếu</button>
            </div>
            <p class="text-xs text-base-content/40 mb-3">Ghi chú tự do — vd sản phẩm gợi ý, bài viết liên quan... "type"/"id" chỉ mang tính ghi chú, không liên kết cứng tới bảng nào.</p>
            <div class="space-y-3">
                <template x-for="(item, index) in related_references" :key="index">
                    <div class="flex gap-2 items-start border border-base-200 rounded-lg p-3">
                        <input type="text" :name="`related_references[${index}][type]`" x-model="item.type" placeholder="type (vd: product)"
                               class="input input-bordered input-sm w-28">
                        <input type="text" :name="`related_references[${index}][id]`" x-model="item.id" placeholder="id"
                               class="input input-bordered input-sm w-24">
                        <input type="text" :name="`related_references[${index}][label]`" x-model="item.label" placeholder="Nhãn hiển thị"
                               class="input input-bordered input-sm flex-1">
                        <button type="button" class="btn btn-ghost btn-xs text-error" @click="related_references.splice(index, 1)">Xoá</button>
                    </div>
                </template>
                <p class="text-xs text-base-content/40" x-show="related_references.length === 0">Chưa có tham chiếu nào.</p>
            </div>
        </div>
    </div>

    {{-- ── Ghi chú bổ sung ──────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body space-y-4">
            <h2 class="card-title text-base">Ghi chú bổ sung</h2>
            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Gợi ý liên kết nội bộ</span></label>
                <textarea name="internal_linking_notes" rows="2" class="textarea textarea-bordered textarea-sm w-full">{{ old('internal_linking_notes', $snapshot['internal_linking_notes'] ?? '') }}</textarea>
            </div>
            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Chỉ dẫn bổ sung</span></label>
                <textarea name="additional_instructions" rows="3" class="textarea textarea-bordered textarea-sm w-full">{{ old('additional_instructions', $snapshot['additional_instructions'] ?? '') }}</textarea>
            </div>
        </div>
    </div>

    </fieldset>

    <div class="flex gap-2">
        @unless($isApproved)
        <button type="submit" class="btn btn-primary btn-sm">{{ $brief ? 'Lưu thay đổi' : 'Tạo brief' }}</button>
        @endunless
        <a href="{{ route('backend.content_brief.items.index') }}" class="btn btn-ghost btn-sm">Huỷ</a>
        @if($brief)
        <a href="{{ route('backend.content_brief.items.versions', $brief) }}" class="btn btn-ghost btn-sm ml-auto">Lịch sử phiên bản</a>
        @endif
    </div>
</div>

@once
@push('scripts')
<script>
function briefForm(initial) {
    return {
        outline: initial.outline.length ? initial.outline : [],
        key_facts: initial.key_facts,
        competitor_references: initial.competitor_references,
        related_references: initial.related_references,
    };
}
</script>
@endpush
@endonce
