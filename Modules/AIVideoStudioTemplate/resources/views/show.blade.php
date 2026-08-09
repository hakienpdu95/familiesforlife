@extends('layouts.backend')
@section('title', $project->name.' — AI Video Studio')

@section('content')
<div>
    @foreach(['success','error'] as $type)
        @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition.opacity.duration.500ms
             class="alert alert-{{ $type }} mb-4 text-sm">
            <span>{{ session($type) }}</span>
            <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
        </div>
        @endif
    @endforeach

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-base-content">{{ $project->name }}</h1>
                <span class="badge badge-sm {{ match($project->status) {
                    'active' => 'badge-success',
                    'archived' => 'badge-ghost',
                    default => 'badge-warning',
                } }}">{{ $project->status }}</span>
            </div>
            @if($project->description)
            <p class="text-sm text-base-content/50 mt-0.5">{{ $project->description }}</p>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('backend.aivideostudiotemplate.edit', $project) }}" class="btn btn-ghost btn-sm">Sửa project</a>
            <a href="{{ route('backend.aivideostudiotemplate.index') }}" class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Quay lại
            </a>
        </div>
    </div>

    {{-- v1.2 (Hedra "how-to-make-ai-video" Step 1) — Creative Brief: mục tiêu/đối tượng/định dạng
         đã chốt trước khi soạn prompt, hiển thị dạng chỉ đọc (sửa qua "Sửa project"). --}}
    @if($project->objective || $project->target_audience || $project->video_type || $project->core_message || $project->aspect_ratio || $project->resolution)
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
        <div class="card-body">
            <h2 class="card-title text-base">Creative Brief</h2>
            {{-- v1.7 — phân biệt rõ với Anchoring bên dưới: nhóm này được ghi vào prompt và LAN
                 xuống mọi Shot khi sửa; anchoring thì chỉ prefill lúc tạo Shot mới. --}}
            <p class="text-xs text-base-content/50 -mt-1 mb-2">
                Tỷ lệ khung hình · Độ phân giải · Loại video · Khán giả · Thông điệp cốt lõi được ghi vào prompt của mọi Shot; sửa sẽ tự động build lại prompt.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-xs font-medium text-base-content/50 mb-1">Mục tiêu</p>
                    <p class="whitespace-pre-wrap">{{ $project->objective ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-base-content/50 mb-1">Đối tượng khán giả</p>
                    <p class="whitespace-pre-wrap">{{ $project->target_audience ?: '—' }}</p>
                </div>
                {{-- v1.6 (LinkedIn "step-by-step guide creating AI marketing videos prompts").
                     v1.7 — hiển thị nhãn thay vì slug thô (`testimonial`) qua hằng số Model. --}}
                <div>
                    <p class="text-xs font-medium text-base-content/50 mb-1">Loại video</p>
                    <p>{{ $project->videoTypeLabel() ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-base-content/50 mb-1">Thông điệp cốt lõi</p>
                    <p class="whitespace-pre-wrap">{{ $project->core_message ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-base-content/50 mb-1">Tỷ lệ khung hình</p>
                    <p>{{ $project->aspect_ratio ?: '—' }}</p>
                </div>
                {{-- v1.5 (pyxeljam.com) — "Set Video Length, Quality, and File Format Requirements". --}}
                <div>
                    <p class="text-xs font-medium text-base-content/50 mb-1">Độ phân giải</p>
                    <p>{{ $project->resolution ?: '—' }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- spec §8 — Header: field default (anchoring), ghi chú bắt buộc ngay dưới tiêu đề. --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
        <div class="card-body">
            <h2 class="card-title text-base">Anchoring — mô tả cố định</h2>
            <div class="alert alert-info py-2 px-3 text-xs mb-3">
                <span>Áp dụng khi tạo Shot MỚI — sửa ở đây KHÔNG tự động cập nhật các Shot đã tạo trước đó (vì giá trị đã được sao chép vào từng Shot và bạn có thể đã sửa riêng). Khác với Creative Brief phía trên.</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-xs font-medium text-base-content/50 mb-1">Subject mặc định</p>
                    <p class="whitespace-pre-wrap">{{ $project->default_subject ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-base-content/50 mb-1">Style mặc định</p>
                    <p class="whitespace-pre-wrap">{{ $project->default_style ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-base-content/50 mb-1">Constraints mặc định</p>
                    <p class="whitespace-pre-wrap">{{ $project->default_constraints ?: '—' }}</p>
                </div>
            </div>
            @if($project->reference_image_url)
            <div class="mt-3 pt-3 border-t border-base-200">
                <p class="text-xs font-medium text-base-content/50 mb-1">Ảnh tham chiếu (reference image)</p>
                <a href="{{ $project->reference_image_url }}" target="_blank" rel="noopener" class="link link-primary text-sm break-all">{{ $project->reference_image_url }}</a>
            </div>
            @endif
        </div>
    </div>

    {{-- v1.2 (Hedra "how-to-make-ai-video" — Key Prompting Techniques) + v1.3
         (deepreel.com/blog/ai-video-prompts) + v1.4 (byteplus.com) + v1.5 (pyxeljam.com) + v1.8
         (sentx.ai/blog/how-to-write-ai-video-prompts) + v1.9 (veed.io/learn/video-prompts) — nhắc
         nhanh nguyên tắc viết prompt tốt. --}}
    <div class="alert alert-info py-2.5 px-3 text-xs mb-5">
        <span>
            <b>Mẹo viết prompt:</b> mỗi Shot chỉ nên tả 1 cảnh/khoảnh khắc duy nhất, KHÔNG dồn nhiều hành động khác nhau (VD chạy + nhảy + mở cửa + nói) vào 1 shot ngắn ·
            {{-- v1.8 (sentx.ai "Single hero focus") — khác nguyên tắc "1 hành động" ở trên: đây là giới hạn SỐ chủ thể trong khung hình. --}}
            chỉ nên có 1 chủ thể chính + tối đa 1 yếu tố phụ trong khung hình, đừng dồn nhiều người/vật cùng lúc ·
            thay tính từ chung chung ("đẹp", "tốt") bằng mô tả cụ thể ("ánh sáng khuếch tán mềm") ·
            luôn điền Camera (loại cảnh + chuyển động máy) — ảnh hưởng lớn tới chất lượng điện ảnh · Style nên gọi tên phong cách rõ ràng ("phong cách phim tài liệu", "ánh sáng chụp sản phẩm") ·
            đừng bỏ qua Audio (âm thanh môi trường + nhạc nền) — thiếu phần này dễ ra video tĩnh, thiếu sức sống dù hình đẹp ·
            mỗi prompt nên trong khoảng 50-150 từ, đặt thông tin quan trọng nhất ở 20-30 từ đầu (AI đọc trái→phải, ưu tiên phần đầu) ·
            tránh yêu cầu đối lập trong cùng 1 shot (VD "vừa dữ dội vừa yên bình") ·
            Subject/Action/Style nên NÊU RÕ ĐIỀU MUỐN THẤY (khẳng định), còn loại trừ CỤ THỂ ("không motion blur, nét mặt nhân vật") thay vì mô tả mơ hồ ("không mờ") thì dồn hết vào Constraints — tránh trộn 2 kiểu diễn đạt trong cùng 1 field ·
            {{-- v1.9 (veed.io "Platform-Specific Strategies") — câu chung trước đây giờ trỏ tới 2 dòng gợi ý CỤ THỂ mới trong Creative Brief (tự hiện theo Tỷ lệ khung hình/Loại video đã chọn). --}}
            điều chỉnh giọng văn/độ chi tiết theo nền tảng và đối tượng khán giả đã khai ở Creative Brief — xem 2 dòng "Gợi ý theo nền tảng"/"Gợi ý theo loại video" tự hiện ở đó theo Tỷ lệ khung hình/Loại video đã chọn ·
            {{-- v1.8 (sentx.ai "Take approach") — nhịp lấy cảnh (pacing) là 1 lựa chọn riêng, khác Duration (độ dài). --}}
            1 cú máy chậm, liên tục tạo cảm giác khác hẳn chuyển động nhanh, dồn dập — chọn nhịp (pacing) phù hợp tâm trạng của shot, không chỉ chọn số giây ·
            đừng kỳ vọng ra kết quả hoàn hảo ngay lần đầu — nên bắt đầu đơn giản (Subject+Action) rồi thêm dần Style/Camera/Audio, tạo vài biến thể song song thay vì cầu toàn 1 bản, lặp lại 3-4 lần/shot, mỗi lần chỉ sửa 1 vấn đề cụ thể (ghi vào QC bên dưới) ·
            {{-- v1.8 (sentx.ai "Troubleshooting by Slot") — trỏ tới khối mới trong tài liệu xuất. --}}
            output ra sai ý? xem khối "Xử lý sự cố theo triệu chứng" trong tài liệu xuất (nút "Xuất Director Prompt Template" bên dưới) để biết đúng field cần sửa.
        </span>
    </div>

    <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-semibold">Shot ({{ $project->shots->count() }})</h2>
        <button type="button" id="aivsAddShotBtn" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm shot
        </button>
    </div>

    <div id="aivsShotList" data-project-uuid="{{ $project->uuid }}" class="space-y-4 mb-8">
        @foreach($project->shots as $shot)
        <div class="card bg-base-100 shadow-sm border border-base-200 aivs-shot-card"
             data-shot-id="{{ $shot->id }}" data-shot-uuid="{{ $shot->uuid }}" data-project-uuid="{{ $project->uuid }}">
            <div class="card-body space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <input type="text" class="aivs-field input input-bordered input-xs font-medium w-56" data-field="label"
                               value="{{ $shot->label }}" placeholder="VD: Shot 1 — Hook">
                        {{-- v1.3 (deepreel.com/blog/ai-video-prompts) — Duration là 1 thành phần công thức nguồn
                             ("Subject + Style + Camera + Mood + Duration"), tách riêng số vì cộng dồn được tổng
                             thời lượng project (xem CompileProjectDirectorPromptAction). --}}
                        <input type="number" min="1" max="36000" class="aivs-field input input-bordered input-xs w-24" data-field="duration_seconds"
                               value="{{ $shot->duration_seconds }}" placeholder="Giây" title="Thời lượng ước tính (giây)">
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="aivs-save-status text-xs text-base-content/40"></span>
                        <button type="button" class="btn btn-ghost btn-xs aivs-move-up" title="Lên">↑</button>
                        <button type="button" class="btn btn-ghost btn-xs aivs-move-down" title="Xuống">↓</button>
                        <button type="button" class="btn btn-ghost btn-xs text-error aivs-delete-shot">Xoá</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach([
                        'subject' => ['Subject (Chủ thể)', null],
                        'action' => ['Action (Hành động)', null],
                        {{-- v1.9 (veed.io) — field CUỐI trong nhóm 5 field cốt lõi còn thiếu ví dụ (Camera/
                             Style đã có từ v1.8); bổ sung "spatial descriptors" (không gian) + "temporal
                             cues" (thời điểm) — nguồn nhấn mạnh cần mô tả CẢ HAI, không chỉ 1 trong 2. --}}
                        'environment' => ['Environment (Bối cảnh)', 'VD: hẻm nhỏ về đêm, đồng cỏ rộng mở, studio trong nhà, hang động dưới nước + thời điểm: lúc rạng đông, chiều tà, sáng mùa đông, giờ vàng (golden hour) — mô tả cả không gian VÀ thời điểm'],
                        {{-- v1.8 (sentx.ai) — 2 field quan trọng nhất theo callout "Mẹo viết prompt" phía trên
                             lại chưa có placeholder ví dụ nào; bổ sung vốn từ vựng cỡ cảnh/góc máy/chuyển
                             động máy (Camera) và cách gọi tên nguồn sáng + thời điểm trong ngày (Style —
                             module không có field lighting riêng, xem ghi chú ở PRE_GENERATION_CHECKLIST). --}}
                        'camera' => ['Camera (Góc máy)', 'VD: cận cảnh (close-up), góc thấp (low angle), máy đẩy chậm vào chủ thể (slow push-in) — cỡ cảnh: toàn/trung/cận/đại cận; chuyển động: lia ngang (pan), tracking, tĩnh (locked-off), cầm tay'],
                        {{-- v1.9 (veed.io "Artistic style references") — thêm ví dụ phong cách hoạt hình/
                             nghệ thuật cạnh ví dụ ánh sáng/thời điểm đã có từ v1.8. --}}
                        'style' => ['Style (Phong cách)', 'VD: phong cách phim tài liệu, ánh sáng chụp sản phẩm, nắng vàng hoàng hôn, ánh trăng lạnh (không chỉ "đẹp") — gọi tên rõ nguồn sáng + thời điểm trong ngày nếu ánh sáng quan trọng với cảnh; hoặc phong cách hoạt hình/nghệ thuật (anime, claymation, tranh màu nước, hiệu ứng VHS cũ) nếu cần bản sắc hình ảnh riêng biệt'],
                        'mood' => ['Mood (Tâm trạng)', 'VD: năng lượng cao, trầm lắng, chuyên nghiệp, vui tươi'],
                        'audio_direction' => ['Audio (Âm thanh/Nhạc nền)', 'VD: tiếng bước chân trên sỏi, gió thổi xa xa; nhạc: điện tử tối giản, tempo trung bình — KHÁC lời thoại bên dưới'],
                        'script_line' => ['Lời thoại (Script line)', null],
                        'cta_text' => ['Call-to-action (CTA)', 'VD: "Mua ngay - Giảm 20% hôm nay" hoặc text nút/đếm ngược hiển thị trên màn hình'],
                        'constraints' => ['Constraints (Ràng buộc)', 'VD cụ thể (negative prompt): "không motion blur, nét mặt nhân vật, không lens flare" — tránh mô tả chung chung như "không mờ"'],
                    ] as $field => [$fieldLabel, $fieldPlaceholder])
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">{{ $fieldLabel }}</span></label>
                        <textarea rows="2" class="aivs-field textarea textarea-bordered textarea-xs w-full" data-field="{{ $field }}"
                                  placeholder="{{ $fieldPlaceholder }}">{{ $shot->{$field} }}</textarea>
                    </div>
                    @endforeach
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1 flex items-center justify-between">
                        <span class="label-text text-xs font-medium">Compiled prompt (tự sinh)</span>
                        {{-- v1.3 — deepreel.com khuyến nghị 50-150 từ/prompt; cảnh báo nhẹ nếu lệch khoảng này. --}}
                        <span class="aivs-word-count text-xs text-base-content/40"></span>
                    </label>
                    <div class="flex gap-2 items-start">
                        <textarea readonly rows="4" class="aivs-compiled textarea textarea-bordered textarea-xs w-full font-mono">{{ $shot->compiled_prompt }}</textarea>
                        <button type="button" class="btn btn-outline btn-xs shrink-0 aivs-copy-compiled">Copy</button>
                    </div>
                </div>

                {{-- v1.2 (Hedra Model Selection Criteria + Step 4 "test alternative models") — ghi
                     lại tool/model AI ngoài đã dùng cho shot này, KHÔNG đưa vào compiled_prompt. --}}
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Model/Tool đã dùng</span></label>
                    <input type="text" class="aivs-field input input-bordered input-xs w-full" data-field="model_tool"
                           value="{{ $shot->model_tool }}" placeholder="VD: Kling 1.6 - motion trung bình">
                </div>

                {{-- v1.4 (byteplus.com) — link ảnh/video/audio tham chiếu bổ sung riêng cho shot này
                     (VD video mẫu cho chuyển động máy, track nhạc mẫu cho mood), ngoài ảnh anchor
                     chung của Project (§ Anchoring). Metadata — KHÔNG đưa vào compiled_prompt. --}}
                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Tài liệu tham chiếu bổ sung</span></label>
                    <input type="text" class="aivs-field input input-bordered input-xs w-full" data-field="reference_assets"
                           value="{{ $shot->reference_assets }}" placeholder="VD: link video mẫu chuyển động máy, link track nhạc mẫu cho mood">
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Kết quả AI (dán link/text)</span></label>
                    <div class="flex gap-2 items-start">
                        <textarea rows="2" class="aivs-ai-result textarea textarea-bordered textarea-xs w-full">{{ $shot->ai_result }}</textarea>
                        <button type="button" class="btn btn-outline btn-xs shrink-0 aivs-save-result">Lưu kết quả</button>
                    </div>
                </div>

                {{-- v1.2 (Hedra Step 3 "Generate First Draft" — checklist đánh giá output) + v1.3
                     (deepreel.com — quy trình lặp: ghi lại 3 điểm được + 1 điểm cần sửa mỗi lần, rồi
                     sửa đúng 1 vấn đề đó trước khi tạo lại, lặp 3-4 lần/shot). --}}
                <div class="form-control">
                    <label class="label py-0 pb-1">
                        <span class="label-text text-xs font-medium">Ghi chú đánh giá (QC)</span>
                        <span class="label-text-alt text-xs text-base-content/40">chủ thể · chuyển động · góc máy · phong cách · artifact</span>
                    </label>
                    <textarea rows="2" class="aivs-field textarea textarea-bordered textarea-xs w-full" data-field="qc_notes"
                              placeholder="VD: Đúng nhân vật, ánh sáng đẹp, chuyển động mượt (3 điểm được) — góc máy hơi xa, lần sau zoom cận hơn (1 điểm cần sửa)">{{ $shot->qc_notes }}</textarea>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- spec §6/§8 — xuất Director Prompt Template tổng hợp: tải .md + copy toàn bộ. --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <div class="flex items-center justify-between mb-2">
                <h2 class="card-title text-base">Director Prompt Template — tổng hợp</h2>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-outline btn-sm" id="aivsCopyAllBtn" onclick="aivsCopy('aivsCompiledAll', this)">Copy toàn bộ</button>
                    <a href="{{ route('backend.aivideostudiotemplate.export', $project) }}" class="btn btn-primary btn-sm">Xuất file .md</a>
                </div>
            </div>
            <textarea readonly rows="10" id="aivsCompiledAll" class="textarea textarea-bordered textarea-xs w-full font-mono">{{ $compiledDocument }}</textarea>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>window.aivsApiBaseUrl = @json(url('/backend/api/ai-video-studio'));</script>
@vite(['Modules/AIVideoStudioTemplate/resources/assets/js/aivideostudiotemplate.js'], 'build/backend')
@endpush
