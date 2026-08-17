{{-- Dùng chung giữa create.blade.php và edit.blade.php — render field theo framework đã chọn.
     Cần Alpine scope cha (component `promptGenerator`) cung cấp sẵn: selectedFramework, values,
     focusedKey, categoryUuid, editorialBlock, editorialHasFoundation, loadingEditorial,
     loadEditorialContext(), isFieldThin(), assembledPreview, estimatedWordCount.

     Tách ra đây để 2 trang không lặp lại cùng 1 khối markup và tự trôi lệch nhau qua thời gian khi
     1 trong 2 được sửa mà quên trang còn lại.

     Biến Blade cần có: $categories (từ ListCategoryFoundationsAction, withFoundationDetails:false),
     $selectedCategoryUuid (uuid đã lưu ở trang edit, null ở trang create). --}}

{{-- ── Ngữ cảnh biên tập ─────────────────────────────────────────────────────────────────────
     Đặt TRƯỚC các field framework vì đây là thứ định hình cách điền chúng: biết độc giả là ai rồi
     mới viết Audience/Tone cho đúng. Nhãn nêu LỢI ÍCH thay vì gọi là "tuỳ chọn nâng cao" — mục
     optional mà không nói rõ mất gì khi bỏ qua thì không ai điền (khuôn CoreIdeaExtractor). --}}
<div class="rounded-lg border border-base-200 bg-base-200/30 p-3 space-y-2">
    <div class="form-control">
        <label class="label py-0 pb-1">
            <span class="label-text font-medium text-sm">Chuyên mục nội dung</span>
            <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn — bỏ qua thì prompt không có thông tin gì về độc giả thật của trang</span>
        </label>
        <select name="post_category_uuid" x-model="categoryUuid" @change="loadEditorialContext()"
                class="select select-sm select-bordered w-full @error('post_category_uuid') select-error @enderror">
            {{-- KHÔNG dùng @selected ở đây: `x-model="categoryUuid"` sẽ ghi đè thuộc tính selected
                 của HTML ngay khi Alpine khởi tạo, nên để cả 2 là có 2 nguồn sự thật mà chỉ 1 cái
                 thắng — giá trị ban đầu truyền qua serverData.initialCategoryUuid (đã tính cả
                 old() cho trường hợp validate lỗi quay lại form). --}}
            <option value="">— Không gắn chuyên mục —</option>
            @foreach($categories as $c)
            <option value="{{ $c['uuid'] }}">{{ str_repeat('— ', $c['depth']) }}{{ $c['name'] }}</option>
            @endforeach
        </select>
        @error('post_category_uuid')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
    </div>

    <p x-show="loadingEditorial" x-cloak class="text-xs text-base-content/50">Đang tải ngữ cảnh biên tập...</p>

    {{-- Nói rõ payload vô hình vừa được gắn thêm: người dùng phải biết cái gì đang được chèn vào
         prompt mà họ không nhìn thấy trong form — đây là điều kiện để tin công cụ. --}}
    <div x-show="!loadingEditorial && editorialHasFoundation" x-cloak
         class="rounded border border-success/30 bg-success/5 px-2.5 py-2 text-xs">
        <p class="text-success-content/90">
            ✓ Ngữ cảnh biên tập của chuyên mục này (độc giả, nỗi đau, nghi ngờ, tiêu chí chọn, giọng văn)
            <b>sẽ được chèn vào đầu prompt</b> — bạn không cần gõ lại vào các ô bên dưới.
        </p>
        <p class="text-base-content/50 mt-1">Xem nguyên văn ở khung "Xem trước prompt" cuối trang.</p>
    </div>

    <div x-show="!loadingEditorial && categoryUuid && !editorialHasFoundation" x-cloak
         class="rounded border border-warning/40 bg-warning/10 px-2.5 py-2 text-xs">
        Chuyên mục này <b>chưa có ngữ cảnh biên tập</b> nào được soạn — prompt sẽ không có khối bối cảnh.
        Soạn 1 lần tại <a href="{{ route('backend.contentfoundation.index') }}" class="link link-primary" target="_blank" rel="noopener">Content Foundation</a>,
        sau đó mọi prompt gắn chuyên mục này đều dùng lại được.
    </div>
</div>

{{-- Dải cấu trúc framework — thể hiện các field LIÊN KẾT THEO THỨ TỰ với nhau (đúng bản chất
     framework), không phải danh sách rời rạc. Field đang gõ được highlight để người dùng luôn
     biết mình đang ở khối nào trong chuỗi. --}}
<div class="flex flex-wrap items-center justify-between gap-2">
    <div class="flex flex-wrap items-center gap-1">
        <template x-for="(field, idx) in selectedFramework.fields" :key="'chip-' + field.key">
            <div class="flex items-center gap-1">
                <span class="text-base-content/20 text-xs" x-show="idx > 0">→</span>
                <span class="badge badge-xs font-mono transition-colors"
                      :class="focusedKey === field.key ? 'badge-primary' : 'badge-ghost text-base-content/40'"
                      x-text="field.label"></span>
            </div>
        </template>
    </div>

    {{-- spec/AIIdeaMatrixGenerator.md §2.2 — nút "Ngẫu nhiên" GENERIC cho mọi framework có ≥1 field
         `select` (không riêng `heritage_idea_matrix`), chọn ngẫu nhiên 1 khoá hợp lệ cho MỖI field
         select cùng lúc, thuần JS phía client, không endpoint mới. --}}
    <button type="button" x-show="hasSelectFields" @click="randomizeSelectFields()"
            class="btn btn-outline btn-xs gap-1 shrink-0">
        🎲 Ngẫu nhiên
    </button>
</div>

{{-- spec/AIIdeaMatrixGenerator.md §2.9 (v2.4) — khối "Ví dụ tham khảo", đặt NGAY TRÊN field đầu
     tiên (Thông điệp cốt lõi ở heritage_idea_matrix) theo đúng yêu cầu: người dùng thấy được TOÀN
     CẢNH 1 ví dụ hoàn chỉnh trước khi điền, thay vì tự ghép lại từ placeholder rải rác từng ô. Mở
     sẵn (không phải `<details>` đóng) — đây là thứ CẦN thấy TRƯỚC khi điền, không phải tra cứu thêm
     khi cần (khác khối "Xem trước prompt" ở cuối, vốn chỉ hữu ích SAU khi đã có ít nhất vài field).
     Generic cho MỌI framework — `exampleRows` rỗng thì khối này tự ẩn (framework nào không có
     `example` đầy đủ, hiếm khi xảy ra vì đã có test bao phủ). --}}
<div x-show="exampleRows.length > 0" x-cloak
     class="rounded-lg border border-info/30 bg-info/5 p-3 space-y-1.5">
    <p class="text-xs font-semibold text-info-content/90 flex items-center gap-1.5">
        📋 Ví dụ tham khảo — điền thử nội dung nào để hình dung trước khi bạn tự điền:
    </p>
    <template x-for="row in exampleRows" :key="row.label">
        <p class="text-xs leading-snug">
            <span class="font-medium text-base-content/70" x-text="row.label + ':'"></span>
            <span class="text-base-content/60" x-text="row.value"></span>
        </p>
    </template>
</div>

{{-- Lưới 2 cột cho field ngắn (text) — field textarea chiếm trọn hàng. Không cần metadata nhóm
     riêng: field 'text' của cùng 1 framework thường là các thuộc tính ngắn cùng bản chất (vd
     Style/Tone trong CO-STAR) nên tự nhiên nằm cạnh nhau theo đúng thứ tự khai báo. --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
    <template x-for="field in selectedFramework.fields" :key="field.key">
        <div class="form-control" :class="field.type === 'textarea' ? 'md:col-span-2' : ''">
            <label class="label py-0 pb-0.5">
                <span class="label-text font-medium">
                    <span x-text="field.label"></span>
                    <span x-show="field.required" class="text-error">&nbsp;*</span>
                </span>
            </label>
            {{-- Hint: câu giải thích/gợi ý hiển thị THƯỜNG TRỰC, không phải placeholder — placeholder
                 biến mất ngay khi gõ ký tự đầu tiên nên không thể là nơi mang hướng dẫn cách điền. --}}
            <p class="text-xs text-base-content/45 mb-1 leading-snug" x-text="field.hint"></p>

            {{-- Tip: chỉ có ở field ẢNH HƯỞNG NHIỀU NHẤT đến chất lượng prompt trong framework này
                 (1 field/framework, xem config §"tip") — nêu hệ quả nếu điền mơ hồ + 1 phép thử cụ
                 thể để tự kiểm tra câu trả lời, cùng tinh thần "phép thử pass/fail" đã dùng ở
                 CoreIdeaExtractor. Tách hẳn kiểu dáng khỏi hint để không đánh đồng field nào cũng
                 quan trọng như nhau. --}}
            <p class="text-xs text-info/80 bg-info/5 border-l-2 border-info/30 pl-2 py-1 mb-1 leading-snug"
               x-show="field.tip">
                <span class="font-medium">💡</span> <span x-text="field.tip"></span>
            </p>

            <textarea x-show="field.type === 'textarea'" x-model="values[field.key]" rows="3"
                      @focus="focusedKey = field.key" @blur="focusedKey = null"
                      :placeholder="'VD: ' + (selectedFramework.example[field.key] || '')"
                      class="textarea textarea-bordered textarea-sm w-full placeholder:text-base-content/30"></textarea>
            <input x-show="field.type === 'text'" x-model="values[field.key]" type="text"
                   @focus="focusedKey = field.key" @blur="focusedKey = null"
                   :placeholder="'VD: ' + (selectedFramework.example[field.key] || '')"
                   class="input input-bordered input-sm w-full placeholder:text-base-content/30">
            {{-- spec/AIIdeaMatrixGenerator.md §2.1 — field type MỚI `select`, dùng chung cho mọi
                 framework (không riêng `heritage_idea_matrix`) khi cần giới hạn lựa chọn thay vì
                 text tự do. Giá trị lưu/gửi là KHOÁ (`field.options` khoá => nhãn) — dịch sang nhãn
                 khi render ở server (RenderPromptFromFrameworkAction), không phải ở đây.

                 §2.5 (v2.1) — KHÔNG x-model trực tiếp: field có `allow_custom` thêm lựa chọn
                 sentinel `__custom__` mở ô text tự nhập bên dưới, nên giá trị hiển thị của <select>
                 (selectValueFor) tách khỏi giá trị thật (values[field.key] — khoá HOẶC text tự do);
                 mọi ghi đều đi qua onSelectChange để 2 trạng thái không giẫm nhau. --}}
            <select x-show="field.type === 'select'"
                    :value="selectValueFor(field)" @change="onSelectChange(field, $event.target.value)"
                    @focus="focusedKey = field.key" @blur="focusedKey = null"
                    class="select select-bordered select-sm w-full">
                <option value="">— Chưa chọn —</option>
                <template x-for="[optKey, optLabel] in Object.entries(field.options || {})" :key="optKey">
                    <option :value="optKey" x-text="optLabel"></option>
                </template>
                <template x-if="field.allow_custom">
                    <option value="__custom__">✏️ Khác (tự nhập)…</option>
                </template>
            </select>
            <input x-show="field.type === 'select' && isCustomSelect(field)" x-model="values[field.key]" type="text"
                   @focus="focusedKey = field.key" @blur="focusedKey = null"
                   :maxlength="field.custom_max_length || 150"
                   {{-- spec/AIIdeaMatrixGenerator.md §2.8 (v2.3) — `custom_placeholder` RIÊNG từng
                        field (nếu config có khai) thay vì 1 chuỗi generic dùng chung cho mọi field
                        allow_custom — ví dụ "Trẻ sợ đi khám răng" hợp với Tình huống Gia đình nhưng
                        SAI ngữ cảnh nếu hiện y hệt cho Yếu tố Di sản/Sản phẩm. --}}
                   :placeholder="field.custom_placeholder || '1 cụm từ ngắn — không phải cả đoạn quảng cáo/thông cáo báo chí'"
                   class="input input-bordered input-sm w-full mt-1.5 placeholder:text-base-content/30">
            {{-- spec/AIIdeaMatrixGenerator.md §2.6 (v2.2) — cảnh báo mềm, cùng tinh thần isFieldThin
                 (không chặn submit, `maxlength` HTML ở trên mới là chặn cứng): nhắc lại field này là
                 CỤM TỪ, không phải đoạn văn, ngay khi người dùng gõ gần chạm giới hạn. --}}
            <p class="text-xs text-warning mt-1" x-show="isCustomFieldNearLimit(field)">
                Gần chạm giới hạn <span x-text="field.custom_max_length || 150"></span> ký tự — ô này nên là 1 cụm từ ngắn, không phải đoạn văn/nội dung quảng cáo đầy đủ. Nội dung dài hơn nên đưa vào field textarea khác của mẫu này (nếu có).
            </p>

            {{-- Gợi ý chất lượng, KHÔNG chặn submit — chỉ nhắc field bắt buộc đã điền nhưng còn
                 ngắn/có khả năng chung chung (xem isFieldThin trong prompt-framework-studio.js). --}}
            <p class="text-xs text-warning mt-1" x-show="isFieldThin(field)">
                Có vẻ còn ngắn — thử cụ thể hơn để AI hiểu đúng ý bạn?
            </p>
        </div>
    </template>
</div>

{{-- input ẩn để submit form thường (không AJAX) — 2 widget (textarea/input) cùng field chỉ hiện
     1 theo type, tránh trùng name giữa chúng bằng cách KHÔNG đặt name trực tiếp lên input/textarea
     hiển thị ở trên, mà mirror sang đây theo đúng field.key hiện hành. --}}
<template x-for="field in selectedFramework.fields" :key="'hidden-' + field.key">
    <input type="hidden" :name="`field_values[${field.key}]`" :value="values[field.key]">
</template>

@error('field_values')<p class="text-xs text-error">{{ $message }}</p>@enderror

{{-- Xem trước prompt được ghép real-time — WYSIWYG đúng logic dựng khối phía server (xem
     assembledPreview trong prompt-framework-studio.js), để thấy ngay phần nào còn trống/sơ sài
     TRƯỚC khi bấm sinh, thay vì phải submit xong mới biết cần sửa gì. --}}
<div class="collapse collapse-arrow bg-base-200/50 border border-base-200 rounded-lg">
    <input type="checkbox" checked>
    <div class="collapse-title text-xs font-medium py-2 min-h-0 pr-10">
        Xem trước prompt sẽ được ghép
        <span class="text-base-content/40 font-normal">— ước lượng <span x-text="estimatedWordCount.toLocaleString('vi-VN')"></span> từ</span>
    </div>
    <div class="collapse-content">
        {{-- Cảnh báo độ dài: CHỈ cảnh báo, không tự cắt — cùng quyết định đã ghi ở
             spec/CoreIdeaExtractor.md §12.5 ("cắt nội dung phá mất chiều sâu là cái mất CHẮC CHẮN
             100%, lợi ích chỉ là suy đoán"). Ngưỡng 6.000 từ giống ContentOutlines. --}}
        <p x-show="estimatedWordCount > 6000" x-cloak class="text-xs text-warning mb-2">
            ⚠ Prompt khá dài (hơn 6.000 từ) — một số AI xử lý kém đi ở mức này. Cân nhắc rút gọn phần dán thêm hoặc tách thành nhiều lượt.
        </p>
        <pre class="text-xs whitespace-pre-wrap font-mono text-base-content/70 leading-relaxed" x-text="assembledPreview"></pre>
    </div>
</div>
