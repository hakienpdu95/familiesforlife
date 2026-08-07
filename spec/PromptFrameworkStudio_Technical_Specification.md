# Module Thư viện & Sinh Prompt theo Framework (PromptFrameworkStudio)

**Đặc tả Kỹ thuật Chi tiết — Sẵn sàng Triển khai**

**Phiên bản:** 2.5
**Ngày:** 07/08/2026
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Module mới:** `Modules/PromptFrameworkStudio`
**Module liên quan:** Không có (module độc lập, không phụ thuộc/không bị phụ thuộc bởi module nghiệp vụ nào — tương tự `Modules/ContentOutlines`, xem §0)

> **v1.1 (review round — chốt các điểm phải làm rõ trước khi code):** (1) Hoàn thiện đầy đủ
> `fields`/`template`/`example` cho **cả 13 framework** trong §2 — v1.0 chỉ viết đầy đủ `costar`,
> 12 framework còn lại ghi "cùng cấu trúc" là chưa đủ để code không đoán mò. (2) Thêm §5.4 — quyết
> định hành vi khi `framework_key` của 1 bản ghi cũ bị gỡ khỏi config (orphaned). (3) Thêm §4.2 —
> cách cụ thể truyền dữ liệu framework từ config xuống Alpine (`@json(...)`). (4) Thêm
> `index('label')` ở migration (§3.1) và danh sách cột Tabulator cụ thể (§4.3). (5) Bổ sung 4 test
> case còn thiếu ở §8. (6) Ghi rõ "live preview khi gõ" và "nút Dùng framework này từ Library" vào
> §7 là việc **để sau** (bản phát hành tính năng kế tiếp), không thuộc phạm vi triển khai lần đầu.

> **v1.2 (polish round — defense-in-depth):** (1) §4.1 — `RegenerateGeneratedPromptAction` tự kiểm
> tra framework tồn tại thay vì chỉ dựa vào controller đã lọc trước (§5.4), phòng trường hợp Action
> được gọi từ chỗ khác không đi qua `edit()`. (2) §4.2 — ghi rõ cách pre-fill `values` từ
> `$prompt->field_values` khi `x-data` khởi tạo ở trang `edit`, thay vì chỉ có ví dụ cho `create`.

> **v1.3 (BUG FIX must-fix + UI/UX polish cho người dùng không rành kỹ thuật):**
> (1) **Sửa lỗi thật**: §4.2 ghi `@json(config(...))` nhúng vào `x-data="..."` — SAI, vì JSON tự nó
> dùng `"` làm cú pháp bắt buộc (không phải nội dung cần escape), nên `@json()` không bao giờ an
> toàn khi nhúng vào 1 thuộc tính HTML cũng bọc bằng `"`; dấu `"` đầu tiên trong JSON cắt đứt
> attribute ngay lập tức → Alpine nhận biểu thức vỡ cú pháp → toàn bộ scope (`frameworks`,
> `selectedKey`, `selectedFramework`) undefined. Đã đổi toàn bộ sang `@js()` (biên dịch ra
> `Illuminate\Support\Js::from(...)->toHtml()`, bọc trong `JSON.parse('...')` bằng nháy đơn +
> `"`) — đúng cơ chế `Js::from()` đã dùng cho 2 tham số còn lại của trang `edit`, và đúng
> convention `Modules/ContentOutlines` đã áp dụng trước đó. (2) **UI/UX cho người không rành kỹ
> thuật** (§4.2/§7): thăng cấp "Dùng framework này" từ mục để-sau lên **đã làm** —
> `PromptGenerationController::create()` nhận `?framework=key` (validate theo config keys, bỏ qua
> nếu không hợp lệ), Thư viện (`library/index`) có nút "Dùng mẫu này" đi thẳng vào `create` với
> mẫu đã chọn sẵn; đổi ngôn ngữ hiển thị bớt thuật ngữ ("framework" → "mẫu", thêm mô tả "Phù hợp
> khi..." nổi bật); mỗi field trong form giờ có `placeholder` lấy trực tiếp từ `example` đã có sẵn
> trong config (không thêm dữ liệu mới) — người dùng thấy ngay ví dụ cụ thể phải điền gì, không
> phải đoán; thêm nút "Đổi mẫu khác" ngay tại bước 2 (không cần rời trang); thêm thanh bước 1→2.
> (3) **Sửa lỗi thật #2**: thanh bước 1→2 dùng `<template x-if="selectedKey">&nbsp;✓</template>` —
> nội dung của `x-if` chỉ là text, không bọc trong 1 element; Alpine lấy nội dung `x-if` qua
> `.firstElementChild` (chỉ nhận Element, text node → `null`) → gán scope lên `null` → "can't
> access property '_x_dataStack', e is null". Đổi sang `<span x-show="selectedKey">` (không cần
> mount/unmount DOM cho 1 ký tự trang trí). Đã rà soát lại toàn bộ `<template x-if>`/`x-for` khác
> trong module — không còn trường hợp nội dung không có element gốc.

> **v1.4 (đối chiếu nguồn tham khảo — làm giàu ví dụ RACE theo yêu cầu người dùng):** đối chiếu
> `promptquorum.com`/`promptary.dev` (bảng tổng hợp) với trang chi tiết riêng
> `promptary.dev/frameworks/race/` phát hiện: trang chi tiết có thêm field tuỳ chọn **Rules**
> (ràng buộc áp dụng chung — do chính promptary.dev tự thêm, không phải thành phần gốc của RACE,
> nguồn `promptquorum.com` không có) và nhấn mạnh Context là field hay bị bỏ sót nhất nhưng ảnh
> hưởng nhiều nhất. Đối chiếu chéo `promptary.dev/frameworks/costar/` cho thấy cùng khoảng trống
> hệ thống (cũng có field Rules tương tự) — nhưng theo quyết định của người dùng, **chỉ nâng cấp
> `race`** ở vòng này (§2): thêm field `rules` (tuỳ chọn), bổ sung ghi chú vai trò Context ngay
> trong `label`, viết lại ví dụ đầy đủ hơn (có tình huống "đã thử mà chưa hiệu quả" — đúng tinh
> thần Context nên chứa gì). 12 framework còn lại giữ nguyên cấu trúc v1.2, không đối chiếu lại.

> **v1.5 (đối chiếu tiếp `promptary.dev/frameworks/risen/` theo yêu cầu người dùng):** cùng khoảng
> trống hệ thống như v1.4 — trang chi tiết RISEN cũng có field tuỳ chọn **Rules** (do promptary.dev
> tự thêm, không phải thành phần gốc), nhấn mạnh **Steps nên đánh số 3-7 bước, mỗi bước 1 hành
> động**, và **Narrowing nên nêu rõ AI KHÔNG được làm gì** (loại trừ + quy tắc định dạng), cùng
> khuyến nghị "việc đơn giản tự đứng một mình thì dùng RACE cho nhanh hơn". Đã nâng cấp `risen`
> (§2) tương tự `race`: thêm field `rules` (tuỳ chọn), bổ sung ghi chú vào `label` của
> `steps`/`narrowing`, cập nhật `best_for` nêu rõ ranh giới với `race`, viết lại ví dụ (thêm bước
> 4 kiểm tra trùng lặp, thêm giá trị `rules`). 11 framework còn lại (trừ `race`/`risen`) vẫn giữ
> nguyên cấu trúc v1.2 — chỉ nâng cấp khi được yêu cầu cụ thể từng cái, không tự ý audit hàng loạt.

> **v1.6 (đối chiếu tiếp `promptary.dev/frameworks/costar/` theo yêu cầu người dùng):** cùng
> khoảng trống hệ thống như `race`/`risen` — trang chi tiết CO-STAR cũng có field tuỳ chọn
> **Rules** (do promptary.dev tự thêm), cùng 2 nhấn mạnh chưa có trong config cũ: (1) **Audience
> cần cụ thể hoá tối đa** — ví dụ trang cho: không viết "lập trình viên" mà "kỹ sư backend cấp
> cao, hoài nghi công cụ mới"; (2) **Style khác Tone** — Style là cách tiếp cận viết (phân tích/kể
> chuyện/thuyết phục...), Tone là sắc thái cảm xúc, lớp tinh tế hơn Style. Cũng ghi nhận thêm 1
> thông tin công khai đáng tin (không phải nội dung độc quyền của trang): CO-STAR do GovTech
> Singapore phát triển, vô địch cuộc thi prompt engineering GPT-4 năm 2023 — thêm vào
> `description` cho người dùng có ngữ cảnh. Đã nâng cấp `costar` (§2) tương tự `race`/`risen`:
> thêm field `rules` (tuỳ chọn), bổ sung ghi chú vào `label` từng field theo đúng 2 nhấn mạnh
> trên, cập nhật `best_for` nêu rõ ranh giới với `race`/`craft` (đúng nguyên văn "Comparative
> Advantages" của trang, diễn giải lại không sao chép), viết lại ví dụ `audience` cụ thể hoá hơn
> (nêu rõ nỗi lo cụ thể + trình độ hiểu biết, thay vì chỉ độ tuổi/thu nhập chung chung), thêm giá
> trị `rules`. 10 framework còn lại (trừ `race`/`risen`/`costar`) vẫn giữ nguyên cấu trúc v1.2.

> **v1.7 (đối chiếu tiếp `promptary.dev/frameworks/rtf/` theo yêu cầu người dùng):** cùng field
> tuỳ chọn **Rules** như 3 framework trước, nhưng lần này còn phát hiện **`best_for` cũ sai lệch
> định hướng**: bản v1.2 ghi "Đào tạo nội bộ, nội dung chuẩn hoá, tài liệu giảng dạy" (lấy từ
> nguồn `promptquorum.com` ở vòng thu thập dữ liệu ban đầu), trong khi `promptary.dev` mô tả RTF
> đúng bản chất tối giản 3-field của nó hơn: **tác vụ nhanh, bối cảnh đã hiển nhiên** (chuyển đổi
> định dạng, dịch thuật, chỉnh code ngắn — "dưới 30 giây"), và **khi kết quả ra chung chung thì
> nâng cấp lên RACE (thêm Context) thay vì cố ép RTF** — soạn tài liệu đào tạo thực ra cần Context/
> Audience nên hợp với CO-STAR/RISEN hơn. Đã sửa `best_for` theo hướng `promptary.dev` (hợp lý hơn
> với cấu trúc thật của framework), thêm field `rules` (tuỳ chọn), bổ sung ghi chú vào `label`
> (Role viết gọn 1 câu, Task tránh mơ hồ, Format có thể yêu cầu "chỉ trả về kết quả, không giải
> thích"), viết lại ví dụ theo tinh thần "tác vụ nhanh" (dịch đoạn văn bản) thay vì ví dụ "soạn tài
> liệu đào tạo" cũ (không còn khớp `best_for` mới). 9 framework còn lại vẫn giữ nguyên cấu trúc v1.2.

> **v1.8 (đối chiếu tiếp `promptary.dev/frameworks/ape/` theo yêu cầu người dùng):** cùng field
> tuỳ chọn **Rules** như 4 framework trước; insight quan trọng nhất của trang này: **"Purpose là
> điểm khác biệt giữa APE và RTF"** — Purpose không chỉ là lý do, mà giúp AI tự phán đoán đúng ý
> khi Action còn mơ hồ/nhiều cách hiểu, thay vì làm theo nghĩa đen. Trang cũng nêu rõ khi nào
> KHÔNG dùng APE: cần vai trò/bối cảnh chi tiết → dùng RACE (có thêm Role + Context). **Sửa kèm 1
> điểm nhất quán schema**: đổi `expectation` từ optional → **required** — APE chỉ có 3 chữ cái cốt
> lõi (Action/Purpose/Expectation), cả 3 nên bắt buộc giống cách đã áp dụng cho RTF (3 field cốt
> lõi đều required, chỉ Rules thêm vào mới optional) — v1.2 để `expectation` optional là thiếu nhất
> quán, không phải quyết định có chủ đích. Cập nhật kèm 1 test bị ảnh hưởng
> (`PromptGenerationControllerTest::test_update_regenerates_prompt_but_keeps_uuid_and_framework_key`
> đổi `expectation` từ rỗng sang có giá trị vì field không còn optional ở `ape` — hành vi "field
> optional để trống" vẫn được test đầy đủ qua `costar`, framework còn field optional thật). Viết
> lại ví dụ theo đúng ví dụ minh hoạ của trang (rewrite thông báo lỗi kỹ thuật — thể hiện rõ vai
> trò Purpose). 8 framework còn lại vẫn giữ nguyên cấu trúc v1.2.

> **v1.9 (đối chiếu tiếp `promptary.dev/frameworks/crit/` theo yêu cầu người dùng):** cùng field
> tuỳ chọn **Rules** như 5 framework trước; insight riêng của CRIT: **Role không chỉ là vai trò
> viết văn mà còn quyết định LOẠI CÂU HỎI AI sẽ hỏi lại** (vai trò khác → hướng hỏi khác); tip quan
> trọng — nên ghi **số lượng câu hỏi (3-5) + hướng cần hỏi**, KHÔNG viết sẵn câu hỏi cụ thể (để AI
> tự soạn câu hỏi đúng theo vai trò đã gán, đây là bản chất "hỏi lại" chứ không phải "trả lời sẵn
> hộ"). Trang cũng nêu use case cụ thể (chiến lược thương hiệu, tư vấn định hướng) và khi nào KHÔNG
> dùng (đã biết rõ muốn gì → RACE nhanh hơn; CRIT cần nhiều lượt qua lại, không hợp tự động hoá 1
> lần). Đã cập nhật `crit` (§2): thêm field `rules` (tuỳ chọn), sửa `label` của `role`/
> `interview_questions`/`task` theo đúng các nhấn mạnh trên, cập nhật `best_for`, và **sửa lại ví
> dụ `interview_questions`** — bản cũ viết sẵn 3 câu hỏi cụ thể (đi ngược tip của trang), đổi sang
> nêu số lượng + hướng cần hỏi đúng tinh thần khuyến nghị. 7 framework còn lại vẫn giữ cấu trúc v1.2.

> **v2.0 (BUG FIX thật — không chỉ enrichment — đối chiếu `promptary.dev/frameworks/craft/` theo
> yêu cầu người dùng):** phát hiện `craft` ở v1.0-v1.9 dùng **SAI field thứ 5** — 5 chữ cái đúng
> của CRAFT là Context-Role-Action-Format-**Tone**, nhưng config lại có field `target` ("Đối tượng
> mục tiêu"). Đây là lỗi nhập liệu thật từ vòng thu thập dữ liệu ban đầu (cả `promptquorum.com` lẫn
> bảng tổng hợp `promptary.dev/frameworks` đều ghi rõ "Tone", không phải "Target") — không phải
> khoảng trống "thiếu chi tiết" như 5 framework đã audit trước (`race`/`risen`/`costar`/`rtf`/
> `ape`/`crit`). Trang chi tiết CRAFT còn nêu rõ: **Tone là điểm khác biệt cốt lõi** của framework
> này (cho phép quy định sắc thái cảm xúc TÁCH BIỆT khỏi Format — Format là cấu trúc, Tone là cảm
> xúc), và khi nào KHÔNG dùng CRAFT: giọng điệu đã ngầm hiểu qua Role thì dùng RACE; cần cả phong
> cách + đối tượng + định dạng chi tiết thì dùng CO-STAR. Đã sửa `craft` (§2): đổi field `target` →
> `tone` đúng gốc, thêm field `rules` (tuỳ chọn), cập nhật `template`/`best_for`/`description`,
> viết lại toàn bộ ví dụ (gộp thông tin đối tượng vào `context` vì CRAFT không có field Audience
> riêng, thêm giá trị `tone` thật thể hiện đúng sắc thái cảm xúc tách biệt khỏi ràng buộc độ dài ở
> `format`). **Sửa kèm 1 test bị ảnh hưởng trực tiếp**
> (`PromptGenerationControllerTest::test_store_fails_validation_when_required_field_missing` đổi
> assertion từ `field_values.target` sang `field_values.tone`). 6 framework còn lại (`tag`/`care`/
> `para`/`specs`/`trace`/`react`) vẫn giữ nguyên cấu trúc v1.2, chưa đối chiếu lại.

> **v2.1 (đối chiếu tiếp `promptary.dev/frameworks/tag/` theo yêu cầu người dùng):** cùng field
> tuỳ chọn **Rules** như các framework trước; insight riêng của TAG: đây là framework **"tối giản
> hướng KẾT QUẢ"** — chú trọng ĐẠT ĐƯỢC GÌ (Goal) hơn là trình bày RA SAO, đối lập trực tiếp với
> RTF (chú trọng định dạng). `best_for` cũ ("giao việc ngắn, không cần nhiều ngữ cảnh") quá chung
> chung, không phản ánh đúng use case thực tế của trang: **viết thuyết phục, lời kêu gọi hành động
> (CTA), giải thích ngắn gọn**. Khi nào KHÔNG dùng TAG: định dạng đầu ra là ràng buộc chính → dùng
> RTF. Đã cập nhật `tag` (§2): thêm field `rules` (tuỳ chọn), sửa `label` của `task` (làm rõ là
> "chủ đề/tình huống", không phải "nhiệm vụ" — Action mới là hành động thật sự), cập nhật
> `best_for`/`description` theo đúng use case CTA/thuyết phục, **thay ví dụ** từ "trả lời bình
> luận độc giả" sang ví dụ CTA (nút đăng ký nhận bản tin) đúng tinh thần trang gốc. 5 framework còn
> lại (`care`/`para`/`specs`/`trace`/`react`) vẫn giữ nguyên cấu trúc v1.2, chưa đối chiếu lại.

> **v2.2 (đối chiếu tiếp `promptary.dev/frameworks/care/` theo yêu cầu người dùng):** khác các lần
> trước — config gốc `care` đã khá khớp cấu trúc (4 field cốt lõi đúng, ví dụ đã đúng tinh thần
> "ví dụ mẫu thật"), chỉ thiếu field tuỳ chọn **Rules** và 1 insight cốt lõi: CARE vận hành theo
> nguyên lý **few-shot bằng MINH HOẠ, không phải MÔ TẢ** — Example không phải "ví dụ tham khảo" mà
> là **bằng chứng thật AI cần bắt chước**, hợp nhất khi thứ mong muốn "khó mô tả bằng lời nhưng dễ
> minh hoạ". Trang cũng nêu rõ khi nào KHÔNG dùng CARE: không có ví dụ tham chiếu cụ thể trong tay
> → dùng RACE hoặc CO-STAR (kiểm soát phong cách bằng mô tả, không phải minh hoạ). Đã cập nhật
> `care` (§2): thêm field `rules` (tuỳ chọn), bổ sung ghi chú "ví dụ THẬT" vào `label` của
> `example`, cập nhật `best_for`/`description` theo đúng nguyên lý few-shot trên. 4 framework còn
> lại (`para`/`specs`/`trace`/`react`) vẫn giữ nguyên cấu trúc v1.2, chưa đối chiếu lại.

> **v2.3 (đối chiếu tiếp `promptary.dev/frameworks/para/` và `.../react/` theo yêu cầu người dùng
> — 2 framework cùng lượt):**
> **PARA** — khác biệt lớn nhất phát hiện được kể từ vụ `craft`/`target`: `best_for` cũ mô tả PARA
> như 1 framework "phân tích vấn đề, tìm nguyên nhân" (giống công cụ audit/báo cáo nội bộ), nhưng
> trang gốc định vị PARA là framework **viết NỘI DUNG** (blog kỹ thuật, tài liệu hướng dẫn, case
> study, nội dung giáo dục) cần cả "vì sao" (Problem/Approach) lẫn "làm thế nào" — ví dụ minh hoạ
> cũ ("phân tích tỷ lệ đọc hết bài") đã bị thay hoàn toàn vì đi sai định hướng. Field `application`
> cũng được nâng từ optional → **required**, đúng vai trò trang mô tả: *"shapes vocabulary, depth,
> and example choice more than any other field"* — không thể để tuỳ chọn 1 field quan trọng nhất.
> Khi nào KHÔNG dùng PARA: tone/đối tượng là mối quan tâm chính (nội dung thuyết phục/thương hiệu)
> → dùng CO-STAR.
> **ReAct** — khác các framework trước, 6 field cốt lõi ĐÃ ĐÚNG sẵn từ v1.2 (không phải audit tìm
> lỗi), chỉ cần thêm field `rules` (tuỳ chọn) và bổ sung 2 quy ước định dạng đặc trưng trang nhấn
> mạnh: Reasoning nên bắt đầu bằng `"Thought:"`, Action nên theo khuôn `"Action: [tên công cụ]"` +
> `"Action Input: [tham số]"` — đưa vào cả `label` lẫn ví dụ minh hoạ. Làm rõ thêm ranh giới với
> RISEN: RISEN hợp việc 1 lần có thứ tự bước rõ, ReAct hợp agent THẬT SỰ lặp vòng qua công cụ.
> Đã cập nhật cả 2 (§2) theo đúng các điểm trên. Còn 2 framework chưa đối chiếu: `specs`, `trace`
> — 2 framework này thực ra đến từ `promptquorum.com` ở vòng thu thập dữ liệu ban đầu, KHÔNG có
> trang riêng trên `promptary.dev` để đối chiếu tiếp (đây là lý do người dùng chuyển sang các
> framework khác thay vì tiếp tục `specs`/`trace` — không phải bỏ sót).

> **v2.4 (MỞ RỘNG PHẠM VI — thêm 5 framework mới theo yêu cầu người dùng, đối chiếu
> `promptary.dev/frameworks/{skeleton,tot,plansolve,selfrefine,freeform}/`):** khác các v2.x trước
> (chỉ audit/sửa framework đã có), đây là quyết định **mở rộng danh mục từ 13 → 18** — đã hỏi rõ
> người dùng trước khi thêm (không tự ý mở rộng phạm vi), người dùng chọn "thêm cả 5". Nhóm này
> thuộc dòng "suy luận/chất lượng" (khác 13 framework trên chủ yếu để VIẾT nội dung):
> - **`skeleton`** (Skeleton-of-Thought): Topic/Skeleton Points/Expand Instructions/Output Format/
>   Rules — chia viết nội dung dài (>500 từ) thành 2 giai đoạn (lên khung → viết chi tiết), tránh
>   AI lạc cấu trúc giữa chừng.
> - **`tot`** (Tree-of-Thought): Problem/Number of Branches/Evaluation Criteria/Selection/Rules —
>   AI đưa ra nhiều phương án song song, chấm điểm rồi mới chọn, tránh chốt phương án đầu tiên.
> - **`plansolve`** (Plan-and-Solve): Task/Plan Instructions/Solve Instructions/Output Format/
>   Rules — bắt AI lập kế hoạch trước khi thực thi, tránh làm ẩu/bỏ bước ở việc nhiều bước.
> - **`selfrefine`** (Self-Refine): Role/Task/Criteria/Rules — AI tự viết nháp, tự phê bình theo
>   tiêu chí đo được, rồi mới ra bản hoàn chỉnh; hợp khi chất lượng quan trọng hơn tốc độ.
> - **`freeform`** (Freeform): **CHỈ 1 field** (`text`), không có khái niệm "field cốt lõi" như 17
>   framework còn lại — bản chất là 1 container lưu nguyên văn prompt đã viết sẵn ở nơi khác, không
>   ép vào khuôn nào. Đây là trường hợp kiến trúc khác biệt nhất trong toàn danh mục.
>
> Quy ước `required` giữ nhất quán với 13 framework đã audit trước: chữ cái CỐT LÕI trong tên
> framework → `required: true`; `Rules` (nếu có) → luôn `optional` để đồng bộ trải nghiệm toàn
> thư viện. Đã cập nhật: `config/prompt_framework_studio.php` (thêm 5 mục), §0/§1/§2/§8 (đổi mọi
> chỗ ghi "13 framework" → "18"), `RenderPromptFromFrameworkActionTest::assertCount(13→18)`.
> `FrameworkLibraryControllerTest` không cần sửa (đã lặp qua config động, không hardcode số lượng).

> **v2.5 (đối chiếu `promptquorum.com/frameworks/specs` và `.../trace` theo yêu cầu người dùng —
> nguồn gốc thật của 2 framework này, xác nhận ghi chú ở v2.3):** khác 17 framework kia (nguồn
> `promptary.dev`), `promptquorum.com` **không hề nhắc tới field "Rules"** cho bất kỳ framework
> nào của họ — đây là đặc trưng riêng của promptary.dev, không phải quy ước chung của mọi nguồn.
> **Quyết định:** KHÔNG tự thêm field `rules` vào `specs`/`trace` dù đã thành thói quen làm vậy ở
> 17 framework trước — làm vậy sẽ là bịa thêm dữ liệu không có căn cứ từ nguồn tương ứng, đi ngược
> nguyên tắc "cấu trúc field tham khảo đúng theo nguồn đọc được" (§0). Cả `specs` và `trace` vốn đã
> đúng đủ 5 field cốt lõi từ v1.2, không có field nào sai/thiếu — chỉ enrichment về nhấn mạnh:
> - **`specs`**: Expected Output là "điểm khác biệt cốt lõi" (loại bỏ mơ hồ, ngăn câu trả lời
>   chung chung); nêu rõ khi nào KHÔNG dùng (việc nhanh → APE/RTF; việc sáng tạo/trình tự tự nhiên
>   → RISEN) và so sánh với CO-STAR (SPECS hợp kỹ thuật hơn nhờ đầu ra chặt, CO-STAR hợp giọng
>   điệu/đối tượng hơn).
> - **`trace`**: Example là "field MẠNH NHẤT" của TRACE, đúng tinh thần "show, don't tell" (dạy
>   bằng minh hoạ, không phải mô tả); nêu rõ khi nào KHÔNG dùng (thiếu ví dụ tốt, việc sáng tạo cần
>   độc đáo, câu hỏi thực tế đơn giản → dùng APE).
> Đã cập nhật `label`/`best_for`/`description` của cả 2 (§2), KHÔNG đổi `fields`/`required`/
> `example` vì không có bằng chứng nào cho thấy cấu trúc cũ sai. **Đến đây, toàn bộ 18/18 framework
> trong thư viện đã được đối chiếu với đúng nguồn gốc của từng framework** (13 với `promptary.dev`,
> 2 với `promptquorum.com`, 5 mới thêm cũng từ `promptary.dev`).

---

## 0. Quyết định đã chốt

| Chủ đề | Quyết định | Lý do |
|---|---|---|
| **Có gọi AI Provider trong app không?** | **KHÔNG.** "Sinh prompt" là ghép chuỗi thuần (template có placeholder `{{field_key}}`), không gọi `app/Services/AI/*` | Đúng tinh thần `Modules/ContentOutlines` (`module.json`: "soạn prompt... để dán sang AI ngoài — không gọi AI Provider trong app"). Đây là công cụ giúp NGƯỜI DÙNG tự viết prompt tốt hơn, không phải 1 tính năng AI của hệ thống — không tốn chi phí AI, không có bề mặt tấn công prompt-injection nào cần phòng ở tầng backend (không có gì được gửi tới LLM nội bộ nào) |
| **Danh mục framework (18 loại) lưu ở đâu?** | **Config PHP** (`config/prompt_framework_studio.php`), KHÔNG phải bảng DB, KHÔNG có CRUD admin cho framework | Cùng nguyên tắc `config('banner.placements')` (`spec/Banner_Management_Technical_Specification.md` §0) — danh mục framework thay đổi hiếm, do dev thêm khi cần (thêm 1 framework mới = thêm 1 phần tử config + deploy, không cần màn hình quản trị riêng cho việc hiếm khi xảy ra này) |
| **"Quản lý" (trong yêu cầu) áp dụng cho cái gì?** | Áp dụng cho **prompt người dùng đã sinh ra** (`generated_prompts` — bảng DB, có CRUD/lịch sử/tìm lại), KHÔNG áp dụng cho danh mục framework (đó là config tĩnh, xem trên) | Nhu cầu thật là "lưu lại prompt tôi đã tạo để dùng lại/sửa lại", không phải "tự thêm framework mới qua UI" |
| **Có `organization_id`/`TenantAwareModel` không?** | **KHÔNG** — `GeneratedPrompt` là `Illuminate\Database\Eloquent\Model` trần, không tenant-scope | Cùng nhóm `ContentOutline`, `content_foundations` — công cụ nội bộ đội content/AI dùng chung, không phải dữ liệu khách hàng theo tổ chức |
| **Có soft-delete / activity log không?** | **KHÔNG** cả hai | Cùng lý do `ContentOutline` (§2.1 spec đó): không phải credential, không phải tài sản nghiệp vụ cần audit — xoá là xoá thật, đơn giản hoá |
| **Ai được dùng?** | Permission mới `prompt_framework_studio.use`, seed trực tiếp cho 3 role: `platform_content_editor`, `platform_content_head`, `platform_section_editor` | Giống hệt `ContentOutlinesPermissionSeeder` — cùng nhóm người dùng (đội biên tập/AI content), không phải 1 trong 8 `RoleEnum` core |
| **Có validate nội dung field người dùng nhập (chống prompt injection) không?** | **KHÔNG cần validate/lọc đặc biệt** — nội dung field là dữ liệu của chính người dùng, ghép ra rồi hiển thị lại cho CHÍNH họ đọc/copy, không có bước nào tự động đưa nó cho AI/người khác | Khác hẳn CoreIdeaExtractor/VideoIdeaExtractor (nội dung ở đó được AI trong app tiêu thụ tự động — cần bọc delimiter theo CLAUDE.md); ở đây output chỉ là text hiển thị lại cho tác giả của chính nó |
| **Bài học ("để học") thể hiện ở đâu?** | 1 trang **Thư viện framework** (`index`, đọc từ config, không cần đăng nhập vào tính năng sinh prompt để xem) — mỗi framework có mô tả, khi nào dùng, và **1 ví dụ đã điền sẵn** | Tách riêng khỏi form sinh prompt thật — cho phép đọc để học trước khi thao tác |
| **Ví dụ mẫu trong config lấy từ đâu?** | **Tự biên soạn**, không sao chép nguyên văn từ 3 bài blog đã tham khảo (`promptquorum.com`, `promptary.dev/frameworks` — 2 nguồn duy nhất đọc được nội dung đầy đủ; `talentgroglobal.com` và `gptpromptmaker.com` chỉ lấy được tiêu đề, không lấy được nội dung do trang render bằng JS) | Cấu trúc/tên field của mỗi framework tham khảo đúng theo các nguồn đọc được; câu ví dụ minh hoạ cụ thể là nội dung mới, tránh gán nhầm là trích dẫn |
| **Bản ghi cũ có `framework_key` đã bị gỡ khỏi config (orphaned) thì xử lý sao?** | **Suy biến an toàn (graceful degrade)**: vẫn cho xem `rendered_prompt` (readonly) và xoá, **không** cho sửa/sinh lại (không còn `fields`/`template` để dựng lại form) — xem §5.4 | Dữ liệu quá khứ (`rendered_prompt` đã lưu) không phụ thuộc config tại thời điểm xem lại — chỉ riêng thao tác CẦN đọc lại config (sửa, sinh lại) mới bị chặn có thông báo rõ ràng, thay vì lỗi 500/trang trắng |
| **Alpine lấy dữ liệu 18 framework (fields/template) từ đâu?** | Nhúng thẳng bằng `@js(config('prompt_framework_studio.frameworks'))` vào `x-data` của trang `create`/`edit` — KHÔNG gọi thêm 1 API JSON riêng (đổi từ `@json()` sang `@js()` ở v1.3 — xem changelog, `@json()` không an toàn khi nhúng vào attribute HTML nháy kép) | Dữ liệu tĩnh (config, không đổi theo user/request), nhúng thẳng vào HTML tránh 1 round-trip network không cần thiết — cùng tinh thần "không tạo route mới cho mỗi nhu cầu nhỏ" đã dùng ở nhiều module khác |

---

## 1. Giới thiệu & Mục tiêu

Đội biên tập/AI content hiện không có nơi tra cứu nhanh "framework nào phù hợp cho việc mình đang cần viết prompt", và mỗi lần soạn prompt (dù cho ChatGPT/Claude bên ngoài hay cho các tính năng AI nội bộ như `CoreIdeaExtractor`) đều gõ tự do, dễ thiếu ý (quên nêu đối tượng, quên nêu định dạng đầu ra...).

Module **PromptFrameworkStudio** giải quyết bằng:
1. **Thư viện học** — 18 framework prompt phổ biến, mỗi cái có mô tả, khi nào dùng, và ví dụ điền sẵn.
2. **Form sinh prompt có cấu trúc** — chọn 1 framework, điền lần lượt từng trường đúng theo cấu trúc framework đó, hệ thống ghép thành 1 đoạn prompt hoàn chỉnh, sẵn sàng copy.
3. **Lịch sử/quản lý** — lưu lại các prompt đã sinh, đặt tên, tìm lại, sửa và sinh lại.

**Phi mục tiêu:** không gọi AI để "chấm điểm"/"cải thiện" prompt (v1); không cho tự thêm framework mới qua UI; không tích hợp gửi thẳng prompt đã sinh sang `CoreIdeaExtractor`/`VideoIdeaExtractor` (người dùng tự copy-paste — xem §8).

---

## 2. Danh mục 18 framework (nội dung `config/prompt_framework_studio.php`)

Mỗi framework gồm: `key`, `name`, `description` (mô tả ngắn), `best_for` (khi nào dùng), `fields` (danh sách trường theo đúng thứ tự), `template` (chuỗi ghép có placeholder `{{field_key}}`), `example` (mảng field_key => giá trị mẫu, tự biên soạn).

| Key | Tên đầy đủ | Trường (thứ tự) | Khi nào dùng |
|---|---|---|---|
| `costar` | Context · Objective · Style · Tone · Audience · Response · Rules (tuỳ chọn) | context, objective, style, tone, audience, response_format, rules | Nội dung cần hiểu đối tượng đọc + giọng văn — vượt trội `race` khi chú trọng phong cách, vượt trội `craft` khi nhắm đối tượng cụ thể |
| `risen` | Role · Instructions · Steps · End Goal · Narrowing · Rules (tuỳ chọn) | role, instructions, steps, end_goal, narrowing, rules | Nhiều giai đoạn cần đúng trình tự, kiểm soát chặt cả quy trình lẫn kết quả — việc đơn giản tự đứng một mình thì dùng `race` |
| `craft` | Context · Role · Action · Format · Tone · Rules (tuỳ chọn) | context, role, action, format, tone, rules | Giữ giọng điệu thương hiệu nhất quán — giao tiếp khách hàng, mạng xã hội. Tone ngầm hiểu qua Role thì dùng `race`; cần cả phong cách+đối tượng+định dạng thì dùng `costar` |
| `race` | Role · Action · Context · Expectation · Rules (tuỳ chọn) | role, action, context, expectation, rules | Việc "tự đứng một mình", không cần nhiều bước tuần tự (viết chuyên nghiệp, review, phân tích, tóm tắt) — nhiều bước có thứ tự bắt buộc thì dùng `risen` |
| `rtf` | Role · Task · Format · Rules (tuỳ chọn) | role, task, format, rules | Tác vụ nhanh, rõ ràng, bối cảnh hiển nhiên (chuyển đổi định dạng, dịch thuật, code ngắn) — chưa đầy 30 giây; ra kết quả chung chung thì nâng cấp lên `race` |
| `ape` | Action · Purpose · Expectation · Rules (tuỳ chọn) | action, purpose, expectation, rules | Mục đích phía sau không rõ từ hành động — muốn AI tự phán đoán hợp ý định; cần vai trò/bối cảnh chi tiết hơn thì dùng `race` |
| `tag` | Task · Action · Goal · Rules (tuỳ chọn) | task, action, goal, rules | Viết thuyết phục, CTA, giải thích ngắn — kết quả quan trọng hơn định dạng; định dạng là ràng buộc chính thì dùng `rtf` |
| `care` | Context · Action · Result · Example · Rules (tuỳ chọn) | context, action, result, example, rules | Few-shot bằng ví dụ thật — hợp thứ "khó mô tả bằng lời nhưng dễ minh hoạ"; không có ví dụ tham chiếu thì dùng `race`/`costar` |
| `crit` | Context · Role · Interview · Task · Rules (tuỳ chọn) | context, role, interview_questions, task, rules | Việc mở, sáng tạo/tư vấn chiến lược — chất lượng phụ thuộc chi tiết chưa lường trước; đã biết rõ muốn gì thì `race` nhanh hơn |
| `para` | Problem · Approach · Result · Application · Rules (tuỳ chọn) | problem, approach, result, application, rules | Bài viết kỹ thuật/hướng dẫn/case study — cần cả "vì sao" lẫn "làm thế nào"; tone/đối tượng là mối quan tâm chính thì dùng `costar` |
| `specs` | Situation · Purpose · Expected Output · Context · Style | situation, purpose, expected_output, context, style | Phân tích/tài liệu kỹ thuật phức tạp, đầu ra chặt chẽ; việc nhanh thì dùng `ape`/`rtf`, việc sáng tạo/trình tự tự nhiên thì dùng `risen` |
| `trace` | Task · Request · Action · Context · Example | task, request, action, context, example | Có sẵn ví dụ đầu ra ưng ý — Example là field mạnh nhất; thiếu ví dụ tốt hoặc câu hỏi đơn giản thì dùng `ape` |
| `react` | Role · Instructions · Tools · Reasoning · Action · Observation · Rules (tuỳ chọn) | role, instructions, tools, reasoning, action, observation, rules | Agent AI lặp vòng có công cụ ngoài — việc 1 lần không dùng công cụ thì framework khác đơn giản hơn (kể cả `risen`) |
| `skeleton` | Topic · Skeleton Points · Expand Instructions · Output Format · Rules (tuỳ chọn) | topic, skeleton_points, expand_instructions, output_format, rules | Nội dung dài >500 từ cần mạch lạc xuyên suốt — lên khung trước, viết chi tiết sau; nội dung ngắn thì dùng `rtf`/`craft` |
| `tot` | Problem · Number of Branches · Evaluation Criteria · Selection · Rules (tuỳ chọn) | problem, branches_count, evaluation_criteria, selection, rules | Quyết định chiến lược, nhiều lựa chọn hợp lý — so sánh song song trước khi chọn; việc đơn giản thì dùng `race`/`rtf` |
| `plansolve` | Task · Plan Instructions · Solve Instructions · Output Format · Rules (tuỳ chọn) | task, plan_instructions, solve_instructions, output_format, rules | Nghiên cứu/debug/phân tích nhiều bước — lập kế hoạch trước khi thực thi; việc đơn giản thì dùng `rtf`/`race` |
| `selfrefine` | Role · Task · Criteria · Rules (tuỳ chọn) | role, task, criteria, rules | Chất lượng quan trọng hơn tốc độ, có tiêu chí đo được — AI tự phê bình trước khi ra bản cuối; việc nhanh thì dùng `rtf` |
| `freeform` | Text (không cấu trúc) | text | Lưu nguyên văn 1 prompt đã có sẵn/đã dùng tốt — không viết mới, không ép khuôn |

Đầy đủ cả 18 framework — **must-fix trước khi code**, không được để dev tự viết `fields`/`template`/`example` (dễ lệch chuẩn, chất lượng ví dụ không đồng đều):

```php
// config/prompt_framework_studio.php
return [
    'frameworks' => [

        'costar' => [
            'name' => 'CO-STAR',
            'description' => 'Ghép 6 khối: bối cảnh, mục tiêu, phong cách, giọng điệu, đối tượng, định dạng phản hồi (+ Rules tuỳ chọn) — do GovTech Singapore phát triển, vô địch cuộc thi prompt engineering GPT-4 năm 2023.',
            'best_for' => 'Viết nội dung (blog, marketing, email, mạng xã hội, tài liệu) khi hiểu đối tượng đọc và giọng văn là yếu tố quan trọng nhất. Vượt trội hơn RACE khi cần chú trọng phong cách giao tiếp; vượt trội hơn CRAFT khi nhắm đúng đối tượng cụ thể là ưu tiên hàng đầu.',
            'fields' => [
                ['key' => 'context', 'label' => 'Context (Bối cảnh — tình huống/sản phẩm/chủ đề AI cần hiểu trước khi viết)', 'type' => 'textarea', 'required' => true],
                ['key' => 'objective', 'label' => 'Objective (Mục tiêu — người đọc nên nghĩ/cảm nhận/làm gì sau khi đọc xong)', 'type' => 'textarea', 'required' => true],
                ['key' => 'style', 'label' => 'Style (Phong cách viết: phân tích, kể chuyện, thuyết phục, hướng dẫn... có thể tham chiếu 1 tác giả/ấn phẩm cụ thể)', 'type' => 'text', 'required' => false],
                ['key' => 'tone', 'label' => 'Tone (Giọng điệu — sắc thái cảm xúc: chuyên nghiệp, thân thiện, khẩn cấp, đồng cảm... chi tiết hơn Style)', 'type' => 'text', 'required' => false],
                ['key' => 'audience', 'label' => 'Audience (Đối tượng đọc — càng cụ thể càng tốt, tránh chung chung. VD: không viết "lập trình viên" mà "kỹ sư backend cấp cao, hoài nghi công cụ mới")', 'type' => 'text', 'required' => true],
                ['key' => 'response_format', 'label' => 'Response (Định dạng đầu ra: độ dài, cấu trúc, tiêu đề, gạch đầu dòng, số từ, ngôn ngữ)', 'type' => 'text', 'required' => false],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc áp dụng chung cho toàn bộ yêu cầu — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Context: {{context}}\nObjective: {{objective}}\nStyle: {{style}}\nTone: {{tone}}\nAudience: {{audience}}\nResponse format: {{response_format}}\nRules: {{rules}}",
            'example' => [
                'context' => 'Chúng tôi vận hành 1 trang tin gia đình, sắp ra bài về quản lý tài chính cho cha mẹ có con nhỏ.',
                'objective' => 'Viết đoạn mở bài (100-150 từ) thu hút phụ huynh đọc tiếp.',
                'style' => 'Gần gũi, dễ hiểu, tránh thuật ngữ tài chính phức tạp.',
                'tone' => 'Ấm áp, đồng cảm, không giáo điều.',
                'audience' => 'Cha mẹ 28-40 tuổi, thu nhập trung bình, đang lo lắng về khoản tiết kiệm học phí cho con — chưa am hiểu đầu tư tài chính, không phải nhóm đọc báo kinh tế thường xuyên.',
                'response_format' => 'Đoạn văn liền mạch, không gạch đầu dòng.',
                'rules' => 'Không dùng thuật ngữ tài chính chuyên sâu (trái phiếu, danh mục đầu tư...); không đưa lời khuyên đầu tư cụ thể, chỉ mang tính tham khảo.',
            ],
        ],

        'risen' => [
            'name' => 'RISEN',
            'description' => 'Role · Instructions · Steps · End Goal · Narrowing (+ Rules tuỳ chọn) — giao việc nhiều bước, kiểm soát chặt cả quy trình lẫn kết quả.',
            'best_for' => 'Việc nhiều giai đoạn cần đúng trình tự, cần kiểm soát chặt cả cách làm lẫn kết quả (kế hoạch, quy trình nhiều bước). Việc đơn giản, tự đứng một mình thì dùng RACE cho nhanh.',
            'fields' => [
                ['key' => 'role', 'label' => 'Role (Vai trò AI đóng)', 'type' => 'text', 'required' => true],
                ['key' => 'instructions', 'label' => 'Instructions (Yêu cầu tổng quát, KHÔNG cần nêu cách làm cụ thể — để dành cho Steps)', 'type' => 'textarea', 'required' => true],
                ['key' => 'steps', 'label' => 'Steps (Trình tự các bước — nên đánh số 3-7 bước, mỗi bước 1 hành động rõ ràng)', 'type' => 'textarea', 'required' => true],
                ['key' => 'end_goal', 'label' => 'End Goal (Kết quả cuối cùng thế nào là xong)', 'type' => 'text', 'required' => true],
                ['key' => 'narrowing', 'label' => 'Narrowing (Phạm vi, loại trừ, quy tắc định dạng — nêu rõ AI KHÔNG được làm gì)', 'type' => 'textarea', 'required' => false],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc áp dụng chung cho toàn bộ yêu cầu — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Role: {{role}}\nInstructions: {{instructions}}\nSteps:\n{{steps}}\nEnd goal: {{end_goal}}\nNarrowing/constraints: {{narrowing}}\nRules: {{rules}}",
            'example' => [
                'role' => 'Bạn là chuyên gia lập kế hoạch nội dung cho website gia đình.',
                'instructions' => 'Lập lịch đăng bài 4 tuần cho chuyên mục "Nuôi dạy con", mỗi tuần 3 bài.',
                'steps' => "1) Liệt kê 12 chủ đề theo độ tuổi con (0-3, 3-6, 6-12 tuổi)\n2) Gắn mỗi chủ đề với 1 ngày đăng cụ thể\n3) Gợi ý tiêu đề chuẩn SEO cho từng bài\n4) Kiểm tra không trùng chủ đề đã đăng trong 2 tháng gần nhất",
                'end_goal' => 'Một bảng lịch đăng bài đầy đủ, sẵn sàng giao cho đội viết — không cần chỉnh sửa thêm.',
                'narrowing' => 'Không đề xuất chủ đề trùng 2 tháng gần nhất; không dùng ngôn ngữ hàn lâm; không vượt quá 12 chủ đề.',
                'rules' => 'Giữ văn phong gần gũi, phù hợp phụ huynh Việt Nam; ưu tiên chủ đề lồng ghép được kỹ năng số an toàn cho trẻ.',
            ],
        ],

        'craft' => [
            'name' => 'CRAFT',
            'description' => 'Context · Role · Action · Format · Tone (+ Rules tuỳ chọn) — Tone (giọng điệu cảm xúc) là điểm khác biệt cốt lõi, tách bạch với Format (cấu trúc).',
            'best_for' => 'Nội dung cần giữ giọng điệu thương hiệu nhất quán — giao tiếp với khách hàng, mạng xã hội, nội dung có bản sắc riêng. Giọng điệu đã ngầm hiểu qua vai trò thì dùng RACE; cần thêm cả phong cách + đối tượng + định dạng chi tiết thì dùng CO-STAR.',
            'fields' => [
                ['key' => 'context', 'label' => 'Context (Bối cảnh — sản phẩm/đối tượng/tình huống AI cần hiểu)', 'type' => 'textarea', 'required' => true],
                ['key' => 'role', 'label' => 'Role (Vai trò chuyên môn AI đóng)', 'type' => 'text', 'required' => true],
                ['key' => 'action', 'label' => 'Action (Việc cụ thể cần làm)', 'type' => 'textarea', 'required' => true],
                ['key' => 'format', 'label' => 'Format (Định dạng CẤU TRÚC: độ dài, số đoạn, gạch đầu dòng, số từ)', 'type' => 'text', 'required' => false],
                ['key' => 'tone', 'label' => 'Tone (Giọng điệu CẢM XÚC — khác Format: ấm áp, đáng tin cậy, vui vẻ, khẩn cấp...)', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc áp dụng chung — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Context: {{context}}\nRole: {{role}}\nAction: {{action}}\nFormat: {{format}}\nTone: {{tone}}\nRules: {{rules}}",
            'example' => [
                'context' => 'Trang đích quảng bá khoá học kỹ năng số miễn phí, hướng tới phụ huynh đang lo lắng về việc con dùng mạng xã hội an toàn.',
                'role' => 'Bạn là copywriter chuyên viết landing page chuyển đổi cao.',
                'action' => 'Viết 1 headline và 1 sub-headline thu hút phụ huynh đăng ký.',
                'format' => 'Headline tối đa 12 từ, sub-headline tối đa 25 từ.',
                'tone' => 'Ấm áp, đáng tin cậy — trấn an chứ không doạ dẫm; tránh khiến phụ huynh cảm thấy mình đang thất bại trong việc bảo vệ con.',
                'rules' => 'Không dùng số liệu giật gân về rủi ro mạng xã hội; không nhắc tên nền tảng mạng xã hội cụ thể.',
            ],
        ],

        'race' => [
            'name' => 'RACE',
            'description' => 'Role · Action · Context · Expectation (+ Rules tuỳ chọn) — yêu cầu nhanh gọn, có vai trò rõ.',
            'best_for' => 'Việc "tự đứng một mình" (không cần nhiều bước tuần tự): viết chuyên nghiệp, review, phân tích, tóm tắt. Cần nhiều bước có thứ tự bắt buộc thì dùng RISEN thay vì RACE.',
            'fields' => [
                ['key' => 'role', 'label' => 'Role (Vai trò AI đóng)', 'type' => 'text', 'required' => true],
                ['key' => 'action', 'label' => 'Action (Việc cần làm, dùng động từ rõ nghĩa: Viết/Phân tích/Rà soát/Tóm tắt)', 'type' => 'textarea', 'required' => true],
                ['key' => 'context', 'label' => 'Context (Bối cảnh — hay bị bỏ qua nhất nhưng ảnh hưởng kết quả nhiều nhất: đối tượng đọc, ràng buộc, cái gì đã thử mà chưa hiệu quả)', 'type' => 'textarea', 'required' => false],
                ['key' => 'expectation', 'label' => 'Expectation (Kết quả thế nào là đạt: định dạng, độ dài, giọng văn, phải có/phải tránh gì)', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc áp dụng chung cho toàn bộ yêu cầu — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Role: {{role}}\nAction: {{action}}\nContext: {{context}}\nExpectation: {{expectation}}\nRules: {{rules}}",
            'example' => [
                'role' => 'Bạn là biên tập viên SEO có 5 năm kinh nghiệm viết nội dung sức khoẻ - giáo dục gia đình.',
                'action' => 'Viết lại tiêu đề bài viết sau cho chuẩn SEO.',
                'context' => "Tiêu đề gốc: \"5 mẹo giúp con học tốt hơn\". Từ khoá mục tiêu: \"phương pháp học tập cho trẻ tiểu học\". 2 tiêu đề tương tự đã đăng trước đây có tỷ lệ click thấp vì quá chung chung, không nêu lợi ích cụ thể.",
                'expectation' => '3 phương án tiêu đề, mỗi tiêu đề dưới 60 ký tự, chứa từ khoá chính, có ít nhất 1 con số hoặc lợi ích cụ thể.',
                'rules' => 'Không dùng dấu chấm than hoặc từ ngữ giật tít quá đà; giữ giọng văn đáng tin cậy, phù hợp trang thông tin gia đình.',
            ],
        ],

        'rtf' => [
            'name' => 'RTF',
            'description' => 'Role · Task · Format (+ Rules tuỳ chọn) — cực ngắn gọn, đủ 3 khối cốt lõi, điền xong trong chưa đầy 30 giây.',
            'best_for' => 'Tác vụ nhanh, rõ ràng, bối cảnh đã hiển nhiên: chuyển đổi định dạng, dịch thuật, chỉnh sửa đoạn văn bản/code ngắn. Nếu kết quả ra chung chung, thiếu chiều sâu — nâng cấp lên RACE (thêm Context) thay vì cố ép RTF.',
            'fields' => [
                ['key' => 'role', 'label' => 'Role (Vai trò AI đóng — viết gọn trong 1 câu)', 'type' => 'text', 'required' => true],
                ['key' => 'task', 'label' => 'Task (Nhiệm vụ — càng cụ thể càng tốt, tránh mơ hồ)', 'type' => 'textarea', 'required' => true],
                ['key' => 'format', 'label' => 'Format (Định dạng đầu ra chính xác — có thể yêu cầu "chỉ trả về kết quả, không giải thích thêm")', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc áp dụng chung — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Role: {{role}}\nTask: {{task}}\nFormat: {{format}}\nRules: {{rules}}",
            'example' => [
                'role' => 'Bạn là trợ lý biên tập, thành thạo cả tiếng Việt và tiếng Anh.',
                'task' => 'Dịch đoạn mô tả sản phẩm sau sang tiếng Anh, giữ nguyên tên riêng và số liệu.',
                'format' => 'Chỉ trả về bản dịch, không giải thích, không thêm ghi chú.',
                'rules' => 'Giữ nguyên định dạng đoạn văn gốc, không thêm gạch đầu dòng.',
            ],
        ],

        'ape' => [
            'name' => 'APE',
            'description' => 'Action · Purpose · Expectation (+ Rules tuỳ chọn) — Purpose là điểm khác biệt giữa APE và RTF.',
            'best_for' => 'Việc mà MỤC ĐÍCH phía sau không rõ ràng chỉ từ hành động — muốn AI tự phán đoán, ra quyết định hợp ý định thay vì làm máy móc theo nghĩa đen. Cần vai trò/bối cảnh chi tiết hơn thì dùng RACE (có thêm Role + Context).',
            'fields' => [
                ['key' => 'action', 'label' => 'Action (Việc cần làm)', 'type' => 'textarea', 'required' => true],
                ['key' => 'purpose', 'label' => 'Purpose (Vì sao cần làm — điểm khác biệt với RTF, giúp AI phán đoán đúng ý khi việc còn mơ hồ)', 'type' => 'text', 'required' => true],
                ['key' => 'expectation', 'label' => 'Expectation (Kỳ vọng kết quả: định dạng, độ dài, giọng điệu, ràng buộc)', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc áp dụng chung — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Action: {{action}}\nPurpose: {{purpose}}\nExpectation: {{expectation}}\nRules: {{rules}}",
            'example' => [
                'action' => 'Viết lại các thông báo lỗi kỹ thuật trên form đăng ký khoá học thành tiếng Việt dễ hiểu.',
                'purpose' => 'Người dùng chủ yếu là phụ huynh không rành công nghệ; mục tiêu là giảm số lượt gọi tổng đài hỏi vì không hiểu lỗi.',
                'expectation' => 'Mỗi thông báo 1 câu, dưới 20 từ, không dùng thuật ngữ kỹ thuật.',
                'rules' => 'Giữ giọng điệu thân thiện, không đổ lỗi cho người dùng.',
            ],
        ],

        'tag' => [
            'name' => 'TAG',
            'description' => 'Task · Action · Goal (+ Rules tuỳ chọn) — tối giản, hướng tới KẾT QUẢ đạt được hơn là định dạng trình bày (khác RTF).',
            'best_for' => 'Viết thuyết phục, lời kêu gọi hành động (CTA), giải thích ngắn gọn — khi ĐẠT ĐƯỢC MỤC TIÊU quan trọng hơn cấu trúc trình bày. Định dạng đầu ra là ràng buộc chính thì dùng RTF thay vì TAG.',
            'fields' => [
                ['key' => 'task', 'label' => 'Task (Chủ đề/tình huống AI cần xử lý)', 'type' => 'text', 'required' => true],
                ['key' => 'action', 'label' => 'Action (Hành động cụ thể cần thực hiện trên chủ đề đó)', 'type' => 'textarea', 'required' => true],
                ['key' => 'goal', 'label' => 'Goal (Kết quả mong muốn — tiêu chí để biết đã thành công)', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc áp dụng chung — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Task: {{task}}\nAction: {{action}}\nGoal: {{goal}}\nRules: {{rules}}",
            'example' => [
                'task' => 'Nút kêu gọi hành động (CTA) trên trang đăng ký nhận bản tin gia đình.',
                'action' => 'Viết lại chữ trên nút bấm để tăng tỷ lệ đăng ký, giảm cảm giác ngại/do dự.',
                'goal' => 'Người đọc bấm đăng ký ngay, không thấy đây là 1 cam kết nặng nề hay tốn thời gian.',
                'rules' => 'Chữ trên nút tối đa 4 từ; không dùng từ "Đăng ký" trần trụi, ưu tiên động từ gợi lợi ích.',
            ],
        ],

        'care' => [
            'name' => 'CARE',
            'description' => 'Context · Action · Result · Example (+ Rules tuỳ chọn) — few-shot: dạy AI bằng VÍ DỤ THẬT thay vì mô tả bằng lời.',
            'best_for' => 'Khi có sẵn 1 ví dụ/mẫu cụ thể thể hiện đúng phong cách/định dạng mong muốn — nhất là thứ "khó mô tả bằng lời nhưng dễ minh hoạ". Không có ví dụ tham chiếu cụ thể thì dùng RACE hoặc CO-STAR (mô tả bằng lời) thay vì CARE.',
            'fields' => [
                ['key' => 'context', 'label' => 'Context (Bối cảnh — lĩnh vực, đối tượng, tình huống, ràng buộc)', 'type' => 'textarea', 'required' => true],
                ['key' => 'action', 'label' => 'Action (Việc cần làm)', 'type' => 'textarea', 'required' => true],
                ['key' => 'result', 'label' => 'Result (Mô tả đầu ra mong muốn: nội dung, độ dài, cấu trúc)', 'type' => 'text', 'required' => true],
                ['key' => 'example', 'label' => 'Example (Ví dụ mẫu THẬT — dán 1 kết quả tốt đã có để AI bắt chước đúng phong cách)', 'type' => 'textarea', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc áp dụng chung — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Context: {{context}}\nAction: {{action}}\nDesired result: {{result}}\nExample to follow:\n{{example}}\nRules: {{rules}}",
            'example' => [
                'context' => 'Chúng tôi muốn viết caption Facebook quảng bá bài viết mới.',
                'action' => 'Viết 1 caption theo đúng phong cách của ví dụ bên dưới.',
                'result' => 'Caption dưới 200 ký tự, có 1 câu hỏi mở ở cuối để tăng tương tác.',
                'example' => 'Con lười ăn rau? Đừng lo, đây là 3 cách biến rau thành món khoái khẩu của bé! 👉 Đọc ngay: [link]. Mẹ đã thử cách nào trong 3 cách này chưa?',
                'rules' => 'Không dùng quá 2 biểu tượng cảm xúc trong 1 caption; giữ giọng gần gũi, không quảng cáo lộ liễu.',
            ],
        ],

        'crit' => [
            'name' => 'CRIT',
            'description' => 'Context · Role · Interview · Task (+ Rules tuỳ chọn) — đặc trưng là AI tự hỏi lại làm rõ trước khi làm, hợp khi bạn chưa biết hết thông tin cần thiết.',
            'best_for' => 'Việc mở, mang tính sáng tạo/tư vấn chiến lược — khi chất lượng phụ thuộc nhiều vào chi tiết bạn chưa lường trước được (xây chiến lược thương hiệu, tư vấn định hướng...). Đã biết chính xác mình muốn gì thì RACE nhanh hơn — CRIT cần nhiều lượt qua lại, không hợp việc tự động hoá 1 lần.',
            'fields' => [
                ['key' => 'context', 'label' => 'Context (Tình huống/bối cảnh/vấn đề cần giải quyết)', 'type' => 'textarea', 'required' => true],
                ['key' => 'role', 'label' => 'Role (Vai trò AI đóng — quyết định loại câu hỏi AI sẽ đặt ra)', 'type' => 'text', 'required' => true],
                ['key' => 'interview_questions', 'label' => 'Interview (Số lượng câu hỏi AI cần hỏi lại — nêu khoảng 3-5 câu + hướng cần hỏi, KHÔNG cần viết sẵn câu hỏi cụ thể)', 'type' => 'textarea', 'required' => true],
                ['key' => 'task', 'label' => 'Task (Việc AI cần tạo ra SAU KHI đã hỏi xong)', 'type' => 'textarea', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc áp dụng chung — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Context: {{context}}\nRole: {{role}}\nBefore starting, ask me these questions:\n{{interview_questions}}\nThen: {{task}}\nRules: {{rules}}",
            'example' => [
                'context' => 'Chúng tôi muốn viết 1 bài so sánh 2 loại bảo hiểm nhân thọ cho gia đình trẻ.',
                'role' => 'Bạn là chuyên gia tư vấn tài chính gia đình.',
                'interview_questions' => 'Hỏi tôi khoảng 3-4 câu trước khi bắt đầu, tập trung vào: (1) 2 sản phẩm cụ thể cần so sánh, (2) đối tượng đọc ưu tiên độ tuổi nào, (3) mức độ trung lập mong muốn của bài viết.',
                'task' => 'Sau khi có câu trả lời, viết dàn ý bài so sánh khoảng 800 từ.',
                'rules' => 'Không đề xuất tên sản phẩm cụ thể nào nếu tôi chưa cung cấp; giữ giọng văn trung lập trong toàn bộ câu hỏi.',
            ],
        ],

        'para' => [
            'name' => 'PARA',
            'description' => 'Problem · Approach · Result · Application (+ Rules tuỳ chọn) — viết nội dung giáo dục/kỹ thuật cần cả "vì sao" lẫn "làm thế nào".',
            'best_for' => 'Bài viết kỹ thuật, tài liệu hướng dẫn, case study, nội dung giáo dục — khi người đọc cần hiểu cả nguyên nhân lẫn cách làm. Tone/đối tượng là mối quan tâm chính (nội dung thuyết phục/thương hiệu) thì dùng CO-STAR thay vì PARA.',
            'fields' => [
                ['key' => 'problem', 'label' => 'Problem (Vấn đề/nỗi đau thực tế bài viết giải quyết — neo nội dung vào tình huống cụ thể)', 'type' => 'textarea', 'required' => true],
                ['key' => 'approach', 'label' => 'Approach (Phương pháp/kỹ thuật/giải pháp cụ thể cần giải thích hoặc dạy)', 'type' => 'textarea', 'required' => true],
                ['key' => 'result', 'label' => 'Result (Yêu cầu đầu ra: độ dài, định dạng, các phần bắt buộc, có cần ví dụ minh hoạ không)', 'type' => 'text', 'required' => true],
                ['key' => 'application', 'label' => 'Application (Đối tượng đọc + bối cảnh sử dụng — field ảnh hưởng nhiều nhất đến từ ngữ, độ sâu, cách chọn ví dụ)', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc riêng cho bài viết này — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Problem: {{problem}}\nApproach: {{approach}}\nDesired result: {{result}}\nApplication: {{application}}\nRules: {{rules}}",
            'example' => [
                'problem' => 'Nhiều phụ huynh không biết cách thiết lập giới hạn thời gian dùng điện thoại cho con mà không gây xung đột.',
                'approach' => 'Giải thích phương pháp "thoả thuận cùng con" thay vì áp đặt 1 chiều, kèm các bước thực hiện cụ thể.',
                'result' => 'Bài viết khoảng 600 từ, có 1 đoạn hội thoại mẫu minh hoạ giữa cha mẹ và con.',
                'application' => 'Phụ huynh có con 8-14 tuổi, đã từng thử áp đặt giờ giấc nhưng thất bại, đang tìm cách khác.',
                'rules' => 'Không phán xét cách nuôi dạy con trước đây của người đọc; tránh ngôn ngữ mang tính chỉ trích.',
            ],
        ],

        'specs' => [
            'name' => 'SPECS',
            'description' => 'Situation · Purpose · Expected Output · Context · Style — Expected Output là điểm khác biệt cốt lõi, loại bỏ sự mơ hồ trong yêu cầu.',
            'best_for' => 'Phân tích/tài liệu kỹ thuật phức tạp, yêu cầu đầu ra chặt chẽ, dự án cần nhiều bối cảnh. Việc nhanh/thường lệ thì dùng APE hoặc RTF; việc sáng tạo cần linh hoạt hoặc có trình tự tự nhiên thì dùng RISEN. So với CO-STAR (chú trọng giọng điệu/đối tượng), SPECS hợp việc kỹ thuật hơn nhờ định nghĩa đầu ra chặt chẽ.',
            'fields' => [
                ['key' => 'situation', 'label' => 'Situation (Tình huống/thách thức hiện tại cần giải quyết)', 'type' => 'textarea', 'required' => true],
                ['key' => 'purpose', 'label' => 'Purpose (Vì sao việc này quan trọng — mục tiêu phía sau)', 'type' => 'textarea', 'required' => true],
                ['key' => 'expected_output', 'label' => 'Expected Output (Đầu ra CHÍNH XÁC cần có — điểm khác biệt cốt lõi của SPECS, loại bỏ mơ hồ, tránh câu trả lời chung chung)', 'type' => 'text', 'required' => true],
                ['key' => 'context', 'label' => 'Context (Ràng buộc/ngữ cảnh thêm)', 'type' => 'textarea', 'required' => false],
                ['key' => 'style', 'label' => 'Style (Văn phong)', 'type' => 'text', 'required' => false],
            ],
            'template' => "Situation: {{situation}}\nPurpose: {{purpose}}\nExpected output: {{expected_output}}\nContext: {{context}}\nStyle: {{style}}",
            'example' => [
                'situation' => 'Website đang chuyển đổi hệ thống quản lý bài viết sang module mới.',
                'purpose' => 'Cần tài liệu hướng dẫn nội bộ cho đội biên tập làm quen hệ thống mới.',
                'expected_output' => '1 tài liệu Markdown có mục lục, ảnh minh hoạ placeholder.',
                'context' => 'Đội biên tập không rành kỹ thuật, đã quen giao diện cũ trong 2 năm.',
                'style' => 'Ngôn ngữ đơn giản, từng bước, tránh thuật ngữ lập trình.',
            ],
        ],

        'trace' => [
            'name' => 'TRACE',
            'description' => "Task · Request · Action · Context · Example — Example là field MẠNH NHẤT của TRACE, dạy AI bằng minh hoạ thay vì mô tả (\"show, don't tell\").",
            'best_for' => 'Có sẵn 1 ví dụ đầu ra ưng ý muốn AI học theo — nhân bản phong cách/định dạng cụ thể, sinh dữ liệu có cấu trúc theo khuôn mẫu thấy được. Thiếu ví dụ tốt, việc sáng tạo cần độc đáo (ví dụ dễ giới hạn sự mới mẻ), hoặc câu hỏi thực tế đơn giản thì dùng APE thay vì TRACE.',
            'fields' => [
                ['key' => 'task', 'label' => 'Task (Loại/nhóm công việc tổng quát)', 'type' => 'text', 'required' => true],
                ['key' => 'request', 'label' => 'Request (Yêu cầu cụ thể, chính xác cần gì)', 'type' => 'textarea', 'required' => true],
                ['key' => 'action', 'label' => 'Action (Hành động cụ thể AI cần thực hiện để đáp ứng yêu cầu)', 'type' => 'text', 'required' => false],
                ['key' => 'context', 'label' => 'Context (Bối cảnh, ràng buộc liên quan)', 'type' => 'textarea', 'required' => false],
                ['key' => 'example', 'label' => 'Example (Ví dụ mẫu — field MẠNH NHẤT của TRACE, dạy AI chính xác điều bạn muốn tốt hơn mọi mô tả bằng lời)', 'type' => 'textarea', 'required' => true],
            ],
            'template' => "Task: {{task}}\nRequest: {{request}}\nAction: {{action}}\nContext: {{context}}\nExample:\n{{example}}",
            'example' => [
                'task' => 'Viết mô tả ngắn (meta description) cho bài viết.',
                'request' => 'Viết 1 meta description 150-160 ký tự cho bài viết chủ đề "dạy con quản lý tiền tiêu vặt".',
                'action' => 'Nêu bật lợi ích cụ thể, có lời kêu gọi đọc tiếp.',
                'context' => 'Bài viết nhắm phụ huynh có con 8-12 tuổi.',
                'example' => 'Dạy con quản lý tiền tiêu vặt từ 8 tuổi: 5 bước đơn giản giúp con hình thành thói quen tiết kiệm suốt đời. Xem ngay!',
            ],
        ],

        'react' => [
            'name' => 'ReAct',
            'description' => 'Role · Instructions · Tools · Reasoning · Action · Observation (+ Rules tuỳ chọn) — dành cho agent AI lặp vòng nhiều bước, KHÔNG phải yêu cầu 1 lần.',
            'best_for' => 'Xây dựng AI agent có dùng công cụ ngoài (tìm kiếm web, chạy code, gọi API), cần suy luận nhiều bước + gọi công cụ tuần tự. Việc 1 lần không cần công cụ thì dùng framework khác đơn giản hơn — kể cả RISEN (RISEN hợp việc có thứ tự bước rõ nhưng không lặp vòng thật qua công cụ; ReAct hợp agent thực sự tương tác vòng lặp).',
            'fields' => [
                ['key' => 'role', 'label' => 'Role (Vai trò AI đóng)', 'type' => 'text', 'required' => true],
                ['key' => 'instructions', 'label' => 'Instructions (Mục tiêu/nhiệm vụ tổng thể agent cần hoàn thành)', 'type' => 'textarea', 'required' => true],
                ['key' => 'tools', 'label' => 'Tools (Danh sách công cụ khả dụng + mô tả từng công cụ)', 'type' => 'text', 'required' => true],
                ['key' => 'reasoning', 'label' => 'Reasoning (Yêu cầu suy nghĩ thành lời trước mỗi hành động — vd bắt đầu bằng "Thought:")', 'type' => 'textarea', 'required' => false],
                ['key' => 'action', 'label' => 'Action (Định dạng gọi công cụ — vd "Action: [tên công cụ]" rồi "Action Input: [tham số]")', 'type' => 'textarea', 'required' => true],
                ['key' => 'observation', 'label' => 'Observation (Cách xử lý kết quả công cụ trả về trước khi suy nghĩ bước tiếp theo)', 'type' => 'text', 'required' => false],
                ['key' => 'rules', 'label' => 'Rules (Điều kiện dừng, định dạng câu trả lời cuối, ràng buộc an toàn — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Role: {{role}}\nInstructions: {{instructions}}\nTools available: {{tools}}\nReasoning: {{reasoning}}\nAction: {{action}}\nObservation: {{observation}}\nRules: {{rules}}",
            'example' => [
                'role' => 'Bạn là trợ lý nghiên cứu nội dung, có thể dùng công cụ tìm kiếm.',
                'instructions' => 'Tìm 3 nguồn số liệu uy tín về tỷ lệ trẻ em Việt Nam dùng mạng xã hội trước 13 tuổi.',
                'tools' => 'Công cụ tìm kiếm web, công cụ đọc PDF.',
                'reasoning' => 'Trước mỗi lần tìm kiếm, bắt đầu bằng "Thought:" rồi nêu rõ đang tìm gì và vì sao.',
                'action' => 'Ghi rõ "Action: tìm kiếm web" kèm "Action Input: [từ khoá]"; sau khi có kết quả, trích dẫn nguồn kèm link.',
                'observation' => 'Sau mỗi kết quả, đánh giá độ tin cậy nguồn trước khi dùng tiếp.',
                'rules' => 'Dừng lại và báo cáo nếu tìm 3 lần liên tiếp không ra nguồn đáng tin cậy; câu trả lời cuối phải liệt kê đủ 3 nguồn kèm link.',
            ],
        ],

        // v2.4 — 5 framework mới thêm theo yêu cầu người dùng, đối chiếu promptary.dev. Đây là
        // nhóm "suy luận/chất lượng" khác hẳn 13 framework trên (chủ yếu viết nội dung) — quy ước
        // required giữ nhất quán: các chữ cái CỐT LÕI của tên framework đều required, riêng
        // Rules (nếu có) luôn optional để đồng bộ trải nghiệm với 13 framework kia.
        'skeleton' => [
            'name' => 'Skeleton-of-Thought',
            'description' => 'Topic · Skeleton Points · Expand Instructions · Output Format · Rules — chia viết nội dung dài thành 2 giai đoạn: lên khung trước, viết chi tiết sau, tránh AI lạc cấu trúc giữa chừng.',
            'best_for' => 'Nội dung dài trên 500 từ cần mạch lạc xuyên suốt (bài hướng dẫn, tài liệu kỹ thuật, blog nhiều phần). Nội dung ngắn thì dùng RTF hoặc CRAFT — lên khung trước không đáng công.',
            'fields' => [
                ['key' => 'topic', 'label' => 'Topic (Chủ đề, đối tượng đọc, mục đích — để định hướng khung sườn)', 'type' => 'text', 'required' => true],
                ['key' => 'skeleton_points', 'label' => 'Skeleton Points (Các phần/mục chính — tự liệt kê hoặc để AI đề xuất trước khi viết chi tiết)', 'type' => 'textarea', 'required' => true],
                ['key' => 'expand_instructions', 'label' => 'Expand Instructions (Mỗi phần viết bao nhiêu đoạn, có ví dụ không, độ sâu ra sao)', 'type' => 'textarea', 'required' => true],
                ['key' => 'output_format', 'label' => 'Output Format (Định dạng cuối: Markdown/heading, số từ, cách trình bày nếu có code)', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Ràng buộc đảm bảo đi đúng 2 giai đoạn — vd "luôn hiện khung trước khi viết chi tiết" — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Topic: {{topic}}\nSkeleton points:\n{{skeleton_points}}\nExpand instructions: {{expand_instructions}}\nOutput format: {{output_format}}\nRules: {{rules}}",
            'example' => [
                'topic' => 'Bài hướng dẫn "Chuẩn bị đồ dùng học tập cho con vào lớp 1", hướng tới phụ huynh lần đầu có con đi học.',
                'skeleton_points' => "1) Vì sao cần chuẩn bị sớm\n2) Danh sách đồ dùng bắt buộc\n3) Đồ dùng nên có nhưng không bắt buộc\n4) Cách rèn con tự sắp xếp đồ dùng\n5) Sai lầm thường gặp khi mua sắm",
                'expand_instructions' => 'Mỗi phần viết 2-3 đoạn, có ít nhất 1 ví dụ cụ thể; phần 2 và 3 trình bày dạng danh sách.',
                'output_format' => 'Markdown, dùng heading H2 cho từng phần, khoảng 800 từ.',
                'rules' => 'Luôn hiện khung 5 phần trước, chờ xác nhận rồi mới viết chi tiết từng phần; không bỏ qua phần nào.',
            ],
        ],

        'tot' => [
            'name' => 'Tree-of-Thought',
            'description' => 'Problem · Number of Branches · Evaluation Criteria · Selection · Rules — AI đưa ra NHIỀU phương án song song, chấm điểm rồi mới chọn, tránh chốt phương án đầu tiên quá sớm.',
            'best_for' => 'Quyết định chiến lược, việc quan trọng có nhiều lựa chọn hợp lý (chọn hướng nội dung, chọn cách triển khai). Việc đơn giản/hướng đã rõ thì dùng RACE hoặc RTF.',
            'fields' => [
                ['key' => 'problem', 'label' => 'Problem (Vấn đề/quyết định cần giải quyết, kèm ràng buộc loại trừ sẵn nếu có)', 'type' => 'textarea', 'required' => true],
                ['key' => 'branches_count', 'label' => 'Number of Branches (Số phương án cần so sánh — thường 3 là vừa đủ đa dạng, không quá rối)', 'type' => 'text', 'required' => true],
                ['key' => 'evaluation_criteria', 'label' => 'Evaluation Criteria (3-4 tiêu chí chấm điểm từng phương án, định nghĩa rõ từng tiêu chí)', 'type' => 'textarea', 'required' => true],
                ['key' => 'selection', 'label' => 'Selection (Cách chọn phương án thắng: bảng điểm, trọng số, hay khuyến nghị kèm lý do)', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Đảm bảo các phương án THẬT SỰ khác nhau; hiện bảng điểm trước khi khuyến nghị — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Problem: {{problem}}\nNumber of branches: {{branches_count}}\nEvaluation criteria: {{evaluation_criteria}}\nSelection: {{selection}}\nRules: {{rules}}",
            'example' => [
                'problem' => 'Chọn hướng phát triển chuyên mục mới cho website gia đình trong quý tới, ngân sách nội dung có hạn.',
                'branches_count' => '3 phương án',
                'evaluation_criteria' => 'Tiềm năng traffic (dựa xu hướng tìm kiếm), chi phí sản xuất nội dung, mức độ cạnh tranh với đối thủ.',
                'selection' => 'Lập bảng điểm 3 phương án theo 3 tiêu chí, sau đó đưa ra khuyến nghị kèm lý do.',
                'rules' => '3 phương án phải thực sự khác hướng nhau (không phải biến thể của cùng 1 ý); hiện bảng điểm trước khi khuyến nghị.',
            ],
        ],

        'plansolve' => [
            'name' => 'Plan-and-Solve',
            'description' => 'Task · Plan Instructions · Solve Instructions · Output Format · Rules — bắt AI LẬP KẾ HOẠCH trước, rồi mới thực thi đúng theo kế hoạch đó, tránh làm ẩu/bỏ bước.',
            'best_for' => 'Nghiên cứu, phân tích cạnh tranh, debug, báo cáo nhiều bước — việc cần thu thập/cân nhắc nhiều thông tin theo trình tự. Việc đơn giản thì dùng RTF hoặc RACE.',
            'fields' => [
                ['key' => 'task', 'label' => 'Task (Mục tiêu cụ thể cần hoàn thành — càng cụ thể, kế hoạch AI lập càng sát)', 'type' => 'textarea', 'required' => true],
                ['key' => 'plan_instructions', 'label' => 'Plan Instructions (Yêu cầu AI liệt kê từng bước TRƯỚC khi bắt tay vào làm)', 'type' => 'textarea', 'required' => true],
                ['key' => 'solve_instructions', 'label' => 'Solve Instructions (Yêu cầu AI thực hiện đúng theo kế hoạch đã lập, có thể hiện quá trình làm)', 'type' => 'textarea', 'required' => true],
                ['key' => 'output_format', 'label' => 'Output Format (Cấu trúc/định dạng của kết quả cuối cùng)', 'type' => 'text', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Đảm bảo hiện kế hoạch trước khi bắt đầu, không bỏ qua bước nào đã lập — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Task: {{task}}\nPlan instructions: {{plan_instructions}}\nSolve instructions: {{solve_instructions}}\nOutput format: {{output_format}}\nRules: {{rules}}",
            'example' => [
                'task' => 'Phân tích 4 website gia đình đối thủ đang làm nội dung nuôi dạy con, tìm khoảng trống nội dung có thể khai thác.',
                'plan_instructions' => 'Trước tiên, liệt kê từng bước sẽ làm để phân tích (thu thập chuyên mục, đếm tần suất đăng bài, xác định chủ đề còn thiếu...).',
                'solve_instructions' => 'Sau đó thực hiện lần lượt từng bước đã liệt kê, cho thấy quá trình làm ở mỗi bước.',
                'output_format' => 'Bảng so sánh 4 đối thủ + đoạn khuyến nghị 3 câu về hướng khai thác.',
                'rules' => 'Hiện kế hoạch đầy đủ trước khi bắt đầu phân tích; không bỏ qua bước nào đã lập ra.',
            ],
        ],

        'selfrefine' => [
            'name' => 'Self-Refine',
            'description' => 'Role · Task · Criteria · Rules — AI tự đóng vai vừa là người viết vừa là người phê bình: viết nháp, tự chấm theo tiêu chí, rồi mới đưa bản hoàn chỉnh.',
            'best_for' => 'Chất lượng quan trọng hơn tốc độ, có tiêu chí đo được rõ ràng (tài liệu kỹ thuật, email chuyên nghiệp, code cần đúng). Việc cần nhanh/đơn giản thì dùng RTF — thêm bước tự phê bình sẽ chậm hơn.',
            'fields' => [
                ['key' => 'role', 'label' => 'Role (Vai trò chuyên môn AI đóng — đặt kỳ vọng chất lượng ngay từ đầu)', 'type' => 'text', 'required' => true],
                ['key' => 'task', 'label' => 'Task (Việc cần tạo ra, kèm phạm vi/ràng buộc)', 'type' => 'textarea', 'required' => true],
                ['key' => 'criteria', 'label' => 'Criteria (3-5 tiêu chí chất lượng CỤ THỂ, ĐO ĐƯỢC — không dùng tiêu chí mơ hồ như "hay", "rõ ràng")', 'type' => 'textarea', 'required' => true],
                ['key' => 'rules', 'label' => 'Rules (Yêu cầu hiện đủ 3 bước: Bản nháp → Tự phê bình → Bản hoàn chỉnh — tuỳ chọn)', 'type' => 'textarea', 'required' => false],
            ],
            'template' => "Role: {{role}}\nTask: {{task}}\nCriteria:\n{{criteria}}\nRules: {{rules}}",
            'example' => [
                'role' => 'Bạn là biên tập viên kỳ cựu chuyên viết email chăm sóc khách hàng.',
                'task' => 'Viết email nhắc phụ huynh hoàn tất hồ sơ đăng ký khoá học đang bỏ dở.',
                'criteria' => "Dưới 120 từ; có đúng 1 lời kêu gọi hành động (CTA) duy nhất; nhắc đúng tên khoá học đã đăng ký dở; giọng văn nhắc nhở nhẹ nhàng, không tạo áp lực; không dùng từ \"vui lòng\" quá 1 lần.",
                'rules' => 'Hiện đủ 3 phần: Bản nháp → Tự phê bình theo từng tiêu chí trên → Bản hoàn chỉnh đã sửa hết các điểm bị chê.',
            ],
        ],

        // Freeform khác biệt kiến trúc so với 17 framework còn lại: CHỈ 1 field, không cấu trúc,
        // đúng bản chất "container lưu nguyên văn prompt đã có sẵn" — KHÔNG áp field 'required'
        // theo nghĩa "field cốt lõi của framework" như các mục trên vì bản thân Freeform không có
        // khái niệm "cốt lõi", chỉ có 1 field duy nhất và nó bắt buộc vì để trống thì vô nghĩa.
        'freeform' => [
            'name' => 'Freeform',
            'description' => 'Không cấu trúc — dùng để LƯU LẠI nguyên văn 1 prompt đã viết sẵn/đã dùng tốt ở nơi khác, không ép vào khuôn nào.',
            'best_for' => 'Di chuyển prompt đã có sẵn (từ Notion, file text, ChatGPT...) vào đây để quản lý/tìm lại — không viết prompt mới từ đầu. Viết mới từ đầu hoặc muốn tinh chỉnh có hệ thống thì dùng RACE/RISEN.',
            'fields' => [
                ['key' => 'text', 'label' => 'Nội dung prompt (dán nguyên văn — không cần theo cấu trúc nào)', 'type' => 'textarea', 'required' => true],
            ],
            'template' => '{{text}}',
            'example' => [
                'text' => "Bạn là trợ lý chăm sóc khách hàng của nền tảng khoá học trực tuyến cho gia đình. Nhiệm vụ của bạn là trả lời câu hỏi về cách sử dụng nền tảng, cách đăng ký khoá học, và các vấn đề thanh toán.\n\nQuy tắc:\n- Luôn trả lời bằng tiếng Việt, giọng thân thiện.\n- Không được bịa ra tính năng mà nền tảng chưa có.\n- Nếu không chắc câu trả lời, hướng dẫn người dùng liên hệ tổng đài thay vì đoán.\n- Giữ câu trả lời dưới 100 từ trừ khi người dùng yêu cầu chi tiết hơn.",
            ],
        ],

    ],
];
```

`type` mỗi field chỉ nhận `text` (input 1 dòng) hoặc `textarea` — không cần loại field phức tạp hơn cho v1. Field không `required` mà người dùng để trống sẽ được thay bằng chuỗi rỗng khi ghép (§4.1) — chấp nhận dòng nhãn không có nội dung theo sau (vd `Style: `) ở v1, không tự động lược bỏ dòng trống (xem §7 — để v1.1 kế tiếp nếu cần polish).

---

## 3. Kiến trúc dữ liệu

### 3.1 Migration

```php
Schema::create('generated_prompts', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique(); // route key — cùng quy ước PostCategory/ContentOutline

    $table->string('framework_key', 30); // khớp key trong config('prompt_framework_studio.frameworks') — validate ở FormRequest (§5.1), KHÔNG FK vì nguồn là config chứ không phải bảng
    $table->string('label', 150); // tên người dùng tự đặt để nhận diện trong danh sách quản lý (vd "Mở bài blog tài chính gia đình")
    $table->json('field_values'); // {field_key: giá trị} — dùng để tải lại form khi sửa/sinh lại
    $table->longText('rendered_prompt'); // kết quả ghép cuối cùng — ghi đè khi "Sinh lại" (không versioning, cùng quyết định ContentOutline.generated_prompt)

    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();

    $table->index('framework_key');
    $table->index('created_at');
    $table->index('label'); // trang quản lý (§4.3) tìm/sắp theo tên người dùng tự đặt — tăng dần theo số lượng prompt đã lưu
});
```

Không soft-delete, không activity log (xem §0).

### 3.2 Model

```php
namespace Modules\PromptFrameworkStudio\Models;

class GeneratedPrompt extends Model
{
    protected $table = 'generated_prompts';

    protected $fillable = [
        'uuid', 'framework_key', 'label', 'field_values', 'rendered_prompt',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'field_values' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
```

---

## 4. Cấu trúc module (Features/)

```
Modules/PromptFrameworkStudio/
├── app/
│   ├── Features/
│   │   ├── FrameworkLibrary/
│   │   │   └── Http/FrameworkLibraryController.php      — GET /dashboard/prompt-studio/library (đọc config, render danh sách + ví dụ)
│   │   └── PromptGeneration/
│   │       ├── Actions/
│   │       │   ├── RenderPromptFromFrameworkAction.php  — nhận framework_key + field_values, trả rendered_prompt (strtr template, thiếu field = chuỗi rỗng)
│   │       │   ├── CreateGeneratedPromptAction.php       — gọi Action trên rồi lưu bản ghi mới
│   │       │   └── RegenerateGeneratedPromptAction.php   — cập nhật field_values + ghi đè rendered_prompt của bản ghi có sẵn
│   │       └── Http/
│   │           ├── PromptGenerationController.php        — index (form chọn framework), create (form field động), store, edit, update, destroy
│   │           └── Requests/StoreGeneratedPromptRequest.php — validate framework_key ∈ config keys; field_values theo đúng field khai báo của framework đó (required fields bắt buộc)
│   ├── Models/GeneratedPrompt.php
│   └── Providers/{PromptFrameworkStudioServiceProvider,RouteServiceProvider}.php
├── config/prompt_framework_studio.php
├── database/
│   ├── migrations/..._create_generated_prompts_table.php
│   └── seeders/PromptFrameworkStudioPermissionSeeder.php
├── resources/views/
│   ├── library/index.blade.php     — thư viện học (đọc config)
│   ├── prompts/index.blade.php     — danh sách đã sinh (quản lý) — Tabulator, cùng pattern các module khác
│   ├── prompts/create.blade.php    — bước 1: chọn framework (grid 13 thẻ) → bước 2: form field động (Alpine, hiện field theo framework chọn)
│   └── prompts/edit.blade.php
├── routes/web.php
└── module.json
```

### 4.1 `RenderPromptFromFrameworkAction` — logic ghép chuỗi

```php
class RenderPromptFromFrameworkAction
{
    use AsAction;

    public function handle(string $frameworkKey, array $fieldValues): string
    {
        $framework = config("prompt_framework_studio.frameworks.{$frameworkKey}");
        abort_if(! $framework, 422, 'Framework không tồn tại.');

        $replacements = [];
        foreach ($framework['fields'] as $field) {
            $replacements['{{'.$field['key'].'}}'] = trim((string) ($fieldValues[$field['key']] ?? ''));
        }

        return strtr($framework['template'], $replacements);
    }
}
```

Không dùng Blade template engine cho bước này (không cần logic điều kiện trong template, `strtr` đủ và tránh rủi ro injection cú pháp Blade từ dữ liệu người dùng).

`abort_if(! $framework, 422, ...)` bên trong `RenderPromptFromFrameworkAction::handle()` (đã có ở trên) là nơi kiểm tra **duy nhất và bắt buộc** cho việc framework có tồn tại hay không — `CreateGeneratedPromptAction` và `RegenerateGeneratedPromptAction` đều gọi xuyên qua `RenderPromptFromFrameworkAction` để lấy `rendered_prompt` (không tự ghép chuỗi riêng), nên cả 2 **tự động thừa hưởng** guard này mà không cần lặp lại kiểm tra ở từng Action. Đây là lý do §5.4 khẳng định `update`/`RegenerateGeneratedPromptAction` "từ chối với lỗi 422" kể cả khi bị gọi thẳng, không đi qua `edit()`: bản thân `RegenerateGeneratedPromptAction::handle()` luôn gọi `RenderPromptFromFrameworkAction::run($prompt->framework_key, $newFieldValues)` trước khi lưu, nên orphaned framework tự nhiên bị chặn ở đúng 1 chỗ, không phải nhớ thêm `if` riêng ở Controller lẫn Action (defense-in-depth mà không trùng lặp logic).

### 4.2 Truyền dữ liệu framework xuống Alpine (`resources/views/prompts/create.blade.php` và `edit.blade.php`)

```blade
<div
    x-data="promptGenerator(@json(config('prompt_framework_studio.frameworks')))"
    x-init="init()"
>
    {{-- Bước 1: lưới 13 thẻ chọn framework --}}
    <template x-for="(fw, key) in frameworks" :key="key">
        <button type="button" @click="select(key)" x-text="fw.name"></button>
    </template>

    {{-- Bước 2: form field động theo framework đã chọn --}}
    <template x-if="selectedKey">
        <div>
            <template x-for="field in frameworks[selectedKey].fields" :key="field.key">
                <div>
                    <label x-text="field.label"></label>
                    <textarea x-show="field.type === 'textarea'" x-model="values[field.key]"></textarea>
                    <input x-show="field.type === 'text'" x-model="values[field.key]" type="text" />
                </div>
            </template>
        </div>
    </template>

    {{-- input ẩn để submit form Laravel bình thường (không AJAX) --}}
    <input type="hidden" name="framework_key" :value="selectedKey">
    <template x-for="field in (frameworks[selectedKey]?.fields ?? [])" :key="field.key">
        <input type="hidden" :name="`field_values[${field.key}]`" :value="values[field.key]">
    </template>
</div>
```

```js
// resources/assets/js/prompt-framework-studio.js
// initialKey/initialValues: null ở trang create; ở trang edit truyền
// promptGenerator(@json($frameworks), @json($prompt->framework_key), @json($prompt->field_values))
function promptGenerator(frameworks, initialKey = null, initialValues = null) {
    return {
        frameworks,
        selectedKey: initialKey,
        values: initialValues ?? {},
        init() {
            // Trang edit: framework đã biết trước (không đổi được — §5.3), chỉ cần đảm bảo mọi field
            // của framework đó đều có key trong `values` (kể cả field optional chưa từng điền trước
            // đây) để x-model không bị "undefined" khi field mới được thêm vào framework sau này.
            if (this.selectedKey && this.frameworks[this.selectedKey]) {
                for (const field of this.frameworks[this.selectedKey].fields) {
                    if (!(field.key in this.values)) this.values[field.key] = '';
                }
            }
        },
        select(key) {
            this.selectedKey = key;
            this.values = Object.fromEntries(frameworks[key].fields.map(f => [f.key, '']));
        },
    };
}
```

Ở `edit.blade.php`, KHÔNG render lưới chọn framework (bước 1) — chỉ render thẳng bước 2 (form field) vì `selectedKey` đã cố định từ `initialKey`, đúng quyết định "framework không đổi được sau khi tạo" (§5.3). Trang `edit` chỉ dựng được khi `config("prompt_framework_studio.frameworks.{$prompt->framework_key}")` tồn tại — trường hợp orphaned rẽ sang view read-only khác hẳn (§5.4), không tái sử dụng `x-data="promptGenerator(...)"` này.

`@json()` nhúng thẳng toàn bộ config vào HTML lúc render (server-side, không phải AJAX) — dữ liệu tĩnh, không đổi theo request nên không cần endpoint JSON riêng (§0). Form submit theo kiểu POST thường (không AJAX) qua các input ẩn được Alpine đồng bộ — giữ `StoreGeneratedPromptRequest` (§5.1) xử lý y hệt 1 form HTML thường, không cần thêm code nhận JSON riêng.

### 4.3 Cột Tabulator — `prompts/index.blade.php` (danh sách quản lý)

| Cột | Nguồn dữ liệu | Ghi chú |
|---|---|---|
| Tên prompt | `label` | Link tới `edit` (hoặc `show` nếu orphaned — §5.4) |
| Framework | `config("prompt_framework_studio.frameworks.{$framework_key}.name")` | Nếu key không còn trong config: hiện `framework_key` thô kèm badge "Đã gỡ" (§5.4) |
| Người tạo | `createdBy.name` | |
| Cập nhật lần cuối | `updated_at` (format `d/m/Y H:i`) | Sort mặc định giảm dần |
| Thao tác | Xem / Sửa / Xoá | Nút "Sửa" ẩn nếu orphaned (§5.4) |

Endpoint JSON cho Tabulator: `GET backend/api/prompt-studio/prompts` (cùng pattern `N8nLogApiController`/`backend/api/n8n/logs/*`), phân trang server-side, filter theo `framework_key`/`label` (tìm kiếm chuỗi con qua `label`, tận dụng `index('label')` ở §3.1).

---

## 5. Validate & luồng nghiệp vụ

### 5.1 `StoreGeneratedPromptRequest`

```php
public function rules(): array
{
    $frameworkKey = $this->input('framework_key');
    $framework = config("prompt_framework_studio.frameworks.{$frameworkKey}");

    $rules = [
        'framework_key' => ['required', 'string', Rule::in(array_keys(config('prompt_framework_studio.frameworks')))],
        'label' => ['required', 'string', 'max:150'],
        'field_values' => ['required', 'array'],
    ];

    if ($framework) {
        foreach ($framework['fields'] as $field) {
            $rules["field_values.{$field['key']}"] = $field['required']
                ? ['required', 'string', 'max:5000']
                : ['nullable', 'string', 'max:5000'];
        }
    }

    return $rules;
}
```

### 5.2 Luồng tạo mới

1. Người dùng vào `/dashboard/prompt-studio/prompts/create` → chọn 1 trong 13 thẻ framework (mỗi thẻ hiện `name` + `description` ngắn, đọc từ config).
2. Alpine.js hiện form field động theo đúng `fields` của framework chọn (không reload trang).
3. Submit → `StoreGeneratedPromptRequest` validate → `CreateGeneratedPromptAction` gọi `RenderPromptFromFrameworkAction` → lưu `GeneratedPrompt` với `rendered_prompt` đã ghép, `created_by = auth()->id()`.
4. Trang kết quả hiện `rendered_prompt` trong `<textarea readonly>` + nút "Copy" (JS `navigator.clipboard`).

### 5.3 Luồng sửa/sinh lại

`edit` tải lại `field_values` cũ vào đúng form của `framework_key` đã lưu (framework không đổi được sau khi tạo — muốn dùng framework khác thì tạo bản ghi mới) → `RegenerateGeneratedPromptAction` ghi đè `rendered_prompt` + `updated_by`.

### 5.4 Framework bị gỡ khỏi config (orphaned `framework_key`)

Config là nguồn duy nhất cho `fields`/`template` (§0) — nếu dev xoá 1 key khỏi `config/prompt_framework_studio.php` sau khi đã có `GeneratedPrompt` dùng key đó, các bản ghi cũ **không được để vỡ trang** (lỗi 500/trang trắng khi bấm "Sửa"). Quyết định:

- `PromptGenerationController::edit()` kiểm tra `config("prompt_framework_studio.frameworks.{$prompt->framework_key}")` **trước** khi render form. Nếu `null` (orphaned):
  - Chuyển hướng sang view **read-only** (`prompts/show.blade.php`, không phải `edit.blade.php`) — hiện `label`, `rendered_prompt` (readonly, vẫn copy được), và 1 banner cảnh báo: *"Framework '{{ $prompt->framework_key }}' đã bị gỡ khỏi hệ thống — không thể sửa hoặc sinh lại. Bạn vẫn có thể xem và sao chép nội dung đã lưu, hoặc xoá bản ghi này."*
  - Route `update`/`RegenerateGeneratedPromptAction` **từ chối** với lỗi 422 rõ ràng nếu vẫn có request gọi tới (phòng trường hợp gọi thẳng API, không chỉ qua UI) — không âm thầm bỏ qua.
  - `destroy` (xoá) **vẫn hoạt động bình thường** — orphaned không cản việc dọn dữ liệu cũ.
- Danh sách quản lý (§4.3) đánh dấu các dòng orphaned bằng badge "Đã gỡ" cạnh tên framework, giúp nhận diện ngay từ danh sách mà không cần mở từng bản ghi.
- **Không** tự động xoá bản ghi khi framework bị gỡ khỏi config — quyết định xoá hay giữ lại là của người dùng, hệ thống không tự ý mất dữ liệu.

---

## 6. RBAC

```php
// database/seeders/PromptFrameworkStudioPermissionSeeder.php — cùng khuôn ContentOutlinesPermissionSeeder
private const PERMISSIONS = ['prompt_framework_studio.use'];
private const ROLES_WITH_ACCESS = [
    'platform_content_editor',
    'platform_content_head',
    'platform_section_editor',
];
```

`super-admin` luôn full quyền qua `syncPermissions(Permission::all())` (cùng mẫu mọi seeder khác). Route group `middleware(['auth', 'permission:prompt_framework_studio.use'])`, `prefix('dashboard/prompt-studio')`.

Trang **Thư viện** (`library/index`) có thể mở rộng quyền xem sau (vd cho mọi role đăng nhập) nếu cần dùng làm tài liệu đào tạo chung — v1 dùng chung 1 permission cho cả thư viện lẫn form sinh prompt để đơn giản.

---

## 7. Ngoài phạm vi (v1)

- Không có nút "AI gợi ý framework phù hợp nhất" — cần mô tả mục tiêu bằng ngôn ngữ tự nhiên rồi AI chấm, đây là tính năng AI thật (Layer 2), để lại cho spec version sau nếu có nhu cầu.
- Không tích hợp 1-click "gửi prompt này sang CoreIdeaExtractor/VideoIdeaExtractor" — copy-paste thủ công ở v1.
- Không cho người dùng tự định nghĩa framework mới qua UI (chỉ dev sửa config).
- Không có chấm điểm/so sánh chất lượng giữa các phiên bản prompt đã sinh.
- **Live preview khi gõ** (xem `rendered_prompt` cập nhật real-time trong lúc điền form, trước khi submit) — để bản kế tiếp; v1 chỉ hiện kết quả sau khi submit.
- ~~Nút "Dùng framework này" ngay tại trang Thư viện~~ — **đã làm ở v1.3** (xem changelog v1.3), không còn là việc để sau.
- **Tự động lược bỏ dòng nhãn trống** khi field optional không điền (vd ẩn hẳn dòng `Style: ` nếu để trống thay vì hiện nhãn không có nội dung) — để bản kế tiếp, v1 chấp nhận hiện nguyên nhãn trống (§2).
- **Lưu ý dài hạn (không phải việc phải làm ở v1):** giả định nền tảng "chỉ chính người tạo nhìn thấy `rendered_prompt`" (§0, dòng chống prompt-injection) sẽ **không còn đúng** nếu sau này thêm tính năng chia sẻ prompt giữa các thành viên hoặc gửi thẳng sang 1 module AI khác — lúc đó phải xét lại việc bọc delimiter theo quy ước CLAUDE.md, vì nội dung field lúc đó có thể bị 1 người dùng khác/1 AI khác tiêu thụ.

---

## 8. Testing

- `RenderPromptFromFrameworkActionTest`: mỗi 1 trong 18 framework — field đủ, field thiếu (thay bằng rỗng), framework_key không tồn tại (abort 422).
- `PromptGenerationControllerTest`:
  - Tạo mới; validate required field theo đúng framework (kể cả framework có field optional như `costar.style`).
  - Field optional để trống → `rendered_prompt` vẫn sinh ra, dòng nhãn tương ứng để trống (không lỗi).
  - Sửa + sinh lại (`RegenerateGeneratedPromptAction`) ghi đè đúng `rendered_prompt` và `updated_by`, **`uuid` và `framework_key` không đổi**, các field không được gửi lại trong request giữ nguyên giá trị cũ (không bị xoá về rỗng).
  - Truy cập `edit`/`update` một bản ghi có `framework_key` không còn trong config → chuyển hướng view read-only (§5.4), `update` trả 422 nếu gọi trực tiếp.
  - `destroy`: xoá thành công kể cả khi orphaned; phân quyền — role không có `prompt_framework_studio.use` bị chặn ở mọi action (index/create/store/edit/update/destroy).
- `FrameworkLibraryControllerTest`: render đủ 18 framework từ config, không lỗi khi thiếu `example`.
