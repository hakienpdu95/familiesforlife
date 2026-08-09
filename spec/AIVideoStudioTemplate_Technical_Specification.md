# AI Video Studio Template — Quản lý Director Prompt Template cho video AI

**Đặc tả Kỹ thuật Chi tiết — ĐÃ triển khai (v1.0/v1.1); v1.2-v1.6 bổ sung techniques từ Hedra/DeepReel/BytePlus/Pyxeljam/LinkedIn; v1.7 rà soát nội bộ; v1.8 bổ sung từ sentx.ai; v1.9 bổ sung từ veed.io, xem changelog dưới**

**Phiên bản:** 1.9
**Ngày:** 2026-08-09
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Module:** `Modules/AIVideoStudioTemplate`

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
> `..._220001_add_resolution_field...` (v1.5), `..._230001_add_marketing_video_fields...` (v1.6) —
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

    // 5 field BỐI CẢNH PROJECT — được RENDER vào compiled_prompt của mọi Shot; sửa 1 trong 5 field
    // này sẽ build lại prompt toàn bộ Shot (§3.6, v1.7). Tập giá trị: hằng số ở Model (§4.1).
    $table->text('target_audience')->nullable();      // đối tượng khán giả mục tiêu
    // v1.6 (LinkedIn marketing guide) — Step 1 "Define Objectives" tách 2 khái niệm này riêng.
    $table->string('video_type', 20)->nullable();     // explainer|testimonial|product_demo|storytelling|other
    $table->text('core_message')->nullable();         // thông điệp/lời hứa cụ thể, VD "Tăng năng suất 40%"
    $table->string('aspect_ratio', 10)->nullable();   // 16:9|9:16|1:1|4:5
    $table->string('resolution', 10)->nullable();     // v1.5 (pyxeljam.com) — 720p|1080p|2K|4K

    // Anchoring — prefill vào Shot mới, KHÔNG bắt buộc (§0).
    $table->text('default_subject')->nullable();     // mô tả nhân vật/sản phẩm cố định
    $table->text('reference_image_url')->nullable(); // v1.2 — anchor bằng ẢNH tham chiếu (Hedra Step 2 + magichour.ai)
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

    $table->longText('compiled_prompt')->nullable(); // BuildShotPromptAction — ghi đè khi sửa
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
ÂM THANH (Audio/Soundscape): ...                             ← v1.4
LỜI THOẠI: "..."
CALL-TO-ACTION (CTA): ...                                    ← v1.6
RÀNG BUỘC (Constraints): ...

--- BỐI CẢNH CHIẾN DỊCH (tham khảo để giữ đúng tông — KHÔNG cần thể hiện toàn bộ trong shot này) ---
- Loại video: Testimonial (đánh giá/trải nghiệm thật)        ← Project (v1.7)
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
| `objective` / `reference_image_url` / `model_tool` / `reference_assets` / `qc_notes` / `ai_result` / `label` **KHÔNG BAO GIỜ** vào prompt | `objective` là mục tiêu kinh doanh (không đổi thứ gì trên khung hình); ảnh/asset tham chiếu là file đính kèm, cú pháp khác nhau tuỳ tool (§0/v1.4); còn lại là metadata nội bộ |
| Giá trị nhiều dòng: thụt lề 4 space cho dòng nối, chuẩn hoá `\r\n`→`\n` | Giữ ranh giới `NHÃN: giá trị` khi người dùng dán text xuống dòng; KHÔNG gộp về 1 dòng để không phá lời thoại nhiều câu |
| **KHÔNG** đưa vị trí "Shot N/M" vào prompt | Sẽ lỗi thời sau mỗi lần thêm/xoá/đổi thứ tự shot; đổi lại phải build lại toàn bộ sau mỗi cú bấm "↑/↓" — không đáng. Thứ tự đã có ở heading `## Shot N` của tài liệu xuất (§3.5) |

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

Thực thi ở `UpdateProjectAction::rebuildShotPrompts()` — chỉ đụng đúng cột `compiled_prompt`, không
chạm field nội dung nào của Shot. Chỉ chạy khi 1 trong 5 field bối cảnh thực sự đổi (so sánh trước/sau
`update()`), nên sửa `name`/`description`/`objective`/anchoring KHÔNG gây ghi thừa.

Cả 2 khác biệt này **bắt buộc hiển thị trên UI** (§8): callout ở khối Creative Brief ("sửa sẽ tự động
build lại prompt") và callout ở khối Anchoring ("KHÔNG tự động cập nhật Shot đã tạo").

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
    "style": "...", "mood": "...", "duration_seconds": 15, "audio_direction": "...",
    "constraints": "...", "script_line": "...",
    "model_tool": "...", "reference_assets": "...", "compiled_prompt": "...", "ai_result": null, "qc_notes": "..."
  }
  ```
  (`model_tool`/`qc_notes` thêm ở v1.2 — Hedra Model Selection Criteria + Step 3 evaluation checklist.
  `mood`/`duration_seconds` thêm ở v1.3 — deepreel.com, 2 field này nằm trong `compiled_prompt`.
  `audio_direction` (trong `compiled_prompt`) + `reference_assets` (metadata) thêm ở v1.4 — byteplus.com.)
- **`PUT shots/{shot}` là MERGE, KHÔNG phải replace (v1.7)** — field **vắng mặt** trong request giữ nguyên giá trị đang lưu; gửi **chuỗi rỗng** vẫn xoá field như cũ. Trước v1.7 field vắng mặt bị ghi `null`, nên `PUT {"subject":"x"}` xoá sạch 14 field còn lại; UI luôn gửi đủ nên chưa lộ, nhưng chỉ cần thêm 1 field mà quên gắn `.aivs-field` trong JS là field đó bị xoá ở MỌI shot sau mỗi lần gõ. Payload đầy đủ (như UI đang gửi) hành xử y hệt trước, nên đổi này KHÔNG phá contract hiện có.
- **Lỗi validate (422)** — dùng đúng shape mặc định của Laravel FormRequest (`{"message": "...", "errors": {"field": ["..."]}}`) — KHÔNG tự chế shape lỗi riêng, để JS xử lý bằng 1 hàm dùng chung cho mọi form trong module.
- **Lỗi ownership ở `reorder` (§3.4)** — trả 422 (không phải 403) với `{"message": "1 hoặc nhiều shot không thuộc project này."}` — đây là lỗi dữ liệu gửi lên sai, không phải thiếu quyền truy cập chức năng.
- **`DELETE shots/{shot}`** — 204 No Content, không body.
- **`PUT shots/{shot}/result`** — trả lại `{"ai_result": "..."}` (đủ dùng, không cần trả cả shot).
- **Concurrency (2 tab cùng sửa 1 shot)** — chấp nhận **last-write-wins** ở v1 (không optimistic locking/`updated_at` check) — đúng mức độ rủi ro chấp nhận được cho 1 công cụ nội bộ ít người dùng đồng thời trên cùng 1 project; ghi chú rõ đây là quyết định CÓ CHỦ ĐÍCH, không phải bỏ sót.

### 6.2 UX ghi/lưu field (chống spam request)

- Mỗi ô input/textarea trong Shot card: **debounce 600-800ms** sau khi ngừng gõ rồi mới gọi `PUT shots/{shot}` — KHÔNG gọi API mỗi keystroke.
- `<textarea>` hiển thị `compiled_prompt`: **luôn `readonly`** (chỉ đọc, sinh tự động) — không cho sửa tay trực tiếp, tránh lệch với 7 field nguồn khi user quên bấm sinh lại.
- Có trạng thái hiển thị nhỏ cạnh mỗi field đang debounce/đang lưu ("Đang lưu..."/"Đã lưu") để người dùng biết chắc đã ghi nhận trước khi rời trang — tránh mất dữ liệu do rời trang giữa lúc debounce chưa bắn request.

## 7. Validation

- `StoreProjectRequest`/`UpdateProjectRequest`: `name` required|string|max:200; `description`/`objective`/`target_audience`/`core_message`/`default_subject`/`reference_image_url`(max:2048)/`default_style`/`default_constraints` nullable|string; `aspect_ratio` nullable|string|in:16:9,9:16,1:1,4:5 (v1.2); `resolution` nullable|string|in:720p,1080p,2K,4K (v1.5); `video_type` nullable|string|in:explainer,testimonial,product_demo,storytelling,other (v1.6).
- `StoreShotRequest`/`UpdateShotRequest`: tất cả field director + `label` + `model_tool`(max:150, v1.2) + `qc_notes`(v1.2) đều `nullable|string`; `mood` nullable|string (v1.3); `duration_seconds` nullable|integer|min:1|max:36000 (v1.3); `audio_direction`/`reference_assets` nullable|string (v1.4); `cta_text` nullable|string|max:200 (v1.6) — không bắt buộc field nào (cho phép điền dần).
- `SaveShotAiResultRequest`: `ai_result` nullable|string.
- `ReorderShotsRequest`: `shot_ids` required|array, `shot_ids.*` required|integer — ownership thật (thuộc đúng project) kiểm tra ở Action (§3.4), KHÔNG kiểm tra được ở FormRequest thuần (cần query DB theo `{project}` route param).

## 8. Views — pattern UI

Trang `show.blade.php` (`@extends('layouts.backend')`):
- **Creative Brief card** (v1.2, chỉ hiện nếu có ít nhất 1 field điền) — hiển thị `objective`/`target_audience`/`video_type`/`core_message`(v1.6)/`aspect_ratio`/`resolution`(v1.5) dạng chỉ đọc, sửa qua "Sửa project".
- Header: tên project + field default (anchoring) hiển thị dạng card, có nút "Sửa" mở modal/inline edit. **Bắt buộc có 1 dòng ghi chú nhỏ, rõ ràng ngay dưới tiêu đề card**: *"Áp dụng khi tạo Shot MỚI — sửa ở đây KHÔNG tự động cập nhật các Shot đã tạo trước đó."* — đây là điểm dễ hiểu nhầm nhất của cơ chế anchoring (§0), phải hiển thị luôn trên UI, không chỉ nằm trong spec. Card này cũng hiện link `reference_image_url` (v1.2) nếu có điền.
- **Callout "Mẹo viết prompt"** (v1.2-v1.6, tĩnh, ngay trên danh sách Shot) — tóm tắt Key Prompting Principles của Hedra (mỗi shot 1 cảnh/khoảnh khắc, thay tính từ chung chung bằng mô tả cụ thể, luôn điền Camera, gọi tên phong cách rõ ràng) + deepreel.com (50-150 từ/prompt, ưu tiên 20-30 từ đầu, tránh yêu cầu đối lập, negative prompt cụ thể, kỳ vọng lặp 3-4 lần) + byteplus.com (không dồn nhiều hành động vào 1 shot ngắn, đừng bỏ qua Audio Direction) + pyxeljam.com/LinkedIn (v1.6 — phân biệt diễn đạt khẳng định ở Subject/Action/Style với loại trừ cụ thể dồn vào Constraints; điều chỉnh giọng văn theo nền tảng/đối tượng đã khai ở Creative Brief) + sentx.ai (v1.8 — "single hero focus" giới hạn số chủ thể trong khung hình, khác nguyên tắc "1 hành động/shot" đã có; "pacing" — nhịp lấy cảnh tách khỏi Duration; trỏ tới khối troubleshooting mới trong tài liệu xuất). Placeholder field Camera/Style (§8) cũng bổ sung vốn từ vựng cỡ cảnh/góc máy/chuyển động máy và cách gọi tên nguồn sáng + thời điểm trong ngày (v1.8) — 2 field callout nhắc là quan trọng nhưng trước đó không có ví dụ nào. v1.9 (veed.io) — câu chung "điều chỉnh giọng văn theo nền tảng" giờ trỏ rõ tới 2 dòng "Gợi ý theo nền tảng"/"Gợi ý theo loại video" tự hiện trong Creative Brief; placeholder Environment bổ sung spatial+temporal descriptors (field cuối cùng trong nhóm 5 field cốt lõi còn thiếu ví dụ), Style bổ sung ví dụ phong cách hoạt hình/nghệ thuật.
- Danh sách Shot: mỗi Shot là 1 card có:
  - **Input "Thời lượng (giây)"** (v1.3, `duration_seconds`, số) — đặt cạnh Label ở đầu card.
  - 10 input (textarea nhỏ) cho Subject/Action/Environment/Camera/Style/Mood/Audio/Lời thoại/CTA/Constraints — debounce theo §6.2, gọi `PUT shots/{shot}` → nhận về **toàn bộ shot resource mới** (§6.1), cập nhật lại `<textarea readonly>` hiển thị `compiled_prompt`. Field `mood` (v1.3), `audio_direction` (v1.4), `cta_text` (v1.6) và `constraints` có placeholder ví dụ cụ thể (Mood: liệt kê vài tông cảm xúc mẫu; Audio: ví dụ âm thanh môi trường + nhạc nền, phân biệt rõ với lời thoại; CTA: ví dụ text nút/đếm ngược; Constraints: minh hoạ kỹ thuật negative prompt — loại trừ rõ ràng thay vì mơ hồ).
  - `<textarea readonly>` (bắt buộc `readonly`, §6.2) hiển thị `compiled_prompt` + nút "Copy" (tái dùng pattern JS đã có ở `content-outlines.js`/breaking-news picker) + **bộ đếm từ** (v1.3, `.aivs-word-count`, JS tính client-side) cảnh báo nhẹ (đổi màu) nếu ngoài khoảng 50-150 từ — không chặn lưu.
  - **Input "Model/Tool đã dùng"** (v1.2, `model_tool`) — cùng cơ chế debounce/`.aivs-field` như các field trên, KHÔNG đưa vào `compiled_prompt`.
  - **Input "Tài liệu tham chiếu bổ sung"** (v1.4, `reference_assets`) — link ảnh/video/audio tham chiếu riêng cho shot này (khái niệm multi-reference của byteplus.com), KHÔNG đưa vào `compiled_prompt`.
  - `<textarea>` "Kết quả AI" (dán link/text) + nút "Lưu kết quả" → `PUT shots/{shot}/result`.
  - **Textarea "Ghi chú đánh giá (QC)"** (v1.2, `qc_notes`) — gợi ý 5 tiêu chí Hedra Step 3 (chủ thể/chuyển động/góc máy/phong cách/artifact) qua `label-text-alt`; placeholder (v1.3) minh hoạ cấu trúc ghi chú "3 điểm được + 1 điểm cần sửa" theo quy trình tinh chỉnh lặp của deepreel.com.
  - Nút xoá shot (**có `confirm()`/modal xác nhận** — xoá shot không cascade gì thêm nhưng vẫn là hành động mất dữ liệu không hoàn tác được); sắp xếp lại bằng 2 nút "↑"/"↓" đổi `sort_order` với shot liền kề (đã xác nhận: `PostCategory::reorder()` — tiền lệ reorder duy nhất tìm thấy trong repo — nhận thẳng mảng `order[]` từ client, KHÔNG dùng thư viện JS drag-drop nào (không có `Sortable`/`sortablejs` trong `resources/js/`) — v1 dùng nút mũi tên cho đơn giản, tránh thêm dependency JS mới; có thể nâng cấp lên drag-drop ở bản sau nếu cần).
  - Nút "+ Thêm shot".
- Cuối trang: nút "Xuất Director Prompt Template" → gọi `GET {project}/export`, trả về file `.md` tải xuống (nội dung từ `CompileProjectDirectorPromptAction`, v1.2 gồm cả khối Creative Brief + checklist đánh giá — xem §3.5), + nút "Copy toàn bộ" hiển thị trong `<textarea readonly>` lớn.

`_form.blade.php` (create/edit project, v1.2-v1.6):
- Khối "Creative Brief" mới (trước khối Anchoring) — `objective`/`target_audience` (textarea) + `video_type` (`<select>` 5 lựa chọn: explainer/testimonial/product_demo/storytelling/other, v1.6) + `core_message` (textarea, v1.6) + `aspect_ratio` (`<select>` 4 lựa chọn cố định: 16:9/9:16/1:1/4:5) + `resolution` (`<select>` 4 lựa chọn: 720p/1080p/2K/4K, v1.5) — tất cả không bắt buộc.
- Khối Anchoring thêm input `reference_image_url` (URL ảnh tham chiếu, cạnh `default_subject`).
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
- **Theo dõi hiệu suất thực tế sau khi đăng** (views/engagement/conversion — thực hành #10 pyxeljam.com "Test and Improve") — v1.5 chỉ dừng ở đánh giá CHẤT LƯỢNG video tạo ra (`qc_notes`, checklist §3.5), KHÔNG theo dõi hiệu suất phân phối/kinh doanh sau khi video được đăng lên nền tảng — thuộc phạm vi công cụ phân tích riêng, ngoài phạm vi "Director Prompt Template" của module này.

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
