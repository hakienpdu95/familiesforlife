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

    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
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

    {{-- v1.13 (phản hồi người dùng) — bỏ hẳn thanh quy trình 4 bước (UI/UX v2, content24.ai) sau khi
         phát hiện logic "active" không nhất quán (step 1 luôn active bất kể dữ liệu, step 2-4 lại theo
         điều kiện) và người dùng thấy không hữu ích. Vẫn giữ badge số + id="step-N" trên từng card bên
         dưới (label rõ ràng "Bước N", không phụ thuộc gì vào thanh progress đã bỏ). --}}

    {{-- v1.2 (Hedra "how-to-make-ai-video" Step 1) — Creative Brief: mục tiêu/đối tượng/định dạng
         đã chốt trước khi soạn prompt, hiển thị dạng chỉ đọc (sửa qua "Sửa project"). --}}
    @if($project->objective || $project->target_audience || $project->video_type || $project->core_message || $project->aspect_ratio || $project->resolution)
    <div id="step-1" class="card bg-base-100 shadow-sm border border-base-200 mb-5 scroll-mt-4">
        <div class="card-body">
            <h2 class="card-title text-base"><span class="badge badge-primary badge-sm">1</span> Mục tiêu &amp; Định dạng</h2>
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
    <div id="step-2" class="card bg-base-100 shadow-sm border border-base-200 mb-5 scroll-mt-4">
        <div class="card-body">
            <h2 class="card-title text-base">
                <span class="badge badge-primary badge-sm">2</span> Ảnh tham chiếu
                <span class="badge badge-ghost badge-sm font-normal">Không bắt buộc</span>
            </h2>
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
            {{-- v1.13 (phản hồi người dùng) — bỏ hẳn hiển thị `reference_image_url` khỏi UI (không xoá
                 cột/dữ liệu). v1.11 — thay cho 2 field ảnh KOL/sản phẩm + prompt tự sinh ghép ảnh của
                 v1.10: người dùng đã tự chuẩn bị sẵn ảnh ở ngoài, chỉ cần 1 ô text ngắn tự gõ để nhớ
                 lại ngữ cảnh, KHÔNG tự sinh gì cả. --}}
            @if($project->reference_context_prompt)
            <div class="mt-3 pt-3 border-t border-base-200">
                <p class="text-xs font-medium text-base-content/50 mb-1">Mô tả ngữ cảnh ảnh tham chiếu</p>
                <p class="text-sm whitespace-pre-wrap">{{ $project->reference_context_prompt }}</p>
            </div>
            @endif
        </div>
    </div>

    <div id="step-3" class="mb-3 scroll-mt-4">
        <h2 class="text-lg font-semibold flex items-center gap-2"><span class="badge badge-primary badge-sm">3</span> Kịch bản &amp; Timeline</h2>
        <p class="text-xs text-base-content/50 mt-0.5">Mỗi Shot là 1 cảnh (scene) trong video — điền theo thứ tự xuất hiện, Timeline bên dưới sẽ tự cập nhật.</p>
    </div>

    {{-- UI/UX v2 (content24.ai — Bước 2 "Write a 30-60s script" của nguồn) — khung thời gian mẫu dịch
         từ bảng gốc (Hook/Problem/Solution/Proof/CTA), giúp người không chuyên có sẵn 1 khuôn để bắt
         đầu thay vì nhìn trang trắng. Mở sẵn khi project chưa có shot nào (lúc cần nhất), đóng khi đã
         có nội dung (tránh chiếm chỗ). Nội dung tĩnh — không phụ thuộc dữ liệu. --}}
    <details {{ $project->shots->isEmpty() ? 'open' : '' }} class="collapse collapse-arrow bg-base-100 border border-base-200 mb-4">
        <summary class="collapse-title text-sm font-medium py-3">📐 Khung thời gian mẫu (30-60 giây) — dùng làm khuôn tham khảo</summary>
        <div class="collapse-content text-xs">
            <div class="overflow-x-auto">
                <table class="table table-xs">
                    <thead>
                        <tr class="text-xs">
                            <th>Phần</th>
                            <th>Thời lượng</th>
                            <th>Nội dung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td class="font-medium">Hook</td><td class="whitespace-nowrap">0–3s</td><td>Gây chú ý ngay — pattern interrupt hoặc 1 câu tuyên bố táo bạo</td></tr>
                        <tr><td class="font-medium">Vấn đề</td><td class="whitespace-nowrap">3–10s</td><td>1 câu khiến khán giả thấy "đúng là mình"</td></tr>
                        <tr><td class="font-medium">Giải pháp</td><td class="whitespace-nowrap">10–25s</td><td>Sản phẩm hoặc kết quả xuất hiện trực quan</td></tr>
                        <tr><td class="font-medium">Bằng chứng</td><td class="whitespace-nowrap">25–40s</td><td>Số liệu, testimonial, hình trước/sau</td></tr>
                        <tr><td class="font-medium">CTA</td><td class="whitespace-nowrap">40–60s</td><td>1 hành động duy nhất — nói VÀ hiển thị chữ trên màn hình</td></tr>
                    </tbody>
                </table>
            </div>
            {{-- v1.14 (imagine.art "make-ai-marketing-videos") — khung 20s ngắn hơn cho video UGC/quảng
                 cáo nhanh (khác khung 60s ở trên dành cho video có "Vấn đề" rõ ràng cần build-up dài hơi). --}}
            <p class="text-base-content/60 mt-2">
                <b>Video ngắn (UGC/quảng cáo nhanh, ~20s):</b> Hook 0–3s → Sản phẩm 3–8s → Lợi ích 8–12s → Bằng chứng 12–18s → CTA 18–20s.
            </p>
            <p class="text-base-content/60 mt-2">
                <b>Lỗi thường gặp làm giảm engagement:</b> mở đầu bằng logo thay vì hook, ẩn sản phẩm quá lâu (nên xuất hiện rõ trong 5 giây đầu), nhồi 3 thông điệp vào 1 clip 20 giây, bỏ qua phụ đề (80% video mạng xã hội được xem không tiếng).
            </p>
            {{-- v1.14 (mindstudio.ai "multi-agent-workflow") — bảng định dạng/thời lượng tối đa theo
                 nền tảng, giúp chọn Tỷ lệ khung hình + ước lượng Tổng thời lượng phù hợp TRƯỚC khi soạn
                 shot hàng loạt. Thuần tham khảo — KHÔNG chặn lưu nếu vượt quá. --}}
            <div class="overflow-x-auto mt-3">
                <table class="table table-xs">
                    <thead>
                        <tr class="text-xs">
                            <th>Nền tảng</th>
                            <th>Tỷ lệ khung hình</th>
                            <th>Thời lượng tối đa thường dùng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>TikTok/Reels</td><td>9:16</td><td>~3 phút</td></tr>
                        <tr><td>YouTube Shorts</td><td>9:16</td><td>60 giây</td></tr>
                        <tr><td>YouTube (thường)</td><td>16:9</td><td>Không giới hạn</td></tr>
                        <tr><td>LinkedIn</td><td>16:9</td><td>~10 phút</td></tr>
                        <tr><td>Instagram Feed</td><td>1:1</td><td>60 giây</td></tr>
                    </tbody>
                </table>
            </div>
            @if($project->shots->isEmpty())
            <p class="text-base-content/60 mt-2">Muốn dùng luôn khung 60s? Bấm <b>"Chèn 5 cảnh mẫu"</b> ở khối bên dưới — hệ thống tự tạo 5 Shot theo đúng 5 phần trên.</p>
            @endif
        </div>
    </details>

    {{-- v1.2 (Hedra "how-to-make-ai-video" — Key Prompting Techniques) + v1.3
         (deepreel.com/blog/ai-video-prompts) + v1.4 (byteplus.com) + v1.5 (pyxeljam.com) + v1.8
         (sentx.ai/blog/how-to-write-ai-video-prompts) + v1.9 (veed.io/learn/video-prompts) — nhắc
         nhanh nguyên tắc viết prompt tốt. UI/UX v2 — thu gọn mặc định (trước đây là 1 alert luôn mở,
         chiếm ~10 dòng trước khi vào danh sách Shot). --}}
    <details class="collapse collapse-arrow bg-base-100 border border-base-200 mb-4">
        <summary class="collapse-title text-sm font-medium py-3">💡 Mẹo viết prompt (bấm để xem)</summary>
        <div class="collapse-content text-xs text-base-content/70">
            <span>
                mỗi Shot chỉ nên tả 1 cảnh/khoảnh khắc duy nhất, KHÔNG dồn nhiều hành động khác nhau (VD chạy + nhảy + mở cửa + nói) vào 1 shot ngắn ·
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
                {{-- v1.14 (mindstudio.ai "multi-agent-workflow" — Agent 1 "Script + Shot List") — công
                     thức tốc độ đọc chuẩn, dùng để ước lượng độ dài Lời thoại khớp với Thời lượng đã điền. --}}
                Lời thoại (script_line): ước lượng ~125-150 từ cho mỗi 1 phút video khi viết — giúp khớp độ dài lời đọc với Thời lượng (giây) đã điền, tránh lời thoại quá dài so với shot ngắn ·
                {{-- v1.14 (mindstudio.ai — Agent 2 "Voiceover") — bước tạo giọng đọc TRƯỚC khi tạo
                     video, dùng timestamp cấp từ để canh khớp lời thoại với từng shot khi dựng/ghép. --}}
                có lời thoại dài/nhiều shot? cân nhắc tạo giọng đọc (voiceover) bằng TTS chất lượng cao (ElevenLabs/PlayHT/OpenAI TTS/Kokoro) TRƯỚC khi tạo video từng shot — dùng timestamp cấp từ do TTS trả về để canh đúng lời thoại với từng shot lúc dựng/ghép, thay vì đoán thời lượng ·
                {{-- v1.8 (sentx.ai "Troubleshooting by Slot") — trỏ tới khối mới trong tài liệu xuất. --}}
                output ra sai ý? xem khối "Xử lý sự cố theo triệu chứng" trong tài liệu xuất (nút "Xuất Director Prompt Template" bên dưới) để biết đúng field cần sửa.
            </span>
        </div>
    </details>

    {{-- UI/UX v2 — timeline trực quan: 1 đoạn/shot, độ rộng theo tỉ lệ duration_seconds (dùng flex-grow
         nên KHÔNG cần tính % thủ công, và shot chưa điền duration vẫn có độ rộng tối thiểu hợp lý thay
         vì biến mất). Render server-side lần đầu; `aivideostudiotemplate.js`'s `renderTimeline()` build
         lại y hệt cấu trúc này từ DOM sau mỗi lần thêm/sửa/xoá/sắp xếp lại shot — KHÔNG cần tải lại
         trang để thấy timeline cập nhật (§6.2 tinh thần "mọi thứ tự lưu" của module). --}}
    <div id="aivsTimeline" class="mb-5">
        @php
            $timelineTotal = $project->shots->sum('duration_seconds');
            $timelineHasAny = $project->shots->whereNotNull('duration_seconds')->isNotEmpty();
            $timelineCursor = 0;
            $timelinePalette = ['bg-primary/10 border-primary/30', 'bg-secondary/10 border-secondary/30', 'bg-accent/10 border-accent/30', 'bg-info/10 border-info/30'];
        @endphp
        @if($project->shots->isEmpty())
        {{-- Chưa có shot nào — chưa có gì để vẽ, khối empty-state bên dưới đã đủ hướng dẫn. --}}
        @elseif(!$timelineHasAny)
        <div class="alert py-2 px-3 text-xs bg-base-200/60 border border-base-200">
            <span>Điền <b>Thời lượng (giây)</b> cho từng cảnh bên dưới để xem timeline trực quan tại đây.</span>
        </div>
        @else
        <div class="flex items-center justify-between mb-1.5">
            <span class="text-xs font-medium text-base-content/60">Timeline video</span>
            <span class="badge badge-neutral badge-sm">Tổng: {{ $timelineTotal }} giây</span>
        </div>
        <div class="flex w-full rounded-lg overflow-hidden border border-base-200 divide-x divide-base-200">
            @foreach($project->shots as $shot)
            @php
                $segStart = $timelineCursor;
                $segDuration = $shot->duration_seconds ?: 5;
                $timelineCursor += $segDuration;
                $segColor = $timelinePalette[$loop->index % count($timelinePalette)];
            @endphp
            <a href="#shot-{{ $shot->uuid }}" style="flex: {{ $segDuration }} 1 0" class="{{ $segColor }} border-t-0 border-b-0 px-2 py-2 text-[11px] leading-tight hover:brightness-95 transition-[filter] min-w-[64px]">
                <div class="font-medium truncate">{{ $loop->iteration }}. {{ $shot->label ?: 'Chưa đặt tên' }}</div>
                <div class="text-base-content/50">{{ $shot->duration_seconds ? "{$segStart}–" . ($segStart + $shot->duration_seconds) . 's' : 'chưa có thời lượng' }}</div>
            </a>
            @endforeach
        </div>
        @endif
    </div>

    <div class="flex items-center justify-between mb-3">
        <h3 id="aivsShotCountHeading" class="text-sm font-medium text-base-content/60">Danh sách cảnh ({{ $project->shots->count() }})</h3>
        <button type="button" id="aivsAddShotBtn" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm cảnh
        </button>
    </div>

    {{-- UI/UX v2 — empty-state thân thiện thay vì danh sách trống trơn: 2 lối vào rõ ràng cho người
         không chuyên (tự viết từ đầu, hoặc chèn sẵn 5 cảnh theo khung thời gian mẫu ở trên). Nút
         "Chèn 5 cảnh mẫu" chỉ gọi lại đúng API POST/PUT shots đã có sẵn 5 lần liên tiếp — KHÔNG có
         Action/route mới ở backend. --}}
    <div id="aivsEmptyState" class="card bg-base-100 border border-dashed border-base-300 mb-8" style="{{ $project->shots->isNotEmpty() ? 'display:none' : '' }}">
        <div class="card-body items-center text-center py-8">
            <p class="font-medium text-base-content">Chưa có cảnh nào trong kịch bản</p>
            <p class="text-xs text-base-content/50 max-w-md">Bắt đầu bằng 1 cảnh trống, hoặc chèn sẵn 5 cảnh theo khung thời gian mẫu (Hook → Vấn đề → Giải pháp → Bằng chứng → CTA) ở trên rồi sửa lại nội dung cho phù hợp.</p>
            <div class="flex flex-wrap gap-2 justify-center mt-2">
                <button type="button" id="aivsQuickStartBtn" class="btn btn-primary btn-sm gap-1.5">⚡ Chèn 5 cảnh mẫu</button>
                <button type="button" id="aivsAddFirstShotBtn" class="btn btn-ghost btn-sm">+ Thêm 1 cảnh trống</button>
            </div>
        </div>
    </div>

    <div id="aivsShotList" data-project-uuid="{{ $project->uuid }}" class="space-y-4 mb-8">
        @foreach($project->shots as $shot)
        <div id="shot-{{ $shot->uuid }}" class="card bg-base-100 shadow-sm border border-base-200 aivs-shot-card scroll-mt-4"
             data-shot-id="{{ $shot->id }}" data-shot-uuid="{{ $shot->uuid }}" data-project-uuid="{{ $project->uuid }}">
            <div class="card-body space-y-3">
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <div class="flex items-center gap-2">
                        <span class="badge badge-neutral badge-sm shrink-0 aivs-shot-number">Cảnh {{ $loop->iteration }}</span>
                        <input type="text" class="aivs-field input input-bordered input-sm font-medium w-56" data-field="label"
                               value="{{ $shot->label }}" placeholder="VD: Shot 1 — Hook">
                        {{-- v1.3 (deepreel.com/blog/ai-video-prompts) — Duration là 1 thành phần công thức nguồn
                             ("Subject + Style + Camera + Mood + Duration"), tách riêng số vì cộng dồn được tổng
                             thời lượng project (xem CompileProjectDirectorPromptAction) và vẽ timeline (UI/UX v2). --}}
                        <input type="number" min="1" max="36000" class="aivs-field input input-bordered input-sm w-24" data-field="duration_seconds"
                               value="{{ $shot->duration_seconds }}" placeholder="Giây" title="Thời lượng ước tính (giây)">
                    </div>
                    {{-- v1.13 (phản hồi người dùng) — bỏ autosave debounce PUT mỗi lần gõ (giảm số lượng
                         request AJAX); thay bằng nút "Lưu" bấm tay, gõ xong cả shot rồi lưu 1 lần. --}}
                    <div class="flex items-center gap-1">
                        <button type="button" class="btn btn-primary btn-xs aivs-save-shot">Lưu</button>
                        <span class="aivs-save-status text-xs text-base-content/40"></span>
                        <button type="button" class="btn btn-ghost btn-xs aivs-move-up" title="Lên">↑</button>
                        <button type="button" class="btn btn-ghost btn-xs aivs-move-down" title="Xuống">↓</button>
                        <button type="button" class="btn btn-ghost btn-xs text-error aivs-delete-shot">Xoá</button>
                    </div>
                </div>

                {{-- UI/UX v2 — 3 nhóm field theo cách người không chuyên nghĩ về 1 cảnh (Cái gì đang
                     diễn ra / Trông và nghe thế nào / Nói gì và khi nào), thay vì 1 lưới 10 field phẳng
                     không phân biệt. Constraints đứng riêng cuối cùng (catch-all, không thuộc nhóm nào). --}}
                <div class="space-y-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/40 mb-1.5">Nội dung cảnh — đang diễn ra chuyện gì</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            @foreach([
                                'subject' => ['Subject (Chủ thể)', null],
                                'action' => ['Action (Hành động)', null],
                                {{-- v1.9 (veed.io) — field CUỐI trong nhóm 5 field cốt lõi còn thiếu ví dụ (Camera/
                                     Style đã có từ v1.8); bổ sung "spatial descriptors" (không gian) + "temporal
                                     cues" (thời điểm) — nguồn nhấn mạnh cần mô tả CẢ HAI, không chỉ 1 trong 2. --}}
                                'environment' => ['Environment (Bối cảnh)', 'VD: hẻm nhỏ về đêm, đồng cỏ rộng mở, studio trong nhà, hang động dưới nước + thời điểm: lúc rạng đông, chiều tà, sáng mùa đông, giờ vàng (golden hour) — mô tả cả không gian VÀ thời điểm'],
                            ] as $field => [$fieldLabel, $fieldPlaceholder])
                            <div class="form-control">
                                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">{{ $fieldLabel }}</span></label>
                                <textarea rows="2" class="aivs-field textarea textarea-bordered textarea-sm w-full" data-field="{{ $field }}"
                                          placeholder="{{ $fieldPlaceholder }}">{{ $shot->{$field} }}</textarea>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/40 mb-1.5">Hình ảnh &amp; Âm thanh — trông và nghe thế nào</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach([
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
                            ] as $field => [$fieldLabel, $fieldPlaceholder])
                            <div class="form-control">
                                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">{{ $fieldLabel }}</span></label>
                                <textarea rows="2" class="aivs-field textarea textarea-bordered textarea-sm w-full" data-field="{{ $field }}"
                                          placeholder="{{ $fieldPlaceholder }}">{{ $shot->{$field} }}</textarea>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/40 mb-1.5">Timeline &amp; Lời thoại — nói gì, khi nào</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            {{-- v1.10 (bài LinkedIn, ví dụ Synthesia "kịch bản theo timeline") — breakdown của
                                 Duration (số giây, nhập ở header card) theo mốc thời gian, cho shot dài/nhiều nhịp. --}}
                            <div class="form-control md:col-span-2">
                                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Timeline nội dung (theo giây)</span></label>
                                <textarea rows="2" class="aivs-field textarea textarea-bordered textarea-sm w-full" data-field="timeline_breakdown"
                                          placeholder="VD: 0-5s: hook mở đầu gây chú ý&#10;5-15s: nội dung chính&#10;15-20s: kết + CTA">{{ $shot->timeline_breakdown }}</textarea>
                            </div>
                            <div class="form-control">
                                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Lời thoại (Script line)</span></label>
                                <textarea rows="2" class="aivs-field textarea textarea-bordered textarea-sm w-full" data-field="script_line">{{ $shot->script_line }}</textarea>
                            </div>
                            <div class="form-control">
                                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Call-to-action (CTA)</span></label>
                                <textarea rows="2" class="aivs-field textarea textarea-bordered textarea-sm w-full" data-field="cta_text"
                                          placeholder="VD: &quot;Mua ngay - Giảm 20% hôm nay&quot; hoặc text nút/đếm ngược hiển thị trên màn hình">{{ $shot->cta_text }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Constraints (Ràng buộc)</span></label>
                        <textarea rows="2" class="aivs-field textarea textarea-bordered textarea-sm w-full" data-field="constraints"
                                  placeholder="VD cụ thể (negative prompt): &quot;không motion blur, nét mặt nhân vật, không lens flare&quot; — tránh mô tả chung chung như &quot;không mờ&quot;">{{ $shot->constraints }}</textarea>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1 flex items-center justify-between">
                        <span class="label-text text-xs font-medium">Prompt hoàn chỉnh (tự sinh)</span>
                        {{-- v1.3 — deepreel.com khuyến nghị 50-150 từ/prompt; cảnh báo nhẹ nếu lệch khoảng này. --}}
                        <span class="aivs-word-count text-xs text-base-content/40"></span>
                    </label>
                    <div class="flex gap-2 items-start">
                        <textarea readonly rows="4" class="aivs-compiled textarea textarea-bordered textarea-sm w-full font-mono">{{ $shot->compiled_prompt }}</textarea>
                        <button type="button" class="btn btn-outline btn-xs shrink-0 aivs-copy-compiled">Copy</button>
                    </div>
                </div>

                {{-- v1.10 (bài LinkedIn, mục 3.2 "Image-to-Video") — quy trình 2 bước thay thế cho
                     compiled_prompt gộp ở trên: tạo ảnh tĩnh trước (Midjourney/DALL-E), rồi hoạt hình
                     hoá đúng ảnh đó (RunwayML/Kling). v1.12 (phản hồi người dùng) — trước đây 2 ô này
                     readonly/tự sinh từ field khác giống compiled_prompt, gây hiểu nhầm là lỗi không
                     gõ được; đổi thành NHẬP TAY tự do (`.aivs-field`, autosave debounce như mọi field
                     khác) — KHÔNG còn tự sinh/ghi đè. Đóng mặc định vì vẫn là workflow nâng cao. --}}
                <details class="collapse collapse-arrow bg-base-200/40 border border-base-200">
                    <summary class="collapse-title text-xs font-medium py-2 min-h-0">Prompt 2 bước — Ảnh + Motion (Image-to-Video)</summary>
                    <div class="collapse-content space-y-3">
                        <div class="form-control">
                            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Prompt Ảnh (tạo keyframe)</span></label>
                            <div class="flex gap-2 items-start">
                                <textarea rows="3" class="aivs-field aivs-image-prompt textarea textarea-bordered textarea-xs w-full font-mono" data-field="image_prompt"
                                          placeholder="VD: Midjourney/DALL-E prompt cho khung hình tĩnh — chủ thể, bối cảnh, góc máy, phong cách... --ar 9:16">{{ $shot->image_prompt }}</textarea>
                                <button type="button" class="btn btn-outline btn-xs shrink-0 aivs-copy-image-prompt">Copy</button>
                            </div>
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Prompt Motion (hoạt hình hoá ảnh)</span></label>
                            <div class="flex gap-2 items-start">
                                <textarea rows="3" class="aivs-field aivs-motion-prompt textarea textarea-bordered textarea-xs w-full font-mono" data-field="motion_prompt"
                                          placeholder="VD: RunwayML/Kling prompt cho chuyển động — hành động, chuyển động máy, thời lượng...">{{ $shot->motion_prompt }}</textarea>
                                <button type="button" class="btn btn-outline btn-xs shrink-0 aivs-copy-motion-prompt">Copy</button>
                            </div>
                        </div>
                    </div>
                </details>

            </div>
        </div>
        @endforeach
    </div>

    {{-- spec §6/§8 — xuất Director Prompt Template tổng hợp: tải .md + copy toàn bộ. --}}
    <div id="step-4" class="card bg-base-100 shadow-sm border border-base-200 scroll-mt-4">
        <div class="card-body">
            <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
                <h2 class="card-title text-base"><span class="badge badge-primary badge-sm">4</span> Xuất &amp; Dùng</h2>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-outline btn-sm" id="aivsCopyAllBtn" onclick="aivsCopy('aivsCompiledAll', this)">Copy toàn bộ</button>
                    <a href="{{ route('backend.aivideostudiotemplate.export', $project) }}" class="btn btn-primary btn-sm">Xuất file .md</a>
                </div>
            </div>
            <p class="text-xs text-base-content/50 mb-2">Dán vào ChatGPT/Claude hoặc tool tạo video AI theo đúng thứ tự cảnh — hoặc tải file .md để lưu/chia sẻ cho team.</p>
            <textarea readonly rows="10" id="aivsCompiledAll" class="textarea textarea-bordered textarea-xs w-full font-mono">{{ $compiledDocument }}</textarea>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>window.aivsApiBaseUrl = @json(url('/backend/api/ai-video-studio'));</script>
@vite(['Modules/AIVideoStudioTemplate/resources/assets/js/aivideostudiotemplate.js'], 'build/backend')
@endpush
