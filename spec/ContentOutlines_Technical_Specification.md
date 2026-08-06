# Module Dàn ý Nội dung (ContentOutlines)

**Phiên bản:** 1.11
**Ngày:** 2026-08-07
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Module mới:** `Modules/ContentOutlines` (tạo bằng `php artisan module:make ContentOutlines`)
**Module phụ thuộc:** `Modules\ContentFoundation` (ngữ cảnh biên tập theo `PostCategory`, 1 chiều — cùng cách `CoreIdeaExtractor`/`VideoIdeaExtractor` phụ thuộc module này). Không tích hợp `app/Services/AI/` (xem §0).
**Trạng thái:** v1.11 — đã vá 5 rủi ro sau review v1.0 (§4.1-§4.5) + áp dụng 2 điểm checklist content-marketing (§4.6) + 3 điểm phương pháp luận outline tổng quát (§4.7) + 4 điểm SEO content outline chuyên biệt (§4.8) + mô hình internal-link Pillar↔Cluster (§4.9) + CTA/độ tin cậy dữ liệu (§4.10) + answer-first/AI answer engine/chặn bịa số liệu/list lead-in/sai số ±10% (§4.11) + structure archetype/intent map 3 câu hỏi/Content-H3/differentiation note/FAQ nguồn PAA thật/anchor text (§4.12) + gợi ý Schema markup/alt text hình ảnh (§4.13) + từ khoá gần đầu/Meta 140-160/keyword trong 150 từ đầu/chặn nhồi từ khoá (§4.14). Chưa qua vòng tinh chỉnh dựa trên phản hồi sử dụng thật DÀI HẠN (khác `CoreIdeaExtractor.md` đã qua 28 version).

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
- **`updated_by`/`updated_at` LUÔN cập nhật** — `updated_by` set thủ công trong mảng update(), `updated_at` tự động qua Eloquent timestamps ở MỌI lần gọi `update()` (kể cả khi nội dung field không đổi thực chất).
- **Confirm dialog trước khi submit** — `edit.blade.php` gắn `data-confirm-regenerate="1"` trên `<form>`, `content-outlines.js` chặn submit bằng `window.confirm()` nếu người dùng không xác nhận — cảnh báo rõ: ghi đè prompt hiện tại, KHÔNG thể khôi phục (không versioning, §1).

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
      OutlineGeneration/
        Actions/
          BuildContentOutlinePromptAction.php   // §3/§4.1
          CreateContentOutlineAction.php        // validate input → build prompt → lưu bản ghi
          RegenerateContentOutlinePromptAction.php // sửa input → build lại prompt → ghi đè generated_prompt (§4.2)
          LinkContentOutlineToArticleAction.php // gắn/gỡ linked_post_article_id (§4.4 — 1-1)
          Concerns/
            ResolvesCategoryContext.php         // dùng chung Create/Regenerate — foundation + existing article titles (§4.6, v1.2)
        Data/
          ContentOutlineInputData.php            // §3.1 — DTO input dùng chung Create/Regenerate, có outline_depth
        Http/
          Requests/
            StoreContentOutlineRequest.php
            UpdateContentOutlineRequest.php
          ContentOutlineController.php           // trang list/create/edit/show + store/update/destroy/link
          ContentOutlineApiController.php         // JSON Tabulator cho trang danh sách
          Resources/
            ContentOutlineListResource.php        // + is_long_prompt (§4.1)
        Queries/
          ListContentOutlinesForAdminQuery.php
          ListContentOutlinesForAdminHandler.php
    Providers/
      ContentOutlinesServiceProvider.php   // Gate::define không cần (dùng permission string trực tiếp, cùng CoreIdeaExtractor) — chỉ đăng ký RouteServiceProvider
      RouteServiceProvider.php
  database/
    migrations/
      xxxx_create_content_outlines_table.php
      xxxx_add_outline_depth_to_content_outlines_table.php   // §4.1 — migration riêng, sau khi bảng đã tồn tại
      xxxx_add_content_role_to_content_outlines_table.php    // §4.9 — migration riêng, sau outline_depth
    seeders/
      ContentOutlinesPermissionSeeder.php   // content_outlines.use → platform_content_editor/head/section_editor (§7)
  resources/
    views/
      index.blade.php    // danh sách (Tabulator) + modal xoá hiện label/topic (§4.3)
      create.blade.php / edit.blade.php  // dùng chung _form.blade.php
      _form.blade.php    // + outline_depth + ước lượng độ dài client-side (§4.1)
      show.blade.php      // prompt thô/preview Markdown collapsible + copy + download .md (§4.5) + banner cảnh báo dài (§4.1) + gắn bài viết (§4.4)
    assets/js/content-outlines.js
  routes/
    web.php
  tests/
    Feature/
      ContentOutlineAdminTest.php
    Unit/
      BuildContentOutlinePromptActionTest.php
  module.json
```

`module.json` — `alias: "content-outlines"`, `providers: ["Modules\\ContentOutlines\\Providers\\ContentOutlinesServiceProvider", "Modules\\ContentOutlines\\Providers\\RouteServiceProvider"]`.

---

## 7. RBAC

- **Permission mới**: `content_outlines.use` (`App\Enums\PermissionEnum::CONTENT_OUTLINES_USE`) — seed qua `ContentOutlinesPermissionSeeder`, gán cho `platform_content_editor`/`platform_content_head`/`platform_section_editor` — **CÙNG NHÓM CHÍNH XÁC** với `CORE_IDEA_EXTRACTOR_USE`/`VIDEO_IDEA_EXTRACTOR_USE`/`CONTENT_FOUNDATION_USE` (không phải permission mới độc lập theo `config/permissions.php` Lớp B).
- Route gate phẳng bằng middleware `can:content_outlines.use` (không cần Policy riêng theo model — §2.1 đã chốt không có owner-based ACL, §4.3 ghi rõ rủi ro đi kèm).
- Sidebar: đặt cùng nhóm "Trích ý bài viết"/"Content Foundation"/"Trích ý video" (route `backend.contentoutlines.index`, nhãn **"Dàn ý nội dung"**), gate bằng `@can(\App\Enums\PermissionEnum::CONTENT_OUTLINES_USE->value)`.

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
- `create.blade.php`/`edit.blade.php` dùng chung `_form.blade.php` (picker category qua JS gọi `ContentFoundation` API, cùng pattern `CoreIdeaExtractor`; field `outline_depth` + ước lượng độ dài client-side, §4.1). `edit.blade.php` gắn `data-confirm-regenerate` (§4.2).
- `show.blade.php` — toggle "Prompt thô" (textarea readonly, dùng Copy)/"Xem trước Markdown" (`Str::markdown()`, collapsible theo `## `, §4.5); nút "Copy"/"Download .md"; banner cảnh báo khi prompt vượt ngưỡng độ dài (§4.1); nút "Sửa & Sinh lại" (điều hướng `edit`); form "Gắn vào bài viết" (1-1, §4.4, dán UUID `PostArticle`, gọi `link-article`).

---

## 9. Ngoài phạm vi (v1 - v1.11)

- Gọi AI Provider trong app (§0).
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
- Đối chiếu framework/nguồn bổ sung khác ngoài các nguồn đã dẫn ở §0/§4.6/§4.7/§4.9/§4.10/§4.11/§4.12/§4.13/§4.14 — để mở cho các vòng tinh chỉnh sau dựa trên phản hồi sử dụng thật (cùng tinh thần "Adaptive" đã áp dụng cho `CoreIdeaExtractor.md`, xem changelog module đó).

---

## 10. Testing

- **`BuildContentOutlinePromptActionTest` (Unit)**: input tối thiểu (chỉ topic+keyword) → prompt vẫn hợp lệ, các khối optional bị bỏ đúng cách khi field null; input đầy đủ + có `$foundation` → MIDDLE xuất hiện đủ các gợi ý map từ `pain_points`/`objections`/`decision_criteria`; `$foundation = null` → MIDDLE chỉ còn khối "Hệ giá trị gia đình" (vẫn LUÔN xuất hiện, §3.2); `language = 'en'` → TOP ghi "English". **(v1.1 bổ sung)**: `outline_depth = 'brief'` → field foundation bị cắt đúng ngưỡng (300 ký tự) + số `competitor_urls` bị giới hạn đúng (3); `outline_depth = 'detailed'` → BOTTOM có "Đánh giá độ khó cạnh tranh"; `estimateWordCount()` đếm đúng với chuỗi tiếng Việt có dấu. **(v1.2 bổ sung)**: `existingArticleTitles` không rỗng → khối "Bài viết đã có trong chuyên mục" xuất hiện, cắt đúng theo `outline_depth`; rỗng → không xuất hiện khối; "Lưu ý EEAT" xuất hiện ở CẢ 3 `outline_depth`. **(v1.3 bổ sung)**: bước "luận điểm chính" + mục `## Luận điểm chính` + chỉ dẫn "dạng ngữ pháp" (song song) xuất hiện ở CẢ 3 `outline_depth`; chỉ dẫn "3-5 điểm/H3" chỉ xuất hiện ở `standard`/`detailed` (không phải `brief`). **(v1.5 bổ sung)**: bước "USP" + mục `## USP` + ghi chú "khối lượng tìm kiếm" + named flow pattern ("giải quyết vấn đề") xuất hiện ở CẢ 3 `outline_depth`; chỉ dẫn benchmark "BẰNG hoặc NHIỀU HƠN" chỉ ở `standard`/`detailed`; self-check mở rộng ("từ khoá mục tiêu có ở tiêu đề", "bao quát các chủ đề phụ chính") chỉ ở `standard`/`detailed`. **(v1.6 bổ sung)**: `content_role = null` → không có ghi chú vai trò/placeholder rò ra ngoài; `content_role = 'pillar'`/`'cluster'` → dòng "Vai trò nội dung" ở TOP CẢ 3 depth, nhưng ghi chú "Vai trò TRỤ CỘT"/"Vai trò CỤM" ở BOTTOM chỉ xuất hiện ở `standard`/`detailed` (không ở `brief`); nội dung ghi chú đổi đúng theo `$hasExistingArticles` (có/không có bài trong "Bài viết đã có trong chuyên mục" để link tới/lên/ngang). **(v1.7 bổ sung)**: mục `## CTA` + "Độ tin cậy dữ liệu" + mốc "12 tháng" xuất hiện ở CẢ 3 `outline_depth`. **(v1.8 bổ sung)**: "answer-first" + "trả lời TRỰC TIẾP" ở bước H2 xuất hiện CẢ 3 `outline_depth`; "AI answer engine" ở bước research xuất hiện CẢ 3 `outline_depth`; "Không bịa số liệu"/"cần biên tập viên xác minh" xuất hiện CẢ 3 `outline_depth`; "danh sách/bullet"/"không thả bullet trơ trọi" chỉ ở `standard`/`detailed` (không ở `brief` — không có bước "làm rõ nội dung heading"); "±10%"/"kể cả phần mở đầu" chỉ ở `detailed` (không ở `brief`/`standard` — không có bước ước lượng số từ mỗi phần). **(v1.9 bổ sung)**: "structure archetype" + "## Kiểu bài" + liệt kê 4 kiểu bài chỉ ở `standard`/`detailed` (không ở `brief`); "BẮT ĐẦU đọc" + "bỏ bài này đi tìm bài khác" (intent map 3 câu hỏi) chỉ ở `standard`/`detailed`; "nhãn chung" (Label H3) + "400 từ" chỉ ở `standard`/`detailed`; "đối thủ điển hình" (differentiation note mỗi H2) chỉ ở `standard`/`detailed`; "THẬT quan sát được" (FAQ nguồn PAA thật) ở CẢ 3 `outline_depth`; "anchor text" chỉ ở `standard`/`detailed`. **(v1.10 bổ sung)**: "Schema markup" + "FAQPage" xuất hiện ở CẢ 3 `outline_depth`; "HowTo"/"ItemList" (theo structure archetype) chỉ ở `standard`/`detailed` (không ở `brief` — không có bước chọn kiểu bài); "alt text" chỉ ở `standard`/`detailed` (không ở `brief` — không có bước "làm rõ nội dung heading"). **(v1.11 bổ sung)**: "GẦN ĐẦU" xuất hiện ở CẢ 3 `outline_depth`; "140-160 ký tự" + "lời mời hành động" xuất hiện ở CẢ 3 `outline_depth`; "100-150 từ đầu bài" xuất hiện ở CẢ 3 `outline_depth`; "Mật độ từ khoá" + "keyword stuffing" xuất hiện ở CẢ 3 `outline_depth`.
- **`ContentOutlineAdminTest` (Feature)**: user có `content_outlines.use` tạo được outline, `generated_prompt` lưu đúng khớp kết quả `BuildContentOutlinePromptAction`; user KHÔNG có permission → 403 mọi route; sửa input rồi update → `generated_prompt` được ghi đè (khác giá trị cũ); `link-article` gắn đúng `linked_post_article_id`; xoá outline → không còn trong danh sách. **(v1.1 bổ sung)**: update (regenerate) KHÔNG đổi `linked_post_article_id` đã gắn từ trước; update LUÔN cập nhật `updated_by`.

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
