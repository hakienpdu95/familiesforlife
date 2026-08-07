{{-- Dùng chung create/edit — spec/ContentOutlines_Technical_Specification.md §7.2/§7.3.
     $outline = null ở create. Category picker gọi thẳng API của ContentFoundation
     (backend/api/content-foundation/category-foundations/{category}) để hiện ngữ cảnh THAM
     KHẢO — v1 KHÔNG tự auto-fill vào field override (target_audience/content_goal/tone_style),
     người dùng tự đọc gợi ý rồi viết lại theo ý mình (§8 "Ngoài phạm vi" — để mở UX tinh chỉnh sau).

     §4.1 (v1.1) — các field dài (secondary_keywords/target_audience/content_goal/tone_style/
     competitor_urls/additional_notes) + outline_depth đều x-model để tính ƯỚC LƯỢNG số từ prompt
     TRƯỚC khi submit (content-outlines.js `estimatedWordCount`) — giá trị khởi tạo đọc từ
     Alpine state (serverData.fields), KHÔNG còn value="".../nội dung Blade song song để tránh 2
     nguồn sự thật lệch nhau. --}}
<div class="grid grid-cols-1 xl:grid-cols-[1fr_360px] gap-6 items-start"
     x-data="contentOutlineForm({{ Js::from([
        'foundationApiUrlTemplate' => route('backend.api.contentfoundation.category-foundations.show', ['category' => '__UUID__']),
        'initialCategoryUuid' => $outline?->category?->uuid,
        'fields' => [
            'outline_depth' => old('outline_depth', $outline?->outline_depth ?? 'standard'),
            'secondary_keywords' => old('secondary_keywords', $outline?->secondary_keywords),
            'target_audience' => old('target_audience', $outline?->target_audience),
            'content_goal' => old('content_goal', $outline?->content_goal),
            'tone_style' => old('tone_style', $outline?->tone_style),
            'competitor_urls' => old('competitor_urls', $outline?->competitor_urls),
            'additional_notes' => old('additional_notes', $outline?->additional_notes),
        ],
     ]) }})">

    <div class="space-y-5">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Thông tin dàn ý
                </h2>

                <div class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tên gọi (tuỳ chọn)</span>
                            <span class="label-text-alt text-xs text-base-content/40">Để trống = dùng chủ đề làm tên</span>
                        </label>
                        <input type="text" name="label" value="{{ old('label', $outline?->label) }}"
                               class="input input-bordered input-sm w-full @error('label') input-error @enderror"
                               maxlength="200" placeholder="VD: Dàn ý — Cách chọn sữa cho trẻ 1 tuổi">
                        @error('label')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Chủ đề <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="topic" value="{{ old('topic', $outline?->topic) }}"
                               class="input input-bordered input-sm w-full @error('topic') input-error @enderror"
                               maxlength="300" required>
                        @error('topic')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Từ khoá mục tiêu <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="target_keyword" value="{{ old('target_keyword', $outline?->target_keyword) }}"
                                   class="input input-bordered input-sm w-full @error('target_keyword') input-error @enderror"
                                   maxlength="150" required>
                            @error('target_keyword')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ý định tìm kiếm</span>
                            </label>
                            <select name="search_intent" class="select select-sm select-bordered w-full @error('search_intent') select-error @enderror">
                                <option value="" {{ old('search_intent', $outline?->search_intent) === null ? 'selected' : '' }}>— Để AI tự xác định —</option>
                                @foreach(['informational' => 'Học/tìm hiểu thông tin', 'commercial' => 'So sánh/đánh giá lựa chọn', 'transactional' => 'Sẵn sàng hành động/mua', 'navigational' => 'Tìm 1 trang/thương hiệu cụ thể', 'comparison' => 'So sánh trực tiếp A vs B'] as $key => $optLabel)
                                <option value="{{ $key }}" {{ old('search_intent', $outline?->search_intent) === $key ? 'selected' : '' }}>{{ $optLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Từ khoá phụ/liên quan</span>
                        </label>
                        <input type="text" name="secondary_keywords" x-model="fields.secondary_keywords"
                               class="input input-bordered input-sm w-full @error('secondary_keywords') input-error @enderror"
                               maxlength="500" placeholder="Phân tách bằng dấu phẩy">
                        @error('secondary_keywords')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="divider my-1">Ngữ cảnh chuyên mục (tuỳ chọn)</div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Chuyên mục</span>
                            <span class="label-text-alt text-xs text-base-content/40">Kéo ngữ cảnh biên tập từ Content Foundation</span>
                        </label>
                        <select name="post_category_uuid" x-model="categoryUuid" @change="onCategoryChange()"
                                class="select select-sm select-bordered w-full @error('post_category_uuid') select-error @enderror">
                            <option value="">— Không chọn —</option>
                            @foreach($categories as $c)
                            <option value="{{ $c['uuid'] }}" {{ old('post_category_uuid', $outline?->category?->uuid) === $c['uuid'] ? 'selected' : '' }}>
                                {{ str_repeat('— ', $c['depth']) }}{{ $c['name'] }}
                            </option>
                            @endforeach
                        </select>
                        @error('post_category_uuid')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div x-show="foundation" x-cloak class="rounded-lg border border-base-200 bg-base-200/40 p-3 text-xs space-y-1.5">
                        <p class="font-semibold text-base-content/60 uppercase tracking-wide text-[11px]">Ngữ cảnh chuyên mục (tham khảo)</p>
                        <template x-if="foundation?.core_focus"><p><b>Trọng tâm:</b> <span x-text="foundation.core_focus"></span></p></template>
                        <template x-if="foundation?.audience"><p><b>Đối tượng độc giả:</b> <span x-text="foundation.audience"></span></p></template>
                        <template x-if="foundation?.pain_points"><p><b>Khó khăn của độc giả:</b> <span x-text="foundation.pain_points"></span></p></template>
                        <template x-if="foundation?.objections"><p><b>Nghi ngờ/chưa tin:</b> <span x-text="foundation.objections"></span></p></template>
                        <template x-if="foundation?.decision_criteria"><p><b>Tiêu chí lựa chọn:</b> <span x-text="foundation.decision_criteria"></span></p></template>
                        <p class="text-base-content/40 italic">Đọc gợi ý này rồi tự điền/điều chỉnh các field bên dưới theo ý bạn cho ĐÚNG dàn ý này.</p>
                    </div>

                    <div class="divider my-1">Chi tiết cho dàn ý này</div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Đối tượng độc giả</span></label>
                        <input type="text" name="target_audience" x-model="fields.target_audience"
                               class="input input-bordered input-sm w-full @error('target_audience') input-error @enderror" maxlength="500">
                        @error('target_audience')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Mục tiêu bài viết</span></label>
                        <textarea name="content_goal" x-model="fields.content_goal" rows="2" maxlength="2000"
                                  class="textarea textarea-bordered textarea-sm w-full @error('content_goal') textarea-error @enderror"></textarea>
                        @error('content_goal')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">CTA URL</span>
                            <span class="label-text-alt text-xs text-base-content/40">URL thật để chèn vào câu CTA cuối outline/bài viết</span>
                        </label>
                        <input type="url" name="cta_url" value="{{ old('cta_url', $outline?->cta_url) }}"
                               class="input input-bordered input-sm w-full font-mono text-xs @error('cta_url') input-error @enderror"
                               maxlength="500" placeholder="https://...">
                        @error('cta_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Giọng văn</span></label>
                        <textarea name="tone_style" x-model="fields.tone_style" rows="2" maxlength="2000"
                                  class="textarea textarea-bordered textarea-sm w-full @error('tone_style') textarea-error @enderror"></textarea>
                        @error('tone_style')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Nguồn/đối thủ tham khảo</span>
                            <span class="label-text-alt text-xs text-base-content/40">1 URL/dòng — chỉ để AI tự tham khảo, không tự crawl</span>
                        </label>
                        <textarea name="competitor_urls" x-model="fields.competitor_urls" rows="3" maxlength="2000"
                                  class="textarea textarea-bordered textarea-sm w-full font-mono text-xs @error('competitor_urls') textarea-error @enderror"></textarea>
                        @error('competitor_urls')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Số từ mong muốn</span></label>
                            <input type="number" name="desired_word_count" min="100" max="20000"
                                   value="{{ old('desired_word_count', $outline?->desired_word_count) }}"
                                   class="input input-bordered input-sm w-full @error('desired_word_count') input-error @enderror">
                            @error('desired_word_count')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ngôn ngữ đầu ra</span></label>
                            <select name="language" class="select select-sm select-bordered w-full @error('language') select-error @enderror">
                                <option value="vi" {{ old('language', $outline?->language ?? 'vi') === 'vi' ? 'selected' : '' }}>Tiếng Việt</option>
                                <option value="en" {{ old('language', $outline?->language) === 'en' ? 'selected' : '' }}>English</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Độ chi tiết dàn ý</span>
                            <span class="label-text-alt text-xs text-base-content/40">Ảnh hưởng độ dài prompt — xem cảnh báo bên cạnh nút "Sinh"</span>
                        </label>
                        <select name="outline_depth" x-model="fields.outline_depth"
                                class="select select-sm select-bordered w-full @error('outline_depth') select-error @enderror">
                            <option value="brief">Rút gọn (brief) — 5 bước, ít cắt ngữ cảnh nhất</option>
                            <option value="standard">Chuẩn (standard) — 9 bước, khuyến nghị</option>
                            <option value="detailed">Chi tiết (detailed) — mở rộng, không cắt ngữ cảnh chuyên mục</option>
                        </select>
                        @error('outline_depth')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Vai trò nội dung</span>
                            <span class="label-text-alt text-xs text-base-content/40">Định hướng chiều internal link (Pillar/Cluster)</span>
                        </label>
                        <select name="content_role"
                                class="select select-sm select-bordered w-full @error('content_role') select-error @enderror">
                            <option value="" {{ old('content_role', $outline?->content_role) === null ? 'selected' : '' }}>— Chưa xác định —</option>
                            <option value="pillar" {{ old('content_role', $outline?->content_role) === 'pillar' ? 'selected' : '' }}>Trụ cột (pillar) — bài tổng quan, link TỚI các bài cụm</option>
                            <option value="cluster" {{ old('content_role', $outline?->content_role) === 'cluster' ? 'selected' : '' }}>Cụm (cluster) — bài hẹp, link LÊN bài tổng quan + NGANG bài cụm liên quan</option>
                        </select>
                        @error('content_role')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Ghi chú thêm</span></label>
                        <textarea name="additional_notes" x-model="fields.additional_notes" rows="2" maxlength="2000"
                                  class="textarea textarea-bordered textarea-sm w-full @error('additional_notes') textarea-error @enderror"></textarea>
                        @error('additional_notes')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="xl:sticky xl:top-4 space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-3">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                {{-- §4.1 (v1.1) — ước lượng số từ TRƯỚC khi submit, chỉ mang tính tham khảo (không
                     tính chính xác bằng BuildContentOutlinePromptAction::estimateWordCount() ở
                     server — xem show.blade.php để có số liệu thật sau khi sinh). --}}
                <div class="rounded-lg border p-2.5 mb-3 text-xs"
                     :class="estimatedWordCount > 6000 ? 'border-warning bg-warning/10' : 'border-base-200 bg-base-200/40'">
                    <p>Ước lượng: <b x-text="estimatedWordCount.toLocaleString('vi-VN')"></b> từ</p>
                    <p x-show="estimatedWordCount > 6000" class="text-warning-content mt-0.5">
                        ⚠ Có thể vượt ngưỡng 1 số AI xử lý tốt (~6.000 từ) — cân nhắc chọn "Rút gọn" hoặc bớt nguồn tham khảo/ghi chú.
                    </p>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('backend.contentoutlines.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        {{ $outline ? 'Sinh lại prompt' : 'Sinh dàn ý' }}
                    </button>
                </div>
                <p class="text-center text-xs text-base-content/30 mt-2.5"><span class="text-error">*</span> là trường bắt buộc</p>
            </div>
        </div>
    </div>

</div>
