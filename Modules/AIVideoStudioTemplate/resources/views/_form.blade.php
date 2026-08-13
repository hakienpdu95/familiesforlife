{{-- Dùng chung create/edit — spec/AIVideoStudioTemplate_Technical_Specification.md §8.
     `status` chỉ hiện khi sửa (create luôn khởi tạo 'draft' theo default của Model). --}}
<div class="card bg-base-100 shadow-sm border border-base-200 max-w-2xl">
    <div class="card-body space-y-4">

        <div class="form-control">
            <label class="label py-0 pb-1.5">
                <span class="label-text font-medium">Tên project <span class="text-error">*</span></span>
            </label>
            <input type="text" name="name" value="{{ old('name', $project?->name) }}"
                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                   placeholder="VD: Review bỉm" maxlength="200">
            @error('name')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Mô tả</span></label>
            <textarea name="description" rows="2"
                      class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                      placeholder="Ghi chú ngắn về chủ đề/mục tiêu video này">{{ old('description', $project?->description) }}</textarea>
        </div>

        @if($project)
        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Trạng thái</span></label>
            <select name="status" class="select select-bordered select-sm w-full max-w-xs">
                @foreach(\Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject::STATUSES as $key => $label)
                <option value="{{ $key }}" @selected(old('status', $project->status) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- UI/UX v2 — cùng ngôn ngữ hình ảnh (badge số bước) với show.blade.php, để người dùng quen
             mắt xuyên suốt từ lúc tạo/sửa project tới lúc soạn kịch bản. --}}
        <h3 class="text-sm font-semibold flex items-center gap-2 pt-1"><span class="badge badge-primary badge-sm">1</span> Mục tiêu &amp; Định dạng <span class="font-normal text-xs text-base-content/40">— chốt trước khi soạn prompt</span></h3>

        {{-- Hedra "how-to-make-ai-video" Step 1: chốt mục tiêu/đối tượng/định dạng TRƯỚC khi generate
             — bỏ qua bước này là sai lầm phổ biến nhất bài viết liệt kê. Không bắt buộc ở v1. --}}
        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Mục tiêu chính</span></label>
            <textarea name="objective" rows="2" class="textarea textarea-bordered textarea-sm w-full"
                      placeholder="VD: Tăng nhận diện thương hiệu / thúc đẩy chốt đơn ngay trong video">{{ old('objective', $project?->objective) }}</textarea>
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Đối tượng khán giả</span></label>
            <textarea name="target_audience" rows="2" class="textarea textarea-bordered textarea-sm w-full"
                      placeholder="VD: Mẹ bỉm sữa 25-35 tuổi, quan tâm sản phẩm an toàn cho da bé">{{ old('target_audience', $project?->target_audience) }}</textarea>
        </div>

        {{-- v1.6 (LinkedIn "step-by-step guide creating AI marketing videos prompts") — Step 1
             "Define Objectives": tách loại video + thông điệp cốt lõi khỏi Mục tiêu/Đối tượng chung. --}}
        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Loại video</span></label>
            <select name="video_type" class="select select-bordered select-sm w-full max-w-xs">
                <option value="">— Chưa chọn —</option>
                @foreach(\Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject::VIDEO_TYPES as $key => $label)
                <option value="{{ $key }}" @selected(old('video_type', $project?->video_type) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- v1.16 (tulsainternetmarketingservice.com/blog/video-marketing-formulas) — công thức KỂ
             CHUYỆN (narrative arc), trục khác với Loại video (nội dung) ở trên: 1 video product_demo
             vẫn có thể dùng cấu trúc PSA hoặc Hook-Value-CTA tuỳ ý đồ. --}}
        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Công thức kịch bản</span></label>
            <select name="video_formula" class="select select-bordered select-sm w-full max-w-xs">
                <option value="">— Chưa chọn —</option>
                @foreach(\Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject::VIDEO_FORMULAS as $key => $label)
                <option value="{{ $key }}" @selected(old('video_formula', $project?->video_formula) === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="text-xs text-base-content/40 mt-1">Cấu trúc trình tự kể chuyện cho toàn bộ shot của project — VD PSA: shot đầu nêu vấn đề, shot giữa cho thấy giải pháp, shot cuối là CTA.</p>
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Thông điệp cốt lõi</span></label>
            <textarea name="core_message" rows="2" class="textarea textarea-bordered textarea-sm w-full"
                      placeholder="VD: Tiết kiệm 40% thời gian nấu ăn — 1 câu cụ thể khán giả sẽ nhớ, khác với Mục tiêu (mục tiêu kinh doanh chung)">{{ old('core_message', $project?->core_message) }}</textarea>
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Tỷ lệ khung hình</span></label>
            <select name="aspect_ratio" class="select select-bordered select-sm w-full max-w-xs">
                <option value="">— Chưa chọn —</option>
                @foreach(\Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject::ASPECT_RATIOS as $key => $label)
                <option value="{{ $key }}" @selected(old('aspect_ratio', $project?->aspect_ratio) === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="text-xs text-base-content/40 mt-1">Chọn trước để khớp kênh phân phối — tránh phải quay/dựng lại vì sai định dạng. Giá trị này được ghi thẳng vào prompt của mọi Shot (§3.1).</p>
        </div>

        {{-- v1.5 (pyxeljam.com) — "Set Video Length, Quality, and File Format Requirements": phần
             Quality/độ phân giải, cạnh Tỷ lệ khung hình. Video Length đã có qua duration_seconds (v1.3). --}}
        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Độ phân giải</span></label>
            <select name="resolution" class="select select-bordered select-sm w-full max-w-xs">
                <option value="">— Chưa chọn —</option>
                @foreach(\Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject::RESOLUTIONS as $key => $label)
                <option value="{{ $key }}" @selected(old('resolution', $project?->resolution) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- v1.7 — 5 field bối cảnh dưới đây được RENDER vào compiled_prompt của mọi Shot, nên sửa
             chúng sẽ build lại prompt toàn bộ Shot (khác anchoring bên dưới — xem UpdateProjectAction). --}}
        <div class="alert alert-warning py-2 px-3 text-xs">
            <span>Loại video · Công thức kịch bản · Thông điệp cốt lõi · Khán giả · Tỷ lệ khung hình · Độ phân giải được ghi vào prompt của MỌI Shot — sửa ở đây sẽ tự động cập nhật lại prompt đã sinh.</span>
        </div>

        <h3 class="text-sm font-semibold flex items-center gap-2 pt-2"><span class="badge badge-primary badge-sm">2</span> Ảnh tham chiếu <span class="badge badge-ghost badge-sm font-normal">Không bắt buộc</span></h3>

        {{-- spec §8 — ghi chú bắt buộc hiển thị ngay dưới tiêu đề anchoring, tránh hiểu nhầm rằng
             sửa ở đây sẽ cập nhật lại các Shot đã tạo trước đó. --}}
        <div class="alert alert-info py-2 px-3 text-xs">
            <span>Áp dụng khi tạo Shot MỚI — sửa ở đây KHÔNG tự động cập nhật các Shot đã tạo trước đó.</span>
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Subject mặc định</span></label>
            <textarea name="default_subject" rows="2" class="textarea textarea-bordered textarea-sm w-full"
                      placeholder="VD: Người mẹ trẻ khoảng 28 tuổi, tóc buộc gọn, áo thun trắng — không dùng mô tả chung chung như 'một người phụ nữ'">{{ old('default_subject', $project?->default_subject) }}</textarea>
        </div>

        {{-- v1.13 (phản hồi người dùng) — bỏ hẳn input `reference_image_url` (link ảnh) khỏi UI, chỉ
             còn 1 ô text ngắn tự gõ. KHÔNG xoá cột/dữ liệu backend — cùng nguyên tắc "bỏ UI, giữ data"
             đã áp dụng cho model_tool/qc_notes/ai_result trước đó. v1.11 (phản hồi người dùng — đã tự
             chuẩn bị sẵn ảnh KOL + sản phẩm ở NGOÀI tool) — chỉ cần 1 ô text NGẮN tự gõ để nhớ lại
             ngữ cảnh của ảnh đã chuẩn bị sẵn — KHÔNG tự sinh, KHÔNG vào compiled_prompt của Shot nào. --}}
        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Mô tả ngữ cảnh ảnh tham chiếu</span></label>
            <textarea name="reference_context_prompt" rows="2" maxlength="300" class="textarea textarea-bordered textarea-sm w-full"
                      placeholder="VD: KOL mặc áo trắng, cầm sản phẩm trước ngực, ánh sáng studio, nền trắng">{{ old('reference_context_prompt', $project?->reference_context_prompt) }}</textarea>
            <p class="text-xs text-base-content/40 mt-1">Ghi ngắn gọn bối cảnh của ảnh tham chiếu bạn đã chuẩn bị sẵn (KOL + sản phẩm) — để bạn tự nhớ lại khi viết prompt cho từng shot.</p>
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Style mặc định</span></label>
            <textarea name="default_style" rows="2" class="textarea textarea-bordered textarea-sm w-full"
                      placeholder="VD: Cinematic, tone màu vàng ấm, bảng màu thương hiệu #F5A623, depth of field nông — bao gồm cả bảng màu/tone giọng thương hiệu, không chỉ 'đẹp/chuyên nghiệp'">{{ old('default_style', $project?->default_style) }}</textarea>
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Constraints mặc định</span></label>
            <textarea name="default_constraints" rows="2" class="textarea textarea-bordered textarea-sm w-full"
                      placeholder="VD: giọng đọc nữ miền Nam, không chèn text. Kỹ thuật negative prompt: nêu loại trừ CỤ THỂ ('không motion blur, nét mặt nhân vật, không lens flare') thay vì mơ hồ ('không mờ')">{{ old('default_constraints', $project?->default_constraints) }}</textarea>
        </div>

        <div class="card-actions justify-end pt-2">
            <button type="submit" class="btn btn-primary btn-sm">{{ $project ? 'Lưu' : 'Tạo project' }}</button>
        </div>

    </div>
</div>
