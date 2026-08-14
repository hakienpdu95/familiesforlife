# AI Video Studio Template — Quản lý Director Prompt Template cho video AI

**Đặc tả Kỹ thuật Chi tiết — ĐÃ triển khai (v1.0/v1.1); v1.2-v1.6 bổ sung techniques từ Hedra/DeepReel/BytePlus/Pyxeljam/LinkedIn; v1.7 rà soát nội bộ; v1.8 bổ sung từ sentx.ai; v1.9 bổ sung từ veed.io; v1.10-v1.13 xem tóm tắt bên dưới; v1.14-v1.15 bổ sung từ mindstudio.ai + imagine.art; v1.16 bổ sung từ tulsainternetmarketingservice.com + swarmify.com; v1.17 UI theo phản hồi người dùng; v1.18 bổ sung từ ngram.com (2 bài); v1.19 UI theo phản hồi người dùng; v1.20 bổ sung từ leadde.ai; v1.21 bổ sung từ buffer.com, xem changelog dưới**

**Phiên bản:** 1.21
**Ngày:** 2026-08-14
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Module:** `Modules/AIVideoStudioTemplate`

> **v1.21 (2026-08-14 — đọc `buffer.com/resources/social-media-marketing-strategy`, rà soát kỹ thuật
> còn thiếu so với v1.20, theo yêu cầu áp dụng cho module + rà soát cả hệ thống):** nguồn là hướng
> dẫn 7 bước xây dựng CHIẾN LƯỢC social media tổng thể (kiểm toán tài khoản → định nghĩa khán giả
> tổng quát → mục tiêu SMART → chọn nền tảng → content pillar → lịch đăng/tần suất → đo lường/xoay
> trục hàng tháng-quý) — bản chất là 1 công cụ QUẢN LÝ TÀI KHOẢN MẠNG XÃ HỘI (Buffer chính là tool
> lịch đăng/phân tích). Rà soát cả hệ thống (không chỉ module này) không tìm thấy nơi nào tương ứng:
> `ContentCalendar` (spec riêng, §16 "Ngoài phạm vi Phase 1") đã tường minh loại "đa kênh (repurpose
> sang social/newsletter)" khỏi phạm vi — module đó chỉ quản lý pipeline biên tập bài viết CỦA NỀN
> TẢNG (`Post`), không đăng/lên lịch lên tài khoản mạng xã hội ngoài; module này (`AIVideoStudioTemplate`)
> chỉ dựng PROMPT cho 1 video/TVC đơn lẻ, không quản lý tài khoản/lịch đăng/KPI của cả kênh — đã CỐ Ý
> không theo dõi hiệu suất phân phối từ v1.5/v1.10/v1.14/v1.16 (§10, không mở lại). Vì vậy phần lớn 7
> bước (kiểm toán, audience tổng quát, SMART goal, content pillar, lịch đăng/tần suất, đo lường) KHÔNG
> áp dụng — không có chỗ hợp lý nào trong hệ thống hiện tại để đặt, không phải khoảng trống của riêng
> module này.
>
> **1 gap thật sự áp dụng được** — nửa đầu Bước 4 "Chọn nền tảng" của nguồn: bảng MỤC TIÊU THUẬT
> TOÁN theo nền tảng (TikTok/Reels = thời gian xem + tỷ lệ HOÀN THÀNH; YouTube = thời gian xem + tỷ
> lệ NHẤP) — khác hẳn `PLATFORM_TIPS_BY_ASPECT_RATIO` (v1.9) vốn chỉ nói cách MỞ ĐẦU (hook 1-2 giây
> đầu), không nói gì về việc GIỮ nhịp xuyên suốt để thuật toán không cắt giảm phân phối giữa chừng.
> Enrich 2 tip `9:16`/`16:9` đã có (`CompileProjectDirectorPromptAction::PLATFORM_TIPS_BY_ASPECT_
> RATIO`) — không thêm field/schema mới, dùng đúng cơ chế v1.9. Nửa sau Bước 4 (bảng hiệu suất ĐỊNH
> DẠNG theo nền tảng — VD LinkedIn Carousel/PDF vượt trội Video, Instagram Carousel vượt ảnh đơn) CỐ
> Ý không áp dụng: module này CHỈ tạo prompt cho VIDEO (§1), không có carousel/ảnh đơn/text post để
> so sánh — dữ liệu "định dạng nào tốt hơn video" không có hành động tương ứng trong 1 tool CHỈ làm
> video.
>
> **Từ chối áp dụng, có lý do (còn lại của nguồn):** "1-2 nền tảng làm kỹ hơn 4-5 nền tảng làm mỏng"/
> kiểm toán đối thủ/content pillar — chiến lược cấp KÊNH, không phải cấp 1 Project/video đơn lẻ mà
> module quản lý; SMART goal — đã có `objective` (Creative Brief, v1.2) đúng vai trò mục tiêu kinh
> doanh cho 1 video, không cần khung SMART chi tiết hơn; lịch đăng/tần suất/đo lường hiệu suất — trùng
> quyết định "không theo dõi hiệu suất phân phối" đã chốt nhiều lần (§10); "3 lựa chọn dùng AI" (soạn
> thảo VỚI AI, không xuất bản THẲNG TỪ AI) — đúng triết lý module đã có sẵn từ v1.0 (copy tay sang tool
> ngoài + 2 checklist trước/sau generate, §0/§3.5), không phải khoảng trống; SEO caption/searchable
> phrases — thuộc siêu dữ liệu XUẤT BẢN (tiêu đề/mô tả khi đăng), khác phạm vi "Director Prompt
> Template" (nội dung HÌNH ẢNH của shot, §1), cùng nhóm đã loại ở v1.16 (video SEO — "module không
> host/index video").
>
> Chi tiết đầy đủ 2 tip enrich xem docblock hằng số `PLATFORM_TIPS_BY_ASPECT_RATIO` trong
> `CompileProjectDirectorPromptAction` (nguồn thật, tránh trôi khỏi code như đã cảnh báo ở §3.1).
>
> **v1.20 (2026-08-14 — đọc `leadde.ai/blog/marketing-script-template`, rà soát kỹ thuật còn thiếu so
> với v1.19):** nguồn liệt kê 15 marketing script template xếp thành 3 nhóm theo funnel (Video/Ads,
> Product-Sales-Funnel, Trust-Education-Retention), 8 thành phần script, Google ABCD Framework cho
> YouTube Ads, và 1 "AI Prompt Template" (cấu trúc "Act as [role]...create a script...") để soạn kịch
> bản qua chatbot. Phần lớn 15 template chỉ là biến thể phối lại nhịp Hook/Problem/Solution/Proof/CTA
> đã phủ từ v1.0-v1.18 dưới tên khác (VD "Basic Marketing Script" ≈ PSA đã có). "AI Prompt Template"
> CỐ Ý không áp dụng — đây là prompt cho chatbot SOẠN kịch bản văn bản, khác domain module (Director
> Prompt Template cho tool AI tạo VIDEO, §0 "không gọi AI Provider"; cùng lý do đã loại "leverage
> ChatGPT để soạn prompt" ở v1.9, không mở lại). Bảng Metrics theo giai đoạn funnel + phân loại 15
> template theo awareness/consideration/conversion/retention CỐ Ý không áp dụng — trùng quyết định
> "không theo dõi hiệu suất phân phối" (§10) + "không thêm field `funnel_stage`" đã chốt ở v1.5/v1.14/
> v1.16 (không mở lại).
>
> 3 gap thật sự còn thiếu, cả 3 đều nằm trên trục `video_formula` đã có (VIDEO_FORMULAS/FORMULA_TIPS_
> BY_VIDEO_FORMULA/FORMULA_BEATS, cơ chế từ v1.16-v1.18 — không field/migration mới, cột `string(20)`
> đã đủ chỗ cho cả 3 khoá mới dài nhất `testimonial_5part` 18 ký tự): (1) **`abcd`** (Google ABCD
> Framework: Attention-Branding-Connection-Direction) — framework chính thức của Google cho YouTube
> Ads (có thể bị bấm "Bỏ qua" sau 5s); khác biệt thật với PSA/BAB/Hook-Value-CTA/Demo-5-phần: 4 công
> thức đó đều đặt thương hiệu/sản phẩm ở nhịp GIỮA/CUỐI, ABCD đặt "Branding" NGAY nhịp thứ 2 (giới
> thiệu thương hiệu sớm vì quảng cáo có thể bị bỏ qua bất cứ lúc nào) — nguyên tắc dàn nhịp khác hẳn.
> (2) **`testimonial_5part`** (Before→Challenge→Solution→Result→Recommendation) — tách khỏi BAB (3
> nhịp) bằng cách thêm nhịp "Challenge" (khó khăn cụ thể, tách khỏi Before chung chung) và đổi "Bridge"
> (góc nhìn sản phẩm) thành "Recommendation" (góc nhìn người được phỏng vấn khuyên trực tiếp người
> xem) — cùng logic đã dùng để chấp nhận `demo_5part` tách khỏi `psa` ở v1.18, áp dụng cho
> `video_type = testimonial` đã có từ v1.6. (3) **`onboarding_5part`** (Welcome→First value→Steps→
> Mistakes→Support CTA) — gắn với `video_type` MỚI `onboarding` (§2.1/§4.1); formula RETENTION đầu
> tiên của module (4 công thức cũ đều hướng tới khán giả CHƯA MUA, công thức này hướng dẫn khách ĐÃ
> MUA — CTA cuối là "tìm hỗ trợ", không phải CTA bán hàng).
>
> Thêm `video_type` MỚI **`onboarding`** (Onboarding khách hàng — SOP/đào tạo/hướng dẫn sử dụng) vào
> `VIDEO_TYPES` — nguồn xếp "Customer Onboarding Script" vào nhóm Trust-Education-Retention mà 6 loại
> cũ (explainer/testimonial/product_demo/storytelling/spokesperson/offer_promo) hoàn toàn không phủ:
> `explainer` là giải thích sản phẩm cho khách TIỀM NĂNG (trước khi mua), khác hẳn onboarding là hướng
> dẫn khách ĐÃ MUA dùng sản phẩm — use-case retention vắng mặt hoàn toàn trước v1.20. Kèm tip tương ứng
> trong `CONTENT_TIPS_BY_VIDEO_TYPE`, cùng cơ chế v1.9/v1.14 (không field/UI/migration mới —
> `array_keys()`/`@foreach` ở FormRequest và `_form.blade.php` tự nhận khoá mới).
>
> Nhân tiện: `Brand Story Script` (Origin→Mission→Customer problem→Belief→Vision) của nguồn CỐ Ý không
> thêm formula riêng dù cũng là 1 trục khác biệt (không xoay quanh pain-point/giải pháp) — nội dung
> phần lớn trùng hướng dẫn "mô tả mạch cảm xúc xuyên suốt, tiến triển cảnh theo trình tự" đã có ở
> `CONTENT_TIPS_BY_VIDEO_TYPE['storytelling']` (v1.6), và use-case hẹp (trang About/tuyển dụng) không
> đủ giá trị biên để đánh đổi việc phình `video_formula` lên 8 lựa chọn — có thể mở lại nếu có nhu cầu
> thực tế sau này. Chi tiết đầy đủ 3 công thức + tip `onboarding` xem docblock class
> `CompileProjectDirectorPromptAction` (nguồn thật, tránh trôi khỏi code như đã cảnh báo ở §3.1).
>
> **v1.19 (2026-08-13, cùng ngày với v1.18 — phản hồi người dùng: "Chỗ khối nội dung 'Mẹo viết prompt'
> nên trình bày dễ đọc, chuyên nghiệp. Hiện tại trải dài như một text nội dung, khó nắm bắt thông
> tin"):** callout "💡 Mẹo viết prompt" từ v1.2 tới v1.18 là 1 `<span>` duy nhất nối 14 mẹo bằng dấu
> "·", đọc như 1 đoạn văn dài — càng về sau càng khó quét mắt khi số mẹo tăng dần qua mỗi nguồn đọc
> thêm. Đổi sang 4 nhóm có tiêu đề nhỏ (cùng kiểu chữ với nhóm field "Nội dung cảnh"/"Hình ảnh & Âm
> thanh" đã có ở mỗi Shot card) + danh sách gạch đầu dòng `<ul class="list-disc list-inside">` (cùng
> pattern hiển thị lỗi validate ở `create.blade.php`/`edit.blade.php`) — mỗi mẹo là 1 dòng riêng biệt:
> (1) **Phạm vi 1 Shot** — quyết định TRƯỚC khi viết field nào; (2) **Cách mô tả từng field** — lúc
> đang gõ Subject/Action/Style/Audio/CTA; (3) **Nhịp độ & thời lượng** — lúc chọn Duration/pacing;
> (4) **Lặp lại & xử lý sự cố** — lúc đã có `compiled_prompt`/kết quả AI, cần tinh chỉnh. Nội dung
> từng mẹo GIỮ NGUYÊN so với v1.18 — chỉ đổi cách trình bày, không đổi/bớt kỹ thuật nào, không có
> field/schema mới.
>
> **v1.18 (2026-08-13 — đọc `ngram.com/blog/demo-video-script-template` +
> `ngram.com/blog/how-to-make-demo-video`, rà soát kỹ thuật còn thiếu so với v1.17):** 2 bài đều nói
> về video demo phần mềm QUAY MÀN HÌNH THẬT (screen recording) — khác domain với module (AI-generated
> video qua tool ngoài, §0). Phần lớn nội dung KHÔNG áp dụng vì thuộc về quay/dựng thật, không phải
> viết prompt: thiết lập ghi hình (độ phân giải màn hình, đóng tab, mic vật lý, rehearse, ghi liên
> tục — công cụ Camtasia/OBS/Loom), kỹ thuật dựng hậu kỳ (cắt lỗi, callout/annotation mũi tên/khoanh
> tròn, transition — trùng quyết định "không animatic/edit trong app" §10), vị trí đặt video đã xuất
> bản (landing page/email/help center + số liệu conversion theo nơi đặt — trùng "không theo dõi hiệu
> suất phân phối" §10), quy trình AI Script Generation 6 bước của chính ngram (mô tả tính năng sản
> phẩm CẠNH TRANH, không phải kỹ thuật — trùng quyết định "không gọi AI Provider" §0).
>
> 2 gap thật sự áp dụng được (chỉ phần VIẾT KỊCH BẢN, tách khỏi phần quay/dựng thật của nguồn):
> (1) **5-Part Demo Video Script Framework** (Hook → Problem Deep-dive → Solution Introduction → Key
> Features in Action → CTA & Outcome, 90-120s) — trục giống `video_formula` đã có (v1.16/v1.17) nhưng
> khác PSA ở 2 điểm: tách riêng nhịp Hook khỏi Problem, và có nhịp "Key Features in Action" trình diễn
> NHIỀU tính năng (2-5) mà PSA gộp chung vào 1 nhịp Solution — không trùng lặp. Thêm khoá `demo_5part`
> vào `AiVideoStudioProject::VIDEO_FORMULAS` + `CompileProjectDirectorPromptAction::FORMULA_TIPS_BY_
> VIDEO_FORMULA`/`FORMULA_BEATS` (dùng lại đúng cơ chế đã có, KHÔNG field/UI/migration mới — cột
> `video_formula` đã là `string(20)`, `demo_5part` 10 ký tự vẫn đủ chỗ); `FORMULA_BEATS['demo_5part']`
> giữ NGUYÊN số giây cụ thể nguồn cho (5-10s, 10-30s...) thay vì quy đổi % như 3 công thức cũ, đúng
> tinh thần "Include Specific Numbers" của chính nguồn này. (2) Enrich tip `product_demo` trong
> `CONTENT_TIPS_BY_VIDEO_TYPE` — nguồn "show one feature, one workflow, one result" bổ sung giới hạn
> SỐ tính năng nên trình diễn (2-5 tính năng, mỗi tính năng gắn 1 lợi ích) mà tip cũ chỉ có gợi ý hình
> ảnh (ánh sáng/góc máy/nhạc nền), chưa nói tới số lượng.
>
> Nhân tiện bổ sung 3 điểm nhỏ vào callout "Mẹo viết prompt" (`show.blade.php`, §8) — đều là nguyên
> tắc VIẾT, không phụ thuộc domain quay màn hình của nguồn: "Lead with Outcomes, Not Features" (Action/
> CTA nên mô tả KẾT QUẢ khán giả nhận được, không liệt kê tính năng/thông số), "Include Specific
> Numbers" (số liệu cụ thể trong Script/CTA dễ nhớ và đáng tin hơn số liệu chung chung), và "Slow down"
> (nghiêng về Duration/pacing CHẬM hơn cảm giác của người viết prompt — thiên kiến quen thuộc ý tưởng,
> khác góc độ pacing-theo-tâm-trạng đã có ở v1.8).
>
> **v1.17 (2026-08-13, cùng ngày với v1.16 — phản hồi người dùng: "Khi chọn Loại video ... và Công
> thức kịch bản ... thì ở bên dưới nội dung khối Kịch bản & Timeline, sẽ load những gợi ý về cách
> trình bày mô tả tương đương cho loại content đó"):** v1.16 chỉ hiển thị gợi ý `video_type`/
> `video_formula` trong Creative Brief của TÀI LIỆU XUẤT (`CompileProjectDirectorPromptAction`,
> §3.5) — người dùng phải bấm "Xuất Director Prompt Template" mới thấy, không có gì ngay trên trang
> `show` lúc đang gõ từng shot. Bổ sung `CompileProjectDirectorPromptAction::FORMULA_BEATS` — breakdown
> mỗi công thức (`psa`/`bab`/`hook_value_cta`) thành 3 nhịp (tên/tỉ lệ % thời lượng gợi ý trong khoảng
> tổng thời lượng đã có ở `FORMULA_TIPS_BY_VIDEO_FORMULA`/cách viết Subject-Action-Script cho nhịp đó)
> — chi tiết hơn 1 câu tóm tắt hiện có. Đổi `CONTENT_TIPS_BY_VIDEO_TYPE` từ `private` sang `public` để
> tái dùng, không chép lại nội dung. Thêm 1 khối `<details>` mới **"🎯 Gợi ý mô tả theo Loại video &
> Công thức kịch bản đã chọn"** ở `show.blade.php`, đặt NGAY DƯỚI phần mở đầu section "3. Kịch bản &
> Timeline" — TRƯỚC khối "Khung thời gian mẫu" tĩnh đã có (khối mới đặc thù theo lựa chọn Project, khối
> cũ là fallback chung chung không đổi theo lựa chọn). Nội dung: câu tip theo `video_type` (nếu có) +
> bảng 3 nhịp theo `video_formula` (nếu có) — chỉ 1 trong 2 vẫn hiện được (thiếu formula thì chỉ có
> câu tip + gợi ý bấm "Sửa project" để chọn thêm). CHỈ hiện khối nếu có ít nhất 1 trong 2 field — dự
> án chưa chọn gì thì không có gì để gợi ý (§8, §11). Thuần hiển thị — KHÔNG có field/schema mới, KHÔNG
> vào `compiled_prompt`/tài liệu xuất (đó vẫn là vai trò của `FORMULA_TIPS_BY_VIDEO_FORMULA` cũ).
>
> **v1.16 (2026-08-13 — đọc `tulsainternetmarketingservice.com/blog/video-marketing-formulas` +
> `swarmify.com/blog/video-marketing-strategy`, rà soát kỹ thuật còn thiếu so với v1.15):**
> `swarmify.com` là bài chiến lược marketing tổng quát, phần lớn NGOÀI phạm vi module hoặc trùng field
> đã có: mục tiêu kinh doanh + KPI 90 ngày (~`objective`), nghiên cứu thói quen xem video của khán giả
> (~`target_audience`), video SEO (schema markup/transcript/từ khoá — module không host/index video),
> tối ưu hiệu suất trang web nhúng video (CDN/lazy-load/facade pattern — module không xuất bản video
> lên web), tối ưu ngân sách sản xuất bằng AI tool + đo lường/lặp lại theo tuần/tháng/quý sau khi đăng
> (trùng quyết định "không gọi AI Provider" §0 và "không theo dõi hiệu suất phân phối" §10 đã chốt từ
> v1.5/v1.14, không mở lại). Phân loại theo giai đoạn funnel (awareness/consideration/conversion, tỷ
> lệ 60/25/15) CỐ Ý không áp dụng: trùng lấn `video_type` đã có (`product_demo`/`testimonial`/
> `offer_promo` đã ngầm gắn với từng giai đoạn) mà không thêm hướng dẫn viết prompt nào khác biệt —
> thêm field này sẽ tạo 2 trục phân loại nội dung chồng chéo nhau, đi ngược kỷ luật "không field trùng
> lặp" module đã giữ xuyên suốt (VD quyết định không thêm `funnel_stage` hệt lý do không lặp lại phân
> loại `video_type`).
>
> `tulsainternetmarketingservice.com` có 1 gap thật sự: 3 **công thức kịch bản** (narrative arc) —
> Problem-Solution-CTA (30-60s), Before-After-Bridge (60-90s), Hook-Value-CTA (15-45s), mỗi công thức
> là 1 TRÌNH TỰ 3 nhịp cho toàn bộ video + khoảng thời lượng khuyến nghị. Đây là TRỤC KHÁC với
> `video_type`: video_type nói video thuộc LOẠI NỘI DUNG gì, công thức nói video KỂ CHUYỆN THEO TRÌNH
> TỰ nào — 1 video `product_demo` vẫn dùng được cấu trúc PSA hoặc Hook-Value-CTA tuỳ ý đồ, không trùng
> lặp như `funnel_stage` bị loại ở trên. Thêm `video_formula` (nullable, cấp Project, migration ALTER
> riêng sau `video_type` — §2.1) + hằng số `AiVideoStudioProject::VIDEO_FORMULAS` + helper
> `videoFormulaLabel()` (§4.1); hiển thị 2 dòng **Công thức kịch bản**/**Cấu trúc gợi ý** trong Creative
> Brief của tài liệu xuất (`CompileProjectDirectorPromptAction::FORMULA_TIPS_BY_VIDEO_FORMULA`, §3.5) —
> cùng pattern `CONTENT_TIPS_BY_VIDEO_TYPE` (v1.9); RENDER vào `compiled_prompt` của mọi Shot qua
> `BuildShotPromptAction::buildCampaignContextLines()` (dòng "Công thức kịch bản", §3.1) — cùng nhóm
> với `video_type`, nên thêm vào `PROMPT_CONTEXT_FIELDS` của `UpdateProjectAction` (sửa sẽ build lại
> `compiled_prompt` toàn bộ Shot, §3.6). Form (`_form.blade.php`) thêm `<select>` 3 lựa chọn cạnh Loại
> video; trang `show.blade.php` thêm dòng hiển thị cùng khối Creative Brief (§8).
>
> **v1.15 (2026-08-12, cùng phiên — phản hồi người dùng "không áp dụng kỹ thuật nào mới ah?" sau
> v1.14):** đúng — v1.14 chỉ thêm tip/text + 2 lựa chọn `video_type`, chưa có khối TÍNH TOÁN mới nào
> từ 2 nguồn `mindstudio.ai`/`imagine.art`. Rà soát lại: `mindstudio.ai` Agent 2 "Voiceover" có khái
> niệm **EDL (Edit Decision List)** — bảng đối chiếu narration/thời gian với từng shot, dùng ở Agent 4
> "Assembly" để canh clip khớp voiceover/phụ đề — module có đủ dữ liệu (`duration_seconds`,
> `script_line`, `sort_order`) nhưng chưa từng tổng hợp thành bảng này, kể cả trong tài liệu XUẤT
> (.md tải về đưa cho editor). Thêm `CompileProjectDirectorPromptAction::buildEdlBlock()` — xem §3.5.
>
> **v1.14 (2026-08-12 — đọc `mindstudio.ai/blog/ai-video-generation-content-marketing-multi-agent-workflow`
> + `imagine.art/blogs/make-ai-marketing-videos`, rà soát kỹ thuật còn thiếu so với v1.13):** 2 nguồn
> phần lớn lặp lại nguyên tắc đã có (hook đầu video, 1 CTA duy nhất, checklist trước/sau khi tạo, thử
> biến thể — đã phủ từ v1.0-v1.9); phần rõ ràng KHÔNG áp dụng (gọi API TTS/video model thật, retry/
> backoff, tích hợp content-calendar, đo hiệu suất phân phối sau khi đăng) trùng quyết định "chỉ tổ
> chức prompt, không gọi AI Provider" (§0) và mục đã loại khỏi phạm vi ở §10 — không mở lại. 4 kỹ
> thuật thật sự còn thiếu, đã áp dụng vào `CompileProjectDirectorPromptAction`/`AiVideoStudioProject`/
> `show.blade.php` (chi tiết ở docblock class + §2.1/§4.1/§7/§8): (1) 2 `video_type` mới
> `spokesperson`/`offer_promo` (imagine.art, 7 định dạng cụ thể hơn) + tip tương ứng; (2) công thức
> tốc độ đọc 125-150 từ/phút (mindstudio.ai Agent 1) cho `script_line`; (3) ghi chú quy trình
> Voiceover/TTS TRƯỚC khi tạo video, dùng timestamp cấp từ canh khớp shot (mindstudio.ai Agent 2 —
> chỉ là hướng dẫn, module không gọi TTS thật); (4) bảng định dạng/thời lượng tối đa theo nền tảng +
> "ẩn sản phẩm quá lâu"/phụ đề mờ thêm vào `QC_CHECKLIST`. Nhân tiện sửa 1 lỗi phát hiện khi rà soát:
> `buildCreativeBriefBlock()` in slug thô `video_type` thay vì `videoTypeLabel()` dù §11 (v1.7) đã ghi
> nhận hành vi ĐÚNG này từ trước — code lệch spec, không phải do thay đổi vừa rồi.
>
> **v1.10-v1.13 (2026-08-12, cùng phiên làm việc, tóm tắt — xem chi tiết ở docblock từng file):**
> v1.10 (LinkedIn, đọc lại) thêm `image_prompt`/`motion_prompt` tự sinh (quy trình 2 bước Image-to-
> Video) + `timeline_breakdown` + 2 field ảnh nguồn ghép KOL/sản phẩm; v1.11 (phản hồi người dùng) bỏ
> 2 field ảnh nguồn đó, thay bằng `reference_context_prompt` (text ngắn tự gõ); v1.12 (phản hồi người
> dùng) bỏ tự sinh `image_prompt`/`motion_prompt`, đổi thành nhập tay tự do; v1.13 (phản hồi người
> dùng) làm lại UX trang `show.blade.php` — bỏ thanh quy trình 4 bước (logic "active" không nhất
> quán), bỏ input `reference_image_url` khỏi UI, bỏ khối "Nâng cao"/"Kết quả AI", đổi autosave debounce
> mỗi field thành nút "Lưu"/shot bấm tay (giảm request AJAX) + cảnh báo `beforeunload` nếu chưa lưu.
>
> **v1.9 (2026-08-09 — đọc `veed.io/learn/video-prompts`, rà soát kỹ thuật còn thiếu so với v1.8):**
> nguồn dùng công thức "3 thành phần" (Subject & Action/Visual Style/Technical Direction) + "power
> words" (temporal/spatial/emotional cues) + tổ chức prompt theo thứ tự + sai lầm phổ biến — ĐÃ phủ
> từ v1.0-v1.8. Gap thật sự: nguồn có khối **"Platform-Specific Strategies"** map RÕ cách viết theo
> định dạng/loại nội dung (TikTok-Reels 9:16 cần hook mạnh 1-2s đầu; YouTube 16:9 giáo dục cần
> narrator+overlay, tutorial cần hands-in-frame, storytelling cần mạch cảm xúc; Business cần
> benefit/lighting/CTA theo từng loại) — module trước đó chỉ có 1 câu chung "điều chỉnh giọng văn
> theo nền tảng" (v1.6), không map cụ thể sang field nào. Bổ sung `PLATFORM_TIPS_BY_ASPECT_RATIO`
> (dùng lại `aspect_ratio` đã có từ v1.2 — CHỈ 9:16/16:9 vì nguồn chỉ nói rõ 2 định dạng này) và
> `CONTENT_TIPS_BY_VIDEO_TYPE` (dùng lại `video_type` đã có từ v1.6 — bỏ qua `other`), hiển thị trong
> khối Creative Brief nếu field tương ứng có điền và có tip khớp (§3.5) — KHÔNG schema mới. Đồng thời
> enrich placeholder Environment (`show.blade.php`, §8) với spatial descriptors (hẻm nhỏ, đồng cỏ,
> studio, hang động...) + temporal cues (rạng đông, chiều tà, giờ vàng...) — field cuối cùng trong
> nhóm 5 field cốt lõi còn thiếu ví dụ (Camera/Style đã có từ v1.8); Style thêm ví dụ phong cách hoạt
> hình/nghệ thuật (anime, claymation, tranh màu nước, VHS cũ — nguồn "Artistic style references").
> CỐ Ý không áp dụng: "leverage ChatGPT để soạn prompt" và danh sách model cụ thể (MiniMax/PixVerse/
> Kling...) — trùng quyết định "không phụ thuộc 1 tool cụ thể" đã chốt ở §0.
>
> **v1.8 (2026-08-09 — đọc `sentx.ai/blog/how-to-write-ai-video-prompts`, rà soát kỹ thuật còn thiếu
> so với v1.7):** nguồn dùng công thức 7-lớp (Subject/Action/Setting/Camera/Lighting & Mood/Style/
> Duration & Pacing) + Subject Definition/Action Practices/Optimization Habits/Prompt Length — phần
> lớn ĐÃ phủ từ v1.0-v1.6 (subject cụ thể, action khả thi trong vài giây/1 hành động mỗi shot, style
> gọi tên rõ, front-load 20-30 từ đầu, negative prompt cụ thể, show-vs-tell). "Swipe file" (lưu prompt
> tốt để tái dùng) trùng quyết định "Duplicate/clone Project/Shot" đã ghi để dành bản sau (§10) — không
> mở lại. 2 gap thật sự:
> 1. **"Single hero focus"** — nguồn tách riêng khỏi "1 shot = 1 hành động" đã có (Hedra v1.2): giới
>    hạn SỐ chủ thể trong khung hình (1 chính + tối đa 1 phụ), không phải số hành động. Bổ sung 1 mục
>    vào `PRE_GENERATION_CHECKLIST` (§3.5) + callout "Mẹo viết prompt" (§8) — không cần schema mới.
> 2. **Troubleshooting theo triệu chứng** — nguồn map cụ thể triệu chứng output sang field cần sửa
>    ("video tĩnh → thêm Action+Camera", "sai tâm trạng/màu → gọi tên nguồn sáng + thời điểm trong
>    ngày ở Style/Mood", "style bị bỏ qua → gắn Style với đặc điểm cụ thể", "nhân vật lệch → dùng lại
>    nguyên văn đặc điểm nhận diện cố định"); checklist QC hiện có (Hedra, v1.2) chỉ nêu TIÊU CHÍ đánh
>    giá, không map sang CÁCH SỬA. Bổ sung `TROUBLESHOOTING_GUIDE` + khối `## Xử lý sự cố theo triệu
>    chứng` trong tài liệu xuất (§3.5), đặt SAU checklist QC (dùng khi checklist đó phát hiện lỗi).
>
> Đồng thời enrich placeholder Camera (vốn từ vựng cỡ cảnh: toàn/trung/cận/đại cận; góc: eye-level/
> thấp/cao; chuyển động: đẩy chậm/lia ngang/tracking/tĩnh/cầm tay) và Style (gọi tên nguồn sáng + thời
> điểm trong ngày cụ thể — "nắng vàng hoàng hôn" thay vì "ánh sáng đẹp") ở `show.blade.php` (§8) — 2
> field callout đã nhắc là quan trọng nhưng trước đó KHÔNG có placeholder ví dụ nào (`null`). Cập nhật
> callout "Mẹo viết prompt" thêm điểm "pacing" (nhịp lấy cảnh — 1 cú máy chậm liên tục khác chuyển
> động nhanh dồn dập, tách khỏi Duration/độ dài) và trỏ tới khối troubleshooting mới.
>
> **v1.7 (2026-08-09 — rà soát chuyên sâu logic build prompt, KHÔNG đọc nguồn mới):** sau 5 vòng bổ
> sung field từ v1.2→v1.6, rà lại toàn module và tìm ra 6 lỗ hổng — phần lớn là hệ quả của việc thêm
> field nhanh mà không soát lại đường đi của dữ liệu:
>
> 1. **Bối cảnh Project không bao giờ tới prompt (nặng nhất).** `aspect_ratio`/`resolution` (v1.2/v1.5)
>    và `video_type`/`target_audience`/`core_message` (v1.6) được nhập ở Creative Brief nhưng
>    `BuildShotPromptAction` chỉ đọc field của Shot → prompt dán sang tool AI ngoài KHÔNG hề nói video
>    phải 9:16, tool mặc định 16:9. Dữ liệu đã thu thập nhưng không tới đích. Sửa: §3.1 render thêm
>    dòng `ĐỊNH DẠNG (Format)` (ngắn, đặt trước nội dung shot) + khối `BỐI CẢNH CHIẾN DỊCH` (đặt
>    CUỐI, đánh dấu rõ "KHÔNG cần thể hiện toàn bộ trong shot này" để AI không nhồi cả chiến dịch vào
>    1 shot — đúng sai lầm "overloading" cả 3 nguồn đều cảnh báo). `objective` CỐ Ý không vào prompt
>    (mục tiêu kinh doanh, không đổi thứ gì trên khung hình); `reference_image_url` cũng không, cùng
>    lý do với `reference_assets` (§0/v1.4 — cú pháp đính kèm khác nhau tuỳ tool).
> 2. **Shot rỗng sinh "prompt ma"; placeholder ở §3.5 là nhánh chết.** `BuildShotPromptAction` luôn
>    trả về ít nhất câu dẫn → `compiled_prompt` KHÔNG BAO GIỜ null với shot tạo qua app, nên nhánh
>    `_(chưa có prompt...)_` chỉ chạy được với dữ liệu dựng tay trong test. Người dùng bấm "Thêm shot"
>    rồi Copy ngay sẽ dán sang AI một câu "hãy tạo cảnh quay theo mô tả sau:" không có mô tả nào. Sửa:
>    trả `''` khi chưa có field nội dung nào, Create/UpdateShotAction lưu thành `null`.
> 3. **Staleness do (1).** Bối cảnh Project được RENDER vào prompt nên đổi `aspect_ratio` mà không
>    build lại thì mọi prompt đã lưu nói sai định dạng. Sửa: `UpdateProjectAction` build lại
>    `compiled_prompt` của MỌI shot khi 1 trong 5 field bối cảnh đổi (§3.6). **Cố ý KHÔNG đưa vị trí
>    "Shot N/M" vào prompt** dù có ích cho tính liên tục: nó sẽ lỗi thời sau mỗi lần thêm/xoá/đổi thứ
>    tự shot, đổi lấy việc phải build lại toàn bộ sau mỗi cú bấm "↑/↓" — không đáng.
> 4. **Giá trị nhiều dòng phá cấu trúc prompt.** Prompt là các dòng `NHÃN: giá trị` nối bằng `\n`;
>    một giá trị dán từ Word xuống dòng giữa chừng khiến dòng sau trông y hệt 1 field mới. Sửa: thụt
>    lề dòng nối 4 space (giữ nguyên nội dung, không gộp dòng để khỏi phá lời thoại nhiều câu) +
>    chuẩn hoá CRLF.
> 5. **`PUT shots/{shot}` thiếu field xoá trắng dữ liệu.** `UpdateShotRequest::toData()` map input
>    vắng mặt thành `null` → `PUT {"subject":"x"}` xoá sạch 14 field còn lại. UI luôn gửi đủ nên chưa
>    lộ, nhưng chỉ cần thêm 1 field mà quên gắn `.aivs-field` trong JS là field đó bị xoá ở MỌI shot
>    sau mỗi lần gõ. Sửa: merge — field VẮNG MẶT giữ nguyên, gửi chuỗi rỗng vẫn xoá như cũ (§6.1).
> 6. **Tập giá trị enum nhân bản 3 nơi.** `video_type`/`aspect_ratio`/`resolution`/`status` viết lại
>    ở `_form.blade.php` + 2 FormRequest → thêm lựa chọn ở form mà quên sửa validate = 422 khó hiểu;
>    trang `show` và tài liệu xuất còn in slug thô (`testimonial`) trong khi form hiện nhãn đẹp. Sửa:
>    gom về hằng số Model (§4.1), `Rule::in(array_keys(...))`, thêm `videoTypeLabel()`.
>
> **Ghi chú prompt-injection:** quy ước bọc `<<<DELIMITER>>>` của CLAUDE.md áp dụng cho text không tin
> cậy đi vào prompt do CHÍNH APP gửi qua `app/Services/AI/`. Module này không gọi AI — chuỗi chỉ được
> copy tay sang tool ngoài và mọi giá trị do biên tập viên nội bộ tự gõ, nên rủi ro thực tế là vỡ cấu
> trúc (mục 4) chứ không phải injection. Nếu sau này module tự gọi AI Provider thì PHẢI bọc delimiter.
>
> **v1.6 (2026-08-09 — đọc bài LinkedIn "step-by-step guide creating AI marketing videos prompts",
> rà soát kỹ thuật còn thiếu so với v1.5):** nguồn tập trung vào quy trình marketing cụ thể (Define
> Objectives → Tool Selection → Prepare Input Materials → Prompt Refinement → Quality Optimization).
> Bổ sung: (1) **Loại video** (`video_type`: explainer/testimonial/product_demo/storytelling/other)
> và **Thông điệp cốt lõi** (`core_message`, VD "Tăng năng suất 40%") vào Project — Step 1 "Define
> Objectives" tách 2 khái niệm này khỏi `objective`/`target_audience` đã có (v1.2), hiển thị trong
> Creative Brief (§2.1, §3.5, §8); (2) **CTA** (`cta_text`) vào Shot — nguồn liệt kê "CTA
> specifications (button text, countdown)" là 1 thành phần cấu trúc prompt riêng cho video marketing;
> đưa vào `compiled_prompt` (overlay AI cần vẽ lên màn hình), đặt sau Lời thoại, trước Constraints
> (§2.2, §3.1, §8); (3) bổ sung 3 mục checklist đặc thù marketing vào `QC_CHECKLIST` (thời lượng khớp
> tỷ lệ nền tảng, nhất quán thương hiệu, âm thanh rõ ràng — từ "Quality Optimization Checklist" của
> nguồn, §3.5); (4) cập nhật callout "Mẹo viết prompt": phân biệt rõ diễn đạt khẳng định (Subject/
> Action/Style) với loại trừ cụ thể (Constraints), và điều chỉnh giọng văn theo nền tảng/đối tượng.
>
> **v1.5 (2026-08-09 — đọc `pyxeljam.com` "10 best practices for writing effective AI video
> prompts", rà soát kỹ thuật còn thiếu so với v1.4):** rà 10 thực hành của nguồn — phần lớn đã phủ
> (Creative Brief, Style/Mood, plain language, action/camera detail, reference materials, template
> tái dùng, positive framing, iteration). Genuine gap: thực hành #7 "Set Video Length, Quality, and
> File Format Requirements" — phần Video Length đã có (`duration_seconds`, v1.3) nhưng phần Quality
> (độ phân giải xuất bản) chưa có field — thêm **Độ phân giải** (`resolution`: 720p/1080p/2K/4K) vào
> Project, cạnh `aspect_ratio` (§2.1, §3.5, §8). Thực hành #10 "Test and Improve — track performance"
> ghi rõ vào §10 Ngoài phạm vi (module chỉ đánh giá CHẤT LƯỢNG video tạo ra, không theo dõi hiệu suất
> phân phối/kinh doanh sau khi đăng).
>
> **v1.4 (2026-08-09 — đọc `byteplus.com` "how to write AI video prompts that actually work", rà
> soát kỹ thuật còn thiếu so với v1.3):** nguồn dùng công thức `[Subject] + [Action] + [Scene] +
> [Camera Movement] + [Style/Mood] + [Audio Direction]` — 5 thành phần đầu đã có; **Audio Direction**
> (âm thanh môi trường + nhạc nền, KHÁC lời thoại `script_line`) còn thiếu, bổ sung `audio_direction`
> ở Shot (§2.2, §3.1, §8) — đưa vào `compiled_prompt` (nội dung prompt thật, đặt sau Mood/Duration,
> trước Lời thoại). Nguồn cũng nêu khái niệm **multi-reference** (gắn ảnh/video/audio tham chiếu
> riêng cho từng cảnh — ảnh nhân vật, video mẫu chuyển động máy, track nhạc mẫu) mà module mới có 1
> ảnh anchor cấp Project (`reference_image_url`, v1.2) — bổ sung `reference_assets` ở Shot (§2.2, §8)
> để ghi chú link tham chiếu bổ sung riêng cho shot đó; đây là metadata (như `model_tool`/`qc_notes`),
> KHÔNG đưa vào `compiled_prompt` vì cú pháp gắn reference (`@tag`...) khác nhau tuỳ tool AI ngoài,
> trong khi module chủ trương không phụ thuộc 1 tool cụ thể (§0). Cập nhật callout "Mẹo viết prompt"
> (`show.blade.php`) với 2 điểm: đừng dồn nhiều hành động khác nhau vào 1 shot ngắn, và đừng bỏ qua
> Audio Direction (thiếu dễ ra video như ảnh tĩnh hơi rung).
>
> **v1.3 (2026-08-09 — đọc `deepreel.com/blog/ai-video-prompts`, rà soát kỹ thuật còn thiếu so với
> v1.2):** nguồn dùng công thức "Subject + Style + Camera + Mood + Duration" — Subject/Camera/Style
> đã có; 2 thành phần **Mood** và **Duration** còn thiếu, bổ sung ở Shot (`mood` text,
> `duration_seconds` unsignedSmallInteger — CẢ HAI đưa vào `compiled_prompt` vì là nội dung prompt
> thật, khác `model_tool`/`qc_notes` ở v1.2 vốn là metadata) — xem §2.2, §3.1, §8. Nguồn cũng nêu 4 kỹ
> thuật khác được áp dụng KHÔNG cần schema mới: (1) **Negative prompt cụ thể** cho Constraints (loại
> trừ rõ ràng thay vì mơ hồ) — cập nhật placeholder ở `_form.blade.php`/`show.blade.php`/JS (§8); (2)
> **Word count 50-150 từ/prompt, ưu tiên 20-30 từ đầu** — thêm bộ đếm từ cạnh `compiled_prompt` trên
> UI (`aivideostudiotemplate.js`, cảnh báo nhẹ nếu lệch khoảng, không chặn lưu); (3) **Checklist TRƯỚC
> khi generate** (khác checklist QC SAU khi generate ở v1.2) — thêm khối tĩnh riêng trong tài liệu
> xuất (§3.5), luôn hiện trước khối QC; (4) **Vòng lặp tinh chỉnh 3-4 lần/shot, mỗi lần 1 vấn đề** —
> cập nhật placeholder `qc_notes` theo cấu trúc "3 điểm được + 1 điểm cần sửa". Đồng thời cộng dồn
> `duration_seconds` các shot để hiện **tổng thời lượng ước tính** đầu tài liệu xuất (§3.5) — giá trị
> gia tăng từ việc có field Duration dạng số thay vì text tự do.
>
> **v1.2 (2026-08-09 — đọc `hedra.com/blog/how-to-make-ai-video`, rà soát kỹ thuật còn thiếu):**
> nguồn liệt kê 6 nhóm kỹ thuật (Prompt Writing/Layered Structure, Workflow 6 bước, Model Selection
> Criteria, Common Mistakes, Best Practices) — cấu trúc layered prompt (Subject/Action/Environment/
> Camera/Style) và anchor xuyên suốt shot ĐÃ có từ v1.0/v1.1; 3 nhóm còn thiếu được bổ sung ở v1.2:
> (1) **Creative Brief** (Workflow Step 1 — "Define Creative Brief" + Common Mistake "skipping the
> creative brief") — thêm `objective`/`target_audience`/`aspect_ratio` ở Project (§2.1, §7, §8),
> hiển thị đầu tài liệu xuất (§3.5); (2) **Reference image anchor** (Workflow Step 2 "Prepare Source
> Materials" — "high-resolution reference images with clean backgrounds", bổ sung cho anchor bằng
> TEXT đã có) — thêm `reference_image_url` ở Project (§2.1, §8); (3) **Model Selection + iterate**
> (nguồn: "test alternative models", "Model Selection Criteria") — thêm `model_tool` ở Shot ghi lại
> tool/model AI ngoài đã dùng (§2.2, §8), KHÔNG đưa vào `compiled_prompt` (metadata, không gửi cho AI
> generator); (4) **Evaluation checklist** (Workflow Step 3 "Generate First Draft" — checklist
> subject accuracy/motion smoothness/camera angle/style consistency/artifacts) — thêm `qc_notes` ở
> Shot (§2.2, §8) + khối checklist tĩnh trong tài liệu xuất (§3.5). Đồng thời cập nhật placeholder
> form (`_form.blade.php`) theo "Key Prompting Principles" (thay tính từ chung chung bằng mô tả cụ
> thể) + thêm callout mẹo viết prompt ở `show.blade.php`. Sửa luôn tiêu đề tài liệu (đã lỗi thời, ghi
> "CHƯA triển khai" dù module đã code xong từ v1.1).
>
> **v1.1 (sau review nội bộ v1.0, trước khi code):** bổ sung ownership check cho `ReorderShotsAction`
> (§3.4) — v1.0 chưa chặn 1 project reorder nhầm/cố ý shot của project khác; định nghĩa rõ Model
> `AiVideoStudioProject`/`AiVideoStudioShot` bằng code thật thay vì chỉ ghi "theo khuôn
> ContentOutline" (§4.1); chốt contract JSON response + debounce/readonly cho UI inline (§6.1/§6.2 —
> đây là phần rủi ro triển khai lớn nhất theo review, để lại mơ hồ sẽ khiến UX "mỏng"); thêm ghi chú
> UI bắt buộc cho anchoring "chỉ áp dụng khi tạo mới" + confirm khi xoá Project/Shot (§8); thêm
> filter status + tìm theo tên ở trang index (§8, tận dụng cột đã có sẵn nhưng chưa dùng ở v1.0);
> mở rộng §11 (ownership reorder, shape lỗi 422, edge case export, không cần optimistic locking);
> liệt kê tường minh các mục để dành bản sau ở §10 (drag-drop, apply-defaults hàng loạt).
**Nguồn tham khảo (đọc trong hội thoại trước khi viết spec này, không phải nguồn học thuật):**
- `moudjadj.com/ai-prompt-for-creating-viral-videos-on-youtube` — tâm lý hook + 6 building block của 1 prompt video viral.
- `academy.techpresso.co/prompts/chatgpt-prompts-video-marketing` — quy trình ChatGPT cho vòng đời video marketing.
- `help.flexclip.com/.../how-to-write-effective-text-prompts-to-generate-ai-videos` — công thức `Subject + Action + Scene + Camera Movement + Lighting + Style`.
- `neolemon.com/blog/beginners-guide-to-ai-video-creation-from-zero-to-hero` — Director Prompt Template (`Subject + Action + Environment + Camera + Style + Constraints`), khái niệm identity anchor, animatic, shot list.
- `magichour.ai` (2 bài về reference image trong image-to-video + character consistency) — kỹ thuật "anchoring description block", reference strength.
- `hedra.com/blog/how-to-make-ai-video` (v1.2) — Layered prompt structure (5 lớp: Subject/Action/Environment/Camera/Style), Key Prompting Principles, Workflow 6 bước (Creative Brief → Prepare Source Materials → First Draft → Refine & Iterate → Post-Production → Export), Model Selection Criteria, Common Mistakes, Best Practices.
- `deepreel.com/blog/ai-video-prompts` (v1.3) — công thức `Subject + Style + Camera + Mood + Duration`, hướng dẫn độ dài prompt (50-150 từ, ưu tiên 20-30 từ đầu), kỹ thuật negative prompt cụ thể, quy trình tinh chỉnh lặp (3-4 lần/shot, mỗi lần 1 vấn đề), checklist trước khi generate, gợi ý theo nền tảng (YouTube/Instagram-TikTok/LinkedIn).
- `byteplus.com` "how to write AI video prompts that actually work" (v1.4) — công thức `Subject + Action + Scene + Camera Movement + Style/Mood + Audio Direction`, kỹ thuật soundscape/music direction, khái niệm multi-reference (ảnh/video/audio tham chiếu riêng theo vai trò), sai lầm phổ biến (dồn nhiều hành động vào 1 clip ngắn, bỏ qua audio, tham chiếu mâu thuẫn nhau).
- `pyxeljam.com/10-best-practices-for-writing-effective-ai-video-prompts` (v1.5) — 10 thực hành: background rõ ràng, style/mood, role prompting, ngôn ngữ đơn giản, chi tiết hành động/camera, reference materials, video length/quality/format, nhất quán + template tái dùng, tuỳ biến theo ngành/nền tảng, test & cải thiện lặp lại.
- Bài LinkedIn "step-by-step guide creating AI marketing videos with prompts" (v1.6) — quy trình 3 bước (Define Objectives → Tool Selection → Prepare Input Materials), cấu trúc prompt text-to-video/image-to-video/website-to-video, CTA specifications, quy tắc cụ thể hoá (brand color constraints, emotional triggers), Quality Optimization Checklist (tỷ lệ nền tảng, nhất quán thương hiệu, âm thanh rõ).
- `sentx.ai/blog/how-to-write-ai-video-prompts` (v1.8) — công thức 7-lớp (Subject/Action/Setting/Camera/Lighting & Mood/Style/Duration & Pacing), "single hero focus" (giới hạn số chủ thể trong khung hình), troubleshooting theo triệu chứng (map lỗi output → field cần sửa), token weighting/front-load, show-vs-tell, "swipe file" prompt tái dùng.
- `veed.io/learn/video-prompts` (v1.9) — công thức 3 thành phần (Subject & Action/Visual Style/Technical Direction), power words (temporal/spatial/emotional cues), Platform-Specific Strategies (TikTok/Reels, YouTube theo loại nội dung, Business/Commercial), artistic style references (anime/claymation/watercolor/VHS), model-specific recommendations (MiniMax/PixVerse/Seedance/Veo/Kling).
- `mindstudio.ai/blog/ai-video-generation-content-marketing-multi-agent-workflow` (v1.14/v1.15) — quy trình multi-agent (Script → Voiceover → Visual → Assembly), công thức tốc độ đọc 125-150 từ/phút, EDL (Edit Decision List) đối chiếu narration/thời gian với từng shot, bảng định dạng/thời lượng theo nền tảng.
- `imagine.art/blogs/make-ai-marketing-videos` (v1.14) — 7 định dạng video marketing cụ thể (bao gồm Spokesperson/Offer-Promo), 7 lỗi phổ biến (ẩn sản phẩm quá lâu, phụ đề mờ...), chỉ số hiệu suất phân phối (Hook rate/Hold rate/CTR/CPC/CPA).
- `tulsainternetmarketingservice.com/blog/video-marketing-formulas` (v1.16) — 3 công thức kịch bản (narrative arc) kèm khoảng thời lượng khuyến nghị: Problem-Solution-CTA (30-60s), Before-After-Bridge (60-90s), Hook-Value-CTA (15-45s).
- `swarmify.com/blog/video-marketing-strategy` (v1.16 — phần lớn ngoài phạm vi, xem changelog) — chiến lược marketing tổng quát: mục tiêu kinh doanh, nghiên cứu khán giả, phân loại funnel (awareness/consideration/conversion), tối ưu ngân sách sản xuất, video SEO, tối ưu hiệu suất trang web nhúng video, đo lường/lặp lại.
- `ngram.com/blog/demo-video-script-template` (v1.18) — 5-Part Demo Video Script Framework (Hook/Problem Deep-dive/Solution Introduction/Key Features in Action/CTA & Outcome), 6 kỹ thuật viết script (lead with outcomes, show-don't-tell, conversational, one problem one solution, specific numbers, clear next step), số liệu conversion theo loại script.
- `ngram.com/blog/how-to-make-demo-video` (v1.18 — phần lớn ngoài phạm vi vì nói về quay màn hình thật, xem changelog) — quy trình 8 bước làm video demo phần mềm (screen recording): mục tiêu/đối tượng, script, độ dài/format, ghi hình, chỉnh sửa, chọn nơi đặt, CTA, theo dõi hiệu suất; genuine gap áp dụng được: "slow down" (thiên kiến pacing của người quay/viết).
- `leadde.ai/blog/marketing-script-template` (v1.20) — 15 marketing script template xếp theo 3 nhóm funnel (Video/Ads, Product-Sales-Funnel, Trust-Education-Retention), 8 thành phần script, Google ABCD Framework (YouTube Ads), AI Prompt Template ("Act as [role]..."), sai lầm phổ biến + tips chuyển đổi, metrics theo giai đoạn funnel. Genuine gap áp dụng được: ABCD Framework, Testimonial 5-part (Before-Challenge-Solution-Result-Recommendation), Customer Onboarding Script (Welcome-First value-Steps-Mistakes-Support CTA) — cả 3 xem changelog v1.20.
- `buffer.com/resources/social-media-marketing-strategy` (v1.21) — hướng dẫn 7 bước xây dựng chiến lược social media (kiểm toán, audience, SMART goal, chọn nền tảng, content pillar, lịch đăng/tần suất, đo lường) — phần lớn NGOÀI phạm vi (công cụ quản lý tài khoản mạng xã hội, không có module tương ứng trong hệ thống, xem changelog v1.21). Genuine gap áp dụng được: bảng mục tiêu thuật toán theo nền tảng (TikTok = thời gian xem + tỷ lệ hoàn thành; YouTube = thời gian xem + tỷ lệ nhấp) — enrich `PLATFORM_TIPS_BY_ASPECT_RATIO` (9:16/16:9).

---

## 0. Quyết định đã chốt

| Chủ đề | Quyết định | Lý do |
|---|---|---|
| **KHÔNG gọi AI Provider (image/video generation) trong app** | Người dùng tự chạy prompt trên tool AI ngoài (Midjourney/Kling/Runway/Sora/ChatGPT...), dán kết quả (link/text) vào ô lưu trong module | `app/Services/AI/` hiện chỉ có `AnthropicProvider`/`OpenAIProvider` (chat completion văn bản) — KHÔNG có provider cho image/video generation. Xây provider mới cho từng tool ngoài (Midjourney không có API chính thức, Kling/Runway có API riêng biệt, phí theo lượt) ngoài phạm vi v1. Đúng tiền lệ đã kiểm chứng ở `ContentOutlines`/`CoreIdeaExtractor` (module content khác trong repo) |
| **Platform-wide, KHÔNG organization-scoped** | Không `TenantAwareModel`, không `organization_id` — cùng nhóm `content_outlines`/`content_foundations` | Người dùng xác nhận đây là công cụ nội bộ cho team content của nền tảng, không phải data theo khách hàng/tổ chức riêng |
| **Cấu trúc dữ liệu: Project → nhiều Shot (row)** | 1 `AiVideoStudioProject` (1 chủ đề, vd "Review bỉm") có nhiều `AiVideoStudioShot` (mỗi row = 1 cảnh/shot) | Đúng yêu cầu người dùng: "quản lý theo từng row data chủ đề đó" |
| **Mỗi Shot có đúng 6+1 field theo Director Prompt Template** | `subject`, `action`, `environment`, `camera`, `style`, `constraints` (6 field theo neolemon) + `script_line` (lời thoại — bổ sung, không có trong công thức gốc nhưng bắt buộc cho use-case TVC có voice-over của người dùng) | Người dùng xác nhận: "form có các input nhập prompt cho Subject + Action + Environment + Camera + Style + Constraints". `script_line` tách riêng khỏi `action` vì đây là lời thoại cần đọc, khác hành động hình ảnh — ví dụ thực tế người dùng đưa ra ("Nhà ai vẫn đang đứng canh bếp...") là lời thoại, không phải mô tả hành động |
| **Anchoring ở cấp Project — prefill khi tạo Shot mới, KHÔNG khoá cứng** | Project có `default_subject`/`default_style`/`default_constraints`. Khi tạo Shot mới, 3 field `subject`/`style`/`constraints` tự điền từ default, nhưng vẫn sửa được riêng từng Shot | Kỹ thuật "anchoring description block" (magichour.ai): giữ mô tả nhân vật/sản phẩm/giọng đọc CỐ ĐỊNH xuyên suốt mọi shot để tránh AI tạo lệch nhân vật giữa các đoạn — nhưng không khoá cứng vì có thể có shot đặc biệt cần lệch (vd cảnh cận sản phẩm không cần nhắc nhân vật) |
| **`compiled_prompt` tự sinh, ghi đè khi sửa — KHÔNG versioning** | Mỗi lần lưu Shot, `BuildShotPromptAction` build lại `compiled_prompt` từ 7 field, ghi đè bản cũ | Cùng nguyên tắc đã chốt ở `ContentOutlines` (`generated_prompt`/`article_draft_prompt`/`review_prompt` đều ghi đè, không versioning) — v1 không có nhu cầu xem lại lịch sử prompt |
| **`ai_result` — chỉ lưu text/link, KHÔNG upload file** | 1 cột `longText` tự do — người dùng dán URL ảnh/video (Midjourney/Kling thường tự host) hoặc ghi chú | Người dùng không yêu cầu storage riêng; đúng tinh thần "copy-paste thủ công" đã kiểm chứng ở `ContentOutlines`. Không cần thêm Media Library/hạ tầng upload cho v1 |
| **Quản lý Shot ngay trên trang chi tiết Project (inline), không tách trang riêng/shot** | Trang `show` của Project hiển thị danh sách Shot dạng bảng/card, thêm/sửa/xoá/sắp xếp lại bằng JS (fetch tới API JSON), không load lại trang | Đúng UX "quản lý theo từng row data" — thao tác nhanh trên nhiều row, không hợp lý nếu mỗi lần sửa 1 field phải chuyển trang |
| **Xuất "Director Prompt Template" tổng hợp = ghép các `compiled_prompt` theo `sort_order`** | 1 action/endpoint nối toàn bộ Shot đã có `compiled_prompt`, phân cách bằng heading `## Shot {n}: {label}`, xuất ra 1 khối text lớn để copy/tải `.md` | Đây là đầu ra cuối cùng người dùng cần: "tổng hợp lại thành quy trình director prompt template" — không cần lưu riêng 1 bản snapshot, tính động mỗi lần xem (giống cách `ContentOutlines::show()` tính `promptHtml` động, không lưu HTML) |
| **Không Policy riêng theo model — gate phẳng bằng permission** | Middleware `can:ai_video_studio_template.use`, mọi người có quyền thấy/sửa MỌI project (không owner-based ACL) | Cùng quyết định đã chốt ở `ContentOutlines` §2.1 — không có nhu cầu phân quyền theo người tạo ở v1 |
| **Permission seed riêng module, cấp cho 3 role content hiện có** | `platform_content_editor`, `platform_content_head`, `platform_section_editor` — seed qua `AIVideoStudioTemplatePermissionSeeder`, KHÔNG sửa `config/permissions.php`/`RoleEnum` | Đúng convention đã ghi ở CLAUDE.md: các module content/AI tự seed permission riêng, không thuộc 8 role lõi `RoleEnum` |

---

## 1. Giới thiệu & Mục tiêu

Sản xuất 1 TVC/video quảng cáo bằng AI hiện đòi hỏi lặp lại nhiều lần việc: viết prompt ảnh tham chiếu → chạy trên tool ảnh AI → viết prompt video (kèm lời thoại + yêu cầu giọng đọc) cho từng shot → chạy trên tool video AI → ghép các kết quả theo đúng thứ tự kịch bản. Hiện KHÔNG có nơi nào trong hệ thống tổ chức được quy trình này — người dùng đang làm thủ công trên note/doc rời rạc, dễ quên đồng bộ mô tả nhân vật/giọng đọc giữa các shot (gây lệch nhân vật/giọng đọc giữa các đoạn — vấn đề "identity drift" đã biết).

**Mục tiêu v1:** 1 module quản lý theo cấu trúc `Project (chủ đề) → nhiều Shot (row)`, mỗi Shot có form nhập 7 field theo Director Prompt Template, tự động ghép thành 1 prompt hoàn chỉnh để copy, có ô lưu lại kết quả dán từ AI ngoài, và xuất được toàn bộ project thành 1 tài liệu Director Prompt Template duy nhất.

**Phi mục tiêu v1** (xem §8 chi tiết): không gọi AI Provider, không upload file, không animatic/preview ghép hình, không versioning, không disclosure/compliance tooling.

---

## 2. Kiến trúc dữ liệu

> **Lưu ý migration**: 2 khối code dưới đây mô tả schema HIỆU LỰC (đầy đủ field) cho dễ đọc, nhưng
> các field đánh dấu "v1.2"-"v1.6" KHÔNG nằm trong `Schema::create` gốc (2 file đó đã `Ran` ở batch
> 95 trước khi có các yêu cầu này) — mỗi bản nằm trong 1 migration `Schema::table` (ALTER) riêng:
> `..._190001_add_hedra_technique_fields...` (v1.2), `..._200001_add_deepreel_mood_duration_fields...`
> (v1.3), `..._210001_add_byteplus_audio_and_reference_fields...` (v1.4),
> `..._220001_add_resolution_field...` (v1.5), `..._230001_add_marketing_video_fields...` (v1.6),
> `2026_08_12_100001_add_two_step_prompt_and_reference_composition_fields...` (v1.10),
> `2026_08_13_100001_add_video_formula_field...` (v1.16) —
> cùng pattern `Modules/ContentOutlines/database/migrations/2026_08_07_170001_add_article_drafting_fields_to_content_outlines_table.php`
> (KHÔNG sửa migration đã chạy — luôn thêm migration ALTER mới).

### 2.1 `ai_video_studio_projects`

```php
Schema::create('ai_video_studio_projects', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique(); // route key

    $table->string('name', 200);          // chủ đề, vd "Review bỉm"
    $table->text('description')->nullable();

    // Creative Brief (v1.2 — Hedra Step 1 "Define Creative Brief") — không bắt buộc.
    // CHỈ dành cho người đọc (khối Creative Brief ở tài liệu xuất) — KHÔNG vào prompt (§3.1, v1.7).
    $table->text('objective')->nullable();            // mục tiêu kinh doanh, không đổi thứ gì trên khung hình

    // 6 field BỐI CẢNH PROJECT — được RENDER vào compiled_prompt của mọi Shot; sửa 1 trong 6 field
    // này sẽ build lại prompt toàn bộ Shot (§3.6, v1.7). Tập giá trị: hằng số ở Model (§4.1).
    $table->text('target_audience')->nullable();      // đối tượng khán giả mục tiêu
    // v1.6 (LinkedIn marketing guide) — Step 1 "Define Objectives" tách 2 khái niệm này riêng.
    // v1.14 (imagine.art) — thêm spokesperson|offer_promo, `string(20)` vẫn đủ chỗ (dài nhất 12 ký tự).
    // v1.20 (leadde.ai) — thêm onboarding (retention, `string(20)` vẫn đủ chỗ, 10 ký tự).
    $table->string('video_type', 20)->nullable();     // explainer|testimonial|product_demo|storytelling|spokesperson|offer_promo|onboarding|other
    // v1.16 (tulsainternetmarketingservice.com) — TRỤC KHÁC video_type: công thức KỂ CHUYỆN (trình
    // tự), không phải loại nội dung. Migration ALTER riêng (`2026_08_13_100001...`), đặt sau video_type.
    // v1.18 (ngram.com) thêm demo_5part; v1.20 (leadde.ai) thêm abcd|testimonial_5part|onboarding_5part
    // — dài nhất `testimonial_5part` 18 ký tự, `string(20)` vẫn đủ chỗ, không cần migration mới.
    $table->string('video_formula', 20)->nullable();  // psa|bab|hook_value_cta|demo_5part|abcd|testimonial_5part|onboarding_5part
    $table->text('core_message')->nullable();         // thông điệp/lời hứa cụ thể, VD "Tăng năng suất 40%"
    $table->string('aspect_ratio', 10)->nullable();   // 16:9|9:16|1:1|4:5
    $table->string('resolution', 10)->nullable();     // v1.5 (pyxeljam.com) — 720p|1080p|2K|4K

    // Anchoring — prefill vào Shot mới, KHÔNG bắt buộc (§0).
    $table->text('default_subject')->nullable();     // mô tả nhân vật/sản phẩm cố định
    $table->text('reference_image_url')->nullable(); // v1.2 — anchor bằng ẢNH tham chiếu (Hedra Step 2 + magichour.ai)
    // v1.11 (phản hồi người dùng, cùng ngày với v1.10) — v1.10 ban đầu thêm 2 field ảnh nguồn
    // (kol_reference_image_url/product_reference_image_url) + BuildReferenceCompositionPromptAction để
    // TỰ SINH prompt "ghép 2 ảnh thành 1 ảnh mới". Sai bài toán: người dùng đã tự chuẩn bị SẴN ảnh KOL +
    // sản phẩm ở NGOÀI tool trước khi tới bước này — không cần tool sinh prompt ghép hộ. Thay bằng 1 ô
    // text NGẮN tự gõ để nhớ lại ngữ cảnh của ảnh tham chiếu đã có sẵn — KHÔNG tự sinh, KHÔNG vào
    // compiled_prompt của Shot nào (cùng nhóm "chỉ dành cho người đọc" với `objective`, §3.1).
    $table->text('reference_context_prompt')->nullable(); // mô tả ngắn tự gõ, VD "KOL mặc áo trắng, cầm sản phẩm..."
    $table->text('default_style')->nullable();        // phong cách hình ảnh cố định
    $table->text('default_constraints')->nullable();  // ràng buộc cố định (giọng đọc, "không text"...)

    $table->string('status', 10)->default('draft');   // draft|active|archived

    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();

    $table->index('status');
});
```

### 2.2 `ai_video_studio_shots`

```php
Schema::create('ai_video_studio_shots', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();

    $table->foreignId('project_id')->constrained('ai_video_studio_projects')->cascadeOnDelete();
    $table->unsignedInteger('sort_order')->default(0);
    $table->string('label', 200)->nullable(); // vd "Shot 1 — Hook"

    // 6 field Director Prompt Template (neolemon) + 1 field bổ sung script_line (§0).
    $table->text('subject')->nullable();
    $table->text('action')->nullable();
    $table->text('environment')->nullable();
    $table->text('camera')->nullable();
    $table->text('style')->nullable();
    // v1.3 (deepreel.com) — 2 thành phần còn thiếu của công thức "Subject+Style+Camera+Mood+Duration",
    // CẢ HAI đưa vào compiled_prompt (nội dung prompt thật, khác model_tool/qc_notes bên dưới).
    $table->text('mood')->nullable();                          // tâm trạng/tông cảm xúc
    $table->unsignedSmallInteger('duration_seconds')->nullable(); // thời lượng ước tính (giây)
    // v1.10 (LinkedIn, ví dụ Synthesia "kịch bản theo timeline 0-5s/5-15s/kết") — breakdown tự do của
    // duration_seconds theo mốc thời gian, cho shot dài/nhiều nhịp. Nội dung prompt thật → compiled_prompt.
    $table->text('timeline_breakdown')->nullable();
    // v1.4 (byteplus.com) — âm thanh môi trường + nhạc nền, KHÁC lời thoại script_line bên dưới.
    // Nội dung prompt thật → đưa vào compiled_prompt.
    $table->text('audio_direction')->nullable();
    $table->text('script_line')->nullable(); // lời thoại — riêng biệt với action
    // v1.6 (LinkedIn marketing guide) — CTA specifications (button text, countdown). Nội dung
    // prompt thật (overlay AI cần vẽ) → đưa vào compiled_prompt, đặt sau lời thoại.
    $table->string('cta_text', 200)->nullable();
    $table->text('constraints')->nullable();

    // v1.2 — Hedra Model Selection Criteria + Step 4 "test alternative models": metadata, KHÔNG
    // đưa vào compiled_prompt (không gửi cho AI generator ngoài).
    $table->string('model_tool', 150)->nullable();
    // v1.4 (byteplus.com) — link ảnh/video/audio tham chiếu bổ sung riêng cho shot này (khái niệm
    // multi-reference của nguồn), ngoài ảnh anchor chung của Project. Metadata — KHÔNG đưa vào compiled_prompt.
    $table->text('reference_assets')->nullable();

    $table->longText('compiled_prompt')->nullable(); // BuildShotPromptAction::handle() — TỰ SINH, quy trình 1 bước (text-to-video), ghi đè khi sửa field khác
    // v1.10 (LinkedIn mục 3.2 "Image-to-Video") — quy trình 2 bước thay thế: prompt ảnh tĩnh (keyframe)
    // + prompt hoạt hình hoá ảnh đó, tách riêng khỏi compiled_prompt. v1.12 (phản hồi người dùng —
    // 2 field TỰ SINH này gây hiểu nhầm "không gõ được = lỗi") — hết tự sinh, giờ NHẬP TAY tự do như
    // model_tool/qc_notes, KHÔNG còn method BuildShotPromptAction::buildImagePrompt()/buildMotionPrompt().
    $table->longText('image_prompt')->nullable();  // nhập tay, KHÔNG vào compiled_prompt
    $table->longText('motion_prompt')->nullable(); // nhập tay, KHÔNG vào compiled_prompt
    $table->longText('ai_result')->nullable();       // dán link/text kết quả từ AI ngoài
    $table->text('qc_notes')->nullable();            // v1.2 — Hedra Step 3 evaluation checklist, ghi chú tự do

    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();

    $table->index(['project_id', 'sort_order']);
});
```

> Theo đúng convention migration của project (README `database/migrations`): viết 2 file `Schema::create` này vào `Modules/AIVideoStudioTemplate/database/migrations/`, sau đó chạy `php artisan migration:sync` để merge vào `render_migration_file.json`, rồi `php artisan migration:generate --fresh` để sinh `database/migrations/generated/` — KHÔNG viết tay vào `generated/`/`extensions/`.

---

## 3. Actions (CQRS-lite)

### 3.1 `BuildShotPromptAction` — lõi của module

> **v1.7 — mục này mô tả CẤU TRÚC OUTPUT, không chép lại code.** Bản v1.0-v1.6 dán nguyên thân hàm
> vào đây và nó đã trôi (còn kẹt ở 7 field, thiếu Mood/Duration/Audio/CTA thêm từ v1.3-v1.6). Contract
> thật sự cần chốt là *chuỗi sinh ra trông thế nào*; code xem tại
> `Modules/AIVideoStudioTemplate/app/Features/ShotManagement/Actions/BuildShotPromptAction.php`.

Chữ ký: `handle(AiVideoStudioShot $shot, ?AiVideoStudioProject $project = null): string`
— `$project` bỏ trống thì tự lấy `$shot->project` (an toàn với model chưa lưu: belongsTo khoá ngoại
null KHÔNG chạm DB); caller nào sẵn có Project thì truyền vào để tránh query thừa.

Output đầy đủ (mọi dòng đều BỎ QUA nếu field rỗng):

```text
Bạn là chuyên gia video chuyên nghiệp, hãy tạo 1 cảnh quay (shot) chuyên nghiệp, chuyển cảnh mượt, theo đúng mô tả sau:

ĐỊNH DẠNG (Format): khung hình 9:16, độ phân giải 1080p     ← Project (v1.7)

CHỦ THỂ (Subject): ...
HÀNH ĐỘNG (Action): ...
BỐI CẢNH (Environment): ...
GÓC MÁY (Camera): ...
PHONG CÁCH (Style): ...
TÂM TRẠNG (Mood): ...                                        ← v1.3
THỜI LƯỢNG (Duration): 15 giây                               ← v1.3
TIMELINE NỘI DUNG (Content Timeline): 0-5s: ...; 5-15s: ...  ← v1.10
ÂM THANH (Audio/Soundscape): ...                             ← v1.4
LỜI THOẠI: "..."
CALL-TO-ACTION (CTA): ...                                    ← v1.6
RÀNG BUỘC (Constraints): ...

--- BỐI CẢNH CHIẾN DỊCH (tham khảo để giữ đúng tông — KHÔNG cần thể hiện toàn bộ trong shot này) ---
- Loại video: Testimonial (đánh giá/trải nghiệm thật)        ← Project (v1.7)
- Công thức kịch bản: Before–After–Bridge (60-90s)           ← Project (v1.16)
- Khán giả mục tiêu: ...
- Thông điệp xuyên suốt: ...
```

**Quy tắc bất biến (v1.7):**

| Quy tắc | Lý do |
|---|---|
| Shot chưa có field nội dung nào → trả **chuỗi rỗng** (caller lưu `null`) | Không sinh "prompt ma" chỉ có câu dẫn; đồng thời làm placeholder §3.5 thành nhánh sống thật |
| `label` KHÔNG tính là nội dung | Nhãn quản lý nội bộ, không phải mô tả cảnh |
| `ĐỊNH DẠNG` đứng TRƯỚC nội dung shot, giữ thật ngắn | "20-30 từ đầu có trọng số cao nhất" (deepreel.com) — dành chỗ đó cho Subject/Action; byteplus cũng dẫn ví dụ mở đầu bằng "UGC-style 9:16 video" |
| `BỐI CẢNH CHIẾN DỊCH` đứng CUỐI + ghi rõ "KHÔNG cần thể hiện toàn bộ" | Chặn lỗi "overloading" — AI cố nhồi cả chiến dịch vào 1 clip 4-15 giây |
| `objective` / `reference_image_url` / `model_tool` / `reference_assets` / `qc_notes` / `ai_result` / `label` / `image_prompt` / `motion_prompt` (v1.12) **KHÔNG BAO GIỜ** vào prompt | `objective` là mục tiêu kinh doanh (không đổi thứ gì trên khung hình); ảnh/asset tham chiếu là file đính kèm, cú pháp khác nhau tuỳ tool (§0/v1.4); còn lại là metadata nội bộ — `image_prompt`/`motion_prompt` nhập tay từ v1.12, cùng nhóm |
| Giá trị nhiều dòng: thụt lề 4 space cho dòng nối, chuẩn hoá `\r\n`→`\n` | Giữ ranh giới `NHÃN: giá trị` khi người dùng dán text xuống dòng; KHÔNG gộp về 1 dòng để không phá lời thoại nhiều câu |
| **KHÔNG** đưa vị trí "Shot N/M" vào prompt | Sẽ lỗi thời sau mỗi lần thêm/xoá/đổi thứ tự shot; đổi lại phải build lại toàn bộ sau mỗi cú bấm "↑/↓" — không đáng. Thứ tự đã có ở heading `## Shot N` của tài liệu xuất (§3.5) |

**v1.10 — `buildImagePrompt()`/`buildMotionPrompt()` — ĐÃ GỠ BỎ (v1.12):** 2 method này từng tự sinh
`image_prompt`/`motion_prompt` từ field khác (Subject/Camera/Style/Action/...), theo quy trình 2 bước
của bài LinkedIn mục 3.2 "Image-to-Video". Phản hồi người dùng: 2 ô hiển thị kết quả bị khoá readonly
(đúng thiết kế "tự sinh") nhưng gây hiểu nhầm là lỗi "không gõ được text". Đã xoá 2 method — xem
docblock class + `image_prompt`/`motion_prompt` ở §2.2 (giờ nhập tay tự do, cùng nhóm `model_tool`).

### 3.2 `CreateShotAction` — prefill anchoring từ Project

```php
public function handle(AiVideoStudioProject $project, ShotInputData $data, int $userId): AiVideoStudioShot
{
    $shot = $project->shots()->create([
        'sort_order'   => $data->sortOrder ?? ($project->shots()->max('sort_order') + 1),
        'label'        => $data->label,
        'subject'      => $data->subject ?: $project->default_subject,
        'action'       => $data->action,
        'environment'  => $data->environment,
        'camera'       => $data->camera,
        'style'        => $data->style ?: $project->default_style,
        'constraints'  => $data->constraints ?: $project->default_constraints,
        'script_line'  => $data->scriptLine,
        'created_by'   => $userId,
        'updated_by'   => $userId,
    ]);

    $shot->update(['compiled_prompt' => app(BuildShotPromptAction::class)->handle($shot)]);

    return $shot;
}
```

`UpdateShotAction` — cùng logic, gọi lại `BuildShotPromptAction` sau khi update field, KHÔNG tự prefill lại từ default (chỉ áp dụng lúc TẠO MỚI, tránh ghi đè field người dùng đã cố tình sửa khác default).

### 3.3 `SaveShotAiResultAction` — lưu kết quả dán tay

```php
public function handle(AiVideoStudioShot $shot, string $aiResult, int $userId): void
{
    $shot->update(['ai_result' => $aiResult, 'updated_by' => $userId]);
}
```

### 3.4 `ReorderShotsAction`

```php
/**
 * @param  int[]  $shotIdsInOrder
 *
 * @throws \Illuminate\Auth\Access\AuthorizationException  Nếu có ID không thuộc $project — chặn
 *   request thủ công cố cập nhật sort_order của shot thuộc project KHÁC (2 project khác nhau đều
 *   được phép bởi cùng 1 permission phẳng 'ai_video_studio_template.use' — không có ranh giới
 *   quyền giữa các project, nên phải tự kiểm tra ownership ở tầng Action, KHÔNG dựa vào
 *   Policy/route-model-binding để chặn hộ).
 */
public function handle(AiVideoStudioProject $project, array $shotIdsInOrder): void
{
    $ownedIds = $project->shots()->pluck('id');

    if (collect($shotIdsInOrder)->diff($ownedIds)->isNotEmpty()) {
        throw new \Illuminate\Auth\Access\AuthorizationException('1 hoặc nhiều shot không thuộc project này.');
    }

    foreach ($shotIdsInOrder as $index => $shotId) {
        $project->shots()->whereKey($shotId)->update(['sort_order' => $index]);
    }
}
```

`ShotApiController::reorder()` bắt `AuthorizationException` này và trả **422** (không phải 403 — đây là lỗi input sai, không phải thiếu quyền) kèm message rõ, theo đúng shape lỗi chung ở §6.1.

### 3.5 `CompileProjectDirectorPromptAction` — xuất tài liệu tổng hợp

> **v1.2** — thêm khối `## Creative Brief` đầu tài liệu (chỉ in field NÀO CÓ giá trị: `objective`/
> `target_audience`/`aspect_ratio`/`reference_image_url`) + khối `## Checklist đánh giá output` tĩnh
> (5 mục theo Hedra Step 3, luôn xuất hiện kể cả project 0 shot/0 brief) + mỗi shot thêm dòng
> **Model/Tool đã dùng** (nếu có `model_tool`) và **Ghi chú đánh giá (QC)** (nếu có `qc_notes`).
>
> **v1.3 (deepreel.com)** — thêm dòng **Tổng thời lượng ước tính** vào khối Creative Brief (cộng
> `duration_seconds` các shot đã điền, CHỈ hiện nếu ≥1 shot có điền) + thêm khối
> `## Checklist trước khi generate` tĩnh (8 mục, đặt TRƯỚC khối Checklist đánh giá output — 2 khối
> khác thời điểm sử dụng: trước vs sau khi chạy prompt qua tool AI ngoài).
>
> **v1.4 (byteplus.com)** — mỗi shot thêm dòng **Tài liệu tham chiếu bổ sung** (nếu có
> `reference_assets`), đặt ngay sau dòng Model/Tool đã dùng.
>
> **v1.5/v1.6** — khối Creative Brief thêm **Độ phân giải**, **Loại video**, **Thông điệp cốt lõi**;
> `QC_CHECKLIST` thêm 3 mục marketing.
>
> **v1.7** — placeholder `_(chưa có prompt — điền field còn thiếu)_` giờ mới thật sự chạy được với
> dữ liệu thật: trước đó `compiled_prompt` không bao giờ null nên nhánh này là nhánh chết (§3.1).
> Khối Creative Brief in **nhãn** của `video_type` thay vì slug thô (`videoTypeLabel()`, §4.1).
>
> **v1.8 (sentx.ai)** — `PRE_GENERATION_CHECKLIST` thêm 1 mục "single hero focus" (giới hạn số chủ
> thể, khác "1 hành động/shot" đã có). Thêm khối tĩnh `## Xử lý sự cố theo triệu chứng` (5 mục,
> `TROUBLESHOOTING_GUIDE`) đặt SAU khối `## Checklist đánh giá output` — không phụ thuộc dữ liệu
> project/shot, luôn xuất hiện kể cả project 0 shot.
>
> **v1.9 (veed.io)** — khối `## Creative Brief` thêm 2 dòng CÓ ĐIỀU KIỆN: **Gợi ý theo nền tảng**
> (ngay sau dòng Tỷ lệ khung hình, chỉ in nếu `aspect_ratio` là `9:16`/`16:9` — có tip khớp trong
> `PLATFORM_TIPS_BY_ASPECT_RATIO`) và **Gợi ý theo loại video** (ngay sau dòng Loại video, chỉ in
> nếu `video_type` khác `other` — có tip khớp trong `CONTENT_TIPS_BY_VIDEO_TYPE`). Dùng lại đúng 2
> field đã tồn tại (`aspect_ratio` v1.2, `video_type` v1.6) — không thêm field/schema mới.
>
> **v1.10 (LinkedIn, mục 3.2 "Image-to-Video")** — mỗi shot section thêm 2 dòng CÓ ĐIỀU KIỆN NGAY SAU
> `compiled_prompt` chính: **Prompt Ảnh (keyframe)** (nếu có `image_prompt`) và **Prompt Motion (hoạt
> hình hoá ảnh)** (nếu có `motion_prompt`). v1.12 — 2 field này đổi từ tự sinh sang nhập tay (§3.1),
> nhưng logic hiển thị ở đây KHÔNG đổi (vẫn chỉ in nếu có giá trị, bất kể nguồn gốc giá trị là gì).
>
> **v1.15 (mindstudio.ai — Agent 2/4 "Voiceover → Assembly", phản hồi người dùng sau v1.14 rằng
> chưa có gì "kỹ thuật mới" thật sự)** — thêm `buildEdlBlock()`: khối `## EDL — Bảng đối chiếu Lời
> thoại & Thời gian` (bảng Markdown Cảnh|Thời gian|Lời thoại|Mô tả hình ảnh), đặt CUỐI phần header
> (sau khối troubleshooting, TRƯỚC danh sách Shot). Thời gian cộng dồn từ `duration_seconds` theo thứ
> tự `sort_order` — shot KHÔNG điền `duration_seconds` hiện `"{cursor}s+ (?)"` (không cộng dồn tiếp,
> báo hiệu mốc sau đó chỉ là ước lượng) thay vì giả định 1 con số. Cột "Mô tả hình ảnh" ưu tiên
> `label`, rơi xuống `Str::limit(subject + action, 60)` nếu label rỗng. CHỈ xuất hiện nếu project có
> **ít nhất 1 shot** và **ít nhất 1 trong 2** `duration_seconds`/`script_line` có điền ở bất kỳ shot
> nào — bảng toàn placeholder không có giá trị tham khảo. Đây là bảng DUY NHẤT trong tài liệu xuất
> tổng hợp timing + lời thoại cùng lúc — timeline trực quan (`#aivsTimeline`, §8) chỉ có trên trang
> `show`, KHÔNG có trong file `.md` tải về đưa cho editor.

```php
namespace Modules\AIVideoStudioTemplate\Features\ProjectManagement\Actions;

class CompileProjectDirectorPromptAction
{
    use AsAction;

    public function handle(AiVideoStudioProject $project): string
    {
        $shots = $project->shots()->orderBy('sort_order')->get();
        // v1.2: $header = $this->buildCreativeBriefBlock($project) — xem code thật trong Action.

        $sections = $shots->map(function (AiVideoStudioShot $shot, int $index) {
            $heading = '## Shot '.($index + 1).($shot->label ? " — {$shot->label}" : '');
            $body = $shot->compiled_prompt ?: '_(chưa có prompt — điền field còn thiếu)_';
            $result = filled($shot->ai_result) ? "\n\n**Kết quả AI:**\n{$shot->ai_result}" : '';

            return "{$heading}\n\n{$body}{$result}";
        });

        return "# Director Prompt Template — {$project->name}\n\n".$sections->implode("\n\n---\n\n");
    }
}
```

### 3.6 Hai cơ chế lan truyền từ Project xuống Shot — ĐỪNG NHẦM (v1.7)

Đây là điểm dễ hiểu sai nhất của module sau v1.7, vì 2 nhóm field ở cùng 1 form Project nhưng hành xử
ngược nhau:

| | **Anchoring** (`default_subject`/`default_style`/`default_constraints`) | **Bối cảnh Project** (`aspect_ratio`/`resolution`/`video_type`/`target_audience`/`core_message`) |
|---|---|---|
| Cơ chế | **Sao chép** giá trị vào field của Shot lúc TẠO MỚI | **Render** vào `compiled_prompt` mỗi lần build |
| Sửa ở Project | **KHÔNG** lan xuống Shot đã tạo | **CÓ** — build lại `compiled_prompt` toàn bộ Shot |
| Vì sao | Giá trị đã nằm trong field của Shot và người dùng có thể đã cố ý sửa khác default — ghi đè sẽ mất công sức của họ (§0) | Không nằm ở field nào của Shot nên **không có gì để mất**; không build lại thì mọi prompt đã lưu nói sai định dạng/tông |

Thực thi ở `UpdateProjectAction::rebuildShotPrompts()` — chỉ đụng đúng cột tự sinh `compiled_prompt`,
không chạm field nội dung nào của Shot. Chỉ chạy khi 1 trong 5 field bối cảnh thực sự đổi (so sánh
trước/sau `update()`), nên sửa `name`/`description`/`objective`/anchoring KHÔNG gây ghi thừa.

> **v1.10 → v1.12**: từ v1.10, method này CŨNG rebuild `image_prompt`/`motion_prompt` (đọc
> `aspect_ratio`/`resolution` qua `buildFormatLine()` như `compiled_prompt`). v1.12 bỏ lại — 2 field
> này giờ nhập tay tự do (§3.1/§2.2), rebuild ở đây sẽ GHI ĐÈ mất nội dung người dùng vừa gõ, đúng lỗi
> mà cơ chế "không đụng field nội dung Shot" ở bảng trên tồn tại để tránh.

Cả 2 khác biệt này **bắt buộc hiển thị trên UI** (§8): callout ở khối Creative Brief ("sửa sẽ tự động
build lại prompt") và callout ở khối Anchoring ("KHÔNG tự động cập nhật Shot đã tạo").

### 3.7 `BuildReferenceCompositionPromptAction` — ĐÃ GỠ BỎ (v1.11, cùng ngày với v1.10)

v1.10 từng thêm Action này (prep-prompt tự sinh để ghép `kol_reference_image_url` +
`product_reference_image_url` thành 1 ảnh mới, lấy cảm hứng từ ví dụ Kling AI của bài LinkedIn) + 2
field ảnh nguồn tương ứng ở §2.1. Phản hồi người dùng ngay sau đó: sai bài toán — người dùng đã tự
chuẩn bị SẴN ảnh KOL + sản phẩm ở NGOÀI tool trước khi tới bước này, không cần tool tự sinh prompt
"ghép ảnh" hộ. Đã xoá file Action + 2 field ảnh nguồn, thay bằng 1 field `reference_context_prompt`
(text ngắn, tự gõ, xem §2.1) — không có Action/logic tự sinh nào thay thế, đây thuần là 1 ô ghi chú.
Vì migration ALTER `2026_08_12_100001...` của v1.10 chưa release ở môi trường nào khác lúc sửa (cùng
ngày, cùng phiên làm việc, chưa commit), thay đổi này SỬA TRỰC TIẾP vào chính file migration đó thay
vì thêm 1 migration mới chỉ để đảo ngược — xem ghi chú đầu file migration đó.

---

## 4. Cấu trúc module (AVSA + CQRS-lite)

```
Modules/AIVideoStudioTemplate/
├── app/
│   ├── Features/
│   │   ├── ProjectManagement/
│   │   │   ├── Actions/{CreateProjectAction,UpdateProjectAction,DeleteProjectAction}.php
│   │   │   ├── Actions/CompileProjectDirectorPromptAction.php
│   │   │   ├── Data/ProjectInputData.php
│   │   │   ├── Http/ProjectController.php
│   │   │   ├── Http/Requests/{StoreProjectRequest,UpdateProjectRequest}.php
│   │   │   └── Queries/{ListProjectsForAdminHandler,ListProjectsForAdminQuery}.php
│   │   └── ShotManagement/
│   │       ├── Actions/{BuildShotPromptAction,CreateShotAction,UpdateShotAction,DeleteShotAction,ReorderShotsAction,SaveShotAiResultAction}.php
│   │       ├── Data/ShotInputData.php
│   │       ├── Http/ShotApiController.php   // JSON — CRUD inline trên trang show project
│   │       └── Http/Requests/{StoreShotRequest,UpdateShotRequest,SaveShotAiResultRequest,ReorderShotsRequest}.php
│   ├── Models/{AiVideoStudioProject,AiVideoStudioShot}.php
│   └── Providers/{AIVideoStudioTemplateServiceProvider,RouteServiceProvider}.php
├── database/
│   ├── migrations/{..._create_ai_video_studio_projects_table,..._create_ai_video_studio_shots_table}.php
│   └── seeders/AIVideoStudioTemplatePermissionSeeder.php
├── resources/views/
│   ├── index.blade.php   // danh sách project
│   ├── create.blade.php / edit.blade.php  // form project (name/description/3 default field)
│   └── show.blade.php    // chi tiết project — quản lý shot inline (JS gọi ShotApiController)
├── routes/web.php
└── module.json
```

### 4.1 Models — định nghĩa đầy đủ (KHÔNG suy ra từ `ContentOutline`, viết rõ ở đây)

Cả 2 model: KHÔNG `TenantAwareModel`, KHÔNG soft-delete, KHÔNG `LogsActivity` — cùng lý do `ContentOutline` (§2.1 spec đó: không phải credential/tài sản nghiệp vụ cần audit).

> **v1.7 — hằng số tập giá trị đặt ở `AiVideoStudioProject`, là NGUỒN DUY NHẤT:**
> `VIDEO_TYPES`, `ASPECT_RATIOS`, `RESOLUTIONS`, `STATUSES` (dạng `key => nhãn hiển thị`), cùng
> helper `videoTypeLabel()`. FormRequest dùng `Rule::in(array_keys(...))`, Blade dùng chính mảng đó
> cho `<select>` và để hiển thị. Trước v1.7 mỗi tập được viết lại ở 3 nơi (`_form.blade.php` + 2
> FormRequest) — thêm 1 lựa chọn ở form mà quên sửa validate sẽ ra 422 khó hiểu, và trang `show`/tài
> liệu xuất in slug thô (`testimonial`) trong khi form hiện nhãn đẹp. Thêm giá trị mới: sửa đúng 1 chỗ.
>
> **v1.16** — thêm `VIDEO_FORMULAS` (`psa`/`bab`/`hook_value_cta` => nhãn kèm khoảng thời lượng, VD
> `"Problem–Solution–CTA (30-60s)"`) + helper `videoFormulaLabel()`, cùng pattern trên. Không có khoá
> `other` (khác `VIDEO_TYPES`) — để trống nghĩa là "không áp dụng công thức nào cố định".
>
> **v1.18 (ngram.com/blog/demo-video-script-template)** — thêm khoá thứ 4 `demo_5part` =>
> `"Demo Script 5 phần (90-120s)"` vào `VIDEO_FORMULAS`. Validate/`<select>`/`FORMULA_TIPS_BY_VIDEO_
> FORMULA`/`FORMULA_BEATS` (§3.5) tự động nhận khoá mới qua `array_keys()`/`@foreach` đã có sẵn —
> KHÔNG cần sửa `StoreProjectRequest`/`UpdateProjectRequest`/`_form.blade.php`.
>
> **v1.20 (leadde.ai/blog/marketing-script-template)** — thêm 1 khoá `VIDEO_TYPES` (`onboarding` =>
> `"Onboarding khách hàng (SOP/đào tạo/hướng dẫn sử dụng)"`) + 3 khoá `VIDEO_FORMULAS` (`abcd`,
> `testimonial_5part`, `onboarding_5part` — xem lý do không trùng lặp với 4 công thức cũ ở docblock
> class `CompileProjectDirectorPromptAction`, §3.5). Cùng cơ chế v1.16/v1.18 — thêm giá trị vào 2 hằng
> số này là ĐỦ, không đổi `StoreProjectRequest`/`UpdateProjectRequest`/`_form.blade.php`/migration.

```php
namespace Modules\AIVideoStudioTemplate\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Support\Str;

class AiVideoStudioProject extends Model
{
    protected $table = 'ai_video_studio_projects';

    protected $fillable = [
        'uuid', 'name', 'description',
        'default_subject', 'default_style', 'default_constraints',
        'status', 'created_by', 'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->uuid ??= (string) Str::uuid();
            $model->status ??= 'draft';
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function shots(): HasMany
    {
        return $this->hasMany(AiVideoStudioShot::class, 'project_id')->orderBy('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

```php
namespace Modules\AIVideoStudioTemplate\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AiVideoStudioShot extends Model
{
    protected $table = 'ai_video_studio_shots';

    protected $fillable = [
        'uuid', 'project_id', 'sort_order', 'label',
        'subject', 'action', 'environment', 'camera', 'style', 'constraints', 'script_line',
        'compiled_prompt', 'ai_result', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(AiVideoStudioProject::class, 'project_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

> `AiVideoStudioShot` KHÔNG cần `getRouteKeyName() = 'uuid'` cho route `dashboard/ai-video-studio/*` (không có route web nào bind trực tiếp theo shot — chỉ có route API `shots/{shot}`), nhưng vẫn đặt `uuid` làm route key để nhất quán quy ước toàn app (không lộ `id` tuần tự tăng dần qua API) và tránh route-model-binding tình cờ dùng `id` nếu sau này thêm route web cho shot.

---

## 5. RBAC

| Permission | Cấp cho role | Ghi chú |
|---|---|---|
| `ai_video_studio_template.use` | `platform_content_editor`, `platform_content_head`, `platform_section_editor`, `system_admin` | Seed qua `AIVideoStudioTemplatePermissionSeeder`, cùng nhóm `ContentOutlinesPermissionSeeder`. `super-admin` luôn có mọi permission qua `Gate::before()` |

Middleware gate phẳng `can:ai_video_studio_template.use` trên toàn bộ route (web + API JSON) — không Policy riêng theo model.

> **Cập nhật sau triển khai (2026-08-09):** bổ sung `system_admin` (role RBAC lõi, `RoleEnum::System_Admin`)
> vào danh sách được cấp permission — theo yêu cầu thực tế lúc kiểm thử (account quản trị hệ thống
> cũng cần dùng được module), khác quyết định gốc ở trên (chỉ 3 role content). Không đổi
> `config/permissions.php`/`RoleEnum`, chỉ thêm 1 dòng gán permission trong
> `AIVideoStudioTemplatePermissionSeeder`.

---

## 6. Routes

```php
Route::middleware(['auth', 'can:ai_video_studio_template.use'])
    ->prefix('dashboard/ai-video-studio')
    ->name('backend.aivideostudiotemplate.')
    ->group(function (): void {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('create', [ProjectController::class, 'create'])->name('create');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::get('{project}', [ProjectController::class, 'show'])->name('show');
        Route::get('{project}/edit', [ProjectController::class, 'edit'])->name('edit');
        Route::put('{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('{project}', [ProjectController::class, 'destroy'])->name('destroy');
        Route::get('{project}/export', [ProjectController::class, 'export'])->name('export'); // .md download
    });

// JSON — quản lý Shot inline trên trang show (fetch, không reload trang).
Route::middleware(['auth', 'can:ai_video_studio_template.use'])
    ->prefix('backend/api/ai-video-studio')
    ->name('backend.api.aivideostudiotemplate.')
    ->group(function (): void {
        Route::post('projects/{project}/shots', [ShotApiController::class, 'store'])->name('shots.store');
        Route::put('shots/{shot}', [ShotApiController::class, 'update'])->name('shots.update');
        Route::delete('shots/{shot}', [ShotApiController::class, 'destroy'])->name('shots.destroy');
        Route::post('projects/{project}/shots/reorder', [ShotApiController::class, 'reorder'])->name('shots.reorder');
        Route::put('shots/{shot}/result', [ShotApiController::class, 'saveResult'])->name('shots.save-result');
    });
```

### 6.1 Contract JSON — bắt buộc để JS phía client làm việc được (điểm còn thiếu ở bản v1.0)

Đây là residual risk lớn nhất của spec (UI inline phụ thuộc hoàn toàn vào shape response đúng) — chốt rõ ở đây thay vì để implementer tự suy đoán:

- **`POST shots`/`PUT shots/{shot}`** — thành công (200/201) trả về **toàn bộ shot resource** (không chỉ `compiled_prompt`), để JS cập nhật lại MỌI field hiển thị (kể cả field đã bị prefill từ default lúc tạo mới):
  ```json
  {
    "id": 12, "uuid": "...", "sort_order": 0, "label": "Shot 1 — Hook",
    "subject": "...", "action": "...", "environment": "...", "camera": "...",
    "style": "...", "mood": "...", "duration_seconds": 15, "timeline_breakdown": "...", "audio_direction": "...",
    "constraints": "...", "script_line": "...",
    "model_tool": "...", "reference_assets": "...",
    "compiled_prompt": "...", "image_prompt": "...", "motion_prompt": "...",
    "ai_result": null, "qc_notes": "..."
  }
  ```
  (`model_tool`/`qc_notes` thêm ở v1.2 — Hedra Model Selection Criteria + Step 3 evaluation checklist.
  `mood`/`duration_seconds` thêm ở v1.3 — deepreel.com, 2 field này nằm trong `compiled_prompt`.
  `audio_direction` (trong `compiled_prompt`) + `reference_assets` (metadata) thêm ở v1.4 — byteplus.com.
  `timeline_breakdown`/`image_prompt`/`motion_prompt` thêm ở v1.10 — LinkedIn, xem §3.1/§3.6.)
- **`PUT shots/{shot}` là MERGE, KHÔNG phải replace (v1.7)** — field **vắng mặt** trong request giữ nguyên giá trị đang lưu; gửi **chuỗi rỗng** vẫn xoá field như cũ. Trước v1.7 field vắng mặt bị ghi `null`, nên `PUT {"subject":"x"}` xoá sạch 14 field còn lại; UI luôn gửi đủ nên chưa lộ, nhưng chỉ cần thêm 1 field mà quên gắn `.aivs-field` trong JS là field đó bị xoá ở MỌI shot sau mỗi lần gõ. Payload đầy đủ (như UI đang gửi) hành xử y hệt trước, nên đổi này KHÔNG phá contract hiện có.
- **Lỗi validate (422)** — dùng đúng shape mặc định của Laravel FormRequest (`{"message": "...", "errors": {"field": ["..."]}}`) — KHÔNG tự chế shape lỗi riêng, để JS xử lý bằng 1 hàm dùng chung cho mọi form trong module.
- **Lỗi ownership ở `reorder` (§3.4)** — trả 422 (không phải 403) với `{"message": "1 hoặc nhiều shot không thuộc project này."}` — đây là lỗi dữ liệu gửi lên sai, không phải thiếu quyền truy cập chức năng.
- **`DELETE shots/{shot}`** — 204 No Content, không body.
- **`PUT shots/{shot}/result`** — trả lại `{"ai_result": "..."}` (đủ dùng, không cần trả cả shot).
- **Concurrency (2 tab cùng sửa 1 shot)** — chấp nhận **last-write-wins** ở v1 (không optimistic locking/`updated_at` check) — đúng mức độ rủi ro chấp nhận được cho 1 công cụ nội bộ ít người dùng đồng thời trên cùng 1 project; ghi chú rõ đây là quyết định CÓ CHỦ ĐÍCH, không phải bỏ sót.

### 6.2 UX ghi/lưu field (chống spam request)

> **v1.13 — ĐỔI CƠ CHẾ (phản hồi người dùng "hạn chế lưu ajax, mỗi shot bấm lưu khi làm xong")**: bản
> gốc dưới đây (debounce 600-800ms, tự PUT sau mỗi lần ngừng gõ) đã bị THAY THẾ bằng nút **"Lưu"** bấm
> tay/shot (`.aivs-save-shot`) — gõ bao nhiêu field cũng chỉ 1 request `PUT` khi bấm, thay vì 1 request
> mỗi 600-800ms/field. Lý do đổi: giảm số lượng request AJAX (đúng yêu cầu), đổi lại người dùng phải
> tự nhớ bấm Lưu — bù bằng 2 cơ chế an toàn: (1) trạng thái "Chưa lưu — bấm 'Lưu'" hiện ngay khi gõ
> (không cần chờ mạng, đọc/ghi thẳng `card.dataset.dirty`), (2) `beforeunload` cảnh báo nếu còn shot
> `data-dirty="true"` lúc rời trang. Timeline trực quan (§8) vẫn cập nhật LIVE theo từng keystroke —
> khác PUT, việc này đọc thẳng DOM, không gọi mạng, nên không mâu thuẫn với mục tiêu giảm AJAX.
>
> Nội dung cũ (không còn đúng, giữ lại để biết đã đổi từ đâu): Mỗi ô input/textarea trong Shot card
> debounce 600-800ms sau khi ngừng gõ rồi mới gọi `PUT shots/{shot}` — KHÔNG gọi API mỗi keystroke; có
> trạng thái hiển thị nhỏ cạnh mỗi field đang debounce/đang lưu ("Đang lưu..."/"Đã lưu").

- `<textarea>` hiển thị `compiled_prompt`: **luôn `readonly`** (chỉ đọc, sinh tự động) — không cho sửa tay trực tiếp, tránh lệch với field nguồn khi user quên bấm Lưu lại. (`image_prompt`/`motion_prompt` KHÔNG readonly từ v1.12 — nhập tay tự do, xem §3.1.)
- Nút "Lưu"/shot gửi `PUT shots/{shot}` với **toàn bộ** giá trị hiện tại của mọi `.aivs-field` trong card (`collectShotPayload()`), không chỉ field vừa đổi — khớp hành vi PUT merge đã có (§6.1), chỉ khác thời điểm gọi (bấm tay thay vì debounce).

## 7. Validation

- `StoreProjectRequest`/`UpdateProjectRequest`: `name` required|string|max:200; `description`/`objective`/`target_audience`/`core_message`/`default_subject`/`reference_image_url`(max:2048)/`default_style`/`default_constraints` nullable|string; `aspect_ratio` nullable|string|in:16:9,9:16,1:1,4:5 (v1.2); `resolution` nullable|string|in:720p,1080p,2K,4K (v1.5); `video_type` nullable|string|in:explainer,testimonial,product_demo,storytelling,spokesperson,offer_promo,other (v1.6; 2 giá trị cuối trước `other` thêm ở v1.14); `video_formula` nullable|string|in:psa,bab,hook_value_cta (v1.16); `reference_context_prompt` nullable|string|max:300 (v1.11 — cố ý giới hạn ngắn, đây là ghi chú không phải kịch bản).
- `StoreShotRequest`/`UpdateShotRequest`: tất cả field director + `label` + `model_tool`(max:150, v1.2) + `qc_notes`(v1.2) đều `nullable|string`; `mood` nullable|string (v1.3); `duration_seconds` nullable|integer|min:1|max:36000 (v1.3); `audio_direction`/`reference_assets` nullable|string (v1.4); `cta_text` nullable|string|max:200 (v1.6); `timeline_breakdown` nullable|string (v1.10); `image_prompt`/`motion_prompt` nullable|string (v1.12 — field nhập tay, không giới hạn độ dài vì là nội dung prompt thật) — không bắt buộc field nào (cho phép điền dần).
- `SaveShotAiResultRequest`: `ai_result` nullable|string.
- `ReorderShotsRequest`: `shot_ids` required|array, `shot_ids.*` required|integer — ownership thật (thuộc đúng project) kiểm tra ở Action (§3.4), KHÔNG kiểm tra được ở FormRequest thuần (cần query DB theo `{project}` route param).

## 8. Views — pattern UI

Trang `show.blade.php` (`@extends('layouts.backend')`):
- **Creative Brief card** (v1.2, chỉ hiện nếu có ít nhất 1 field điền) — hiển thị `objective`/`target_audience`/`video_type`/`video_formula`(v1.16)/`core_message`(v1.6)/`aspect_ratio`/`resolution`(v1.5) dạng chỉ đọc, sửa qua "Sửa project".
- **Khối "🎯 Gợi ý mô tả theo Loại video & Công thức kịch bản đã chọn"** (v1.17, `<details>`, mở sẵn nếu project chưa có shot) — đặt đầu section "3. Kịch bản & Timeline", TRƯỚC khối "Khung thời gian mẫu". Chỉ hiện nếu project có `video_type` HOẶC `video_formula`; nội dung đọc lại `CompileProjectDirectorPromptAction::CONTENT_TIPS_BY_VIDEO_TYPE`/`FORMULA_BEATS` (§3.5) — câu tip theo loại nội dung + bảng 3 nhịp (tên/tỉ lệ thời lượng/cách viết mô tả) theo công thức kịch bản.
- Header: tên project + field default (anchoring) hiển thị dạng card, có nút "Sửa" mở modal/inline edit. **Bắt buộc có 1 dòng ghi chú nhỏ, rõ ràng ngay dưới tiêu đề card**: *"Áp dụng khi tạo Shot MỚI — sửa ở đây KHÔNG tự động cập nhật các Shot đã tạo trước đó."* — đây là điểm dễ hiểu nhầm nhất của cơ chế anchoring (§0), phải hiển thị luôn trên UI, không chỉ nằm trong spec. Card này cũng hiện `reference_context_prompt` (v1.11, nếu có điền — hiển thị dạng text thường). v1.13 (phản hồi người dùng) — KHÔNG còn hiện link `reference_image_url` ở UI (input lẫn display đã bỏ cả 2, chỉ giữ lại cột/dữ liệu backend).
- **Callout "Mẹo viết prompt"** (v1.2-v1.6, tĩnh, ngay trên danh sách Shot) — tóm tắt Key Prompting Principles của Hedra (mỗi shot 1 cảnh/khoảnh khắc, thay tính từ chung chung bằng mô tả cụ thể, luôn điền Camera, gọi tên phong cách rõ ràng) + deepreel.com (50-150 từ/prompt, ưu tiên 20-30 từ đầu, tránh yêu cầu đối lập, negative prompt cụ thể, kỳ vọng lặp 3-4 lần) + byteplus.com (không dồn nhiều hành động vào 1 shot ngắn, đừng bỏ qua Audio Direction) + pyxeljam.com/LinkedIn (v1.6 — phân biệt diễn đạt khẳng định ở Subject/Action/Style với loại trừ cụ thể dồn vào Constraints; điều chỉnh giọng văn theo nền tảng/đối tượng đã khai ở Creative Brief) + sentx.ai (v1.8 — "single hero focus" giới hạn số chủ thể trong khung hình, khác nguyên tắc "1 hành động/shot" đã có; "pacing" — nhịp lấy cảnh tách khỏi Duration; trỏ tới khối troubleshooting mới trong tài liệu xuất). Placeholder field Camera/Style (§8) cũng bổ sung vốn từ vựng cỡ cảnh/góc máy/chuyển động máy và cách gọi tên nguồn sáng + thời điểm trong ngày (v1.8) — 2 field callout nhắc là quan trọng nhưng trước đó không có ví dụ nào. v1.9 (veed.io) — câu chung "điều chỉnh giọng văn theo nền tảng" giờ trỏ rõ tới 2 dòng "Gợi ý theo nền tảng"/"Gợi ý theo loại video" tự hiện trong Creative Brief; placeholder Environment bổ sung spatial+temporal descriptors (field cuối cùng trong nhóm 5 field cốt lõi còn thiếu ví dụ), Style bổ sung ví dụ phong cách hoạt hình/nghệ thuật.
- Danh sách Shot: mỗi Shot là 1 card có:
  - Badge số thứ tự "Cảnh N" (`.aivs-shot-number`, tính lại phía client sau add/xoá/sắp xếp — xem `renumberShots()`) + **Input "Thời lượng (giây)"** (v1.3, `duration_seconds`, số) — đặt cạnh Label ở đầu card.
  - **Nút "Lưu"/shot** (`.aivs-save-shot`, v1.13) — xem §6.2 (đã đổi từ debounce tự động sang bấm tay).
  - 11 input (textarea nhỏ, nhóm theo 3 khối UI/UX v2: "Nội dung cảnh", "Hình ảnh & Âm thanh", "Timeline & Lời thoại") cho Subject/Action/Environment/Camera/Style/Mood/Timeline(v1.10)/Audio/Lời thoại/CTA/Constraints — KHÔNG tự gọi mạng khi gõ (v1.13), chỉ đánh dấu "chưa lưu"; bấm nút "Lưu" mới gọi `PUT shots/{shot}` → nhận về **toàn bộ shot resource mới** (§6.1), cập nhật lại `<textarea readonly>` hiển thị `compiled_prompt`. Field `mood` (v1.3), `audio_direction` (v1.4), `cta_text` (v1.6), `timeline_breakdown` (v1.10) và `constraints` có placeholder ví dụ cụ thể (Mood: liệt kê vài tông cảm xúc mẫu; Audio: ví dụ âm thanh môi trường + nhạc nền, phân biệt rõ với lời thoại; CTA: ví dụ text nút/đếm ngược; Timeline: ví dụ mốc thời gian kiểu Synthesia "0-5s/5-15s/kết"; Constraints: minh hoạ kỹ thuật negative prompt — loại trừ rõ ràng thay vì mơ hồ).
  - `<textarea readonly>` (bắt buộc `readonly`, §6.2) hiển thị `compiled_prompt` + nút "Copy" (tái dùng pattern JS đã có ở `content-outlines.js`/breaking-news picker) + **bộ đếm từ** (v1.3, `.aivs-word-count`, JS tính client-side) cảnh báo nhẹ (đổi màu) nếu ngoài khoảng 50-150 từ — không chặn lưu.
  - **Khối `<details>` "Prompt 2 bước — Ảnh + Motion (Image-to-Video)"** (v1.10, đóng mặc định — dùng `<details class="collapse collapse-arrow">` của DaisyUI, KHÔNG thêm JS dependency mới) — 2 `<textarea>` NHẬP TAY (v1.12, `.aivs-field`, KHÔNG `readonly` — xem §3.1) hiển thị/lưu `image_prompt`/`motion_prompt`, mỗi ô có nút Copy riêng cùng pattern `.aivs-copy-compiled`.
  - **v1.13 (phản hồi người dùng) — ĐÃ BỎ khỏi UI**: input "Model/Tool đã dùng" (`model_tool`), "Tài liệu tham chiếu bổ sung" (`reference_assets`), textarea "Ghi chú đánh giá QC" (`qc_notes`) — từng gộp trong khối `<details>` "Nâng cao"; và textarea + nút "Lưu kết quả" cho `ai_result` (`PUT shots/{shot}/result`). Cả 4 field/route/Action backend liên quan (`SaveShotAiResultAction`, `shots.save-result`) KHÔNG bị xoá — chỉ không còn UI, dữ liệu cũ (nếu có) vẫn hiện trong tài liệu xuất (§3.5).
  - Nút xoá shot (**có `confirm()`/modal xác nhận** — xoá shot không cascade gì thêm nhưng vẫn là hành động mất dữ liệu không hoàn tác được); sắp xếp lại bằng 2 nút "↑"/"↓" đổi `sort_order` với shot liền kề (đã xác nhận: `PostCategory::reorder()` — tiền lệ reorder duy nhất tìm thấy trong repo — nhận thẳng mảng `order[]` từ client, KHÔNG dùng thư viện JS drag-drop nào (không có `Sortable`/`sortablejs` trong `resources/js/`) — v1 dùng nút mũi tên cho đơn giản, tránh thêm dependency JS mới; có thể nâng cấp lên drag-drop ở bản sau nếu cần).
  - Nút "+ Thêm cảnh" + (v1.13) nút "⚡ Chèn 5 cảnh mẫu" trong empty-state khi project 0 shot.
- Cuối trang: nút "Xuất Director Prompt Template" → gọi `GET {project}/export`, trả về file `.md` tải xuống (nội dung từ `CompileProjectDirectorPromptAction`, v1.2 gồm cả khối Creative Brief + checklist đánh giá — xem §3.5), + nút "Copy toàn bộ" hiển thị trong `<textarea readonly>` lớn.

`_form.blade.php` (create/edit project, v1.2-v1.6):
- Khối "Creative Brief" mới (trước khối Anchoring) — `objective`/`target_audience` (textarea) + `video_type` (`<select>` 7 lựa chọn: explainer/testimonial/product_demo/storytelling/spokesperson/offer_promo/other, v1.6; 2 lựa chọn giữa thêm ở v1.14) + `video_formula` (`<select>` 3 lựa chọn: psa/bab/hook_value_cta, v1.16 — không có "Khác", ngay sau Loại video) + `core_message` (textarea, v1.6) + `aspect_ratio` (`<select>` 4 lựa chọn cố định: 16:9/9:16/1:1/4:5) + `resolution` (`<select>` 4 lựa chọn: 720p/1080p/2K/4K, v1.5) — tất cả không bắt buộc.
- Khối Anchoring thêm 1 textarea `reference_context_prompt` (v1.11, `maxlength="300"` — mô tả ngắn tự gõ, không tự sinh gì, xem §3.7). v1.13 (phản hồi người dùng) — input `reference_image_url` (URL ảnh tham chiếu) ĐÃ BỎ khỏi form, chỉ giữ lại cột/dữ liệu backend.
- Placeholder các field mô tả (`default_subject`/`default_style`/`default_constraints`) viết lại theo Key Prompting Principles — ví dụ cụ thể thay vì mô tả chung chung, nhắc kèm bảng màu/tone thương hiệu ở `default_style`, kỹ thuật negative prompt ở `default_constraints`.

**Xoá Project (từ trang `index`/`edit`)** — bắt buộc modal/`confirm()` xác nhận nêu rõ hậu quả: *"Xoá project sẽ xoá VĨNH VIỄN toàn bộ N shot bên trong (cascade). Không thể hoàn tác."* — đúng vì `cascadeOnDelete()` (§2.2) xoá sạch shot con ngay lập tức, không soft-delete để khôi phục.

Trang `index.blade.php`:
- Bảng danh sách project với **filter theo `status`** (draft/active/archived, dropdown) + **ô tìm theo `name`** — tận dụng cột `status`/`name` đã có sẵn trong schema (§2.1) nhưng chưa được dùng ở bản v1.0 ban đầu của spec này. Dùng `ListProjectsForAdminQuery`/`Handler` (đã liệt kê ở §4 cây thư mục) nhận `search`/`status` làm tham số lọc.

## 9. Sidebar & `PermissionEnum`

Đã xác nhận: `app/Enums/PermissionEnum.php` có 1 case riêng cho MỖI permission module content (`CONTENT_OUTLINES_USE`, `CORE_IDEA_EXTRACTOR_USE`, `VIDEO_IDEA_EXTRACTOR_USE`, `PROMPT_FRAMEWORK_STUDIO_USE`...) dù các permission này được seed qua `*PermissionSeeder.php` riêng của module (KHÔNG qua `config/permissions.php` Lớp B) — `PermissionEnum` chỉ đóng vai trò hằng số chuỗi dùng ở Blade/`$this->authorize()`, không phải nguồn seed. Theo đúng convention này, cần thêm 1 case mới:

```php
case AI_VIDEO_STUDIO_TEMPLATE_USE = 'ai_video_studio_template.use';
```

Thêm 1 mục top-level (không nằm trong nhóm "Bài viết") trong `resources/views/layouts/partials/sidebar.blade.php`:

```blade
@can(\App\Enums\PermissionEnum::AI_VIDEO_STUDIO_TEMPLATE_USE->value)
<div class="nav-group">
    <a href="{{ route('backend.aivideostudiotemplate.index') }}"
       class="nav-link {{ request()->routeIs('backend.aivideostudiotemplate.*') ? 'active' : '' }}">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">...</svg>
        <span class="nav-label">AI Video Studio</span>
    </a>
</div>
@endcan
```

---

## 10. Ngoài phạm vi (v1)

- Gọi trực tiếp API Midjourney/Kling/Runway/Sora để tự động generate ảnh/video trong app — v1 chỉ tổ chức prompt + lưu kết quả dán tay.
- Upload file ảnh/video thật (Media Library) — v1 chỉ lưu link/text.
- Animatic/preview ghép các ảnh tĩnh theo timeline — thuộc công đoạn edit ngoài app.
- Versioning lịch sử prompt (mỗi lần sửa ghi đè, không giữ bản cũ).
- Duplicate/clone Project hoặc Shot — có thể thêm sau nếu cần tái dùng cấu trúc cho sản phẩm mới. Trùng khái niệm "swipe file" (lưu prompt tốt để remix) mà sentx.ai (v1.8) liệt kê như 1 optimization habit — không mở lại quyết định, chỉ ghi nhận thêm 1 nguồn ủng hộ tính năng này ở bản sau.
- Drag-drop reorder Shot — v1 dùng nút "↑"/"↓" (§8); nâng cấp lên `Sortable`/`sortablejs` là việc riêng của bản sau, KHÔNG thêm dependency JS mới ở v1.
- "Apply defaults to empty fields" hàng loạt (nút áp lại `default_subject`/`default_style`/`default_constraints` vào MỌI shot đang trống field tương ứng, kể cả shot đã tạo từ trước) — v1 chỉ prefill lúc tạo mới (§0); tính năng "áp lại hàng loạt" là hành động ghi đè có chủ đích trên nhiều row cùng lúc, cần UI xác nhận riêng, để dành bản sau.
- Optimistic locking / cảnh báo xung đột khi 2 tab cùng sửa 1 shot — chấp nhận last-write-wins ở v1 (§6.1).
- Disclosure/compliance labeling (EU AI Act...) — không thuộc phạm vi công cụ nội bộ.
- Đa ngôn ngữ cho nội dung Project/Shot, và cho chính LABEL các field prompt (`CHỦ THỂ (Subject)`...) — hard-code tiếng Việt trong `BuildShotPromptAction` (§3.1), chấp nhận được cho v1 nhưng là điểm cần sửa nếu sau này module phục vụ đa ngôn ngữ.
- **Theo dõi hiệu suất thực tế sau khi đăng** (views/engagement/conversion — thực hành #10 pyxeljam.com "Test and Improve") — v1.5 chỉ dừng ở đánh giá CHẤT LƯỢNG video tạo ra (`qc_notes`, checklist §3.5), KHÔNG theo dõi hiệu suất phân phối/kinh doanh sau khi video được đăng lên nền tảng — thuộc phạm vi công cụ phân tích riêng, ngoài phạm vi "Director Prompt Template" của module này. v1.14 (imagine.art liệt kê Hook rate/Hold rate/CTR/CPC/CPA/add-to-cart) — cùng quyết định, không mở lại.
- **Gọi API TTS thật để tạo giọng đọc** (ElevenLabs/PlayHT/OpenAI TTS/Kokoro — mindstudio.ai Agent 2 "Voiceover") — v1.14 chỉ THÊM GHI CHÚ hướng dẫn quy trình vào callout "Mẹo viết prompt" (§8), không tích hợp API — trùng quyết định cốt lõi "không gọi AI Provider" (§0).
- **Retry logic/exponential backoff, tích hợp content calendar (Notion/Airtable/Google Sheets), Brand Style Library dùng lại xuyên PROJECT** (mindstudio.ai "Optimizing at Scale") — đều là hạ tầng tự động hoá/pipeline thật, ngoài phạm vi 1 tool tổ chức prompt thủ công của module (§0). "Brand Style Library" gần nhất với `default_style`/`default_constraints` đã có (anchoring CẤP PROJECT, §0) — chưa có nhu cầu dùng lại xuyên nhiều Project, để dành bản sau nếu phát sinh.

## 11. Testing (bắt buộc trước khi coi là hoàn thành)

- Tạo Project với đủ/thiếu field default → tạo Shot mới → xác nhận `subject`/`style`/`constraints` được prefill đúng từ default khi field đó để trống, KHÔNG prefill nếu người dùng đã nhập riêng.
- `BuildShotPromptAction`: field nào rỗng thì KHÔNG xuất hiện dòng tương ứng trong `compiled_prompt`; đủ 7 field thì thứ tự đúng như spec.
- Sửa 1 field của Shot đã có `compiled_prompt` → `compiled_prompt` được build lại đúng nội dung mới (ghi đè, không giữ bản cũ).
- `ReorderShotsAction` → `sort_order` cập nhật đúng theo mảng ID truyền vào.
- **`ReorderShotsAction` — ownership**: truyền vào 1 ID shot thuộc project KHÁC → ném `AuthorizationException`, `sort_order` của MỌI shot (kể cả những ID hợp lệ trong cùng request) **không bị thay đổi** (toàn bộ thao tác phải all-or-nothing, không update một phần rồi mới phát hiện ID sai).
- `CompileProjectDirectorPromptAction` → xuất đúng thứ tự `sort_order`, có heading từng shot, nhúng `ai_result` nếu có, bỏ qua đẹp nếu rỗng.
- **`CompileProjectDirectorPromptAction` — edge case dữ liệu hỗn hợp**: project có 0 shot → xuất được tài liệu không lỗi, **luôn kèm khối checklist đánh giá output tĩnh** (v1.2 — không còn "chỉ có tiêu đề" như v1.1, vì checklist không phụ thuộc dữ liệu); project có shot chưa điền field nào (`compiled_prompt` null) xen giữa các shot đã đầy đủ → vẫn xuất đủ N shot theo đúng thứ tự, shot rỗng hiện placeholder `_(chưa có prompt...)_` thay vì bị bỏ qua hoặc gây lỗi.
- **v1.2 — Creative Brief trong tài liệu xuất**: project có điền `objective`/`target_audience`/`aspect_ratio`/`reference_image_url` → khối `## Creative Brief` xuất hiện đầu tài liệu với đúng giá trị; project KHÔNG điền field nào trong nhóm này → khối `## Creative Brief` KHÔNG xuất hiện (chỉ còn checklist tĩnh).
- **v1.2 — `model_tool`/`qc_notes` trong tài liệu xuất**: shot có điền → dòng "Model/Tool đã dùng"/"Ghi chú đánh giá (QC)" xuất hiện đúng nội dung; KHÔNG điền → không xuất hiện dòng tương ứng.
- **v1.2 — `model_tool`/`qc_notes` KHÔNG lọt vào `compiled_prompt`**: `BuildShotPromptAction` chạy với shot có điền 2 field này → `compiled_prompt` KHÔNG chứa nội dung của chúng (metadata nội bộ, không gửi cho AI generator ngoài).
- Xoá Project → cascade xoá hết Shot con (`cascadeOnDelete`).
- Người không có permission `ai_video_studio_template.use` → 403 ở mọi route (web + API).
- `super-admin` luôn truy cập được dù không seed permission trực tiếp (qua `Gate::before()`).
- **API validation lỗi (422)** — `POST/PUT shots` thiếu/sai kiểu dữ liệu 1 field bất kỳ → response đúng shape `{"message", "errors"}` mặc định Laravel (§6.1), không phải shape tự chế.
- **`SaveShotAiResultAction`** — dán `ai_result` rất dài (vd nhiều link + ghi chú nhiều dòng) → lưu/đọc lại đúng nguyên vẹn (không bị cắt bởi giới hạn cột — đã dùng `longText`).
- **API resource shape (v1.2/v1.3/v1.4)** — `POST/PUT shots` trả về JSON có đủ `model_tool`/`qc_notes` (v1.2), `mood`/`duration_seconds` (v1.3), `audio_direction`/`reference_assets` (v1.4, §6.1).
- **v1.3 — Mood/Duration trong `compiled_prompt`**: shot có điền `mood`/`duration_seconds` → 2 dòng tương ứng xuất hiện đúng thứ tự (sau Style, trước Lời thoại/Constraints); KHÔNG điền → không xuất hiện dòng tương ứng (khác `model_tool`/`qc_notes` — 2 field này KHÔNG BAO GIỜ xuất hiện trong `compiled_prompt` dù có điền).
- **v1.3 — Tổng thời lượng trong tài liệu xuất**: project có N shot, M shot (M≤N) có điền `duration_seconds` → dòng "Tổng thời lượng ước tính" hiện đúng tổng và tỉ lệ `M/N`; 0 shot có điền → dòng này KHÔNG xuất hiện.
- **v1.3 — Checklist trước khi generate**: luôn xuất hiện (kể cả project 0 shot), đứng TRƯỚC khối Checklist đánh giá output trong tài liệu xuất.
- **v1.4 — Audio Direction trong `compiled_prompt`**: shot có điền `audio_direction` → dòng "ÂM THANH (Audio/Soundscape)" xuất hiện, đặt sau Duration và trước Lời thoại; KHÔNG điền → không xuất hiện; lời thoại (`script_line`) vẫn xuất hiện riêng, không bị trộn lẫn với Audio.
- **v1.4 — `reference_assets` KHÔNG lọt vào `compiled_prompt`**: cùng nguyên tắc `model_tool`/`qc_notes` — chỉ xuất hiện trong JSON resource (§6.1) và tài liệu xuất (§3.5, dòng "Tài liệu tham chiếu bổ sung"), không bao giờ trong `compiled_prompt`.
- **v1.5 — Độ phân giải trong Creative Brief**: project có điền `resolution` → dòng "Độ phân giải" xuất hiện trong khối `## Creative Brief`; KHÔNG điền → không xuất hiện.
- **v1.6 — `video_type`/`core_message` trong Creative Brief**: project có điền → dòng "Loại video"/"Thông điệp cốt lõi" xuất hiện đúng nội dung; KHÔNG điền → không xuất hiện dòng tương ứng.
- **v1.6 — `cta_text` trong `compiled_prompt`**: shot có điền → dòng "CALL-TO-ACTION (CTA)" xuất hiện, đặt SAU Lời thoại, TRƯỚC Constraints; KHÔNG điền → không xuất hiện.
- **v1.6 — QC checklist marketing**: 3 mục mới (thời lượng khớp tỷ lệ nền tảng, nhất quán thương hiệu, âm thanh rõ) luôn xuất hiện trong khối `## Checklist đánh giá output`, kể cả project 0 shot.
- **v1.8 — Khối `## Xử lý sự cố theo triệu chứng`**: luôn xuất hiện trong tài liệu xuất (kể cả project 0 shot, không phụ thuộc dữ liệu), đứng SAU khối `## Checklist đánh giá output`.
- **v1.8 — `PRE_GENERATION_CHECKLIST` "single hero focus"**: mục mới xuất hiện trong khối `## Checklist trước khi generate`, khác nội dung mục "1 hành động/shot" đã có ở callout `show.blade.php`.
- **v1.9 — Gợi ý theo nền tảng**: project có `aspect_ratio` = `9:16` hoặc `16:9` → dòng "Gợi ý theo nền tảng" xuất hiện ngay sau dòng "Tỷ lệ khung hình"; `aspect_ratio` = `1:1`/`4:5` → dòng "Tỷ lệ khung hình" vẫn xuất hiện nhưng KHÔNG có dòng gợi ý (không có tip khớp).
- **v1.9 — Gợi ý theo loại video**: project có `video_type` khác `other` (VD `testimonial`) → dòng "Gợi ý theo loại video" xuất hiện ngay sau dòng "Loại video"; `video_type` = `other` → dòng "Loại video" vẫn xuất hiện nhưng KHÔNG có dòng gợi ý.
- **v1.10 — `timeline_breakdown` trong `compiled_prompt`**: shot có điền → dòng "TIMELINE NỘI DUNG (Content Timeline)" xuất hiện, đặt SAU Duration, TRƯỚC Audio; KHÔNG điền → không xuất hiện.
- **v1.10 — `buildImagePrompt()`/`buildMotionPrompt()` — ĐÃ GỠ BỎ (v1.12)**: gọi 2 method này (namespace cũ) → `method not found`; `BuildShotPromptAction` chỉ còn `handle()`.
- **v1.12 — `image_prompt`/`motion_prompt` là field nhập tay**: `PUT shots/{shot} {"image_prompt": "x"}` → lưu đúng `"x"` (không qua build/biến đổi gì); vắng mặt trong request → giữ nguyên giá trị đang lưu (cùng `valueOrExisting()` như `qc_notes`); KHÔNG xuất hiện trong `compiled_prompt` dù có điền.
- **v1.12 — KHÔNG rebuild khi đổi bối cảnh Project**: đổi `aspect_ratio`/`resolution`/`video_type`/`target_audience`/`core_message` → `image_prompt`/`motion_prompt` của mọi shot giữ NGUYÊN (chỉ `compiled_prompt` rebuild, §3.6) — khác hành vi v1.10 đã gỡ.
- **v1.10 — API resource shape**: `POST/PUT shots` trả về JSON có đủ `timeline_breakdown`/`image_prompt`/`motion_prompt` (§6.1).
- **v1.11 — `reference_context_prompt`**: field text tự do, KHÔNG xuất hiện trong `compiled_prompt`/`image_prompt`/`motion_prompt` của bất kỳ shot nào (cùng nhóm `objective` — chỉ dành cho người đọc); project có điền → hiển thị dạng text (không phải link) ở card Anchoring trên `show.blade.php`; không điền → dòng đó không xuất hiện.
- **v1.11 — `BuildReferenceCompositionPromptAction`/`kol_reference_image_url`/`product_reference_image_url` đã bị xoá**: gọi `BuildReferenceCompositionPromptAction` (namespace cũ) → `class not found`; `ShotApiController`/`ProjectController` không còn phụ thuộc class này (§3.7).
- **v1.14 — `video_type` mới**: `Rule::in()` chấp nhận `spokesperson`/`offer_promo` (không còn báo lỗi 422); `videoTypeLabel()` trả đúng nhãn tiếng Việt cho 2 giá trị này; `CompileProjectDirectorPromptAction` in đúng dòng "Gợi ý theo loại video" tương ứng khi project chọn 1 trong 2.
- **v1.14 — `video_type` label trong tài liệu xuất (sửa lỗi)**: project có `video_type` → dòng "Loại video" trong `## Creative Brief` in **nhãn** (VD "Product demo (trình diễn sản phẩm)"), KHÔNG in slug thô (VD "product_demo") — trước v1.14 code in nhầm slug thô dù §11 (v1.7) đã ghi nhận hành vi đúng từ trước.
- **v1.14 — `QC_CHECKLIST` phụ đề**: mục "Phụ đề (nếu có) đủ lớn, tương phản cao..." luôn xuất hiện trong khối `## Checklist đánh giá output`, kể cả project 0 shot (cùng hành vi các mục QC tĩnh khác).
- **v1.15 — `buildEdlBlock()` ẩn khi không có dữ liệu**: project 0 shot → khối `## EDL` KHÔNG xuất hiện; project có shot nhưng KHÔNG shot nào điền `duration_seconds` lẫn `script_line` → khối `## EDL` KHÔNG xuất hiện; chỉ cần ĐÚNG 1 shot có 1 trong 2 field đó → khối xuất hiện với ĐỦ mọi shot (kể cả shot rỗng hoàn toàn, hiện placeholder).
- **v1.15 — Thời gian cộng dồn đúng thứ tự `sort_order`**: 3 shot lần lượt 3s/không điền/5s → cột Thời gian lần lượt `0–3s`, `3s+ (?)`, `3–8s` (shot không điền KHÔNG cộng dồn tiếp, shot sau tính tiếp từ mốc cursor cũ).
- **v1.15 — Cột "Mô tả hình ảnh"**: shot có `label` → ưu tiên hiện `label`; shot KHÔNG có `label` nhưng có `subject`/`action` → hiện `Str::limit(subject+action, 60)`; shot rỗng cả 3 → hiện `_(chưa có mô tả)_`.
- **v1.15 — Escape ký tự `|` trong bảng Markdown**: `script_line`/`label`/`subject` chứa ký tự `|` → thay bằng `/` trong ô tương ứng, không làm vỡ cấu trúc bảng.
- **v1.16 — `video_formula` trong Creative Brief**: project có điền → dòng "Công thức kịch bản" (nhãn qua `videoFormulaLabel()`, không phải slug thô) và dòng "Cấu trúc gợi ý" (từ `FORMULA_TIPS_BY_VIDEO_FORMULA`) xuất hiện ngay sau khối Loại video; KHÔNG điền → cả 2 dòng không xuất hiện.
- **v1.16 — `video_formula` trong `compiled_prompt`**: project có điền → dòng "Công thức kịch bản" xuất hiện trong khối `BỐI CẢNH CHIẾN DỊCH` của mọi Shot, ngay sau dòng "Loại video"; KHÔNG điền → không xuất hiện.
- **v1.16 — Rebuild khi đổi `video_formula`**: đổi `video_formula` của Project (kể cả khi `aspect_ratio`/`resolution`/`video_type`/`target_audience`/`core_message` giữ nguyên) → `compiled_prompt` của MỌI shot được build lại (`video_formula` nằm trong `PROMPT_CONTEXT_FIELDS` của `UpdateProjectAction`, §3.6).
- **v1.16 — `Rule::in()` cho `video_formula`**: giá trị ngoài `psa`/`bab`/`hook_value_cta` → 422; để trống → hợp lệ (nullable).
- **v1.17 — khối gợi ý trên trang `show`**: project có cả `video_type` và `video_formula` → khối "🎯 Gợi ý mô tả..." hiện cả câu tip loại nội dung LẪN bảng 3 nhịp; project chỉ có `video_type` (không có `video_formula`) → chỉ hiện câu tip + dòng gợi ý "chọn Công thức kịch bản ở Sửa project", KHÔNG hiện bảng nhịp; project không có field nào trong 2 field này → khối KHÔNG xuất hiện (khối "Khung thời gian mẫu" cũ vẫn hiện bình thường, không phụ thuộc 2 field này).
- **v1.17 — không có deprecation warning khi cả 2 field đều trống**: `video_type`/`video_formula` đều `null` → không truy cập `array[null]` trực tiếp (đã guard bằng `filled()` trước khi lấy index).
- **v1.18 — `demo_5part` hợp lệ như 3 khoá cũ**: `Rule::in()` chấp nhận `demo_5part` (không còn báo lỗi 422); `videoFormulaLabel()` trả `"Demo Script 5 phần (90-120s)"`; `<select>` ở `_form.blade.php` có đủ 4 lựa chọn (tự động qua `@foreach`, không cần sửa view).
- **v1.18 — `FORMULA_BEATS['demo_5part']` có 5 dòng**: khối "🎯 Gợi ý mô tả..." ở `show.blade.php` (§8) hiện bảng 5 nhịp (Hook/Problem Deep-dive/Solution Introduction/Key Features in Action/CTA & Outcome) khi project chọn `video_formula = demo_5part` — khác 3 khoá cũ chỉ có 3 nhịp; `compiled_prompt` của mọi Shot có dòng "Công thức kịch bản: Demo Script 5 phần (90-120s)" trong khối Bối cảnh chiến dịch.
- **v1.18 — tip `product_demo` cập nhật**: project có `video_type = product_demo` → dòng "Gợi ý theo loại video" trong Creative Brief (tài liệu xuất) và khối "🎯 Gợi ý mô tả..." (trang `show`) đều chứa cụm "giới hạn 2-5 tính năng chính".

**v1.7 — rà soát logic build prompt (xem changelog đầu tài liệu):**

- **Bối cảnh Project vào prompt**: project có `aspect_ratio`/`resolution` → dòng `ĐỊNH DẠNG (Format)` xuất hiện TRƯỚC `CHỦ THỂ`; có `video_type`/`target_audience`/`core_message` → khối `BỐI CẢNH CHIẾN DỊCH` xuất hiện SAU nội dung shot, kèm câu "KHÔNG cần thể hiện toàn bộ trong shot này"; `video_type` in **nhãn**, không phải slug.
- **Field CỐ Ý không vào prompt**: `objective`, `reference_image_url`, `model_tool`, `reference_assets`, `qc_notes`, `ai_result`, `label` — điền đủ cả 7 rồi build → `compiled_prompt` KHÔNG chứa giá trị nào trong số đó.
- **Shot rỗng**: tạo shot không điền field nào (trên project KHÔNG có anchoring default) → `compiled_prompt` lưu `null`, tài liệu xuất hiện placeholder. Lưu ý: project CÓ `default_subject` thì shot mới được prefill nên KHÔNG còn rỗng — đúng thiết kế anchoring (§0), không phải lỗi.
- **`label` không tính là nội dung**: shot chỉ có `label` → vẫn `null`.
- **Rebuild khi đổi bối cảnh Project**: đổi `aspect_ratio` → `compiled_prompt` của MỌI shot đổi theo, trong khi field nội dung của Shot và anchoring cũ **không bị đụng** (§3.6). Đổi field ngoài prompt (vd `objective`) → prompt giữ nguyên byte-for-byte.
- **Giá trị nhiều dòng**: `subject` có `\n` (và `\r\n`) → dòng nối được thụt lề 4 space, ranh giới `NHÃN: giá trị` vẫn đọc được, không còn ký tự `\r`.
- **PUT merge (§6.1)**: `PUT {"subject":"x"}` chỉ đổi `subject`, 14 field còn lại nguyên vẹn; `PUT {"camera":""}` vẫn xoá `camera`; payload đầy đủ hành xử y hệt trước v1.7.

> **Hạn chế môi trường (2026-08-09):** toàn bộ Feature test của **cả repo** hiện không chạy được —
> `Modules/Post/database/migrations/2026_07_28_000002_add_geo_checklist_state_...` gọi
> `->after('direct_answer')` nhưng cột `direct_answer` chỉ được tạo ở
> `database/migrations/extensions/2026_08_08_060039_000209_...` (ngày muộn hơn → chạy sau), nên
> `migrate:fresh` hỏng và mọi test dùng `RefreshDatabase` fail ở `setUp`. Lỗi có sẵn, KHÔNG do module
> này. Vì vậy các hành vi thuần logic của v1.7 (build prompt, merge PUT) được phủ bằng **Unit test
> không chạm DB** (`tests/Unit/`, 21 test) để vẫn kiểm chứng được ngay; phần cần DB
> (rebuild prompt khi sửa Project) nằm ở `tests/Feature/` và sẽ chạy khi lỗi migration trên được sửa.
- **API resource shape (v1.5/v1.6)** — `POST/PUT shots` trả về JSON có đủ `cta_text` (v1.6, §6.1); `resolution`/`video_type`/`core_message` không thuộc resource Shot (chỉ ở Project, hiển thị qua trang `show`/`edit` project, không qua API JSON).

## 12. Kế hoạch triển khai

1. `php artisan module:make AIVideoStudioTemplate`
2. Viết 2 migration (§2.1/§2.2) trong module, chạy `migration:sync` rồi `migration:generate --fresh` để đồng bộ vào `render_migration_file.json`/`generated/`.
3. Model `AiVideoStudioProject`/`AiVideoStudioShot` (§4).
4. `BuildShotPromptAction` + test đơn vị field rỗng/đủ (§3.1, §11).
5. `CreateProjectAction`/`UpdateProjectAction`/`DeleteProjectAction` + `ProjectInputData`.
6. `CreateShotAction`/`UpdateShotAction`/`DeleteShotAction`/`ReorderShotsAction`/`SaveShotAiResultAction` + `ShotInputData`.
7. `CompileProjectDirectorPromptAction`.
8. `ProjectController` (index/create/store/show/edit/update/destroy/export) + `ShotApiController` (JSON, §6).
9. FormRequests (§7).
10. Views: `index`/`create`/`edit`/`show` (§8) — `show.blade.php` là trọng tâm UI.
11. JS quản lý inline shot (fetch tới `ShotApiController`, tái dùng pattern copy-to-clipboard đã có).
12. `AIVideoStudioTemplatePermissionSeeder` + đăng ký vào seeder tổng (`SystemDataSeeder` hoặc tương đương).
13. Route `web.php` (§6).
14. Sidebar entry (§9).
15. Feature test theo §11.
16. `vendor/bin/pint` + `php artisan test`.
