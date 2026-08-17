<?php

namespace Modules\AIVideoStudioTemplate\Features\ProjectManagement\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioShot;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §3.5 — xuất tài liệu Director Prompt
 * Template tổng hợp = ghép các `compiled_prompt` theo `sort_order`, tính động mỗi lần xem (KHÔNG
 * lưu snapshot riêng — giống ContentOutlines::show() tính promptHtml động).
 *
 * v1.2 (Hedra "how-to-make-ai-video"): thêm khối Creative Brief đầu tài liệu (Step 1 — chốt mục
 * tiêu/đối tượng/định dạng TRƯỚC khi generate, tránh sai lầm phổ biến "bỏ qua creative brief") + gợi
 * ý checklist đánh giá output (Step 3) + metadata model/tool đã dùng cho từng shot (Step 4 + Model
 * Selection Criteria) — CHỈ hiển thị nếu người dùng có điền, không ép buộc.
 *
 * v1.3 (deepreel.com/blog/ai-video-prompts): thêm khối checklist TRƯỚC khi generate (khác khối QC
 * ở v1.2 vốn dùng SAU khi generate để đánh giá output) + tổng thời lượng ước tính của project (cộng
 * `duration_seconds` các shot đã điền) — 1 thành phần công thức nguồn ("Duration") mà việc lập kế
 * hoạch nhiều shot/nhiều nền tảng cần biết trước khi generate hàng loạt.
 *
 * v1.4 (byteplus.com — "how to write AI video prompts"): thêm dòng **Tài liệu tham chiếu bổ sung**
 * (`reference_assets`) mỗi shot nếu có điền — cùng nhóm metadata với `model_tool`, KHÔNG có trong
 * `compiled_prompt` (khái niệm multi-reference của nguồn: ảnh/video/audio tham chiếu riêng cho từng
 * shot, ghi chú lại để nhớ dùng file nào, không gửi thẳng vào prompt vì cú pháp tham chiếu khác nhau
 * tuỳ tool AI ngoài).
 *
 * v1.5 (pyxeljam.com — "10 best practices for writing effective AI video prompts"): thêm dòng
 * **Độ phân giải** (`resolution`) vào khối Creative Brief nếu có điền — thực hành #7 của nguồn "Set
 * Video Length, Quality, and File Format Requirements" (phần Quality; phần Video Length đã có qua
 * `duration_seconds`/tổng thời lượng ở v1.3).
 *
 * v1.6 (bài LinkedIn "step-by-step guide creating AI marketing videos prompts"): thêm dòng **Loại
 * video** (`video_type`) và **Thông điệp cốt lõi** (`core_message`) vào khối Creative Brief — Step 1
 * "Define Objectives" của nguồn tách 2 khái niệm này khỏi `objective`/`target_audience` đã có (v1.2).
 * Bổ sung 3 mục checklist đặc thù marketing vào QC_CHECKLIST (thời lượng khớp tỷ lệ nền tảng, nhất
 * quán thương hiệu, âm thanh rõ ràng).
 *
 * v1.8 (sentx.ai/blog/how-to-write-ai-video-prompts): công thức 7-lớp + đa số nguyên tắc (subject cụ
 * thể, action khả thi trong vài giây, front-load 20-30 từ đầu, show-vs-tell, "swipe file" prompt tái
 * dùng) nguồn liệt kê ĐÃ phủ từ v1.0-v1.6 (swipe file trùng "Duplicate/clone Shot", đã ghi nhận để dành
 * bản sau ở §10 spec — không lặp lại quyết định ở đây). 2 kỹ thuật thật sự còn thiếu: (1) **"Single
 * hero focus"** — nguồn tách riêng khỏi nguyên tắc "1 shot = 1 hành động" đã có (Hedra v1.2): giới hạn
 * SỐ LƯỢNG chủ thể trong khung hình (1 chính + tối đa 1 phụ), không phải số hành động — bổ sung 1 mục
 * vào `PRE_GENERATION_CHECKLIST`; (2) **Troubleshooting theo triệu chứng** (nguồn: "static appearance →
 * strengthen action + camera", "wrong mood/color → name light source + time of day", "style ignored →
 * move earlier with concrete anchors", "inconsistent subject → fixed identifying traits") — nguồn
 * TRƯỚC đây chỉ cho checklist đánh giá kết quả (Hedra QC_CHECKLIST) mà không map triệu chứng cụ thể
 * sang field cần sửa; bổ sung `TROUBLESHOOTING_GUIDE` + khối `## Xử lý sự cố theo triệu chứng` (đặt
 * SAU checklist QC — dùng khi checklist QC phát hiện lỗi, cần biết sửa Ở ĐÂU). Đồng thời cập nhật
 * placeholder Camera/Style ở `show.blade.php` với vốn từ vựng cỡ cảnh/góc máy/chuyển động máy và gọi
 * tên nguồn sáng + thời điểm trong ngày mà nguồn liệt kê cụ thể hơn callout hiện có.
 *
 * v1.9 (veed.io/learn/video-prompts): công thức "3 thành phần" (Subject & Action/Visual Style/
 * Technical Direction) + phần lớn "power words" (temporal/spatial/emotional cues), tổ chức prompt
 * theo thứ tự, sai lầm phổ biến — ĐÃ phủ từ v1.0-v1.8. Gap thật sự: nguồn có 1 khối **"Platform-
 * Specific Strategies"** map RÕ cách viết theo định dạng/loại nội dung (TikTok-Reels 9:16 cần hook
 * mạnh 1-2s đầu + cắt nhanh; YouTube 16:9 giáo dục cần narrator+overlay, tutorial cần hands-in-frame,
 * storytelling cần mạch cảm xúc; Business cần benefit/lighting/CTA placement theo từng loại) mà
 * module trước đó chỉ có 1 câu chung "điều chỉnh giọng văn theo nền tảng" (v1.6) — không map cụ thể
 * sang field nào. Bổ sung `PLATFORM_TIPS_BY_ASPECT_RATIO` (map `aspect_ratio` đã có sẵn — CHỈ 9:16/
 * 16:9 vì nguồn chỉ nói rõ 2 định dạng này, không đoán thêm cho 1:1/4:5) và `CONTENT_TIPS_BY_VIDEO_
 * TYPE` (map `video_type` đã có sẵn — bỏ qua `other` vì nguồn không có gợi ý chung phù hợp), hiển thị
 * trong khối Creative Brief nếu field tương ứng có điền và có tip khớp. KHÔNG cần schema mới — dùng
 * lại đúng 2 field đã tồn tại từ v1.2/v1.6. Đồng thời enrich placeholder Environment (`show.blade.php`)
 * với "spatial descriptors" (hẻm nhỏ, đồng cỏ rộng, studio, hang động...) + "temporal cues" (rạng
 * đông, chiều tà, giờ vàng...) — field CUỐI CÙNG trong nhóm 5 field cốt lõi còn thiếu ví dụ (Camera/
 * Style đã có từ v1.8); và Style thêm ví dụ phong cách hoạt hình/nghệ thuật (anime, claymation, tranh
 * màu nước, VHS cũ) — nguồn liệt kê riêng như 1 kỹ thuật anchor bản sắc hình ảnh. "Leveraging ChatGPT
 * để soạn thảo" và danh sách model cụ thể (MiniMax/PixVerse/Kling...) CỐ Ý không áp dụng — trùng
 * quyết định "không phụ thuộc 1 tool cụ thể" đã chốt ở §0 spec (module chỉ ghi `model_tool` tự do).
 *
 * v1.10 (đọc lại bài LinkedIn — mục 3.2 "Image-to-Video"): mỗi Shot section giờ có thêm khối
 * **Prompt Ảnh (keyframe)** / **Prompt Motion** (nếu `image_prompt`/`motion_prompt` có giá trị — xem
 * `BuildShotPromptAction::buildImagePrompt()`/`buildMotionPrompt()`), đặt NGAY SAU `compiled_prompt`
 * chính — phục vụ quy trình 2 bước (text-to-image rồi image-to-video) khác với quy trình 1 bước
 * (text-to-video) mà `compiled_prompt` phục vụ từ trước.
 *
 * v1.14 (mindstudio.ai "ai-video-generation-content-marketing-multi-agent-workflow" +
 * imagine.art "make-ai-marketing-videos" — đọc theo yêu cầu tổng hợp kỹ thuật mới): 2 nguồn phần lớn
 * lặp lại nguyên tắc đã có (hook đầu video, 1 CTA duy nhất, kiểm tra trước khi đăng, thử biến thể —
 * đã phủ từ v1.0-v1.9); các phần rõ ràng KHÔNG áp dụng (gọi API TTS/video model thật, retry/backoff,
 * content-calendar integration, đo hiệu suất sau khi đăng) trùng quyết định "chỉ tổ chức prompt,
 * không gọi AI Provider" (§0) và mục "Theo dõi hiệu suất thực tế" đã loại khỏi phạm vi ở §10 — không
 * mở lại. 4 kỹ thuật thật sự còn thiếu, đã áp dụng: (1) **2 `video_type` mới** `spokesperson`/
 * `offer_promo` (imagine.art liệt kê 7 định dạng cụ thể hơn 5 loại cũ, §4.1) — kèm tip tương ứng ở
 * `CONTENT_TIPS_BY_VIDEO_TYPE`; (2) **công thức tốc độ đọc 125-150 từ/phút** (mindstudio.ai Agent 1)
 * cho `script_line`, thêm vào callout "Mẹo viết prompt" (`show.blade.php`); (3) **bước Voiceover/TTS**
 * (mindstudio.ai Agent 2 — tạo giọng đọc TRƯỚC khi tạo video, dùng timestamp cấp từ canh khớp shot),
 * thêm vào cùng callout — chỉ là GHI CHÚ quy trình, module không gọi TTS thật; (4) **bảng định dạng/
 * thời lượng theo nền tảng** (mindstudio.ai) + **"ẩn sản phẩm quá lâu"/phụ đề mờ** (imagine.art) —
 * thêm vào khung thời gian mẫu + `QC_CHECKLIST` (`show.blade.php`). Nhân tiện sửa 1 lỗi phát hiện khi
 * rà soát: `buildCreativeBriefBlock()` in slug thô `$project->video_type` thay vì `videoTypeLabel()`
 * dù spec §11 (v1.7) đã ghi nhận hành vi ĐÚNG này từ trước — code trước đó lệch với spec, không phải
 * do thay đổi vừa rồi.
 *
 * v1.15 (cùng nguồn mindstudio.ai — phản hồi người dùng "không áp dụng kỹ thuật nào mới ah?" sau
 * v1.14, đúng: v1.14 chỉ thêm tip/text, chưa có khối TÍNH TOÁN mới nào): nguồn có khái niệm **EDL
 * (Edit Decision List)** ở Agent 2 "Voiceover" — bảng đối chiếu narration/thời gian với từng shot,
 * dùng ở Agent 4 "Assembly" để canh clip khớp voiceover/phụ đề. Module CHƯA có bảng này dù đã đủ dữ
 * liệu (`duration_seconds`, `script_line`, `sort_order`) — trước đây chỉ có ("Tổng thời lượng ước
 * tính", 1 số) và timeline trực quan trên `show.blade.php` (chỉ hiện TRÊN TRANG, không có trong tài
 * liệu xuất — bản .md tải về để đưa cho editor lại thiếu). Bổ sung `buildEdlBlock()`: bảng Markdown
 * (Cảnh | Thời gian | Lời thoại | Mô tả hình ảnh), thời gian tính cộng dồn TỪ `duration_seconds` (như
 * `renderTimeline()`/timeline strip đã làm ở `show.blade.php`, KHÔNG tách logic dùng chung vì 1 bên
 * PHP 1 bên JS — chấp nhận trùng lặp nhỏ, đã có tiền lệ tương tự giữa `handle()` và JS timeline). CHỈ
 * xuất hiện nếu ít nhất 1 shot có `duration_seconds` HOẶC `script_line` — dự án chưa viết gì thì bảng
 * rỗng không có ý nghĩa. Đặt SAU khối troubleshooting (cuối phần header, ngay trước danh sách Shot).
 *
 * v1.16 (tulsainternetmarketingservice.com/blog/video-marketing-formulas +
 * swarmify.com/blog/video-marketing-strategy — đọc theo yêu cầu tổng hợp kỹ thuật mới): swarmify.com
 * phần lớn NGOÀI phạm vi module (video SEO/schema/transcript, CDN/hiệu suất trang web nhúng video, đo
 * hiệu suất phân phối sau khi đăng — trùng quyết định §0/§10 đã chốt, module không lưu trữ/host
 * video); "phân loại theo giai đoạn funnel" (awareness/consideration/conversion, tỷ lệ 60/25/15) CỐ
 * Ý không áp dụng — trùng lấn `video_type` đã có (product_demo/testimonial/offer_promo đã ngầm gắn
 * với từng giai đoạn) mà không thêm hướng dẫn viết prompt nào khác biệt. tulsainternetmarketingservice.com
 * có 3 công thức kịch bản (narrative arc) — TRỤC KHÁC với `video_type`: video_type nói loại NỘI DUNG,
 * công thức nói TRÌNH TỰ kể chuyện (1 video product_demo vẫn dùng được cấu trúc PSA hoặc
 * Hook-Value-CTA tuỳ ý đồ) — không trùng lặp. Thêm `video_formula` ở Project (migration riêng, sau
 * `video_type`) + `VIDEO_FORMULAS` ở Model + `FORMULA_TIPS_BY_VIDEO_FORMULA` bên dưới, hiển thị dòng
 * **Công thức kịch bản** + **Cấu trúc gợi ý** trong Creative Brief (chỉ in nếu có điền, cùng pattern
 * `CONTENT_TIPS_BY_VIDEO_TYPE`); đồng thời RENDER vào `compiled_prompt` của mọi Shot qua
 * `BuildShotPromptAction::buildCampaignContextLines()` (dòng "Công thức kịch bản") — cùng nhóm với
 * `video_type`, nên nằm trong `PROMPT_CONTEXT_FIELDS` của `UpdateProjectAction` (sửa sẽ build lại
 * `compiled_prompt` toàn bộ Shot, §3.6).
 *
 * v1.17 (phản hồi người dùng — "khi chọn Loại video và Công thức kịch bản thì bên dưới khối Kịch bản
 * & Timeline nên load gợi ý cách trình bày mô tả tương đương cho loại content đó"): 2 map v1.16
 * (`CONTENT_TIPS_BY_VIDEO_TYPE`, đổi `private`→`public`) + `FORMULA_TIPS_BY_VIDEO_FORMULA` chỉ hiển
 * thị trong tài liệu XUẤT (Creative Brief), KHÔNG có trên trang `show` lúc đang soạn shot — người dùng
 * phải bấm "Xuất Director Prompt Template" mới thấy được gợi ý. Thêm `FORMULA_BEATS` (breakdown mỗi
 * công thức thành 3 nhịp: tên/tỉ lệ thời lượng/cách viết Subject-Action-Script cho nhịp đó) + hiển thị
 * bảng này (dùng lại `CONTENT_TIPS_BY_VIDEO_TYPE` cho câu tip loại nội dung đi kèm) trong 1 `<details>`
 * mới ở `show.blade.php` (§8), đặt NGAY DƯỚI phần mở đầu section "Kịch bản & Timeline" — TRƯỚC khối
 * "Khung thời gian mẫu" tĩnh đã có, vì khối mới đặc thù theo lựa chọn Project còn khối cũ là fallback
 * chung chung. CHỈ hiện nếu project có điền `video_type` HOẶC `video_formula` (không có gì để gợi ý
 * nếu cả 2 đều trống — khối "Khung thời gian mẫu" cũ vẫn phủ trường hợp này).
 *
 * v1.18 (đọc `ngram.com/blog/demo-video-script-template` + `ngram.com/blog/how-to-make-demo-video`
 * — đọc theo yêu cầu tổng hợp kỹ thuật mới): 2 bài đều nói về video demo phần mềm QUAY MÀN HÌNH THẬT
 * (screen recording) — khác domain với module (AI-generated video qua tool ngoài, §0). Phần lớn nội
 * dung KHÔNG áp dụng vì thuộc về quay dựng thật, không phải viết prompt: thiết lập ghi hình (độ phân
 * giải màn hình, đóng tab, mic vật lý, rehearse, ghi liên tục — công cụ Camtasia/OBS/Loom), kỹ thuật
 * dựng hậu kỳ (cắt lỗi, callout/annotation mũi tên/khoanh tròn, transition — trùng quyết định "không
 * animatic/edit trong app" §10), vị trí đặt video đã xuất bản (landing page/email/help center + số
 * liệu conversion theo nơi đặt — trùng "không theo dõi hiệu suất phân phối" §10), quy trình AI Script
 * Generation 6 bước của chính ngram (mô tả tính năng sản phẩm CẠNH TRANH, không phải kỹ thuật — trùng
 * quyết định "không gọi AI Provider" §0). 2 gap thật sự áp dụng được (chỉ phần VIẾT KỊCH BẢN, tách
 * khỏi phần quay/dựng thật của nguồn): (1) **5-Part Demo Video Script Framework** (Hook → Problem
 * Deep-dive → Solution Introduction → Key Features in Action → CTA & Outcome, 90-120s) — TRỤC giống
 * `video_formula` đã có (v1.16/v1.17) nhưng khác PSA ở 2 điểm: tách riêng nhịp Hook khỏi Problem, và
 * có nhịp "Key Features in Action" trình diễn NHIỀU tính năng (2-5) mà PSA gộp chung vào 1 nhịp
 * Solution — không trùng lặp, thêm khoá `demo_5part` vào `VIDEO_FORMULAS`/`FORMULA_TIPS_BY_VIDEO_
 * FORMULA`/`FORMULA_BEATS` (dùng lại đúng cơ chế v1.16/v1.17, không có field/UI mới); (2) enrich tip
 * `product_demo` trong `CONTENT_TIPS_BY_VIDEO_TYPE` — nguồn "show one feature, one workflow, one
 * result" bổ sung giới hạn SỐ tính năng nên trình diễn (2-5) mà tip cũ chỉ có gợi ý hình ảnh (ánh
 * sáng/góc máy/nhạc nền), chưa nói tới số lượng. Nhân tiện bổ sung 2 điểm nhỏ vào callout "Mẹo viết
 * prompt" (`show.blade.php`, §8): "Lead with Outcomes, Not Features" (mở đầu bằng kết quả khách hàng
 * muốn, không phải liệt kê tính năng) và số liệu cụ thể ghi nhớ lâu hơn số liệu chung chung — cả 2
 * đều là nguyên tắc VIẾT, không phụ thuộc domain quay màn hình của nguồn.
 *
 * v1.20 (đọc `leadde.ai/blog/marketing-script-template` — đọc theo yêu cầu tổng hợp kỹ thuật mới):
 * nguồn liệt kê 15 template xếp thành 3 nhóm theo funnel (Video/Ads, Product-Sales-Funnel, Trust-
 * Education-Retention) + 8 thành phần script + Google ABCD Framework cho YouTube Ads + 1 "AI Prompt
 * Template" (cấu trúc "Act as [role]...") để soạn kịch bản qua chatbot. Phần lớn 15 template chỉ là
 * biến thể phối lại các nhịp Hook/Problem/Solution/Proof/CTA đã phủ từ v1.0-v1.18 dưới tên khác nhau
 * (VD "Basic Marketing Script" = Hook→Problem→Solution→Proof→CTA trùng gần hệt PSA đã có, chỉ thêm 1
 * nhịp Proof lồng vào Solution/CTA — không đủ khác biệt để tách công thức riêng); "AI Prompt Template"
 * CỐ Ý không áp dụng — đây là prompt cho chatbot SOẠN kịch bản, khác domain module (Director Prompt
 * Template cho tool AI tạo VIDEO, §0 "không gọi AI Provider"); bảng Metrics theo từng giai đoạn funnel
 * và phân loại 15 template theo awareness/consideration/conversion/retention CỐ Ý không áp dụng — trùng
 * quyết định "không theo dõi hiệu suất phân phối" + "không thêm field funnel_stage" đã chốt ở v1.5/
 * v1.14/v1.16 (không mở lại, xem docblock v1.16 phía trên). 3 gap thật sự còn thiếu, cả 3 đều là
 * TRỤC `video_formula` (không phải field mới — dùng đúng cơ chế `VIDEO_FORMULAS`/`FORMULA_TIPS_BY_
 * VIDEO_FORMULA`/`FORMULA_BEATS` đã có từ v1.16/v1.17/v1.18):
 * (1) **`abcd`** (Google ABCD Framework: Attention-Branding-Connection-Direction) — nguồn ghi rõ đây
 *     là framework CHÍNH THỨC của Google cho YouTube Ads (pre-roll/in-stream, có thể bị bấm "Bỏ qua"
 *     sau 5s). Khác biệt THẬT với 4 công thức đã có: cả PSA/BAB/Hook-Value-CTA/Demo-5-phần đều đặt
 *     thương hiệu/sản phẩm ở nhịp GIỮA hoặc CUỐI (sau khi đã nêu vấn đề/giá trị) — ABCD đặt "Branding"
 *     ngay nhịp thứ 2 (giới thiệu thương hiệu SỚM), vì quảng cáo có thể bị bỏ qua bất cứ lúc nào nên
 *     không đợi được tới cuối mới lộ diện thương hiệu. Đây là 1 nguyên tắc dàn nhịp khác hẳn, không
 *     trùng lặp.
 * (2) **`testimonial_5part`** (Before→Challenge→Solution→Result→Recommendation) — nguồn tách riêng
 *     khỏi BAB đã có: BAB chỉ có 3 nhịp (Before/After/Bridge, Bridge = SẢN PHẨM giải thích vì sao nó là
 *     cầu nối), còn công thức này có 5 nhịp, thêm "Challenge" (khó khăn CỤ THỂ đã gặp, tách khỏi
 *     "Before" chung chung) và đổi "Bridge" (góc nhìn sản phẩm) thành "Recommendation" (góc nhìn NGƯỜI
 *     ĐƯỢC PHỎNG VẤN khuyên trực tiếp người xem, giọng điệu cá nhân hơn hẳn) — cùng logic đã dùng để
 *     chấp nhận `demo_5part` tách khỏi `psa` ở v1.18 (thêm nhịp + đổi góc nhìn = không trùng lặp), áp
 *     dụng cho `video_type = testimonial` đã có sẵn từ v1.6.
 * (3) **`onboarding_5part`** (Welcome→First value→Steps→Mistakes→Support CTA) — gắn với `video_type`
 *     MỚI `onboarding` (Model §4.1); đây là formula RETENTION đầu tiên của module — 4 công thức cũ đều
 *     hướng tới thuyết phục khán giả CHƯA MUA (problem→solution/before→after→CTA mua hàng), còn công
 *     thức này hướng dẫn khách hàng ĐÃ MUA dùng sản phẩm hiệu quả (CTA cuối là "tìm hỗ trợ", không phải
 *     CTA bán hàng) — trục hoàn toàn khác, không thể ép vào 4 công thức cũ.
 *
 * Nhân tiện: `Brand Story Script` (Origin→Mission→Customer problem→Belief→Vision) của nguồn CỐ Ý
 * không thêm công thức riêng dù cũng là 1 trục khác biệt (không xoay quanh pain-point/giải pháp) — nội
 * dung phần lớn trùng hướng dẫn "mô tả mạch cảm xúc xuyên suốt, tiến triển cảnh theo trình tự" đã có ở
 * `CONTENT_TIPS_BY_VIDEO_TYPE['storytelling']` (v1.6), và use-case hẹp (trang About/tuyển dụng) không
 * đủ giá trị biên để đánh đổi việc phình danh sách `video_formula` lên 8 lựa chọn — có thể mở lại nếu
 * có nhu cầu thực tế sau này.
 *
 * v1.21 (đọc `buffer.com/resources/social-media-marketing-strategy` — đọc theo yêu cầu tổng hợp kỹ
 * thuật mới, áp dụng cho module + rà soát cả hệ thống): nguồn là hướng dẫn 7 BƯỚC xây dựng CHIẾN LƯỢC
 * social media tổng thể — kiểm toán tài khoản, định nghĩa khán giả (tổng quát), đặt mục tiêu SMART,
 * chọn nền tảng, định nghĩa "content pillar" (3-5 chủ đề lặp lại cho CẢ 1 kênh), xây lịch đăng + tần
 * suất theo nền tảng, đo lường/xoay trục hàng tháng-quý. Đây là công cụ QUẢN LÝ TÀI KHOẢN MẠNG XÃ HỘI
 * (Buffer chính là 1 tool lịch đăng/phân tích) — RÀ SOÁT CẢ HỆ THỐNG không tìm thấy module nào tương
 * ứng: `ContentCalendar` (spec §16 "Ngoài phạm vi") đã tường minh loại "đa kênh (repurpose sang
 * social/newsletter)" khỏi Phase 1 — module đó chỉ quản lý pipeline biên tập bài viết CỦA NỀN TẢNG
 * (`Post`), không đăng/lên lịch lên tài khoản mạng xã hội ngoài; `AIVideoStudioTemplate` (module này)
 * chỉ là công cụ dựng PROMPT cho 1 video/TVC đơn lẻ, không quản lý tài khoản/lịch đăng/KPI của cả kênh
 * — đã CỐ Ý không theo dõi hiệu suất phân phối từ v1.5/v1.10/v1.14/v1.16 (§10, không mở lại). Vì vậy
 * phần lớn 7 bước (Bước 1 kiểm toán, Bước 2 audience tổng quát, Bước 3 SMART goal, Bước 5 content
 * pillar, Bước 6 lịch đăng/tần suất, Bước 7 đo lường) KHÔNG áp dụng — không có nơi nào trong hệ thống
 * hợp lý để đặt các khái niệm này, không phải khoảng trống của riêng module này.
 *
 * **1 gap thật sự áp dụng được cho `AIVideoStudioTemplate`** — nửa đầu Bước 4 "Chọn nền tảng": bảng
 * MỤC TIÊU THUẬT TOÁN theo nền tảng (TikTok/Reels = thời gian xem + tỷ lệ HOÀN THÀNH; YouTube = thời
 * gian xem + tỷ lệ NHẤP) — khác hẳn `PLATFORM_TIPS_BY_ASPECT_RATIO` (v1.9) vốn chỉ nói cách MỞ ĐẦU
 * (hook), không nói gì về việc GIỮ nhịp xuyên suốt để thuật toán không cắt giảm phân phối giữa chừng.
 * Enrich 2 tip `9:16`/`16:9` đã có — không thêm field/schema mới (xem docblock hằng số
 * `PLATFORM_TIPS_BY_ASPECT_RATIO` bên dưới). Nửa sau Bước 4 (bảng hiệu suất ĐỊNH DẠNG theo nền tảng —
 * VD LinkedIn Carousel/PDF vượt trội Video, Instagram Carousel vượt ảnh đơn) CỐ Ý không áp dụng: module
 * này CHỈ tạo prompt cho VIDEO (§1 "Mục tiêu"), không có carousel/ảnh đơn/text post để so sánh — dữ
 * liệu "định dạng nào tốt hơn video" không có hành động tương ứng nào trong 1 tool CHỈ làm video.
 *
 * **Từ chối áp dụng, có lý do (còn lại của nguồn):** "1-2 nền tảng làm kỹ hơn 4-5 nền tảng làm mỏng"/
 * kiểm toán đối thủ/content pillar — chiến lược cấp KÊNH, không phải cấp 1 Project/video đơn lẻ; SMART
 * goal — đã có `objective` (Creative Brief, v1.2) đúng vai trò mục tiêu kinh doanh, không cần khung
 * SMART chi tiết hơn cho 1 video đơn; lịch đăng/tần suất/đo lường hiệu suất — trùng quyết định "không
 * theo dõi hiệu suất phân phối" đã chốt nhiều lần (§10); "3 lựa chọn dùng AI" (soạn thảo VỚI AI, không
 * xuất bản THẲNG TỪ AI) — đúng triết lý module đã có sẵn từ v1.0 (copy tay sang tool ngoài + 2 checklist
 * trước/sau generate, §0/§3.5), không phải khoảng trống; SEO caption/searchable phrases — thuộc siêu dữ
 * liệu XUẤT BẢN (tiêu đề/mô tả video khi đăng), khác phạm vi "Director Prompt Template" (nội dung HÌNH
 * ẢNH của shot, §1), cùng nhóm đã loại ở v1.16 ("module không host/index video" — video SEO).
 */
class CompileProjectDirectorPromptAction
{
    use AsAction;

    /**
     * Checklist đánh giá SAU khi có kết quả AI — Hedra Step 3 "Generate First Draft" (v1.2) + 3 mục
     * đặc thù marketing từ bài LinkedIn "step-by-step guide creating AI marketing videos" (v1.6).
     */
    private const QC_CHECKLIST = [
        'Đúng chủ thể/nhân vật (subject accuracy)',
        'Chuyển động mượt, không giật/lỗi khung hình (motion smoothness)',
        'Góc máy đúng ý đồ (camera angle effectiveness)',
        'Đúng phong cách/thương hiệu xuyên suốt (style consistency)',
        'Không có artifact/lỗi hình ảnh lạ (visual artifacts)',
        'Thời lượng khớp tỷ lệ khung hình của nền tảng phát hành (v1.6)',
        'Nhất quán thương hiệu — vị trí logo, đúng bảng màu đã khai (v1.6)',
        'Âm thanh rõ ràng, không tạp âm nền (v1.6)',
        // v1.14 (imagine.art) — phụ đề mờ/nhỏ là 1 trong 7 lỗi phổ biến nguồn liệt kê; 80% video xem
        // không tiếng (đã ghi ở khung thời gian mẫu) nên phụ đề PHẢI đọc được, không chỉ "có mặt".
        'Phụ đề (nếu có) đủ lớn, tương phản cao, đọc được trên nền video (v1.14)',
    ];

    /** Checklist rà lại prompt TRƯỚC khi chạy trên tool AI ngoài — deepreel.com (v1.3). */
    private const PRE_GENERATION_CHECKLIST = [
        'Subject đã mô tả cụ thể (không còn từ chung chung như "một người phụ nữ")',
        // v1.8 (sentx.ai "Single hero focus") — khác nguyên tắc "1 shot = 1 hành động" đã có: đây
        // giới hạn SỐ chủ thể trong khung hình, không phải số hành động.
        'Chỉ 1 chủ thể chính + tối đa 1 yếu tố phụ trong khung hình (không dồn nhiều người/vật cùng lúc)',
        'Style/phong cách đã xác định rõ',
        'Camera (loại cảnh + chuyển động) đã điền',
        'Mood/tâm trạng đã xác định',
        'Ánh sáng/lighting đã mô tả (thường nằm trong Style/Constraints)',
        'Duration/thời lượng đã điền nếu cần',
        'Không có yêu cầu đối lập nhau trong cùng 1 shot (VD "vừa dữ dội vừa yên bình")',
        'Yêu cầu khả thi về mặt vật lý/hình ảnh (không mâu thuẫn logic)',
    ];

    /**
     * v1.8 (sentx.ai "Troubleshooting by Slot") — map triệu chứng output SAI sang field cần sửa; dùng
     * SAU khi checklist QC (`QC_CHECKLIST`) phát hiện có vấn đề, để biết sửa ở đâu trước khi tạo lại.
     *
     * @var array<string, string> triệu chứng => cách sửa
     */
    private const TROUBLESHOOTING_GUIDE = [
        'Video tĩnh, gần như không có chuyển động' => 'Làm rõ động từ hành động cụ thể ở Action + thêm 1 chỉ dẫn chuyển động máy ở Camera (đẩy chậm, lia ngang, tracking...) — thiếu 1 trong 2 dễ ra kết quả tĩnh.',
        'Hình ảnh hỗn loạn, méo/biến dạng giữa khung hình (morphing)' => 'Giảm về đúng 1 chủ thể + 1 hành động ở Subject/Action, rút ngắn Duration — quá nhiều yêu cầu trong 1 shot ngắn khiến model không xử lý hết.',
        'Sai tâm trạng hoặc màu sắc so với ý muốn' => 'Gọi tên RÕ nguồn sáng + thời điểm trong ngày ở Style/Mood (VD "nắng vàng lúc hoàng hôn" thay vì "ánh sáng đẹp", "ánh trăng lạnh" thay vì "tối") — ánh sáng quyết định tông màu và cảm xúc của cảnh.',
        'Model bỏ qua yêu cầu Style' => 'Không để Style là ý phụ ở cuối câu — gắn nó với đặc điểm cụ thể (chất liệu film, kiểu ánh sáng, tông màu) chứ không chỉ 1 nhãn chung như "cinematic".',
        'Nhân vật/sản phẩm bị lệch (identity drift) giữa các shot trong cùng project' => 'Dùng lại NGUYÊN VĂN các đặc điểm nhận diện cố định (màu tóc, trang phục, phụ kiện) đã khai ở Subject mặc định — không diễn đạt lại theo cách khác giữa các shot; kèm Ảnh tham chiếu (anchor) nếu cần chắc hơn.',
    ];

    /**
     * v1.9 (veed.io "Platform-Specific Strategies") — map `aspect_ratio` (đã có sẵn từ v1.2/v1.5,
     * `AiVideoStudioProject::ASPECT_RATIOS`) sang gợi ý viết prompt riêng cho định dạng đó. CHỈ 2
     * khoá vì nguồn chỉ nói rõ 2 định dạng này — không đoán thêm tip cho `1:1`/`4:5`.
     *
     * v1.21 (buffer.com/resources/social-media-marketing-strategy) — enrich CẢ 2 tip với mục tiêu
     * TỐI ƯU HOÁ CỦA THUẬT TOÁN nền tảng (bảng "thuật toán ưu tiên gì" của nguồn: TikTok = thời gian
     * xem + tỷ lệ hoàn thành; YouTube = thời gian xem + tỷ lệ nhấp) — góc HOÀN TOÀN KHÁC tip gốc v1.9
     * (chỉ nói cách mở đầu/hook, không nói gì về việc GIỮ nhịp xuyên suốt để xem hết). Không trùng
     * "Slow down" (v1.18, thiên kiến pacing chủ quan của người viết prompt) hay "single hero focus"
     * (v1.8, giới hạn SỐ chủ thể) — đây là 1 trục khác: nhịp độ PHÂN BỔ theo THỜI LƯỢNG, không phải
     * tốc độ tuyệt đối hay số lượng chủ thể.
     *
     * @var array<string, string>
     */
    private const PLATFORM_TIPS_BY_ASPECT_RATIO = [
        '9:16' => 'TikTok/Reels/Shorts: 1-2 giây đầu PHẢI có hành động/phản ứng gây chú ý ngay (hook) — đừng mở đầu chậm; ưu tiên mô tả cắt nhanh, zoom-in, nhấn cá tính/chuyển động nhân vật. Thuật toán các nền tảng này ưu tiên THỜI GIAN XEM + TỶ LỆ XEM HẾT (completion rate) — hook mạnh chỉ giữ được người xem 1-2 giây đầu, còn lại phụ thuộc nhịp độ CÓ ĐỀU xuyên suốt hay không: nếu nhiều shot nối tiếp nhau, đừng để phần giữa/cuối đuối nhịp so với đầu — Action/Camera của MỌI shot nên giữ mức năng lượng gần bằng nhau, không chỉ dồn lực vào shot mở đầu.',
        '16:9' => 'YouTube: nội dung giáo dục — nêu rõ kiểu người dẫn/kể (narrator) + hình thức hiển thị + chỉ dẫn text overlay; nội dung hướng dẫn từng bước (tutorial) — mô tả khung có tay/vật thể đang thực hiện (hands-in-frame); kể chuyện thương hiệu — mô tả rõ mạch cảm xúc, tiến triển cảnh, kiểu chuyển cảnh điện ảnh. Thuật toán ưu tiên THỜI GIAN XEM + TỶ LỆ NHẤP (click-through rate) — với video nhiều shot, tránh đặt các shot ít thông tin/lặp lại liên tiếp gây rớt người xem giữa chừng; mỗi shot nên có LÝ DO tồn tại rõ ràng (tiến triển nội dung, không chỉ đẹp mắt) để giữ thời gian xem cộng dồn.',
    ];

    /**
     * v1.9 (veed.io "Platform-Specific Strategies" — nhóm Business/Commercial) — map `video_type`
     * (đã có sẵn từ v1.6, `AiVideoStudioProject::VIDEO_TYPES`) sang gợi ý viết prompt riêng cho loại
     * nội dung đó. Bỏ qua `other` vì nguồn không có gợi ý chung nào phù hợp để gán vào.
     *
     * v1.14 (imagine.art "make-ai-marketing-videos") — thêm tip cho 2 loại mới `spokesperson`/
     * `offer_promo` (§4.1) ngay khi thêm option, tránh lặp lại tình trạng field có mà chưa có tip
     * khớp (đã xảy ra với `other` từ v1.6 tới giờ, chấp nhận vì nguồn không có gợi ý chung phù hợp).
     *
     * v1.17 — đổi `public` để `show.blade.php` đọc lại được (khối "Gợi ý mô tả theo Loại video &
     * Công thức kịch bản", §8), tránh chép lại 6 dòng tip này lần 2 trong view.
     *
     * @var array<string, string>
     */
    public const CONTENT_TIPS_BY_VIDEO_TYPE = [
        'explainer' => 'Nêu rõ lợi ích/thông tin chính cần truyền đạt + hình thức hiển thị (talking head/screen recording/hoạt hình).',
        'testimonial' => 'Mô tả rõ bối cảnh người chia sẻ (ở đâu, đang làm gì) + vị trí đặt call-to-action trong cảnh.',
        // v1.18 (ngram.com/blog/how-to-make-demo-video — "show one feature, one workflow, one
        // result") — bổ sung giới hạn SỐ tính năng trình diễn, tránh liệt kê hết mọi tính năng làm
        // loãng thông điệp; nguyên bản chỉ có gợi ý hình ảnh (ánh sáng/góc máy/nhạc nền).
        'product_demo' => 'Nêu rõ lợi ích chính của sản phẩm cần lên hình + kiểu ánh sáng, góc máy, tâm trạng nhạc nền phù hợp. Nếu demo nhiều tính năng: giới hạn 2-5 tính năng chính, mỗi tính năng gắn với 1 lợi ích cụ thể — show, don\'t tell, tránh liệt kê hết mọi tính năng làm loãng thông điệp.',
        'storytelling' => 'Mô tả mạch cảm xúc xuyên suốt, tiến triển cảnh theo trình tự, phong cách lời dẫn, kiểu chuyển cảnh điện ảnh.',
        'spokesperson' => 'Mô tả rõ người nói nhìn thẳng vào camera (không phải góc nghiêng/quay lưng), giọng điệu tự tin/gần gũi tuỳ thương hiệu; sản phẩm nên xuất hiện trong khung hình cùng người nói, không chỉ nhắc bằng lời.',
        'offer_promo' => 'Nhấn mạnh RÕ con số/thời hạn cụ thể trong Constraints hoặc CTA (VD "giảm 25%, chỉ tuần này") — nhịp cắt nhanh, phụ đề in đậm; đây là loại video ưu tiên tốc độ truyền tải hơn cảm xúc/câu chuyện.',
        // v1.20 (leadde.ai/blog/marketing-script-template) — video_type MỚI, không có nguồn tip nào
        // trước đó phủ được use-case retention này.
        'onboarding' => 'Mở đầu chào mừng khách hàng MỚI (không phải khách tiềm năng) + cho thấy NGAY 1 "quick win" để giữ động lực; phần chính hướng dẫn từng bước cụ thể (numbered steps, show-don\'t-tell) + cảnh báo lỗi/hiểu lầm thường gặp; CTA cuối là nơi tìm hỗ trợ thêm, KHÔNG phải CTA bán hàng.',
    ];

    /**
     * v1.16 (tulsainternetmarketingservice.com/blog/video-marketing-formulas) — map `video_formula`
     * (Model §4.1) sang cấu trúc kịch bản + khoảng thời lượng gợi ý của nguồn. Không có khoá `other`
     * (Model không định nghĩa) — mọi giá trị trong `VIDEO_FORMULAS` đều có tip khớp, khác
     * `CONTENT_TIPS_BY_VIDEO_TYPE` (bỏ qua `other`).
     *
     * v1.25 (§13, "Cố vấn & Sinh Master Prompt Kịch bản") — đổi `private` → `public` để
     * `BuildMasterScriptPromptAction` tái dùng đúng 1 nguồn định nghĩa 7 công thức khi liệt kê trong
     * Master Prompt, cùng lý do/pattern đã áp dụng cho `CONTENT_TIPS_BY_VIDEO_TYPE` ở v1.17 (tránh
     * chép lại nội dung — chép tay từng gây lệch định nghĩa ABCD ở bản nháp người dùng cung cấp,
     * xem §13.0).
     *
     * @var array<string, string>
     */
    public const FORMULA_TIPS_BY_VIDEO_FORMULA = [
        'psa' => 'Problem–Solution–CTA (30-60s): mở đầu nêu RÕ nỗi đau/vấn đề khán giả đang gặp → phần giữa cho thấy sản phẩm/dịch vụ giải quyết vấn đề đó thế nào → kết thúc mời hành động cụ thể. Phù hợp video quảng cáo sản phẩm, bản demo.',
        'bab' => 'Before–After–Bridge (60-90s): mở đầu mô tả tình trạng TRƯỚC khi có sản phẩm → phần giữa cho thấy kết quả/thành công SAU khi dùng → kết thúc giải thích sản phẩm là "cầu nối" tạo ra chuyển đổi đó. Phù hợp lời chứng thực (testimonial), câu chuyện chuyển đổi.',
        'hook_value_cta' => 'Hook–Value–CTA (15-45s): 1-2 giây đầu gây chú ý bằng phát biểu/hình ảnh mạnh (hook) → phần giữa truyền đạt 1 mẹo/insight hữu ích (value) → kết thúc mời hành động cụ thể. Phù hợp nội dung giáo dục, chia sẻ chuyên môn, mẹo/chiến lược.',
        // v1.18 (ngram.com/blog/demo-video-script-template — "5-Part Demo Video Script Framework") —
        // 5 nhịp thay vì 3, có nhịp Hook riêng biệt với Problem (PSA gộp chung) và nhịp "Key Features
        // in Action" trình diễn NHIỀU tính năng (PSA chỉ có 1 nhịp Solution chung). Dài hơn PSA
        // (90-120s so với 30-60s) — phù hợp demo có nhiều thứ cần trình diễn hơn 1 quảng cáo ngắn.
        'demo_5part' => 'Demo Script 5 phần (90-120s): Hook (pain point cụ thể, 5-10s) → Problem Deep-dive (hậu quả/chi phí nếu không giải quyết, 10-30s) → Solution Introduction (nối RÕ với vấn đề vừa nêu, 30-50s) → Key Features in Action (trình diễn 2-5 tính năng, show-don\'t-tell, 50-90s+) → CTA & Outcome (1 CTA + 1 kết quả cụ thể, 90-120s). Phù hợp video demo sản phẩm nhiều tính năng, dài hơi hơn PSA.',
        // v1.20 (leadde.ai/blog/marketing-script-template) — 3 công thức mới, xem docblock class để
        // biết lý do không trùng lặp với 4 công thức trên.
        'abcd' => 'ABCD — Attention-Branding-Connection-Direction: framework của Google cho YouTube Ads (pre-roll/in-stream, người xem có thể bấm "Bỏ qua quảng cáo" sau 5s). Attention (gây chú ý NGAY vài giây đầu) → Branding (giới thiệu thương hiệu/sản phẩm SỚM — khác 4 công thức kia thường để cuối) → Connection (kết nối trực tiếp với vấn đề/mong muốn của khán giả mục tiêu) → Direction (1 CTA rõ ràng). Phù hợp quảng cáo YouTube có thể bị bỏ qua giữa chừng.',
        'testimonial_5part' => 'Testimonial Script 5 phần: Before (cuộc sống/tình huống TRƯỚC khi biết sản phẩm) → Challenge (khó khăn CỤ THỂ đã gặp phải, tách riêng khỏi Before) → Solution (cách họ tìm ra/áp dụng sản phẩm) → Result (kết quả cụ thể đạt được, càng nhiều số liệu càng thuyết phục) → Recommendation (lời khuyên trực tiếp tới người xem, giọng điệu cá nhân — khác "Bridge" của BAB vốn là góc nhìn sản phẩm). Phù hợp video chứng thực/case study chi tiết hơn BAB.',
        'onboarding_5part' => 'Onboarding Script 5 phần: Welcome (chào mừng khách hàng MỚI, xác nhận họ chọn đúng) → First value (cho thấy NGAY 1 kết quả/quick win để duy trì động lực) → Steps (hướng dẫn từng bước cụ thể, numbered, show-don\'t-tell — phần nặng nhất) → Mistakes (cảnh báo lỗi/hiểu lầm thường gặp cần tránh) → Support CTA (hướng dẫn nơi tìm hỗ trợ thêm, KHÔNG phải CTA bán hàng). Phù hợp video đào tạo/SOP/hướng dẫn sử dụng — công thức RETENTION đầu tiên của module, khác 4 công thức trên đều hướng tới khách CHƯA MUA.',
    ];

    /**
     * v1.17 (phản hồi người dùng — chọn Loại video + Công thức kịch bản thì cần gợi ý CÁCH VIẾT MÔ TẢ
     * cho từng nhịp, không chỉ 1 câu tóm tắt như `FORMULA_TIPS_BY_VIDEO_FORMULA`): breakdown mỗi công
     * thức thành 3 nhịp (name/duration/guide) — hiển thị dạng bảng ở `show.blade.php` (§8), ngay dưới
     * phần mở đầu section "Kịch bản & Timeline", TRƯỚC danh sách Shot — để người dùng biết chia N shot
     * của mình thành mấy cụm và viết Subject/Action/Script mỗi cụm theo hướng nào, TRƯỚC khi gõ.
     * `duration` là tỉ lệ % gợi ý trong khoảng tổng thời lượng đã nêu ở `FORMULA_TIPS_BY_VIDEO_FORMULA`
     * (VD PSA tổng 30-60s) — KHÔNG phải số giây cố định, vì tổng thời lượng thật phụ thuộc
     * `duration_seconds` người dùng tự điền cho từng shot (không có field riêng nào lưu lại N nhịp
     * này — thuần là bảng hướng dẫn, KHÔNG vào `compiled_prompt`/tài liệu xuất, khác `FORMULA_TIPS_
     * BY_VIDEO_FORMULA` vẫn dùng cho khối Creative Brief đã có). v1.18 (`demo_5part`) — ngoại lệ: giữ
     * NGUYÊN số giây cụ thể nguồn cho (5-10s, 10-30s...) thay vì quy đổi %, đúng tinh thần "Include
     * Specific Numbers" của chính nguồn ngram.com — số giây cụ thể vẫn chỉ là gợi ý tham khảo, không
     * ép buộc khớp đúng `duration_seconds` thật của shot.
     *
     * @var array<string, array<int, array{name: string, duration: string, guide: string}>>
     */
    public const FORMULA_BEATS = [
        'psa' => [
            ['name' => 'Vấn đề (Problem)', 'duration' => '~25-35% đầu', 'guide' => 'Subject/Action mô tả RÕ tình huống khó chịu/nỗi đau khán giả đang gặp — CHƯA cho sản phẩm xuất hiện.'],
            ['name' => 'Giải pháp (Solution)', 'duration' => '~45-55% giữa', 'guide' => 'Subject chuyển sang sản phẩm/dịch vụ; Action cho thấy TRỰC QUAN nó giải quyết đúng vấn đề vừa nêu như thế nào.'],
            ['name' => 'Kêu gọi hành động (CTA)', 'duration' => '~15-25% cuối', 'guide' => 'Điền field CTA với hành động cụ thể, có thể kèm ưu đãi/thời hạn để tăng tính khẩn cấp.'],
        ],
        'bab' => [
            ['name' => 'Trước (Before)', 'duration' => '~25-35% đầu', 'guide' => 'Subject/Action mô tả cuộc sống/kết quả TRƯỚC khi có sản phẩm — nêu rõ khó khăn/thiếu sót, giọng điệu chân thực (phù hợp testimonial).'],
            ['name' => 'Sau (After)', 'duration' => '~35-45% giữa', 'guide' => 'Mô tả kết quả/thành công SAU khi dùng sản phẩm — càng cụ thể (số liệu, cảm xúc rõ ràng) càng thuyết phục.'],
            ['name' => 'Cầu nối (Bridge)', 'duration' => '~20-30% cuối', 'guide' => 'Giải thích RÕ sản phẩm là "cầu nối" tạo ra chuyển đổi Before→After đó; có thể lồng CTA nhẹ ở field CTA.'],
        ],
        'hook_value_cta' => [
            ['name' => 'Hook', 'duration' => '~10-15% đầu (1-2 giây)', 'guide' => 'Subject/Action gây chú ý NGAY bằng 1 phát biểu/hình ảnh mạnh — pattern-interrupt hoặc tuyên bố táo bạo, đừng mở đầu chậm.'],
            ['name' => 'Giá trị (Value)', 'duration' => '~55-65% giữa', 'guide' => 'Truyền đạt 1 mẹo/insight hữu ích, cụ thể và khả thi ngay — đây là phần "cho đi" trước khi kêu gọi hành động.'],
            ['name' => 'CTA', 'duration' => '~20-30% cuối', 'guide' => 'Điền field CTA với hành động cụ thể (theo dõi, tải về, đăng ký...).'],
        ],
        // v1.18 (ngram.com/blog/demo-video-script-template) — 5 nhịp thay vì 3; nguồn cho số giây cụ
        // thể (không chỉ tỉ lệ %) cho 1 video mẫu ~120s, giữ nguyên số giây thay vì quy đổi % vì đúng
        // tinh thần "Include Specific Numbers" của chính nguồn này.
        'demo_5part' => [
            ['name' => 'Hook', 'duration' => '5-10s (đầu)', 'guide' => 'Subject/Action mở bằng 1 pain point CỤ THỂ, không phải liệt kê tính năng — VD "quản lý phản hồi khách hàng qua nhiều kênh tốn hàng giờ mỗi tuần".'],
            ['name' => 'Đào sâu vấn đề (Problem Deep-dive)', 'duration' => '10-30s', 'guide' => 'Mở rộng hậu quả/chi phí nếu KHÔNG giải quyết vấn đề — càng cụ thể càng thuyết phục (VD "mất 2-3 giờ/ngày chỉ để sắp xếp").'],
            ['name' => 'Giới thiệu giải pháp (Solution Introduction)', 'duration' => '30-50s', 'guide' => 'Subject chuyển sang sản phẩm; nối RÕ ràng với vấn đề vừa nêu — không nhảy cóc sang tính năng ngay.'],
            ['name' => 'Trình diễn tính năng (Key Features in Action)', 'duration' => '50-90s+', 'guide' => 'Trình diễn 2-5 tính năng chính, MỖI tính năng gắn với 1 lợi ích cụ thể — show, don\'t tell (hình ảnh thay vì chỉ mô tả bằng lời).'],
            ['name' => 'CTA & Kết quả (Outcome)', 'duration' => '90-120s (cuối)', 'guide' => 'Điền field CTA với 1 hành động tiếp theo RÕ RÀNG + 1 kết quả cụ thể — tránh mơ hồ (VD không chỉ "Cảm ơn đã xem").'],
        ],
        // v1.20 (leadde.ai/blog/marketing-script-template) — 3 breakdown mới, cùng cơ chế v1.17/v1.18.
        'abcd' => [
            ['name' => 'Attention', 'duration' => '~15-20% đầu (vài giây)', 'guide' => 'Subject/Action mở bằng hình ảnh/tình huống gây chú ý NGAY (pattern-interrupt) — quảng cáo YouTube có thể bị bấm "Bỏ qua" sau 5s, không có thời gian khởi động chậm.'],
            ['name' => 'Branding', 'duration' => '~10-20%, ngay sau Attention', 'guide' => 'Cho logo/sản phẩm/thương hiệu xuất hiện SỚM trong Subject/Environment — đừng đợi tới CTA cuối mới lộ diện thương hiệu, khác thói quen ở PSA/BAB/Hook-Value-CTA.'],
            ['name' => 'Connection', 'duration' => '~30-40% giữa', 'guide' => 'Action/Lời thoại kết nối trực tiếp với vấn đề/mong muốn của khán giả mục tiêu (`target_audience`) — cho họ thấy video đang "nói về họ".'],
            ['name' => 'Direction', 'duration' => '~20-30% cuối', 'guide' => 'Điền field CTA với 1 hành động cụ thể, rõ ràng.'],
        ],
        'testimonial_5part' => [
            ['name' => 'Trước (Before)', 'duration' => '~15-20% đầu', 'guide' => 'Subject/Action mô tả tình huống/cuộc sống của khách hàng TRƯỚC khi biết tới sản phẩm — giọng điệu chân thực.'],
            ['name' => 'Khó khăn (Challenge)', 'duration' => '~15-20%', 'guide' => 'Nêu rõ khó khăn/rào cản CỤ THỂ họ từng gặp phải — tách riêng khỏi nhịp Before, không gộp chung như BAB.'],
            ['name' => 'Giải pháp (Solution)', 'duration' => '~20-25%', 'guide' => 'Mô tả họ tìm ra/bắt đầu dùng sản phẩm như thế nào.'],
            ['name' => 'Kết quả (Result)', 'duration' => '~20-25%', 'guide' => 'Kết quả cụ thể đạt được — càng nhiều số liệu/chi tiết càng thuyết phục.'],
            ['name' => 'Giới thiệu (Recommendation)', 'duration' => '~15-20% cuối', 'guide' => 'Lời khuyên/giới thiệu trực tiếp tới người xem từ góc nhìn NGƯỜI ĐƯỢC PHỎNG VẤN — giọng điệu cá nhân, khác Bridge của BAB (góc nhìn sản phẩm).'],
        ],
        'onboarding_5part' => [
            ['name' => 'Chào mừng (Welcome)', 'duration' => '~10-15% đầu', 'guide' => 'Chào mừng khách hàng MỚI, xác nhận họ đã đưa ra lựa chọn đúng — giọng điệu thân thiện, không phải bán hàng.'],
            ['name' => 'Giá trị đầu tiên (First value)', 'duration' => '~15-20%', 'guide' => 'Cho thấy NGAY 1 kết quả/quick win họ có thể đạt được sớm, để duy trì động lực học tiếp.'],
            ['name' => 'Các bước (Steps)', 'duration' => '~35-45% giữa', 'guide' => 'Hướng dẫn từng bước cụ thể (numbered), show-don\'t-tell — mỗi bước 1 hành động rõ ràng; đây là phần nặng nhất của công thức.'],
            ['name' => 'Lỗi thường gặp (Mistakes)', 'duration' => '~10-15%', 'guide' => 'Cảnh báo lỗi/hiểu lầm phổ biến cần tránh khi thực hiện các bước trên.'],
            ['name' => 'CTA hỗ trợ (Support CTA)', 'duration' => '~10-15% cuối', 'guide' => 'Điền field CTA với nơi tìm hỗ trợ thêm (kênh hỗ trợ, tài liệu, liên hệ) — KHÔNG phải CTA bán hàng.'],
        ],
    ];

    public function handle(AiVideoStudioProject $project): string
    {
        $shots = $project->shots()->orderBy('sort_order')->get();

        $header = $this->buildCreativeBriefBlock($project, $shots);

        $sections = $shots->map(function (AiVideoStudioShot $shot, int $index) {
            $heading = '## Shot '.($index + 1).($shot->label ? " — {$shot->label}" : '');
            $body = $shot->compiled_prompt ?: '_(chưa có prompt — điền field còn thiếu)_';
            // v1.10 — quy trình 2 bước Image-to-Video (BuildShotPromptAction::buildImagePrompt/
            // buildMotionPrompt), chỉ xuất hiện nếu shot có đủ field liên quan để build 2 prompt này.
            $imagePrompt = filled($shot->image_prompt) ? "\n\n**Prompt Ảnh (keyframe):**\n{$shot->image_prompt}" : '';
            $motionPrompt = filled($shot->motion_prompt) ? "\n\n**Prompt Motion (hoạt hình hoá ảnh):**\n{$shot->motion_prompt}" : '';
            $modelTool = filled($shot->model_tool) ? "\n\n**Model/Tool đã dùng:** {$shot->model_tool}" : '';
            $referenceAssets = filled($shot->reference_assets) ? "\n\n**Tài liệu tham chiếu bổ sung:** {$shot->reference_assets}" : '';
            $result = filled($shot->ai_result) ? "\n\n**Kết quả AI:**\n{$shot->ai_result}" : '';
            $qcNotes = filled($shot->qc_notes) ? "\n\n**Ghi chú đánh giá (QC):** {$shot->qc_notes}" : '';

            return "{$heading}\n\n{$body}{$imagePrompt}{$motionPrompt}{$modelTool}{$referenceAssets}{$result}{$qcNotes}";
        });

        return "# Director Prompt Template — {$project->name}\n\n{$header}".$sections->implode("\n\n---\n\n");
    }

    /**
     * Creative Brief (§0 v1.2) + tổng thời lượng ước tính (v1.3) + 2 checklist (trước/sau generate)
     * — chỉ in field NÀO CÓ giá trị, bỏ qua rỗng.
     */
    private function buildCreativeBriefBlock(AiVideoStudioProject $project, Collection $shots): string
    {
        $brief = [];
        if (filled($project->objective)) {
            $brief[] = "- **Mục tiêu:** {$project->objective}";
        }
        if (filled($project->target_audience)) {
            $brief[] = "- **Đối tượng khán giả:** {$project->target_audience}";
        }
        if (filled($project->video_type)) {
            // v1.14 — sửa lỗi in slug thô (vd "product_demo") thay vì nhãn đẹp; spec §11 (v1.7) đã
            // ghi nhận hành vi ĐÚNG này từ trước nhưng code trước đó lệch, phát hiện khi rà soát lại.
            $brief[] = "- **Loại video:** {$project->videoTypeLabel()}";
            // v1.9 (veed.io) — chỉ in nếu CÓ tip khớp; bỏ qua `other` (nguồn không có gợi ý phù hợp).
            if ($tip = self::CONTENT_TIPS_BY_VIDEO_TYPE[$project->video_type] ?? null) {
                $brief[] = "- **Gợi ý theo loại video:** {$tip}";
            }
        }
        // v1.16 (tulsainternetmarketingservice.com) — trục KHÁC video_type (cấu trúc kể chuyện, không
        // phải loại nội dung), đặt ngay sau khối video_type vì cùng nhóm "định hình mạch video".
        if (filled($project->video_formula)) {
            $brief[] = "- **Công thức kịch bản:** {$project->videoFormulaLabel()}";
            if ($tip = self::FORMULA_TIPS_BY_VIDEO_FORMULA[$project->video_formula] ?? null) {
                $brief[] = "- **Cấu trúc gợi ý:** {$tip}";
            }
        }
        if (filled($project->core_message)) {
            $brief[] = "- **Thông điệp cốt lõi:** {$project->core_message}";
        }
        if (filled($project->aspect_ratio)) {
            $brief[] = "- **Tỷ lệ khung hình:** {$project->aspect_ratio}";
            // v1.9 (veed.io) — chỉ in nếu CÓ tip khớp (9:16/16:9); im lặng bỏ qua với 1:1/4:5.
            if ($tip = self::PLATFORM_TIPS_BY_ASPECT_RATIO[$project->aspect_ratio] ?? null) {
                $brief[] = "- **Gợi ý theo nền tảng:** {$tip}";
            }
        }
        if (filled($project->resolution)) {
            $brief[] = "- **Độ phân giải:** {$project->resolution}";
        }
        if (filled($project->reference_image_url)) {
            $brief[] = "- **Ảnh tham chiếu (anchor):** {$project->reference_image_url}";
        }

        $totalDuration = $shots->sum('duration_seconds');
        $shotsWithDuration = $shots->whereNotNull('duration_seconds')->count();
        if ($shotsWithDuration > 0) {
            $brief[] = "- **Tổng thời lượng ước tính:** {$totalDuration} giây ({$shotsWithDuration}/{$shots->count()} shot có điền thời lượng)";
        }

        $preChecklist = implode("\n", array_map(fn (string $item) => "- [ ] {$item}", self::PRE_GENERATION_CHECKLIST));
        $qcChecklist = implode("\n", array_map(fn (string $item) => "- [ ] {$item}", self::QC_CHECKLIST));

        $sections = [];
        if ($brief !== []) {
            $sections[] = "## Creative Brief\n\n".implode("\n", $brief);
        }
        $sections[] = "## Checklist trước khi generate (mỗi shot, ngay trước khi dán prompt sang tool AI)\n\n{$preChecklist}";
        $sections[] = "## Checklist đánh giá output (mỗi shot, sau khi có kết quả AI, trước khi chấp nhận)\n\n{$qcChecklist}";
        $sections[] = $this->buildTroubleshootingBlock();

        if ($edl = $this->buildEdlBlock($shots)) {
            $sections[] = $edl;
        }

        return implode("\n\n", $sections)."\n\n---\n\n";
    }

    /**
     * v1.15 (mindstudio.ai — Agent 2/4 "Voiceover → Assembly") — bảng EDL cộng dồn thời gian từ
     * `duration_seconds` (giống `renderTimeline()`/timeline strip ở `show.blade.php`), đối chiếu với
     * `script_line`, dùng lúc dựng/ghép video hoặc thu voiceover khớp từng shot.
     */
    private function buildEdlBlock(Collection $shots): ?string
    {
        if ($shots->isEmpty() || $shots->every(fn (AiVideoStudioShot $shot) => blank($shot->duration_seconds) && blank($shot->script_line))) {
            return null;
        }

        $rows = ['| Cảnh | Thời gian | Lời thoại | Mô tả hình ảnh |', '|---|---|---|---|'];
        $cursor = 0;

        foreach ($shots as $index => $shot) {
            $shotLabel = 'Cảnh '.($index + 1).($shot->label ? ' — '.str_replace('|', '/', $shot->label) : '');

            if (filled($shot->duration_seconds)) {
                $time = "{$cursor}–".($cursor + $shot->duration_seconds).'s';
                $cursor += $shot->duration_seconds;
            } else {
                $time = "{$cursor}s+ (?)";
            }

            $scriptLine = filled($shot->script_line) ? '"'.str_replace(["\r\n", "\n", '|'], [' ', ' ', '/'], trim($shot->script_line)).'"' : '_(chưa có lời thoại)_';
            $visual = $shot->label ?: (Str::limit(trim("{$shot->subject} {$shot->action}"), 60) ?: '_(chưa có mô tả)_');
            $visual = str_replace('|', '/', $visual);

            $rows[] = "| {$shotLabel} | {$time} | {$scriptLine} | {$visual} |";
        }

        return "## EDL — Bảng đối chiếu Lời thoại & Thời gian (Edit Decision List)\n\n"
            ."Dùng khi thu giọng đọc (voiceover) hoặc dựng/ghép video — canh từng shot khớp đúng mốc thời gian và lời thoại. \"(?)\" nghĩa là shot chưa điền Thời lượng, mốc thời gian sau đó chỉ là ước lượng.\n\n"
            .implode("\n", $rows);
    }

    /**
     * v1.8 (sentx.ai "Troubleshooting by Slot") — khối tĩnh, không phụ thuộc dữ liệu project/shot,
     * đặt SAU checklist QC vì đây là bước tiếp theo khi checklist đó phát hiện có vấn đề.
     */
    private function buildTroubleshootingBlock(): string
    {
        $items = [];
        foreach (self::TROUBLESHOOTING_GUIDE as $symptom => $fix) {
            $items[] = "- **{$symptom}** → {$fix}";
        }

        return "## Xử lý sự cố theo triệu chứng (nếu output AI chưa đúng ý, trước khi tạo lại)\n\n".implode("\n", $items);
    }
}
