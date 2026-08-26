# CoreIdeaExtractor

**Version:** 1.31  
**Last Updated:** 2026-08-26  
**Status:** Design Specification (Ready for Implementation)

> **v1.31 (Đối chiếu searchengineland.com/guide/topic-clusters-for-ai-search — "Query Fan-out" +
> topic cluster cho Layer 2, xem §12.14):** nguồn (bài hướng dẫn về tối ưu nội dung cho AI search)
> nêu triết lý cốt lõi: AI search engine (Google AI Overview, ChatGPT, Perplexity...) trả lời 1 câu
> hỏi RỘNG bằng cách tự "phân rã" (fan-out) thành nhiều câu hỏi con rồi tổng hợp — nội dung bao phủ
> TOÀN DIỆN 1 chủ đề + mạng câu hỏi liên quan của nó dễ được trích dẫn hơn nội dung chỉ nhắm 1 ý
> định tìm kiếm đơn lẻ. Đối chiếu 4 khuyến nghị cụ thể của bài (được người dùng diễn giải theo 3
> hướng áp dụng):
> - **(1) "Mở rộng truy vấn, dự đoán nỗi đau mới thay vì chỉ dựa từ khoá tĩnh" — ÁP DỤNG (§12.14a):**
>   thêm 1 chỉ dẫn ở BƯỚC 1 cho phép brainstorm 1-2 ý tưởng "dự đoán xu hướng" (nỗi đau/mối quan tâm
>   mới nổi mà công cụ từ khoá tĩnh chưa kịp phản ánh), BẮT BUỘC gắn nhãn rõ trong `reason` để không
>   lẫn với ý tưởng có căn cứ quan sát thật.
> - **(2) "Tự sinh mạng lưới câu hỏi ngách bao quanh chủ đề chính (Query Fan-out)" — ÁP DỤNG
>   (§12.14b):** 2 field mới per-idea trên `RunLayer2ExtractionAction::RESPONSE_SCHEMA` —
>   `cluster_questions` (3-6 câu hỏi ngách) và `content_role_suggestion` (pillar/cluster, ánh xạ
>   trực tiếp sang `content_role` đã có ở `ContentOutlines` §4.9). Đây là thay đổi DUY NHẤT trong
>   bản vá này có ảnh hưởng tới CHI PHÍ THẬT (Layer 2 gọi AI Provider tự động qua
>   `RunLayer2ExtractionAction`, khác 3 module kia chỉ sinh text cho người dùng tự copy) — mỗi ý
>   tưởng dài hơn ~3-6 câu hỏi ngắn, chấp nhận được vì đổi lại là tín hiệu bao phủ chủ đề thật, không
>   phải trang trí.
> - **(3) "Tối ưu extractability — BLUF/bullet/bảng ngay dưới subheading" ở `ContentOutlines` — ĐÃ
>   CÓ TỪ TRƯỚC, không cần vá:** đối chiếu `ContentOutlines_Technical_Specification.md` xác nhận
>   module đó đã có cơ chế tương đương từ trước bài viết này: answer-first bắt buộc mở đầu mỗi H2/H3
>   bằng câu trả lời trực tiếp (§4.11), khớp ĐÚNG định dạng bullet/bảng khi quan sát được featured
>   snippet cùng dạng (§4.15), và PAA/list lead-in nguồn thật (§4.12). Không thêm gì trùng lặp.
> - **(4) "Xây dựng thẩm quyền qua cụm chủ đề (topic cluster authority)" — ĐÃ CÓ TỪ TRƯỚC:** khớp
>   `content_role` (pillar/cluster) + mô hình internal-link Pillar↔Cluster đã có ở `ContentOutlines`
>   §4.9 — bản vá này chỉ nối thêm 1 GỢI Ý sớm hơn (ngay từ khâu ý tưởng ở `CoreIdeaExtractor`) cho
>   quyết định pillar/cluster mà biên tập viên vẫn tự chọn tay khi lên dàn ý, KHÔNG tự động hoá lựa
>   chọn đó.
>
> Xem §12.14 cho chi tiết đầy đủ.

> **v1.30 (Đối chiếu martech.org/how-to-build-an-ai-content-system-that-works — chuẩn hoá "Constants"
> layer dùng chung cho `CoreIdeaExtractor`/`ContentOutlines`/`PromptFrameworkStudio`/
> `AIVideoStudioTemplate`, 1 khoảng trống ÁP DỤNG + 3 khuyến nghị đã cân nhắc và KHÔNG áp dụng, có
> lý do):** nguồn (bài kỹ thuật về xây dựng hệ thống nội dung AI cho B2C) nêu 4 khối: (1) tách rõ
> "Constants" (ICP, brand voice có VÍ DỤ thật, tài liệu sản phẩm/dịch vụ, nội dung/dàn ý mẫu tốt
> nhất) khỏi "Variables" (topic/angle/keyword mỗi lần chạy); (2) pipeline nhiều bước có Orchestrator
> (kickoff→research→outline→write); (3) tách Editor/Fact-checker/AI-tell-detector thành agent riêng,
> mỗi agent 1 context window độc lập; (4) human-in-the-loop có state thật ở bước outline VÀ trước
> publish. Đối chiếu với 4 module đang có (xem khảo sát chi tiết trước khi áp dụng):
> - **(1) Constants — ÁP DỤNG, khoảng trống thật:** `content_foundations` (§12.2 trở đi) đã phủ ICP
>   khá đầy đủ (`audience` Level 1, `pain_points`/`objections`/`decision_criteria` Level 2,
>   `audience_behavior` Level 3 — §12.12) và brand voice (`style_sample`, family values §12.10/
>   §12.11), nhưng **CHƯA có** "tài liệu mô tả chi tiết sản phẩm/dịch vụ" và "nội dung/dàn ý mẫu TỐT
>   NHẤT đã có" — 2 phần MarTech liệt kê riêng, khác `style_sample` (chỉ là 1 đoạn văn mẫu ngắn về
>   giọng văn, không phải tài liệu sản phẩm hay 1 bài/dàn ý hoàn chỉnh). Thêm 2 field mới — xem §12.13.
>   Nhân tiện, đã sửa placeholder/label `style_sample` (ở trang quản lý `ContentFoundation`) yêu cầu
>   dán NGUYÊN VĂN đoạn văn thật thay vì mô tả bằng tính từ ("gần gũi, thân thiện...") — đúng đúng
>   điểm nguồn nhấn mạnh ("ví dụ thực tế thay vì chỉ dùng tính từ mô tả"), không đổi field/schema.
> - **(2) Pipeline nhiều bước + Orchestrator — CÂN NHẮC, KHÔNG áp dụng cho `ContentOutlines` và
>   `AIVideoStudioTemplate`:** cả 2 module đã CHỦ ĐỘNG chọn kiến trúc "sinh 1 prompt, chạy 1 lần" và
>   TỪ CHỐI kiến trúc multi-step/multi-turn — quyết định có lý do ghi rõ trong chính spec của từng
>   module (`ContentOutlines_Technical_Specification.md` §0 mục 1 + nhiều mục changelog từ chối
>   framework nhiều bước; `AIVideoStudioTemplate_Technical_Specification.md` — class docblock
>   `BuildMasterScriptPromptAction` nói rõ đây là tool "không trạng thái, không nhớ giữa các lần gọi",
>   cố ý đảo ngược mô hình 3-4 bước). Đảo lại quyết định này là THAY ĐỔI KIẾN TRÚC, không phải "cập
>   nhật kỹ thuật" theo tinh thần các bản vá khác trong changelog này — để dành cho quyết định riêng
>   nếu người dùng muốn đổi hướng, KHÔNG tự ý làm kèm bản vá Constants này. `CoreIdeaExtractor` đã có
>   sẵn 1 vòng lặp thật gần nhất với "pipeline nhiều bước" (Layer 1 fetch → Layer 2 sinh ý tưởng có
>   loop tự đánh giá lại nếu chưa đạt tiêu chí, xem §4/§7) nhưng dừng ở "sinh ý tưởng" — CHƯA mở rộng
>   thêm bước outline/write (thuộc phạm vi `ContentOutlines`, module khác).
> - **(3) Agent QA tách context riêng — KHÔNG áp dụng cho module nào ở bản vá này:** không module nào
>   trong 4 module hiện có 1 lượt gọi AI thứ 2 đóng vai "người phản biện" độc lập (Editor/Fact-
>   checker/AI-tell-detector) — mọi guardrail (chống văn phong lộ AI, chặn bịa số liệu...) đều nhúng
>   trong CÙNG 1 prompt/lượt gọi. Đây là khoảng trống thật nhưng THÊM 1 lượt gọi AI là thay đổi hành
>   vi/chi phí lớn hơn hẳn phạm vi "chuẩn hoá Constants" — để dành cho `CoreIdeaExtractor` (module có
>   pipeline thật gần nhất) nếu có quyết định riêng mở rộng, không làm tràn sang 3 module còn lại.
> - **(4) Human-in-the-loop có state thật — KHÔNG áp dụng ở bản vá này:** `ContentOutlines` hiện chỉ
>   có gate ngầm ("có dữ liệu ở field bước trước mới cho bước sau" qua `approved_outline`/
>   `drafted_article`), không phải state machine `status`(pending/approved/rejected) +
>   `approved_at` thật. Ghi nhận là cải tiến ĐÁNG LÀM riêng (đổi schema + luồng dữ liệu hiện có, rủi
>   ro cao hơn thêm field Constants thuần additive) — để dành cho 1 bản vá kế tiếp của
>   `ContentOutlines_Technical_Specification.md`, không gộp vào đây.
>
> Xem §12.13 cho chi tiết 2 field mới (duy nhất phần code thay đổi ở bản vá này).

> **v1.29 (Đối chiếu lindapophal.substack.com/p/the-2026-content-marketing-imperative — "3 cấp độ
> hiểu đối tượng", 1 khoảng trống thật ÁP DỤNG, phần còn lại đã có sẵn hoặc từ chối có lý do):**
> nguồn (bài blog ngắn, không trích số liệu/nghiên cứu — ghi rõ để không nhầm là nguồn học thuật) nêu
> khung 3 tầng: **Demographic** (tuổi/ngành/chức danh/địa lý — điểm khởi đầu, chưa đủ), **Psychographic**
> (họ quan tâm gì/lo lắng gì/khao khát gì), **Behavioral** (ngày của họ trông thế nào, họ thực sự đang
> làm gì, tiêu thụ/chia sẻ/bỏ qua nội dung nào, gõ câu hỏi gì vào công cụ tìm kiếm — nguồn gọi đây là
> tầng tạo lợi thế cạnh tranh khi AI sinh nội dung chung chung tràn lan). Đối chiếu với chuỗi ngữ cảnh
> đối tượng đã có (§12.2/§12.6/§12.6-v1.16): `audience` (freetext, Max 500) chỉ phủ Level 1; `pain_
> points`/`objections`/`decision_criteria` đã phủ Level 2 khá đầy đủ (khó khăn thật/nghi ngờ chưa tin/
> tiêu chí so sánh — đúng tinh thần "quan tâm gì, lo gì"). **Level 3 hoàn toàn vắng mặt** — khối chỉ
> dẫn `audienceText` ở BƯỚC 0 (`buildLayer2PromptText()`, thêm ở vá 2026-08-06) có NHẮC tới "bối cảnh
> sinh hoạt, điều kiện kinh tế và quỹ thời gian thực tế" khi diễn giải cách DÙNG `audienceText`, nhưng
> đó là suy luận model tự làm TỪ 1 mô tả nhân khẩu học ngắn — không phải dữ liệu hành vi THẬT do editor
> quan sát/ghi lại, đúng khoảng trống nguồn chỉ ra ("a lot of brands stop short of doing the real
> work").
>
> Thêm field mới **`audience_behavior`** (text, nullable, cùng nhóm §12.2 — xem §12.12 chi tiết đầy
> đủ) trên `content_foundations` (migration ALTER riêng, KHÔNG sửa migration gốc đã chạy) — tách khỏi
> `audience` đúng logic đã dùng để tách `objections`/`decision_criteria` khỏi `pain_points` ở v1.16
> (gộp chung khiến editor bỏ sót 1 trong 2 hoặc viết lẫn lộn 2 khái niệm khác bản chất). Render vào
> TOP của "Copy prompt cho AI" ở CẢ `CoreIdeaExtractor` lẫn `VideoIdeaExtractor` (module song sinh,
> cùng đọc `CategoryContentFoundation`) ngay sau khối `audienceText`, đúng nhóm "độc giả là ai/sống thế
> nào" trước khi tới nhóm "họ nghĩ gì" (`writer_insights`/`pain_points`/...).
>
> **Không áp dụng (có lý do):**
> - **"Shadow buyers"** (độc giả âm thầm nghiên cứu/ảnh hưởng quyết định nhưng cố tình tránh tương tác
>   công khai qua form/webinar) — khái niệm gốc thuộc bối cảnh B2B lead-gen (form điền thông tin liên
>   hệ, đăng ký webinar để "lộ diện"). Platform familiesforlife xuất bản nội dung biên tập công khai
>   cho gia đình Việt Nam, không thu lead qua form/webinar — không có khái niệm "buyer ẩn danh cố tránh
>   liên hệ" tương ứng. Phần tinh thần CÓ thể chuyển hoá được ("đừng chỉ đo đối tượng qua ai bình luận
>   công khai — nhiều độc giả đọc/bị ảnh hưởng nhưng im lặng") đã gộp vào hướng dẫn nguồn dữ liệu của
>   `audience_behavior` ở §12.12 (không cần khái niệm/field riêng).
> - **"Viết cho 1 người cụ thể, không phải đám đông"** — ĐÃ có sẵn từ vá 2026-08-06 (không phải khoảng
>   trống): câu "KHÔNG mở rộng sang nhóm độc giả khác cho 'an toàn' — ý tưởng nhắm đúng 1 nhóm cụ thể
>   luôn tốt hơn ý tưởng chung chung ai đọc cũng được" (`index.blade.php`, khối `audienceText`) đã diễn
>   đạt đúng tinh thần này; nhánh "chưa có mô tả đối tượng" còn ép model tự dựng 1 chân dung cụ thể
>   TRƯỚC khi brainstorm thay vì brainstorm chung chung rồi mới nghĩ tới đối tượng.
> - **Phương pháp nghiên cứu thực tiễn** (đọc bình luận, theo dõi engagement, ghi câu hỏi lặp lại, xem
>   nội dung nào kém hiệu quả, phỏng vấn trực tiếp) — đã đúng tinh thần tài liệu hoá ở `pain_points`
>   (§12.6, v1.10: "khảo sát, feedback, câu hỏi lặp lại từ độc giả/khách hàng"); tích hợp TỰ ĐỘNG các
>   nguồn này (phân tích bình luận/search analytics thật) đã bị loại khỏi phạm vi từ v1.10 ("KHÔNG tự
>   động hoá việc thu thập pain points... không tích hợp khảo sát/phân tích sales call") — không mở
>   lại, cùng lý do; `audience_behavior` mới thêm CŨNG là field editor tự điền tay, không tích hợp gì.
> - **"Lợi thế cạnh tranh so với nội dung AI chung chung"** — khung diễn giải Ý NGHĨA của việc hiểu đối
>   tượng, không phải 1 kỹ thuật/field cụ thể để áp — không có gì để implement.
>
> Không đổi Layer 1/Layer 2 JSON schema (§5, §7) — thuần field UI/prompt-template như mọi field khác
> trong §12.2.
>
> **v1.28 (Rà lại toàn bộ chuỗi ngữ cảnh biên tập sau khi thêm khung ứng xử — vá 12 lỗ hổng):** khung
> §12.11 ở v1.27 chạy được nhưng rà lại cả chuỗi (đối tượng độc giả → khung giá trị → khung ứng xử →
> BƯỚC 1 sinh ý → BƯỚC 2 lọc) lộ ra 12 lỗ hổng, đáng chú ý nhất là 2 lỗi LOGIC chứ không phải câu chữ:
> (1) câu chữ gốc của tiêu chí "con với cha mẹ" có mệnh đề *"phù hợp với độ tuổi, giới tính"* mâu
> thuẫn trực diện với giá trị Tiến bộ (bình đẳng giới) ở khối đứng ngay cạnh, mà prompt không có luật
> nào xử lý xung đột GIỮA 2 KHUNG; (2) mỗi cặp quan hệ có hai phía nhưng không chỗ nào nói độc giả
> đứng ở phía nào, nên sinh ra ý tưởng nhắc nghĩa vụ của thành viên KHÔNG đọc bài. Chi tiết đầy đủ 9
> lỗ hổng + lý do từng cái là lỗi thật: §12.11.4. Áp cho cả `CoreIdeaExtractor` và
> `VideoIdeaExtractor`; không đổi DB schema, không đổi Layer 1/Layer 2 JSON schema (§5, §7).

> **v1.27 (`spec/giadinh.md` đổi nội dung lần nữa — "Bộ tiêu chí ứng xử trong gia đình", 4 cặp quan
> hệ vợ chồng/cha mẹ-con/con-cha mẹ/anh chị em):** khung KHÁC bản chất với 4 giá trị `family_values`
> ở §12.10 (mục tiêu xã hội tổng thể) — đây là quy tắc hành vi theo TỪNG vai trò trong gia đình, nên
> tách thành khối Lớp 1 + Lớp 2 riêng (`family_conduct_standards`/`family_conduct_focus`), cùng cơ
> chế 2 lớp đã có ở §12.10 chứ không gộp chung. Xem §12.11. `decision_ref` KHÔNG trích số hiệu/ngày
> ban hành cụ thể (bộ tiêu chí này ban hành/cập nhật theo từng địa phương/năm, không có 1 văn bản
> gốc số hiệu cố định như Quyết định 1189/QĐ-TTg) — đúng nguyên tắc "không bịa chi tiết văn bản pháp
> quy" đã áp dụng cho `family_values`.

> **v1.26 (Đối chiếu tigergraph.com/blog/context-engineering-vs-prompt-engineering — 1 khoảng
> trống thật ÁP DỤNG, phần còn lại không áp dụng được hoặc đã có sẵn):** bài phân biệt "prompt
> engineering" (cách model DÙNG thông tin — instruction, ví dụ, format) với "context engineering"
> (chất lượng/độ đầy đủ của THÔNG TIN được đưa vào — retrieval, freshness, structured/unstructured
> grounding, memory, explainability). Đối chiếu 6 trục chính của "context engineering" theo bài:
>
> - **Structured context grounding**: đã có từ v1.4 — Category Content Foundation (core_focus,
>   unique_angle, pain_points, objections, decision_criteria) là dữ liệu CÓ CẤU TRÚC từ DB model,
>   không phải văn bản tự do.
> - **Data freshness**: đã có — so `date_modified` giữa các nguồn (§12), tiêm "Ngày hôm nay" vào
>   TOP mọi prompt.
> - **Agent memory qua nhiều bước**: đã có — goal-based loop (`RunLayer2ExtractionAction`/
>   `RunVideoIdeaLayer2Action`) giữ trạng thái `$accepted`/`$products` xuyên suốt các lượt lặp.
> - **Context window composition/relevance ranking**: đã có ở mức cơ bản — `truncateForHint()`
>   (progressive disclosure, §12.6) + cảnh báo kích thước (§12.5); chưa có thuật toán ranking phức
>   tạp nhưng KHÔNG có bằng chứng cần tới mức đó ở quy mô dữ liệu hiện tại.
> - **Retrieval architecture (vector/graph search)**: KHÔNG áp dụng — bài viết đến từ 1 công ty
>   graph database (TigerGraph), đề xuất tự nhiên nghiêng về giải pháp graph DB; module này chạy
>   trên Laravel/MySQL thông thường, việc đưa vào 1 tầng vector-search/knowledge-graph riêng cho 2
>   module content-tooling nội bộ là quá mức cần thiết (over-engineering) so với quy mô dữ liệu
>   thực tế (vài chục category, không phải hàng triệu thực thể quan hệ phức tạp).
> - **Explainability & Auditability**: **KHOẢNG TRỐNG THẬT, ĐÃ ÁP DỤNG.** Mỗi nguồn trong payload
>   MIDDLE (`buildAiPayload()`) đã có `title` (và `url` khi ở chế độ batch của CoreIdeaExtractor)
>   định danh rõ ràng, nhưng model trước đây KHÔNG bị bắt buộc trích dẫn đã dùng nguồn nào cho từng
>   ý tưởng — khi chạy batch nhiều nguồn/nhiều video, biên tập viên không tự kiểm chứng lại được ý
>   tưởng X dựa vào nguồn nào. Đã thêm field bắt buộc `source_reference` vào `RESPONSE_SCHEMA` của
>   cả 2 Action (`RunLayer2ExtractionAction`, `RunVideoIdeaLayer2Action`) + cột "Nguồn căn cứ" mới
>   trong `renderMarkdownTable()` và trong mô tả BẢNG 1 ở nhánh `forExternalChat` của
>   `buildLayer2PromptText()` (cả 2 module) — model phải copy đúng `title`/`url` của (các) nguồn
>   đã dùng, ngăn cách bằng dấu chấm phẩy nếu tổng hợp nhiều nguồn.
>
> Đổi `RESPONSE_SCHEMA` (thêm field `source_reference`, required) — khác các version trước chỉ ghi
> chú không đổi schema. Test `Modules/CoreIdeaExtractor/tests/Feature/CoreIdeaExtractorTest.php`
> (38 test) vẫn pass — cột mới fallback rỗng nếu field vắng mặt, không phá vỡ hành vi cũ.

> **v1.25 (Đối chiếu 2 framework do người dùng cung cấp trực tiếp — PROMPT của Sarah Hartman-
> Caverly (Penn State Berks) và CLEAR của Leo S. Lo (UVA Libraries) — KHÔNG áp dụng gì, cả 2 đã
> được đáp ứng đầy đủ bởi thiết kế hiện tại):**
>
> **PROMPT** (Persona/Requirements/Organization/Medium/Purpose/Tone) — đối chiếu từng chữ cái:
> - Persona: `top.push('Bạn là biên tập viên giàu kinh nghiệm...')` (`index.blade.php`) — đã có từ
>   bản đầu tiên (§12.4).
> - Requirements: toàn bộ BƯỚC 1/2/3 + `RESPONSE_SCHEMA` (required fields, boolean 4 tiêu chí) CHÍNH
>   LÀ "define parameters for output" ở mức chi tiết hơn ví dụ minh hoạ của framework.
> - Organization: BƯỚC 3 quy định thứ tự "ĐÚNG 2 bảng Markdown theo thứ tự dưới đây" — đúng "describe
>   the structure of the output".
> - Medium: rẽ nhánh `forExternalChat` (Markdown 2 bảng khi copy sang chat ngoài, JSON Schema khi
>   gọi qua app) — đúng "describe the format of the output", còn chi tiết hơn ví dụ của framework
>   (framework chỉ nói 1 format, code này phải phục vụ 2 format cho cùng 1 nội dung).
> - Purpose: `goalText`/`brief.goal` ("Phục vụ mục tiêu") — đúng "rhetorical purpose"; `audienceText`
>   ("Phù hợp đối tượng độc giả") — đúng "intended audience", còn tách RIÊNG thành 1/4 tiêu chí đánh
>   giá bắt buộc (BƯỚC 2 tiêu chí 4) thay vì chỉ nêu 1 lần ở đầu như ví dụ của framework.
> - Tone: gộp vào tiêu chí 4 ("giọng văn của ý tưởng có khớp với hoàn cảnh... của đúng đối tượng
>   này không", `index.blade.php:1150`) thay vì tách riêng — hợp lý vì tone phụ thuộc audience, tách
>   riêng sẽ trùng lặp; `styleSampleText` (giọng văn mẫu THẬT trích từ editor) ở `buildRewritePromptText()`
>   còn cụ thể hơn 1 tính từ mô tả tone ("academic") như ví dụ của framework — đã ghi nhận từ v1.22.
>
> **CLEAR** (Concise/Logical/Explicit/Adaptive/Reflective) — đây là checklist QUY TRÌNH cho người
> viết prompt, không phải kỹ thuật nhúng vào prompt runtime, nên đối chiếu với QUY TRÌNH phát triển
> module thay vì với văn bản prompt:
> - Concise: prompt Layer 2 dài (TOP/MIDDLE/BOTTOM đầy đủ ngữ cảnh) nhưng đã có cơ chế tự cảnh báo
>   độ dài (§12.5, `isPromptLarge()`) thay vì cắt mù quáng — độ dài là có chủ đích (ngữ cảnh chuyên
>   mục/decision log), không phải "superfluous language".
> - Logical: cấu trúc TOP → MIDDLE → BOTTOM/BƯỚC 0-3 tuần tự đã là "structured logically like
>   instructions".
> - Explicit: `RESPONSE_SCHEMA` + BƯỚC 3 mô tả định dạng output chi tiết tới từng cột bảng.
> - Adaptive: chính bảng changelog này (v1.5 → v1.25, 25 lần điều chỉnh dựa trên đối chiếu/phản hồi
>   thật) LÀ hành vi "Adaptive" trong thực tế, chỉ chưa từng gọi tên vậy.
> - Reflective: `selfCheckLine()` (tự rà lại bịa/bỏ sót trước khi trả lời, dùng cho tool tóm
>   tắt/viết lại) là dạng runtime của "Reflective"; ở cấp quy trình, mọi entry changelog đối chiếu
>   framework bên ngoài (v1.16 trở đi) là "Reflective" ở cấp phát triển module.
>
> Không đổi Layer 1/Layer 2 JSON schema (§5, §7), không đổi code — khác v1.24 (2 khoảng trống thật
> đã áp dụng), lần đối chiếu này xác nhận độ phủ đã đủ, không phát sinh việc mới.

> **v1.24 (Đối chiếu leewayhertz.com/prompt-engineering — 2 kỹ thuật ÁP DỤNG THẬT, phần còn lại đã
> có sẵn dưới tên khác hoặc từ chối có lý do):** bài liệt kê ~16 kỹ thuật prompt engineering
> (zero-shot, few-shot, chain-of-thought, self-consistency, least-to-most, generated knowledge,
> directional stimulus, ReAct, multimodal CoT, graph prompting, prompt chaining, tree-of-thoughts,
> ART, APE, active-prompt, role-specific prompting).
>
> Đối chiếu phát hiện: chain-of-thought (BƯỚC 0-3 + `selfCheckLine()`), role prompting (persona ở
> TOP mọi prompt), prompt chaining dạng nhẹ (goal-based loop §RunLayer2ExtractionAction) đã có sẵn
> từ các version trước (xem v1.22/v1.23) — không lặp lại phân tích ở đây.
>
> **2 khoảng trống thật, ĐÃ áp dụng version này:**
> 1. **Few-shot examples** — trước đây TOÀN BỘ prompt (cả `CoreIdeaExtractor` lẫn `VideoIdeaExtractor`)
>    là instruction-based thuần, không có 1 ví dụ input→output nào minh hoạ mức độ cụ thể cần có ở
>    cột "Lý do" (Bước 2). Đã thêm 2 dòng ví dụ (1 ý tưởng ĐẠT + 1 ý tưởng BỊ LOẠI, cùng lý do mẫu)
>    vào cuối BƯỚC 2 của `buildLayer2PromptText()` — chủ đề minh hoạ (ăn dặm) cố tình chọn KHÔNG
>    liên quan chuyên mục thật đang xử lý, có ghi rõ "chỉ minh hoạ" để model không chép nhầm thành
>    ý tưởng thật hoặc suy diễn ngược chuyên mục hiện tại phải giống ví dụ.
> 2. **Prompt caching cho vòng lặp goal-based** — hạ tầng (`AIRequestOptions`/`AnthropicProvider::
>    splitSystemMessages()`) đã hỗ trợ `role=system` + `cacheable=true` từ Phase 6 Aicem
>    (`Modules/Aicem/BuildPromptAction`), nhưng `RunLayer2ExtractionAction`/`RunVideoIdeaLayer2Action`
>    trước đó CHỈ gửi 1 message `role=user` — mỗi lượt lặp (`max_loop_iterations`, tối đa 3) gửi lại
>    NGUYÊN VĂN prompt gốc (có thể hàng chục nghìn ký tự) dù nội dung không đổi giữa các lượt. Đã
>    tách: prompt gốc → 1 message `system` cacheable=true (gửi y hệt mọi lượt, Anthropic tính phí
>    cache-read rẻ hơn từ lượt 2); đoạn "Bổ sung — vòng lặp tiếp theo" (hoặc câu mở đầu lượt 1) →
>    message `user`, không còn nối chuỗi vào prompt gốc. KHÔNG đổi nội dung/ý nghĩa prompt, chỉ đổi
>    vai trò message. OpenAI bỏ qua cờ `cacheable` an toàn (đã có sẵn cơ chế lọc ở `OpenAIProvider`),
>    không ảnh hưởng tenant dùng OpenAI.
>
> **Từ chối áp dụng (có lý do):** ReAct/ART (Automatic Reasoning and Tool-Use) cần hạ tầng tool-use
> loop — `AIProviderManager`/`AIRequestOptions` hiện chỉ hỗ trợ 1 lệnh gọi JSON-schema đơn, không có
> vòng lặp gọi tool giữa các bước suy luận; thêm vào đòi hỏi thiết kế lại toàn bộ tầng AI Provider,
> ngoài phạm vi 2 module này. Tree-of-Thoughts (khám phá song song nhiều nhánh suy luận) tốn thêm
> N lần gọi model mỗi nhánh mà không có bằng chứng BƯỚC 1→2→3 hiện tại (1 nhánh suy luận tường minh)
> đang cho kết quả kém — cùng loại rủi ro "framework flourish" đã từ chối nhiều lần (v1.6, v1.8,
> v1.18, v1.22, v1.23). APE (Automatic Prompt Engineer)/Active-Prompt là kỹ thuật META (tự động hoá
> VIỆC VIẾT prompt qua 1 pipeline tối ưu riêng), không phải kỹ thuật áp trực tiếp vào 1 prompt —
> không có hạ tầng đánh giá/tối ưu prompt tự động, ngoài phạm vi. Multimodal CoT/Graph Prompting
> không áp dụng được: pipeline 2 module này thuần văn bản (Layer 1 trích xuất text, không xử lý
> ảnh/video frame trực tiếp).
>
> Không đổi Layer 1/Layer 2 JSON schema (§5, §7).

> **v1.23 (Đối chiếu thepromptwarrior.com/p/5-prompt-frameworks-level-prompts — cùng chủ đề v1.22,
> nguồn khác — KHÔNG áp dụng gì, 2/5 kỹ thuật hoá ra ĐÃ CÓ SẴN dưới tên khác):** bài liệt kê 5
> framework — RTF (Role/Task/Format), Chain-of-Thought ("Let's think step-by-step"), RISEN (định
> nghĩa KHÁC bản ở §v1.22/`giadinh.md`: ở đây là Role/Instructions/Steps/End goal/Narrowing, không
> phải Role/Input/Scenario/Expectation/Nuance), RODES (Role/Objective/Details/Examples/**Sense
> Check**), Chain of Density (tóm tắt lặp 5 vòng, mỗi vòng nhồi thêm thực thể cụ thể mà giữ nguyên
> độ dài, để tăng "độ đậm đặc" thông tin).
>
> Đối chiếu phát hiện: **Chain-of-Thought và "Sense Check" (RODES) đã có sẵn trong codebase dưới
> tên `selfCheckLine()`** (`index.blade.php`, dùng cho `buildSummarizePromptText()`/
> `buildRewritePromptText()` ở cả `CoreIdeaExtractor` và `VideoIdeaExtractor` — module song sinh) —
> đúng kỹ thuật "phác thảo nháp trong đầu (ẩn) → tự rà lại xem có bịa/bỏ sót ràng buộc không → viết
> lại phần yếu → chỉ trả kết quả cuối", cùng docblock giải thích rõ vì sao KHÔNG áp dòng này cho
> `buildLayer2PromptText()` (BƯỚC 1→2→3 ở đó đã LÀ 1 dạng chain-of-thought tường minh hơn hẳn 1
> dòng nhắc chung — thêm vào sẽ trùng lặp, không phải thiếu sót). Tức là 2/5 kỹ thuật cốt lõi nhất
> của nguồn này đã được áp dụng từ trước, có chủ đích, có tài liệu — không phải khoảng trống.
>
> RTF là tập con của Role/Task/Format đã có ở mọi tool (xem v1.22). RISEN (Steps/End goal/
> Narrowing) trùng hoàn toàn BƯỚC 0-3/`goalText`/`constraintsText` đã có. Chain of Density (lặp 5
> vòng tăng mật độ) là kỹ thuật DUY NHẤT chưa có tương đương — nhưng đây là suy luận ẩn bên trong 1
> lượt gọi model, không kiểm chứng được model có thực sự lặp đủ 5 vòng nội bộ hay không (hộp đen),
> và KHÔNG có phản hồi/test thật nào cho thấy `buildSummarizePromptText()` hiện tại ra tóm tắt quá
> chung chung/thiếu thực thể cụ thể — thêm vào lúc này là suy đoán thiếu bằng chứng, đúng loại rủi
> ro "framework flourish" mà module đã từ chối nhiều lần trước đây (v1.6, v1.8, v1.18, v1.22).
>
> Không đổi Layer 1/Layer 2 JSON schema (§5, §7), không đổi code.

> **v1.22 (Đối chiếu `spec/giadinh.md` — "6 framework cấu trúc prompt AI cho marketing" —
> KHÔNG áp dụng gì, prompt hiện tại đã vượt qua từng framework):** nguồn là bài blog marketing
> (không trích số liệu/nghiên cứu, thuần kinh nghiệm cá nhân người viết qua ~1 năm dùng thử) liệt
> kê 6 khung cấu trúc PROMPT — C.O.R.E. (Context/Objective/Role/Example), C.R.E.A.T.E. (+ Audience/
> Tone/End Goal), R.I.S.E.N. (Role/Input/Scenario/Expectation/Nuance — cho phân tích/thought
> leadership), P.A.R.A. (Problem/Analysis/Recommendation/Action — cho problem-solving), D.A.R.E.
> (Describe/Act/Resonate/Elevate — cho storytelling), R.O.A.D. (Recognize/Options/Analyze/Decide —
> cho ra quyết định chiến lược) — nhắm tới người viết prompt ad-hoc, 1 lần, cho các tác vụ
> marketing rời rạc (LinkedIn post, sales page, whitepaper, crisis response...). Đối chiếu với
> `buildLayer2PromptText()` (§12.4, áp dụng song song cho cả `CoreIdeaExtractor` và
> `VideoIdeaExtractor`) qua từng framework:
>
> - **C.O.R.E./C.R.E.A.T.E.:** cả 4+2 thành phần (Context/Objective/Role/Example/Audience/Tone/
>   End Goal) ĐÃ có, cụ thể hoá theo domain hơn hẳn mức generic của framework — Role qua khối
>   "# Vai trò & Bối cảnh" (persona biên tập viên/kênh video); Context qua Hệ giá trị gia đình Việt
>   Nam (§12.10) + Category Content Foundation (§12.2); Objective/End Goal qua `goalText` (tiêu chí
>   3, BƯỚC 2); Audience qua khối riêng tách biệt (không chỉ mệnh đề phụ trong persona như trước
>   v1.21); Tone qua `styleSampleText` (ví dụ giọng văn THẬT trích từ editor, mạnh hơn 1 tính từ mô
>   tả tone chung chung như framework đề xuất); Example qua cấu trúc bảng cứng quy định ở BƯỚC 3.
>   Không có khoảng trống nào để vá.
> - **R.I.S.E.N.:** "Nuance" (đa góc nhìn) là thành phần duy nhất chưa map thẳng vào chỗ nào — nhưng
>   framework này tự nhận "Best for analysis & thought leadership", khác bản chất với nhiệm vụ hiện
>   tại là BRAINSTORM đa dạng (đã có cơ chế riêng cho việc này: 20-25 ý ứng viên đa dạng dạng/góc
>   nhìn ở BƯỚC 1, lọc qua 4 tiêu chí ở BƯỚC 2), không phải phân tích 1 luận điểm duy nhất cần cân
>   nhắc nhiều phía — áp Nuance vào đây sẽ làm sai lệch mục đích module chứ không phải vá lỗ hổng.
> - **P.A.R.A./R.O.A.D.:** 2 framework cho problem-solving/ra quyết định chiến lược (VD tối ưu tỉ lệ
>   mở email, chọn kênh quảng cáo) — không có tính năng tương ứng trong CoreIdeaExtractor/
>   VideoIdeaExtractor (2 module chỉ sinh Ý TƯỞNG bài viết/video, không chẩn đoán vấn đề kinh doanh
>   hay so sánh phương án chiến lược); cũng không có module nào khác trong codebase phù hợp hơn
>   (`Aicem` dùng Context Engineering Framework riêng — xem §6, `AICEM_Technical_Specification.md`
>   — không phải prompt ad-hoc dạng này). Ngoài phạm vi, không có nơi áp dụng.
> - **D.A.R.E.:** storytelling đầy đủ (Describe/Act/Resonate/Elevate) vượt xa phạm vi module hiện
>   tại (chỉ dừng ở gợi ý Ý TƯỞNG + tiêu đề đề xuất, KHÔNG viết nội dung bài/video đầy đủ — xem §11/
>   §12.3 "Ngoài phạm vi"). Phần duy nhất chạm storytelling là ràng buộc tiêu đề đề xuất — đã có,
>   dạng ràng buộc PHỦ ĐỊNH (không giật gân/không lợi dụng nỗi sợ hãi) chứ không cần thêm chỉ dẫn
>   "Resonate/Elevate" tích cực nào mới, vì module không viết nội dung để "resonate" trọn vẹn.
>
> **Kết luận:** nguồn không đưa vào codebase nội dung kỹ thuật mới nào — không phải vì framework
> sai, mà vì prompt hiện tại (kết quả 21 version tinh chỉnh dựa trên test thật với Grok/Claude, xem
> lịch sử §12.4 từ v1.5-v1.21) đã đạt/vượt từng thành phần của cả 6 framework một cách cụ thể hoá
> theo đúng domain (gia đình Việt Nam) thay vì dừng ở mức generic mà framework mô tả. KHÔNG đổi
> Layer 1/Layer 2 JSON schema (§5, §7), không đổi code.

> **v1.21 (Vá lỗ hổng ngữ cảnh biên tập + đổi khối "Gợi ý sản phẩm" + tách định dạng output theo
> đường đi):** 3 nhóm thay đổi, áp dụng SONG SONG cho `CoreIdeaExtractor` và `VideoIdeaExtractor`.
>
> **(1) Hệ giá trị gia đình (§12.10) — khối grounding từ 1 câu dài thành khối nhiều dòng.**
> `buildFamilyValuesGroundingLine()` → `buildFamilyValuesGroundingLines()` (trả mảng dòng), cộng
> `buildFamilyValuesBoundaryLine()` bản NÉN cho các tool KHÔNG sinh ý tưởng (Tái cấu trúc nội dung
> bên CoreIdeaExtractor; 6 tool tiêu đề/hook/Shorts/dàn ý/CTA/biên tập lời nói bên
> VideoIdeaExtractor — nhóm này trước đây hoàn toàn không có khung giá trị nào dù chính chúng sinh
> ra câu chữ ĐĂNG THẬT). Thêm `FAMILY_VALUE_EDITORIAL_NOTES` — lớp diễn giải theo `key` của riêng
> module, KHÔNG phải câu chữ văn bản gốc (config vẫn là nguồn sự thật duy nhất cho label/
> description/decision_ref): 4 định nghĩa trong config viết ở tầng CHÍNH SÁCH nên model không tự
> bắc cầu sang 1 ý tưởng cụ thể, đó là lý do nó hay trả ý tưởng dạng khẩu hiệu. 6 lỗ hổng được vá:
> phép thử "thế nào là PHỤC VỤ giá trị"; khung này là ĐỊNH HƯỚNG NỘI BỘ, cấm đề xuất bài phân tích/
> trích dẫn Quyết định 1189 (rủi ro model bịa điều khoản văn bản pháp quy); đa dạng mô hình gia
> đình; cạm bẫy gán vai trò theo giới (cách vi phạm giá trị Tiến bộ phổ biến nhất mà không hề "cổ
> suý" gì); phân biệt phong tục với hủ tục; cách xử lý khi 2 giá trị xung đột (an toàn thân thể/
> tinh thần + bình đẳng giới là mức sàn). Ranh giới cứng mở rộng khỏi "cha mẹ" sang MỌI thành viên.
>
> **(2) Lỗ hổng ngữ cảnh khác trong 2 blade.** CoreIdeaExtractor: `writer_insights` trước đây bị bỏ
> quên hoàn toàn dù endpoint `show()` vẫn trả về và VideoIdeaExtractor đã dùng; thêm khối "đối
> tượng độc giả" tường minh (trước chỉ là mệnh đề phụ trong persona); vá fallback
> `foundation.style_sample` (chọn chuyên mục SAU khi trích xuất thì mất giọng văn mẫu); mở rộng
> nhóm chủ đề rủi ro sang thai kỳ/người cao tuổi/sức khoẻ tinh thần + tách riêng bạo lực gia đình/
> xâm hại/trầm cảm sau sinh; nguồn ngoại phải chuyển cả BỐI CẢNH chứ không chỉ ngôn ngữ; 2 tool
> Tóm tắt/Tái cấu trúc nhận cảnh báo `extraction_confidence`/paywall và yêu cầu giữ nguyên MỨC ĐỘ
> CHẮC CHẮN của nguồn. VideoIdeaExtractor: danh sách chuyên mục ở Bước 0 giờ dùng hint
> `toHintArray()` (trọng tâm/góc nhìn/ý ĐÃ TỪ CHỐI) vốn server đã trả sẵn nhưng blade bỏ phí; sửa
> 1 TIỀN ĐỀ SAI trong prompt — `existing-articles` trả tiêu đề BÀI VIẾT nhưng 2 prompt "Kịch bản
> đầy đủ"/"Mô tả & Tag SEO" giới thiệu chúng là "danh sách video CÓ THẬT của kênh", khiến kịch bản
> mời người xem "xem tiếp video [tên]" không tồn tại (giờ chuyển sang gợi ý theo CHỦ ĐỀ); thêm ràng
> buộc phẩm giá cho thumbnail (trước đây ràng buộc chỉ áp cho tiêu đề, không phủ sang hình).
>
> **(3) "Gợi ý sản phẩm" + định dạng output — xem §12.4.1 và §12.11.** Theo yêu cầu người dùng:
> sản phẩm gợi ý đổi từ field per-idea `suggested_product` (1 chuỗi nullable, thực tế cho ra 0-2
> sản phẩm/lần chạy vì mỗi ý xét độc lập) sang danh sách RIÊNG cấp kết quả `suggested_products`,
> **tối thiểu 5** cho cả bộ ý tưởng, render thành bảng Markdown thứ 2. Đồng thời tách định dạng
> output theo ĐƯỜNG ĐI: nút "Chạy AI" trong app giữ structured output + PHP render bảng, còn nút
> "Copy prompt cho AI" (dán sang Grok/Claude) yêu cầu thẳng 2 bảng Markdown thay vì mô tả theo tên
> field — trước đây 2 đường dùng chung 1 bản mô tả field nên chat AI ngoài trả về đúng 1 khối JSON
> thô. Sửa cả `RunLayer2ExtractionAction` và `RunVideoIdeaLayer2Action` (schema + vòng lặp gom sản
> phẩm + renderer).

> **v1.20 (Bỏ Bảng 2 — Ý tưởng bị loại, đảo ngược quyết định v1.8 — ghi nhận đúng trạng thái code hiện tại):**
> theo yêu cầu người dùng, "Copy prompt cho AI" (§12.4) không còn xuất **Bảng 2** (ý tưởng bị loại
> kèm tiêu chí không đạt + lý do) — chỉ còn ĐÚNG 1 bảng (ý tưởng đạt cả 4 tiêu chí), đảo ngược quyết
> định đã ghi ở v1.8 (thêm Bảng 2 để "chứng minh bộ lọc thực sự chạy", dựa trên test thật với
> grok.com/claude.ai). Lần này KHÔNG có test/phản hồi cụ thể đối lập lại lý do v1.8 đã nêu — thuần
> là quyết định sản phẩm mới của người dùng ưu tiên output gọn hơn, chấp nhận đánh đổi mất khả năng
> verify bộ lọc có thực sự lọc hay không mà v1.8 từng nhắm tới giải quyết.
>
> **Lưu ý quan trọng — spec/code đã LỆCH NHAU trước khi sửa bản này:** khi rà soát để thực hiện thay
> đổi này, phát hiện `index.blade.php` (`buildLayer2PromptText()`, BƯỚC 3) **đã chỉ có 1 bảng từ
> trước** (`'Bảng — Ý tưởng ĐẠT cả 4 tiêu chí'`, không có `'Bảng 2'`/`'BỊ LOẠI'` nào trong code) —
> dù spec v1.8 mô tả đã thêm Bảng 2. Không rõ Bảng 2 bị bỏ khỏi code từ version nào (không có version
> nào sau v1.8 ghi nhận việc bỏ nó) — đây là 1 trường hợp spec mô tả sai hành vi thật của code trong
> nhiều version liền, chỉ phát hiện ra khi người dùng yêu cầu bỏ tính năng này. Bản v1.20 này KHÔNG
> cần sửa code (code đã đúng ý người dùng từ trước) — chỉ sửa lại §12.4 cho khớp đúng thực tế.
>
> Cùng thay đổi áp dụng cho module `VideoIdeaExtractor` (module riêng, dùng chung thiết kế prompt
> nhưng code độc lập) — module đó đã sửa code thật (có Bảng 2 thật, đã bỏ).

> **v1.19 (Nối pain_points/objections/decision_criteria với gợi ý ĐỊNH DẠNG ở BƯỚC 1 — tham khảo chapters-agency.com/blog/content-marketing-blog/content-formats-2026):** bài dẫn (content-marketing của 1 agency, không trích số liệu/nghiên cứu — đã ghi rõ để không nhầm là nguồn học thuật) nêu 1 ý CỐT LÕI đáng dùng dù nguồn không có citation: định dạng nội dung nên khớp mức độ SẴN SÀNG/nhận thức của độc giả, không chỉ đa dạng hoá ngẫu nhiên. `pain_points`/`objections`/`decision_criteria` (§12.6, v1.16) vốn ĐÃ phân tầng đúng 3 mức độ này (mới nhận ra vấn đề → còn nghi ngờ → sắp quyết định) nhưng trước giờ chỉ dùng làm NGUỒN Ý đưa vào TOP — chưa từng nối sang lựa chọn định dạng ở BƯỚC 1. Thêm mảng `formatHints` (`buildLayer2PromptText()`) — với mỗi field đang có giá trị trên `foundation`, ánh xạ sang 1 gợi ý định dạng: `pain_points` → giáo dục/hướng dẫn/checklist; `objections` → FAQ/"bóc trần ngộ nhận" (dạng mới thêm ở v1.18); `decision_criteria` → so sánh/"lý do chọn A thay vì B" (dạng mới thêm ở v1.18). Gộp thành 1 dòng, chèn NGAY SAU danh sách "dạng" chính ở BƯỚC 1 — ghi rõ đây là GỢI Ý ƯU TIÊN, không phải giới hạn cứng (khác nhóm ràng buộc "LOẠI ngay" ở Bước 2), vì 1 nguồn cụ thể có thể không đủ chất liệu cho đúng dạng được gợi ý. Không xuất hiện gì nếu foundation không có field nào trong 3 field trên (kể cả khi chưa chọn category). Không đổi Layer 1/Layer 2 JSON schema (§5, §7).

> **v1.18 (Đối chiếu conthunt.app/blog/content-ideas — CHỈ lấy 3 dạng ý tưởng tổng quát, từ chối phần còn lại):** nguồn này là bài content-marketing của 1 SaaS trend-intelligence (conthunt.app) nhắm tới creator VIDEO NGẮN (TikTok/Reels) — phần lớn framework ("Velocity-First Filtering", "Breakout Nodes", "ContHunt Benchmark") phụ thuộc công cụ trả phí đó, không tự áp dụng được; các chiến thuật khác (Visual Hook 1.5 giây, green screen, 4K B-roll, neural voice cloning) đặc thù cho định dạng video, không có điểm chạm với platform bài viết dài (`Post`); các số liệu (VD "Viral Conversion Rate 3.5x", "+75% retention") không có phương pháp luận kèm theo — không đưa vào codebase như sự thật đã kiểm chứng, đúng cảnh báo "LLM-generated boilerplate slop" đã ghi ở v1.17(4). Đã trao đổi lại với người dùng, CHỈ lấy phần format-agnostic, không phụ thuộc vendor/video: 3 "dạng" ý tưởng mới thêm vào danh sách đa dạng hoá ở BƯỚC 1 (`buildLayer2PromptText()`, §12.4) — "bóc trần ngộ nhận" (Mythbuster — chỉ ra 1 quan niệm phổ biến nhưng sai + dẫn chứng đúng), "phát hiện từ dữ liệu" (Data Reveal — chỉ dùng khi ≥2 nguồn, tổng hợp điểm chung/khác biệt thành 1 nhận định, khác `common_keywords`/ý tưởng tổng hợp chéo BẮT BUỘC đã có ở §12.4 vì đây chỉ là 1 LỰA CHỌN thêm trong danh sách brainstorm, không phải yêu cầu cứng), "lý do chọn A thay vì B" (Decision Logic — chốt hẳn 1 khuyến nghị cụ thể, khác dạng so sánh đã có ở chỗ không liệt kê ưu nhược 2 bên mà đưa ra 1 lập trường rõ ràng). Không đổi Layer 1/Layer 2 JSON schema (§5, §7) — thuần thêm gợi ý trong nhiệm vụ sinh ý tưởng.

> **v1.17 (Đối chiếu sapient.coffee/posts/2026/context-engineering-2026 — 3 điểm vá vào `buildLayer2PromptText()`, không đổi Layer 1/Layer 2 JSON schema §5/§7):**
>
> (1) **Chế độ đa chuyên mục khi chưa chọn category (mở rộng §12.10 BƯỚC 0):** nguồn RỘNG hơn 1 chuyên mục (sản phẩm/dịch vụ gia đình, chủ đề chạm nhiều mặt đời sống) trước đây bị ép chọn ĐÚNG 1 chuyên mục — nay AI được chọn 2-3 chuyên mục liên quan nhất, sinh ý tưởng phủ đều, mỗi ý gắn đúng 1 chuyên mục qua cột mới "Chuyên mục đề xuất" (chỉ xuất hiện ở chế độ chưa chọn category). Tiêu chí 1-2-4 ở Bước 2 đánh giá theo chuyên mục CỦA TỪNG Ý (không dùng 1 khuôn chung cho cả bảng).
>
> (2) **Rò rỉ ràng buộc cứng ở chế độ đa chuyên mục — lỗi phát sinh từ (1):** `rejected_ideas` (Decision Log, §12.7) trước đây chỉ lộ ra cho category ĐÃ CHỌN (qua biến `foundation`) — khi chưa chọn category, AI có thể vô tình đề xuất lại đúng ý đã bị từ chối cho 1 trong các category nó tự gán ở Bước 0, vì hint rút gọn trong "Danh sách chuyên mục" trước đó chỉ có `core_focus`/`unique_angle`. Vá: thêm hint `rejected_ideas` (cắt ngắn 160 ký tự, cùng cơ chế `truncateForHint()` đã có) cho MỌI category trong danh sách, không chỉ category đã chọn — đúng tinh thần *progressive disclosure* (đủ tín hiệu để tránh sai, không tải nguyên văn foundation của category chưa chắc được chọn).
>
> (3) **Nhận diện nguồn thương mại (`content_type_signal`/`declared_content_type` chứa "product", đã có sẵn từ Layer 1 §5.2/§6.1.1) — field mới `hasProductLikeSource`:** khi nguồn là trang sản phẩm/dịch vụ, Bước 1 thêm chỉ dẫn chống viết bài PR (ý tưởng phải đứng về phía độc giả — hướng dẫn chọn/so sánh trung lập/giải đáp lo ngại, không ca ngợi 1 thương hiệu) + tôn trọng giá trị "ấm no" cụ thể hoá vào ngữ cảnh mua sắm (không tạo cảm giác phải chi tiêu vượt khả năng mới là chăm lo gia đình).
>
> (4) **Pink Elephant / Distraction (bài dẫn: chỉ dẫn thuần phủ định "KHÔNG làm X" dẫn hướng chú ý model vào chính X):** `buildFamilyValuesGroundingLine()` (§12.10) trước đây MỞ ĐẦU bằng danh sách ràng buộc cấm ("RÀNG BUỘC CỨNG — loại mọi ý tưởng...") — đổi thứ tự: dẫn bằng MỤC TIÊU TÍCH CỰC trước (ý tưởng nên giúp gia đình độc giả tiến gần giá trị nào, qua lợi ích thực tế), danh sách ví dụ vi phạm chuyển xuống làm phần LÀM RÕ ranh giới, không còn là câu mở đầu của cả khối. Nội dung ràng buộc không đổi, chỉ đổi THỨ TỰ trình bày.
>
> Điểm đã đối chiếu nhưng KHÔNG cần vá (đã đúng thiết kế từ trước): *Context Pollution/Attention Degradation* (bài dẫn số liệu NoLiMa giống hệt neosage.io đã dẫn ở §12.5 — accuracy 99.3%→69.7% qua 32k token) — module đã chọn "cảnh báo kích thước, không tự cắt nội dung" từ v1.9, cùng kết luận với bài này; *Hierarchy — rule cụ thể đè rule chung* — đã có sẵn qua pattern `brief.audience || foundation?.audience` (input phiên làm việc đè giá trị bền vững) và BƯỚC 0/2 (chuyên mục cụ thể đè mặc định); *Spec-Driven Development* — quy trình của cả file spec này (viết spec trước/song song code) đã đúng tinh thần bài dẫn.

> **v1.16 (Hệ giá trị gia đình Việt Nam — chuẩn nền tảng cố định trong context engine, + objections/decision_criteria):** 2 thay đổi, cùng nhóm "mở rộng Category Content Foundation" (§12):
>
> (1) **`family_values_focus` (field mới, §12.10) — KHÁC BẢN CHẤT mọi field khác trong §12.2:** đối chiếu `spec/giadinh.md` — Hệ giá trị gia đình Việt Nam (4 trụ cột: ấm no/hạnh phúc/tiến bộ/văn minh) do Thủ tướng Chính phủ ban hành qua **Quyết định 1189/QĐ-TTg ngày 02/07/2026** (không phải Nghị định) — là CHUẨN NỀN TẢNG của platform (familiesforlife, nội dung cho gia đình Việt), không phải ngữ cảnh editor tự viết như `core_focus`/`pain_points`/`objections`/... Định nghĩa 4 giá trị (label + mô tả) sống ở `config('core_idea_extractor.family_values')` — NGUỒN SỰ THẬT DUY NHẤT, không hardcode lặp lại ở PHP/blade/JS chỗ khác. Khối này CỐ ĐỊNH, LUÔN được inject vào TOP của "Copy prompt cho AI" (§12.4) bất kể có chọn chuyên mục hay không (`buildFamilyValuesGroundingLine()`), kèm 1 câu chặn cứng "KHÔNG đi ngược các giá trị này" (không cổ suý bất bình đẳng giới/bạo lực gia đình/hủ tục lạc hậu/lối sống thiếu chuẩn mực). Cột mới `family_values_focus` (JSON, mảng key) trên `cie_category_foundations` — editor TICK (không tự viết) giá trị nào chuyên mục ưu tiên phục vụ, validate qua rule `in:...` đọc ĐỘNG từ config (không hardcode 2 nơi). Khi có, thêm 1 dòng ƯU TIÊN bổ sung vào TOP (không thay thế khối cố định). Xem §12.10.
>
> (2) **`objections`/`decision_criteria` (field mới, cùng nhóm §12.2):** đối chiếu bài context-engineering (animalz.co) — tách khỏi `pain_points` (vốn chỉ là khó khăn/câu hỏi thực tế): `objections` là LÝ DO CÒN NGHI NGỜ/CHƯA TIN khiến độc giả chưa hành động; `decision_criteria` là TIÊU CHÍ họ dùng để so sánh/chọn giữa các lựa chọn. Gộp chung vào `pain_points` khiến editor bỏ sót 1 trong 2 hoặc viết lẫn lộn. Cùng nhóm validate/quyền/UI với các field ad-hoc khác (không Gate riêng), đưa vào TOP của "Copy prompt cho AI" ngay sau `pain_points`.
>
> Cả 2 field mới đều: field text tự do (`objections`/`decision_criteria`) hoặc mảng key cố định (`family_values_focus`) trên `cie_category_foundations` — **không đổi Layer 1/Layer 2 JSON schema (§5, §7)**, thuần field UI/prompt-template như các field §12.2 khác.

> **v1.15 (Tinh chỉnh nhỏ — noise trong thân bài, common_keywords chặt hơn, word_count/publish_date cho lean payload, gộp headings/sections):** 5 điểm nhỏ, không phải bug lớn:
>
> (1) **Noise "trong thân bài"** (không phải widget/sidebar mà `stripNoise()` đã lọc trước đó): byline ("bởi Team")/ngày đăng/lượt xem/link "Mua ngay" đôi khi vẫn lẫn trong `sections`/`main_content`. Thêm từ khoá `byline`, `post meta`, `entry meta`, `view count`, `reading time`, `add to cart`, `buy now`, `cta button` vào `NOISE_KEYWORDS` (viết cách nhau bằng khoảng trắng, KHÔNG phải gạch nối — `stripNoise()` đã translate `-`/`_` thành khoảng trắng trước khi so khớp). Cùng cơ chế/ngưỡng an toàn (tỉ lệ chữ tối đa so với `<body>`) như từ khoá đã có, không phải rule riêng. Tiện thể chuẩn hoá luôn `"\r\n"`/`"\r"` (line ending Windows) về `"\n"` trong `cleanText()` — trước đây sót lại thành ký tự lạ.
>
> (2) **`common_keywords` khoan dung hơn với biến thể dấu câu:** "Omega 3" (nguồn A) vs "Omega-3" (nguồn B) — cùng khái niệm, chỉ khác dấu câu — trước đây so khớp NGUYÊN VĂN nên bỏ lỡ. `ExtractBatchResultData::buildCommonKeywords()` thêm bước chuẩn hoá gạch nối/gạch dưới về khoảng trắng trước khi so khớp — vẫn thuần so khớp CHUỖI (không suy luận ngữ nghĩa), chỉ khoan dung hơn. `summary_note` KHÔNG đổi — đã làm rõ ở v1.14: field này chỉ nói về ĐỘ PHỦ FETCH theo đúng thiết kế gốc (§7.1), không phải "tín hiệu tổng hợp chéo nguồn" (vai trò đó là của `common_keywords`).
>
> (3) **`word_count` xuất ra ở single-URL** (`RawExtractionData`) — trước đây chỉ có ở batch (`BatchSourceResultData`, §7.1.1), coi là "dữ liệu nội bộ" cho single-URL. Nay nhất quán cả 2 mode, giúp đánh giá độ mỏng/dày nguồn mà không cần tự đếm từ trên `main_content`.
>
> (4) **`buildAiPayload()` (lean payload "Copy prompt cho AI") — 3 thay đổi:** (a) thêm `publish_date`/`word_count` — đã có sẵn ở Layer 1, không cần trích gì mới, nhưng trước đây bị loại khỏi payload rút gọn; `publish_date` giúp đánh giá độ mới bằng field CHUẨN HOÁ (site tự khai qua article:published_time/JSON-LD) thay vì AI phải tự parse ngày tháng lẫn trong text tự do; (b) bỏ hẳn `headings` phẳng khi đã có `sections` — `sections[].heading` luôn chứa cùng danh sách text heading, gửi cả 2 là trùng lặp tốn token (headings rỗng ⟺ sections rỗng nên không mất thông tin); (c) thêm `_schema_notes` (chuỗi ngắn) NGAY TRONG `promptData` — trước đây chú giải field chỉ nằm ở câu dẫn trong prompt, nếu người dùng copy riêng JSON (không kèm phần dẫn) sẽ mất ngữ cảnh; nay JSON tự mô tả được.
>
> Ngoài phạm vi (v1.15, đã cân nhắc nhưng KHÔNG làm): `products_mentioned`/`entities` (tách brand/tên sản phẩm thành field riêng) — cùng lý do đã nêu ở v1.12/v1.14, đây là nhận diện thực thể (NER), cần suy luận ngữ nghĩa chứ không thuần rule cú pháp như các sửa ở trên; `keywords[]` (đã ưu tiên heading ngắn từ v1.14) đã phần nào surface được tên sản phẩm/brand khi chúng xuất hiện dưới dạng heading, coi là đủ cho nhu cầu hiện tại.

> **v1.14 (content_type_signal + keyword quality — phản hồi thật với nội dung dinh dưỡng/nuôi dạy con):** Phản hồi chỉ ra: (1) `content_type_signal` vẫn `null` dù headings rõ ràng (danh sách dưỡng chất, mục "cách phòng dị ứng") vì bài liệt kê N mục kiểu này thường KHÔNG đánh số ở text heading/title (mỗi mục 1 heading tên riêng, VD "DHA", "Omega 3", không phải "1. DHA") — heuristic listicle cũ chỉ nhìn pattern số trong text nên bỏ sót; (2) `keywords` nghiêng hẳn về tên brand (VD "Hi-Q", "S-Mom Club" từ meta[name=keywords] — vốn là SEO/marketing tag của shop) hơn hẳn chủ đề THẬT của bài (DHA, Omega, dấu hiệu dị ứng...); (3) `common_keywords` (batch) trống dù 2 nguồn cùng chủ đề, vì hệ quả trực tiếp của (2) — không nguồn nào có keyword thật sự phản ánh chủ đề để giao nhau.
>
> Sửa: (a) `classifyContentTypeSignal()` thêm `has_numbered_lists` (đã có sẵn ở `source_structure`, tính từ `<ol>` thật trong main_content) làm 1 tín hiệu listicle nữa, độc lập với pattern số trong heading/title; thêm vài cụm HOWTO_TITLE_KEYWORDS liên quan sức khoẻ/nuôi con ("phòng ngừa", "phòng tránh", "biện pháp", "lưu ý khi"); thêm fallback **`educational`** (độ tin cậy THẤP hơn các nhãn khác, có ghi chú rõ trong docblock) khi bài có ≥3 heading có ý nghĩa nhưng không khớp rule cụ thể nào — vẫn hơn `null`, dùng cho bài kiến thức/y tế/nuôi dạy con nhiều phần không rơi gọn vào listicle/how_to/review/faq/product. (b) `extractKeywords()` thêm nguồn mới `extractHeadingKeywords()` — heading NGẮN (≤4 từ, không phải câu hỏi, không trùng title) tự nó CHÍNH LÀ 1 chủ đề/thực thể cụ thể của bài (VD "DHA", "Omega 3"), xếp ƯU TIÊN ĐẦU danh sách (trước cả tên sản phẩm JSON-LD, xa hẳn phía trước meta keywords/article:tag/JSON-LD keywords chung — nhóm này đẩy XUỐNG CUỐI vì phản hồi thực tế cho thấy thường chỉ là brand/shop, ít phản ánh đúng chủ đề nội dung). (c) `common_keywords` không cần sửa code — tự nhiên cải thiện nhờ (b): 2 nguồn cùng nhắc 1 chủ đề dưới dạng heading giống hệt nhau (VD cùng heading "DHA") giờ khớp được dù brand mỗi nguồn khác nhau hoàn toàn.
>
> **`summary_note` — làm rõ, KHÔNG phải bug:** phản hồi hiểu nhầm field này là "tín hiệu tổng hợp chủ đề chéo nguồn" — thực ra theo đúng thiết kế gốc (§7.1), `summary_note` CHỈ nói về ĐỘ PHỦ FETCH (bao nhiêu nguồn thành công/bị chặn/lỗi), `null` khi mọi nguồn đều `success` là ĐÚNG hành vi, không phải thiếu sót. Field đóng đúng vai trò "tín hiệu tổng hợp chéo nguồn" là `common_keywords` (đã có từ trước) — xem (c) ở trên.
>
> **`sections` (v1.14, THAY CHO "structured core ideas" đầy đủ):** Yêu cầu gốc là `key_points[]`/`claims[]`/`prevention_steps[]`/`nutrients_benefits[]` tự sinh bằng rule PHP — đã KHÔNG chọn phương án này vì vẫn là tự động hoá Layer 2 (diễn giải Ý NGHĨA nội dung) bằng heuristic, đi ngược §12.3 như đã trao đổi ở v1.12, và rủi ro suy diễn sai cao hơn hẳn lợi ích với nội dung sức khoẻ/dinh dưỡng trẻ em. Đã trao đổi lại với người dùng, chọn phương án AN TOÀN HƠN: field mới `sections` (§5.2/§7 — cùng nhóm field Layer 1 thuần trích xuất như `source_structure`, `headings`) — THUẦN TỔ CHỨC LẠI `main_content` đã có theo ranh giới heading (`ExtractRawContentAction::buildSections()`), giữ NGUYÊN VĂN từng đoạn, KHÔNG gắn nhãn ý nghĩa/diễn giải gì (không hallucinate vì không suy luận). Cách khớp: so khớp TUẦN TỰ từng đoạn đã tách của `main_content` (ranh giới dòng trống) với `headings` (thật hoặc pseudo) theo đúng thứ tự — đoạn nào trùng khớp y hệt 1 heading đang chờ → bắt đầu section mới. Không có heading nào → `sections = []` (dùng thẳng `main_content`, tránh trùng lặp dữ liệu). **Batch/single đều phải tính `sections` LẠI trên `main_content` SAU KHI đã cắt theo ngân sách ký tự** (`truncateBatchMainContent()`/`truncateMainContent()`) — nếu tính trên bản chưa cắt sẽ làm `sections` rò rỉ nội dung đã bị cắt bỏ, vô hiệu hoá ngân sách ký tự/nguồn của batch (§12.5). "Copy prompt cho AI" (`buildAiPayload()`) dùng `sections` THAY CHO `main_content` khi có (tránh gửi trùng lặp cùng 1 nội dung 2 dạng), giúp AI khỏi phải tự tách đoạn theo heading.
>
> **v1.13 (Selector riêng theo từng URL trong batch):** `main_content_selector` (batch) trước đây là 1 giá trị DUY NHẤT áp dụng cho MỌI URL — không hợp lý vì các nguồn trong 1 batch thường thuộc nhiều domain khác nhau, mỗi domain có bố cục CSS/template riêng, 1 selector chung hiếm khi đúng cho tất cả. Thêm field mới `main_content_selectors` (`ExtractBatchRequestData`) — mảng override theo ĐÚNG index của `urls` (`urls[i]` dùng `main_content_selectors[i]` nếu có giá trị), không bắt buộc cùng độ dài (`nullable|array`, mỗi phần tử `nullable|string|max:255`). Thứ tự ưu tiên chọn selector cho 1 URL (`CoreIdeaExtractorController::resolveSelectorForUrl()`): override riêng → `main_content_selector` chung (giữ vai trò fallback/mặc định, KHÔNG bỏ field này) → tự động (`resolveContentRoot()`).
>
> UI: bản đầu (v1.13 draft) thử cú pháp gõ tay `URL | selector` ngay trong textarea — phản hồi ngay sau đó chỉ ra đây là UX kém: cú pháp ẩn, không có ô nhập riêng biệt tương ứng trực quan với từng URL, dễ gõ sai/khó sửa. Sửa NGAY trong cùng version: textarea giữ nguyên thuần "mỗi dòng 1 URL" như trước (không cần cú pháp đặc biệt), thêm 1 danh sách riêng bên dưới — mỗi URL đã parse có 1 dòng kèm 1 ô input selector RIÊNG (`selectorOverrides`, state JS dạng object key theo chính URL, không phải theo index/thứ tự dòng — tránh override bị lệch sang URL khác khi người dùng thêm/xoá/sắp xếp lại dòng trong textarea). Để trống ô = dùng `main_content_selector` mặc định. `parsedSources()` (JS) ghép `parsedUrls()` (giữ nguyên logic parse/dedup cũ, không đổi) với `selectorOverrides` theo URL.
>
> **KHÔNG đổi Layer 1/Layer 2 JSON schema (§5, §7)** — thuần thêm field request đầu vào (`main_content_selectors`), response mỗi `sources[]` không đổi shape.
>
> **v1.12 (Chất lượng trích xuất + lean AI payload — phản hồi thật sau khi test "Copy prompt cho AI" với Claude/Grok trên 1 trang sản phẩm):** Phản hồi chỉ ra 2 vấn đề: (1) trang dùng đoạn/bôi đậm đánh số làm "tiêu đề mục" thay vì `<h1>`-`<h3>` thật (VD "1. CÔNG DỤNG") khiến `headings:[]`/`content_type_signal:null`/`keywords` chỉ có tên shop dù nội dung có cấu trúc rõ ràng — lỗi extraction thật, không phải nguồn thiếu tổ chức; (2) payload dán vào chat AI mang theo nhiều field kỹ thuật thuần không giúp ích cho brainstorm ý tưởng, tốn token vô ích. Sửa: (a) `extractHeadings()` thêm fallback `extractPseudoHeadings()` — CHỈ chạy khi không có heading thật nào, nhận diện đoạn/`<li>` đánh số hoặc `<strong>`/`<b>` chiếm trọn cả đoạn cha làm heading giả định (level 2); (b) `content_type_signal` thêm nhãn `product`/`product_faq` (kết hợp, không tách mảng — giữ đúng quyết định "field đơn giản, string không phải mảng" đã có), ưu tiên xét TRƯỚC listicle/how_to/review/faq vì dựa trên `declared_content_type` (dữ liệu publisher tự khai, đáng tin hơn heuristic heading/title đoán mò); (c) `extractDeclaredContentType()` thêm fallback đọc `@type` JSON-LD khi thiếu `og:type` (nhiều trang WooCommerce/Shopify chỉ khai schema.org qua JSON-LD); (d) `extractKeywords()` thêm tên sản phẩm + thương hiệu từ JSON-LD Product (`name`/`brand.name`, đọc ĐÚNG block có `@type` khớp "product" — tránh lẫn field cùng tên ở block Organization/BreadcrumbList khác cùng trang) đặt lên ĐẦU danh sách; (e) "Copy prompt cho AI" (§12.4) dùng `buildAiPayload()` (client-side, JS-only) thay vì gửi nguyên `this.result` — loại field kỹ thuật thuần (`final_url`/`failure_type`/`http_status`/`content_hash`/`duplicate_of`/`fetched_at`/`error_message`/`canonical_url`/`content_category`/`publish_date`/`date_modified`/`author`), batch: nguồn `blocked`/`error` rút gọn còn `{url, domain, status, failure_type}`. **KHÔNG đổi Layer 1/Layer 2 JSON schema (§5, §7)** — (a)-(d) là cải thiện chất lượng field ĐÃ CÓ (không thêm field mới), (e) thuần biến đổi ở tầng UI trước khi dán vào clipboard, "Copy JSON"/API response (`prettyJson()`) không đổi, vẫn đầy đủ nguyên trạng cho debug/audit. `main_content` trong `buildAiPayload()` CHỈ bị cắt khi `extraction_confidence = "low"` (mức Layer 2 không bao giờ chạy tới, §4/§7) — CỐ Ý KHÔNG áp dụng cho `medium`/`high` (nguồn ĐANG được dùng để sinh ý), giữ đúng quyết định đã có ở §12.4/§12.5 (đã thử cắt content cho mọi trường hợp rồi BỎ vì phá mất chiều sâu) — đây là 1 quy tắc hẹp hơn, không mâu thuẫn với quyết định đó. Ngoài phạm vi (v1.12, đề xuất riêng đã cân nhắc nhưng KHÔNG làm): trích xuất `structured` (key_benefits/claims/faqs/key_actives...) bằng rule PHP tự chế cho `declared_content_type = "product"` — về bản chất là tự động hoá Layer 2 (diễn giải ý nghĩa) bằng heuristic thay vì AI, đi ngược §12.3 ("không tự động hoá Layer 2"), và rule tự chế cho domain sức khoẻ/skincare rủi ro suy diễn sai claim y khoa cao hơn hẳn lợi ích — để dành cho thảo luận riêng nếu cần.
>
> **v1.11 (Tránh trùng lặp ý tưởng — §12.7/§12.8):** Tham khảo matthopkins.com (Decision Log — ghi lại quyết định + lý do để AI không đề xuất lại thứ đã bị bác bỏ) + memgraph.com (curation/entity resolution — tránh trùng lặp qua nhận diện thực thể đã tồn tại), 2 bài độc lập cùng chỉ vào đúng khoảng trống đã ghi ở §11/§12.3 từ trước ("không tự động kéo tag/bài viết hiện có vào prompt"). Làm CẢ 2 cách bổ sung cho nhau: (1) `rejected_ideas` (§12.7) — field tay mới trên Category Content Foundation, editor tự ghi ý tưởng đã cân nhắc và quyết định KHÔNG viết kèm lý do (tribal knowledge, không suy ra được từ dữ liệu); (2) `ListCategoryExistingArticlesAction` (§12.8) — tự động kéo tiêu đề bài ĐÃ publish trong category (qua `PostCategory::articles()` có sẵn của Post, không cần Post sửa gì), fetch on-demand khi chọn category (không preload cho mọi category). Cả 2 đưa vào TOP của "Copy prompt cho AI" + có chỉ dẫn tường minh "KHÔNG đề xuất trùng" ở BOTTOM (không chỉ đưa context suông). KHÔNG đổi Layer 1/Layer 2 JSON schema (§5, §7).
>
> **v1.10 (Pain Points — §12.6):** Tham khảo case study B2B thought-leadership (nghiên cứu khách hàng định kỳ — phỏng vấn + phân tích sales call để tìm pain point — là NỀN của content strategy, không chỉ mô tả trừu tượng). Thêm field `pain_points` vào Category Content Foundation (bảng `cie_category_foundations`, cột mới sau `content_goals`) — câu hỏi/khó khăn thường gặp của độc giả rút ra từ nghiên cứu thực tế (khảo sát/feedback/câu hỏi lặp lại), khác với `core_focus`/`unique_angle`/`content_goals` (mô tả tĩnh do editor tự viết). Đưa vào TOP của "Copy prompt cho AI" (§12.4) và tóm tắt hiển thị trên trang trích xuất chính. KHÔNG đổi Layer 1/Layer 2 JSON schema (§5, §7) — vẫn thuần field UI/prompt-template như core_focus/unique_angle/content_goals.
>
> **v1.9 (Cảnh báo kích thước prompt — §12.5):** Tham khảo https://blog.neosage.io/p/the-ai-application-layer-where-context — độ chính xác AI giảm dần khi context dài + nhiệm vụ phức tạp, KHÔNG có ngưỡng an toàn cụ thể (bài viết dẫn số liệu GPT-4o: 99.3% → 69.7%). Thêm cảnh báo NHẸ (không chặn) trên UI khi dữ liệu dựng prompt vượt 50.000 ký tự (~12.500 token ước tính, đo trên `prettyJson()` thật — không phải giả định) — gợi ý người dùng tự giảm số nguồn/chạy theo đợt nhỏ hơn nếu câu trả lời AI không ổn. CỐ Ý KHÔNG lặp lại cách tiếp cận đã bỏ ở v1.6 (tự động cắt `main_content`) — bài học từ lần đó: cắt nội dung phá mất chiều sâu chắc chắn 100%, trong khi cảnh báo để người dùng tự quyết không đánh đổi gì.
>
> **v1.8 (Prompt — dựa trên test thật với grok.com/claude.ai):** Test thực tế cho thấy mọi ý tưởng trả về đều "Có" tuyệt đối ở cả 3 tiêu chí — vì AI tự lọc TRƯỚC khi hiển thị (đúng yêu cầu "chỉ giữ ý tưởng thoả cả 3"), khiến cột Có/Không thành "con dấu" không verify được. 3 tinh chỉnh cho BƯỚC nhiệm vụ ở §12.4: (1) thêm cột **"Lý do"** (1 câu) vào Bảng 1 — không còn Có/Không suông; (2) thêm **Bảng 2 — Ý tưởng bị loại** kèm tiêu chí không đạt + lý do, để thấy bộ lọc thực sự hoạt động chứ không chỉ ẩn ý tưởng không đạt; (3) khi có ≥2 nguồn thành công (batch), bắt buộc ít nhất 1 ý tưởng **tổng hợp chéo nhiều nguồn** (dạng insight khó sao chép nhất, chỉ áp dụng khi đủ ≥2 nguồn — single-URL hoặc batch chỉ 1 nguồn thành công thì bỏ qua). Nhiệm vụ giờ chia rõ 3 bước (sinh ý tưởng chưa lọc → đánh giá từng tiêu chí → xuất 2 bảng) thay vì 1 bước lọc-luôn như trước.
>
> **v1.7 (Spec ↔ code residuals):** (1) Thêm §7.1 "Batch Output Schema" — khoá đúng shape `extract-batch` ĐÃ CHẠY THẬT trong code/UI từ trước nhưng chưa từng lên spec (envelope `topic/brief/requested_count/source_coverage/summary_note/sources/processed_at` + shape 1-duy-nhất của mỗi phần tử `sources[]`, liệt kê đủ giá trị `failure_type`, quy tắc `source_structure = null` khi `status != success`) — đây là khoảng trống spec ↔ code rõ nhất trước bản này; (2) thêm 1 câu làm rõ ở §13: advisory note của `source_structure` chỉ là gợi ý (append `notes`), KHÔNG ảnh hưởng `extraction_confidence`/`error`/`status`; (3) thêm ghi chú ở §7: field Layer 2 lý thuyết (`article_type`/`thesis`/`core_ideas`/...) chưa từng được endpoint nào trả về thật — khoảng cách đã có từ thiết kế ban đầu, không phải lỗi mới. KHÔNG đổi ngưỡng/logic/schema nào đang chạy — thuần cập nhật tài liệu cho khớp implementation.
>
> **v1.6 (Prompt hardening — §12.4):** Thêm 1 dòng khoá format CỨNG ở cuối cùng của "Copy prompt cho AI" ("Chỉ trả về bảng Markdown. Không viết giải thích, không mở đầu, không kết luận.") — giảm AI trả lời dài dòng trước/sau bảng. (Có thử thêm rút gọn `main_content` trong JSON dựng prompt còn ~500 ký tự để giảm "phình" prompt, nhưng ĐÃ BỎ NGAY trong cùng phiên bản sau phản hồi: 500 ký tự chỉ đủ 1 đoạn mở bài, phá mất chiều sâu nội dung nguồn — trong khi cả module tồn tại để nghiên cứu SÂU nguồn tham khảo. Cái mất là chắc chắn, cái được [model "chú ý" cuối prompt tốt hơn] chỉ là suy đoán và không đáng với model hiện đại/context window lớn có ranh giới cấu trúc rõ ràng. Server đã tự giới hạn kích thước sẵn — single 100.000 ký tự, batch 12.000 ký tự/nguồn — nên không cần thêm 1 lớp cắt nữa ở client.)
>
> **v1.5 (Source Structure Signal):** Thêm §13 + field `source_structure` vào §5.2/§7 — tín hiệu THÔ (có bảng/danh sách số/tỉ lệ heading dạng câu hỏi) cho biết nguồn tham khảo đã "cấu trúc tốt cho AI trích xuất" tới đâu (tham khảo https://kime.ai/blog/structure-content-for-llm-extraction), kèm ghi chú advisory khi nguồn đã tối ưu tốt. ĐÂY LÀ THAY ĐỔI SCHEMA ĐẦU TIÊN từ v1.3 — mọi response (kể cả batch, kể cả `extraction_confidence=low`) từ nay có thêm field `source_structure` (§7), downstream cần cập nhật để đọc field mới này nếu parse JSON theo schema cứng.
>
> **v1.4 (Category Content Foundation):** Thêm §12 — ngữ cảnh biên tập lưu bền vững theo TỪNG `PostCategory` ("Business Foundation Document" áp dụng cho biên tập nội dung, xem https://afterhoursai.substack.com/p/how-to-train-ai-to-extract-content), thay cho việc phải gõ lại `audience/goal/constraints/style_sample` mỗi lần chạy. Module có Eloquent Model đầu tiên (`CategoryContentFoundation`) — không đổi gì ở Layer 1/Layer 2 JSON schema (§5, §7), chỉ bổ sung 1 lớp prefill + prompt-template ở tầng UI.
>
> **v1.1:** Gộp output về 1 schema duy nhất cho mọi trường hợp (thêm field `error`, các field Layer 2 để `null` thay vì bị bỏ khỏi JSON khi `low`) + nới rule `core_ideas` cho trường hợp `medium` + nội dung mỏng (cho phép < 3 ý, không độn ý) — xem §6.1.4, §7, §8, §9.
>
> **v1.2 (Consistency & Rule fixes):** (1) Chuẩn hóa ngưỡng từ về DUY NHẤT 1 mốc `< 200 từ → low` (xoá vùng xám 150–199 từ giữa §5.4 và §9 cũ), `< 150 từ` chỉ còn là điều kiện phụ set `error=true`; (2) thay mô tả cảm tính "nội dung mỏng" bằng "Thin content trigger" định lượng (`medium` + word_count<300 hoặc ≤1 heading có ý nghĩa) ở §6.1.4/§8; (3) sửa wording pipeline §4 khớp hành vi thật (`low` → skip Layer 2 nhưng vẫn trả full schema, không phải "dừng & trả lỗi"); (4) đánh dấu rõ `publish_date`/`author` (§5.2) là dữ liệu nội bộ Layer 1, không propagate sang Final Output Schema (§7).
>
> **v1.3 (Residual minor fixes):** (1) Định nghĩa tường minh "headings có ý nghĩa" = heading còn lại sau khi loại noise §5.3, không tính heading trang trí/label rỗng nghĩa; (2) làm rõ cách đếm `word_count` (đếm trên `main_content` đã làm sạch; ngôn ngữ không phân từ bằng khoảng trắng thì ước lượng theo cảm nhận ngữ nghĩa, không cần đếm chính xác) — xem §6.1.4; (3) thêm bảng tín hiệu nhận diện cho từng `article_type` để giảm variance giữa các lần chạy — xem §6.1.1.

---

## 1. Overview

**CoreIdeaExtractor** là module tự động trích xuất **ý chính trọng tâm (Core Ideas)** từ bất kỳ URL bài viết nào, phục vụ mục đích tìm kiếm và chuẩn hóa ý tưởng để viết bài.

Module được thiết kế theo hướng **Hybrid**:
- Cố gắng lấy tối đa dữ liệu có thể từ trang web
- Luôn trả về mức độ tin cậy (`extraction_confidence`)
- Chỉ thực hiện trích xuất ý chính khi dữ liệu đạt ngưỡng tối thiểu

---

## 2. Goals

- Chuẩn hóa quy trình trích xuất ý chính từ nhiều nguồn khác nhau
- Trả về dữ liệu có cấu trúc rõ ràng, dễ tích hợp vào tool/workflow khác
- Ưu tiên độ tin cậy và khả năng tái sử dụng cao
- Output luôn bằng **tiếng Việt** (dù bài gốc thuộc ngôn ngữ nào)

---

## 3. Input

| Field     | Type   | Required | Description                  |
|-----------|--------|----------|------------------------------|
| `url`     | string | Yes      | URL của bài viết cần phân tích |

---

## 4. High-level Pipeline

```
URL
  │
  ▼
[Layer 1] Robust Content Extraction
  │
  ├── extraction_confidence = "low"  → Skip Layer 2, trả FULL schema §7 với mọi field
  │                                    Layer 2 = null + notes giải thích (error=true chỉ khi
  │                                    thất bại hoàn toàn, xem §9)
  │
  └── extraction_confidence = "medium" | "high"
        │
        ▼
[Layer 2] Core Ideas Extraction
  │
  ▼
Structured JSON Output
```

---

## 5. Layer 1: Robust Content Extraction

### 5.1 Mục tiêu
Trích xuất dữ liệu thô một cách ổn định nhất có thể từ nhiều cấu trúc website khác nhau.

### 5.2 Schema dữ liệu sau khi extract

**Đây là schema NỘI BỘ của Layer 1** (dữ liệu trung gian, dùng làm input cho Layer 2) — KHÔNG phải Final Output Schema. `publish_date` và `author` chỉ tồn tại ở bước này, KHÔNG propagate sang Final Output Schema (§7) — nếu cần citing nguồn/ngày đăng ở output cuối, xem §11 Future Extensibility.

```json
{
  "title": "string",
  "meta_description": "string | null",
  "headings": [
    {
      "level": 1 | 2 | 3,
      "text": "string"
    }
  ],
  "main_content": "string",
  "publish_date": "string | null",
  "author": "string | null",
  "language": "string",
  "extraction_confidence": "high | medium | low",
  "notes": "string | null",
  "source_structure": {
    "has_tables": "boolean",
    "has_numbered_lists": "boolean",
    "has_bullet_lists": "boolean",
    "question_heading_ratio": "float (0.0–1.0)"
  }
}
```

`source_structure` (v1.5, xem §13) — tín hiệu cấu trúc THÔ của nguồn, KHÁC PROPAGATE SANG §7 (không như `publish_date`/`author`): field này CÓ mặt ở Final Output Schema vì hữu ích trực tiếp cho người viết (không phải dữ liệu nội bộ chỉ dùng cho Layer 2).

### 5.3 Chiến lược trích xuất (theo thứ tự ưu tiên)

**Title:**
1. `<title>`
2. `og:title`
3. `h1` đầu tiên

**Meta Description:**
1. `<meta name="description">`
2. `og:description`

**Headings:**
- Lấy toàn bộ `h1`, `h2`, `h3` theo thứ tự xuất hiện trên trang
- Loại bỏ heading nằm trong menu, footer, sidebar nếu phát hiện được
- (v1.12) Nếu KHÔNG có `h1`-`h3` thật nào trong main_content — fallback nhận diện đoạn/danh sách đánh số (VD "1. Công dụng") hoặc `<strong>`/`<b>` chiếm trọn 1 đoạn làm "heading giả định" (level 2) — nhiều trang (đặc biệt trang sản phẩm) dùng cách này thay vì thẻ heading chuẩn, xem `ExtractRawContentAction::extractPseudoHeadings()`

**Main Content:**
- Ưu tiên thẻ `<article>`
- Nếu không có → tìm khối có mật độ chữ cao nhất (thường chứa class/id: content, post, entry, body, article-body...)
- Loại bỏ: menu, navigation, footer, related posts, comments, quảng cáo, social share

**Language:**
- Tự động phát hiện dựa trên nội dung chính

### 5.4 Mức độ tin cậy (extraction_confidence)

| Level    | Điều kiện                                                                 | Hành động tiếp theo          |
|----------|---------------------------------------------------------------------------|------------------------------|
| `high`   | Có title rõ + ≥ 2 headings + main_content ≥ 400 từ                        | Chuyển sang Layer 2          |
| `medium` | Có title + main_content ≥ 200 từ (có thể thiếu heading hoặc hơi nhiễu)    | Vẫn chuyển sang Layer 2      |
| `low`    | Thiếu title HOẶC main_content < 200 từ HOẶC quá nhiều noise               | Dừng, Skip Layer 2 (xem §4)   |

**Ngưỡng từ — DUY NHẤT 1 mốc, không còn vùng xám:** `main_content < 200 từ` → luôn là `low` (khớp chính xác với mốc bắt đầu của `medium`, không còn khoảng trống 150–199 từ như bản trước). Mốc `< 150 từ` (xem §9) KHÔNG phải mốc `low` riêng — nó là điều kiện PHỤ, chỉ dùng để set thêm `error = true` khi nội dung quá yếu (gần như không có gì để trích), trong khi mọi trường hợp `< 200 từ` đã chắc chắn là `low` rồi.

---

## 6. Layer 2: Core Ideas Extraction

Chỉ chạy khi `extraction_confidence` = `medium` hoặc `high`.

### 6.1 Các thành phần cần trích xuất

1. **article_type**  
   Phân loại cố định, dùng tín hiệu nhận diện sau để giảm variance giữa các lần chạy (ưu tiên khớp từ trên xuống, khớp tín hiệu đầu tiên phù hợp nhất):
   | Loại | Tín hiệu nhận diện |
   |------|---------------------|
   | `product_review` | Đánh giá/nhận xét 1 hoặc nhiều sản phẩm cụ thể (ưu/nhược điểm, điểm số, "review", "đánh giá") |
   | `how_to_guide` | Có cấu trúc bước/hướng dẫn thực hiện việc gì đó ("cách làm", "hướng dẫn", danh sách bước tuần tự) |
   | `listicle` | Tiêu đề/cấu trúc dạng danh sách đếm số ("N cách...", "N điều...", "Top N...") mà KHÔNG phải review sản phẩm |
   | `opinion_analysis` | Thể hiện quan điểm/lập luận cá nhân của tác giả về 1 vấn đề, không chỉ liệt kê sự kiện |
   | `news` | Tường thuật sự kiện/tin tức mới xảy ra, có yếu tố thời gian/địa điểm cụ thể |
   | `comparison` | So sánh trực tiếp ≥ 2 đối tượng (sản phẩm, phương pháp, lựa chọn) với nhau |
   | `other` | Không khớp rõ tín hiệu nào ở trên |

2. **thesis**  
   - Chỉ **1 câu** duy nhất
   - Trả lời câu hỏi: “Bài viết này muốn người đọc tin / hiểu / hành động điều gì nhất?”
   - Độ dài tối đa: 25 từ
   - Viết lại bằng tiếng Việt, không copy nguyên văn

3. **main_sections**  
   - Danh sách các ý lớn dựa trên headings hoặc cấu trúc nội dung
   - Tối đa 7 ý
   - Viết ngắn gọn bằng tiếng Việt

4. **core_ideas**  
   - Bình thường (không khớp "Thin content trigger" dưới đây): bắt buộc từ **3 đến 5** ý
   - **Thin content trigger** (điều kiện định lượng, thay cho mô tả cảm tính "nội dung mỏng" — cho phép < 3 ý):
     ```
     extraction_confidence == "medium"
     VÀ (word_count(main_content) < 300 HOẶC số headings có ý nghĩa ≤ 1)
     → cho phép core_ideas trong khoảng 1–5 ý, bắt buộc ghi notes giải thích rõ lý do
       (vd: "Nội dung mỏng (~220 từ, 1 heading), chỉ trích được 2 ý có giá trị thực"),
       KHÔNG độn ý để đạt đủ 3–5.
     ```
     - **"Headings có ý nghĩa"** = heading còn lại SAU KHI đã loại noise theo §5.3 (menu/navigation/footer/sidebar) — không tính heading trang trí/label rỗng nghĩa (vd "Xem thêm", "Chia sẻ").
     - **Cách đếm `word_count`**: đếm trên `main_content` đã làm sạch (sau khi loại noise ở §5.3), không tính trên HTML thô. Với ngôn ngữ không phân từ bằng khoảng trắng (Thái, Trung, Nhật...), không cần đếm chính xác theo từ — ước lượng theo độ dài nội dung tương đương hoặc theo cảm nhận ngữ nghĩa (module dùng LLM tự đánh giá, không phải đếm bằng code).
   - Mỗi ý:
     - Ngắn (≤ 20 từ)
     - Độc lập (đứng một mình vẫn hiểu được)
     - Mang tính thông tin / quan điểm / lời khuyên (không phải mô tả bề mặt)
   - Viết lại hoàn toàn, không copy nguyên câu từ bài gốc

5. **writing_inspiration**  
   - 1–2 câu gợi ý cách sử dụng bộ ý này để viết bài mới

---

## 7. Final Output Schema

**Schema DUY NHẤT, áp dụng cho mọi trường hợp** (kể cả khi `extraction_confidence = low` hoặc `error = true`) — luôn xuất đủ tất cả field dưới đây, không bao giờ bỏ field. Field nào Layer 2 không chạy tới (vì `confidence = low`) thì để `null`, không được xoá khỏi JSON.

```json
{
  "url": "string",
  "title": "string | null",
  "language": "string | null",
  "article_type": "string | null",
  "thesis": "string | null",
  "main_sections": ["string"] | null,
  "core_ideas": ["string"] | null,
  "writing_inspiration": "string | null",
  "extraction_confidence": "high | medium | low",
  "notes": "string | null",
  "error": "boolean",
  "source_structure": {
    "has_tables": "boolean",
    "has_numbered_lists": "boolean",
    "has_bullet_lists": "boolean",
    "question_heading_ratio": "float (0.0–1.0)"
  }
}
```

- `error`: luôn có mặt, mặc định `false`. Chỉ `true` khi không thể trích xuất được nội dung tối thiểu (xem §9).
- Khi `extraction_confidence = "low"`: `article_type`, `thesis`, `main_sections`, `core_ideas`, `writing_inspiration` LUÔN là `null` (Layer 2 không chạy) — `title`/`language` vẫn giữ giá trị nếu Layer 1 lấy được (chỉ `null` khi hoàn toàn không lấy được gì, xem §9).
- **Lưu ý triển khai thực tế**: `url`/`article_type`/`thesis`/`main_sections`/`core_ideas`/`writing_inspiration`/`error` là field LÝ THUYẾT của Layer 2 — CHƯA endpoint nào của module (`extract` hay `extract-batch`, xem §7.1) từng trả về các field này, vì Layer 2 chưa bao giờ được tự động hoá (§1, §12.3: module chỉ trích xuất, người dùng tự chạy Layer 2 bằng cách dán JSON vào chat AI). Response THẬT của `extract` hôm nay chỉ gồm đúng field Layer 1 (§5.2) + `source_structure` — khác với schema lý thuyết ở trên. Đây là khoảng cách đã tồn tại từ thiết kế ban đầu, không phải lỗi mới.
- `source_structure` (v1.5): LUÔN có mặt kể cả khi `extraction_confidence = "low"` (là dữ liệu Layer 1, không phụ thuộc Layer 2) — chỉ toàn `false`/`0.0` khi không lấy được HTML nào để phân tích (§9). Xem §13.

### 7.1 Batch Output Schema (v1.7)

Áp dụng riêng cho `POST extract-batch` (nhiều URL cùng lúc, tối đa `batch.max_urls` — mặc định
7) — schema NÀY, không phải §7 gốc (single-URL). Khoá đúng shape đã implement
(`ExtractBatchResultData`/`BatchSourceResultData`), CHƯA từng được viết ra spec trước v1.7 dù đã
chạy thật trong code + UI — đây là khoảng trống spec ↔ code rõ nhất trước khi vá.

**Envelope cấp batch:**

```json
{
  "topic": "string | null",
  "brief": {
    "audience": "string | null",
    "goal": "string | null",
    "constraints": "string | null",
    "style_sample": "string | null"
  },
  "requested_count": "int",
  "source_coverage": {
    "success": "int",
    "blocked": "int",
    "error": "int"
  },
  "summary_note": "string | null",
  "sources": ["… xem §7.1.1"],
  "processed_at": "string (ISO 8601)"
}
```

- `topic`: từ khoá nghiên cứu người dùng nhập — thuần metadata echo lại, KHÔNG ảnh hưởng logic fetch/extract.
- `brief`: ngữ cảnh PHÍA NGƯỜI VIẾT (đối tượng đọc/mục tiêu/ràng buộc/giọng văn) — khác `sources[]` (ngữ cảnh phía NGUỒN). Thuần passthrough dữ liệu người dùng tự gõ, không qua AI xử lý.
- `source_coverage`: đếm theo `status` của từng phần tử `sources[]` — `success + blocked + error = requested_count` luôn đúng.
- `summary_note`: `null` khi mọi nguồn đều `success`; ngược lại là câu tóm tắt số nguồn không trích được tự động (kèm gợi ý dùng tab "Dán mã HTML") — KHÔNG thay thế `notes`/`error_message` của từng nguồn, chỉ để không bị bỏ sót khi có nhiều nguồn lỗi.

#### 7.1.1 `sources[]` — 1 SHAPE DUY NHẤT cho mọi `status`

```json
{
  "url": "string",
  "final_url": "string | null",
  "domain": "string",
  "status": "success | blocked | error",
  "failure_type": "string | null",
  "http_status": "int | null",
  "title": "string | null",
  "meta_description": "string | null",
  "keywords": ["string"],
  "headings": [{ "level": 1 | 2 | 3, "text": "string" }],
  "main_content": "string | null",
  "content_hash": "string | null",
  "duplicate_of": "string | null",
  "word_count": "int | null",
  "publish_date": "string | null",
  "author": "string | null",
  "language": "string | null",
  "extraction_confidence": "high | medium | low | null",
  "notes": "string | null",
  "error_message": "string | null",
  "fetched_at": "string (ISO 8601)",
  "source_structure": {
    "has_tables": "boolean",
    "has_numbered_lists": "boolean",
    "has_bullet_lists": "boolean",
    "question_heading_ratio": "float (0.0–1.0)"
  } | null
}
```

**Quy tắc cứng** (mỗi phần tử `sources[]` kế thừa gần đúng field Layer 1 của §5.2 — KHÔNG có
field Layer 2 lý thuyết như `article_type`/`thesis`/`core_ideas`, cùng lý do nêu ở §7 — cộng thêm
vài field riêng của batch: trạng thái fetch + chống trùng):

- **1 shape duy nhất cho mọi `status`** — field không áp dụng thì `null` có chủ đích, để downstream (kể cả AI đọc JSON) không phải rẽ nhánh theo `status` mới biết field nào tồn tại. Ngoại lệ: `keywords`/`headings` dùng mảng rỗng `[]` khi không có dữ liệu (list, không phải scalar).
- `status = "success"`: `failure_type` = `null`, `error_message` = `null`; mọi field trích xuất (`title` → `source_structure`) có giá trị thật (có thể vẫn `null` riêng lẻ nếu Layer 1 không lấy được, y hệt §7).
- `status = "blocked"` hoặc `"error"`: `failure_type` có giá trị (`rate_limited`, `cloudflare_challenge`, `bot_protection`, `http_error`, `redirect_error`, `invalid_url`, `invalid_content_type`, `network_error`, `too_many_redirects` — xem `ClassifyFetchFailureAction`/`FetchArticlesBatchAction`), `error_message` có giá trị; **MỌI field trích xuất đều `null`** (`title`, `meta_description`, `main_content`, `word_count`, `publish_date`, `author`, `language`, `extraction_confidence`, `notes`), `keywords`/`headings` = `[]`.
- **`source_structure = null` khi `status != "success"`** — Layer 1 hoàn toàn không chạy (không có HTML nào để phân tích), không phải giá trị `false`/`0.0` mặc định như trường hợp lỗi hoàn toàn ở single-URL (§9) — batch phân biệt rõ "không chạy" (`null`) với "chạy nhưng không thấy tín hiệu" (single-URL).
- `duplicate_of`: URL ĐẦU TIÊN (có thể từ 1 batch khác, không chỉ trong batch hiện tại — phát hiện qua cache cross-request theo `content_hash`) có cùng nội dung đã chuẩn hoá; `null` nếu URL này là URL đầu tiên có nội dung đó.

---

## 8. Hard Rules (Quy tắc cứng)

| Thành phần            | Quy tắc bắt buộc                                                                 |
|-----------------------|----------------------------------------------------------------------------------|
| Ngôn ngữ output       | Luôn là **tiếng Việt**                                                          |
| Cấu trúc JSON         | Luôn đủ tất cả field ở §7 (không bỏ field nào), field chưa trích được để `null`  |
| Thesis                | Chỉ 1 câu, ≤ 25 từ                                                              |
| Core Ideas            | 3–5 ý, mỗi ý ≤ 20 từ, phải viết lại — riêng khi khớp "Thin content trigger" (§6.1.4: `medium` + word_count<300 hoặc ≤1 heading có ý nghĩa): cho phép 1–5 ý kèm `notes` giải thích, KHÔNG độn ý để đủ số |
| Main Sections         | Tối đa 7 ý                                                                      |
| Writing Inspiration   | Bắt buộc có khi Layer 2 chạy (confidence medium/high)                          |
| Khi confidence = low  | Không được bịa ý chính. Mọi field của Layer 2 (`article_type`, `thesis`, `main_sections`, `core_ideas`, `writing_inspiration`) để `null`, chỉ trả về thông tin Layer 1 đã extract được (nếu có) + `notes` |

---

## 9. Error Handling

- Nếu không lấy được title và content → trả về (đúng schema §7, các field Layer 1/2 đều `null`):
  ```json
  {
    "url": "https://example.com/bai-viet-khong-doc-duoc",
    "title": null,
    "language": null,
    "article_type": null,
    "thesis": null,
    "main_sections": null,
    "core_ideas": null,
    "writing_inspiration": null,
    "extraction_confidence": "low",
    "notes": "Không thể trích xuất nội dung chính từ trang này",
    "error": true
  }
  ```
- Nếu trang yêu cầu login / paywall → đánh dấu `low` + `notes` rõ ràng (title/language có thể vẫn giữ được nếu đọc được phần công khai trước tường phí)
- Ngưỡng từ (đồng bộ với §5.4, không còn vùng xám):
  - `main_content < 200 từ` → `extraction_confidence = "low"` (rule chính, duy nhất).
  - Trong nhóm đó, nếu `main_content < 150 từ` (quá yếu, gần như không có nội dung) → thêm `error = true`; từ 150–199 từ vẫn là `low` nhưng `error = false` (Layer 1 vẫn lấy được ít nội dung, chỉ không đủ để chạy Layer 2).

---

## 10. Example Output

```json
{
  "url": "https://maerakluke.com/topics/34716",
  "title": "รีวิว 7 แป้งเด็กสูตรอ่อนโยน ไม่ระคายเคือง เหมาะกับผิวทารก",
  "language": "th",
  "article_type": "product_review",
  "thesis": "Phụ huynh nên chọn phấn trẻ em dịu nhẹ không chứa talcum và hương liệu để bảo vệ da nhạy cảm của trẻ.",
  "main_sections": [
    "Tầm quan trọng của việc chọn phấn dịu nhẹ",
    "Tiêu chí lựa chọn phấn an toàn",
    "Review 7 sản phẩm cụ thể",
    "Lưu ý khi sử dụng phấn trẻ em"
  ],
  "core_ideas": [
    "Da trẻ sơ sinh rất nhạy cảm và dễ bị kích ứng",
    "Nên ưu tiên phấn không talcum, không hương, thành phần tự nhiên",
    "Có nhiều sản phẩm dịu nhẹ đang được đánh giá cao",
    "Không được rắc phấn trực tiếp lên người trẻ",
    "Cần theo dõi phản ứng da sau khi sử dụng"
  ],
  "writing_inspiration": "Có thể viết bài về '5 tiêu chí bắt buộc khi chọn phấn trẻ em' hoặc 'So sánh các loại phấn không talcum đang phổ biến'.",
  "extraction_confidence": "high",
  "notes": null,
  "error": false
}
```

**Ví dụ khi `confidence = medium` + nội dung mỏng** (minh hoạ rule mới ở §6.1.4/§8 — cho phép ít hơn 3 `core_ideas`, không độn ý):

```json
{
  "url": "https://example.com/mot-bai-viet-ngan",
  "title": "Mẹo giữ ấm cho trẻ khi trời lạnh",
  "language": "vi",
  "article_type": "how_to_guide",
  "thesis": "Cha mẹ nên mặc nhiều lớp áo mỏng cho trẻ thay vì 1 lớp áo dày khi trời lạnh.",
  "main_sections": [
    "Vì sao nên mặc nhiều lớp áo mỏng",
    "Lưu ý khi chọn chất liệu áo"
  ],
  "core_ideas": [
    "Mặc nhiều lớp áo mỏng giữ nhiệt tốt hơn 1 lớp áo dày",
    "Ưu tiên chất liệu cotton, tránh gây bí da cho trẻ"
  ],
  "writing_inspiration": "Có thể mở rộng thành bài 'Cách mặc ấm đúng cách cho trẻ theo từng mức nhiệt độ'.",
  "extraction_confidence": "medium",
  "notes": "Nội dung mỏng (~220 từ), chỉ trích được 2 ý có giá trị thực, không độn thêm để đủ 3 ý.",
  "error": false
}
```

---

## 11. Future Extensibility

Các hướng mở rộng có thể triển khai sau:

- Hỗ trợ input là file PDF / transcript video
- Thêm trường `key_quotes` (trích dẫn quan trọng)
- Thêm trường `sentiment` hoặc `tone`
- Cho phép tùy chỉnh số lượng `core_ideas` (3–7)
- Tích hợp đánh giá chất lượng ý tưởng viết (idea quality score)
- Tự động kéo tag/bài viết hiện có của chuyên mục (§12) vào prompt để AI tránh gợi ý trùng nội dung đã viết — chưa triển khai ở v1.4, xem §12 "Ngoài phạm vi"

---

## 12. Category Content Foundation (v1.4)

### 12.1 Bối cảnh

Tham khảo từ https://afterhoursai.substack.com/p/how-to-train-ai-to-extract-content — AI chỉ gợi ý nội dung sắc bén khi được cấp 1 "Business Foundation" bền vững (core offering/UVP/goals) thay vì lặp lại ngữ cảnh mỗi lần chat, và mỗi ý tưởng nên được lọc qua 3 câu hỏi: *có gắn với core offering không? chỉ mình mới chia sẻ được insight này không? có phục vụ mục tiêu cụ thể không?*

Module đã có ngữ cảnh ad-hoc (`audience/goal/constraints/style_sample`, §"Ngữ cảnh cho người viết") nhưng phải gõ lại mỗi lần chạy. `Post` (nơi bài viết thật sự tồn tại) là tài sản **platform-wide, không tenant-scoped** — biên tập viên được gán **theo `PostCategory`** (`post_category_editors`), không theo Organization. Vì vậy phạm vi hợp lý cho 1 bộ ngữ cảnh bền vững là **theo từng PostCategory**, không phải Organization hay tác giả.

### 12.2 Thiết kế

- Bảng `cie_category_foundations` (tên rút gọn — tên đầy đủ `core_idea_extractor_category_foundations` khiến tên constraint auto-gen của Laravel vượt giới hạn 64 ký tự của MySQL) (model `CategoryContentFoundation`) — model Eloquent ĐẦU TIÊN của module — sống trong `CoreIdeaExtractor`, FK `post_category_id → post_categories.id` (unique, 1 bản ghi/category), cùng hướng phụ thuộc 1 chiều với `Ocop → Post` (`post_article_ocop_products`). `Post` module không cần sửa gì.
- Field: `core_focus`, `unique_angle`, `content_goals` (3 thành phần Business Foundation ánh xạ sang ngữ cảnh biên tập) + `pain_points` (câu hỏi/khó khăn thường gặp của độc giả, rút ra từ nghiên cứu thực tế — xem §12.6, v1.10) + `rejected_ideas` (Decision Log — ý tưởng đã cân nhắc và quyết định KHÔNG viết, xem §12.7, v1.11) + `audience`, `constraints`, `style_sample` (persist hoá field ad-hoc đã có).
- **Không đổi Layer 1/Layer 2 JSON schema (§5, §7)** — foundation chỉ dùng để prefill form và dựng prompt ở tầng UI, không bao giờ chèn vào JSON output.
- Quyền sửa foundation của 1 category: `platform_content_editor`/`platform_content_head` sửa được mọi category (giữ nguyên quyền `core_idea_extractor.use` không giới hạn hiện có); `platform_section_editor` chỉ sửa được category mình được gán qua `post_category_editors` — cùng pattern với `PostArticlePolicy::approve()`. Implement bằng `Gate::define('core_idea_extractor.manage_category_foundation', ...)` (KHÔNG phải `Policy` gắn vào `PostCategory`, vì `Post` module đã đăng ký `PostCategoryPolicy` cho chính model đó — đăng ký thêm 1 policy nữa sẽ ghi đè lẫn nhau).
- UI: trang quản lý riêng (`/dashboard/core-idea-extractor/category-foundations`) để CRUD foundation theo từng category; trang trích xuất chính thêm `<select>` chuyên mục — khi chọn, tự prefill `audience/goal/constraints/style_sample` (vẫn tự sửa được, không khoá field) + nút "Copy prompt cho AI" bọc JSON + foundation + 3 câu hỏi lọc ý tưởng (bản dịch sang ngữ cảnh biên tập) thành 1 prompt dán thẳng vào chat AI.

### 12.3 Ngoài phạm vi (v1.4)

- Không tự động kéo tag/bài viết hiện có của category vào prompt (tránh trùng nội dung đã viết) — để dành cho lần lặp sau (§11).
- Không tự động hoá Layer 2 (gọi AI Provider thật) — module vẫn là công cụ nghiên cứu, copy tay vào chat AI, đúng triết lý hiện có.

### 12.4 "Copy prompt cho AI" — cấu trúc context sandwich (v1.5, hardening v1.6, task 3 bước v1.8, bỏ Bảng 2 v1.20)

Tham khảo https://www.mindstudio.ai/blog/context-sandwich-prompting-method-ai-results +
https://www.promptingguide.ai/guides/context-engineering-guide: prompt copy ra ở §12.2 dựng
theo cấu trúc sandwich — **TRÊN** = vai trò biên tập viên + bối cảnh (foundation/ad-hoc, súc
tích — "more context isn't always better") + ngày hôm nay (dynamic context); **GIỮA** = JSON thô
ĐẦY ĐỦ đã trích xuất (phần "filling", chỉ để tham khảo — KHÔNG rút gọn `main_content`, xem lý do
bên dưới); **DƯỚI CÙNG** = nhiệm vụ 3 bước + định dạng output tường minh (1 bảng Markdown duy nhất
từ v1.20, xem bên dưới — kime.ai: bảng được trích dẫn nhiều gấp 4.2x văn xuôi), đặt NGAY TRƯỚC chỗ
model bắt đầu sinh câu trả lời (khác bản v1.4 để JSON ở cuối). Trang quản lý foundation (§12.2) có
thêm gợi ý viết ngắn gọn (1-2 câu/field).

**v1.6 — khoá format cứng:** luôn kết thúc BOTTOM bằng chỉ dẫn không viết giải thích/mở
đầu/kết luận ngoài các bảng yêu cầu — đặt SAU CÙNG (đúng nguyên tắc sandwich: chỗ model chú ý
nhất ngay trước khi generate) để giảm tình trạng AI viết lời dẫn/kết luận thừa quanh bảng.

**Đã cân nhắc nhưng KHÔNG làm — rút gọn `main_content` trong JSON giữa prompt:** ý tưởng ban đầu
là cắt `main_content` còn ~500 ký tự khi batch nhiều nguồn/bài dài, để tránh phần GIỮA phình to
đẩy nhiệm vụ ở BOTTOM ra xa vùng model chú ý nhất. Bỏ NGAY trong cùng phiên bản sau khi cân nhắc
lại: 500 ký tự chỉ đủ 1 đoạn mở bài, phá mất chiều sâu nội dung nguồn — trong khi cả module tồn
tại để nghiên cứu SÂU nguồn tham khảo, không phải lướt qua tiêu đề. Cái mất (nội dung thực chất)
là chắc chắn 100%, cái được (model "chú ý" cuối prompt tốt hơn) chỉ là suy đoán và không đáng kể
với model hiện đại (context window lớn, có ranh giới cấu trúc rõ ràng giữa các phần trong prompt).
Server đã tự giới hạn kích thước sẵn (single 100.000 ký tự — `max_main_content_chars`; batch
12.000 ký tự/nguồn — `batch.max_main_content_chars_per_source`), không cần thêm 1 lớp cắt nữa ở
client.

**v1.8 — nhiệm vụ chia 3 bước, dựa trên kết quả test thật (dán prompt vào grok.com/claude.ai)
với 2 URL thật:** kết quả trả về mọi ý tưởng đều "Có" tuyệt đối ở cả 3 tiêu chí — đúng bản chất,
vì bản v1.6 yêu cầu AI *"chỉ giữ lại ý tưởng thoả cả 3 điều kiện"*, nên AI tự lọc TRƯỚC khi hiển
thị, người đọc không còn thấy được ý tưởng nào bị loại hay ranh giới lọc ở đâu — 3 cột Có/Không
trở thành "con dấu" không mang thông tin. BOTTOM đổi từ 1 bước "lọc luôn" thành 3 bước tường
minh:

1. **BƯỚC 1 — Sinh ý tưởng (chưa lọc)**: liệt kê tối đa 8-10 ý tưởng ứng viên. Khi có **≥2 nguồn
   thành công** (batch — đếm qua `sources[].status === 'success'`, KHÔNG tính nguồn `blocked`/
   `error`), bắt buộc thêm chỉ dẫn: phải có ít nhất 1 ý tưởng **tổng hợp chéo nhiều nguồn** (kết
   hợp insight của ≥2 nguồn thành 1 góc nhìn mà không nguồn đơn lẻ nào tự có — dạng ý tưởng khó bị
   sao chép nhất, đúng tinh thần câu hỏi lọc #2). Single-URL hoặc batch chỉ có 1 nguồn thành công
   → bỏ qua chỉ dẫn này (không có gì để tổng hợp chéo).
2. **BƯỚC 2 — Đánh giá từng ý tưởng qua cả 3 tiêu chí** (không đổi nội dung 3 câu hỏi so với
   v1.5, chỉ đổi cách dùng: đánh giá tường minh thay vì lọc ngầm).
3. **BƯỚC 3 — Xuất đúng 1 bảng Markdown** (ý tưởng đạt cả 3 tiêu chí) — thêm cột **"Lý do"** (1
   câu) so với v1.5: không còn Có/Không suông, người viết verify được AI đang dựa vào đâu để kết
   luận "khớp"/"độc quyền".
   - **[v1.20 — ĐÃ BỎ] Bảng 2** (ý tưởng bị loại) — bản v1.8 từng thêm bảng này để liệt kê ý tưởng
     KHÔNG đạt ít nhất 1 tiêu chí kèm tiêu chí không đạt + lý do loại, "chứng minh bộ lọc thực sự
     chạy" (không phải chỉ ẩn đi). Theo yêu cầu người dùng ở v1.20, bảng này đã bị bỏ — output giờ
     chỉ còn ý tưởng đạt tiêu chí, đánh đổi mất khả năng verify bộ lọc lỏng/chặt mà v1.8 từng nhắm
     tới giải quyết.

Dòng khoá format cứng (v1.6) vẫn giữ nguyên, chỉ chuyển thành 1 phần của chỉ dẫn Bước 3 (không
viết gì ngoài bảng — chỉ 1 bảng từ v1.20, 2 bảng từ v1.21 khi có sản phẩm gợi ý).

#### 12.4.1 Hai đường đi, hai định dạng output (v1.21)

Cùng 1 chuỗi prompt (`buildLayer2PromptText()`) phục vụ 2 đường đi có cơ chế định dạng khác hẳn
nhau — trước v1.21 cả hai dùng chung 1 bản mô tả theo TÊN FIELD, nên đường thứ hai bị hỏng:

| | Nút "Chạy Layer 2 bằng AI" (trong app) | Nút "Copy prompt cho AI" (dán sang Grok/Claude) |
|---|---|---|
| Ép định dạng | `RESPONSE_SCHEMA` (structured output) ở Action | Không có gì ép |
| Ai dựng bảng | PHP (`renderMarkdownTable()`) | Chính model |
| Prompt mô tả | theo tên field (`ideas`, `suggested_products`…) | theo BẢNG 1 / BẢNG 2 Markdown |

Cờ `forExternalChat` (mặc định `false`) chọn nhánh; `copyPromptForAi()` truyền `true`. Nhánh
external còn (a) ánh xạ `category_note`/`audience_assumption`/`insufficient_reason` — vốn được nhắc
như "trường" ở Bước 0/Bước 2 — thành DÒNG CHỮ THƯỜNG đặt trước/sau bảng 1, đúng vị trí PHP đang
render, và (b) sửa lại "Mục tiêu số lượng": lời hứa *"hệ thống sẽ tự yêu cầu bạn sinh thêm ở lượt
sau"* chỉ đúng với vòng lặp goal-based trong app, ở chat ngoài là 1 lượt hỏi đáp duy nhất nên phải
yêu cầu đủ số lượng ngay trong câu trả lời. Bố cục 2 bảng của 2 nhánh được giữ GIỐNG NHAU có chủ ý
(kể cả quy tắc cột "Chuyên mục đề xuất" chỉ xuất hiện khi chưa chọn chuyên mục) — người biên tập
không phải học 2 bố cục cho cùng 1 việc.

### 12.5 Cảnh báo kích thước prompt (v1.9)

Tham khảo https://blog.neosage.io/p/the-ai-application-layer-where-context — model chỉ nhìn thấy
đúng token trong context window; nghiên cứu được bài viết dẫn cho thấy độ chính xác giảm dần khi
context dài + nhiệm vụ phức tạp (GPT-4o: 99.3% → 69.7%), và **không có ngưỡng an toàn cụ thể** —
hiệu năng giảm dần đều theo độ dài, không phải "dưới X thì an toàn, trên X thì hỏng".

**Cách tiếp cận: cảnh báo, KHÔNG cắt nội dung.** Đã từng thử cắt `main_content` để giảm kích
thước prompt (v1.6, bỏ ngay trong cùng bản — xem §12.4) — bài học rút ra: cắt nội dung phá mất
chiều sâu là cái mất CHẮC CHẮN 100%, trong khi lợi ích (context ngắn hơn) chỉ là suy đoán. v1.9 áp
dụng đúng bài học đó theo hướng khác: đo kích thước THẬT (không giả định) và hiển thị cảnh báo
nhẹ, để NGƯỜI DÙNG tự quyết định giảm số nguồn/chạy theo đợt nhỏ hơn — không tự động đánh đổi nội
dung.

- Đo trên `prettyJson()` (chính là phần "filling" ở GIỮA prompt, thành phần chiếm phần lớn kích
  thước) — ngưỡng cảnh báo: **> 50.000 ký tự** (~12.500 token, ước lượng thô `ký_tự / 4` — CHỈ để
  người dùng có cảm nhận độ lớn tương đối, không phải con số chính xác cho billing/tokenizer thật).
- Hiển thị dưới dạng 1 dòng cảnh báo (`text-warning`, cùng style với `notes`/`summary_note` đã có)
  ngay dưới khu vực nút "Copy prompt cho AI"/"Copy JSON" — KHÔNG chặn thao tác copy, chỉ là gợi ý.
- Không áp dụng riêng cho single-URL hay batch — tính chung trên kích thước JSON thật của
  `this.result` ở thời điểm đó, đúng với cả 2 chế độ.
- Xem `estimatedPromptChars()`/`isPromptLarge()`/`promptSizeWarningText()` trong `index.blade.php`.

### 12.6 Pain Points (v1.10)

Tham khảo case study B2B thought-leadership: bước nền của toàn bộ content strategy là **nghiên
cứu khách hàng định kỳ** (phỏng vấn + phân tích sales call để tìm pain point/objection lặp lại),
KHÔNG phải chỉ mô tả trừu tượng về "trọng tâm nội dung". `core_focus`/`unique_angle`/
`content_goals` (§12.2) là mô tả TĨNH do editor tự viết một lần; `pain_points` khác — là câu hỏi/
khó khăn THẬT của độc giả, lý tưởng nhất là rút ra từ nghiên cứu thực tế định kỳ (khảo sát,
feedback, câu hỏi lặp lại từ độc giả/khách hàng) chứ không phải editor tự đoán.

- Field mới `pain_points` (text, nullable) trên `cie_category_foundations` — migration
  `2026_07_25_000002_add_pain_points_to_cie_category_foundations_table.php` (thêm cột, KHÔNG đổi
  migration gốc đã chạy).
- Cùng nhóm validate/quyền/UI với `core_focus`/`unique_angle`/`content_goals` — không có quy tắc
  riêng, không có Gate riêng (dùng chung `core_idea_extractor.manage_category_foundation`, §12.2).
- Xuất hiện ở TOP của "Copy prompt cho AI" (§12.4) ngay sau `content_goals`, và ở tóm tắt hiển thị
  trên trang trích xuất chính (`selectedFoundationSummary()`) — cùng cơ chế prefill/hiển thị như
  3 field kia, không thêm luồng riêng.
- **Không đổi Layer 1/Layer 2 JSON schema (§5, §7)** — thuần field UI/prompt-template, giống
  `core_focus`/`unique_angle`/`content_goals`.
- Ngoài phạm vi (v1.10): KHÔNG tự động hoá việc thu thập pain points (không tích hợp khảo sát/
  phân tích sales call nào) — vẫn là field editor tự điền tay, dựa trên nghiên cứu họ tự làm bên
  ngoài hệ thống. Không thuộc phạm vi module này.

### 12.7 Decision Log — `rejected_ideas` (v1.11)

Tham khảo "Five-File Framework" (matthopkins.com) — 1 trong 5 file ngữ cảnh cá nhân được đề xuất
là **Decision Log**: ghi lại quyết định đã chốt + lý do + phương án đã loại bỏ, để AI không đề
xuất lại thứ đã bị bác bỏ hoặc lặp lại câu hỏi đã trả lời rồi.

- Field mới `rejected_ideas` (text, nullable) trên `cie_category_foundations`, SAU `pain_points` —
  migration `2026_07_25_000003_add_rejected_ideas_to_cie_category_foundations_table.php`.
- Khác `pain_points` (câu hỏi/khó khăn CỦA ĐỘC GIẢ) — `rejected_ideas` là ý tưởng BÀI VIẾT đã
  từng được cân nhắc và CHỦ ĐỘNG quyết định không viết, kèm lý do (VD "đối thủ đã làm rất kỹ, khó
  cạnh tranh"). Đây là tribal knowledge — editor tự ghi tay, KHÔNG suy ra được từ dữ liệu có sẵn
  (khác §12.8 — danh sách bài đã publish chỉ cho biết "đã viết gì", không biết "đã cân nhắc rồi
  từ chối cái gì").
- Cùng nhóm validate/quyền/UI với `pain_points`/`core_focus`/`unique_angle`/`content_goals` —
  không có quy tắc/Gate riêng.
- Xuất hiện ở TOP của "Copy prompt cho AI" (§12.4) ngay sau `pain_points`, KÈM 1 chỉ dẫn tường
  minh ở BOTTOM ("KHÔNG đề xuất ý tưởng trùng/gần giống... đã bị từ chối") — không chỉ đưa context
  suông, vì chỉ dẫn tường minh đáng tin hơn hy vọng model tự suy luận từ context (context
  engineering).
- **Không đổi Layer 1/Layer 2 JSON schema (§5, §7)**.

### 12.8 Tự động tránh trùng — bài đã publish trong category (v1.11)

Tham khảo memgraph.com ("Context Engineering for Beginners") — nhấn mạnh **curation**: dọn dữ
liệu trùng lặp qua nhận diện thực thể đã tồn tại (entity resolution), để hệ thống biết "cái này
đã có rồi" trước khi tạo thêm. Bổ sung cho §12.7 (Decision Log — tay, có lý do) bằng 1 nguồn dữ
liệu KHÁCH QUAN, TỰ ĐỘNG: danh sách tiêu đề bài đã publish thật trong category.

- Action mới `ListCategoryExistingArticlesAction` (`Features/CategoryFoundation/Actions/`) — lấy
  bài qua `PostCategory::articles()` (quan hệ ĐÃ CÓ SẴN của `Post`, không cần `Post` sửa gì, cùng
  hướng phụ thuộc 1 chiều đã áp dụng xuyên suốt module này), lấy `mainTranslation()` (helper PHP
  có sẵn trên `PostArticle`, dùng collection `translations` đã eager-load — CÙNG pattern
  `PostArticle::scopeWithMainTranslation()` đang dùng ở `Post::ListArticlesForAdminHandler`,
  KHÔNG viết join/query riêng), lọc `status = TranslationStatus::Published`, sort theo
  `published_at` giảm dần, cắt còn tối đa `existing_articles.max_titles` (mặc định 30 — cấu hình
  ở `config/core_idea_extractor.php`, cùng thói quen module: mọi danh sách không giới hạn đều có
  trần, xem `batch.max_urls`). `existing_articles.db_fetch_limit` (mặc định 100) giới hạn số bản
  ghi fetch thô từ DB trước khi lọc/sort, tránh query runaway với category có hàng nghìn bài.
- Endpoint riêng `GET .../category-foundations/{category}/existing-articles` — fetch ON-DEMAND
  khi người dùng CHỌN category ở trang trích xuất chính (`fetchExistingArticles()`), KHÔNG preload
  cho mọi category lúc tải trang (khác `list()` vốn preload toàn bộ foundation) — 1 category có
  thể có hàng chục/hàng trăm bài, preload hết cho MỌI category sẽ phình payload ban đầu không cần
  thiết cho category người dùng chưa chọn tới.
- Chỉ tiêu đề (chuỗi ngắn) → không có rủi ro "phình" prompt như `main_content` (khác quyết định đã
  bỏ ở v1.6 — xem §12.4) — cap ở đây là phòng ngừa runaway, không phải đánh đổi chiều sâu nội
  dung.
- Đưa vào TOP của "Copy prompt cho AI" ngay sau `rejected_ideas`, liệt kê từng tiêu đề — kèm chỉ
  dẫn tường minh chung với §12.7 ở BOTTOM.
- Ngoài phạm vi (v1.11): không dùng tag/similarity ngữ nghĩa để phát hiện trùng lặp GẦN GIỐNG
  (chỉ liệt kê tiêu đề, để AI tự đánh giá mức độ trùng) — để dành cho lần lặp sau nếu cần chính
  xác hơn.

### 12.10 Hệ giá trị gia đình Việt Nam — chuẩn nền tảng cố định (v1.16, khối grounding mở rộng v1.21)

#### 12.10.1 Bối cảnh

> **Đính chính tham chiếu (v1.21, cập nhật v1.27):** các dòng bên dưới trỏ `spec/giadinh.md` — file
> này đã bị ghi đè nội dung NHIỀU LẦN qua các version (nguồn gốc mỗi lần đổi không rõ): 1 bài tiếng
> Anh về inference pipeline của LLM (tại thời điểm v1.21), rồi "6 framework cấu trúc prompt AI cho
> marketing" (tại thời điểm v1.22, xem đối chiếu ngay dưới đây), và tại thời điểm v1.27 lại là "Bộ
> tiêu chí ứng xử trong gia đình" (4 cặp quan hệ — xem §12.11, một khung KHÁC với 4 giá trị ở đây).
> Tham chiếu đúng cho định nghĩa 4 giá trị VẪN là chính mục §12.10 này + config nêu ở Lớp 1 — không
> đổi qua bất kỳ lần ghi đè nào của file.

Đối chiếu `spec/giadinh.md`: Hệ giá trị gia đình Việt Nam gồm 4 giá trị cốt lõi — **ấm no, hạnh
phúc, tiến bộ, văn minh** — do Thủ tướng Chính phủ ban hành qua **Quyết định 1189/QĐ-TTg ngày
02/07/2026** (không phải văn bản Nghị định). Platform familiesforlife xuất bản nội dung CHO gia
đình Việt Nam, nên đây không phải 1 nguồn tham khảo bên ngoài như các bài context-engineering đã
dẫn ở §12.1/§12.4/§12.7/§12.8 (rephrase-it.com, aimagicx.com, promptingguide.ai — 7 bước context
engineering 2026), mà là **chuẩn biên tập CHÍNH THỨC** của chính platform.

Khác biệt bản chất so với mọi field khác trong §12.2 (`core_focus`/`pain_points`/`objections`/
`decision_criteria`/`rejected_ideas`/...): những field đó là ngữ cảnh **editor tự viết**, có thể
sai/thiếu/lệch chuẩn tuỳ người viết. 4 giá trị gia đình thì **cố định, không editor nào được sửa
lại câu chữ** — vai trò của editor chỉ là chọn giá trị nào áp dụng cho chuyên mục mình phụ trách.

#### 12.10.2 Thiết kế — 2 lớp tách biệt

**Lớp 1 — Khối cố định (grounding), luôn xuất hiện, không thuộc về category nào:**

- Định nghĩa 4 giá trị (`key`, `label`, `description`) + `decision_ref` ("Quyết định 1189/QĐ-TTg
  ngày 02/07/2026") sống ở `config('content_foundation.family_values')` (v1.21 đính chính đường
  dẫn: khối này đã chuyển sang module `ContentFoundation` khi tách module, spec cũ ghi
  `core_idea_extractor.family_values`) — **nguồn sự thật DUY NHẤT**, không hardcode lặp lại câu chữ
  ở PHP/blade/JS chỗ khác (đọc động qua `config(...)` ở mọi nơi cần dùng: rule validate `in:...` ở
  `CategoryFoundationController::upsert()`, UI checkbox ở `category-foundations.blade.php`, khối
  grounding ở `index.blade.php`). Chỉ dẫn cần TRỎ THẲNG tới 1 giá trị cụ thể (VD nguồn thương mại →
  giá trị "Ấm no") đọc nhãn qua helper `familyValueLabel(key, fallback)`, KHÔNG viết cứng chuỗi nhãn
  vào prompt — v1.28 mới vá 3 chỗ vi phạm chính nguyên tắc này, xem §12.11.4-B4.
- `buildFamilyValuesGroundingLines()` (`index.blade.php`; tới v1.20 tên là
  `buildFamilyValuesGroundingLine()` và chỉ trả về ĐÚNG 1 câu) dựng khối nhiều dòng: liệt kê đủ 4
  giá trị kèm mô tả gốc + 1 dòng diễn giải "ở tầng biên tập" cho mỗi giá trị
  (`FAMILY_VALUE_EDITORIAL_NOTES`, map theo `key` — KHÔNG phải câu chữ văn bản gốc nên không vi
  phạm nguyên tắc "nguồn sự thật duy nhất" ở trên; thêm/bớt giá trị trong config vẫn chạy, key lạ
  chỉ đơn giản không có dòng diễn giải), phép thử "thế nào là PHỤC VỤ giá trị", mệnh đề "đây là
  khung NỘI BỘ, không phải chất liệu viết vào bài" (cấm đề xuất bài phân tích/trích dẫn Quyết định
  — model rất dễ bịa điều khoản của 1 văn bản pháp quy), đa dạng mô hình gia đình, cạm bẫy gán vai
  trò theo giới, phân biệt phong tục với hủ tục, cách xử lý khi 2 giá trị xung đột, và cuối cùng
  mới tới mệnh đề CHẶN CỨNG (đúng thứ tự "mục tiêu tích cực trước, ranh giới cấm sau" đã chốt ở
  v1.17 — Pink Elephant Problem). Ranh giới cứng áp cho MỌI thành viên gia đình, không riêng cha mẹ.
  Push vào TOP của "Copy prompt cho AI" (§12.4) NGAY SAU dòng persona + ngày hôm nay,
  TRƯỚC mọi field foundation theo category — **LUÔN xuất hiện kể cả khi chưa chọn chuyên mục nào**
  (khác mọi khối khác trong §12.4 vốn chỉ xuất hiện khi có `foundation`), vì đây là chuẩn áp dụng
  cho MỌI nội dung của platform, không riêng chuyên mục nào. Hệ quả của thứ tự này: khối đứng TRƯỚC
  mục mô tả đối tượng độc giả, nên mọi mệnh đề trong khối nhắc tới mô tả đối tượng phải diễn đạt
  KHÔNG PHỤ THUỘC THỨ TỰ ("mô tả đối tượng độc giả **trong prompt này**", không phải "ở trên") —
  v1.28 vá 2 mệnh đề vi phạm, xem §12.11.4-B1.
- Không có field DB nào cho lớp này — thuần đọc config + build chuỗi ở client, giống cách
  `batch.max_urls`/`foundation.stale_after_days` đã được đọc thẳng từ config vào view.

**Lớp 2 — `family_values_focus` (field mới trên `cie_category_foundations`), theo TỪNG category:**

- Cột `family_values_focus` (`json`, nullable, migration
  `2026_08_01_000002_add_family_values_focus_to_cie_category_foundations_table.php`, sau
  `decision_criteria`) — lưu **TẬP KEY** (VD `["hanh_phuc","tien_bo"]`), KHÔNG lưu lại nhãn/mô tả
  (tránh 2 nơi lệch nhau nếu sau này chỉnh câu chữ mô tả trong config). Cast `array` ở
  `CategoryContentFoundation`.
- UI: nhóm checkbox trong trang quản lý (`category-foundations.blade.php`) — 4 ô tick tương ứng
  4 `key` đọc từ `familyValues` (truyền qua `Js::from()` từ config), có chú thích rõ "chuẩn nền
  tảng cố định — không phải văn bản tự viết" để editor không nhầm đây là 1 field tự do như các ô
  khác cùng trang. Validate ở `CategoryFoundationController::upsert()`: `family_values_focus.*` =>
  `in:<danh sách key đọc động từ config>` — không hardcode lại danh sách key trong rule (nếu sau
  này thêm/bớt giá trị, chỉ cần sửa 1 chỗ duy nhất trong config).
- Khi category có `family_values_focus`, `buildLayer2PromptText()` thêm 1 dòng RIÊNG (SAU khối cố
  định ở Lớp 1, TRƯỚC `core_focus`) nêu tên các giá trị chuyên mục này ưu tiên — đóng vai trò
  **ưu tiên bổ sung** (gợi ý bám sát khi phù hợp), KHÔNG thay thế khối chuẩn cố định (mọi ý tưởng,
  kể cả chuyên mục không tick giá trị nào, vẫn phải tuân thủ mệnh đề chặn cứng ở Lớp 1).
- Cùng cơ chế N-N chia sẻ bộ tiêu chí giữa nhiều category đã có (bảng nối
  `cie_foundation_categories`) — `family_values_focus` là 1 cột trên `CategoryContentFoundation`
  như mọi field khác, tự động dùng chung khi nhiều category share 1 bộ tiêu chí, không cần xử lý
  gì thêm.

#### 12.10.3 Vì sao tách 2 lớp thay vì gộp làm 1

Đã cân nhắc phương án đơn giản hơn — chỉ thêm `family_values_focus` (Lớp 2) và để editor tự đọc
Quyết định 1189 rồi viết mô tả liên quan vào 1 field text tự do (kiểu `core_focus`) — **KHÔNG chọn
phương án này**: (1) định nghĩa 4 giá trị là VĂN BẢN CHÍNH THỨC, để editor tự diễn giải lại dễ sai
lệch/thiếu nhất quán giữa các category (đúng vấn đề mà mọi field cố định khác trong hệ thống — VD
`ExtractionConfidence` enum, `content_type_signal` — đã tránh bằng cách KHÔNG để tự do nhập); (2)
1 platform nội dung gia đình nên có CÙNG 1 khung tham chiếu giá trị cho MỌI bài viết, kể cả chuyên
mục chưa từng cấu hình Content Foundation — nếu chỉ đặt ở Lớp 2 (theo category), category chưa có
foundation sẽ hoàn toàn không có ràng buộc giá trị nào khi sinh ý tưởng.

#### 12.10.4 Ngoài phạm vi (v1.16)

- Không tự động CHẤM ĐIỂM/gắn nhãn ý tưởng AI trả về theo mức độ khớp giá trị nào (VD "ý tưởng này
  70% thuộc giá trị Tiến bộ") — đó là suy luận ngữ nghĩa (NLP/LLM-judge), ngoài phạm vi rule cú
  pháp thuần của Layer 1; người biên tập tự đánh giá khi đọc kết quả AI trả về.
- Không áp dụng khối cố định này vào `buildSummarizePromptText()` ("Tóm tắt nội dung", §content.md
  mục A) — tool đó CHỦ ĐÍCH trung thực tuyệt đối theo nguồn gốc, không thêm bối cảnh biên tập/giá
  trị nào (xem chú thích ngay trong hàm: "không cần bối cảnh chuyên mục hay mục tiêu biên tập nào
  khác"), thêm khối này vào sẽ đi ngược thiết kế đã có của tool đó.
- Không mở rộng sang `buildRewritePromptText()` ("Tái cấu trúc nội dung", mục B) ở v1.16 — tool đó
  viết lại nội dung NGUỒN đã có sẵn (đã qua vòng ý tưởng hoá ở Layer 2 nếu xuất phát từ đó) thành
  nhiều phiên bản theo nền tảng, không phải bước sinh ý tưởng mới; để dành cho lần lặp sau nếu có
  nhu cầu thực tế.
  - **Cập nhật v1.21:** nhu cầu thực tế đã xuất hiện và tool này GIỜ CÓ khung giá trị — nhưng ở bản
    NÉN (`buildFamilyValuesBoundaryLine()`), không phải khối grounding đầy đủ: 3 phiên bản Facebook/
    LinkedIn/Twitter đăng CÔNG KHAI nên cần ranh giới, còn phép thử "phục vụ giá trị nào" thì vô
    nghĩa vì không có ý tưởng nào được sinh ra ở đây. Cùng bản nén đó cũng được dùng cho 6 tool sinh
    câu chữ đăng thật bên `VideoIdeaExtractor` (tiêu đề/hook/Shorts/dàn ý/CTA/biên tập lời nói).
    `buildSummarizePromptText()` VẪN giữ nguyên quyết định không áp dụng (gạch đầu dòng trên), chỉ
    nhận thêm 1 chỉ dẫn về giữ MỨC ĐỘ CHẮC CHẮN của nguồn — đó là tiêu chí trung thực với nguồn,
    không phải bối cảnh biên tập.

### 12.11 Bộ tiêu chí ứng xử trong gia đình — khung thứ 2, tách riêng khỏi §12.10 (v1.27, vá lỗ hổng ngữ cảnh v1.28)

#### 12.11.1 Bối cảnh

`spec/giadinh.md` (nội dung tại thời điểm v1.27) chứa "Bộ tiêu chí ứng xử trong gia đình": 4 cặp quan hệ — **vợ, chồng**
(chung thủy, nghĩa tình), **cha mẹ với con/ông bà với cháu** (gương mẫu, yêu thương), **con với cha
mẹ/ông bà** (hiếu thảo, lễ phép), **anh, chị, em** (hòa thuận, chia sẻ) — kèm mục "Khẩu hiệu tuyên
truyền". Khác `family_values` ở §12.10 (Quyết định 1189/QĐ-TTg, 1 số hiệu cố định), bộ tiêu chí này
**không có 1 văn bản gốc số hiệu cố định duy nhất** — được ban hành/cập nhật theo từng địa phương và
từng năm, bản chất là 1 khung nội dung chuẩn dùng chung hơn là 1 quyết định cụ thể có thể trích dẫn
số hiệu/ngày tháng. `decision_ref` vì vậy chỉ mô tả đây là "khung chuẩn chung", **không bịa số
hiệu/ngày ban hành** — đúng nguyên tắc đã né ở §12.10 (mô hình rất dễ bịa điều khoản văn bản pháp quy
khi được yêu cầu trích dẫn chính xác 1 thứ không tồn tại ở dạng đó).

Khác biệt bản chất với §12.10: `family_values` là **mục tiêu xã hội tổng thể** (ấm no/hạnh phúc/tiến
bộ/văn minh), áp cho MỌI nội dung không phân biệt vai trò; bộ tiêu chí ứng xử là **quy tắc hành vi
cụ thể theo TỪNG cặp quan hệ** trong gia đình — 1 bài viết về "vợ chồng chia sẻ việc nhà" cần bám
chuẩn khác với 1 bài về "con cái chăm sóc cha mẹ già". Vì khác bản chất, **tách thành khối Lớp 1
riêng** (`family_conduct_standards`) thay vì gộp vào `family_values` — tránh làm loãng vai trò
"theo vai trò cụ thể" vốn là giá trị lớn nhất của bộ tiêu chí này.

Mục "Tiêu chí ứng xử chung" (Tôn trọng, bình đẳng, yêu thương, chia sẻ) của văn bản gốc **không lặp
lại** trong config — trùng vai trò với 4 giá trị nền đã có ở `family_values`.

#### 12.11.2 Thiết kế — 2 lớp, cùng cơ chế §12.10.2

**Lớp 1 — khối cố định, luôn xuất hiện, không thuộc category nào:**

- 4 cặp quan hệ (`key`, `relationship`, `label`, `principles[]`) + `decision_ref` sống ở
  `config('content_foundation.family_conduct_standards')` — nguồn sự thật duy nhất, đọc động ở mọi
  nơi cần dùng (rule validate `in:...`, UI checkbox, khối grounding).
- `FAMILY_CONDUCT_EDITORIAL_NOTES` (map theo `key`, cùng vai trò `FAMILY_VALUE_EDITORIAL_NOTES` ở
  §12.10.2) dịch "chuẩn mực" → "gợi ý áp dụng vào bài viết cụ thể", vì lý do y hệt: câu chữ ở tầng
  quy phạm ("hiếu thảo, lễ phép") không tự bắc cầu sang góc bài viết, và dễ kéo model về hướng rao
  giảng đạo lý/khẩu hiệu.
- `buildFamilyConductGroundingLines()` (CoreIdeaExtractor + VideoIdeaExtractor, code trùng lặp có
  chủ đích như mọi hàm dùng chung khác giữa 2 module) push NGAY SAU khối `buildFamilyValuesGrounding
  Lines()` ở TOP của "Copy prompt cho AI" — LUÔN xuất hiện kể cả chưa chọn chuyên mục, cùng vị trí
  và thứ tự "mục tiêu tích cực trước, ranh giới cấm sau" đã chốt cho §12.10. Không đề xuất ý tưởng
  trích dẫn/copy nguyên văn khẩu hiệu tuyên truyền vào tiêu đề — mục "Khẩu hiệu tuyên truyền" của
  văn bản gốc CHỦ ĐÍCH không được đưa vào chất liệu bài viết. Dòng mở khối KHÔNG viết cứng tên
  khung mà chỉ nội suy `decision_ref` (khác dòng tương ứng ở §12.10, nơi `familyValuesRef` thuần số
  hiệu văn bản nên phải có tên khung đứng trước) — xem §12.11.4-A6. Từ v1.28 khối này bắt buộc có
  thêm 2 dòng: **VỊ TRÍ CỦA ĐỘC GIẢ/KHÁN GIẢ** và **luật xử lý xung đột giữa 2 khung** (§12.11.4-A1,
  A2); dòng ranh giới cứng chỉ nêu phần KHÔNG trùng với ranh giới ở khối §12.10 (§12.11.4-A3).
- `buildFamilyConductBoundaryLine()` — bản NÉN, dùng ở `buildRewritePromptText()` (CoreIdeaExtractor)
  và `singleVideoContextLines()` (VideoIdeaExtractor, dùng chung bởi cả 8 tool sinh câu chữ đăng
  thật: tiêu đề/hook/Shorts/dàn ý/CTA/biên tập lời nói/kịch bản đầy đủ/SEO) — cùng cơ chế
  `buildFamilyValuesBoundaryLine()`. Liệt kê theo TÊN CẶP QUAN HỆ (nối bằng ` / `), KHÔNG theo nhãn
  khẩu hiệu (§12.11.4-A5), và cũng chỉ nêu phần không trùng dòng ranh giới giá trị đứng ngay trước
  nó.
- Thực thi ở BƯỚC 2: bộ lọc bắt buộc nêu CẢ 2 khung và có 1 câu hỏi rà riêng cho các cách vi phạm
  mang tính vai trò (§12.11.4-A4) — ranh giới ở Lớp 1 mà không có chốt kiểm ở bước đánh giá thì chỉ
  được "đọc" 1 lần rồi bỏ.

**Lớp 2 — `family_conduct_focus` (cột mới trên `content_foundations`), theo TỪNG category:**

- Cột `family_conduct_focus` (`json`, nullable) — lưu TẬP KEY (VD `["vo_chong","anh_chi_em"]`),
  cùng nguyên tắc không lưu lại nhãn/mô tả như `family_values_focus`. Cast `array` ở
  `CategoryContentFoundation`.
- UI: nhóm checkbox thứ 2 (4 ô) trong `category-foundations` (`ContentFoundation/resources/views/
  index.blade.php`), cạnh nhóm `family_values_focus`.
- Khi category có `family_conduct_focus`, prompt thêm 1 dòng ưu tiên bổ sung SAU khối cố định Lớp 1
  — không thay thế khối cố định, category không tick vẫn phải tôn trọng cả 4 cặp quan hệ khi ý
  tưởng liên quan. Dòng này (và gợi ý tương ứng ở BƯỚC 1) hiển thị dạng `relationship (label)` qua
  biến `familyConductFocusNames`, KHÔNG phải nhãn đứng một mình (§12.11.4-A5), và nêu rõ độc giả/
  khán giả của chuyên mục mặc định đứng ở phía nào của cặp quan hệ đó.

#### 12.11.3 Ngoài phạm vi (v1.27)

- Không áp dụng vào `buildSummarizePromptText()` — cùng lý do đã áp cho `family_values` ở §12.10.4.
- Không tự động chấm điểm/gắn nhãn mức khớp cặp quan hệ nào — cùng lý do §12.10.4.

#### 12.11.4 Vá lỗ hổng ngữ cảnh sau khi rà lại toàn bộ prompt (v1.28)

Bản v1.27 dựng đủ 2 lớp và chạy được, nhưng rà lại toàn bộ chuỗi ngữ cảnh biên tập (đối tượng độc
giả → khung giá trị → khung ứng xử → BƯỚC 1 sinh ý → BƯỚC 2 lọc) lộ ra 12 lỗ hổng (6 thuộc khung ứng
xử, 6 thuộc khung giá trị/ngữ cảnh đối tượng). Áp cho CẢ 2 module
(`CoreIdeaExtractor` + `VideoIdeaExtractor`), diễn đạt riêng theo bài viết/video.

**A. Thuộc khung ứng xử (§12.11):**

- **A1 — VỊ TRÍ của độc giả trong cặp quan hệ (nặng nhất).** Mỗi cặp quan hệ có HAI phía đối xứng,
  nhưng độc giả của 1 bài chỉ đứng ở MỘT phía. v1.27 chỉ nói "bám đúng chuẩn ứng xử của mối quan hệ
  đó" mà không nói viết TỪ phía nào → sinh ra ý tưởng nhắc nghĩa vụ của thành viên KHÔNG đọc bài
  (chuyên mục nuôi dạy con, độc giả là cha mẹ, nhưng ý tưởng lại là "con cái cần hiếu thảo hơn").
  Nội dung đúng chuẩn mực nhưng nói với người không có mặt — không ai dùng được. Thêm dòng bắt buộc
  ở Lớp 1: xác định phía của độc giả từ mô tả đối tượng rồi viết TỪ phía đó, kèm lưu ý 1 độc giả
  thường ở NHIỀU vị trí cùng lúc (vừa là con, vừa là cha/mẹ, vừa là dâu/rể) nên ý tưởng chạm đúng
  chỗ hai vai trò kéo nhau thường giá trị hơn. Bản VideoIdeaExtractor thêm lý lẽ riêng của video:
  người xem nhận ra "cái này không nói với mình" trong vài giây đầu là thoát ngay.
- **A2 — Xung đột giữa 2 khung, chưa có luật xử lý.** Câu chữ GỐC của tiêu chí "con với cha mẹ, ông
  bà" có mệnh đề con phụ giúp việc nhà *"phù hợp với độ tuổi, **giới tính**"* — đi ngược trực diện
  giá trị **Tiến bộ** (bình đẳng giới) ở §12.10 và `FAMILY_VALUE_EDITORIAL_NOTES.tien_bo` ("không
  củng cố khuôn mẫu việc này vốn của ai"), 2 khối này lại đứng NGAY CẠNH NHAU trong cùng 1 prompt.
  Để mâu thuẫn trần thì model thường chọn theo mệnh đề nghe "chính thức" hơn. **Không sửa câu chữ
  gốc** (config là nguồn sự thật duy nhất, và §12.10.3 đã từ chối việc để ai diễn giải lại văn bản)
  — hoá giải ở TẦNG ÁP DỤNG: chia việc nhà theo độ tuổi/sức khoẻ/quỹ thời gian, không lấy giới tính
  làm căn cứ, kèm câu "đây là cách nền tảng ÁP DỤNG văn bản, không phải sửa văn bản". Đồng thời bổ
  sung luật chung: khi 2 khung kéo ngược nhau, mức sàn ở §12.10 (an toàn thân thể/tinh thần của mọi
  thành viên + bình đẳng giới) được ưu tiên. §12.10 trước đó chỉ có luật cho xung đột GIỮA 2 GIÁ TRỊ,
  chưa có luật nào cho xung đột GIỮA 2 KHUNG.
- **A3 — Trùng lặp ranh giới cứng.** v1.27 lặp lại bạo lực/"đòn roi dạy con"/phán xét gia đình khác
  — vốn đã nằm trong ranh giới cứng của khối §12.10 đứng ngay trên. Trùng lặp trong cùng 1 prompt
  làm loãng chú ý (đúng lo ngại đã dẫn ở §12.10) và tốn token vô ích (§12.5). Chỉ giữ phần THỰC SỰ
  bổ sung — các cách vi phạm mang tính VAI TRÒ: gán hiếu thảo/chung thuỷ/gương mẫu với hy sinh VÔ
  ĐIỀU KIỆN của một thành viên; lấy thứ bậc trong nhà biện minh cho áp đặt; dùng áp lực đạo đức
  ("làm con phải...", "làm dâu phải...") thay cho lý lẽ thực tế. Ghi rõ "bổ sung cho ranh giới ở
  khối giá trị, không lặp lại" để người sửa sau không nhét lại phần đã có.
- **A4 — Khung ứng xử không có chốt kiểm ở BƯỚC 2.** Bộ lọc bắt buộc chỉ thực thi §12.10 bằng 3 câu
  hỏi cụ thể, còn ranh giới §12.11 chỉ được "đọc" 1 lần ở TOP rồi không ai hỏi lại — lệch hẳn mức độ
  thực thi giữa 2 khung dù cả hai đều tự nhận là "LOẠI ngay". Thêm ĐÚNG 1 câu hỏi (d) gộp 2 cách vi
  phạm hay lọt lưới nhất (lệch vị trí độc giả + đòi hy sinh vô điều kiện) thay vì thêm 4 câu song
  song với §12.10 — thêm 4 câu sẽ phình bộ lọc và loãng chú ý, đúng thứ A3 vừa cắt.
- **A5 — Nhãn khẩu hiệu thay cho tên cặp quan hệ.** v1.27 in `label` đứng một mình ("chuyên mục này
  ưu tiên: Chung thủy, nghĩa tình") buộc model tự map ngược về cặp quan hệ — đúng thứ nó hay map
  sai, trong khi CẶP QUAN HỆ mới là thông tin quyết định góc viết. Tệ hơn: nối 4 nhãn bằng dấu phẩy
  trong khi mỗi nhãn đã chứa dấu phẩy → ra 1 chuỗi 8 tính từ không đọc được là 4 cặp. Sửa 3 chỗ:
  dòng ưu tiên + gợi ý BƯỚC 1 dùng `relationship (label)` nối bằng `; ` (biến
  `familyConductFocusNames`), bản nén dùng `relationship` nối bằng ` / `, tóm tắt foundation trên UI
  dùng `relationship`.
- **A6 — Tên khung lặp + ngoặc lồng ngoặc (chỉ thấy khi render).** Dòng mở khối bọc
  `Bộ tiêu chí ứng xử trong gia đình (${familyConductStandardsRef})` y hệt dòng tương ứng ở §12.10,
  nhưng `decision_ref` của khung này ĐÃ chứa tên khung → render ra
  *"Bộ tiêu chí ứng xử trong gia đình (Bộ tiêu chí ứng xử trong gia đình (khung chuẩn chung...))"*.
  Bất đối xứng này là bản chất: `familyValuesRef` thuần số hiệu văn bản nên cần tên khung đứng
  trước, `familyConductStandardsRef` thì không (§12.11.1 — khung này không có số hiệu để trích).

**B. Phát hiện cùng lượt rà, thuộc §12.10 / ngữ cảnh đối tượng:**

- **B1 — Tham chiếu ngược sai hướng trong khối §12.10.** 2 mệnh đề nói "mô tả đối tượng độc giả **ở
  trên**"/"**đã nêu**" trong khi khối giá trị được push TRƯỚC khối mô tả đối tượng — chỉ đúng nhờ ăn
  may là câu persona ở dòng đầu có nhắc lướt qua đối tượng, và sai hẳn khi chưa khai báo đối tượng
  nào. Đổi thành "trong prompt này" (không phụ thuộc thứ tự khối, nên đổi vị trí khối về sau vẫn
  đúng) — sửa trong `buildFamilyValuesGroundingLines()` của cả 2 module.
- **B2 — Lỗ hổng THỨ TỰ khi chưa khai báo đối tượng.** Nhánh "chưa có mô tả đối tượng" trước đó CHỈ
  tồn tại ở tiêu chí 4 của BƯỚC 2: model brainstorm 20-25 ý ở BƯỚC 1 mà không có điểm neo độc giả
  nào, rồi tới BƯỚC 2 mới tự nghĩ ra chân dung và lấy chính chân dung vừa nghĩ ra để loại bớt ý vừa
  sinh → hoặc loại oan hàng loạt, hoặc (thường hơn) tự nới chân dung cho vừa với ý đã sinh, tức tiêu
  chí 4 thành hình thức. Thêm nhánh `else` ở khối đối tượng: dựng chân dung TRƯỚC khi brainstorm,
  cấm nới lại chân dung cho vừa ý đã nghĩ; cách GHI vào `audience_assumption` vẫn để nguyên ở tiêu
  chí 4 (chỗ duy nhất phân biệt 1 chuyên mục vs đa chuyên mục).
- **B3 — Khối đối tượng thiếu chiều "vị trí trong gia đình".** Mô tả đối tượng vốn chi phối 3 việc
  (chọn vấn đề / chọn độ sâu / chọn cách xưng hô) — thêm việc thứ 4: xác định nhóm này đang ở VAI
  nào trong gia đình, làm điểm neo cho A1.
- **B4 — Hardcode nhãn giá trị `"ấm no"` trong prompt.** 2 chỗ ở CoreIdeaExtractor, 1 chỗ ở
  VideoIdeaExtractor viết thẳng chuỗi `"ấm no"`, vi phạm đúng nguyên tắc §12.10.2 ("câu chữ chính
  thức chỉ đọc từ config, KHÔNG hardcode lặp lại"): đổi nhãn trong config sẽ khiến prompt trỏ tới 1
  giá trị không còn tồn tại mà không ai phát hiện. Thêm helper `familyValueLabel(key, fallback)`
  đọc động, có fallback để chỉ dẫn vẫn đọc được nếu config bỏ/đổi key.
- **B5 — "dành 1-2 ý" đọc như nới lỏng yêu cầu giá trị.** Khối §12.10 yêu cầu MỌI ý tưởng phục vụ ít
  nhất 1 giá trị; gợi ý ở BƯỚC 1 lại nói "dành 1-2 ý nhắm thẳng vào giá trị chuyên mục ưu tiên" — dễ
  đọc thành "chỉ 1-2 ý cần có giá trị". Thêm 1 mệnh đề làm rõ 2 thứ này không xung đột.
- **B6 — "chi tiêu = làm tròn nghĩa vụ gia đình" chưa bị chặn ở nơi luôn hiệu lực.** Đây là cách vi
  phạm §12.11 bằng ngôn ngữ thương mại, phổ biến trong nội dung gia đình Việt ("mua quà đắt mới là
  có hiếu", "đầu tư mạnh mới là yêu con"). Ban đầu đặt trong khối `hasProductLikeSource` của
  CoreIdeaExtractor — chỉ chạy khi nguồn LÀ trang sản phẩm, trong khi mục "gợi ý tối thiểu 5 sản
  phẩm" thì LUÔN chạy ở cả 2 module. Chuyển vào ràng buộc của danh sách sản phẩm (luôn hiệu lực) ở
  cả 2 module.

**Đã cân nhắc, CHƯA làm — kích thước 2 khối grounding cố định.** Sau khi vá, với 1 nguồn ngắn thì 2
khối §12.10 + §12.11 chiếm ~35% prompt (≈4.300 + ≈5.300 ký tự). Phần chỉ dẫn tự viết đã được nén
(dòng dài nhất 945 → 625 ký tự, theo đúng nguyên tắc "không nhồi 1 đoạn quá dài" đã chốt cho §12.10);
phần còn lại là `principles[]` nguyên văn của cả 4 cặp quan hệ (≈2.600 ký tự) — không nén được mà
không diễn giải lại văn bản, đúng thứ §12.10.3 đã từ chối. Lựa chọn duy nhất để giảm tiếp là
**progressive disclosure**: chỉ in đầy đủ `principles` của cặp quan hệ mà category đã tick ở
`family_conduct_focus`, các cặp còn lại chỉ liệt kê tên + nhãn. Việc đó đi ngược §12.11.2 hiện tại
("Lớp 1 luôn liệt kê đủ 4 cặp, focus chỉ là ưu tiên bổ sung chứ không phải giới hạn") nên là quyết
định thiết kế, không tự đổi. Với nguồn thật (5-7 URL) tỷ lệ này tụt xuống thấp hơn nhiều vì JSON
nguồn lớn hơn hẳn.

### 12.12 Tầng hành vi độc giả — `audience_behavior` (v1.29)

Tham khảo lindapophal.substack.com/p/the-2026-content-marketing-imperative — "3 cấp độ hiểu đối
tượng": Demographic (họ LÀ ai — tuổi, ngành, chức danh, địa lý) → Psychographic (họ QUAN TÂM gì —
lo lắng, khao khát) → Behavioral (họ THỰC SỰ LÀM gì — ngày của họ trông thế nào, tiêu thụ/tìm kiếm
nội dung ở đâu, khi nào). Nguồn nhấn mạnh tầng Behavioral là **lợi thế cạnh tranh** khi AI có thể
sinh nội dung chung chung ở tầng Demographic/Psychographic dễ dàng, còn tầng Behavioral đòi hỏi
quan sát thật mà phần lớn thương hiệu "dừng lại trước khi làm" ("stop short of doing the real
work").

Đối chiếu chuỗi ngữ cảnh đối tượng đã có: `audience` (§12.2, freetext ngắn) là Level 1;
`pain_points`/`objections`/`decision_criteria` (§12.6, v1.16) đã phủ Level 2. **Level 3 chưa có
field nào** — khối chỉ dẫn `audienceText` ở BƯỚC 0 (`buildLayer2PromptText()`) có nhắc "bối cảnh
sinh hoạt, điều kiện kinh tế và quỹ thời gian thực tế" khi diễn giải cách DÙNG mô tả đối tượng,
nhưng đó là model tự SUY LUẬN từ 1 câu nhân khẩu học ngắn (VD "mẹ mới sinh con đầu lòng"), không
phải dữ liệu hành vi THẬT do editor quan sát/ghi lại.

- Field mới `audience_behavior` (text, nullable) trên `content_foundations`, đặt SAU `audience` —
  migration `2026_08_14_000001_add_audience_behavior_to_content_foundations_table.php` (ALTER
  riêng, không sửa migration gốc `2026_08_02_000001_create_content_foundations_table.php`).
- Khác `audience` (mô tả TĨNH họ là ai) — `audience_behavior` là quan sát THẬT về hành vi: giờ họ
  rảnh để đọc/xem, kênh họ hay dùng (Google/TikTok/Facebook group...), kiểu câu hỏi họ hay gõ tìm
  kiếm, họ có xu hướng bình luận công khai hay chỉ đọc/lưu bài trong im lặng — lý tưởng nhất rút ra
  từ quan sát thực tế (bình luận, search analytics, trò chuyện trực tiếp với độc giả/khách hàng),
  cùng tinh thần nguồn gốc của `pain_points` (§12.6: "khảo sát/feedback/câu hỏi lặp lại" thay vì
  editor tự đoán) — không phải suy luận từ `audience`.
- Cùng nhóm validate/quyền/UI với `pain_points`/`objections`/`decision_criteria` — không Gate/rule
  riêng (`CategoryFoundationController::upsert()`, `max:2000` — dài hơn `audience` Max 500 vì mô tả
  hành vi thường cần liệt kê nhiều điểm quan sát hơn 1 câu nhân khẩu học).
- Render vào TOP của "Copy prompt cho AI" ở **CẢ 2 module song sinh** (`CoreIdeaExtractor` VÀ
  `VideoIdeaExtractor` — cùng đọc `CategoryContentFoundation`, đúng tiền lệ "2 lỗ hổng ngữ cảnh
  khác trong 2 blade" đã ghi ở v1.14), đặt NGAY SAU khối `audienceText` (cùng nhóm "độc giả là
  ai/sống thế nào", trước nhóm "họ nghĩ gì" của `writer_insights`/`pain_points`/...). Chỉ dẫn dùng
  để chọn VÍ DỤ/TÌNH HUỐNG gần đời sống thật + ưu tiên góc khai thác khớp CÁCH họ thực sự tiếp cận
  nội dung, khác vai trò của `pain_points` (chọn VẤN ĐỀ) hay `objections` (chọn BẰNG CHỨNG).
- **Không** nối vào `formatHints` (§12.4, v1.19 — `pain_points`/`objections`/`decision_criteria`
  ánh xạ sang gợi ý ĐỊNH DẠNG cụ thể vì mỗi field có 1 Ý NGHĨA rời rạc rõ ràng ánh xạ 1-1 sang 1
  dạng bài). `audience_behavior` là văn bản tự do đa dạng (giờ rảnh, kênh dùng, kiểu tìm kiếm...)
  không có ánh xạ 1-1 sang 1 dạng bài cụ thể — gán cứng sẽ là suy diễn quá tay, đúng loại rủi ro
  "framework flourish" module đã từ chối nhiều lần (v1.6, v1.8, v1.18, v1.22, v1.23, v1.24).
- **Không đổi Layer 1/Layer 2 JSON schema (§5, §7)** — thuần field UI/prompt-template.

**Ngoài phạm vi (v1.29, đã cân nhắc nhưng KHÔNG làm):**
- "Shadow buyers" (độc giả nghiên cứu/ảnh hưởng quyết định nhưng tránh tương tác công khai) — khái
  niệm gốc thuộc B2B lead-gen (form/webinar), không có tương đương ở platform xuất bản nội dung
  biên tập công khai này. Phần tinh thần chuyển hoá được (đừng chỉ đo đối tượng qua ai bình luận
  công khai) đã gộp vào mô tả nguồn dữ liệu của `audience_behavior` ở trên, không cần khái niệm/
  field riêng.
- Tích hợp TỰ ĐỘNG thu thập hành vi (phân tích bình luận thật/Google Search Console/GA...) — cùng
  quyết định đã chốt cho `pain_points` từ v1.10 ("không tích hợp khảo sát/phân tích... vẫn là field
  editor tự điền tay"), không mở lại riêng cho field này.
- "Viết cho 1 người cụ thể, không phải đám đông" — không phải khoảng trống, đã có sẵn từ vá
  2026-08-06 (xem đối chiếu ở changelog v1.29 đầu file).

### 12.13 Tài liệu sản phẩm/dịch vụ + ví dụ nội dung mẫu — chuẩn hoá "Constants" theo MarTech (v1.30)

Tham khảo martech.org/how-to-build-an-ai-content-system-that-works — "Constants" của 1 hệ thống nội
dung AI B2C cần: ICP (đã phủ), brand voice có VÍ DỤ THẬT (đã phủ qua `style_sample`, xem ghi chú
label bên dưới), **tài liệu mô tả chi tiết sản phẩm/dịch vụ**, và **nội dung/dàn ý mẫu TỐT NHẤT đã
có**. 2 phần cuối chưa từng có field riêng trên `content_foundations` — editor phải tự nhét tạm vào
`core_focus`/`writer_insights` (lệch mục đích thiết kế của 2 field đó, xem §12.2).

- 2 field mới trên `content_foundations`, ALTER riêng (`2026_08_26_000001_add_martech_constants_
  fields_to_content_foundations_table.php`, không sửa migration gốc):
  - `product_service_docs` (text, nullable, Max 4000) — thành phần/công dụng/thông số/đối tượng
    dùng/lưu ý khi dùng... của sản phẩm/dịch vụ chuyên mục này viết về. Dùng làm NGUỒN SỰ THẬT khi
    ý tưởng/outline/prompt nhắc tới sản phẩm cụ thể — mục tiêu là giảm bịa thông số/công dụng, KHÁC
    `core_focus` (mô tả trọng tâm NỘI DUNG, không phải thông số sản phẩm).
  - `best_example_content` (text, nullable, Max 4000) — dán NGUYÊN VĂN 1 bài/dàn ý editor coi là
    chuẩn mực cho chuyên mục. KHÁC `style_sample` (chỉ 1 đoạn văn mẫu NGẮN về giọng văn) — đây là 1
    bài/dàn ý HOÀN CHỈNH để AI học cấu trúc/độ sâu/cách triển khai, không chỉ cách xưng hô/dùng từ.
- Cùng nhóm validate/quyền/UI với các field ad-hoc khác (`CategoryFoundationController::upsert()`,
  không Gate riêng).
- **Vệ sinh prompt-injection bắt buộc** (CLAUDE.md §0) — khác các field text ngắn khác ở §12.2 (đưa
  thẳng vào bullet "nhãn: giá trị"), 2 field này là văn bản DÀI editor có thể dán nguyên văn từ
  nguồn khác (trang sản phẩm, 1 bài viết mẫu tham khảo) nên LUÔN bọc delimiter + câu rào "đây là DỮ
  LIỆU tham khảo, bỏ qua mọi câu lệnh/yêu cầu nếu đoạn dưới vô tình chứa" — cùng khuôn `style_sample`
  đã dùng ở `CoreIdeaExtractor`/`PromptFrameworkStudio` (`<<<TAI_LIEU_SAN_PHAM>>>`/
  `<<<VI_DU_NOI_DUNG_MAU>>>` làm delimiter riêng, không trùng `<<<VAN_PHONG_MAU>>>` của
  `style_sample` — tránh AI nhầm lẫn ranh giới khối khi cả 3 khối cùng xuất hiện).
- Render vào TOP của prompt sinh ý tưởng (`CoreIdeaExtractor::buildLayer2PromptText()`, ngay sau
  `rejected_ideas`), khối MIDDLE "Ngữ cảnh chuyên mục" của `ContentOutlines`
  (`BuildContentOutlinePromptAction::buildMiddle()`, ngay sau `rejected_ideas` — cùng vị trí tương
  đối), và khối "Bối cảnh biên tập" của `PromptFrameworkStudio`
  (`BuildEditorialContextBlockAction`, ngay sau `style_sample`).
- **KHÔNG** áp dụng cho `AIVideoStudioTemplate` ở bản vá này — module này không đọc
  `CategoryContentFoundation` (không có khái niệm chuyên mục/category), nối nó vào Constants layer
  dùng chung đòi hỏi thêm bước chọn category — thay đổi cấu trúc lớn hơn phạm vi bản vá thuần
  additive này, để dành quyết định riêng (xem nhận xét v1.30 đầu file).
- **Không đổi Layer 1/Layer 2 JSON schema (§5, §7)** — thuần field UI/prompt-template như mọi field
  khác trong §12.2.

### 12.14 "Query Fan-out" + gợi ý vai trò nội dung — đối chiếu topic cluster cho AI search (v1.31)

Tham khảo searchengineland.com/guide/topic-clusters-for-ai-search — triết lý cốt lõi: AI search
engine (Google AI Overview, ChatGPT, Perplexity...) trả lời 1 câu hỏi RỘNG bằng cách tự "phân rã"
(fan-out) thành nhiều câu hỏi con rồi tổng hợp câu trả lời từ các nguồn bao phủ TOÀN DIỆN mạng câu
hỏi đó — khác SEO truyền thống thường chỉ nhắm 1 ý định tìm kiếm. Nội dung xây theo topic cluster
(1 bài trụ cột + nhiều bài cụm hẹp phủ đều mạng câu hỏi) có xu hướng được trích dẫn nhiều hơn.

**§12.14a — Trend-forward ideation (BƯỚC 1, `buildLayer2PromptText()`):**
- Thêm 1 chỉ dẫn cho phép brainstorm THÊM 1-2 ý tưởng "dự đoán xu hướng" — nỗi đau/mối quan tâm MỚI
  NỔI của phụ huynh mà công cụ nghiên cứu từ khoá tĩnh có thể chưa kịp phản ánh, dựa trên hiểu biết
  chung của model về xu hướng nuôi dạy con/gia đình hiện nay.
- **Guardrail bắt buộc:** phải ghi rõ trong `reason` đây là "dự đoán xu hướng" — khác các ý tưởng
  còn lại vốn phải bám dữ liệu nguồn/`pain_points`/`objections` đã quan sát THẬT (§12.6/§12.6-v1.16).
  Không có guardrail này, biên tập viên không phân biệt được ý nào cần tự kiểm chứng thêm trước khi
  viết — đúng nguyên tắc "không bịa dữ liệu" xuyên suốt module (§9, §12.4).
- Thuần chỉ dẫn văn bản, KHÔNG đổi RESPONSE_SCHEMA — model tự quyết định có đề xuất ý dạng này hay
  không tuỳ chất liệu nguồn, không bắt buộc.

**§12.14b — `cluster_questions` + `content_role_suggestion` (per-idea, THAY ĐỔI SCHEMA):**
- 2 field mới trên `RunLayer2ExtractionAction::RESPONSE_SCHEMA` (`ideas[].properties`), cả 2 đều
  `required`:
  - `cluster_questions` (array of string, 3-6 mục) — mạng câu hỏi ngách 1 AI search engine sẽ phân
    rã ra khi phụ huynh tìm hiểu SÂU chủ đề của ý tưởng. Câu hỏi phải THỰC TẾ (ngôn ngữ tự nhiên phụ
    huynh sẽ gõ/hỏi), không bịa thuật ngữ kỹ thuật xa lạ.
  - `content_role_suggestion` (enum `pillar`/`cluster`) — dựa vào `cluster_questions`: "pillar" nếu
    ý tưởng đủ RỘNG để trả lời TOÀN BỘ mạng câu hỏi trong 1 bài tổng quan (liên kết XUỐNG các bài
    cụm hẹp hơn sau này), "cluster" nếu chỉ trả lời 1 nhánh HẸP (nên liên kết LÊN 1 bài tổng quan
    rộng hơn). **Ánh xạ trực tiếp sang `content_role` (pillar/cluster) đã có ở
    `ContentOutlines_Technical_Specification.md` §4.9** — không phải khái niệm mới, chỉ đưa gợi ý
    sớm hơn (từ khâu ý tưởng) cho quyết định biên tập viên vẫn tự chọn tay khi lên dàn ý. KHÔNG có
    liên kết code trực tiếp giữa 2 module (CoreIdeaExtractor không ghi bản ghi nào `ContentOutlines`
    đọc được) — đây là gợi ý hiển thị cho CON NGƯỜI đọc và tự quyết định khi tạo outline, không phải
    tích hợp dữ liệu tự động.
- Prompt (BƯỚC 1) yêu cầu model hình dung mạng câu hỏi fan-out cho MỖI ý tưởng brainstorm, và đa
  dạng hoá 20-25 ý tưởng theo ĐỘ BAO PHỦ mạng câu hỏi (không chỉ theo góc nhìn như trước) — tránh
  tình trạng 20-25 ý cùng xoay quanh 1 mạng câu hỏi hẹp. BƯỚC 2 (sau tiêu chí 4, mục "(5a)"/"(5b)")
  yêu cầu xác định 2 field này CHỈ cho ý tưởng ĐÃ đạt cả 4 tiêu chí (khớp đúng field nào được đưa
  vào `ideas`).
- **Render:** 2 cột mới cuối bảng Markdown ("Câu hỏi liên quan (fan-out)", "Vai trò gợi ý (Trụ
  cột/Cụm)") ở CẢ 2 đường đi — `RunLayer2ExtractionAction::renderMarkdownTable()` (tự động, ghép
  `cluster_questions` bằng "; ", map `pillar`/`cluster` sang nhãn "Trụ cột"/"Cụm") và
  `ideaTableColumns`/BƯỚC 3 nhánh `forExternalChat` (index.blade.php) — giữ 2 đường đi cho ra bảng
  giống nhau, cùng nguyên tắc đã áp dụng cho mọi cột trước đó (§12.4).
- **Chi phí thật (khác §12.13):** đây là module DUY NHẤT trong 4 module đối chiếu ở v1.30/v1.31 có
  Layer 2 TỰ ĐỘNG gọi AI Provider thật (`RunLayer2ExtractionAction`, vòng lặp goal-based) — thêm 2
  field bắt buộc/idea làm tăng output token thật (mỗi ý tưởng dài hơn ~3-6 câu hỏi ngắn). Chấp nhận
  được vì đổi lại là tín hiệu bao phủ chủ đề CÓ THẬT dùng được ở bước sau (không phải trang trí
  không dùng tới) — cùng tinh thần cân nhắc chi phí đã có ở goal-based loop (§4.4 docblock class,
  `layer2.max_output_tokens`/`layer2.target_idea_count`/`layer2.max_loop_iterations`).
- **Không áp dụng** cho `ContentOutlines`/`PromptFrameworkStudio`/`AIVideoStudioTemplate` — 3 module
  đó không có bước "brainstorm nhiều ý tưởng cần đa dạng hoá độ bao phủ chủ đề" (mỗi lần chạy chỉ
  sinh 1 outline/1 prompt/1 kịch bản CHO 1 chủ đề đã chốt, không phải danh sách ý tưởng cần dàn
  trải). `ContentOutlines` đã có cơ chế tương đương cho ĐÚNG 1 chủ đề đã chọn từ trước (answer-first
  §4.11, PAA thật §4.12, `content_role` pillar/cluster §4.9) — không cần vá thêm, xem đối chiếu đầy
  đủ ở changelog v1.31 đầu file.

---

## 13. Source Structure Signal (v1.5)

### 13.1 Bối cảnh

Tham khảo https://kime.ai/blog/structure-content-for-llm-extraction — nội dung dùng bảng, danh
sách đánh số, heading dạng câu hỏi được AI answer engine (ChatGPT/Perplexity/AI Overviews) trích
dẫn nhiều hơn văn xuôi thường (bảng: 4.2x, danh sách đánh số cho quy trình: 2.7x). Khi nghiên cứu
1 nguồn tham khảo, người viết có lợi khi biết nguồn đó **đã tối ưu tốt cho AI search tới đâu** —
nếu đối thủ đã viết rất "AI-friendly", cạnh tranh thứ hạng trên AI answer engine bằng góc viết
giống hệt sẽ khó hơn; nên cân nhắc chọn góc viết khác biệt.

### 13.2 Thiết kế

- Field mới `source_structure` (object, xem §5.2/§7) — tính trong `ExtractRawContentAction`,
  SCOPE THEO cùng root đã dùng cho `main_content`/`headings` (không quét toàn trang) — tránh đếm
  nhầm bảng/danh sách nằm trong sidebar/widget "bài liên quan" không thuộc nội dung chính:
  - `has_tables`: có ít nhất 1 `<table>` trong phạm vi nội dung chính.
  - `has_numbered_lists`: có ít nhất 1 `<ol>`.
  - `has_bullet_lists`: có ít nhất 1 `<ul>`.
  - `question_heading_ratio`: tỉ lệ heading (đã loại noise/trang trí, §5.3) kết thúc bằng dấu
    "?" trên tổng số heading — `0.0` nếu không có heading nào.
- Đây là tín hiệu THÔ, khách quan (đếm được, không suy diễn) — CỐ Ý không quy về 1 nhãn
  "tốt/xấu" đơn nhất trong field riêng, tránh thêm 1 tầng heuristic mờ vào schema đã version hoá
  chặt chẽ (§5.4/§9 dùng ngưỡng số cụ thể, không phải nhãn cảm tính).
- **Ghi chú advisory** (không phải field riêng): khi `(has_tables HOẶC has_numbered_lists) VÀ
  question_heading_ratio >= 0.3`, phần `notes` (§5.2/§7) có thêm câu gợi ý nguồn đã cấu trúc khá
  tốt cho AI trích xuất, cân nhắc góc viết khác biệt. Ngưỡng `0.3` là heuristic nhẹ (tham khảo,
  không phải số liệu khoa học) — xem `CoreIdeaExtractorController::appendStructureNote()`.
  **Advisory note CHỈ là gợi ý tham khảo (append vào `notes`/hiển thị UI) — KHÔNG ảnh hưởng
  `extraction_confidence`, `error`, hay `status` (§7.1) theo bất kỳ cách nào**; các field đó tính
  hoàn toàn độc lập, đúng như trước khi có `source_structure` (v1.5).
- Luôn có mặt kể cả khi `extraction_confidence = "low"` (dữ liệu Layer 1 thuần, không phụ thuộc
  Layer 2) — toàn `false`/`0.0` khi không lấy được HTML nào để phân tích (§9, xem
  `SourceStructureData::none()`).
- Áp dụng cho CẢ single-URL (`extract`) lẫn batch (`extract-batch`, field `source_structure`
  trong mỗi phần tử `sources[]`, `null` khi nguồn đó `status != success`).

### 13.3 Ngoài phạm vi (v1.5)

- Không phân tích cấu trúc CHÍNH bài viết đang soạn trong module `Post` (đó là 1 tính năng khác —
  "AEO readiness checklist" cho editor, chưa triển khai, xem thảo luận đi kèm).
- Không tự động hoá Layer 2/gọi AI Provider — vẫn thuần phân tích DOM (đếm thẻ/regex), không có
  lệnh gọi AI nào ở tính năng này.

---

**End of Specification**
