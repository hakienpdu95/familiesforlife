# Module Dàn ý Nội dung (ContentOutlines)

**Phiên bản:** 1.25
**Ngày:** 2026-08-26
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Module mới:** `Modules/ContentOutlines` (tạo bằng `php artisan module:make ContentOutlines`)
**Module phụ thuộc:** `Modules\ContentFoundation` (ngữ cảnh biên tập theo `PostCategory`, 1 chiều — cùng cách `CoreIdeaExtractor`/`VideoIdeaExtractor` phụ thuộc module này). Không tích hợp `app/Services/AI/` (xem §0).
**Trạng thái:** v1.25 — thêm 2 field Constants dùng chung `product_service_docs`/`best_example_content` vào khối "Ngữ cảnh chuyên mục" (đối chiếu martech.org/how-to-build-an-ai-content-system-that-works, xem `CoreIdeaExtractor.md` §12.13 — nguồn sự thật của field; CỐ Ý KHÔNG áp dụng phần pipeline nhiều bước/agent QA riêng/human-in-the-loop có state của cùng bài viết, xem changelog v1.30 của `CoreIdeaExtractor.md` để biết lý do). v1.24 — đã vá 5 rủi ro sau review v1.0 (§4.1-§4.5) + áp dụng 2 điểm checklist content-marketing (§4.6) + 3 điểm phương pháp luận outline tổng quát (§4.7) + 4 điểm SEO content outline chuyên biệt (§4.8) + mô hình internal-link Pillar↔Cluster (§4.9) + CTA/độ tin cậy dữ liệu (§4.10) + answer-first/AI answer engine/chặn bịa số liệu/list lead-in/sai số ±10% (§4.11) + structure archetype/intent map 3 câu hỏi/Content-H3/differentiation note/FAQ nguồn PAA thật/anchor text (§4.12) + gợi ý Schema markup/alt text hình ảnh (§4.13) + từ khoá gần đầu/Meta 140-160/keyword trong 150 từ đầu/chặn nhồi từ khoá (§4.14) + kiểm kê SERP feature/khớp định dạng featured snippet/gom nhóm heading lặp lại đối thủ (§4.15) + H2 "Kết luận" (§4.16) + Feature `ArticleDrafting` — "Bước 2" sinh prompt viết bài từ outline đã duyệt (§4.17) + `cta_url` thật/hook mở bài/format scannable/chọn tiêu đề mạnh nhất (§4.18) + before-after example/2-3 phương án Meta/chặn case study bịa/cấm cliché mở bài (§4.19) + Feature `ArticleReview` — "Bước 3" sinh prompt soát lỗi/sửa bài đã viết (§4.20) + gợi ý ý tưởng infographic (§4.21) + gợi ý vị trí chèn câu chuyện/case study/testimonial THẬT của biên tập viên (§4.22) + nêu tên chuyên gia/tổ chức uy tín THẬT nếu biết (§4.23) + cảnh báo cascade khi regenerate + stepper/collapsible UX trang Show (§4.24) + **ghi rõ hành vi cascade vào §4.2 canonical + `<details>` mặc định đóng khi CHƯA DÙNG** (§4.24 mở rộng) + guardrail chống văn phong "lộ AI" (em-dash lạm dụng/từ chuyển ý sáo mòn lặp lại/chuỗi câu ngắn cùng cấu trúc) ở Bước 2 + rà lại ở Bước 3 (§4.25) + guardrail câu ≤20 từ + tránh thuật ngữ mơ hồ không có ngữ cảnh cụ thể (§4.26) + FAQ answer ~125 ký tự cho AI answer engine trích dẫn trực tiếp (§4.27). Chưa qua vòng tinh chỉnh dựa trên phản hồi sử dụng thật DÀI HẠN (khác `CoreIdeaExtractor.md` đã qua 28 version).

> **v1.25 (2026-08-26, đối chiếu martech.org/how-to-build-an-ai-content-system-that-works — chuẩn
> hoá "Constants" dùng chung với `CoreIdeaExtractor`/`PromptFrameworkStudio`, xem `CoreIdeaExtractor.
> md` §12.13 cho lý do đầy đủ):** thêm 2 dòng vào khối "Ngữ cảnh chuyên mục" (`buildMiddle()`) — tài
> liệu mô tả chi tiết sản phẩm/dịch vụ (`product_service_docs`) và ví dụ nội dung/dàn ý mẫu TỐT NHẤT
> (`best_example_content`), đọc thêm 2 cột mới trên `content_foundations`. Bọc delimiter +câu rào
> prompt-injection (CLAUDE.md §0) vì đây là văn bản dài editor có thể dán nguyên văn từ nguồn khác —
> khác các field text ngắn còn lại trong khối này vốn chỉ là 1 bullet "nhãn: giá trị" thuần (kể cả
> `style_sample` ở TOP §4.1 CHƯA từng được bọc — khoảng trống có sẵn từ trước, ngoài phạm vi bản vá
> này, không đổi kèm). Cùng đối chiếu bài viết này, 3 khuyến nghị còn lại (pipeline nhiều bước có
> Orchestrator, agent Editor/Fact-checker/AI-tell-detector tách context riêng, human-in-the-loop có
> `status`/`approved_at` thật) đã cân nhắc và **KHÔNG áp dụng** cho module này — đảo ngược quyết
> định kiến trúc đã chốt ở §0 mục 1 ("sinh 1 prompt, chạy 1 lần", từ chối multi-step nhiều lần trong
> changelog: §4.9/§4.15) là thay đổi lớn hơn hẳn phạm vi "chuẩn hoá Constants", để dành quyết định
> riêng nếu cần đổi hướng.
>
> **v1.24 (2026-08-14, tham khảo goepps.com/blog/which-content-formats-do-ai-engines-actually-cite-most
> — đọc theo yêu cầu tổng hợp kỹ thuật mới, rà soát cả hệ thống + module AIVideoStudioTemplate):**
> nguồn (bài blog quảng bá 1 tool trả phí "AEO by GoEpps") liệt kê 3 nhóm định dạng AI answer engine
> hay trích dẫn: Q&A ngắn gọn/trực tiếp (câu trả lời ≤~125 ký tự), so sánh/danh sách/bảng có cấu
> trúc (facts rời rạc, ưu tiên dữ liệu gốc/insight độc quyền), và đánh giá/đề cập từ bên thứ ba
> (external validation). 2/3 nhóm ĐÃ có cơ chế tương đương từ §4.11/§4.15 (answer-first, khớp định
> dạng featured snippet, USP/differentiation note/"information gain") — không lặp lại. Nhóm thứ 3
> (review/mention bên thứ ba) khác bản chất — nguồn nói về thu thập review TRÊN NỀN TẢNG KHÁC
> (off-page, quy trình xin khách hàng đánh giá/PR), không phải cấu trúc nội dung 1 bài outline; phần
> gần nhất áp được TRONG bài viết (trích dẫn testimonial/chuyên gia thật) đã có ở §4.22/§4.23.
>
> **1 điểm THẬT còn thiếu, đã áp dụng** — ngưỡng CỤ THỂ mà "answer-first" (§4.11) chưa có: câu trả
> lời FAQ nên đủ NGẮN (~125 ký tự) để AI answer engine trích dẫn nguyên văn làm câu trả lời trực
> tiếp — khác answer-first của H2/H3 (bao quát chủ đề rộng hơn, có thể dài 1-2 câu). Thêm vào bước
> FAQ ở CẢ 3 `outline_depth` (`BuildContentOutlinePromptAction::buildBottomBrief/Standard/Detailed()`,
> §4.27 trong docblock class — nguồn thật, tránh trôi khỏi spec) — không đổi số câu hỏi/nguồn PAA đã
> chốt ở §4.12(5), không đổi signature/DB.
>
> **Rà soát module `AIVideoStudioTemplate` (theo yêu cầu) — KHÔNG áp dụng gì:** nguồn nói về cách AI
> answer engine trích dẫn nội dung VĂN BẢN đã xuất bản/index được, trong khi module đó chỉ sinh
> Director Prompt Template cho tool tạo VIDEO — không có caption/mô tả/metadata xuất bản nào để
> "được trích dẫn". Cùng nhóm lý do đã loại "video SEO" (spec đó v1.16) và "SEO caption/searchable
> phrases" (v1.21) — không mở lại, xem changelog `AIVideoStudioTemplate_Technical_Specification.md`.

> **v1.22 (đối chiếu spec/giadinh.md — Moz Whiteboard Friday "7 Tips for Writing Great Content
> with ChatGPT or Gemini", Chima Mmeje, 2026-08-08):** đối chiếu 7 kỹ thuật prompt engineering
> của nguồn với toàn bộ prompt thật đang sinh ở `ContentOutlines`/`CoreIdeaExtractor`/
> `ContentFoundation`/`PromptFrameworkStudio`. 4/7 đã có tương đương đầy đủ (context, product-led
> qua cơ chế khác, cá nhân hoá/storytelling — §4.22, đã có 1 phần cho training-document qua
> `style_sample`). 2/7 là quyết định ĐÃ CHỐT có chủ đích, KHÔNG áp dụng: "viết từng đoạn nhỏ
> nhiều lượt copy-paste" (mâu thuẫn trực tiếp mô hình "1 prompt viết cả bài 1 lần" đã chốt ở
> §4.17) và field "core offering" riêng cho `ContentFoundation` (đã thử và bỏ trước đó, xem
> `CategoryFoundationData.php` — giữ nguyên, không mở lại). "Feedback lặp lại để AI học dần
> (~70% chất lượng)" đòi hỏi lưu lịch sử chỉnh sửa — tính năng mới, KHÔNG làm ở v1.22 (ngoài
> phạm vi, xem §9). 1/7 là khoảng trống THẬT, nhỏ, an toàn — đã áp dụng, xem §4.25.

> **v1.21 (theo dõi đề xuất ưu tiên của người dùng sau v1.20 — 3 điểm "ưu tiên cao" đối chiếu lại,
> xem §4.24):** người dùng hỏi "đã fix chưa" 5 đề xuất ưu tiên. Kết quả rà soát: (1) "Ghi rõ hành
> vi cascade trong spec" — v1.20 CHỈ ghi ở §4.24 (mục rà soát rủi ro), CHƯA cập nhật §4.2 (nơi
> CANONICAL cho hành vi Regenerate) — ĐÃ SỬA, thêm bullet đầy đủ vào §4.2 + sửa câu mô tả
> `data-confirm-regenerate="1"` đã LỖI THỜI (thực tế đã đổi thành message ĐỘNG từ v1.20). (2) "Soft
> warning cho Bước 2/3" — ĐÃ CÓ từ v1.14/v1.16 (xác nhận lại, không đổi). (3) "Regenerate tuyệt đối
> không đụng field Bước 2/3" — ĐÃ ĐÚNG trong code từ trước, verify lại bằng test sống (tạo outline →
> Bước 2/3 → regenerate outline với input khác hẳn → xác nhận 4 field GIỮ NGUYÊN) — bổ sung docblock
> `RegenerateContentOutlinePromptAction` nêu rõ tường minh (trước đó chỉ suy luận được từ việc field
> KHÔNG xuất hiện trong `update()`, chưa có câu khẳng định trực tiếp). (4) "Refactor 3 depth giảm
> duplication" — CHƯA làm (đúng, người dùng đánh dấu "ưu tiên trung bình", không yêu cầu làm ngay).
> (5) "Collapsible mặc định ĐÓNG các khối CHƯA DÙNG" — v1.20 có collapsible nhưng default-open logic
> KHÔNG khớp yêu cầu này (Bước 3 luôn mở bất kể đã dùng hay chưa) — ĐÃ SỬA: cả Bước 2/3 cùng 1 quy
> tắc nhất quán, `<details>` mở CHỈ khi đã có kết quả (`article_draft_prompt`/`review_prompt`), đóng
> khi chưa dùng.

> **v1.20 (rà soát rủi ro nội bộ do người dùng thực hiện — 5 điểm, xem §4.24):** không đối chiếu
> nguồn ngoài — người dùng tự rà soát spec/code và nêu 5 rủi ro. Kết quả: (1) **Instruction
> overload ở `detailed`** — GHI NHẬN là rủi ro cần THEO DÕI sau khi có dữ liệu sử dụng thật, KHÔNG
> code ngay (đúng đề xuất của người dùng — "cân nhắc giảm nếu thấy AI bỏ sót", điều kiện chưa xảy
> ra). (2) **Maintainability 3 biến thể BOTTOM** (sửa 1 chỉ dẫn chung phải sửa 3 nơi) — GHI NHẬN
> là technical debt, người dùng tự đánh dấu "không bắt buộc v1" — CHƯA refactor (rủi ro thay đổi
> hành vi 3 template lớn cùng lúc, cần 1 quyết định riêng nếu muốn làm). (3) **Cascade khi
> Regenerate Outline** — XÁC NHẬN ĐÚNG là gap thật (Regenerate không đụng `approved_outline`/
> `article_draft_prompt`/`drafted_article`/`review_prompt`, nhưng KHÔNG có cảnh báo rõ) — ĐÃ SỬA:
> confirm dialog ở `edit.blade.php` đổi message khi Bước 2/3 đã có + icon ⚠ trên nút "Sửa & Sinh
> lại" ở `show.blade.php`. (4) **Soft warning cho Bước 2/3** — KHÔNG PHẢI gap: đã có từ v1.14
> (`BuildArticleDraftPromptAction::WORD_COUNT_WARNING_THRESHOLD`) và v1.16
> (`BuildArticleReviewPromptAction::WORD_COUNT_WARNING_THRESHOLD`) + banner tương ứng ở
> `show.blade.php` — đã verify lại code, thông báo phản hồi người dùng thông tin này đã lỗi thời.
> (5) **UX trang Show quá tải** — ĐÃ SỬA: thêm stepper Bước 1→2→3 (badge màu theo trạng thái hoàn
> thành, có anchor link) + bọc Bước 2/Bước 3 trong `<details>` (thu gọn được, Bước 2 tự thu gọn khi
> đã có Bước 3, Bước 3 luôn mở vì là bước cuối).

> **v1.19 (đối chiếu tofuhq.com/post/prompt-engineering-for-blog-posts — 1 điểm nhỏ ÁP DỤNG, xem
> §4.23):** nguồn liệt kê kỹ thuật prompt engineering cho blog post ở quy mô team/nhiều brand.
> Brainstorming (10 ý tưởng chủ đề, keyword theo funnel stage) thuộc phạm vi `CoreIdeaExtractor`,
> không phải module này (cùng lý do đã từ chối "Foundation Prompts" ở §4.21); Writing stage
> (word count/audience/tone/CTA/persona) đã có tương đương; "Company-Specific Data Integration"
> (testimonial/case study thật) ĐÃ áp dụng ở §4.22 (v1.18), nguồn này chỉ xác nhận lại; "Example-
> Based Anchoring" (viết theo style 1 tác giả cụ thể) không cần cơ chế mới — đã làm được qua
> `tone_style` (free text) + `competitor_urls` có sẵn. 1 điểm THẬT áp dụng: mở rộng "Độ tin cậy dữ
> liệu" (`standard`/`detailed`) thêm yêu cầu nêu TÊN chuyên gia/tổ chức uy tín THẬT nếu biết, thay
> vì chỉ nói chung "nghiên cứu cho thấy" — cùng guardrail "không chắc thì bỏ qua, không tự bịa
> tên". TỪ CHỐI rõ "mention keyword ít nhất 3 lần" của nguồn — MÂU THUẪN với quyết định đã chốt ở
> §4.14/§4.21 (không có ngưỡng lặp từ khoá bắt buộc) — giữ nguyên quyết định cũ.

> **v1.18 (đối chiếu checkcopywriting.com/write-blog-with-ai — 1 điểm nhỏ ÁP DỤNG, xem §4.22):**
> nguồn là bài chia sẻ quy trình cá nhân (3 giai đoạn: Preparation/Drafting/Finalization) — phần
> lớn đã có cơ chế tương đương hoặc thuộc tầng THỦ CÔNG ngoài AI (đọc to bắt lỗi giọng máy móc ≈
> "robotic" đã có §4.20; Grammarly/Quetext/Ubersuggest là tool ngoài, §0; "AI editing chỉ nên gợi ý,
> không tự viết lại để giữ giọng văn" ≈ "precise edits, không full rewrite" đã có §4.20; "section-
> by-section có gate phê duyệt" — CÙNG kỹ thuật đã đối chiếu và TỪ CHỐI ở §4.21, nguồn này chỉ xác
> nhận lại, không đổi quyết định). 1 điểm THẬT áp dụng, CẢ 3 `outline_depth` (chỉ `standard`/
> `detailed` có bước để gắn vào): nguồn nhấn mạnh chèn personal story/testimonial thật/case study
> có số liệu đo được/dữ liệu độc quyền làm yếu tố khác biệt LỚN NHẤT so với nội dung AI khai thác
> chung — mở rộng "differentiation note" đã có (§4.12) thêm 1 câu mời biên tập viên tự điền nội
> dung THẬT của họ vào vị trí phù hợp (không tự tạo nội dung thay thế).

> **v1.17 (đối chiếu blog.qolaba.ai/.../blog-writing-prompts-from-outline-to-publication — 1 điểm
> nhỏ ÁP DỤNG, xem §4.21):** nguồn mô tả pipeline 5 giai đoạn (Foundation/Outlining/Drafting-
> Editing/SEO/Collaborative Publishing). Đối chiếu: Foundation Prompts (ý tưởng chủ đề TRƯỚC KHI
> có topic) thuộc phạm vi `CoreIdeaExtractor`/`VideoIdeaExtractor`, không phải module này; Strategic
> Outlining + SEO tích hợp xuyên suốt đã có cơ chế tương đương. 1 điểm THẬT áp dụng: bước làm rõ
> nội dung heading (`standard`/`detailed`) thêm gợi ý Ý TƯỞNG INFOGRAPHIC khi phần đó có nhiều số
> liệu/bước liên tiếp phù hợp minh hoạ trực quan. Đã hỏi người dùng 1 câu quyết định phạm vi: nguồn
> khuyến nghị soạn bài theo TỪNG SECTION riêng (nhiều prompt/section) — người dùng xác nhận GIỮ mô
> hình "1 prompt viết cả bài" hiện tại, KHÔNG thêm "Bước 2b". Cũng TỪ CHỐI rõ "mật độ từ khoá 1-2%"
> của nguồn vì MÂU THUẪN trực tiếp với quyết định đã chốt ở §4.14 (không có ngưỡng % bắt buộc) —
> giữ nguyên quyết định cũ, không để 2 nguồn mâu thuẫn cùng tồn tại trong 1 module.

> **v1.16 (đối chiếu junia.ai/blog/ai-blog-writing-prompts — 4 điểm nhỏ ÁP DỤNG + 1 Feature mới,
> xem §4.19/§4.20):** nguồn liệt kê 8 loại prompt cho quy trình viết blog bằng AI — phần lớn đã có
> cơ chế tương đương. Đã hỏi người dùng 2 câu quyết định phạm vi: (1) 4 điểm nhỏ ÁP DỤNG NGAY, CẢ 3
> `outline_depth`, không đổi kiến trúc: gợi ý "before-after comparison" làm 1 loại ví dụ (bước làm
> rõ heading); 2-3 phương án Meta Title/Description (thay vì 1 phương án); mở rộng "Không bịa số
> liệu" thêm "case study"; cấm cụ thể cụm mở đầu sáo rỗng kiểu "trong thế giới hiện đại ngày nay"
> (ArticleDrafting). (2) 3 prompt REVIEW của nguồn (SEO Optimization/Readability Editing/Final
> Editing — đều review 1 bài ĐÃ VIẾT XONG) hé lộ khoảng trống THẬT: module dừng ở "Bước 2 — viết
> bài", chưa có "Bước 3 — soát lỗi/sửa". Người dùng xác nhận MUỐN thêm — đã triển khai Feature
> `ArticleReview` (§4.20): field mới `drafted_article`/`review_prompt`, gộp 3 loại prompt review
> của nguồn thành 1 prompt duy nhất (giữ mô hình "sinh 1 prompt, chạy 1 lần"), yêu cầu đề xuất SỬA
> CHÍNH XÁC từng đoạn — KHÔNG viết lại toàn bài (đúng tinh thần "precise edits, without full
> rewrites" của nguồn). Không gọi AI Provider trong app (§0 mục 1 không đổi).

> **v1.15 (đối chiếu "10-step AI prompt chain viết pillar post" người dùng cung cấp trực tiếp, kèm
> 1 đoạn quảng cáo sản phẩm bên thứ 3 — KHÔNG xác nhận/tích hợp sản phẩm đó, chỉ đối chiếu kỹ
> thuật, xem §4.18):** nguồn là 1 chuỗi 10 prompt tách rời (setup persona → audience deep-dive →
> competitive analysis → headline brainstorm → outline → hook/intro → core content → conclusion/CTA
> → SEO metadata/social snippets → final assembly). Phần lớn đã có cơ chế tương đương (persona,
> audience, competitive analysis+USP, outline, meta title/description, final assembly Markdown-
> only). Đã hỏi người dùng 2 câu quyết định phạm vi trước khi áp dụng: (1) 4 điểm nhỏ ÁP DỤNG — CẢ
> 3 `outline_depth`/không đổi kiến trúc: field mới `cta_url` (URL CTA thật, khác `content_goal` chỉ
> định hướng LOẠI CTA) nhúng vào câu chuyển tiếp cuối outline (bước CTA, §4.10) + cuối bài viết
> (ArticleDrafting, §4.17); chỉ dẫn viết hook mở bài (Bước 2 — ArticleDrafting); yêu cầu định dạng
> dễ scan (đoạn ngắn/bullet/bold) (Bước 2); yêu cầu chọn tiêu đề MẠNH NHẤT + lý do (bước brainstorm
> tiêu đề, Bước 1). (2) Social snippet (X/LinkedIn) + tags — TỪ CHỐI, xem "Ngoài phạm vi" dưới đây.

> **v1.14 (Feature `ArticleDrafting` — "Bước 2: viết bài từ outline", xem §4.17):** người dùng yêu
> cầu rõ: sau khi có outline (từ prompt "Bước 1" hiện có), bước tiếp theo là viết bài DỰA TRÊN
> outline đó — nhưng vẫn CHỈ sinh prompt (không gọi AI Provider trong app, giữ đúng §0). Khác v1.13
> (chỉ thêm 1 khối text TĨNH có placeholder tay vào CUỐI prompt outline): v1.14 thêm 1 FEATURE thật
> — 2 cột DB mới (`approved_outline`: biên tập viên dán outline THẬT nhận từ AI ngoài;
> `article_draft_prompt`: prompt viết bài đã sinh, nhúng SẴN outline đó — không còn placeholder
> tay) + `BuildArticleDraftPromptAction` + `SaveApprovedOutlineAndBuildArticlePromptAction` + route/
> Controller/UI riêng ("Bước 2" ở `show.blade.php`). Khối "## Bước tiếp theo" tĩnh của v1.13 bị XOÁ
> (superseded — 2 nơi cùng hướng dẫn "viết bài từ outline" theo 2 cách khác nhau dễ gây nhầm lẫn).
> Refactor kèm theo: tách `estimateWordCount()`/`buildFamilyValuesBlock()` → trait
> `BuildsSharedPromptBlocks`, dời `ResolvesCategoryContext` → `Features/Concerns/` (cả 2 dùng chung
> bởi `OutlineGeneration` VÀ `ArticleDrafting` — điểm dùng thứ 2 xuất hiện, cùng nguyên tắc extract
> đã áp dụng ở §4.6). Không đổi hành vi/schema của Feature `OutlineGeneration` ngoài việc revert
> phần template tĩnh v1.13.

> **v1.13 (đối chiếu framework "2-bước Outline → Draft" người dùng cung cấp trực tiếp, không kèm
> URL — 2 điểm ÁP DỤNG, xem §4.16):** nguồn mô tả 2 prompt tách rời: Prompt 1 sinh outline (persona/
> audience/goal/format H2-H3+3 bullet/intro+conclusion) và Prompt 2 dùng outline đã duyệt để viết
> bài hoàn chỉnh (word count/tone/style guideline: active voice, tránh fluff, actionable takeaway
> mỗi phần). Phần lớn field của Prompt 1 (persona, audience, goal, format H2/H3+bullet) đã có cơ chế
> tương đương từ v1.0. 2 điểm THẬT chưa có, áp dụng CẢ 3 `outline_depth`: (1) "brief introduction and
> conclusion section" hé lộ khoảng trống: module có "Luận điểm chính" (mở đầu) nhưng CHƯA có H2
> "Kết luận" khép lại Dàn ý — thêm yêu cầu này vào bước dựng H2; (2) Prompt 2 (viết bài từ outline đã
> duyệt) hoàn toàn CHƯA có — thêm 1 khối "## Bước tiếp theo" ở cuối prompt, là 1 PROMPT MẪU TĨNH
> (không tự chạy AI) để biên tập viên copy sang lượt chat MỚI, tái dùng nguyên field đã có
> (`target_keyword`/`desired_word_count`/`tone_style`/`language`), kèm cảnh báo rõ AI không cần xử
> lý khối này trong câu trả lời hiện tại. KHÔNG áp dụng: chạy Prompt 2 thật trong app (đòi hỏi gọi AI
> Provider, ngoài phạm vi §0). Không đổi DB schema/signature Action (chỉ đổi tham số nội bộ của
> `buildBottom()` private).

> **v1.12 (đối chiếu writerush.ai/serp-based-outline-creation — 3 điểm ÁP DỤNG, xem §4.15):** nguồn
> là bài hướng dẫn "SERP-based outline creation" chuyên biệt cho quy trình phân tích SERP trước khi
> dựng outline — phần lớn kỹ thuật (review top 5-10 trang, PAA, tìm kiếm liên quan, content gap,
> EEAT, semantic SEO) đã có cơ chế tương đương từ v1.0-v1.11. 3 điểm THẬT chưa có, áp dụng CẢ 3
> `outline_depth`: (1) bước Research thêm yêu cầu ghi chú CỤ THỂ các SERP feature đang xuất hiện
> cho từ khoá (loại featured snippet: đoạn văn/danh sách/bảng/không có, video, hình ảnh, local
> pack, product panel, "Things to know"...) — trước đây chỉ nói chung "research SERP"; (2) mở
> rộng "answer-first" (đã có §4.11) thêm yêu cầu khớp ĐÚNG định dạng câu trả lời (đoạn văn/danh
> sách/bảng) với định dạng featured snippet quan sát được ở Bước 1 cho câu hỏi tương ứng; (3) bước
> Research thêm yêu cầu nhóm các H2/H3 LẶP LẠI giữa nhiều trang đối thủ thành 1 danh sách chủ đề
> độc giả chắc chắn kỳ vọng thấy, dùng làm input bắt buộc-tham chiếu cho bước dựng H2/H3 + self-
> check. KHÔNG áp dụng: bảng theo dõi đối thủ có cấu trúc cột cố định (content type/format/angle/
> FAQ/CTA mỗi trang) — mục đích cốt lõi (gom nhóm heading lặp lại + xác định gap) đã đạt qua (3)
> mà không cần thêm 1 section output mới hiển thị bảng research thô, khác tinh thần "output chỉ
> gồm artifact đã tổng hợp" hiện tại; đánh giá mức đầu tư nội dung đối thủ đã có ở "Đánh giá độ
> khó cạnh tranh" (`detailed`, §4.8) — mở rộng thêm 1 câu SERP feature vào ĐÚNG bước đó thay vì
> tạo section mới trùng lặp. Không đổi DB schema/signature Action.

> **v1.11 (đối chiếu moodymedia.io/blog/how-to-write-for-seo — 4 điểm ÁP DỤNG, xem §4.14):** nguồn
> là bài SEO writing beginner-guide tổng hợp trích dẫn Google Search Central/Ahrefs/Semrush/
> Bruce Clay/SEO.com/eWebMarketing — phần lớn đã có cơ chế tương đương từ v1.0-v1.10: viết cho
> người đọc trước khi tối ưu SEO (đúng tinh thần thesis+USP đã có); nghiên cứu PAA/H2-H3 đối thủ
> trước khi viết (đã có ở bước Research + FAQ nguồn PAA thật §4.12); EEAT/quality-over-keyword-
> stuffing (đã có §4.11); đoạn ngắn/bullet có dẫn nhập (đã có §4.11); external link (đã có v1.0);
> AI chỉ là trợ lý nghiên cứu, biên tập viên quyết định cuối (đúng bản chất module — chỉ SINH
> PROMPT, không tự chạy AI, §0); 80/20 rule + SEO là việc liên tục dài hạn (triết lý chung, không
> map vào 1 chỉ dẫn cụ thể). Còn lại 4 điểm on-page CỤ THỂ chưa có, áp dụng CẢ 3 `outline_depth`:
> (1) từ khoá mục tiêu phải đặt GẦN ĐẦU (không chỉ "chứa") ở tiêu đề H1 + Meta Title + Meta
> Description; (2) Meta Description đổi ngưỡng "≤155" → khoảng "140-160" ký tự + thêm câu chủ
> động + 1 lời mời hành động ngắn (tăng CTR từ SERP — Meta vẫn đặt SAU bước dựng dàn ý trong quy
> trình, đúng nguyên tắc "viết Meta sau khi có nội dung chính" của nguồn, không cần đổi thứ tự
> bước); (3) bước thesis thêm yêu cầu đặt từ khoá mục tiêu tự nhiên trong 100-150 từ đầu bài; (4)
> thêm ghi chú chung "Mật độ từ khoá" vào dòng Lưu ý EEAT — không có ngưỡng % bắt buộc, tránh
> nhồi từ khoá — gộp nguyên tắc "không nhồi từ khoá" đã áp dụng riêng lẻ cho alt text (§4.13)/
> anchor text (§4.12) thành 1 quy tắc tổng quát cho TOÀN BÀI. Không đổi DB schema/signature Action.

> **v1.10 (đối chiếu tangence.in/blog/seo-content-creation — 2 điểm ÁP DỤNG, xem §4.13):** nguồn
> là bài SEO content creation tổng quát 2026 — hầu hết đã có cơ chế tương đương hoặc ngoài phạm
> vi: topic cluster/pillar ≈ `content_role` (§4.9); tools Ahrefs/SEMrush/Surfer/Clearscope ngoài
> phạm vi (§0 không tích hợp tool ngoài); lịch cập nhật content 6-12 tháng là audit HẬU-publish,
> khác tầng "sinh outline trước khi viết"; semantic/LSI keyword ≈ `secondary_keywords` đã có;
> featured snippet/AI Search optimization ≈ answer-first/AI answer engine đã có (§4.11); EEAT/
> external link/readability đều đã có từ v1.0-v1.2. Còn lại 2 điểm on-page CHƯA có: (1) gợi ý loại
> Schema markup phù hợp (Article/BlogPosting mặc định; +FAQPage nếu có khối FAQ; +HowTo/+ItemList
> theo `structure archetype` đã chọn ở §4.12) nối vào bước Meta, CẢ 3 `outline_depth` (brief dùng
> heuristic đơn giản hơn — không có structure archetype để tham chiếu); (2) alt text ngắn cho MỖI
> hình ảnh được gợi ý trong bước làm rõ nội dung heading, CHỈ `standard`/`detailed`. Cả 2 chỉ GỢI
> Ý (loại schema/alt text), không sinh JSON-LD hay ảnh thật. Không đổi DB schema/signature Action.

> **v1.9 (đối chiếu aiexecutionhub.com/blog/ai-blog-post-outlining-system — 6 điểm ÁP DỤNG, xem
> §4.12):** nguồn là hệ thống outlining 4-bước tách rời (Intent Map → Structure Selection →
> Section Briefs → H3 Logic), mỗi bước gọi AI riêng, có con người dán kết quả bước trước sang
> prompt bước sau — khác model "1 prompt, chạy 1 lần" đã chốt ở §0; kiến trúc 4-bước tách rời với
> nhiều lượt gọi AI KHÔNG áp dụng (đòi hỏi module tự điều phối nhiều lượt gọi AI Provider + lưu
> trạng thái giữa các bước). Nhưng 6 chi tiết PROMPT-LEVEL gộp được vào CÙNG 1 prompt hiện có: (1)
> bước mới "Chọn kiểu bài (structure archetype)" — 4 kiểu Hướng dẫn tuần tự/Framework/So sánh-kết
> luận/Danh sách tài nguyên (`standard`/`detailed`, kèm mục output `## Kiểu bài`); (2) "Xác nhận ý
> định tìm kiếm" mở rộng thành đoạn trả lời 3 câu hỏi (đọc xong làm được gì/biết gì đầu-cuối/vì
> sao bỏ tab, `standard`/`detailed`); (3) quy tắc "Content H3, không phải Label H3" + ngưỡng H2
> >400 từ nên có ≥2 H3 (`standard`/`detailed`); (4) mỗi H2 thêm 1 câu "differentiation note" — phần
> này khác gì bài đối thủ điển hình (`standard`/`detailed`); (5) FAQ ưu tiên câu hỏi PAA THẬT quan
> sát được khi research, không tự bịa (CẢ 3 `outline_depth`); (6) mỗi gợi ý internal link kèm 1
> đề xuất anchor text ngắn (`standard`/`detailed`). Không đổi DB schema/signature Action.

> **v1.8 (đối chiếu advancedwebranking.com/blog/ai-generated-content-prompts-framework — 4 điểm
> ÁP DỤNG, xem §4.11):** nguồn là framework prompt-engineering cho AI content-gen qua kiến trúc
> 3-giai đoạn có approval gate + so sánh/hợp nhất đa AI model — bản chất là quy trình biên tập THỦ
> CÔNG qua nhiều phiên chat AI riêng biệt, khác model "1 prompt, người dùng tự chạy 1 lần" đã chốt
> ở §0; kiến trúc approval-gate, tổng hợp 7 loại tài liệu tham khảo, so sánh 4 AI model, phân loại
> entity Essential/Optional đều KHÔNG áp dụng (đòi hỏi gọi AI Provider trong app hoặc research đa
> vòng, ngoài phạm vi). Nhưng 4 chi tiết PROMPT-LEVEL tách được khỏi kiến trúc đó, áp dụng cho CẢ 3
> `outline_depth`: (1) mỗi H2/H3 phải mở đầu bằng câu trả lời TRỰC TIẾP (answer-first) trước khi
> giải thích thêm; (2) bước research thêm lưu ý cách AI answer engine (AI Overview/ChatGPT/
> Gemini...) đang trả lời câu hỏi, không chỉ SERP cổ điển; (3) nối thêm "Không bịa số liệu" vào
> dòng Lưu ý EEAT; (4) danh sách/bullet phải có câu dẫn nhập ngữ cảnh (`standard`/`detailed`) +
> ước lượng số từ mỗi phần cho phép sai số ±10% (`detailed`). Không đổi DB schema/signature Action.

> **v1.7 (đối chiếu 8bitcontent.com/content-quality-assessment-framework — 2/5 yếu tố ÁP DỤNG,
> xem §4.10):** nguồn là rubric CHẤM ĐIỂM nội dung ĐÃ PUBLISH (5 yếu tố × 0-3 điểm = thang 0-15,
> quyết định xoá/viết lại/cập nhật/quảng bá/tái sử dụng) — bản chất là tool AUDIT hậu-publish,
> khác tầng "sinh outline TRƯỚC KHI viết" của module này; cơ chế chấm điểm KHÔNG áp dụng. Nhưng
> 2/5 yếu tố hé lộ chỉ dẫn NỘI DUNG outline đang thiếu (3/5 còn lại đã có sẵn: Contextual
> Understanding ≈ thesis+FAQ+self-check; Uniqueness & Value-Add ≈ USP+information gain): (1)
> **Guidance** — outline trước đây KHÔNG có bước/mục nào gợi ý CTA hoặc bước tiếp theo cho độc
> giả; thêm bước "Gợi ý CTA/bước tiếp theo" (tuỳ theo `content_goal`/`search_intent` đã có ở
> TOP, tự nhiên né CTA bán hàng khi ý định là informational) + mục `## CTA` cuối định dạng
> output, CẢ 3 `outline_depth`. (2) **Credibility & Freshness** — Lưu ý EEAT trước đây chỉ nói
> "cần chuyên gia rà soát", chưa yêu cầu ưu tiên dữ liệu MỚI; thêm "Độ tin cậy dữ liệu: ưu tiên
> dữ liệu/nguồn trong 12 tháng gần nhất, trích dẫn 2-3 nguồn uy tín cho số liệu quan trọng" nối
> vào cùng dòng EEAT, CẢ 3 `outline_depth`. Không đổi DB schema/signature Action.

> **v1.6 (đối chiếu umbrex.com/.../content-pillars-topic-cluster-framework — 1 điểm ÁP DỤNG,
> xem §4.9):** nguồn là framework CHƯƠNG TRÌNH nội dung cấp site (12 bước: audit, chọn 3-7
> pillar, đo ROI theo quý, PESO distribution...) — hầu hết thuộc tầng lập kế hoạch/đo lường
> nhiều-bài-viết, khác tầng "sinh outline cho 1 bài đã chọn" mà module phục vụ, không áp dụng
> (cùng lý do đã từ chối phần lớn piperocket.digital ở v1.2). 1 điểm THẬT áp dụng được ở mức 1
> bài: **mô hình internal-link Pillar↔Cluster** (cluster → pillar = parent link; pillar → mọi
> cluster = child link; cluster → cluster liên quan = sibling link). Thêm field mới
> `content_role` (`pillar`/`cluster`/rỗng — cột mới trên `content_outlines`) định hướng CHIỀU
> gợi ý internal link ở bước internal/external link (`standard`/`detailed` — `brief` không có
> bước này nên không áp): `pillar` → cấu trúc như hub, mỗi H2 ứng 1 chủ đề hẹp có thể tách bài
> cụm riêng, đề xuất link TỚI bài cụm phù hợp trong "Bài viết đã có trong chuyên mục" (§4.6) nếu
> có; `cluster` → giữ bài HẸP, đề xuất link LÊN bài tổng quan + NGANG (sibling) tới bài cụm liên
> quan nếu có trong danh sách đó. `content_role = null` (mặc định) → hành vi giữ nguyên như
> trước, backward-compatible.

> **v1.5 (đối chiếu healthconditionguide.com/content-outline — 4 điểm ÁP DỤNG, xem §4.8):**
> nguồn khớp sát phạm vi module (SEO content outline chuyên biệt, không phải course/checklist
> tổng quát như 2 nguồn trước). 4 điểm THẬT chưa có, đã thêm vào CẢ 3 `outline_depth`: (1)
> **USP** — bước mới "Xác định USP" (vì sao đọc bài NÀY thay vì các bài đã research), khác
> `unique_angle` (đó là góc nhìn CHUYÊN MỤC bền vững, USP là lý do cạnh tranh cho ĐÚNG outline
> này dựa trên research thật) — thêm mục `## USP` vào định dạng output. (2) **Ước tính khối
> lượng tìm kiếm/độ khó từ khoá** — thêm vào bước Research, chỉ khi AI ước tính được (không bắt
> buộc). (3) **Benchmark độ sâu cấu trúc theo đối thủ** (`standard`/`detailed`) — số H2/H3 nên
> BẰNG hoặc NHIỀU HƠN mốc quan sát được từ top-ranking pages ở bước Research/Đánh giá độ khó
> cạnh tranh. (4) **Named flow pattern cho H2** — thay "thứ tự logic" chung bằng lựa chọn rõ 1
> trong 4 kiểu (từng bước theo thời gian/giải quyết vấn đề/nguyên nhân-kết quả/tổng quát→cụ thể);
> **mở rộng self-check** — thêm kiểm tra từ khoá ở tiêu đề+H2, bao quát chủ đề phụ từ Bước 1, và
> đúng trình tự đã chọn. Không đổi DB schema/signature Action.

> **v1.4 (đối chiếu creatorlms.net/how-to-create-a-course-outline — KHÔNG áp dụng gì, khác loại
> nội dung):** nguồn là hướng dẫn tạo dàn ý **khoá học e-learning** (LMS) — cấu trúc
> module→lesson→quiz, learning objectives SMART, ước tính thời lượng video/bài tập, drip-feed
> theo tuần, 8 loại khoá học (mini/presell/certification/workshop/onboarding/drip-fed/challenge/
> bonus). KHÔNG áp dụng vì 2 lý do: (1) **sai loại nội dung** — module này sinh outline cho BÀI
> VIẾT (blog/SEO, gắn `Modules\Post`), không phải khoá học; không có khái niệm module/lesson/quiz
> nào tương đương trong domain bài viết. (2) **không có module Course/LMS nào trong codebase**
> (rà toàn bộ 29+ module hiện có) để các khái niệm course-specific (SMART objectives, quiz
> points, chứng chỉ, lịch phát hành theo tuần) có nơi áp dụng — thêm vào sẽ là tính năng không có
> nơi dùng, đúng loại rủi ro "framework flourish" đã từ chối nhiều lần ở `CoreIdeaExtractor.md`.
> Vài ý tưởng CHUYỂN ĐƯỢC hoá ra đã có sẵn dưới hình thức khác: "learning objectives rõ ràng" ≈
> "Luận điểm chính" (§4.7, v1.3); "ước tính thời lượng mỗi phần" ≈ "Ước lượng số từ mỗi phần"
> (`outline_depth=detailed`, §4.1); "tránh lỗi phổ biến/bỏ qua đánh giá" ≈ bước tự rà lại
> (self-check, từ v1.0). Không đổi code/DB — bản ghi thuần để không phải xem lại nguồn này lần sau.

> **v1.3 (đối chiếu hypotenuse.ai/blog/how-to-write-an-outline-in-4-simple-steps — 3 điểm ÁP
> DỤNG, xem §4.7):** nguồn là bài hướng dẫn viết outline TỔNG QUÁT (không SEO-specific, khác 4
> nguồn đã dẫn ở §0) — hầu hết nội dung (dàn ý chính thức/không chính thức, FAQ chung về mức độ
> chi tiết) đã được đáp ứng bởi thiết kế hiện tại (`outline_depth` §4.1 chính là cơ chế "chi
> tiết bao nhiêu tuỳ loại nội dung" mà bài này khuyến nghị). 3 điểm THẬT chưa có, đã thêm: (1)
> **Luận điểm chính (thesis)** — thêm 1 bước "Viết luận điểm chính" + mục `## Luận điểm chính`
> trong định dạng output ở CẢ 3 `outline_depth`, yêu cầu 1-2 câu tóm gọn thông điệp toàn bài để
> các H2 không rời rạc. (2) **Số điểm hỗ trợ/H2** — bước "Dựng cấu trúc H2/H3" (standard/detailed)
> thêm ràng buộc rõ "3-5 điểm/H3 hỗ trợ mỗi H2" (brief giữ "2-3" để khớp tinh thần rút gọn, không
> áp cứng số của bản đầy đủ). (3) **Cụm từ song song (parallel phrasing)** — thêm yêu cầu các
> heading CÙNG CẤP dùng cùng dạng ngữ pháp. Không đổi DB schema, không đổi tham số/signature các
> Action — chỉ sửa nội dung văn bản trong `BuildContentOutlinePromptAction::buildBottomBrief()`/
> `buildBottomStandard()`/`buildBottomDetailed()`.

> **v1.2 (đối chiếu piperocket.digital/checklists/content-marketing-checklist — 2/6 mục ÁP DỤNG,
> phần còn lại ngoài phạm vi, xem §4.6):** checklist này là quy trình content-marketing TỔNG THỂ
> (pipeline/ROI/CRM/team ownership) — 4/6 mục (Strategy & Goals, Planning & Calendar,
> Measurement & ROI, Team & Process) thuộc tầng lập kế hoạch/đo lường, không phải tầng "sinh 1
> outline cho 1 chủ đề đã chọn" mà module này phục vụ — không áp dụng. 2 mục ÁP DỤNG THẬT: (1)
> "Build an internal-linking plan that connects new content to existing pages" (Distribution &
> Promotion) — module trước đây chỉ gợi ý LOẠI nguồn chung cho internal link, không biết bài THẬT
> nào đã có trên platform; đã tái dùng `ListCategoryExistingArticlesAction` (có sẵn trong
> `ContentFoundation`, cùng cơ chế `CoreIdeaExtractor` §12.8 dùng để tránh trùng ý tưởng) để đưa
> tối đa N tiêu đề bài đã publish cùng chuyên mục vào MIDDLE, giới hạn theo `outline_depth` (5/10/20)
> cùng nguyên tắc `COMPETITOR_URL_LIMITS`. (2) "Assign a subject-matter expert or reviewer for
> technical accuracy" (Production Workflow) — thêm 1 dòng "Lưu ý EEAT" vào cả 3 biến thể BOTTOM,
> yêu cầu AI tự đánh dấu khi chủ đề cần chuyên gia/nguồn uy tín rà soát (y tế/pháp lý/tài chính/an
> toàn trẻ em) — không thêm field input mới, AI tự đánh giá theo chủ đề. Không đổi DB schema.

> **v1.1 (vá 5 rủi ro phát hiện sau review v1.0 — xem §4.1-§4.5):**
> (1) **Prompt length** — thêm `outline_depth` (brief/standard/detailed, cột mới trên
> `content_outlines`) cắt ngắn field foundation + giới hạn số URL tham khảo + đổi độ chi tiết
> 9 bước ở `BuildContentOutlinePromptAction`; thêm `estimateWordCount()` (PHP, dùng ở
> `show.blade.php` — cảnh báo khi > 6.000 từ) + ước lượng tương đương ở client
> (`content-outlines.js`, hiện ngay trong form trước khi submit). (2) **Regenerate** — chốt rõ
> 3 hành vi: `linked_post_article_id` GIỮ NGUYÊN (không có trong mảng update của
> `RegenerateContentOutlinePromptAction`), `updated_by`/`updated_at` LUÔN cập nhật, thêm confirm
> dialog ở `edit.blade.php` trước khi submit. (3) **Xoá dữ liệu** — modal xác nhận xoá hiện RÕ
> label + topic của outline (không còn câu chung "dàn ý này"), soft-delete để mở cho v1.2 nếu
> phản hồi thật đòi hỏi. (4) **Liên kết bài viết 1-1** — ghi rõ hạn chế này vào §9 "Ngoài phạm
> vi". (5) **UX trang show** — thêm toggle "Prompt thô"/"Xem trước Markdown"
> (`Str::markdown()`, không thêm thư viện JS), nút "Download .md", section "## " tự gom thành
> `<details>` collapsible ở bản xem trước.

---

## 0. Quyết định đã chốt

Đã hỏi người dùng 3 câu hỏi quyết định phạm vi trước khi viết spec v1.0 — chốt lại:

1. **KHÔNG gọi AI Provider nào trong app.** Module chỉ sinh ra 1 đoạn **prompt văn bản** (Markdown) hướng dẫn AI (ChatGPT/Claude/Gemini bên ngoài, do người dùng tự dán vào) research và tạo content outline — khác `CoreIdeaExtractor`/`VideoIdeaExtractor` (có gọi `app/Services/AI/` qua nút "Chạy AI trong app"). Vì vậy: không cần `AIProviderManager`, không cần `AIRequestOptions`/`responseSchema`, không cần theo dõi chi phí/hạn mức AI, không cần Gate chi phí kiểu `CheckCoreIdeaAiBudgetAction`. Đây là điểm khác biệt kiến trúc CHÍNH so với `CoreIdeaExtractor` dù cùng nhóm "công cụ nghiên cứu nội dung".
2. **CÓ lưu lại vào DB.** Mỗi lần "sinh dàn ý" tạo 1 bản ghi `content_outlines` (input đã nhập + prompt đã sinh) — xem lại, sửa, sinh lại, và (tuỳ chọn) gắn vào 1 `PostArticle` sau khi bài viết thật được viết dựa theo dàn ý đó.
3. **Tái dùng `Modules\ContentFoundation`.** Chọn 1 `PostCategory` (không bắt buộc) để kéo ngữ cảnh biên tập bền vững (`core_focus`/`pain_points`/`objections`/`decision_criteria`/`style_sample`/`family_values_focus`...) vào prompt — cùng cơ chế `CoreIdeaExtractor`/`VideoIdeaExtractor` đã dùng (`ListCategoryFoundationsAction` bản rút gọn cho picker + `CategoryFoundationController::show()` fetch full detail on-demand khi chọn category).

**Vì sao KHÔNG chọn thiết kế "1 prompt build client-side dùng chung cho 2 đường" như `CoreIdeaExtractor`**: `CoreIdeaExtractor` cần 2 đường (structured JSON cho "Chạy AI" + Markdown cho "Copy prompt") nên buộc phải build prompt ở JS để đường "Copy" không cần round-trip server. Module này CHỈ có 1 đường (copy ra ngoài) — build prompt ở **PHP Action** (`BuildContentOutlinePromptAction`) đơn giản hơn, dễ test hơn JS, và bắt buộc phải có bản PHP để LƯU được `generated_prompt` vào DB (quyết định 2) — không có lý do giữ 2 bản logic (JS + PHP) như `CoreIdeaExtractor` khi chỉ cần 1.

**Nguồn tham khảo chuẩn hoá nội dung outline** (người dùng cung cấp, xem §3.2 áp dụng cụ thể):
- semrush.com/blog/content-outline — định nghĩa + 5 bước tạo outline (research → xác định điểm chính → tổ chức heading → tinh chỉnh → thêm ghi chú).
- egcreativecontent.com/importance-of-seo-content-outlines — 3 thành phần cốt lõi (câu trả lời rõ ràng sau mỗi subheading, danh sách/bảng, internal+external link) + quy trình chuẩn bị (audience/keyword/search intent/câu hỏi liên quan).
- blog.hubspot.com/marketing/how-to-write-blog-post-outline — 7 bước + cấu trúc outline mẫu (H1 chứa keyword, H2/H3 theo intent, mỗi heading kèm bullet/ví dụ/thống kê/internal link/CTA, EEAT).
- LinkedIn — "13-step SEO content writing framework" (Rahul Karle) — đặc biệt Bước 1 (câu hỏi thật trước keyword), Bước 2 (research đối thủ tìm khoảng trống), Bước 8 (trả lời câu hỏi chính SỚM), Bước 12 ("information gain" — thêm thứ độc giả không tìm thấy ở nơi khác).
- writerush.ai/serp-based-outline-creation (v1.12, §4.15) — quy trình "SERP-based outline creation" chuyên biệt: kiểm kê SERP feature (featured snippet/PAA/tìm kiếm liên quan/video/local pack/product panel), gom nhóm heading lặp lại giữa nhiều trang đối thủ, khớp định dạng câu trả lời với định dạng featured snippet đang thắng.

---

## 1. Bối cảnh & Mục tiêu

Biên tập viên (`platform_content_editor`/`platform_content_head`/`platform_section_editor`) trước khi viết 1 bài cần 1 dàn ý (outline) có cấu trúc — hiện đang tự làm tay hoặc tự soạn prompt mỗi lần. Module `ContentOutlines` chuẩn hoá việc soạn prompt research/outline này thành 1 form nhập liệu có cấu trúc, tái dùng ngữ cảnh chuyên mục đã có (Category Content Foundation), sinh ra 1 đoạn prompt đầy đủ để dán sang AI ngoài, và lưu lại thành lịch sử để tra cứu/sửa/tái sử dụng.

**Phi mục tiêu (v1):**
- Không gọi AI Provider nào — không có "Chạy AI trong app" (§0 mục 1).
- Không tự research/crawl SERP hay fetch URL đối thủ (khác `CoreIdeaExtractor` Layer 1 fetch-by-URL) — việc research là VIỆC CỦA AI được dán prompt vào, module chỉ soạn CHỈ DẪN research, `competitor_urls` (§2.1) chỉ là danh sách text cho AI tự tham khảo/duyệt, không tự fetch nội dung các URL đó.
- Không tự động tạo `PostArticle` từ outline — "gắn vào bài viết" (§2.1 `linked_post_article_id`) chỉ là 1 liên kết tham chiếu thủ công, không sinh nội dung/copy dữ liệu sang `PostArticle`.
- Không versioning nhiều bản outline cho cùng 1 chủ đề — sửa 1 outline chỉ có 1 bản hiện tại (ghi đè `generated_prompt` khi sửa/sinh lại), không giữ lịch sử các lần sửa trước.

---

## 2. Kiến trúc dữ liệu

### 2.1 `content_outlines`

Model `ContentOutline` — **KHÔNG** `extends TenantAwareModel`, **KHÔNG** `organization_id` — cùng nhóm "content tooling nền tảng" với `CategoryContentFoundation` (`content_foundations`, không tenant-scoped) và khác mọi model nghiệp vụ business-per-Organization. Lý do: đây là công cụ dùng CHUNG bởi đội biên tập trung ương, không phải dữ liệu 1 tổ chức khách hàng tự quản lý.

```php
Schema::create('content_outlines', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique(); // route key — cùng quy ước PostCategory::boot(), KHÔNG dùng HasUuids

    $table->string('label', 200); // tên gọi hiển thị trong danh sách lịch sử — mặc định = topic nếu người dùng bỏ trống (xem CreateContentOutlineAction)
    $table->string('topic', 300);
    $table->string('target_keyword', 150);
    $table->string('secondary_keywords', 500)->nullable(); // tự do, phân tách bằng dấu phẩy
    $table->string('search_intent', 20)->nullable(); // enum §3.2 — null = để AI tự xác định qua research
    $table->foreignId('post_category_id')->nullable()->constrained('post_categories')->nullOnDelete();

    // Override cho ĐÚNG outline này — KHÁC field cùng tên trên content_foundations (đó là ngữ
    // cảnh BỀN VỮNG theo category, đây là brief phiên làm việc — cùng phân biệt brief-vs-foundation
    // đã áp dụng ở CoreIdeaExtractor §12.2/index.blade.php `brief.audience || foundation?.audience`).
    $table->string('target_audience', 500)->nullable();
    $table->text('content_goal')->nullable();

    // §4.18 (v1.15) — URL CTA THẬT (khác content_goal — chỉ định hướng LOẠI CTA chung), dùng để
    // nhúng thẳng vào câu chuyển tiếp cuối outline (bước CTA) + cuối bài viết (ArticleDrafting).
    // Migration RIÊNG, thêm SAU khi bảng đã tồn tại.
    $table->string('cta_url', 500)->nullable();

    $table->text('tone_style')->nullable();

    $table->text('competitor_urls')->nullable(); // 1 URL/dòng, tự do — KHÔNG validate url() từng dòng (§8.1 lý do)
    $table->unsignedInteger('desired_word_count')->nullable();
    $table->string('language', 5)->default('vi'); // 'vi' | 'en' — xem §3.2

    // §4.1 (v1.1) — brief|standard|detailed, kiểm soát độ dài prompt (migration RIÊNG, thêm
    // SAU khi bảng đã tồn tại — v1.0 không có cột này).
    $table->string('outline_depth', 10)->default('standard');

    // §4.9 (v1.6) — pillar|cluster|null, định hướng CHIỀU internal link (migration RIÊNG, thêm
    // SAU cả outline_depth).
    $table->string('content_role', 10)->nullable();

    $table->text('additional_notes')->nullable();

    $table->longText('generated_prompt'); // snapshot prompt đã sinh — ghi đè khi sinh lại (§0 mục "phi mục tiêu")

    // §4.17 (v1.14) — Feature ArticleDrafting ("Bước 2"). approved_outline: biên tập viên dán tay
    // outline THẬT nhận về từ AI ngoài sau khi chạy generated_prompt (module không tự lưu được kết
    // quả AI trả về ở "Bước 1" vì AI chạy NGOÀI app, §0 mục 1/2 — phải dán lại). article_draft_prompt:
    // snapshot prompt viết bài đã sinh (nhúng SẴN approved_outline) — ghi đè khi sinh lại, CÙNG
    // nguyên tắc không-versioning với generated_prompt. Migration RIÊNG, thêm SAU khi bảng đã tồn tại.
    $table->longText('approved_outline')->nullable();
    $table->longText('article_draft_prompt')->nullable();

    // §4.20 (v1.16) — Feature ArticleReview ("Bước 3"). drafted_article: biên tập viên dán bài
    // viết ĐÃ VIẾT XONG (từ AI ngoài chạy article_draft_prompt, hoặc viết tay). review_prompt:
    // snapshot prompt soát lỗi/sửa đã sinh — ghi đè khi sinh lại, cùng nguyên tắc không-versioning.
    $table->longText('drafted_article')->nullable();
    $table->longText('review_prompt')->nullable();

    $table->foreignId('linked_post_article_id')->nullable()->constrained('post_articles')->nullOnDelete();

    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();

    $table->index('post_category_id');
    $table->index('created_at'); // trang danh sách sort theo thời gian
});
```

**Không soft-delete (v1)** — cùng nguyên tắc `content_foundations`: đây là tài liệu làm việc cá nhân/nhóm biên tập, xoá là xoá thật, không cần audit trail nghiệp vụ. §4.3/§9 ghi rõ đây là quyết định CÓ Ý THỨC về rủi ro (không phải bỏ sót) — để mở cho v1.2 nếu phản hồi thật đòi hỏi.

**Không Activity Log riêng** — khác `N8nConnection`/`PostCategory`, giá trị field ở đây không nhạy cảm (không phải credential) và không có yêu cầu audit thay đổi; đây là tool năng suất cá nhân, không phải tài sản nghiệp vụ cần theo dõi lịch sử sửa đổi.

**Quyền truy cập bản ghi: KHÔNG theo owner.** Bất kỳ ai có `content_outlines.use` xem/sửa/xoá được MỌI outline (không chỉ outline do chính mình tạo) — cùng mô hình "tài sản chia sẻ của đội biên tập" như `CategoryContentFoundation` (không owner-based ACL), tránh phức tạp hoá Policy cho 1 tool năng suất nội bộ nơi cả đội cần thấy nhau đang nghiên cứu gì để tránh trùng việc. §4.3 ghi rõ rủi ro đi kèm quyết định này (xoá nhầm tài liệu người khác) + biện pháp giảm thiểu ở UI (modal xác nhận hiện rõ label/topic).

### 2.2 Quan hệ với `ContentFoundation`/`Post`

- `post_category_id` → `Modules\Post\Models\PostCategory` (nullable — outline không BẮT BUỘC gắn 1 chuyên mục, VD nghiên cứu chủ đề mới chưa có category phù hợp).
- `linked_post_article_id` → `Modules\Post\Models\PostArticle` (nullable — gắn SAU, qua action riêng §5.3, không set lúc tạo). **1-1** — xem §4.4/§9.
- Ngữ cảnh `CategoryContentFoundation` KHÔNG lưu bản sao vào `content_outlines` — mỗi lần sinh/sinh lại prompt, đọc TRỰC TIẾP từ `content_foundations` tại thời điểm đó (giống cách "brief" của `CoreIdeaExtractor` không tự đông bộ theo foundation sau khi đã prefill — nếu foundation đổi SAU khi outline đã tạo, phải bấm "Sinh lại" để lấy ngữ cảnh mới, không tự động).

---

## 3. `BuildContentOutlinePromptAction` — logic sinh prompt

### 3.1 Input/Output

```php
namespace Modules\ContentOutlines\Features\OutlineGeneration\Actions;

class BuildContentOutlinePromptAction
{
    use AsAction;

    /** §4.1 (v1.1) — ngưỡng cảnh báo "soft warning" ở UI, KHÔNG chặn tạo/sinh lại. */
    public const WORD_COUNT_WARNING_THRESHOLD = 6000;

    /**
     * @param  ContentOutlineInputData  $input  Dữ liệu đã validate từ form (§8.1), bao gồm outline_depth
     * @param  \Modules\ContentFoundation\Models\CategoryContentFoundation|null  $foundation  Đã tra theo
     *   post_category_id (nếu có) TRƯỚC khi gọi Action này — Action không tự query, nhận thẳng model
     *   (dễ test, không phụ thuộc DB trong unit test).
     * @return string  Prompt hoàn chỉnh (Markdown), sẵn sàng lưu vào content_outlines.generated_prompt
     */
    public function handle(ContentOutlineInputData $input, ?CategoryContentFoundation $foundation): string

    /** §4.1 (v1.1) — đếm từ theo khoảng trắng Unicode (an toàn với tiếng Việt có dấu). */
    public static function estimateWordCount(string $text): int
}
```

### 3.2 Cấu trúc prompt — TOP → MIDDLE → BOTTOM (cùng quy ước `CoreIdeaExtractor::buildLayer2PromptText()`)

**TOP — Persona + Brief:**
- 1 câu persona: *"Bạn là chuyên gia SEO Content Strategist giàu kinh nghiệm, có khả năng research SERP thực tế (dùng web search nếu công cụ của bạn hỗ trợ) và tổng hợp thành content outline chi tiết, sẵn sàng để biên tập viên triển khai thành bài viết hoàn chỉnh."*
- Bảng brief: Chủ đề (`topic`), Từ khoá mục tiêu (`target_keyword`), Từ khoá phụ (`secondary_keywords`, bỏ dòng nếu rỗng), Ý định tìm kiếm (`search_intent` — nếu null: *"chưa xác định — bạn tự xác định qua research và NÊU RÕ lựa chọn ở đầu outline"*), Đối tượng độc giả (`target_audience`, ưu tiên brief, fallback `foundation?->audience`), Mục tiêu bài viết (`content_goal`, ưu tiên brief, fallback `foundation?->content_goals`), Giọng văn (`tone_style`, ưu tiên brief, fallback `foundation?->style_sample`), Số từ mong muốn (`desired_word_count`, bỏ dòng nếu null: *"không giới hạn cứng — bạn tự đề xuất độ dài hợp lý theo độ phức tạp chủ đề"*), Ngôn ngữ đầu ra (`language`: `vi` → *"Tiếng Việt"*, `en` → *"English"*), Nguồn/đối thủ tham khảo (`competitor_urls`, liệt kê từng dòng — **§4.1 (v1.1): giới hạn số dòng theo `outline_depth`** (brief 3 / standard 8 / detailed 20, hằng số `COMPETITOR_URL_LIMITS`), phần bị cắt ghi rõ số lượng đã bỏ qua — kèm câu: *"Nếu công cụ của bạn không truy cập được các URL này, hãy research chủ đề tương tự qua tri thức/khả năng tìm kiếm của bạn."*), Ghi chú thêm (`additional_notes`, bỏ dòng nếu rỗng).

**MIDDLE — Ngữ cảnh chuyên mục (chỉ khi có `$foundation`):**
- `core_focus`/`unique_angle` — trọng tâm & góc nhìn riêng của chuyên mục.
- `pain_points` → kèm gợi ý: *"ưu tiên định dạng hướng dẫn/checklist cho phần chạm tới các khó khăn này"*.
- `objections` → kèm gợi ý: *"đưa các nghi ngờ này vào khối FAQ dưới dạng bóc trần ngộ nhận"*.
- `decision_criteria` → kèm gợi ý: *"1 phần so sánh/tiêu chí lựa chọn nên phản ánh đúng các tiêu chí này"*.
- `rejected_ideas` → *"outline KHÔNG trùng các góc độ đã dùng: ..."* (tránh lặp ý cũ, cùng vai trò Decision Log của `CoreIdeaExtractor` §12.7).
- **v1.25 — `product_service_docs`/`best_example_content`** (`CoreIdeaExtractor.md` §12.13) — tài liệu mô tả chi tiết sản phẩm/dịch vụ + ví dụ nội dung/dàn ý mẫu TỐT NHẤT đã có, đặt ngay sau `rejected_ideas`. Bọc delimiter `<<<TAI_LIEU_SAN_PHAM>>>`/`<<<VI_DU_NOI_DUNG_MAU>>>` + câu rào prompt-injection (khác các dòng khác trong khối này — văn bản dài, có thể dán nguyên văn từ nguồn khác).
- **§4.1 (v1.1) — MỖI field trên đều cắt ngắn theo ký tự (`mb_substr`) qua hằng số `FOUNDATION_FIELD_CHAR_LIMITS`** (brief 300 / standard 800 / detailed không giới hạn) TRƯỚC khi ghép vào prompt — đây là nguồn "phình prompt" LỚN NHẤT trong thực tế (foundation đầy đủ có thể tới ~2000 ký tự/field × 5 field), không phải phần BOTTOM cố định.
- `style_sample` — chỉ dùng làm fallback cho `tone_style` ở TOP (không lặp lại ở MIDDLE để tránh trùng).
- **Khối "Hệ giá trị gia đình Việt Nam"** — CỐ ĐỊNH, LUÔN chèn bất kể có chọn category hay không (đúng quy ước `CoreIdeaExtractor` §12.10) — đọc từ `config('content_foundation.family_values')`, không hardcode lặp lại. Nếu `foundation?->family_values_focus` có giá trị, thêm 1 dòng ưu tiên bổ sung. **KHÔNG cắt theo `outline_depth`** — đây là chuẩn nền tảng, không phải ngữ cảnh biên tập tự viết có thể rút gọn.

**BOTTOM — Quy trình, độ dài theo `outline_depth`** (tổng hợp 4 nguồn §0):

| `outline_depth` | Số bước | Đặc điểm |
|---|---|---|
| `brief` | 5 | Research nhanh (không yêu cầu 5-10 trang), FAQ chỉ 3 câu, bỏ bước "làm rõ mỗi heading"/internal-external link/self-check riêng — tối ưu prompt ngắn nhất. |
| `standard` (mặc định) | 9 | Đúng quy trình v1.0: research 5-10 trang → xác nhận intent → 2-3 tiêu đề → cấu trúc H2/H3 → làm rõ mỗi heading → FAQ 4-6 câu → Meta → internal/external link → self-check. |
| `detailed` | 11 | Thêm "đánh giá độ khó cạnh tranh" + yêu cầu ví dụ/số liệu CỤ THỂ cho MỖI bullet + FAQ 6-8 câu + "ước lượng số từ mỗi phần". |

**Định dạng output yêu cầu ở cuối prompt** — 1 khối Markdown, cấu trúc theo `outline_depth`:
- `brief`: `# [Chủ đề]` → `## Phương án tiêu đề` → `## Ý định tìm kiếm` → `## Meta` → `## Dàn ý` → `## FAQ`.
- `standard`: thêm `## Gợi ý Internal/External Link` sau `## FAQ`.
- `detailed`: thêm `## Đánh giá độ khó cạnh tranh` ngay sau `## Phương án tiêu đề`.

---

## 4. Rủi ro đã biết & biện pháp giảm thiểu (v1.1)

### 4.1 Prompt length (rủi ro trung bình-cao)

Khi `CategoryContentFoundation` đầy đủ + `competitor_urls` dài + `additional_notes` dài, prompt v1.0 dễ vượt 8-12k token — 1 số AI ngoài cắt hoặc giảm chất lượng phản hồi với input quá dài.

**Đã vá:**
- `outline_depth` (§2.1/§3.2) — cắt MIDDLE (field foundation, nguồn phình lớn nhất) + giới hạn số `competitor_urls` + đổi độ dài BOTTOM, theo 3 mức brief/standard/detailed.
- `BuildContentOutlinePromptAction::estimateWordCount()` (PHP, đếm chính xác trên prompt THẬT đã sinh) → `show.blade.php` hiện banner cảnh báo khi > `WORD_COUNT_WARNING_THRESHOLD` (6.000 từ) — **soft warning, KHÔNG chặn** tạo/sinh lại (người dùng có thể có lý do chính đáng để giữ prompt dài, VD dán cho AI có context window lớn).
- Ước lượng TƯƠNG ĐƯƠNG (không chính xác tuyệt đối, chỉ để cảnh báo SỚM) ở client (`content-outlines.js`, Alpine `contentOutlineForm.estimatedWordCount`) — hiện ngay cạnh nút submit TRƯỚC KHI gửi request, tính từ field đang gõ + `foundation` đã fetch + baseline cố định theo depth.
- Tabulator danh sách (`ContentOutlineListResource.is_long_prompt`) gắn badge "dài" cho outline đã vượt ngưỡng, giúp nhận diện nhanh khi duyệt danh sách.

### 4.2 Hành vi Regenerate — 3 điểm chốt rõ

- **`linked_post_article_id` GIỮ NGUYÊN** khi "Sinh lại" — `RegenerateContentOutlinePromptAction::handle()` CỐ Ý không đưa field này vào mảng `update()`, nên Eloquent không đụng tới giá trị hiện tại. Lý do: sinh lại prompt là thao tác trên NỘI DUNG nghiên cứu, không liên quan gì tới việc outline đã được gắn vào bài viết nào.
- **`approved_outline`/`article_draft_prompt`/`drafted_article`/`review_prompt` GIỮ NGUYÊN** khi "Sinh lại" outline (v1.20/v1.21) — CÙNG lý do/CÙNG cơ chế với `linked_post_article_id`: `RegenerateContentOutlinePromptAction::handle()` CỐ Ý không đưa 4 field này (thuộc "Bước 2"/"Bước 3", §4.17/§4.20) vào mảng `update()`. **Hệ quả CHỦ Ý cần biết:** outline MỚI sau khi sinh lại có thể KHÔNG còn khớp với nội dung "Bước 2"/"Bước 3" đã sinh dựa trên outline CŨ trước đó — module KHÔNG tự động xoá/cập nhật lại 2 bước đó, biên tập viên phải TỰ quyết định sinh lại Bước 2/3 nếu cần (không có cơ chế tự động phát hiện "outline đã đổi từ lúc sinh Bước 2/3" — không lưu snapshot/timestamp riêng cho từng bước để so sánh). Được CẢNH BÁO ở tầng UI (không phải ở Action) — xem bullet "Confirm dialog" ngay dưới.
- **`updated_by`/`updated_at` LUÔN cập nhật** — `updated_by` set thủ công trong mảng update(), `updated_at` tự động qua Eloquent timestamps ở MỌI lần gọi `update()` (kể cả khi nội dung field không đổi thực chất).
- **Confirm dialog trước khi submit** — `edit.blade.php` gắn `data-confirm-regenerate="<message>"` trên `<form>` (v1.20 — ĐỘNG, không còn cố định `"1"`): message MẶC ĐỊNH (chỉ cảnh báo ghi đè `generated_prompt`, không thể khôi phục) khi outline CHƯA có Bước 2/3; đổi sang message CẢNH BÁO CASCADE (nêu rõ Bước 2/3 đã có sẽ không tự cập nhật theo outline mới) khi `approved_outline`/`article_draft_prompt`/`drafted_article`/`review_prompt` bất kỳ field nào đã có giá trị — `content-outlines.js` chặn submit bằng `window.confirm()` nếu người dùng không xác nhận (§1, §4.24).

### 4.3 Xoá dữ liệu (rủi ro trung bình)

Vì §2.1 chọn KHÔNG soft-delete + KHÔNG owner-based ACL (ai có `content_outlines.use` cũng xoá được MỌI outline) → rủi ro xoá nhầm tài liệu nghiên cứu của đồng nghiệp, mất vĩnh viễn.

**Đã vá (v1.1, trong phạm vi UI, KHÔNG đổi §2.1):** modal xác nhận xoá (`index.blade.php`) hiện RÕ **label + topic** của outline sắp xoá (đọc từ `data-label`/`data-topic` gắn vào nút Xoá bởi Tabulator formatter) — không còn câu chung "Bạn có chắc muốn xoá dàn ý này?" mà người xoá phải đọc đúng tên trước khi bấm xác nhận.

**Chưa vá, để mở cho v1.2 nếu team phản hồi cần:** soft-delete (`deleted_at` + trang "đã xoá" để khôi phục) — KHÔNG làm ngay ở v1.1 vì chưa có phản hồi thật cho thấy xoá-nhầm đã xảy ra; thêm ngay bây giờ là suy đoán thiếu bằng chứng (cùng nguyên tắc "không thêm engineering khi chưa có bằng chứng cần" đã áp dụng nhiều lần ở `CoreIdeaExtractor.md`).

### 4.4 Liên kết bài viết chỉ 1-1

`linked_post_article_id` chỉ cho phép gắn ĐÚNG 1 `PostArticle` tại 1 thời điểm. Trong thực tế, 1 dàn ý tốt có thể được dùng làm gốc cho nhiều biến thể bài viết (VD viết lại theo 2 giọng văn khác nhau cho 2 kênh). **Chấp nhận được cho v1** — ghi rõ vào §9 "Ngoài phạm vi", không mở rộng thành quan hệ N-N ở v1.1 vì chưa có nhu cầu thật cụ thể.

### 4.5 UX trang Show

`generated_prompt` là Markdown dài — v1.0 chỉ có `<textarea readonly>` + nút Copy, khá thô để đọc lại.

**Đã vá:**
- Toggle "Prompt thô" (textarea, dùng để Copy) / "Xem trước Markdown" (`Str::markdown()` — `league/commonmark` đã có sẵn trong vendor qua dependency Laravel, KHÔNG thêm thư viện JS mới).
- Nút "Download .md" — client-side `Blob`, không round-trip server (nội dung đã có sẵn trong DOM).
- Ở bản "Xem trước", mỗi khối `## ` được JS (`contentOutlineMakeCollapsible()`) gom cùng nội dung theo sau vào 1 `<details open>` — không cần server tách khối TOP/MIDDLE/BOTTOM tường minh (cấu trúc "## " sẵn có trong prompt đã đủ để nhóm).

### 4.6 Internal link thật + EEAT (v1.2, đối chiếu piperocket.digital/checklists/content-marketing-checklist)

Nguồn ngoài (checklist content-marketing tổng thể, không chuyên biệt cho outline) chỉ có 2 mục THẬT áp dụng được cho phạm vi module này — 4 mục còn lại (chiến lược/lịch nội dung/ROI/team) thuộc tầng lập kế hoạch, ngoài phạm vi "sinh outline cho 1 chủ đề đã chọn" (xem changelog v1.2 ở đầu file cho lý do đầy đủ từng mục bị từ chối).

**(1) Internal link THẬT, không còn chỉ gợi ý "loại nguồn chung":**
- `ResolvesCategoryContext::resolveExistingArticleTitles()` (đổi tên từ `ResolvesCategoryFoundation`) tái dùng NGUYÊN `Modules\ContentFoundation\Actions\ListCategoryExistingArticlesAction` — tiêu đề bài ĐÃ PUBLISH cùng `post_category_id`, cùng cơ chế `CoreIdeaExtractor` §12.8 dùng để tránh trùng ý tưởng.
- `BuildContentOutlinePromptAction::handle()` thêm tham số thứ 3 `array $existingArticleTitles = []` — MIDDLE thêm khối "## Bài viết đã có trong chuyên mục" (chỉ khi không rỗng), giới hạn số lượng theo `outline_depth` qua `EXISTING_ARTICLE_TITLE_LIMITS` (brief 5 / standard 10 / detailed 20) — CÙNG nguyên tắc `COMPETITOR_URL_LIMITS` (§4.1), không để category nhiều bài làm phình prompt.
- Vẫn giữ nguyên tắc "KHÔNG bịa URL" — model chỉ nhận TÊN bài, không phải URL; biên tập viên tự tìm đúng URL khi ráp internal link thật vào bài mới.
- Vai trò KHÁC `rejected_ideas` (đã có ở MIDDLE): `rejected_ideas` là ý tưởng đã bị LOẠI TRƯỚC KHI VIẾT (Decision Log), khối mới này là bài THẬT đã XUẤT BẢN — 2 nguồn khác nhau, cùng mục tiêu "đừng viết trùng".

**(2) EEAT — chuyên gia rà soát khi cần độ chính xác cao:**
- Thêm 1 dòng "**Lưu ý EEAT:**..." vào CẢ 3 biến thể BOTTOM (`buildBottomBrief()`/`buildBottomStandard()`/`buildBottomDetailed()`) — yêu cầu AI tự đánh dấu khi chủ đề cần độ chính xác cao (y tế/pháp lý/tài chính/an toàn trẻ em) và gợi ý loại chuyên gia/nguồn nên rà soát trước khi publish.
- KHÔNG thêm field input mới — AI tự đánh giá dựa trên `topic`/`target_keyword` đã có, giữ đúng nguyên tắc "không thêm gánh nặng nhập liệu khi có thể suy luận được từ dữ liệu đã có" đã áp dụng xuyên suốt module.

### 4.7 Luận điểm chính + cấu trúc heading chặt hơn (v1.3, đối chiếu hypotenuse.ai/blog/how-to-write-an-outline-in-4-simple-steps)

Nguồn là hướng dẫn viết outline TỔNG QUÁT (không SEO-specific) — phần lớn nội dung (dàn ý chính thức/không chính thức, "nên chi tiết đến đâu") đã được đáp ứng bởi `outline_depth` (§4.1). 3 điểm THẬT chưa có, đã thêm — chỉ sửa nội dung văn bản BOTTOM, KHÔNG đổi signature/DB:

**(1) Luận điểm chính (thesis):**
- Bài dẫn: mẫu outline luôn có "Tuyên bố luận đề/lập luận chính" ngay sau phần giới thiệu — thiếu bước này khiến các H2 dễ viết rời rạc, không cùng phục vụ 1 thông điệp.
- Thêm bước "**Viết luận điểm chính (thesis)**" (đặt SAU bước xác nhận ý định tìm kiếm, TRƯỚC bước đề xuất tiêu đề/dựng H2) + mục `## Luận điểm chính` trong định dạng output — yêu cầu 1-2 câu, đọc xong biết ngay bài giúp độc giả điều gì, và mọi H2 phải phục vụ đúng luận điểm này.
- Áp dụng CẢ 3 `outline_depth` (kể cả brief) — chi phí thêm rất nhỏ (1-2 câu) so với lợi ích mạch lạc.

**(2) Số điểm hỗ trợ/H2:**
- Bài dẫn khuyến nghị rõ 3-5 điểm hỗ trợ dưới mỗi chủ đề chính — trước đó module để AI tự quyết định số lượng, không có ràng buộc, dễ ra outline lúc thì 1 bullet/H2 (thiếu chiều sâu) lúc thì 10 bullet (loãng).
- Bước "Dựng cấu trúc H2/H3" ở `standard`/`detailed` thêm ràng buộc "mỗi H2 nên có 3-5 điểm/H3 hỗ trợ". Ở `brief` giữ **2-3** (không áp cứng số của bản đầy đủ — khớp tinh thần rút gọn của outline_depth=brief, §4.1).

**(3) Cụm từ song song (parallel phrasing):**
- Bài dẫn: các heading cùng cấp nên dùng cùng dạng ngữ pháp (VD cùng là câu hỏi, hoặc cùng bắt đầu bằng động từ) để dễ theo dõi — module trước đó không có chỉ dẫn này.
- Thêm vào bước "Dựng cấu trúc H2" (cả 3 depth): "các heading CÙNG CẤP dùng CÙNG dạng ngữ pháp... để dễ theo dõi."

**Không áp dụng từ nguồn này** (đã đáp ứng bởi thiết kế sẵn có hoặc không phù hợp domain SEO/blog): số La Mã/chữ cái cho outline "chính thức" (module chỉ sinh Markdown H1-H3, không cần định dạng academic); "kết luận/tóm tắt" riêng cho MỖI chủ đề chính (hợp với luận văn dài, không hợp blog SEO — sẽ làm outline dài dòng không cần thiết, khác mục tiêu "trả lời câu hỏi chính SỚM" đã có từ §0); nghiên cứu trước/sau khi dàn ý (đã đúng thứ tự ở BƯỚC 1 "Research" → cấu trúc, từ v1.0).

### 4.8 USP, ước tính từ khoá, benchmark cấu trúc, named flow pattern (v1.5, đối chiếu healthconditionguide.com/content-outline)

Nguồn khớp sát phạm vi module (SEO content outline chuyên biệt) — 4 điểm THẬT chưa có, thêm vào CẢ 3 `outline_depth`, không đổi signature/DB:

**(1) USP (Unique Selling Proposition):**
- Bước mới "**Xác định USP**" — 1-2 câu CỤ THỂ vì sao đọc bài NÀY thay vì các bài đã research (không chỉ nói chung "chất lượng hơn") — đặt SAU luận điểm chính, TRƯỚC đề xuất tiêu đề (tiêu đề phải phản ánh USP).
- KHÁC `unique_angle` (foundation, MIDDLE, §3.2): `unique_angle` là góc nhìn CHUYÊN MỤC bền vững (áp dụng cho MỌI outline thuộc category đó); USP là lý do cạnh tranh riêng cho ĐÚNG outline này, rút ra từ research thật (BƯỚC 1) + `competitor_urls` — 2 khái niệm bổ trợ, không trùng.
- Thêm mục `## USP` vào định dạng output, ngay sau `## Phương án tiêu đề`.

**(2) Ước tính khối lượng tìm kiếm/độ khó từ khoá:**
- Thêm vào cuối bước Research (cả 3 depth): "nếu ước tính được, ghi chú khối lượng tìm kiếm hàng tháng + độ khó từ khoá". KHÔNG bắt buộc — model không có web search thật thì bỏ qua, không bịa số liệu (nguyên tắc "không bịa dữ liệu" nhất quán với cách module xử lý URL/nguồn khác).

**(3) Benchmark độ sâu cấu trúc theo đối thủ (`standard`/`detailed`):**
- Bước Research (`standard`)/Đánh giá độ khó cạnh tranh (`detailed`) thêm ghi chú số H2/H3 + độ dài trung bình của top-ranking pages.
- Bước "Dựng cấu trúc H2/H3" thêm ràng buộc: số H2/H3 nên BẰNG hoặc NHIỀU HƠN mốc đó "nếu có đủ chất liệu để mở rộng có ý nghĩa" (không ép tăng số lượng máy móc khi không có nội dung thật để lấp).
- KHÔNG áp cho `brief` — mâu thuẫn với tinh thần rút gọn của depth đó (§4.1).

**(4) Named flow pattern cho H2 + self-check mở rộng:**
- Bước "Dựng cấu trúc H2" đổi "thứ tự logic từ cơ bản → nâng cao" (mơ hồ) thành yêu cầu CHỌN RÕ 1 trong 4 kiểu trình tự: từng bước theo thời gian / giải quyết vấn đề / nguyên nhân-kết quả / tổng quát → cụ thể — rồi giữ NHẤT QUÁN kiểu đã chọn.
- Self-check (`standard`/`detailed`) mở rộng thêm 3 kiểm tra cụ thể (khớp phần lớn checklist 7 điểm của nguồn): từ khoá mục tiêu có ở tiêu đề VÀ ≥1 H2; đã bao quát chủ đề phụ chính thấy ở bước Research chưa; luồng có ĐÚNG theo kiểu trình tự đã chọn không. `brief` giữ self-check gộp vào bước cuối (không mở rộng, giữ đúng tinh thần rút gọn).

### 4.9 Internal-link Pillar↔Cluster (v1.6, đối chiếu umbrex.com/.../content-pillars-topic-cluster-framework)

Nguồn là framework chương trình nội dung cấp SITE (12 bước: audit, chọn 3-7 pillar, đo ROI theo quý, PESO distribution, quản trị đội...) — hầu hết KHÔNG áp dụng, cùng lý do đã từ chối phần lớn checklist ở §4.6 (thuộc tầng lập kế hoạch/đo lường nhiều-bài-viết, khác tầng "sinh outline cho 1 bài đã chọn"). Nhưng mô hình internal-link Pillar↔Cluster áp dụng được Ở MỨC 1 BÀI:

- Field mới `content_role` (`pillar`/`cluster`/`null`) — người dùng tự khai báo bài NÀY đang đóng vai trò gì trong kiến trúc nội dung của chuyên mục, KHÔNG suy luận tự động (module không có cái nhìn toàn site để tự xếp loại).
- `pillar` (Trụ cột): outline nên cấu trúc như 1 hub tổng quan — mỗi H2 tương ứng 1 chủ đề hẹp hơn có thể tách thành 1 bài cụm riêng; nếu "Bài viết đã có trong chuyên mục" (§4.6, MIDDLE) có bài phù hợp làm cụm cho 1 H2, đề xuất link TỪ pillar TỚI đúng bài đó (parent→child link, theo đúng mô hình nguồn).
- `cluster` (Cụm): outline nên giữ HẸP, tập trung 1 câu hỏi cụ thể; đề xuất link LÊN 1 bài tổng quan rộng hơn nếu có trong danh sách đó (child→parent link), và link NGANG (sibling) tới 1 bài cụm khác liên quan nếu có.
- `null` (mặc định, không khai báo): hành vi giữ NGUYÊN như v1.0-v1.5, không có ghi chú vai trò nào — backward-compatible tuyệt đối, không ảnh hưởng outline đã tạo trước v1.6.
- Chỉ áp cho `standard`/`detailed` — `brief` không có bước internal/external link nào để gắn ghi chú vào (giữ đúng tinh thần tối giản của depth đó, §4.1); TOP vẫn hiển thị dòng "Vai trò nội dung" ở CẢ 3 depth (thông tin ngữ cảnh, không phụ thuộc có bước BOTTOM tương ứng hay không).
- Implementation: `BuildContentOutlinePromptAction::buildBottom()` nhận thêm `$contentRole`/`$hasExistingArticles`, chèn ghi chú qua placeholder `{{ROLE_LINK_NOTE}}` đặt sẵn trong nowdoc `standard`/`detailed` (nowdoc không hỗ trợ interpolation biến, nên dùng `str_replace()` sau khi lấy template thô — tránh phải chuyển nowdoc → heredoc có interpolation, rủi ro escape không cần thiết cho toàn bộ khối text).

### 4.10 CTA/bước tiếp theo + độ tin cậy dữ liệu (v1.7, đối chiếu 8bitcontent.com/content-quality-assessment-framework)

Nguồn là rubric CHẤM ĐIỂM nội dung ĐÃ PUBLISH (5 yếu tố × 0-3 điểm, quyết định xoá/viết lại/cập nhật/quảng bá/tái sử dụng) — bản chất là tool AUDIT hậu-publish, khác tầng "sinh outline trước khi viết". Cơ chế chấm điểm KHÔNG áp dụng. Đối chiếu 5 yếu tố: 3/5 đã có sẵn (Contextual Understanding ≈ thesis+FAQ+self-check §4.7/§0; Uniqueness & Value-Add ≈ USP+information gain §4.8; User Experience ≈ heading mô tả+hình ảnh/bảng đã có từ v1.0). 2/5 hé lộ khoảng trống NỘI DUNG thật, đã thêm CẢ 3 `outline_depth`, không đổi signature/DB:

**(1) Guidance (CTA/bước tiếp theo):**
- Thêm bước mới "**Gợi ý CTA/bước tiếp theo**" (đặt SAU bước internal/external link, TRƯỚC self-check ở `standard`/`detailed`; SAU FAQ ở `brief`) — 1 CTA cụ thể tuỳ `content_goal`/`search_intent` đã có ở TOP; nếu ý định tìm kiếm là informational, ưu tiên CTA hướng dẫn/đọc thêm, KHÔNG chèn CTA bán hàng cứng nhắc (tự nhiên tránh lỗi "nội dung không phù hợp intent" mà nhiều nguồn trước đã cảnh báo, VD §4.6 mục "Nội dung không phù hợp" của piperocket — CTA sai giai đoạn nhận thức là 1 dạng cụ thể của lỗi đó).
- Thêm mục `## CTA` vào định dạng output, vị trí CUỐI CÙNG (sau `FAQ` ở brief, sau `Gợi ý Internal/External Link` ở standard/detailed).

**(2) Credibility & Freshness (độ tin cậy dữ liệu):**
- Nối thêm vào dòng "Lưu ý EEAT" (không tách dòng riêng, tránh thêm 1 khối `**...:**` mới chỉ vì 1 câu ngắn): "**Độ tin cậy dữ liệu:** ưu tiên dữ liệu/nguồn trong khoảng 12 tháng gần nhất khi có thể; với số liệu/khẳng định quan trọng, gợi ý trích dẫn 2-3 nguồn uy tín khác nhau, không chỉ dựa vào 1 nguồn."
- KHÔNG bắt buộc AI tự bịa ngày tháng/nguồn nếu không biết — cùng nguyên tắc "không bịa dữ liệu" đã áp dụng cho ước tính khối lượng tìm kiếm (§4.8).

---

### 4.11 Answer-first, AI answer engine, chặn bịa số liệu, list lead-in, sai số ±10% (v1.8, đối chiếu advancedwebranking.com/blog/ai-generated-content-prompts-framework)

Nguồn là framework prompt-engineering cho AI content-gen: kiến trúc "Multi-Task Chain of Thought" 3 giai đoạn (Analysis → Structure → Execution) có approval gate giữa các giai đoạn, tổng hợp 7 loại tài liệu tham khảo (SERP, AI Overview, phản hồi 4 AI model, People Also Ask...), so sánh/hợp nhất kết quả từ Claude/Copilot/Gemini/ChatGPT thành 1 master brief, phân loại entity Essential/Optional. Đây là quy trình biên tập THỦ CÔNG chạy qua NHIỀU phiên chat AI riêng biệt có con người xét duyệt giữa các bước — khác hẳn model "sinh 1 prompt, người dùng tự copy sang 1 AI ngoài chạy 1 lần" đã chốt ở §0. KHÔNG áp dụng: kiến trúc approval-gate (đòi hỏi module tự điều phối nhiều lượt gọi AI + lưu trạng thái phê duyệt — ngoài phạm vi "không gọi AI Provider trong app"); tổng hợp 7 loại tài liệu tham khảo + so sánh 4 AI model (đòi hỏi tự crawl SERP/AI Overview hoặc tự gọi nhiều AI model — ngoài phạm vi "không tự research/crawl"); phân loại entity Essential/Optional (đòi hỏi thêm cấu trúc input mới, không đủ giá trị ở quy mô 1 bài viết đơn của module này). "Loại bỏ toàn bộ nhận xét sản xuất/scaffolding khỏi output cuối" đã được §0/v1.0 xử lý qua chỉ dẫn "không thêm lời dẫn/giải thích trước hoặc sau khối này" — không cần thêm.

4 chi tiết PROMPT-LEVEL tách được khỏi kiến trúc trên, áp dụng CẢ 3 `outline_depth` (trừ khi ghi chú riêng), không đổi signature/DB:

**(1) Answer-first (mỗi H2/H3 mở đầu bằng câu trả lời trực tiếp):**
- Bước "Dựng cấu trúc H2(/H3)" (cả 3 depth) thêm chỉ dẫn: mỗi heading nên MỞ ĐẦU bằng 1 (hoặc 1-2 ở `standard`/`detailed`) câu trả lời TRỰC TIẾP câu hỏi của chính heading đó rồi mới giải thích/mở rộng thêm — tối ưu cho featured snippet/AI answer engine trích dẫn. Khác chỉ dẫn "câu hỏi/ý chính trả lời SỚM" đã có từ v1.0 (áp dụng cho luận điểm TOÀN BÀI) — đây là bản thu nhỏ áp dụng CHO TỪNG heading riêng lẻ.

**(2) AI answer engine trong bước research:**
- Bước research (cả 3 depth) thêm 1 câu: ngoài SERP truyền thống, cũng lưu ý cách các AI answer engine (Google AI Overview, ChatGPT, Gemini...) hiện đang trả lời câu hỏi này, và outline có thể lấp khoảng trống nào để dễ được các answer engine đó trích dẫn — phản ánh thực tế 2026: xếp hạng ở "answer engine" ngày càng quan trọng ngang SERP cổ điển.

**(3) Chặn bịa số liệu:**
- Nối thêm vào dòng "Lưu ý EEAT/Độ tin cậy dữ liệu" (cùng nguyên tắc gộp dòng như §4.10, không tách khối mới): "**Không bịa số liệu:** nếu không chắc 1 số liệu/thống kê cụ thể, ghi rõ \"[cần biên tập viên xác minh]\" thay vì tạo ra số liệu không kiểm chứng được." Đây là guardrail CHUNG (mọi số liệu trong bài), khác các ghi chú "không bịa" trước đó chỉ áp dụng riêng cho khối lượng tìm kiếm (§4.8) hoặc URL/tên bài (§4.6/§4.9).

**(4) List lead-in + sai số ±10%:**
- Bước "Làm rõ nội dung mỗi heading" (chỉ có ở `standard`/`detailed` — `brief` không có bước này) thêm: nếu 1 phần dùng danh sách/bullet, PHẢI có 1 câu dẫn nhập nêu ngữ cảnh trước danh sách đó, không thả bullet trơ trọi ngay sau heading.
- Bước "Ước lượng số từ mỗi phần" (chỉ có ở `detailed`) thêm: chia ước lượng số từ cho từng H2 (kể cả phần mở đầu — trước đây chỉ nói "từng H2", không tính phần mở bài) sao cho tổng khớp mục tiêu, cho phép sai số ±10%.

---

### 4.12 Structure archetype, intent map 3 câu hỏi, Content-H3, differentiation note, FAQ nguồn PAA thật, anchor text (v1.9, đối chiếu aiexecutionhub.com/blog/ai-blog-post-outlining-system)

Nguồn là hệ thống outlining 4-bước TÁCH RỜI — Search Intent Map → Structure Selection → Section Briefs → H3 Logic — mỗi bước 1 prompt riêng, gọi AI 1 lần rồi người dùng tự dán kết quả sang prompt bước sau (có ví dụ prompt cụ thể cho cả 4 bước). Đây là quy trình biên tập THỦ CÔNG qua nhiều lượt gọi AI có con người chuyển tiếp dữ liệu giữa các bước — khác model "sinh 1 prompt, chạy 1 lần" đã chốt ở §0. Kiến trúc 4-bước tách rời với nhiều lượt gọi AI KHÔNG áp dụng (đòi hỏi module tự điều phối nhiều lượt gọi AI Provider + lưu trạng thái giữa các bước, ngoài phạm vi "không gọi AI Provider trong app"). "Word target mỗi section" (1 trong 4 thành phần Section Brief của nguồn) KHÔNG thêm bước mới vì trùng với bước "Ước lượng số từ mỗi phần" đã có ở `detailed` (§4.11) — không cần lặp lại.

6 chi tiết PROMPT-LEVEL tách được khỏi kiến trúc 4-bước trên, gộp CHUNG vào 1 prompt hiện có (không đổi signature/DB):

**(1) Chọn kiểu bài (structure archetype) — chỉ `standard`/`detailed`:**
- Thêm bước mới "**Chọn kiểu bài**" (đặt SAU bước đề xuất tiêu đề, TRƯỚC bước dựng cấu trúc H2/H3 — quyết định này định hướng cách dựng H2/H3 ngay sau) — chọn 1 trong 4 kiểu tương ứng nguồn: Sequential How-To → **Hướng dẫn tuần tự**, Framework → **Framework/hệ thống**, Comparison/Verdict → **So sánh/kết luận**, Resource List → **Danh sách tài nguyên**; nêu lý do chọn + kiểu nên tránh trong 2 câu (giữ tinh thần "kiểm chứng qua SERP top/PAA/featured snippet" của nguồn qua việc tham chiếu ngược lại bước Research ở Bước 1). Thêm mục output `## Kiểu bài` (đặt sau `## Ý định tìm kiếm`, trước `## Luận điểm chính`). Đây là 1 tầng KHÁC "kiểu trình tự H2" đã có từ v1.0 (từng bước theo thời gian/giải quyết vấn đề/tổng quát→cụ thể — chỉ về THỨ TỰ các H2) — structure archetype quyết định LOẠI BÀI tổng thể, THỨ TỰ H2 vẫn chọn riêng ở bước dựng cấu trúc như cũ.

**(2) Intent map mở rộng 3 câu hỏi — chỉ `standard`/`detailed`:**
- Bước "Xác nhận ý định tìm kiếm" nối thêm yêu cầu viết 1 đoạn ngắn trả lời 3 câu hỏi của nguồn: (a) đọc xong bài, độc giả cần LÀM/ĐẠT được gì; (b) độc giả biết gì lúc BẮT ĐẦU đọc và cần biết gì lúc KẾT THÚC; (c) điều gì khiến họ bỏ bài này đi tìm bài khác — dùng đoạn này soi lại từng H2 có phục vụ đúng ý định không. `brief` giữ nguyên xác nhận nhãn ý định đơn giản, không thêm đoạn này (giữ tinh thần rút gọn).

**(3) Content H3 vs Label H3 + ngưỡng H2 >400 từ — chỉ `standard`/`detailed`:**
- Bước "Dựng cấu trúc H2/H3" thêm: H2 dự kiến dài hơn 400 từ nên có ít nhất 2 H3; mỗi H3 phải nêu 1 điểm CỤ THỂ, đọc riêng tiêu đề H3 cũng hiểu được nội dung bên trong — KHÔNG đặt H3 kiểu nhãn chung như "Ví dụ"/"Lưu ý"/"Mẹo" (Label H3 của nguồn), viết lại thành câu nêu rõ điểm đó (Content H3). `brief` không có H3 trong bước dựng cấu trúc nên không áp dụng.

**(4) Differentiation note mỗi H2 — chỉ `standard`/`detailed`:**
- Bước "Làm rõ nội dung mỗi heading" thêm: với MỖI H2, 1 câu ghi rõ phần này làm KHÁC gì so với các bài đối thủ điển hình về chủ đề này (không chỉ nói chung "chi tiết hơn"). Đây là bản THU NHỎ, áp dụng cho TỪNG H2, của USP (Bước 4, áp dụng cho TOÀN BÀI) — tương tự cách answer-first (§4.11) là bản thu nhỏ của "trả lời sớm".

**(5) FAQ nguồn PAA thật — CẢ 3 `outline_depth`:**
- Bước FAQ (brief/standard/detailed) nối thêm: ưu tiên câu hỏi People Also Ask/tìm kiếm liên quan THẬT quan sát được khi research (Bước 1), không tự bịa câu hỏi chung chung nếu không quan sát được — cùng nguyên tắc "không bịa" đã áp dụng cho số liệu (§4.11) và khối lượng tìm kiếm (§4.8), mở rộng sang câu hỏi FAQ.

**(6) Anchor text gợi ý cho internal link — chỉ `standard`/`detailed`:**
- Bước "Gợi ý internal link + external link" thêm: với MỖI gợi ý internal link, kèm 1 đề xuất anchor text ngắn (2-5 từ, tự nhiên trong câu, không nhồi từ khoá). `brief` không có bước này (§4.9) nên không áp dụng.

---

### 4.13 Gợi ý Schema markup + alt text hình ảnh (v1.10, đối chiếu tangence.in/blog/seo-content-creation)

Nguồn là bài SEO content creation tổng quát 2026 — phần lớn đã có cơ chế tương đương hoặc ngoài phạm vi: topic cluster/pillar ≈ `content_role` (§4.9); tools Ahrefs/SEMrush/Surfer/Clearscope ngoài phạm vi (§0 không tích hợp tool ngoài); lịch cập nhật content 6-12 tháng là audit HẬU-publish, khác tầng "sinh outline trước khi viết"; semantic/LSI keyword ≈ `secondary_keywords` đã có; featured snippet/AI Search optimization ≈ answer-first/AI answer engine đã có (§4.11); EEAT/external link/readability đều đã có từ v1.0-v1.2. Còn lại 2 điểm on-page CHƯA có, không đổi signature/DB:

**(1) Gợi ý loại Schema markup — CẢ 3 `outline_depth`:**
- Bước Meta nối thêm: gợi ý loại Schema markup phù hợp — Article/BlogPosting mặc định, +FAQPage nếu dùng khối FAQ, +HowTo/+ItemList theo `structure archetype` đã chọn ở §4.12 (CHỈ `standard`/`detailed` — `brief` không có bước chọn kiểu bài nên dùng heuristic đơn giản hơn: chỉ Article + FAQPage). Chỉ GỢI Ý loại schema, không sinh JSON-LD.

**(2) Alt text cho hình ảnh — chỉ `standard`/`detailed`:**
- Bước "Làm rõ nội dung mỗi heading" thêm: nếu gợi ý vị trí có hình ảnh, kèm luôn 1 alt text ngắn mô tả đúng nội dung hình, có thể tự nhiên chứa từ khoá liên quan — KHÔNG nhồi từ khoá vào alt text. `brief` không có bước "làm rõ nội dung heading" nên không áp dụng.

---

### 4.14 Từ khoá gần đầu, Meta 140-160 ký tự + CTA, keyword trong 150 từ đầu, chặn nhồi từ khoá (v1.11, đối chiếu moodymedia.io/blog/how-to-write-for-seo)

Nguồn là bài SEO writing beginner-guide tổng hợp trích dẫn Google Search Central/Ahrefs/Semrush/Bruce Clay/SEO.com/eWebMarketing — phần lớn đã có cơ chế tương đương: viết cho người đọc trước khi tối ưu SEO (≈ thesis+USP); nghiên cứu PAA/H2-H3 đối thủ trước khi viết (≈ bước Research + FAQ nguồn PAA thật §4.12); EEAT/tránh nhồi từ khoá tổng thể (≈ §4.11); đoạn ngắn/bullet có dẫn nhập (≈ §4.11); external link (≈ v1.0); AI chỉ là trợ lý nghiên cứu, biên tập viên quyết định cuối (đúng bản chất module — chỉ SINH PROMPT, không tự chạy AI, §0); 80/20 rule + SEO là việc liên tục dài hạn (triết lý chung, không map vào 1 chỉ dẫn cụ thể, không đổi gì). Còn lại 4 điểm on-page CỤ THỂ, áp dụng CẢ 3 `outline_depth`, không đổi signature/DB:

**(1) Từ khoá mục tiêu đặt GẦN ĐẦU:**
- Bước tiêu đề (H1) + bước Meta (Title/Description) đổi từ "chứa từ khoá mục tiêu" → "chứa từ khoá mục tiêu ĐẶT GẦN ĐẦU" — khác việc chỉ xuất hiện đâu đó trong câu.

**(2) Meta Description 140-160 ký tự + câu chủ động + lời mời hành động:**
- Đổi ngưỡng "≤155 ký tự" → khoảng "140-160 ký tự" (theo Semrush 2026 guidance của nguồn — tránh bị cắt (truncate) trên SERP nhưng vẫn dùng đủ không gian hiển thị); thêm yêu cầu dùng câu chủ động + 1 lời mời hành động ngắn để tăng CTR. Meta vẫn đặt SAU bước dựng dàn ý/heading detail trong quy trình — đúng nguyên tắc "viết Meta description sau khi có nội dung chính, không viết trước" của nguồn — không cần đổi thứ tự bước vì thứ tự này đã đúng từ v1.0.

**(3) Từ khoá trong 100-150 từ đầu bài:**
- Bước "Viết luận điểm chính (thesis)" thêm: đặt từ khoá mục tiêu tự nhiên trong 100-150 từ đầu bài (ngay sau H1) — theo nguyên tắc "introduction with the keyword naturally placed in the first 100–150 words" của nguồn.

**(4) Chặn nhồi từ khoá (mật độ từ khoá) — guardrail tổng quát:**
- Nối thêm vào dòng "Lưu ý EEAT" (cùng nguyên tắc gộp dòng như §4.10/§4.11): "**Mật độ từ khoá:** không có ngưỡng % bắt buộc — dùng từ khoá mục tiêu/phụ tự nhiên xuyên suốt bài, tránh nhồi từ khoá (keyword stuffing); ưu tiên bao quát chủ đề đầy đủ hơn là lặp từ khoá." Gộp nguyên tắc "không nhồi từ khoá" đã áp dụng riêng lẻ cho alt text (§4.13)/anchor text (§4.12) thành 1 quy tắc tổng quát cho TOÀN BÀI, khớp với khẳng định của nguồn: Google không quy định mật độ từ khoá bắt buộc.

---

### 4.15 Kiểm kê SERP feature, khớp định dạng featured snippet, gom nhóm heading lặp lại (v1.12, đối chiếu writerush.ai/serp-based-outline-creation)

Nguồn là bài hướng dẫn "SERP-based outline creation" — chuyên biệt cho ĐÚNG chủ đề "phân tích SERP trước khi dựng outline" mà module này phục vụ (khác các nguồn tổng quát hơn ở §4.6/§4.7/§4.9). Đối chiếu: repeated H2/H3 topics, question-format headings, step-by-step/comparison/list content structure, PAA, "related searches", EEAT, semantic SEO expansion (search intent/keyword mapping/topical relevance/internal linking) — tất cả đã có cơ chế tương đương từ v1.0-v1.11 (answer-first §4.11; PAA nguồn thật §4.12; structure archetype §4.12; EEAT §4.6/§4.10/§4.11/§4.14; secondary_keywords/semantic từ v1.0). Nguồn không nêu tool/API cụ thể để tự động crawl SERP (chỉ nói AI có thể tự làm việc này) — khớp đúng model "AI ngoài tự research" đã chốt ở §0, không cần thêm gì.

3 điểm THẬT chưa có, đã thêm vào CẢ 3 `outline_depth`, không đổi signature/DB:

**(1) Kiểm kê SERP feature:**
- Nguồn liệt kê rõ các SERP feature cần quan sát khi phân tích 1 từ khoá: loại featured snippet (đoạn văn/danh sách/bảng), PAA, "related searches", và các feature khác (video, hình ảnh, local pack, product/shopping panel, "Things to know"). Module trước đây chỉ yêu cầu chung "research SERP"/"research 5-10 trang", không tách riêng các feature cụ thể này.
- Thêm vào bước Research (`brief`: Bước 1; `standard`: Bước 1; `detailed`: Bước 1 + Bước 2 "Đánh giá độ khó cạnh tranh"): yêu cầu ghi chú NGẮN các SERP feature đang xuất hiện cho từ khoá mục tiêu (khi quan sát được) — dùng để outline khai thác đúng định dạng Google đang ưu tiên hiển thị. KHÔNG bắt buộc — nếu AI không có khả năng research thật, bỏ qua (cùng nguyên tắc "không bịa dữ liệu" nhất quán toàn module, giống ước tính khối lượng tìm kiếm §4.8).

**(2) Khớp định dạng câu trả lời với định dạng featured snippet đang thắng:**
- Nguồn: "Keep definitions and explanations tight when Featured Snippets are present" + "match section depth to what's ranking" — gợi ý outline nên phản ánh ĐÚNG định dạng (không chỉ nội dung) mà Google hiện đang chọn hiển thị cho câu hỏi đó.
- Mở rộng chỉ dẫn "answer-first" đã có (§4.11 — mở đầu H2/H3 bằng câu trả lời trực tiếp) thêm 1 điều kiện: nếu Bước Research quan sát được featured snippet đang hiển thị dạng danh sách/bảng cho câu hỏi tương ứng, format câu trả lời mở đầu ĐÚNG dạng đó (danh sách/bảng) thay vì đoạn văn — tăng khả năng được Google chọn thay thế snippet hiện tại. Áp dụng bước dựng H2/H3 ở CẢ 3 depth (bước 4/brief, bước 7/standard, bước 8/detailed).

**(3) Gom nhóm heading lặp lại giữa nhiều trang đối thủ:**
- Nguồn (Bước 3 "Reviewing top 5-10 organic results"): "Group similar headings together" + "Repeated topics show what users likely expect from a complete guide" — đây là kỹ thuật lõi của "SERP-based outline creation", biến quan sát rời rạc từng trang thành 1 pattern rõ ràng.
- Thêm vào bước Research (`standard`/`detailed` — `brief` không cần vì research đã rút gọn "không yêu cầu 5-10 trang"): yêu cầu nhóm các H2/H3 LẶP LẠI giữa nhiều trang đã research thành 1 danh sách ngắn chủ đề độc giả chắc chắn kỳ vọng thấy trong 1 bài đầy đủ.
- Bước dựng H2/H3 (`standard`: Bước 7; `detailed`: Bước 8) nối thêm ràng buộc: PHẢI bao quát các chủ đề LẶP LẠI đã ghi nhận đó — nếu chủ động bỏ qua 1 chủ đề lặp lại phổ biến, phải nêu rõ lý do (tránh outline thiếu sót so với kỳ vọng thị trường mà không có chủ ý).
- Self-check (`standard`: Bước 13; `detailed`: Bước 15) mở rộng: kiểm tra đã bao quát chủ đề LẶP LẠI đã ghi ở Bước 1 chưa, và đã khai thác đúng định dạng SERP feature quan sát được chưa.

**KHÔNG áp dụng** (2 điểm của nguồn không map vào cơ chế mới, đã có cơ chế tương đương hoặc ngoài phạm vi):
- **Bảng theo dõi đối thủ có cấu trúc cột cố định** (page title/content type/format/angle/heading structure/examples/FAQ/CTA cho MỖI trang, nguồn gọi là "tracking sheet") — mục đích cốt lõi của bảng này (gom nhóm heading lặp lại + xác định content gap) đã đạt được qua điểm (3) ở trên mà không cần thêm 1 section output mới hiển thị bảng research thô cho MỖI trang đối thủ — khác tinh thần "output chỉ gồm ARTIFACT đã tổng hợp" (title/USP/outline/FAQ/Meta...) hiện tại của module, và sẽ làm phình prompt/output đáng kể nếu áp cho 5-15 trang (đúng loại rủi ro "prompt length" đã né tránh nhiều lần, §4.1). Đánh giá mức đầu tư nội dung đối thủ (độ dài/số H2-H3/backlink) đã có sẵn ở "Đánh giá độ khó cạnh tranh" (`detailed`, §4.8) — SERP feature được gộp THÊM vào ĐÚNG bước đó (điểm (1) ở trên) thay vì tạo section mới trùng lặp mục đích.
- **Validation checklist 4 câu hỏi cuối** ("match search intent? answer completely? natural order? better than competitors?") — đã có cơ chế tương đương từ trước qua self-check (§4.8/§4.11/§4.12, nay mở rộng thêm ở điểm (3)) + USP (§4.8, "vì sao đọc bài NÀY") — không cần thêm 1 bước riêng lặp lại 4 câu hỏi tương tự.

---

### 4.16 H2 "Kết luận" + prompt mẫu Bước 2 viết bài từ outline (v1.13, đối chiếu framework "2-bước Outline → Draft" người dùng cung cấp trực tiếp, không kèm URL)

Nguồn mô tả 1 quy trình 2 prompt tách rời — Prompt 1 sinh outline (persona "expert professional writer", input working title/audience/main goal, format Markdown H2+H3 với 3 bullet/heading, "brief introduction and conclusion section"), Prompt 2 dán outline đã duyệt vào để viết bài hoàn chỉnh (persona "professional subject matter expert", word count, tone, style guideline: câu chủ động, tránh từ sáo rỗng, actionable takeaway mỗi phần). Đối chiếu Prompt 1: persona/audience/goal/format H2-H3+bullet đều đã có cơ chế tương đương từ v1.0 (persona "SEO Content Strategist", `target_audience`/`content_goal`, 2-5 điểm/H3 tuỳ depth §4.7/§3.2) — không đổi gì thêm.

2 điểm THẬT chưa có, áp dụng CẢ 3 `outline_depth`, không đổi DB schema/signature `handle()` (chỉ đổi tham số nội bộ của `buildBottom()` — private method, không ảnh hưởng caller):

**(1) H2 "Kết luận" khép lại Dàn ý:**
- Nguồn: outline nên có "a brief introduction and conclusion section" — module đã có phần mở đầu tương đương (Luận điểm chính, §4.7, đặt từ khoá trong 100-150 từ đầu, §4.14) nhưng CHƯA có yêu cầu nào cho 1 phần KHÉP LẠI Dàn ý.
- Thêm vào bước dựng H2 (`brief`: Bước 4; `standard`: Bước 7; `detailed`: Bước 8): Dàn ý nên khép lại bằng 1 H2 "Kết luận" ngắn tóm lại luận điểm chính (không lặp nguyên văn) — trước khi chuyển sang FAQ.
- Phân biệt rõ với 2 khái niệm đã có: khác **CTA** (§4.10 — CTA là hành động/bước tiếp theo cho độc giả, đặt ở mục riêng CUỐI toàn prompt, ngoài Dàn ý; Kết luận là tóm lại thông điệp, nằm TRONG Dàn ý); khác **"So sánh/kết luận"** (1 trong 4 structure archetype, §4.12 — đó là KIỂU BÀI tổng thể cho 1 số từ khoá "vs"/"nên chọn", không phải MỌI outline đều dùng archetype này) — nếu archetype đã chọn tự nhiên có phần khuyến nghị/tổng kết riêng, dùng LUÔN phần đó làm kết luận, không ép thêm H2 trùng lặp (đã ghi rõ trong text bước dựng H2 ở `standard`/`detailed`).

**(2) Prompt mẫu "Bước tiếp theo" (Prompt 2 — viết bài từ outline đã duyệt):**
- Nguồn: sau khi có outline đã duyệt, dán vào 1 prompt riêng (persona subject-matter-expert, word count, tone, style guideline) để AI viết bài hoàn chỉnh — module trước đây dừng lại hoàn toàn ở outline (đúng §0/§1), không đưa ra gợi ý bước kế tiếp nào cho editor.
- Thêm 1 khối `## Bước tiếp theo (tuỳ chọn — KHÔNG cần xử lý trong câu trả lời của lượt chat này)` ở CUỐI mỗi prompt (`BuildContentOutlinePromptAction::buildDraftPromptTemplate()`) — 1 đoạn **văn bản tĩnh** (không tự chạy AI trong app, KHÔNG vi phạm §0 mục 1) chứa sẵn 1 prompt mẫu để biên tập viên tự copy sang 1 LƯỢT CHAT MỚI sau khi đã đọc/sửa outline, dùng để viết bài hoàn chỉnh từ outline đã duyệt.
- Prompt mẫu tái dùng NGUYÊN field đã có trong `ContentOutlineInputData` — KHÔNG thêm field input mới: `target_keyword` (persona/chủ đề), `desired_word_count` (word count, fallback "độ dài hợp lý theo outline" nếu null), `tone_style` với fallback `foundation?->style_sample` (tone — cùng cách TOP đã resolve, §3.2), `language` (vi/en). Phải TỰ CHỨA đủ các giá trị này (không thể chỉ viết "theo yêu cầu đã nêu ở trên") vì prompt mẫu này được dùng ở 1 lượt chat HOÀN TOÀN MỚI, không còn ngữ cảnh phần "Thông tin đầu vào" phía trên.
- Style guideline của nguồn (active voice, tránh fluff, actionable takeaway mỗi phần, không bịa số liệu, không nhồi từ khoá) đưa NGUYÊN vào trong prompt mẫu này — vì các quy tắc này áp cho VĂN XUÔI hoàn chỉnh (bài viết sẽ được viết ra), không áp được cho chính outline hiện tại (outline là heading+bullet, không phải câu văn liền mạch) — do đó KHÔNG thêm vào dòng "Lưu ý EEAT" của outline hiện tại.
- **Cảnh báo an toàn quan trọng**: khối này mở đầu bằng 1 câu chặn rõ — AI đang xử lý prompt outline KHÔNG được tự ý viết luôn bài đầy đủ hoặc lặp lại khối mẫu này trong câu trả lời của lượt chat hiện tại — phòng rủi ro AI đọc thấy "viết thành 1 bài hoàn chỉnh..." trong prompt mẫu rồi hiểu lầm thực hiện luôn, gây ra kết quả ngoài tầm kiểm soát (vi phạm §0 mục 1/§1 "Phi mục tiêu" — module chỉ sinh outline, không sinh bài viết).
- Implementation: `buildBottom()` đổi signature nhận thêm `ContentOutlineInputData $input, ?CategoryContentFoundation $foundation` (trước đó chỉ nhận `$contentRole` rút gọn) — chèn qua placeholder `{{DRAFT_PROMPT_TEMPLATE}}` đặt sau khối "Định dạng output" ở cả 3 nowdoc (cùng kỹ thuật `str_replace()` post-nowdoc đã dùng cho `{{ROLE_LINK_NOTE}}`, §4.9). `handle()` (public, `AsAction`) giữ NGUYÊN signature — chỉ đổi lời gọi nội bộ tới `buildBottom()`, không ảnh hưởng `CreateContentOutlineAction`/`RegenerateContentOutlinePromptAction`.

**KHÔNG áp dụng:**
- **Chạy Prompt 2 thật trong app** (thêm nút "Viết bài từ outline" gọi `AIProviderManager`) — đòi hỏi gọi AI Provider trong app, ngoài phạm vi đã chốt ở §0 mục 1; Prompt 2 ở đây CHỈ là văn bản mẫu tĩnh nằm trong `generated_prompt`, người dùng tự copy đi chạy ở AI ngoài, giống cách toàn bộ module hoạt động.
- **Cấu trúc outline "chính thức" theo bullet cố định 3 điểm/heading** — module đã có cơ chế tương đương linh hoạt hơn theo `outline_depth` (2-3/3-5 điểm/H3, §4.7) — không cần đổi cứng thành đúng 3.

---

### 4.17 Feature `ArticleDrafting` — "Bước 2: viết bài từ outline" (v1.14)

Người dùng yêu cầu rõ: sau khi có prompt sinh outline ("Bước 1", đã có từ v1.0), bước tiếp theo là **viết bài dựa trên outline đó** — nhưng mục tiêu VẪN CHỈ là sinh prompt (không gọi AI Provider trong app, giữ nguyên §0). Khác các version trước (chỉ sửa TEXT trong prompt hiện có), đây là **1 Feature thật**: cần outline THẬT (không phải placeholder) làm input, nên cần lưu trữ + Action + UI riêng.

**Vì sao KHÔNG tái dùng nguyên khối "## Bước tiếp theo" của v1.13**: khối đó là 1 đoạn text TĨNH chèn cuối `generated_prompt`, chứa placeholder `[Dán outline vào đây]` để biên tập viên tự điền TAY mỗi lần muốn dùng — không có nơi lưu outline thật, không tách biệt được với prompt "Bước 1", và annotator phải tự nhớ xoá cảnh báo "KHÔNG cần xử lý" khi copy dùng riêng. v1.14 thay bằng 1 luồng lưu-rồi-sinh: dán outline → lưu vào DB → sinh 1 prompt ĐỘC LẬP đã nhúng SẴN outline đó. **Khối tĩnh v1.13 bị XOÁ** — giữ cả 2 sẽ tạo 2 nguồn hướng dẫn "viết bài từ outline" khác nhau trong cùng 1 bản ghi, dễ gây nhầm lẫn cho biên tập viên không biết dùng cái nào.

**Thiết kế:**

1. **2 field mới trên `content_outlines`** (§2.1, migration riêng):
   - `approved_outline` (longText, nullable) — biên tập viên dán outline Markdown mà AI ngoài trả về sau khi chạy `generated_prompt` (đã đọc/sửa lại nếu cần) vào đây.
   - `article_draft_prompt` (longText, nullable) — snapshot prompt viết bài đã sinh, ghi đè khi sinh lại — CÙNG nguyên tắc không-versioning với `generated_prompt` (§0/§4.2).

2. **`BuildArticleDraftPromptAction`** (`Features/ArticleDrafting/Actions/`) — `handle(ContentOutlineInputData $input, ?CategoryContentFoundation $foundation, string $approvedOutline): string`. Tái dùng NGUYÊN `ContentOutlineInputData` (không tạo DTO mới) — hydrate trực tiếp từ `ContentOutline` model đã lưu qua `ContentOutlineInputData::from($contentOutline)` (Spatie Laravel Data tự map theo tên property/cột trùng nhau — đã verify hoạt động đúng qua tinker khi viết spec này). Sinh 1 prompt gồm: persona người viết (khác persona "SEO Content Strategist" của Bước 1 — đây là persona VIẾT, không phải RESEARCH) → outline đã duyệt nhúng NGUYÊN VĂN → yêu cầu bài viết (giữ đúng cấu trúc outline, độ dài/ngôn ngữ/giọng văn lấy từ field đã có, câu chủ động/tránh fluff/actionable takeaway/không bịa số liệu/không nhồi từ khoá — kế thừa các guardrail đã có ở prompt outline) → khối "Hệ giá trị gia đình Việt Nam" (LUÔN chèn, CÙNG nguồn `config('content_foundation.family_values')` với prompt outline — bài viết THẬT là nơi rủi ro vi phạm giá trị gia đình cao nhất) → "Lưu ý EEAT" ngắn → "Định dạng output" (1 bài Markdown hoàn chỉnh, không thêm lời dẫn). `WORD_COUNT_WARNING_THRESHOLD = 10000` — ngưỡng RIÊNG (cao hơn 6.000 của outline) vì prompt này CHỦ ĐỘNG nhúng nguyên outline dài, dài hơn là BÌNH THƯỜNG chứ không phải phình do lỗi.

3. **`SaveApprovedOutlineAndBuildArticlePromptAction`** (`Features/ArticleDrafting/Actions/`) — `handle(ContentOutline $outline, string $approvedOutline, int $updatedBy): ContentOutline`. Resolve `$foundation` qua `ResolvesCategoryContext` (dùng lại `$outline->post_category_id`), build prompt, rồi `update()`: `approved_outline`, `article_draft_prompt`, `updated_by`. **KHÔNG đụng** `generated_prompt`/`linked_post_article_id` — bước này ĐỘC LẬP với vòng sinh/sinh lại outline (§4.2), cùng nguyên tắc `linked_post_article_id` giữ nguyên khi regenerate outline.

4. **Refactor kèm theo** (extract khi có điểm dùng THỨ 2, cùng nguyên tắc đã áp dụng cho `ResolvesCategoryContext` ở §4.6):
   - `BuildsSharedPromptBlocks` (trait mới, `Features/Concerns/`) — chứa `estimateWordCount()` (static) + `buildFamilyValuesBlock()`, tách từ `BuildContentOutlinePromptAction` vì `BuildArticleDraftPromptAction` cần CÙNG 2 hàm này. Cả 2 Action `use BuildsSharedPromptBlocks;` — hành vi giống tuyệt đối bản gốc, gọi tĩnh qua tên lớp cụ thể (VD `BuildContentOutlinePromptAction::estimateWordCount()`) vẫn hoạt động bình thường (PHP trait method thừa hưởng như method riêng của lớp).
   - `ResolvesCategoryContext` — dời từ `Features/OutlineGeneration/Actions/Concerns/` lên `Features/Concerns/` (dùng chung `OutlineGeneration` + `ArticleDrafting`).
   - `BuildContentOutlinePromptAction::buildBottom()` **revert** về signature gốc trước v1.13 (`$depth`/`$contentRole`/`$hasExistingArticles`, KHÔNG còn nhận `$input`/`$foundation`) — vì placeholder `{{DRAFT_PROMPT_TEMPLATE}}`/`buildDraftPromptTemplate()` của v1.13 đã XOÁ.

5. **UI (`show.blade.php`)** — thêm card "Bước 2 — Viết bài từ outline": `<textarea name="approved_outline">` (prefill giá trị đã lưu) trong 1 `<form>` POST tới route mới, nút "Lưu & Sinh prompt viết bài" (đổi thành "...Sinh LẠI..." nếu đã có prompt cũ, kèm `data-confirm-regenerate` với message riêng — CHỈ gắn khi `article_draft_prompt` đã tồn tại). Nếu đã có `article_draft_prompt`: hiện CÙNG UX với prompt outline (toggle Prompt thô/Xem trước Markdown, Copy, Download .md, banner cảnh báo dài) — tái dùng 3 hàm JS đã tổng quát hoá theo `elId`/`containerId` (`contentOutlineCopyPrompt`/`contentOutlineDownloadPrompt`/`contentOutlineMakeCollapsible`, trước đó hardcode ID của khối outline) thay vì viết lại 3 hàm mới cho khối thứ 2.

6. **Route/Controller** — `POST {outline}/article-prompt` → `ContentOutlineController::generateArticlePrompt()` (gate CÙNG `content_outlines.use`, KHÔNG cần permission mới — đây là hành động trên CÙNG resource `ContentOutline`, không phải tính năng độc lập). `show()` load thêm `articlePromptWordCount`/`articlePromptIsLong`/`articlePromptHtml` (rỗng an toàn nếu chưa sinh lần nào).

**KHÔNG áp dụng:**
- **Gọi AI Provider trong app để viết bài thật** — đây vẫn là ranh giới đã chốt ở §0 mục 1, không đổi vì yêu cầu người dùng CHỈ RÕ "mục tiêu vẫn là sinh prompt tạo bài viết... thôi". Nếu tương lai cần chạy AI thật, đó là quyết định phạm vi RIÊNG cần hỏi lại (cost/budget/model — xem 4 câu hỏi đã hỏi và bị từ chối ở phần trao đổi trước khi chốt v1.14).
- **Tự động tạo/cập nhật `PostArticle` từ `article_draft_prompt`/kết quả AI trả về** — giữ đúng non-goal §1 "Không tự động tạo PostArticle từ outline"; `linked_post_article_id` (§4.4) vẫn chỉ là liên kết THAM CHIẾU thủ công, không liên quan tới Feature này.
- **Versioning nhiều bản `article_draft_prompt`** — CÙNG quyết định không-versioning đã áp dụng cho `generated_prompt` (§1/§4.2) — sinh lại ghi đè, không giữ lịch sử.
- **Badge "dài" cho `article_draft_prompt` ở trang danh sách** (`ContentOutlineListResource.is_long_prompt` hiện chỉ tính `generated_prompt`) — chỉ cảnh báo ở trang `show` (nơi biên tập viên thực sự đọc/copy prompt này); thêm badge riêng ở danh sách là engineering chưa có nhu cầu thật, để mở cho version sau nếu cần.

---

### 4.18 CTA URL thật, hook mở bài, format scannable, chọn tiêu đề mạnh nhất (v1.15, đối chiếu "10-step AI prompt chain viết pillar post")

Nguồn là 1 chuỗi 10 prompt tách rời cho việc viết TOÀN BỘ 1 bài pillar post từ đầu — kèm theo 1 đoạn quảng bá sản phẩm bên thứ 3 (tool tự động chạy chuỗi 10 prompt đó trong ChatGPT/Claude/Gemini). **Không xác nhận, không đánh giá, không tích hợp sản phẩm đó** — nó vi phạm trực tiếp §0 mục 1 (không tự gọi AI trong app) nếu module tự động hoá chuỗi prompt, và không có cách nào kiểm chứng 1 công cụ bên thứ 3 từ 1 đoạn quảng cáo. Chỉ đối chiếu KỸ THUẬT trong 10 bước, đã hỏi người dùng phạm vi trước khi áp dụng (xem changelog v1.15 ở đầu file).

Đối chiếu 10 bước: Setup/persona (≈ TOP + persona ArticleDrafting); Audience deep-dive (≈ `target_audience`/pain_points + intent map 3 câu hỏi §4.12); Competitive analysis + unique angle (≈ USP §4.8 + differentiation note §4.12); Outline creation (core module); Meta title/description (đã có); Final assembly Markdown-only (≈ "Định dạng output" đã có). 4 điểm THẬT chưa có, ĐÃ ÁP DỤNG:

**(1) `cta_url` — URL CTA thật:**
- Field mới `cta_url` (string 500, nullable) trên `content_outlines` + `ContentOutlineInputData` — khác `content_goal` (chỉ định hướng LOẠI CTA chung, VD "thu hút đăng ký tư vấn") — đây là 1 URL CỤ THỂ để nhúng THẲNG vào câu văn.
- Validate `url()` thật (khác `competitor_urls` — free text cho phép kèm ghi chú) vì sẽ chèn trực tiếp vào câu CTA.
- TOP (`BuildContentOutlinePromptAction::buildTop()`) thêm dòng "CTA URL" nếu có giá trị.
- Bước CTA (CẢ 3 `outline_depth`, đã có từ §4.10) nối thêm: nếu có "CTA URL" ở trên, viết CTA dưới dạng 1 câu chuyển tiếp TỰ NHIÊN mời truy cập ĐÚNG URL đó (không dán trơ URL).
- `BuildArticleDraftPromptAction` — đoạn kết bài PHẢI mời truy cập ĐÚNG `cta_url` (nếu có) bằng 1 câu chuyển tiếp tự nhiên nêu rõ lợi ích; nếu không có, giữ chỉ dẫn CTA chung như v1.14 (fallback, không bắt buộc phải có `cta_url` mới dùng được module).

**(2) Hook mở bài (`BuildArticleDraftPromptAction` — Bước 2, KHÔNG áp cho outline Bước 1):**
- Nguồn có 1 prompt riêng "Hook & Introduction" (150 từ, mở bằng hook chạm đúng pain point, nêu rõ đọc xong biết/làm được gì) — module trước đây chỉ có yêu cầu chung "triển khai đầy đủ outline thành bài viết", chưa có chỉ dẫn CỤ THỂ cho đoạn MỞ BÀI.
- Thêm bullet "Đoạn mở bài" vào "Yêu cầu bài viết" của `BuildArticleDraftPromptAction`: mở bằng 1 hook chạm đúng vấn đề/nỗi đau chính của đối tượng đọc, nêu rõ đọc xong bài sẽ biết/làm được gì — KHÔNG mở đầu vòng vo. Không đặt ở outline (Bước 1) vì outline chỉ có "Luận điểm chính" (thesis, 1-2 câu tóm ý) — đây là chỉ dẫn cho VĂN XUÔI mở bài thật, chỉ có ý nghĩa ở bước VIẾT.

**(3) Format scannable (`BuildArticleDraftPromptAction` — Bước 2):**
- Nguồn (Core Content step): "short paragraphs, bullets, and bold phrases" — module trước đây có "list lead-in" (§4.11, đã có) nhưng chưa có chỉ dẫn TỔNG QUÁT về độ dài đoạn văn + in đậm cụm từ quan trọng.
- Thêm bullet: ưu tiên đoạn văn NGẮN (2-4 câu/đoạn), dùng bullet cho danh sách, in đậm (bold) cụm từ/khái niệm quan trọng.

**(4) Chọn tiêu đề mạnh nhất + lý do (`BuildContentOutlinePromptAction` — bước brainstorm tiêu đề, CẢ 3 depth):**
- Nguồn (Headline Brainstorm): "Indicate the strongest one and why" — module trước đây chỉ yêu cầu đề xuất 1-2/2-3 phương án, không yêu cầu AI tự CHỌN 1 + giải thích.
- Thêm vào bước đề xuất tiêu đề (brief Bước 2/standard Bước 5/detailed Bước 6): đánh dấu rõ phương án MẠNH NHẤT (dự đoán CTR cao nhất) + 1 câu lý do.

**KHÔNG áp dụng (đã hỏi người dùng, từ chối rõ):**
- **Gọi AI Provider trong app để tự động chạy chuỗi prompt** (bản chất sản phẩm quảng cáo trong nguồn) — vi phạm §0 mục 1, không đổi.
- **Social snippet (X/Twitter 280 ký tự, LinkedIn 120 từ) + 10-15 tags** — người dùng xác nhận KHÔNG muốn thêm; đây là artifact PHÂN PHỐI/QUẢNG BÁ, khác tầng "outline + bài viết cho 1 bài" mà module phục vụ — cùng lý do đã từ chối phần lớn checklist content-marketing tổng thể ở §4.6/§9.
- **Audience deep-dive như 1 bước/section riêng** — đã có cơ chế tương đương đủ tốt (`target_audience` + pain_points từ foundation + intent map 3 câu hỏi §4.12); thêm 1 bước/mục output riêng chỉ để tạo lại persona chi tiết là dư thừa.

---

### 4.19 Before-after example, 2-3 phương án Meta, chặn bịa case study, cấm cliché mở bài (v1.16, đối chiếu junia.ai/blog/ai-blog-writing-prompts)

Nguồn liệt kê 8 loại prompt cho quy trình viết blog bằng AI (Outline/First Draft/Introduction Rewriting/Examples Enhancement/SEO Optimization/Readability Editing/Metadata Creation/Final Editing). Đối chiếu: Outline (core module), First Draft (≈ ArticleDrafting §4.17), audience/intent/keyword (đã có từ v1.0). 4 điểm THẬT chưa có ở lớp PROMPT-LEVEL, ĐÃ ÁP DỤNG, CẢ 3 `outline_depth` trừ khi ghi chú riêng, không đổi signature/DB:

**(1) Before-after comparison làm 1 loại ví dụ:**
- Nguồn (Examples Enhancement Prompt) nêu riêng "one before-and-after comparison" như 1 dạng ví dụ mạnh — module trước đây chỉ nói chung "ví dụ/số liệu".
- Thêm vào bước "Làm rõ nội dung mỗi heading" (`standard`: Bước 8, `detailed`: Bước 9): gợi ý "so sánh trước/sau (before-after)" làm 1 loại ví dụ, khi phù hợp chủ đề.

**(2) 2-3 phương án Meta Title/Description:**
- Nguồn (Metadata Creation Prompt) yêu cầu "5 meta title + 5 description options" để chọn — module trước đây chỉ sinh 1 phương án MỖI loại (khoá "chặt" ngay từ đầu).
- Đổi bước Meta (CẢ 3 depth) từ "Meta Title (...) + Meta Description (...)" → "2-3 phương án Meta Title (...) + 2-3 phương án Meta Description (...)" để biên tập viên tự chọn. Chọn 2-3 (không phải 5 như nguồn) để cân bằng với ràng buộc prompt-length đã có (§4.1) — 5×2 phương án sẽ làm phình đáng kể phần Meta so với giá trị thêm được.

**(3) Chặn bịa case study:**
- Nguồn (Examples Enhancement Prompt) cấm riêng "fake case studies" cạnh "fake statistics" — module trước đây (Lưu ý EEAT, §4.11) chỉ cấm số liệu.
- Mở rộng câu "Không bịa số liệu" (CẢ 3 depth) thêm "case study": "...nếu không chắc 1 số liệu/thống kê/case study/ví dụ thực tế cụ thể, ghi rõ [cần biên tập viên xác minh]..."

**(4) Cấm cliché mở đầu (chỉ `BuildArticleDraftPromptAction` — Bước 2, KHÔNG áp cho outline Bước 1):**
- Nguồn (Introduction Rewriting Prompt) cấm CỤ THỂ cụm "in today's fast-paced world" — 1 cliché rất phổ biến của văn bản do AI viết, không chỉ nói chung "tránh sáo rỗng". Module trước đây (v1.15, §4.18) chỉ nói chung "không mở đầu vòng vo", chưa cấm cụm cụ thể + chưa ràng buộc VỊ TRÍ.
- Sửa bullet "Đoạn mở bài": nêu vấn đề trong 1-2 câu ĐẦU (thay vì chỉ nói chung), cấm cụ thể cụm "trong thế giới hiện đại ngày nay"/"trong bối cảnh phát triển như hiện nay"/"chúng ta đều biết rằng" hoặc tương đương. Đặt ở ArticleDrafting (không phải outline) vì đây là chỉ dẫn cho VĂN XUÔI mở bài thật, chỉ có ý nghĩa ở bước VIẾT.
- Đồng thời mở rộng "Không bịa số liệu" của `BuildArticleDraftPromptAction` thêm "case study" (đồng bộ với điểm (3) ở outline).

**KHÔNG áp dụng ở lớp OUTLINE/ArticleDrafting** (3 prompt review của nguồn — xem §4.20 cho quyết định về lớp NÀY): SEO Optimization Prompt/Readability Editing Prompt/Final Editing Prompt đều REVIEW 1 bài ĐÃ VIẾT XONG — khác lớp "sinh outline TRƯỚC KHI viết" (§0) hoặc "sinh bài MỚI từ outline" (§4.17); đã tách thành Feature `ArticleReview` riêng (§4.20) sau khi hỏi người dùng.

### 4.20 Feature `ArticleReview` — "Bước 3: soát lỗi/sửa bài" (v1.16)

3 loại prompt REVIEW của nguồn junia.ai (SEO Optimization/Readability Editing/Final Editing) đều vận hành trên 1 bài viết ĐÃ VIẾT XONG — khác hẳn "Bước 1" (sinh outline TRƯỚC KHI viết) và "Bước 2" (sinh bài MỚI từ outline, §4.17). Module trước đây (v1.15) dừng lại ở "Bước 2", chưa có cơ chế nào cho việc SOÁT LỖI/SỬA 1 bài đã có. Đã hỏi người dùng trước khi triển khai (xem changelog v1.16) — xác nhận MUỐN thêm, quy mô tương tự `ArticleDrafting` (§4.17).

**Thiết kế** (đối xứng hoàn toàn với §4.17, chỉ đổi input/output/nội dung prompt):

1. **2 field mới trên `content_outlines`** (§2.1): `drafted_article` (longText, nullable) — biên tập viên dán bài viết ĐÃ VIẾT XONG (từ AI ngoài chạy `article_draft_prompt`, hoặc viết tay — KHÔNG bắt buộc phải qua "Bước 2" của module này) vào đây; `review_prompt` (longText, nullable) — snapshot prompt soát lỗi/sửa đã sinh, ghi đè khi sinh lại (không versioning, cùng §0/§4.2).

2. **`BuildArticleReviewPromptAction`** (`Features/ArticleReview/Actions/`) — `handle(ContentOutlineInputData $input, ?CategoryContentFoundation $foundation, string $draftedArticle): string`. Tái dùng NGUYÊN `ContentOutlineInputData` + `BuildsSharedPromptBlocks` (giống §4.17). GỘP 3 loại prompt review của nguồn thành 1 prompt duy nhất (giữ mô hình "sinh 1 prompt, chạy 1 lần" đã chốt ở §0 — KHÔNG tách 3 lượt gọi AI riêng như nguồn có thể ngụ ý): (1) **Đánh giá SEO** — khớp tiêu đề/ý định tìm kiếm, heading chưa rõ, từ khoá gượng/nhồi, gợi ý 2-3 câu hỏi FAQ; (2) **Đánh giá độ dễ đọc** — câu/đoạn dài, filler, chuyển đoạn yếu, kèm CẢNH BÁO không đơn giản hoá quá mức làm mất chính xác kỹ thuật (đúng nguyên văn cảnh báo của nguồn); (3) **Rà soát cuối** — đoạn chưa rõ/lặp ý/thiếu dẫn chứng/thiếu ví dụ/giọng máy móc ("robotic"). Kết thúc bằng **"Đề xuất sửa"** — yêu cầu đoạn văn THAY THẾ CỤ THỂ cho MỖI vấn đề, đúng tinh thần "request precise edits, without full rewrites" của nguồn (Final Editing Prompt) — khác hẳn `BuildArticleDraftPromptAction` (sinh bài MỚI), Action này SỬA bài đã có. `WORD_COUNT_WARNING_THRESHOLD = 12000` — ngưỡng RIÊNG, cao hơn cả `BuildArticleDraftPromptAction` (10.000) vì nhúng nguyên 1 bài viết hoàn chỉnh thường dài hơn 1 outline.

3. **`SaveDraftedArticleAndBuildReviewPromptAction`** (`Features/ArticleReview/Actions/`) — `handle(ContentOutline $outline, string $draftedArticle, int $updatedBy): ContentOutline`. Resolve `$foundation` qua `ResolvesCategoryContext` (dùng chung, §4.17), build prompt, `update()`: `drafted_article`, `review_prompt`, `updated_by`. KHÔNG đụng `generated_prompt`/`approved_outline`/`article_draft_prompt`/`linked_post_article_id` — bước ĐỘC LẬP với "Bước 1"/"Bước 2".

4. **UI (`show.blade.php`)** — card "Bước 3 — Soát lỗi/Sửa bài", đối xứng UI với "Bước 2": `<textarea name="drafted_article">` (prefill), nút "Lưu & Sinh (lại) prompt soát lỗi" (`data-confirm-regenerate` khi đã có `review_prompt`), hiện kết quả với CÙNG UX toggle/copy/download/banner (tái dùng 3 hàm JS đã tổng quát hoá từ §4.17, chỉ đổi `elId`/`containerId` thành `content-outline-review-*`).

5. **Route/Controller** — `POST {outline}/review-prompt` → `ContentOutlineController::generateReviewPrompt()` (gate CÙNG `content_outlines.use`, không permission mới — cùng lý do §4.17 mục 6).

**KHÔNG áp dụng:**
- **Gọi AI Provider trong app để tự động soát/sửa** — giữ đúng §0 mục 1, không đổi dù mục đích là "sửa bài".
- **Tách 3 loại review (SEO/readability/final-edit) thành 3 prompt/3 lượt sinh riêng** — nguồn không bắt buộc tách rời (khác các nguồn "kiến trúc N-bước tách rời" đã từ chối ở §4.11/§4.12) — gộp 1 prompt vừa giữ đúng mô hình module, vừa tiết kiệm số lượt copy-paste cho biên tập viên.
- **Tự động áp dụng đề xuất sửa vào `drafted_article`/`PostArticle`** — Action chỉ SINH PROMPT gợi ý, không tự sửa nội dung nào — biên tập viên đọc đề xuất rồi tự áp dụng (hoặc không) vào bài viết thật.

---

### 4.21 Gợi ý ý tưởng infographic (v1.17, đối chiếu blog.qolaba.ai/.../blog-writing-prompts-from-outline-to-publication)

Nguồn mô tả pipeline 5 giai đoạn cho quy trình viết blog bằng AI: Foundation Prompts (ý tưởng chủ đề/hook) → Strategic Outlining → Strategic Drafting & Editing → SEO Optimization → Collaborative Publishing. Đối chiếu: Foundation Prompts (sinh 10 chủ đề blog, hook mở bài TRƯỚC KHI đã chọn topic) thuộc phạm vi `CoreIdeaExtractor`/`VideoIdeaExtractor` (module này CHỈ bắt đầu SAU KHI đã có `topic`/`target_keyword` — field bắt buộc từ v1.0); Strategic Outlining đã có cơ chế tương đương đầy đủ hơn (structure archetype, USP, benchmark độ sâu...); "SEO tích hợp xuyên suốt, không phải bước cuối" đúng triết lý module đã theo từ v1.0 (Meta/schema/FAQ đều nằm TRONG quy trình dựng outline, không phải 1 bước audit riêng ở cuối).

1 điểm THẬT áp dụng, chỉ `standard`/`detailed` (có bước "làm rõ nội dung heading" để gắn vào — `brief` không có), không đổi signature/DB:

**Gợi ý ý tưởng infographic:**
- Nguồn (Format Optimization Prompts): "Create a compelling infographic concept to illustrate these key statistics" — khác alt text cho 1 ảnh ĐƠN đã có (§4.13) — đây là gợi ý Ý TƯỞNG minh hoạ dạng infographic khi 1 phần có NHIỀU số liệu/bước liên tiếp.
- Thêm vào bước "Làm rõ nội dung mỗi heading" (`standard`: Bước 8, `detailed`: Bước 9): nếu phần đó có nhiều số liệu/bước liên tiếp phù hợp minh hoạ trực quan, gợi ý luôn 1 Ý TƯỞNG infographic ngắn (VD "infographic 5 bước...") — chỉ cần Ý TƯỞNG, không cần thiết kế/ảnh thật (cùng nguyên tắc "chỉ gợi ý, không sinh asset thật" đã áp dụng cho Schema markup §4.13/alt text §4.13).

**Đã hỏi người dùng 1 câu quyết định phạm vi trước khi chốt — 2 điểm TỪ CHỐI rõ:**
- **"Draft section-by-section" + Section Expansion Prompt** ("Expand section 3 into 250 words...") — nguồn khuyến nghị soạn TỪNG SECTION riêng qua NHIỀU lượt prompt để tránh lặp ý + giữ quyền kiểm soát sáng tạo, khác model "1 prompt viết cả bài, 1 lần" của `BuildArticleDraftPromptAction` (§4.17). Người dùng xác nhận GIỮ model hiện tại, KHÔNG thêm "Bước 2b: mở rộng 1 section" — lý do: đòi hỏi tách `approved_outline`/`drafted_article` (text tự do người dùng dán vào) thành từng section 1 cách MÁY MÓC (fragile với Markdown tuỳ ý, dễ vỡ nếu outline không theo cấu trúc chuẩn) + tăng số lượt copy-paste cho biên tập viên (N section = N lượt, thay vì 1) — đổi lấy lợi ích ("tránh lặp ý") mà outline đã có cơ chế tương đương (self-check "có phần nào trùng lặp/dư không", §4.8).
- **Mật độ từ khoá 1-2%** (Pre-Publication SEO Checklist của nguồn) — **MÂU THUẪN TRỰC TIẾP** với quyết định đã chốt ở §4.14 (đối chiếu moodymedia.io/Google Search Central: "không có ngưỡng % bắt buộc, tránh nhồi từ khoá"). GIỮ quyết định §4.14 — không áp dụng số % cụ thể của nguồn này, tránh 2 nguồn tham khảo mâu thuẫn cùng tồn tại trong 1 module (nếu tương lai có bằng chứng thực tế mạnh hơn ủng hộ 1 ngưỡng % cụ thể, cần 1 quyết định RIÊNG thay đổi §4.14, không lặng lẽ ghi đè qua nguồn này).

**KHÔNG áp dụng khác:**
- **Collaborative Publishing** (real-time collaborative editing, version control, vai trò writer/editor/SEO-specialist/SME riêng, multi-modal workspace, publishing workflow management) — module KHÔNG có owner-based ACL/versioning/real-time editing (đã chốt §2.1/§9) — đây là mô tả 1 SẢN PHẨM/PLATFORM khác hẳn tầng "sinh prompt cho 1 bài" của module này.
- **Hemingway Editor** (tool đo readability cụ thể được nguồn nhắc tên) — tool ngoài, module không tích hợp tool ngoài (§0) — biên tập viên tự dùng độc lập nếu cần, module chỉ hướng dẫn AI viết dễ đọc qua chỉ dẫn văn bản (§4.9's Examples Enhancement/readability đã có từ trước).
- **Performance Measurement** (metrics hiệu suất/tỷ lệ chuyển đổi/backlink) — thuộc tầng ĐO LƯỜNG hậu-publish, khác tầng "sinh outline/bài viết/soát lỗi" mà module phục vụ — cùng lý do đã loại "chấm điểm content đã publish" ở §4.10.

---

### 4.22 Gợi ý vị trí chèn nội dung gốc (personal story/case study/testimonial thật) (v1.18, đối chiếu checkcopywriting.com/write-blog-with-ai)

Nguồn là bài chia sẻ quy trình cá nhân của 1 người viết blog dùng AI (3 giai đoạn: Preparation/Drafting/Finalization) — không phải bài kỹ thuật SEO/prompt-engineering chuyên biệt như đa số nguồn trước, nhưng khớp sát tầng "viết blog bằng AI thực tế" mà module phục vụ. Đối chiếu: phần lớn kỹ thuật ĐÃ có cơ chế tương đương hoặc thuộc tầng THỦ CÔNG ngoài AI:

- "Đọc to bài viết để bắt câu nghe như máy" (Finalization) ≈ kiểm tra "robotic tone" đã có ở Rà soát cuối (§4.20).
- "AI editing chỉ nên gợi ý sửa, không tự viết lại — giữ đúng giọng văn tác giả" ≈ "request precise edits, without full rewrites" đã có ở `BuildArticleReviewPromptAction` (§4.20).
- "Xử lý TỪNG SECTION, có gate phê duyệt trước khi qua section tiếp theo" — CÙNG kỹ thuật đã đối chiếu và TỪ CHỐI ở §4.21 (nguồn qolaba.ai) — nguồn này chỉ XÁC NHẬN LẠI kỹ thuật tương tự, KHÔNG thay đổi quyết định đã chốt (lý do giữ nguyên: fragile parsing + tăng số lượt copy-paste).
- Grammarly/Quetext (kiểm tra đạo văn)/Ubersuggest (nghiên cứu từ khoá) — tool ngoài, module không tích hợp tool ngoài (§0).
- "Tránh ảnh AI-generated, tránh testimonial giả, tránh tiêu đề AI tự chọn không qua xét duyệt người" — đều đã đúng tinh thần module: chỉ GỢI Ý (alt text/schema/tiêu đề), không tự sinh asset thật, luôn cần biên tập viên xét duyệt cuối.

1 điểm THẬT áp dụng, CẢ 3 `outline_depth` (chỉ `standard`/`detailed` có bước "làm rõ nội dung heading" để gắn vào), không đổi signature/DB:

**Gợi ý vị trí chèn nội dung gốc:**
- Nguồn nhấn mạnh: personal story, testimonial THẬT (không phải do AI tạo), case study có số liệu đo được, dữ liệu/nghiên cứu độc quyền của công ty — là yếu tố khác biệt LỚN NHẤT so với nội dung AI khai thác chung từ internet (không thể bị scrape/tái tạo lại bởi AI khác, cải thiện cả hiệu suất thuật toán và mức độ tin tưởng của độc giả).
- Mở rộng "differentiation note" đã có (§4.12 — "với MỖI H2, thêm 1 câu ghi rõ phần này làm KHÁC gì so với đối thủ") thêm 1 câu: nếu 1 H2 có thể thuyết phục hơn hẳn với 1 câu chuyện/case study/testimonial THẬT của chính biên tập viên (kinh nghiệm cá nhân, khách hàng thật, dữ liệu nội bộ), đánh dấu rõ gợi ý vị trí đó để biên tập viên tự điền — KHÔNG tự tạo nội dung thay thế.
- Khác hướng với "Không bịa case study" đã có (§4.19, `Lưu ý EEAT`) — đó là GUARDRAIL cấm AI tự bịa; đây là lời MỜI chủ động, khuyến khích biên tập viên chủ động ĐIỀN nội dung thật của họ vào — 2 cơ chế bổ trợ nhau, không trùng lặp.

---

### 4.23 Nêu tên chuyên gia/tổ chức uy tín thật nếu biết (v1.19, đối chiếu tofuhq.com/post/prompt-engineering-for-blog-posts)

Nguồn liệt kê kỹ thuật prompt engineering cho blog post ở quy mô team/nhiều brand (brainstorming, writing parameters, advanced techniques). Đối chiếu: **Brainstorming** (10 ý tưởng chủ đề, keyword research chia theo funnel stage top/middle/bottom, "high-performing post" process, "Expert Citation Process") — phần "10 ý tưởng chủ đề"/"keyword theo funnel stage" thuộc phạm vi `CoreIdeaExtractor` (module này bắt đầu SAU KHI đã có `topic`/`target_keyword`, cùng lý do đã từ chối "Foundation Prompts" ở §4.21) — riêng "Expert Citation Process" (hỏi tên chuyên gia đầu ngành + nghiên cứu của họ) tách được thành 1 chi tiết PROMPT-LEVEL áp dụng ở tầng Research, xem dưới. **Writing stage** (word count/audience/tone/structure/CTA/persona) đã có cơ chế tương đương đầy đủ. **Company-Specific Data Integration** (customer review/G2 testimonial/case study thật để giảm cảm giác "do AI viết") — ĐÃ áp dụng ở §4.22 (v1.18, đối chiếu checkcopywriting.com) — nguồn này chỉ XÁC NHẬN LẠI, không đổi quyết định. **Example-Based Anchoring** (viết theo structure/style của 1 tác giả cụ thể qua link) — KHÔNG cần cơ chế mới: đã làm được qua `tone_style` (free text, người dùng tự mô tả "viết theo phong cách X, giữ format pros/cons") + `competitor_urls` (tham khảo cấu trúc) đã có sẵn — không có field nào thiếu để hỗ trợ kỹ thuật này.

1 điểm THẬT áp dụng, chỉ `standard`/`detailed` (nơi có câu "Độ tin cậy dữ liệu"), không đổi signature/DB:

**Nêu tên chuyên gia/tổ chức uy tín thật:**
- Nguồn (Expert Citation Process): hỏi AI tên chuyên gia đầu ngành trong lĩnh vực + top 3 bài nghiên cứu của họ + tóm tắt — thay vì chỉ nói chung "nghiên cứu cho thấy".
- Mở rộng "Độ tin cậy dữ liệu" đã có (§4.7/§4.10 — "gợi ý trích dẫn 2-3 nguồn uy tín khác nhau") thêm: nếu biết, ưu tiên nêu TÊN chuyên gia/tổ chức uy tín THẬT trong lĩnh vực, không chỉ nói chung — cùng guardrail nhất quán với "không bịa số liệu": không chắc thì bỏ qua, KHÔNG tự bịa tên (chuyên gia giả là rủi ro EEAT nghiêm trọng hơn cả số liệu giả — gắn danh tính THẬT vào nội dung sai sự thật).

**KHÔNG áp dụng:**
- **"Mention keyword x, y, z ít nhất 3 lần xuyên suốt bài"** (SEO Integration của nguồn) — MÂU THUẪN với quyết định đã chốt ở §4.14/§4.21 (không có ngưỡng lặp từ khoá bắt buộc, tránh nhồi từ khoá) — GIỮ quyết định cũ, không áp dụng số lần lặp cụ thể của nguồn này, cùng lý do đã từ chối "mật độ 1-2%" ở §4.21.
- **Brainstorming stage** (10 ý tưởng chủ đề, keyword theo funnel stage, "high-performing post" process) — thuộc phạm vi `CoreIdeaExtractor`/`VideoIdeaExtractor`, không phải `ContentOutlines` (module này CHỈ bắt đầu SAU KHI đã chọn topic).
- **Positive framing principle** ("specify 'family-friendly' rather than 'avoid inappropriate content'") — đây là nguyên tắc VIẾT PROMPT dành cho NGƯỜI VIẾT prompt (áp dụng khi lập trình viên soạn văn bản `BuildContentOutlinePromptAction`), không phải 1 chỉ dẫn cần đưa vào NỘI DUNG prompt sinh ra cho AI — đã tự nhiên áp dụng qua việc review văn bản hiện có, không cần 1 mục riêng.

---

### 4.24 Rà soát rủi ro nội bộ — cascade khi regenerate + UX trang Show (v1.20)

Người dùng tự rà soát spec/code (không đối chiếu nguồn ngoài) và nêu 5 rủi ro. Xử lý từng điểm:

**(1) Prompt Complexity / Instruction Overload ở `detailed` (rủi ro cao nhất hiện tại, THEO DÕI — không code):**
- Đúng thực tế: `detailed` đã tích luỹ RẤT NHIỀU chỉ dẫn qua 20 version (structure archetype, answer-first, SERP feature, Content-H3, differentiation note, before-after, infographic, testimonial thật, tên chuyên gia, ±10%...).
- Rủi ro: AI ngoài "instruction overload" → bỏ sót 1 số yêu cầu quan trọng — KHÁC rủi ro "Prompt length" đã ghi ở §4.1 (đó là tổng SỐ TỪ, đây là SỐ LƯỢNG chỉ dẫn riêng biệt — 1 prompt có thể ngắn về từ nhưng dày đặc chỉ dẫn).
- **Quyết định:** GHI NHẬN vào spec (đoạn này), KHÔNG cắt giảm chỉ dẫn nào ngay — người dùng tự đề xuất "theo dõi thực tế... cân nhắc giảm NẾU thấy AI bỏ sót", đây là hành động CÓ ĐIỀU KIỆN dựa trên dữ liệu sử dụng thật chưa có, cùng nguyên tắc "không thêm/bớt engineering khi chưa có bằng chứng cần" đã áp dụng nhiều lần trong module. Để mở cho version sau nếu người dùng quan sát AI ngoài bỏ sót chỉ dẫn thật.

**(2) Maintainability của 3 biến thể BOTTOM (technical debt, KHÔNG bắt buộc v1 — không code):**
- Đúng thực tế: sửa 1 chỉ dẫn CHUNG (VD dòng EEAT) phải sửa lặp lại ở CẢ 3 `buildBottomBrief()`/`buildBottomStandard()`/`buildBottomDetailed()` (đã xảy ra nhiều lần — §4.16/§4.19/§4.23 đều phải `replace_all` qua 2-3 vị trí gần giống nhau).
- Người dùng tự đề xuất 2 hướng (không bắt buộc): extract khối chung thành constant/Markdown partial, HOẶC 1 template chính + flag `outline_depth` bật/tắt section.
- **Quyết định:** GHI NHẬN là technical debt CÓ THẬT, CHƯA refactor — đây là thay đổi cấu trúc 3 template lớn cùng lúc (rủi ro làm lệch nội dung nếu refactor sai), cần 1 quyết định RIÊNG (có thể cần xem lại toàn bộ 3 template để đảm bảo không bỏ sót câu nào khi tách) — không làm ngầm trong 1 lượt rà soát rủi ro chung. Để mở cho version sau nếu người dùng muốn ưu tiên.

**(3) Cascade behavior khi Regenerate Outline (XÁC NHẬN ĐÚNG — ĐÃ SỬA):**
- Đúng thực tế: `RegenerateContentOutlinePromptAction` (§4.2) CHỈ update `generated_prompt` + input fields + `updated_by` — KHÔNG đụng `approved_outline`/`article_draft_prompt`/`drafted_article`/`review_prompt` (giữ nguyên, đúng như người dùng suy luận từ code). Rủi ro thật: outline MỚI có thể không còn khớp với Bước 2/3 đã sinh dựa trên outline CŨ, nhưng UI trước đây KHÔNG cảnh báo riêng về việc này.
- **Đã sửa:** `edit.blade.php` tính `$hasDownstream` (`filled($outline->article_draft_prompt) || filled($outline->review_prompt)`), đổi `data-confirm-regenerate` từ `"1"` (message mặc định) sang message CỤ THỂ cảnh báo cascade khi `$hasDownstream` true — tái dùng CHÍNH cơ chế `data-confirm-regenerate="<message>"` đã tổng quát hoá từ §4.17, không cần sửa JS. `show.blade.php` thêm icon ⚠ + `title` tooltip trên nút "Sửa & Sinh lại" khi `$hasDownstreamSteps` true — cảnh báo NGAY tại trang xem, trước khi bấm vào trang sửa.
- **Chủ động KHÔNG làm:** tự động XOÁ/CLEAR `article_draft_prompt`/`review_prompt` khi regenerate outline — người dùng có thể có lý do chính đáng giữ lại (VD outline chỉ sửa nhẹ, Bước 2/3 vẫn còn phù hợp) — chỉ CẢNH BÁO, để biên tập viên tự quyết định, cùng triết lý "soft warning không chặn" đã áp dụng cho độ dài prompt (§4.1).

**(4) Soft warning độ dài cho Bước 2 & 3 (KHÔNG PHẢI gap — thông tin đã lỗi thời):**
- Người dùng nêu: "chưa có tương đương [`WORD_COUNT_WARNING_THRESHOLD`] cho `article_draft_prompt` và `review_prompt`".
- **Verify lại code:** ĐÃ CÓ từ trước — `BuildArticleDraftPromptAction::WORD_COUNT_WARNING_THRESHOLD = 10000` (§4.17, v1.14) + `BuildArticleReviewPromptAction::WORD_COUNT_WARNING_THRESHOLD = 12000` (§4.20, v1.16), cả 2 đều có banner cảnh báo tương ứng (`$articlePromptIsLong`/`$reviewPromptIsLong`) ở `show.blade.php` từ khi 2 Feature đó ra đời. KHÔNG có thay đổi code cho điểm này — chỉ ghi lại ở đây để xác nhận với người rà soát rằng thông tin trong báo cáo đã lỗi thời (module đã tự vá điểm này song song với việc thêm 2 Feature, không phải bỏ sót).

**(5) UX trang Show quá tải (3 khối prompt lớn + 3 textarea, ĐÃ SỬA):**
- Đúng thực tế: 3 card "Bước 1/2/3" xếp dọc, mỗi card có textarea input + (khi đã sinh) khối kết quả với 2 chế độ xem — tổng chiều cao trang lớn khi cả 3 bước đã có nội dung.
- **Đã sửa — stepper điều hướng:** thêm dải badge "Bước 1 ✓ → Bước 2[ ✓] → Bước 3[ ✓]" ngay dưới tiêu đề trang, mỗi badge là anchor link (`#buoc-1`/`#buoc-2`/`#buoc-3`) + đổi màu (`badge-success` khi bước đó đã có kết quả, `badge-ghost` khi chưa) — cho biên tập viên thấy NGAY tiến độ 3 bước mà không cần cuộn hết trang.
- **Đã sửa — collapsible:** bọc nội dung card "Bước 2"/"Bước 3" (mô tả + form + khối kết quả) trong `<details>` (native HTML, không cần thư viện JS mới) — `<summary>` hiện tiêu đề bước + badge "Đã sinh" nếu đã có prompt. Bước 2 mặc định `open` TRỪ khi `review_prompt` đã có (ngụ ý biên tập viên đã tiến sang Bước 3, tự thu gọn Bước 2 để giảm chiều cao trang — vẫn mở lại được bất kỳ lúc nào, KHÔNG mất nội dung). Bước 3 LUÔN mở (bước cuối, không có bước sau để "đẩy" nó vào nền). Bước 1 (card "Prompt đã sinh") GIỮ NGUYÊN không bọc `<details>` — đây là artifact chính người dùng cần thấy ngay khi vào trang, không cần thu gọn.
- Không đổi hành vi JS đã có (`contentOutlineCopyPrompt`/`contentOutlineDownloadPrompt`/`contentOutlineMakeCollapsible`) — `<details>` là thuần HTML/CSS, không xung đột với Alpine `x-show`/tabs bên trong.

---

### 4.25 Guardrail chống văn phong "lộ AI" — em-dash lạm dụng/từ chuyển ý sáo mòn/chuỗi câu ngắn cùng cấu trúc (v1.22, đối chiếu spec/giadinh.md — Moz Whiteboard Friday "7 Tips for Writing Great Content with ChatGPT or Gemini", Chima Mmeje)

Nguồn liệt kê 7 kỹ thuật prompt engineering khi dùng LLM viết content. Đối chiếu từng điểm với toàn bộ prompt thật đang sinh (không chỉ tên field) — 4/7 đã có tương đương đầy đủ, 2/7 là quyết định ĐÃ CHỐT trước đó (giữ nguyên, không mở lại — xem dưới), 1/7 (feedback lặp lại) là tính năng mới ngoài phạm vi v1.22, và ĐÚNG 1/7 là khoảng trống thật đủ nhỏ để áp dụng ngay:

**Đã có tương đương đầy đủ, KHÔNG cần sửa:**
- "Provide context" — §2/§4.7 (khối "Thông tin đầu vào" đầy đủ mục tiêu/audience/tone/structure).
- "Add core offerings/product-led" — cơ chế khác (AI tự đề xuất sản phẩm + `cta_url` thật, §4.10/§4.18) nhưng phục vụ đúng mục đích "chèn giá trị sản phẩm tự nhiên vào nội dung".
- "Personalize the output" — §4.22 (gợi ý vị trí chèn story/case study/testimonial THẬT của biên tập viên) khớp gần như nguyên văn.
- "Training document" (few-shot mẫu bài cũ) — có 1 phần qua `style_sample` (CategoryContentFoundation) dùng cho GIỌNG VĂN, không dùng nguyên bài — xem lý do KHÔNG mở rộng ở mục dưới.

**Đã là quyết định CHỐT trước đó, KHÔNG mở lại:**
- **"Write in small sections"** (viết từng H2 riêng, nhiều lượt copy-paste) — MÂU THUẪN trực tiếp với mô hình "1 prompt viết cả bài, 1 lần" đã chốt ở §4.17 (lý do: tránh N lượt copy-paste thủ công cho biên tập viên). Giữ nguyên quyết định §4.17.
- **Field "core offering" riêng** cho `CategoryContentFoundation` — lịch sử đã có khái niệm này (comment ở `CategoryFoundationData.php` nhắc "Business Foundation Document") nhưng đã chủ động BỎ, chỉ giữ lại UVP/goals dạng ngữ cảnh biên tập. Không mở lại — cơ chế "AI tự đề xuất sản phẩm" (§4.10) đã phục vụ đúng nhu cầu product-led mà không cần biên tập viên tự liệt kê catalog.
- **"Training document" đầy đủ** (nguyên 1 bài mẫu, không chỉ đoạn giọng văn) — CỐ Ý không mở rộng: `style_sample` hiện tại giới hạn rõ "chỉ dùng tham khảo văn phong, KHÔNG sao chép nội dung/chủ đề" (xem prompt CoreIdeaExtractor) — đây là guardrail chống đạo văn/lặp ý đã có chủ đích, nới rộng thành "dán nguyên 1 bài cũ" sẽ mở lại đúng rủi ro đó.

**Ngoài phạm vi v1.22 (tính năng mới, không phải sửa prompt text):**
- **"Give feedback lặp lại, mục tiêu ~70% chất lượng"** — đòi hỏi lưu lịch sử "đã sửa gì/vì sao" ở 1 lượt sinh để nạp ngược vào lượt sau — module hiện tại là "sinh 1 prompt độc lập, không trạng thái hội thoại" (đúng nguyên tắc §0), không có bảng nào lưu revision/feedback. Cần 1 quyết định phạm vi riêng nếu muốn làm (thêm bảng lưu feedback + logic nạp lại vào prompt lần sau) — không lồng vào 1 lượt sửa nhỏ như tại đây.

**1 điểm THẬT áp dụng — guardrail chống văn phong "lộ AI":**

Nguồn liệt kê cụ thể: lạm dụng dấu gạch ngang em-dash nối 2 vế câu, dùng đi dùng lại 1 số từ ("shape" trong ví dụ tiếng Anh của nguồn), câu ngắn cộc lốc liên tiếp — đây là các "tell" phổ biến khiến văn bản đọc rõ là do AI viết dù nội dung đúng. Module đã có 2 guardrail HẸP liên quan (cấm cliché mở bài §4.19, tự rà "robotic" ở Bước 3 §4.20) nhưng chưa liệt kê cụ thể các dấu hiệu văn phong này.

- **`BuildArticleDraftPromptAction`** (Bước 2, sinh bài mới): thêm 1 bullet mới ngay sau bullet "Câu chủ động, ngắn gọn..." — cấm cụ thể: (1) lạm dụng em-dash "—" nối 2 vế câu (thỉnh thoảng 1 câu dùng được, không lặp lại liên tục nhiều câu); (2) mở nhiều đoạn liên tiếp bằng cùng 1 từ chuyển ý sáo mòn ("Hơn nữa"/"Bên cạnh đó"/"Không chỉ vậy"/"Tóm lại"...); (3) chuỗi câu ngắn liên tiếp cùng 1 cấu trúc ngữ pháp (chủ ngữ-động từ-tân ngữ lặp lại) — yêu cầu xen kẽ độ dài câu tự nhiên như người viết thật.
- **`BuildArticleReviewPromptAction`** (Bước 3, soát bài đã viết): mở rộng mục "3. Rà soát cuối" — bên cạnh "robotic/máy móc" đã có, chỉ rõ 3 dấu hiệu cụ thể ở trên để AI review chủ động tìm VÀ đề xuất câu thay thế, không chỉ nói chung "đọc chưa tự nhiên".
- Không đổi signature/DB, không thêm field mới — chỉ mở rộng text tĩnh trong 2 Action đã có, cùng cách §4.19/§4.23 đã làm.

---

### 4.26 Guardrail câu ≤20 từ + tránh thuật ngữ mơ hồ không có ngữ cảnh cụ thể (v1.23, đối chiếu spec/giadinh.md — bài phân tích xu hướng "zero-click search"/AI visibility, cùng tác giả nguồn đã đối chiếu ở §4.22)

Nguồn khuyến nghị 2 điểm ở tầng CÂU (khác các guardrail đã có, vốn ở tầng ĐOẠN — "60-100 từ/đoạn" §4.6, hoặc tầng VĂN PHONG TOÀN BÀI — em-dash/từ chuyển ý sáo mòn §4.25): (1) tránh câu dài quá ~20 từ — câu dài nhiều mệnh đề khó để AI answer engine trích nguyên văn làm câu trả lời trực tiếp; (2) tránh thuật ngữ mơ hồ không kèm ngữ cảnh cụ thể (VD nói "chiến lược marketing" mà không nói rõ chiến lược nào/áp dụng cho ai) — nội dung mơ hồ vừa khó bị AI trích, vừa ít giá trị thật với người đọc.

**1 điểm THẬT áp dụng — cả 2 vế trên, cùng vị trí đã sửa ở §4.25 (không tách guardrail riêng để tránh module tích luỹ quá nhiều chỉ dẫn rời rạc — rủi ro "instruction overload" đã ghi nhận ở §4.24 mục 1):**

- **`BuildArticleDraftPromptAction`** (Bước 2): mở rộng NGAY bullet "Tránh dấu hiệu văn phong 'lộ AI' phổ biến" đã thêm ở §4.25 — bổ sung thêm 2 ý: (4) câu không quá ~20 từ, câu dài cần tách thành 2 câu ngắn hơn; (5) không dùng thuật ngữ/cụm từ chung chung mà không nói rõ áp dụng cho trường hợp/đối tượng nào cụ thể.
- **`BuildArticleReviewPromptAction`** (Bước 3): mở rộng mục "2. Đánh giá độ dễ đọc (readability)" đã có sẵn câu hỏi "câu/đoạn nào quá dài nên chia nhỏ?" — chỉ rõ thêm ngưỡng cụ thể ~20 từ/câu thay vì để mơ hồ "quá dài" chung chung, và thêm câu hỏi rà thuật ngữ mơ hồ thiếu ngữ cảnh.
- `GeoChecklist.php` — thêm 1 tiêu chí mới trong nhóm "Cấu trúc & câu trả lời" nhắc lại đúng 2 ý này cho biên tập viên tự đối chiếu khi không qua `ContentOutlines` (viết tay/dùng AI ngoài không qua module).
- Không đổi signature/DB, không thêm field mới.

---

## 5. Module `ContentFoundation` — điểm tích hợp

- **Picker chuyên mục ở form**: dùng `Modules\ContentFoundation\Actions\ListCategoryFoundationsAction::handle(withFoundationDetails: false)` — cùng cách `CoreIdeaExtractor` tải danh sách rút gọn lúc mở trang (§12.2 tài liệu tham khảo).
- **Full detail khi chọn 1 category**: gọi `GET backend/api/content-foundation/category-foundations/{category}` (route đã có sẵn của `ContentFoundation`, KHÔNG tạo route trùng lặp trong `ContentOutlines`) — JS của `ContentOutlines` tự fetch on-demand giống `applyCategoryFoundation()` của `CoreIdeaExtractor`.
- **KHÔNG cần Gate mới** — `content_foundation.use` (đọc) đã đủ cho nhu cầu đọc-để-tham-khảo của module này; module KHÔNG cho sửa `CategoryContentFoundation` (việc đó vẫn thuộc trang `dashboard/content-foundation`).

---

## 6. Cấu trúc module (AVSA + CQRS-lite)

```
Modules/ContentOutlines/
  app/
    Models/
      ContentOutline.php
    Features/
      Concerns/                                  // §4.17 (v1.14) — dùng chung ≥2 Feature, dời/tách lên đây
        BuildsSharedPromptBlocks.php             // estimateWordCount() + buildFamilyValuesBlock() — tách từ BuildContentOutlinePromptAction khi BuildArticleDraftPromptAction cần cùng 2 hàm
        ResolvesCategoryContext.php              // dời từ OutlineGeneration/Actions/Concerns/ — foundation + existing article titles (§4.6, v1.2), giờ dùng chung OutlineGeneration + ArticleDrafting
      OutlineGeneration/
        Actions/
          BuildContentOutlinePromptAction.php   // §3/§4.1 — use BuildsSharedPromptBlocks
          CreateContentOutlineAction.php        // validate input → build prompt → lưu bản ghi
          RegenerateContentOutlinePromptAction.php // sửa input → build lại prompt → ghi đè generated_prompt (§4.2)
          LinkContentOutlineToArticleAction.php // gắn/gỡ linked_post_article_id (§4.4 — 1-1)
        Data/
          ContentOutlineInputData.php            // §3.1 — DTO input dùng chung Create/Regenerate/ArticleDrafting, có outline_depth
        Http/
          Requests/
            StoreContentOutlineRequest.php
            UpdateContentOutlineRequest.php
          ContentOutlineController.php           // trang list/create/edit/show + store/update/destroy/link/generateArticlePrompt (§4.17)/generateReviewPrompt (§4.20)
          ContentOutlineApiController.php         // JSON Tabulator cho trang danh sách
          Resources/
            ContentOutlineListResource.php        // + is_long_prompt (§4.1)
        Queries/
          ListContentOutlinesForAdminQuery.php
          ListContentOutlinesForAdminHandler.php
      ArticleDrafting/                           // §4.17 (v1.14) — "Bước 2": sinh prompt viết bài từ outline đã duyệt
        Actions/
          BuildArticleDraftPromptAction.php       // use BuildsSharedPromptBlocks — persona viết + outline nhúng sẵn + guardrail + family values
          SaveApprovedOutlineAndBuildArticlePromptAction.php // use ResolvesCategoryContext — lưu approved_outline + build + ghi đè article_draft_prompt
        Http/
          Requests/
            StoreApprovedOutlineRequest.php
      ArticleReview/                              // §4.20 (v1.16) — "Bước 3": sinh prompt soát lỗi/sửa cho bài đã viết xong
        Actions/
          BuildArticleReviewPromptAction.php       // use BuildsSharedPromptBlocks — SEO+readability+final-edit gộp 1 prompt, yêu cầu precise edits
          SaveDraftedArticleAndBuildReviewPromptAction.php // use ResolvesCategoryContext — lưu drafted_article + build + ghi đè review_prompt
        Http/
          Requests/
            StoreDraftedArticleRequest.php
    Providers/
      ContentOutlinesServiceProvider.php   // Gate::define không cần (dùng permission string trực tiếp, cùng CoreIdeaExtractor) — chỉ đăng ký RouteServiceProvider
      RouteServiceProvider.php
  database/
    migrations/
      xxxx_create_content_outlines_table.php
      xxxx_add_outline_depth_to_content_outlines_table.php   // §4.1 — migration riêng, sau khi bảng đã tồn tại
      xxxx_add_content_role_to_content_outlines_table.php    // §4.9 — migration riêng, sau outline_depth
      xxxx_add_article_drafting_fields_to_content_outlines_table.php // §4.17 (v1.14) — approved_outline + article_draft_prompt
      xxxx_add_cta_url_to_content_outlines_table.php // §4.18 (v1.15) — URL CTA thật
      xxxx_add_article_review_fields_to_content_outlines_table.php // §4.20 (v1.16) — drafted_article + review_prompt
    seeders/
      ContentOutlinesPermissionSeeder.php   // content_outlines.use → platform_content_editor/head/section_editor (§7)
  resources/
    views/
      index.blade.php    // danh sách (Tabulator) + modal xoá hiện label/topic (§4.3)
      create.blade.php / edit.blade.php  // dùng chung _form.blade.php
      _form.blade.php    // + outline_depth + ước lượng độ dài client-side (§4.1)
      show.blade.php      // prompt thô/preview Markdown collapsible + copy + download .md (§4.5) + banner cảnh báo dài (§4.1) + gắn bài viết (§4.4) + card "Bước 2 — Viết bài từ outline" (§4.17) + card "Bước 3 — Soát lỗi/Sửa bài" (§4.20) + stepper Bước 1→2→3 + <details> collapsible Bước 2/3 (§4.24)
    assets/js/content-outlines.js  // §4.17 — 3 hàm copy/download/collapsible tổng quát hoá theo elId/containerId, dùng chung 2 khối prompt
  routes/
    web.php
  tests/
    Feature/
      ContentOutlineAdminTest.php
    Unit/
      BuildContentOutlinePromptActionTest.php
      BuildArticleDraftPromptActionTest.php      // §4.17 (v1.14)
      BuildArticleReviewPromptActionTest.php     // §4.20 (v1.16)
  module.json
```

`module.json` — `alias: "content-outlines"`, `providers: ["Modules\\ContentOutlines\\Providers\\ContentOutlinesServiceProvider", "Modules\\ContentOutlines\\Providers\\RouteServiceProvider"]`.

---

## 7. RBAC

- **Permission mới**: `content_outlines.use` (`App\Enums\PermissionEnum::CONTENT_OUTLINES_USE`) — seed qua `ContentOutlinesPermissionSeeder`, gán cho `platform_content_editor`/`platform_content_head`/`platform_section_editor` — **CÙNG NHÓM CHÍNH XÁC** với `CORE_IDEA_EXTRACTOR_USE`/`VIDEO_IDEA_EXTRACTOR_USE`/`CONTENT_FOUNDATION_USE` (không phải permission mới độc lập theo `config/permissions.php` Lớp B).
- Route gate phẳng bằng middleware `can:content_outlines.use` (không cần Policy riêng theo model — §2.1 đã chốt không có owner-based ACL, §4.3 ghi rõ rủi ro đi kèm).
- Sidebar: đặt cùng nhóm "Trích ý bài viết"/"Content Foundation"/"Trích ý video" (route `backend.contentoutlines.index`, nhãn **"Dàn ý nội dung"**), gate bằng `@can(\App\Enums\PermissionEnum::CONTENT_OUTLINES_USE->value)`.
- **§4.17 (v1.14)** — route `generate-article-prompt` ("Bước 2") dùng CHUNG permission `content_outlines.use`, KHÔNG seed permission mới — đây là hành động trên CÙNG resource `ContentOutline` (cùng ai xem/sửa outline thì cũng dùng được "Bước 2"), không phải tính năng độc lập cần phân quyền riêng.
- **§4.20 (v1.16)** — route `generate-review-prompt` ("Bước 3") cùng lý do, dùng CHUNG `content_outlines.use`.

---

## 8. Routes, Validation, Views

### 8.1 `StoreContentOutlineRequest`

```php
'label'                => ['nullable', 'string', 'max:200'], // rỗng → dùng topic làm label (CreateContentOutlineAction)
'topic'                => ['required', 'string', 'max:300'],
'target_keyword'       => ['required', 'string', 'max:150'],
'secondary_keywords'   => ['nullable', 'string', 'max:500'],
'search_intent'        => ['nullable', Rule::in(['informational', 'commercial', 'transactional', 'navigational', 'comparison'])],
'post_category_uuid'   => ['nullable', 'string', 'uuid', 'exists:post_categories,uuid'],
'target_audience'      => ['nullable', 'string', 'max:500'],
'content_goal'         => ['nullable', 'string', 'max:2000'],
// §4.18 (v1.15) — URL DUY NHẤT dùng để chèn vào câu CTA, KHÁC competitor_urls (free text) —
// validate url() thật vì sẽ nhúng thẳng vào câu văn cuối outline/bài viết.
'cta_url'              => ['nullable', 'url', 'max:500'],
'tone_style'           => ['nullable', 'string', 'max:2000'],
// KHÔNG validate 'url' cho từng dòng competitor_urls — người dùng có thể dán URL kèm ghi chú
// (VD "https://... (bài này thiếu ví dụ thực tế)"), ép format URL thuần sẽ cản việc đó; đây là
// text tự do CHO AI đọc, không phải link app tự bấm được.
'competitor_urls'      => ['nullable', 'string', 'max:2000'],
'desired_word_count'   => ['nullable', 'integer', 'min:100', 'max:20000'],
'language'             => ['nullable', Rule::in(['vi', 'en'])],
// §4.1 (v1.1) — kiểm soát độ dài prompt (BuildContentOutlinePromptAction).
'outline_depth'        => ['nullable', Rule::in(['brief', 'standard', 'detailed'])],
// §4.9 (v1.6) — định hướng chiều internal link (Pillar↔Cluster).
'content_role'         => ['nullable', Rule::in(['pillar', 'cluster'])],
'additional_notes'     => ['nullable', 'string', 'max:2000'],
```

`UpdateContentOutlineRequest` — cùng rules, dùng khi sửa input rồi "Sinh lại" (`RegenerateContentOutlinePromptAction`, §4.2).

**§4.17 (v1.14) — `StoreApprovedOutlineRequest`:**

```php
// max:50000 là ngưỡng AN TOÀN CỨNG (chặn dán nhầm cả 1 trang web/tài liệu không liên quan) —
// KHÁC BuildArticleDraftPromptAction::WORD_COUNT_WARNING_THRESHOLD (soft warning, không chặn).
'approved_outline' => ['required', 'string', 'max:50000'],
```

**§4.20 (v1.16) — `StoreDraftedArticleRequest`:**

```php
// max:100000 — cao hơn StoreApprovedOutlineRequest (1 BÀI VIẾT hoàn chỉnh thường dài hơn 1 outline).
'drafted_article' => ['required', 'string', 'max:100000'],
```

### 8.2 Routes (`routes/web.php`)

```php
Route::middleware(['auth', 'can:content_outlines.use'])
    ->prefix('dashboard/content-outlines')
    ->name('backend.contentoutlines.')
    ->group(function (): void {
        Route::get('/', [ContentOutlineController::class, 'index'])->name('index');
        Route::get('create', [ContentOutlineController::class, 'create'])->name('create');
        Route::post('/', [ContentOutlineController::class, 'store'])->name('store');
        Route::get('{outline}', [ContentOutlineController::class, 'show'])->name('show');
        Route::get('{outline}/edit', [ContentOutlineController::class, 'edit'])->name('edit');
        Route::put('{outline}', [ContentOutlineController::class, 'update'])->name('update'); // = "Sinh lại"
        Route::delete('{outline}', [ContentOutlineController::class, 'destroy'])->name('destroy');
        Route::post('{outline}/link-article', [ContentOutlineController::class, 'linkArticle'])->name('link-article');
        // §4.17 (v1.14) — "Bước 2": lưu approved_outline + sinh article_draft_prompt.
        Route::post('{outline}/article-prompt', [ContentOutlineController::class, 'generateArticlePrompt'])->name('generate-article-prompt');
        // §4.20 (v1.16) — "Bước 3": lưu drafted_article + sinh review_prompt.
        Route::post('{outline}/review-prompt', [ContentOutlineController::class, 'generateReviewPrompt'])->name('generate-review-prompt');
    });

Route::middleware(['auth', 'can:content_outlines.use'])
    ->prefix('backend/api/content-outlines')
    ->name('backend.api.contentoutlines.')
    ->group(function (): void {
        Route::get('items', [ContentOutlineApiController::class, 'index'])->name('items');
    });
```

`{outline}` bind theo `uuid` (`getRouteKeyName()`, cùng quy ước `PostCategory`/`N8nConnection`).

### 8.3 Views

- `index.blade.php` — `@extends('layouts.backend')`, Tabulator danh sách (cột: label + badge "dài" nếu vượt ngưỡng §4.1, topic, target_keyword, category, ngày tạo, người tạo, đã gắn bài viết?), nút "Tạo dàn ý mới"; modal xoá hiện RÕ label/topic (§4.3).
- `create.blade.php`/`edit.blade.php` dùng chung `_form.blade.php` (picker category qua JS gọi `ContentFoundation` API, cùng pattern `CoreIdeaExtractor`; field `outline_depth` + ước lượng độ dài client-side, §4.1; field `cta_url` §4.18 v1.15, đặt cạnh "Mục tiêu bài viết"). `edit.blade.php` gắn `data-confirm-regenerate` (§4.2) — message ĐỘNG cảnh báo cascade Bước 2/3 khi đã có (§4.24, v1.20).
- `show.blade.php` — toggle "Prompt thô" (textarea readonly, dùng Copy)/"Xem trước Markdown" (`Str::markdown()`, collapsible theo `## `, §4.5); nút "Copy"/"Download .md"; banner cảnh báo khi prompt vượt ngưỡng độ dài (§4.1); nút "Sửa & Sinh lại" (điều hướng `edit`); form "Gắn vào bài viết" (1-1, §4.4, dán UUID `PostArticle`, gọi `link-article`); hiện `cta_url` ở panel chi tiết nếu có (§4.18, v1.15). **§4.17 (v1.14)** — thêm card "Bước 2 — Viết bài từ outline": `<textarea name="approved_outline">` (prefill `$outline->approved_outline`) trong `<form>` POST `generate-article-prompt`, `data-confirm-regenerate="<message riêng>"` CHỈ gắn khi `article_draft_prompt` đã tồn tại; nếu đã có `article_draft_prompt` — hiện CÙNG UX toggle/copy/download/banner với khối outline, dùng 3 hàm JS đã tổng quát hoá theo `elId`/`containerId` (`content-outline-article-prompt`/`content-outline-article-preview`/`content-outline-article-copy-btn`). **§4.20 (v1.16)** — thêm card "Bước 3 — Soát lỗi/Sửa bài", đối xứng hoàn toàn với card "Bước 2" (`<textarea name="drafted_article">`, `data-confirm-regenerate` khi đã có `review_prompt`, hiện kết quả với `elId`/`containerId` `content-outline-review-*`).

---

## 9. Ngoài phạm vi (v1 - v1.21)

- Gọi AI Provider trong app (§0) — **kể cả cho Feature `ArticleDrafting`/"Bước 2" (§4.17, v1.14) và `ArticleReview`/"Bước 3" (§4.20, v1.16)**: dù mục đích cuối là "viết bài"/"soát lỗi", module VẪN CHỈ sinh 1 prompt, không tự chạy AI. Đã hỏi rõ người dùng trước khi triển khai cả 2 lần — cả 2 lần xác nhận mục tiêu CHỈ là sinh prompt.
- **Tự động áp dụng đề xuất sửa từ `review_prompt` vào `drafted_article`/`PostArticle`** (§4.20, v1.16) — Action chỉ SINH đề xuất, biên tập viên tự đọc và áp dụng (hoặc không) — không có cơ chế "apply fix" tự động nào.
- Tự fetch/crawl nội dung `competitor_urls`.
- Tự tạo `PostArticle` từ outline.
- Versioning nhiều bản outline theo thời gian (§4.2 — "Sinh lại" ghi đè, không giữ lịch sử).
- Owner-based ACL trên từng outline (§2.1/§4.3 — chấp nhận rủi ro xoá nhầm, giảm thiểu bằng modal xác nhận rõ tên, KHÔNG bằng phân quyền).
- **Soft-delete** — để mở cho version sau nếu phản hồi thật cho thấy xoá-nhầm xảy ra đủ nhiều để đáng đánh đổi thêm phức tạp (§4.3).
- **Liên kết `PostArticle` dạng N-N** (1 outline dùng cho nhiều biến thể bài viết) — v1-v1.4 chỉ hỗ trợ 1-1 (§4.4); muốn gắn cho biến thể thứ 2 phải tạo outline riêng hoặc chấp nhận chỉ theo dõi được 1 liên kết tại 1 thời điểm.
- **Content planning/lịch xuất bản/ROI/CRM** (4/6 mục của piperocket.digital checklist, §4.6) — thuộc tầng "chọn chủ đề nào, khi nào publish, đo lường sau publish", khác tầng "sinh outline cho 1 chủ đề ĐÃ CHỌN" mà module này phục vụ; nếu cần, thuộc phạm vi module `ContentCalendar` đã có sẵn trong codebase, không phải `ContentOutlines`.
- **Dàn ý khoá học (course outline — module/lesson/quiz/SMART objectives/drip-feed/certification, đối chiếu creatorlms.net ở changelog v1.4)** — SAI LOẠI NỘI DUNG (module này sinh outline cho bài viết, không phải khoá học) VÀ không có module Course/LMS nào trong codebase để áp dụng; nếu tương lai codebase có module khoá học, đây là nguồn tham khảo tốt cho module ĐÓ, không phải `ContentOutlines`.
- **Định dạng outline "chính thức" kiểu academic** (số La Mã/chữ hoa/số Ả Rập phân cấp, §4.7) + **kết luận/tóm tắt riêng cho MỖI H2** — module chỉ sinh Markdown H1-H3 cho blog/SEO, không cần định dạng academic; kết luận riêng mỗi H2 sẽ làm outline dài dòng, ngược mục tiêu "trả lời câu hỏi chính SỚM".
- **Content audit/chọn pillar/PESO distribution/đo ROI theo quý cấp chương trình** (11/12 bước của umbrex.com content-pillars framework, §4.9) — thuộc tầng chương trình nội dung cấp SITE (nhiều bài, nhiều quý), khác tầng "sinh outline cho 1 bài đã chọn"; chỉ mô hình internal-link Pillar↔Cluster ở MỨC 1 BÀI được đưa vào (`content_role`).
- **Chấm điểm chất lượng nội dung ĐÃ PUBLISH** (thang 0-15, 5 yếu tố × 0-3 điểm, quyết định xoá/viết lại/cập nhật/quảng bá/tái sử dụng — đối chiếu 8bitcontent.com, §4.10) — bản chất là tool AUDIT hậu-publish, khác tầng "sinh outline trước khi viết"; chỉ lấy 2/5 yếu tố làm CHỈ DẪN nội dung outline (CTA, độ tin cậy dữ liệu), không lấy cơ chế chấm điểm.
- **Kiến trúc AI-prompt 3-giai đoạn có approval gate + tổng hợp 7 loại tài liệu tham khảo + so sánh/hợp nhất đa AI model + phân loại entity Essential/Optional** (đối chiếu advancedwebranking.com/blog/ai-generated-content-prompts-framework, §4.11) — quy trình biên tập THỦ CÔNG qua nhiều phiên chat AI có con người xét duyệt giữa các bước, khác model "sinh 1 prompt, chạy 1 lần" đã chốt ở §0; chỉ lấy 4 chi tiết prompt-level tách được khỏi kiến trúc đó (answer-first, lưu ý AI answer engine, chặn bịa số liệu, list lead-in + sai số ±10%).
- **Kiến trúc outlining 4-bước tách rời (Intent Map → Structure Selection → Section Briefs → H3 Logic), mỗi bước 1 prompt/1 lượt gọi AI riêng** (đối chiếu aiexecutionhub.com/blog/ai-blog-post-outlining-system, §4.12) — quy trình biên tập THỦ CÔNG qua nhiều lượt gọi AI có con người chuyển tiếp dữ liệu giữa các bước, khác model "sinh 1 prompt, chạy 1 lần" đã chốt ở §0; chỉ lấy 6 chi tiết prompt-level gộp được vào CÙNG 1 prompt hiện có (structure archetype, intent map 3 câu hỏi, Content-H3, differentiation note, FAQ nguồn PAA thật, anchor text).
- **Tích hợp tool SEO ngoài** (Ahrefs/SEMrush/Surfer SEO/Clearscope/Google Search Console, đối chiếu tangence.in/blog/seo-content-creation, §4.13) — module chỉ SINH prompt text, không gọi API/tool ngoài nào (§0); các tool này người dùng tự dùng độc lập nếu cần.
- **Audit/cập nhật content ĐÃ PUBLISH theo lịch cố định (6-12 tháng)** (đối chiếu tangence.in, §4.13) — thuộc tầng vận hành HẬU-publish, khác tầng "sinh outline trước khi viết" mà module này phục vụ; cùng lý do đã loại "chấm điểm content đã publish" ở §4.10.
- **Kiến trúc so sánh 4 nguồn (Google Search Central/Ahrefs/Semrush/Bruce Clay/SEO.com) + workflow tự học SEO cho beginner** (đối chiếu moodymedia.io/blog/how-to-write-for-seo, §4.14) — nội dung giáo dục/định hướng nghề nghiệp, không phải chỉ dẫn kỹ thuật cho 1 outline cụ thể; chỉ lấy 4 điểm on-page cụ thể (từ khoá gần đầu, Meta 140-160+CTA, keyword trong 150 từ đầu, chặn nhồi từ khoá).
- **Tự động tạo/cập nhật `PostArticle` từ `article_draft_prompt`/kết quả AI trả về** (§4.17, v1.14) — giữ đúng non-goal "Tự tạo `PostArticle` từ outline" đã có từ v1.0, mở rộng sang cả kết quả của "Bước 2".
- **Versioning nhiều bản `article_draft_prompt`** (§4.17, v1.14) — cùng quyết định không-versioning đã áp dụng cho `generated_prompt`.
- **Social snippet (X/Twitter, LinkedIn) + tags cho mạng xã hội** (đối chiếu "10-step AI prompt chain", §4.18, v1.15) — người dùng xác nhận KHÔNG muốn thêm khi được hỏi trực tiếp; đây là artifact PHÂN PHỐI/QUẢNG BÁ, khác tầng "outline + bài viết cho 1 bài" mà module phục vụ, cùng lý do đã từ chối phần lớn checklist content-marketing tổng thể ở §4.6.
- **Tự động hoá chạy chuỗi 10 prompt trong AI ngoài** (bản chất sản phẩm quảng cáo bên thứ 3 trong nguồn của §4.18) — vi phạm trực tiếp §0 mục 1 (không gọi AI Provider trong app); module KHÔNG xác nhận/đánh giá/tích hợp bất kỳ công cụ bên thứ 3 nào được quảng cáo trong các nguồn tham khảo.
- **"Draft section-by-section" + Section Expansion Prompt** (đối chiếu blog.qolaba.ai, §4.21) — người dùng xác nhận GIỮ model "1 prompt viết cả bài" hiện tại, KHÔNG thêm "Bước 2b" tách theo section — lý do đầy đủ ở §4.21.
- **Mật độ từ khoá theo % cụ thể (1-2%)** (đối chiếu blog.qolaba.ai, §4.21) — MÂU THUẪN với §4.14 (đã chốt "không có ngưỡng % bắt buộc") — giữ nguyên §4.14, không áp dụng.
- **Collaborative Publishing** (real-time editing/version control/vai trò team riêng, đối chiếu blog.qolaba.ai, §4.21) — mô tả 1 sản phẩm/platform khác hẳn tầng "sinh prompt cho 1 bài" của module.
- **Grammarly/Quetext (đạo văn)/Ubersuggest (từ khoá)** (đối chiếu checkcopywriting.com, §4.22) — tool ngoài, module không tích hợp tool ngoài (§0); biên tập viên tự dùng độc lập nếu cần.
- **"Mention keyword ít nhất 3 lần xuyên suốt bài"** (đối chiếu tofuhq.com, §4.23) — MÂU THUẪN với §4.14/§4.21 (không có ngưỡng lặp từ khoá bắt buộc) — giữ nguyên quyết định cũ, không áp dụng.
- **Extract 3 biến thể BOTTOM thành constant/partial chung** (§4.24, v1.20 — technical debt đã ghi nhận) — CHƯA làm, để mở cho version sau nếu người dùng muốn ưu tiên; cần 1 quyết định RIÊNG (rủi ro lệch nội dung khi refactor 3 template lớn cùng lúc).
- **Cắt giảm chỉ dẫn ở `detailed` để giảm instruction overload** (§4.24, v1.20) — CHƯA làm, đây là hành động CÓ ĐIỀU KIỆN chờ dữ liệu sử dụng thật cho thấy AI ngoài bỏ sót chỉ dẫn — không cắt giảm khi chưa có bằng chứng.
- **Tự động XOÁ/CLEAR Bước 2/3 khi regenerate outline** (§4.24, v1.20) — chủ động KHÔNG làm, chỉ CẢNH BÁO — biên tập viên có thể có lý do chính đáng giữ lại Bước 2/3 dù outline đã đổi nhẹ.
- Đối chiếu framework/nguồn bổ sung khác ngoài các nguồn đã dẫn ở §0/§4.6/§4.7/§4.9/§4.10/§4.11/§4.12/§4.13/§4.14/§4.15/§4.18/§4.19/§4.21/§4.22/§4.23 — để mở cho các vòng tinh chỉnh sau dựa trên phản hồi sử dụng thật (cùng tinh thần "Adaptive" đã áp dụng cho `CoreIdeaExtractor.md`, xem changelog module đó).

---

## 10. Testing

- **`BuildContentOutlinePromptActionTest` (Unit)**: input tối thiểu (chỉ topic+keyword) → prompt vẫn hợp lệ, các khối optional bị bỏ đúng cách khi field null; input đầy đủ + có `$foundation` → MIDDLE xuất hiện đủ các gợi ý map từ `pain_points`/`objections`/`decision_criteria`; `$foundation = null` → MIDDLE chỉ còn khối "Hệ giá trị gia đình" (vẫn LUÔN xuất hiện, §3.2); `language = 'en'` → TOP ghi "English". **(v1.1 bổ sung)**: `outline_depth = 'brief'` → field foundation bị cắt đúng ngưỡng (300 ký tự) + số `competitor_urls` bị giới hạn đúng (3); `outline_depth = 'detailed'` → BOTTOM có "Đánh giá độ khó cạnh tranh"; `estimateWordCount()` đếm đúng với chuỗi tiếng Việt có dấu. **(v1.2 bổ sung)**: `existingArticleTitles` không rỗng → khối "Bài viết đã có trong chuyên mục" xuất hiện, cắt đúng theo `outline_depth`; rỗng → không xuất hiện khối; "Lưu ý EEAT" xuất hiện ở CẢ 3 `outline_depth`. **(v1.3 bổ sung)**: bước "luận điểm chính" + mục `## Luận điểm chính` + chỉ dẫn "dạng ngữ pháp" (song song) xuất hiện ở CẢ 3 `outline_depth`; chỉ dẫn "3-5 điểm/H3" chỉ xuất hiện ở `standard`/`detailed` (không phải `brief`). **(v1.5 bổ sung)**: bước "USP" + mục `## USP` + ghi chú "khối lượng tìm kiếm" + named flow pattern ("giải quyết vấn đề") xuất hiện ở CẢ 3 `outline_depth`; chỉ dẫn benchmark "BẰNG hoặc NHIỀU HƠN" chỉ ở `standard`/`detailed`; self-check mở rộng ("từ khoá mục tiêu có ở tiêu đề", "bao quát các chủ đề phụ chính") chỉ ở `standard`/`detailed`. **(v1.6 bổ sung)**: `content_role = null` → không có ghi chú vai trò/placeholder rò ra ngoài; `content_role = 'pillar'`/`'cluster'` → dòng "Vai trò nội dung" ở TOP CẢ 3 depth, nhưng ghi chú "Vai trò TRỤ CỘT"/"Vai trò CỤM" ở BOTTOM chỉ xuất hiện ở `standard`/`detailed` (không ở `brief`); nội dung ghi chú đổi đúng theo `$hasExistingArticles` (có/không có bài trong "Bài viết đã có trong chuyên mục" để link tới/lên/ngang). **(v1.7 bổ sung)**: mục `## CTA` + "Độ tin cậy dữ liệu" + mốc "12 tháng" xuất hiện ở CẢ 3 `outline_depth`. **(v1.8 bổ sung)**: "answer-first" + "trả lời TRỰC TIẾP" ở bước H2 xuất hiện CẢ 3 `outline_depth`; "AI answer engine" ở bước research xuất hiện CẢ 3 `outline_depth`; "Không bịa số liệu"/"cần biên tập viên xác minh" xuất hiện CẢ 3 `outline_depth`; "danh sách/bullet"/"không thả bullet trơ trọi" chỉ ở `standard`/`detailed` (không ở `brief` — không có bước "làm rõ nội dung heading"); "±10%"/"kể cả phần mở đầu" chỉ ở `detailed` (không ở `brief`/`standard` — không có bước ước lượng số từ mỗi phần). **(v1.9 bổ sung)**: "structure archetype" + "## Kiểu bài" + liệt kê 4 kiểu bài chỉ ở `standard`/`detailed` (không ở `brief`); "BẮT ĐẦU đọc" + "bỏ bài này đi tìm bài khác" (intent map 3 câu hỏi) chỉ ở `standard`/`detailed`; "nhãn chung" (Label H3) + "400 từ" chỉ ở `standard`/`detailed`; "đối thủ điển hình" (differentiation note mỗi H2) chỉ ở `standard`/`detailed`; "THẬT quan sát được" (FAQ nguồn PAA thật) ở CẢ 3 `outline_depth`; "anchor text" chỉ ở `standard`/`detailed`. **(v1.10 bổ sung)**: "Schema markup" + "FAQPage" xuất hiện ở CẢ 3 `outline_depth`; "HowTo"/"ItemList" (theo structure archetype) chỉ ở `standard`/`detailed` (không ở `brief` — không có bước chọn kiểu bài); "alt text" chỉ ở `standard`/`detailed` (không ở `brief` — không có bước "làm rõ nội dung heading"). **(v1.11 bổ sung)**: "GẦN ĐẦU" xuất hiện ở CẢ 3 `outline_depth`; "140-160 ký tự" + "lời mời hành động" xuất hiện ở CẢ 3 `outline_depth`; "100-150 từ đầu bài" xuất hiện ở CẢ 3 `outline_depth`; "Mật độ từ khoá" + "keyword stuffing" xuất hiện ở CẢ 3 `outline_depth`. **(v1.12 bổ sung)**: "SERP feature" xuất hiện ở CẢ 3 `outline_depth` (bước Research); "LẶP LẠI" (gom nhóm heading đối thủ) chỉ ở `standard`/`detailed`; khớp định dạng featured snippet (câu "format câu trả lời mở đầu ĐÚNG dạng đó") xuất hiện ở CẢ 3 `outline_depth` (mở rộng answer-first). **(v1.13 bổ sung)**: H2 "Kết luận" (câu "Dàn ý nên khép lại bằng 1 H2 \"Kết luận\"") xuất hiện ở CẢ 3 `outline_depth`. **(v1.14 — REVERT, xem §4.17)**: khối "## Bước tiếp theo"/placeholder `{{DRAFT_PROMPT_TEMPLATE}}` của v1.13 KHÔNG còn xuất hiện trong `generated_prompt` ở CẢ 3 `outline_depth` (test v1.13 cho case này bị XOÁ, không phải bỏ sót); `estimateWordCount()` (đã dời sang `BuildsSharedPromptBlocks`, gọi qua `BuildContentOutlinePromptAction::estimateWordCount()`) vẫn hoạt động giống cũ. **(v1.15 bổ sung, §4.18)**: `cta_url` có giá trị → TOP có dòng "CTA URL:", bước CTA (CẢ 3 depth) có câu "mời truy cập ĐÚNG URL đó"; `cta_url = null` → không có dòng "CTA URL:" ở TOP, bước CTA giữ nguyên chỉ dẫn chung (không lỗi/không thừa câu); bước brainstorm tiêu đề (CẢ 3 depth) có câu "MẠNH NHẤT". **(v1.16 bổ sung, §4.19)**: "before-after" xuất hiện ở bước làm rõ heading chỉ `standard`/`detailed` (không ở `brief` — không có bước đó); "2-3 phương án Meta Title"/"2-3 phương án Meta Description" xuất hiện ở CẢ 3 `outline_depth` (thay cho 1 phương án cũ); "case study" xuất hiện trong câu "Không bịa số liệu" ở CẢ 3 `outline_depth`. **(v1.17 bổ sung, §4.21)**: "infographic" xuất hiện ở bước làm rõ heading chỉ `standard`/`detailed` (không ở `brief` — không có bước đó, giống pattern "alt text"/"before-after"). **(v1.18 bổ sung, §4.22)**: "testimonial THẬT" xuất hiện ở bước làm rõ heading (câu mở rộng differentiation note) chỉ `standard`/`detailed` (không ở `brief` — không có bước đó, cùng pattern). **(v1.19 bổ sung, §4.23)**: "TÊN chuyên gia" xuất hiện trong câu "Độ tin cậy dữ liệu" chỉ `standard`/`detailed` (không ở `brief` — dòng EEAT rút gọn của `brief` không có câu "trích dẫn 2-3 nguồn" để gắn vào, cùng tinh thần rút gọn §4.1 — KHÔNG phải bỏ sót).
- **`BuildArticleDraftPromptActionTest` (Unit, v1.14, §4.17)**: `handle()` với `$approvedOutline` bất kỳ → prompt trả về CHỨA NGUYÊN VĂN outline đó (không cắt/biến đổi); `desired_word_count = null` → dùng câu fallback "độ dài hợp lý theo outline"; có `tone_style` (hoặc fallback `foundation?->style_sample`) → xuất hiện dòng "Giọng văn"; `language = 'en'` → ghi "English"; `$foundation = null` → vẫn còn khối "Hệ giá trị gia đình Việt Nam" (LUÔN xuất hiện, cùng nguyên tắc §3.2); có "Lưu ý EEAT" + "Định dạng output" ở MỌI trường hợp; `estimateWordCount()` (qua `BuildsSharedPromptBlocks`) đếm đúng trên prompt đã sinh. **(v1.15 bổ sung, §4.18)**: `input->cta_url` có giá trị → prompt CHỨA NGUYÊN VĂN URL đó trong câu chuyển tiếp kết bài; `cta_url = null` → dùng câu fallback CTA chung (không lỗi); LUÔN có bullet "Đoạn mở bài" (hook) + bullet "scannable" (đoạn ngắn/bullet/bold) bất kể input. **(v1.16 bổ sung, §4.19)**: prompt LUÔN cấm cụ thể cụm "trong thế giới hiện đại ngày nay" (hoặc tương đương) trong bullet "Đoạn mở bài"; "case study" xuất hiện trong câu "Không bịa số liệu".
- **`BuildArticleReviewPromptActionTest` (Unit, v1.16, §4.20)**: `handle()` với `$draftedArticle` bất kỳ → prompt trả về CHỨA NGUYÊN VĂN bài viết đó; LUÔN có đủ 4 mục "Đánh giá SEO"/"Đánh giá độ dễ đọc"/"Rà soát cuối"/"Đề xuất sửa"; có câu "KHÔNG viết lại toàn bộ bài" (precise edits, không full rewrite); `search_intent = null` → dùng câu fallback "chưa xác định trước — bạn tự đánh giá"; `$foundation = null` → vẫn còn khối "Hệ giá trị gia đình Việt Nam"; `estimateWordCount()` (qua `BuildsSharedPromptBlocks`) đếm đúng trên prompt đã sinh.
- **`ContentOutlineAdminTest` (Feature)**: user có `content_outlines.use` tạo được outline, `generated_prompt` lưu đúng khớp kết quả `BuildContentOutlinePromptAction`; user KHÔNG có permission → 403 mọi route; sửa input rồi update → `generated_prompt` được ghi đè (khác giá trị cũ); `link-article` gắn đúng `linked_post_article_id`; xoá outline → không còn trong danh sách. **(v1.1 bổ sung)**: update (regenerate) KHÔNG đổi `linked_post_article_id` đã gắn từ trước; update LUÔN cập nhật `updated_by`. **(v1.14 bổ sung, §4.17)**: `POST generate-article-prompt` với `approved_outline` hợp lệ → `approved_outline`/`article_draft_prompt` được lưu đúng, `article_draft_prompt` khớp kết quả `BuildArticleDraftPromptAction`, `updated_by` cập nhật; gọi LẦN 2 với outline khác → `article_draft_prompt` bị GHI ĐÈ (khác giá trị cũ), `generated_prompt`/`linked_post_article_id` KHÔNG đổi; thiếu `approved_outline` → validation error; user KHÔNG có `content_outlines.use` → 403. **(v1.15 bổ sung, §4.18)**: `cta_url` không phải URL hợp lệ (VD "abc") → validation error khi store/update; `cta_url` hợp lệ → lưu đúng, hiển thị đúng ở `show.blade.php`. **(v1.16 bổ sung, §4.20)**: `POST generate-review-prompt` với `drafted_article` hợp lệ → `drafted_article`/`review_prompt` lưu đúng, khớp kết quả `BuildArticleReviewPromptAction`, `updated_by` cập nhật; gọi LẦN 2 → `review_prompt` GHI ĐÈ, `generated_prompt`/`approved_outline`/`article_draft_prompt`/`linked_post_article_id` KHÔNG đổi; thiếu `drafted_article` → validation error; user KHÔNG có permission → 403.

---

## 11. Kế hoạch triển khai

1. Scaffold module + migration + model + permission seeder.
2. `ContentOutlineInputData` + `BuildContentOutlinePromptAction` (viết test Unit TRƯỚC, vì đây là logic lõi không phụ thuộc HTTP/DB).
3. `CreateContentOutlineAction`/`RegenerateContentOutlinePromptAction`/`LinkContentOutlineToArticleAction` + FormRequest.
4. Controller + routes + Query/Handler cho Tabulator.
5. Views (`index`/`create`/`edit`/`show`) + JS (picker category qua `ContentFoundation` API, copy-to-clipboard).
6. Sidebar + test Feature.
7. **(v1.1)** Vá 5 rủi ro §4.1-§4.5 — migration `outline_depth`, cập nhật `BuildContentOutlinePromptAction`/Action/Request liên quan, UI (form/list/show) + JS.
8. **(v1.2)** §4.6 — đổi tên `ResolvesCategoryFoundation` → `ResolvesCategoryContext` + thêm `resolveExistingArticleTitles()`; `BuildContentOutlinePromptAction::handle()` thêm tham số `$existingArticleTitles`; thêm "Lưu ý EEAT" vào 3 biến thể BOTTOM.
9. **(v1.3)** §4.7 — sửa nội dung văn bản 3 biến thể BOTTOM: thêm bước "luận điểm chính" + mục output tương ứng, ràng buộc số điểm/H2, chỉ dẫn cụm từ song song. Không đổi signature/DB.
10. **(v1.5)** §4.8 — sửa nội dung văn bản 3 biến thể BOTTOM: thêm bước "USP" + mục output, ghi chú ước tính khối lượng tìm kiếm/độ khó từ khoá, benchmark độ sâu theo đối thủ (`standard`/`detailed`), named flow pattern cho H2, mở rộng self-check. Không đổi signature/DB.
11. **(v1.6)** §4.9 — migration `content_role`; `ContentOutlineInputData`/FormRequest/Action thêm field; `BuildContentOutlinePromptAction::buildBottom()` thêm tham số `$contentRole`/`$hasExistingArticles`, chèn ghi chú qua placeholder `{{ROLE_LINK_NOTE}}` ở `standard`/`detailed`; form + trang show hiển thị field mới.
12. **(v1.7)** §4.10 — sửa nội dung văn bản 3 biến thể BOTTOM: thêm bước "Gợi ý CTA/bước tiếp theo" + mục `## CTA`, nối thêm "Độ tin cậy dữ liệu" vào dòng Lưu ý EEAT. Không đổi signature/DB.
13. **(v1.8)** §4.11 — sửa nội dung văn bản 3 biến thể BOTTOM: bước "Dựng cấu trúc H2(/H3)" thêm chỉ dẫn answer-first; bước research thêm lưu ý AI answer engine; nối thêm "Không bịa số liệu" vào dòng Lưu ý EEAT; bước "Làm rõ nội dung heading" (`standard`/`detailed`) thêm yêu cầu list lead-in; bước "Ước lượng số từ mỗi phần" (`detailed`) thêm sai số ±10% + tính cả phần mở đầu. Không đổi signature/DB.
14. **(v1.9)** §4.12 — sửa nội dung văn bản `standard`/`detailed` BOTTOM: chèn bước mới "Chọn kiểu bài (structure archetype)" trước bước dựng H2/H3 (renumbering các bước sau đó) + mục output `## Kiểu bài`; mở rộng bước "Xác nhận ý định tìm kiếm" thành đoạn 3 câu hỏi; bước "Dựng cấu trúc H2/H3" thêm quy tắc Content-H3/Label-H3 + ngưỡng 400 từ; bước "Làm rõ nội dung heading" thêm differentiation note mỗi H2; bước "Gợi ý internal link" thêm anchor text. CẢ 3 biến thể BOTTOM (kể cả `brief`): bước FAQ thêm yêu cầu nguồn PAA thật. Không đổi signature/DB.
15. **(v1.10)** §4.13 — sửa nội dung văn bản 3 biến thể BOTTOM: bước Meta (CẢ 3 depth) thêm gợi ý loại Schema markup (Article/BlogPosting mặc định, +FAQPage nếu có khối FAQ, +HowTo/+ItemList theo structure archetype ở `standard`/`detailed`); bước "Làm rõ nội dung heading" (`standard`/`detailed`) thêm yêu cầu alt text ngắn cho mỗi hình ảnh gợi ý. Không đổi signature/DB.
16. **(v1.11)** §4.14 — sửa nội dung văn bản 3 biến thể BOTTOM: bước tiêu đề (H1) + bước Meta đổi "chứa từ khoá" → "chứa từ khoá ĐẶT GẦN ĐẦU"; Meta Description đổi ngưỡng "≤155" → "140-160" ký tự + thêm yêu cầu câu chủ động + lời mời hành động; bước thesis thêm yêu cầu từ khoá trong 100-150 từ đầu bài; nối thêm "Mật độ từ khoá" vào dòng Lưu ý EEAT. CẢ 3 biến thể BOTTOM (kể cả `brief`). Không đổi signature/DB.
17. **(v1.12)** §4.15 — sửa nội dung văn bản 3 biến thể BOTTOM: bước Research (CẢ 3 depth) thêm yêu cầu ghi chú SERP feature quan sát được; bước dựng H2/H3 mở rộng answer-first thêm yêu cầu khớp định dạng featured snippet; bước Research (`standard`/`detailed`) thêm yêu cầu gom nhóm heading LẶP LẠI giữa đối thủ + bước dựng H2/H3 + self-check tham chiếu lại danh sách đó. Không đổi signature/DB.
18. **(v1.13)** §4.16 — sửa nội dung văn bản 3 biến thể BOTTOM: bước dựng H2 thêm yêu cầu H2 "Kết luận" khép lại Dàn ý (CẢ 3 depth). Ban đầu CÒN thêm 1 khối "## Bước tiếp theo" tĩnh (`buildDraftPromptTemplate()`) + placeholder `{{ROLE_LINK_NOTE}}`-style `{{DRAFT_PROMPT_TEMPLATE}}` — **đã REVERT ở bước 19 dưới đây (v1.14)**, không triển khai riêng bước này nữa nếu làm lại từ đầu (ghi lại để hiểu TẠI SAO `buildBottom()` từng đổi signature rồi đổi lại).
19. **(v1.14)** §4.17 — Feature `ArticleDrafting` thật: (a) migration `approved_outline`/`article_draft_prompt`; (b) tách `BuildsSharedPromptBlocks` (estimateWordCount/buildFamilyValuesBlock) + dời `ResolvesCategoryContext` lên `Features/Concerns/`; (c) revert `BuildContentOutlinePromptAction::buildBottom()` về signature gốc + XOÁ `buildDraftPromptTemplate()`/placeholder `{{DRAFT_PROMPT_TEMPLATE}}` (supersede v1.13 phần đó); (d) `BuildArticleDraftPromptAction` + `SaveApprovedOutlineAndBuildArticlePromptAction` (Feature mới); (e) `StoreApprovedOutlineRequest` + `ContentOutlineController::generateArticlePrompt()` + route `POST {outline}/article-prompt`; (f) `show.blade.php` thêm card "Bước 2" + tổng quát hoá 3 hàm JS copy/download/collapsible theo `elId`/`containerId`. Không đổi permission (dùng chung `content_outlines.use`).
20. **(v1.15)** §4.18 — đã hỏi người dùng phạm vi trước khi làm (áp dụng 4/6 điểm, từ chối social snippet + tự động hoá AI): (a) migration `cta_url`; (b) `ContentOutlineInputData`/`StoreContentOutlineRequest`/`UpdateContentOutlineRequest`/`CreateContentOutlineAction`/`RegenerateContentOutlinePromptAction` thêm field `cta_url` (validate `url()`); (c) `BuildContentOutlinePromptAction`: TOP thêm dòng "CTA URL", bước CTA (CẢ 3 depth) + bước brainstorm tiêu đề (CẢ 3 depth, "MẠNH NHẤT" + lý do) sửa nội dung văn bản; (d) `BuildArticleDraftPromptAction` thêm bullet "Đoạn mở bài" (hook) + bullet "scannable" (đoạn ngắn/bullet/bold) + bullet "Đoạn kết bài" (dùng `cta_url` nếu có, fallback CTA chung nếu không); (e) `_form.blade.php` thêm input `cta_url`; `show.blade.php` hiện `cta_url` ở panel chi tiết nếu có. Không đổi permission, không đổi signature `handle()` của 2 Build*PromptAction (chỉ đổi NỘI DUNG text sinh ra).
21. **(v1.16)** §4.19/§4.20 — đã hỏi người dùng phạm vi trước khi làm (4 điểm nhỏ áp dụng ngay + 1 Feature mới sau khi xác nhận). §4.19 (sửa text, không đổi DB): bước làm rõ heading (`standard`/`detailed`) thêm "before-after"; bước Meta (CẢ 3 depth) đổi "1 phương án" → "2-3 phương án"; "Không bịa số liệu" (CẢ 3 depth ở outline + `BuildArticleDraftPromptAction`) thêm "case study"; bullet "Đoạn mở bài" (`BuildArticleDraftPromptAction`) cấm cụ thể cụm cliché + ràng buộc vị trí 1-2 câu đầu. §4.20 (Feature mới, đối xứng §4.17): (a) migration `drafted_article`/`review_prompt`; (b) `BuildArticleReviewPromptAction` (gộp SEO+readability+final-edit thành 1 prompt, yêu cầu precise edits) + `SaveDraftedArticleAndBuildReviewPromptAction` (Feature `ArticleReview` mới); (c) `StoreDraftedArticleRequest` + `ContentOutlineController::generateReviewPrompt()` + route `POST {outline}/review-prompt`; (d) `show.blade.php` thêm card "Bước 3", tái dùng NGUYÊN 3 hàm JS đã tổng quát hoá ở v1.14 (chỉ đổi `elId`/`containerId`). Không đổi permission (dùng chung `content_outlines.use`), không đổi signature/hành vi của `OutlineGeneration`/`ArticleDrafting` đã có.
22. **(v1.17)** §4.21 — đã hỏi người dùng 1 câu quyết định phạm vi trước khi làm ("Draft section-by-section" — từ chối). Sửa nội dung văn bản, không đổi DB/signature: bước "Làm rõ nội dung mỗi heading" (`standard`/`detailed`) thêm gợi ý ý tưởng infographic khi phần đó có nhiều số liệu/bước liên tiếp. Từ chối rõ 2 điểm của nguồn (section-by-section drafting, mật độ từ khoá 1-2% — mâu thuẫn §4.14) — ghi lại lý do đầy đủ ở §4.21 để không phải xem lại nguồn này lần sau.
23. **(v1.18)** §4.22 — nguồn xác nhận lại (không đổi) quyết định "từ chối section-by-section" đã chốt ở §4.21. Sửa nội dung văn bản, không đổi DB/signature: mở rộng "differentiation note" (bước "Làm rõ nội dung mỗi heading", `standard`/`detailed`) thêm 1 câu mời biên tập viên tự điền personal story/case study/testimonial THẬT vào vị trí phù hợp. Ghi lại đầy đủ lý do các điểm KHÔNG áp dụng (đã có cơ chế tương đương hoặc tool ngoài) ở §4.22 để không phải xem lại nguồn này lần sau.
24. **(v1.19)** §4.23 — sửa nội dung văn bản, không đổi DB/signature: mở rộng câu "Độ tin cậy dữ liệu" (`standard`/`detailed`) thêm yêu cầu nêu TÊN chuyên gia/tổ chức uy tín THẬT nếu biết. Từ chối rõ "mention keyword ít nhất 3 lần" (mâu thuẫn §4.14/§4.21) + xác nhận Brainstorming/Example-Based Anchoring không cần cơ chế mới — ghi lại đầy đủ lý do ở §4.23.
25. **(v1.20)** §4.24 — rà soát rủi ro nội bộ (không đối chiếu nguồn ngoài), không đổi DB/signature: (a) `edit.blade.php` tính `$hasDownstream`, đổi `data-confirm-regenerate` thành message cảnh báo cascade Bước 2/3 khi đã có; (b) `show.blade.php` thêm `$hasDownstreamSteps`, icon ⚠ + tooltip trên nút "Sửa & Sinh lại", stepper Bước 1→2→3 (badge màu theo trạng thái + anchor link `#buoc-1`/`#buoc-2`/`#buoc-3`), bọc card "Bước 2"/"Bước 3" trong `<details>` (Bước 2 tự thu gọn khi đã có `review_prompt`, Bước 3 luôn mở). Xác nhận 2 điểm KHÔNG cần code (instruction overload — theo dõi; maintainability 3 BOTTOM — technical debt không bắt buộc) + 1 điểm đã lỗi thời (soft warning Bước 2/3 đã có từ v1.14/v1.16) — ghi lại đầy đủ ở §4.24 để không phải rà soát lại các điểm đã xử lý.
26. **(v1.21)** §4.24 (mở rộng) — theo dõi lại 3/5 đề xuất "ưu tiên cao" của người dùng sau v1.20, không đổi DB/signature: (a) thêm bullet đầy đủ vào §4.2 (canonical) về hành vi GIỮ NGUYÊN 4 field Bước 2/3 khi regenerate + sửa câu mô tả `data-confirm-regenerate="1"` đã lỗi thời (thực tế đã ĐỘNG từ v1.20); (b) `RegenerateContentOutlinePromptAction` thêm docblock khẳng định TƯỜNG MINH việc loại trừ 4 field (trước đó chỉ suy luận được từ code, chưa có câu khẳng định trực tiếp) + verify bằng test sống; (c) `show.blade.php` đổi quy tắc mặc định mở/đóng `<details>` Bước 2/3 thành NHẤT QUÁN cho cả 2 (mở CHỈ khi đã có kết quả, đóng khi chưa dùng — trước đó Bước 3 luôn mở, không khớp yêu cầu "đóng mặc định khối chưa dùng").
