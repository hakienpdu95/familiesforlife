# Module Bảng tính Minh hoạ Lương hưu BHXH Tự nguyện (Pension Calculator)
**Đặc tả Kỹ thuật Chi tiết — Sẵn sàng Triển khai (có mục cần xác minh pháp lý trước khi code)**

**Phiên bản:** 1.4 — module đã TRIỂN KHAI (xem `Modules/PensionCalculator`). **§14 mục 1 (tỷ lệ hưởng lương hưu) — mục chặn go-live DUY NHẤT — đã RESOLVED**: có văn bản Luật Bảo hiểm xã hội 2024 thật (`spec/bhxh/41-2024-qh15.pdf`), đối chiếu Điều 66/98/99/102/104 xác nhận đúng công thức, đã seed `pension_rate_brackets`. Toàn bộ tính năng ước tính lương hưu (Bước 5, §7.1, §15.6) nay ra số thật thay vì "chưa xác minh". Chỉ còn mục 5 (một phần — mức trợ cấp hưu trí xã hội cụ thể, Phase 2) và mục 7 (reference_level 2.530.000, không liên quan Luật 2024) — xem §14.
**Ngày:** 2026-08-04
**Framework:** Laravel 13 (PHP 8.4) + NWIDART Modules + Lorisleiva Actions
**Module mới:** `Modules/PensionCalculator`
**Nguồn tài liệu đầu vào:** `spec/bhxh/bhxh.md` (bài tổng hợp mức đóng BHXH tự nguyện, thuvienphapluat.vn), `spec/bhxh/159-cp.signed.pdf` (Nghị định 159/2025/NĐ-CP ngày 25/06/2025 — quy định chi tiết và hướng dẫn thi hành một số điều của Luật Bảo hiểm xã hội về bảo hiểm xã hội tự nguyện, hiệu lực từ 01/07/2025), `spec/bhxh/BHXHVN_340-BHXH-CSXH_03022026.pdf` (Công văn 340/BHXH-CSXH ngày 03/02/2026 của BHXH Việt Nam — hệ số điều chỉnh tiền lương/thu nhập tháng đã đóng BHXH, cột "thu nhập" dùng cho BHXH tự nguyện theo khoản 2 Điều 10 Nghị định 159/2025/NĐ-CP, xem §6.6/§14 mục 6), `spec/bhxh/41-2024-qh15.pdf` (Luật Bảo hiểm xã hội 2024, số 41/2024/QH15, Công báo số 987+988 ngày 25-8-2024 — nguồn Điều 66/98/99/102/104/21/22, xem §14), `spec/bhxh/1.png`–`3.png` (ví dụ minh hoạ cách tính mức bình quân tiền lương đóng BHXH có áp hệ số trượt giá — nguồn cũ, xem cảnh báo ở §2.3), `spec/bhxh/minhhoa.png` (ảnh minh hoạ marketing của 1 đại lý BHXH — ĐÃ đối chiếu và loại bỏ vì tỷ lệ ngụ ý vượt trần 75% hợp pháp, xem §15.6)
**Module liên quan:** `Modules/Post` (nhúng/link từ bài viết), `Modules/RealEstate` (tiền lệ kiến trúc — công cụ tính khoản vay `anlandLoanCalculator`, xem §0)

> **v1.4 — đã seed tỷ lệ hưởng lương hưu thật (RESOLVED mục chặn go-live):** xem chi tiết đối chiếu Điều 66/98/99/102/104 ở §14 (khối cập nhật đầu mục). Tóm tắt: Điều 66 (bắt buộc) và Điều 99 (tự nguyện) dùng CHUNG 1 công thức 45%+2%/năm (nữ 15 năm/nam 20 năm), trần 75%, cộng nhánh phụ nam 15-19 năm dùng 40%+1%/năm — đã seed 3 dòng `pension_rate_brackets` dùng chung cho mọi nhánh eligibility. Đã kiểm chứng cross-check với chính văn bản Luật (nữ 20 năm=55%, nam 20 năm=45%, trần chạm ở nữ 30/nam 35 năm) và test toàn bộ luồng Bước 5 + 2 bảng minh hoạ (§7.1/§15.6) ra số thật.

---

## 0. Quyết định đã chốt

| Chủ đề | Khảo sát hiện trạng | Quyết định spec này | Lý do |
|---|---|---|---|
| **Kiến trúc tổng thể** | Không có module "calculator/estimator/widget" nào trong `Modules/*` (đã grep toàn bộ). Tiền lệ gần nhất: `anlandLoanCalculator` (`resources/js/anland.js:39-73`) — công cụ ước tính khoản vay mua nhà, tính thuần phía client, không gửi dữ liệu tài chính người dùng lên server, gắn ở 1 trang public riêng (`Modules/RealEstate/routes/web.php:36`), có disclaimer "chỉ mang tính tham khảo" | Module mới **`Modules/PensionCalculator`**, độc lập — KHÔNG nhét vào `Modules/Post` (xem hàng dưới) hay `Modules/RealEstate` (khác domain) | Đúng quy mô 1 module 1 domain nghiệp vụ như các module hiện có; tránh phình `Post`/`RealEstate` sang 1 lĩnh vực (bảo hiểm xã hội) không liên quan |
| **Có nhúng vào block bài viết (`post_content_blocks`) không?** | `ContentBlockType` (`Modules/Post/app/Enums/ContentBlockType.php`) chỉ có 5 case cố định (`text/product/faq/citation/howto`), mỗi case có cột FK riêng trên `PostContentBlock` — **không có cơ chế block/shortcode tổng quát**, thêm 1 loại block mới bắt buộc sửa core `Post` (enum + migration + nhánh render trong `ArticleContentRenderer`) | **KHÔNG** thêm `ContentBlockType::Calculator` ở bản v1 — công cụ sống ở 1 trang public riêng của module mới, bài viết chỉ **link tới** trang đó (giống cách bài viết có thể link sang `/anland`) | Sửa core `Post` để phục vụ 1 tính năng ngoài phạm vi ban đầu của `Post` là thay đổi xâm lấn, rủi ro cao hơn lợi ích ở giai đoạn minh hoạ/MVP; nếu sau này cần nhúng thật (không chỉ link), làm 1 case `Calculator` mới là việc của 1 spec riêng, tách khỏi spec này |
| **Kiểu giao diện (Blade+Alpine SPA vs Vue/React build)** | 2 nhóm tồn tại song song: nhóm build Vue/React có `package.json` riêng (Assessment, Auth, Customer, Lead, Survey, WorkflowAutomation) dùng cho CRUD/dashboard phức tạp; nhóm Blade+Alpine 1 file, không build step (`CoreIdeaExtractor`, `VideoIdeaExtractor`, `anlandLoanCalculator`) dùng cho 1 công cụ tương tác gọn | Trang tính **public** dùng Blade+Alpine SPA 1 file (`x-data`, không build step) — đúng khuôn `CoreIdeaExtractor::index.blade.php`. Trang **admin quản lý tham số** (§9) có thể dùng Blade thường + Alpine cho form/bảng (không cần build Vue riêng — số bản ghi nhỏ, thao tác CRUD đơn giản) | Công cụ public là 1 form nhập liệu + tính toán hiển thị ngay, không có xác thực/CRUD phức tạp — không có lý do kéo thêm 1 bundle Vue/React |
| **Dữ liệu tài chính cá nhân của người dùng (dòng thời gian thu nhập nhiều năm) có lưu server không?** | `anlandLoanCalculator` KHÔNG lưu gì — tính 100% phía client, disclaimer rõ "tính toán ngay trên trình duyệt của bạn" | **KHÔNG lưu** dòng thời gian thu nhập/tuổi/giới tính người dùng nhập — toàn bộ phép tính chạy phía client (Alpine, JS thuần); server **chỉ cấp dữ liệu tham chiếu công khai** (tham số pháp lý theo giai đoạn hiệu lực, bảng hệ số trượt giá, bảng tỷ lệ hưởng) qua 1 API GET công khai, không nhận input cá nhân nào | Thu nhập là dữ liệu nhạy cảm; đây là công cụ MINH HOẠ công khai (không đăng nhập), không có lý do nghiệp vụ nào cần giữ lại lịch sử nhập của người dùng ẩn danh — giữ đúng triết lý riêng tư đã có ở `anlandLoanCalculator` |
| **Tham số pháp lý (mức chuẩn nghèo, mức tham chiếu, hệ số trượt giá, tỷ lệ hưởng lương hưu...) — hard-code hay config DB?** | N/A (module mới) | **DB, có hiệu lực theo mốc thời gian** (`effective_from`), KHÔNG hard-code trong PHP/JS, KHÔNG dùng file `config/*.php` tĩnh | Các tham số này thay đổi theo thời gian do văn bản pháp luật mới (mức lương cơ sở/mức chuẩn nghèo đổi theo Nghị định khác; hệ số trượt giá do BHXH Việt Nam công bố **hàng năm** qua Thông tư — Nghị định 159 Điều 18.4 xác nhận rõ điều này). Nếu tính minh hoạ cho 1 mốc thời gian trong quá khứ (VD người dùng có thu nhập đóng từ 2018) mà tham số bị ghi đè bởi giá trị hiện hành thì kết quả sai — cần tra đúng tham số **của đúng giai đoạn** đó, giống cách `hệ số trượt giá năm 2017` trong ảnh ví dụ chỉ áp dụng cho hồ sơ **giải quyết năm 2017**, không phải năm hiện tại |
| **Ai quản lý tham số** | N/A | Permission mới `PENSION_CALCULATOR_MANAGE` (`App\Enums\PermissionEnum`), đăng ký qua **Lớp A** (`config/permissions.php`), gán cho **`RoleEnum::System_Admin`** (+ CEO chỉ xem, không sửa — xem §9.3) | Đây là dữ liệu **tuân thủ pháp luật** ảnh hưởng tới độ chính xác của 1 công cụ công khai toàn platform (không phải nội dung biên tập theo category như CoreIdeaExtractor/VideoIdeaExtractor dùng Lớp B) — đúng bản chất "cấu hình hệ thống" hơn là "nội dung", nên xếp cùng nhóm các permission hệ thống lõi (Lớp A, 8 role RoleEnum) thay vì tự seed role Spatie riêng |
| **Tỷ lệ hưởng lương hưu hằng tháng (%) — số liệu cụ thể** | Nghị định 159 (Điều 11.3) chỉ nói "tỷ lệ hưởng lương hưu hằng tháng nhân với mức bình quân...", **không kèm công thức %** — công thức % nằm ở Luật Bảo hiểm xã hội 2024 (khả năng cao là Điều 66, do Điều 11 không trích số Điều cụ thể cho phần này), **văn bản Luật không nằm trong 2 tài liệu người dùng cung cấp** | ~~Schema có sẵn chỗ chứa (`pension_rate_brackets`, §6.4) nhưng KHÔNG seed cho tới khi đối chiếu trực tiếp~~ **(RESOLVED v1.4)** — đã có `spec/bhxh/41-2024-qh15.pdf`, đối chiếu đúng Điều 66/99, đã seed. Xem §14 mục 1 | Đúng nguyên tắc đã áp dụng cho Quyết định 1189/QĐ-TTg (`spec/CoreIdeaExtractor.md` §12.10, v1.16) — không đưa số liệu pháp luật chưa xác minh trực tiếp từ văn bản gốc vào codebase như sự thật đã kiểm chứng. **v1.4 — hoá ra cấu trúc "45% nền + 2%/năm, trần 75%" được biết rộng rãi là ĐÚNG khi đối chiếu trực tiếp với Điều 66/99 thật** — nguyên tắc "chờ xác minh thay vì bịa theo hiểu biết phổ thông" vẫn đúng đắn dù lần này hiểu biết phổ thông trùng khớp, vì không có cách nào biết trước điều đó mà không đối chiếu. |

---

## 1. Giới thiệu & Mục tiêu

Người tham gia BHXH tự nguyện hiện không có cách nào tự ước tính "nếu tôi đóng mức X trong Y năm thì lương hưu hằng tháng sau này khoảng bao nhiêu" mà không nhờ nhân viên BHXH tính hộ hoặc tự tra cứu nhiều văn bản (Luật BHXH 2024, Nghị định 159/2025/NĐ-CP, các Thông tư công bố hệ số trượt giá/lãi suất đầu tư quỹ hàng năm). Đây là rào cản lớn với đúng nhóm đối tượng chính của BHXH tự nguyện — lao động tự do, nông dân, hộ kinh doanh nhỏ — vốn là chủ đề quen thuộc của 1 nền tảng nội dung gia đình.

Module **PensionCalculator** cung cấp 1 công cụ công khai (không cần đăng nhập) để người đọc:

1. Nhập **dòng thời gian đóng góp** (nhiều giai đoạn, mỗi giai đoạn 1 mức thu nhập chọn đóng khác nhau — đúng thực tế vì thu nhập/mức đóng thường đổi theo năm).
2. Xem **mức đóng hằng tháng** ước tính theo mức thu nhập chọn (có/không thuộc diện hỗ trợ nhà nước).
3. Xem **mức bình quân thu nhập tháng đóng BHXH** sau khi quy đổi theo hệ số trượt giá từng năm.
4. Xem **điều kiện hưởng lương hưu** (đủ tuổi/đủ năm đóng chưa, thuộc trường hợp nào trong 3 trường hợp của Điều 11/13 Nghị định 159).
5. Xem **ước tính lương hưu hằng tháng** (nếu đủ điều kiện) hoặc **hướng dẫn phương án thay thế** (đóng bù, chờ trợ cấp hằng tháng...) nếu chưa đủ điều kiện.

**Đây là công cụ MINH HOẠ/ước tính** — không phải hệ thống tính đúng 100% để làm hồ sơ hưởng chế độ thật (BHXH Việt Nam mới là cơ quan có thẩm quyền chốt số liệu chính thức). Disclaimer này phải hiển thị rõ, thường trực trên giao diện (§10.4), cùng tinh thần với `anlandLoanCalculator`.

---

## 2. Căn cứ pháp lý & đối chiếu nguồn

### 2.1 Văn bản gốc

- **Luật Bảo hiểm xã hội 2024** (số 41/2024/QH15, hiệu lực 01/07/2025) — luật gốc, **không nằm trong tài liệu người dùng cung cấp**, chỉ được Nghị định 159 trích dẫn số Điều. Bất kỳ công thức nào dưới đây ghi "(→ Luật, chưa có trong tài liệu)" là chỗ **bắt buộc lấy văn bản Luật thật để xác nhận trước khi code**, không suy đoán.
- **Nghị định 159/2025/NĐ-CP** (25/06/2025, hiệu lực 01/07/2025) — quy định chi tiết BHXH tự nguyện, thay thế Nghị định 134/2015/NĐ-CP (Điều 17.2). Đây là nguồn chính của spec này, đã trích đầy đủ 18 Điều (xem §6).
- **spec/bhxh/bhxh.md** — bài báo tổng hợp (thuvienphapluat.vn), diễn giải lại đúng Điều 5 Nghị định 159 dưới dạng bảng ví dụ — dùng để **đối chiếu số liệu**, không phải nguồn luật gốc.

### 2.2 Phát hiện sai số trong `bhxh.md` — dùng số đã kiểm chứng, không dùng nguyên văn

`bhxh.md` viết: *"Cao nhất bằng 48.600.000 đồng (bằng 20 lần mức tham chiếu... 2.340.000 đồng)"* — nhưng **20 × 2.340.000 = 46.800.000**, không phải 48.600.000. Bảng ví dụ ngay bên dưới trong cùng bài báo tự mâu thuẫn với câu này: dòng cuối bảng ghi rõ **"46.800.000 (tối đa)"** với "Mức đóng khi chưa hỗ trợ" = 10.296.000 — khớp đúng `22% × 46.800.000 = 10.296.000` (nếu dùng 48.600.000 sẽ ra 10.692.000, sai với bảng). Kết luận: **46.800.000 là số đúng**, "48.600.000" là lỗi đánh máy của bài báo nguồn. Spec này dùng 46.800.000 làm trần đóng hiện hành, ghi rõ cách suy ra (20 × mức tham chiếu) để tự động cập nhật khi mức tham chiếu đổi, không hard-code số tuyệt đối.

### 2.3 Ảnh ví dụ (`1.png`–`3.png`) — chỉ minh hoạ CƠ CHẾ, không phải số liệu hiện hành

3 ảnh mô tả 1 ví dụ tính **trợ cấp BHXH một lần** (không phải lương hưu hằng tháng) cho 1 công nhân đóng BHXH **bắt buộc** giai đoạn 2013-2016, giải quyết vào **2017**, dùng hệ số trượt giá của **năm 2017** (Thông tư 42/2016/TT-BLĐTBXH). 2 điểm cần lưu ý khi dùng ảnh này làm tài liệu tham khảo cho module:

1. **Cơ chế** (tra hệ số trượt giá đúng theo từng năm đã đóng, nhân với số tháng và mức lương/thu nhập của năm đó, cộng dồn rồi chia tổng số tháng ra "mức bình quân") là cơ chế DÙNG CHUNG cho cả BHXH một lần (Điều 9 Nghị định 159) lẫn mức bình quân làm căn cứ tính lương hưu (Điều 11.4) — module tái dùng đúng cơ chế này (§6.7/§7).
2. **Số liệu cụ thể trong ảnh** (hệ số 1,08/1,03/1,03/1 của năm 2017, mức lương 1.200.000-2.515.000đ) là dữ liệu lịch sử của 1 ví dụ cũ (BHXH bắt buộc, không phải tự nguyện) — **không đưa vào seed data**, chỉ dùng để viết test case minh hoạ cơ chế tính (§13).

---

## 3. Phạm vi

### 3.1 Trong phạm vi (MVP — v1.0)

| # | Tính năng | Điều tương ứng (Nghị định 159) |
|---|---|---|
| 1 | Tính mức đóng BHXH tự nguyện hằng tháng theo mức thu nhập chọn + nhóm hỗ trợ | Điều 5 |
| 2 | Nhập dòng thời gian đóng góp nhiều giai đoạn (mức thu nhập khác nhau theo năm) | — (cơ chế dùng chung, không phải 1 Điều riêng) |
| 3 | Điều chỉnh thu nhập theo hệ số trượt giá từng năm | Điều 10 |
| 4 | Tính mức bình quân thu nhập tháng đóng BHXH (trường hợp thuần tự nguyện) | Điều 104 Luật (→ Luật, chưa có trong tài liệu — suy luận từ Điều 11.4 cho trường hợp tổng quát hơn, xem §6.6/§14) |
| 5 | Tính mức bình quân tiền lương/thu nhập (trường hợp có cả BHXH bắt buộc lẫn tự nguyện) | Điều 11.4 |
| 6 | Kiểm tra điều kiện hưởng lương hưu (4 nhánh: mixed ≥15 năm bắt buộc / mixed suy giảm lao động / thuần tự nguyện trước 2021 ≥20 năm / thuần tự nguyện **từ 2021** ≥15 năm — nhánh (d) bổ sung v1.1) | Điều 11.2, Điều 13.1, Điều 98 (nhánh d — xem ghi chú suy luận ở §6.8) |
| 7 | Áp dụng sàn "mức tham chiếu" khi mixed ≥20 năm bắt buộc mà lương hưu tính ra thấp hơn | Điều 11.3 (đoạn 2) |
| 8 | Ước tính lương hưu hằng tháng = tỷ lệ hưởng × mức bình quân | Điều 11.3, Điều 13.2 (**cần xác minh tỷ lệ hưởng, §14 — CHẶN go-live, chưa có nguồn tại thời điểm v1.1**) |
| 9 | Trang admin quản lý tham số theo giai đoạn hiệu lực (mức chuẩn nghèo, mức tham chiếu, tỷ lệ hỗ trợ, hệ số trượt giá, lãi suất đầu tư quỹ, tỷ lệ hưởng lương hưu) | Điều 18.3/18.4 (trách nhiệm cập nhật hàng năm) |
| 15 | Bảng breakdown Mbq theo từng năm + cảnh báo hết hỗ trợ nhà nước sau 120 tháng (bổ sung v1.1) | §10.5/§10.6 |

### 3.2 Phase 1.5 (khuyến nghị bổ sung — dự báo & tối ưu, xem §15)

| # | Tính năng | Ghi chú |
|---|---|---|
| 10.5 | Tab "Dự báo & Tối ưu mức đóng" — nhập mục tiêu lương hưu, tool tự tìm `TN` tối thiểu (hoặc số năm tối thiểu) cần chọn | Thuần client-side (binary search/công thức đại số trên các getter đã có ở MVP) — **kết quả VẪN phụ thuộc `pension_rate_brackets`** (mục 8), nếu bảng đó rỗng thì tab này cũng chỉ hiển thị cảnh báo, không có số |

### 3.3 Phase 2 (sau MVP, đã đặc tả công thức ở §6 nhưng chưa lên UI/API)

| # | Tính năng | Điều |
|---|---|---|
| 11 | Mức đóng 1 lần cho nhiều năm về sau (chiết khấu theo lãi suất đầu tư quỹ) | Điều 6 |
| 12 | Mức đóng 1 lần cho thời gian còn thiếu để đủ 15 năm hưởng lương hưu | Điều 7 |
| 13 | Hoàn trả 1 phần khi chuyển bắt buộc/hưởng 1 lần/chết/đủ điều kiện hưu | Điều 8 |
| 14 | BHXH một lần (rút 1 lần) | Điều 9 (**cần xác minh công thức TC — Điều 102 Luật, §14**) |
| 15b | Trợ cấp hằng tháng cho người đủ tuổi nhưng chưa đủ năm đóng | Điều 14 (**cần xác minh TC_htxh — Điều 21 Luật, §14**) |

### 3.4 Ngoài phạm vi (xem §12 để biết đầy đủ lý do)

Chế độ tử tuất (Điều 12/15), trợ cấp mai táng, quy định chuyển tiếp cho người đã tham gia trước 01/07/2025 (Điều 16 — chỉ ảnh hưởng cách BHXH Việt Nam xử lý hồ sơ thật, không ảnh hưởng công thức minh hoạ), tính năng lưu/so sánh nhiều kịch bản, xuất PDF kết quả, tài khoản người dùng.

---

## 4. Vị trí trong hệ thống & kiến trúc module

```
Modules/PensionCalculator/
├── app/
│   ├── Features/
│   │   ├── PublicEstimation/
│   │   │   ├── Http/PensionCalculatorController.php   — trang public + API tham chiếu (GET, không auth)
│   │   │   └── Actions/BuildActiveParameterSetAction.php
│   │   └── ParameterManagement/
│   │       ├── Http/PensionParameterAdminController.php — CRUD tham số (auth + permission)
│   │       └── Actions/
│   │           ├── SaveParameterPeriodAction.php
│   │           ├── SavePriceIndexCoefficientAction.php
│   │           └── SavePensionRateBracketAction.php
│   ├── Models/
│   │   ├── PensionParameterPeriod.php
│   │   ├── PensionSupportTier.php
│   │   ├── PensionPriceIndexCoefficient.php
│   │   └── PensionRateBracket.php
│   └── Providers/PensionCalculatorServiceProvider.php
├── database/
│   ├── migrations/
│   └── seeders/
│       ├── PensionCalculatorPermissionSeeder.php   (nếu quyết định cuối cùng vẫn seed thêm — xem §9.3, mặc định đi qua Lớp A nên KHÔNG cần seeder riêng)
│       └── PensionCalculatorDemoParameterSeeder.php — seed đúng số liệu đã xác minh trong spec này (KHÔNG seed nhánh cần-xác-minh, xem §14)
├── resources/views/
│   ├── public/index.blade.php     — trang tính (Blade + Alpine SPA, 1 file, theo khuôn CoreIdeaExtractor)
│   └── admin/parameters/index.blade.php
├── routes/web.php
└── module.json
```

**Không** tenant-scoped (không extends `TenantAwareModel`, không cột `organization_id`) — tham số BHXH tự nguyện là quy định pháp luật áp dụng thống nhất toàn quốc, không khác nhau theo Organization sử dụng nền tảng, cùng lý do `Post`/`Banner` không tenant-scoped (tài sản/nội dung dùng chung toàn platform) — **khác** `Product` (catalog riêng theo từng org, xem `project_product_public_embed_scope`).

---

## 5. Route & Permission

```php
// routes/web.php (Modules/PensionCalculator)

// ── Public — không auth, giống /anland ──────────────────────────────────
Route::get('tinh-luong-huu-bhxh-tu-nguyen', [PensionCalculatorController::class, 'index'])
    ->name('pension-calculator.public.index');

Route::get('api/pension-calculator/reference-data', [PensionCalculatorController::class, 'referenceData'])
    ->middleware('throttle:60,1') // chỉ đọc dữ liệu công khai, không nhận input cá nhân — throttle chống scrape/abuse thô, không phải bảo mật dữ liệu nhạy cảm
    ->name('pension-calculator.public.reference-data');

// ── Admin — quản lý tham số ──────────────────────────────────────────────
Route::middleware(['auth', 'can:pension_calculator.manage'])
    ->prefix('dashboard/pension-calculator')
    ->name('backend.pension-calculator.')
    ->group(function (): void {
        Route::get('/', [PensionParameterAdminController::class, 'index'])->name('index');
        Route::get('periods/create', [PensionParameterAdminController::class, 'createPeriod'])->name('periods.create');
        Route::post('periods', [PensionParameterAdminController::class, 'storePeriod'])->name('periods.store');
        Route::get('price-index/create', [PensionParameterAdminController::class, 'createPriceIndex'])->name('price-index.create');
        Route::post('price-index', [PensionParameterAdminController::class, 'storePriceIndex'])->name('price-index.store');
        // Không có "edit"/"update"/"destroy" cho dữ liệu ĐÃ CÓ HIỆU LỰC (§9.2 — bất biến,
        // giống lý do snapshot Assessment không sửa ngược lịch sử).
    });
```

`config/permissions.php` (Lớp A, thêm 1 dòng, không seeder riêng):

```php
// App\Enums\PermissionEnum — thêm case mới
case PENSION_CALCULATOR_MANAGE = 'pension_calculator.manage';

// config/permissions.php
R::System_Admin->value => [
    // ...
    P::PENSION_CALCULATOR_MANAGE->value,
],
R::CEO->value => [
    // ...
    P::PENSION_CALCULATOR_VIEW->value, // chỉ xem, không có quyền sửa tham số pháp lý
],
```

---

## 6. Công thức nghiệp vụ (đầy đủ 18 Điều, kèm chú thích phạm vi sử dụng)

> Ký hiệu dùng thống nhất: `TN` = mức thu nhập chọn đóng (đồng/tháng), `r` = lãi suất đầu tư quỹ BHXH bình quân tháng (do BHXH Việt Nam công bố hàng năm), `CN` = mức chuẩn hộ nghèo khu vực nông thôn, `k` = tỷ lệ hỗ trợ nhà nước (%).

### 6.1 Mức đóng hằng tháng (Điều 5) — **MVP**

```
MĐ = 22% × TN − HTr
HTr = k × (22% × CN)
```

| Nhóm | k | HTr hiện hành (CN = 1.500.000đ) |
|---|---|---|
| Hộ nghèo / xã đảo, đặc khu | 50% | 165.000đ |
| Hộ cận nghèo | 40% | 132.000đ |
| Dân tộc thiểu số | 30% | 99.000đ |
| Người tham gia khác | 20% | 66.000đ |

Ràng buộc: `1.500.000 ≤ TN ≤ 46.800.000` (= 20 × mức tham chiếu 2.340.000, xem §2.2). Thời gian hỗ trợ tối đa 120 tháng theo thời gian tham gia THỰC TẾ (Điều 5.2) — module cần đếm số tháng đã nhận hỗ trợ trong dòng thời gian nhập vào, ngừng cộng `HTr` sau tháng thứ 120.

### 6.2 Mức đóng 1 lần cho n năm về sau (Điều 6) — Phase 2

```
MĐ1 = Σ (i=1 → n×12)  [ TNᵢ × 22% / (1+r)^(i−1) ]
```
`n ∈ {2,3,4,5}` (không quá 5 năm/lần). Chiết khấu theo lãi suất đầu tư quỹ bình quân tháng của **năm trước liền kề với năm đóng**.

### 6.3 Mức đóng 1 lần cho thời gian còn thiếu (Điều 7) — Phase 2

Chỉ áp dụng người **đã đủ tuổi nghỉ hưu** (Bộ luật Lao động Điều 169.2) nhưng còn thiếu **≤ 60 tháng** để đủ 15 năm đóng:

```
MĐ2 = Σ (i=1 → t)  [ (TNᵢ × 22%) × (1+r)^i ]
```
Khác Điều 6 ở chỗ đây là **lãi gộp** (nhân `(1+r)^i`, không chia) vì đóng bù cho quá khứ gần, không phải trả trước cho tương lai.

### 6.4 Hoàn trả một phần (Điều 8) — Phase 2

4 trường hợp được hoàn (chỉ áp dụng cho người đã chọn đóng 3/6/12 tháng 1 lần hoặc đóng 1 lần cho nhiều năm về sau): chuyển sang BHXH bắt buộc; hưởng BHXH một lần (Điều 102 Luật); chết/Toà tuyên bố đã chết; đủ điều kiện và có đề nghị hưởng lương hưu (Điều 98 hoặc khoản 9 Điều 141 Luật).

- Phương thức 3/6/12 tháng: hoàn = tổng tiền đã đóng tương ứng **thời gian còn lại**, KHÔNG gồm phần nhà nước hỗ trợ.
- Phương thức đóng 1 lần cho nhiều năm về sau (Điều 6):
```
HT = Σ (i=n×12−m+1 → n×12)  [ TNᵢ × 22% / (1+r)^(i−1) ]  −  M
```
`m` = số tháng còn lại của phương thức đã đóng; `M` = số tiền nhà nước đã hỗ trợ (nếu có).

### 6.5 BHXH một lần (Điều 9) — Phase 2, **có phần cần xác minh**

```
MH = TC − M
Mᵢ = 0,22 × CNᵢ × k          (số hỗ trợ nhà nước của riêng tháng i)
M  = Σ Mᵢ
```
`TC` = mức hưởng xác định theo **khoản 2 Điều 102 Luật BHXH 2024** — **công thức TC đầy đủ không nằm trong Nghị định 159 hay `bhxh.md`**, chỉ được tham chiếu số Điều. Theo hiểu biết phổ thông (Luật BHXH 2014 tiền nhiệm), TC thường tính theo số năm đóng trước/sau 2014 nhân hệ số 1,5/2,0 lần mức bình quân — **giống hệt cơ chế trong ảnh ví dụ `2.png`/`3.png`** — nhưng đây là **suy đoán chưa xác minh với Luật 2024**, không seed vào code cho tới khi đối chiếu (§14, mục 2).

### 6.6 Điều chỉnh thu nhập tháng đã đóng theo hệ số trượt giá (Điều 10) — **MVP**

```
Thu nhập đã điều chỉnh (năm t) = Thu nhập gốc (năm t) × Hệ số điều chỉnh (năm t)

Hệ số điều chỉnh (năm t) = CPI bình quân năm liền kề trước năm hưởng (gốc so sánh 2008)
                            ─────────────────────────────────────────────────────
                            CPI bình quân năm t (gốc so sánh 2008)
```
Làm tròn đến 2 chữ số thập phân, **tối thiểu 1** (Điều 10.2). Riêng phần thu nhập đóng bù theo Điều 7 (đóng 1 lần cho thời gian còn thiếu) **luôn nhân hệ số = 1** (Điều 10.3 — không trượt giá vì đóng ngay tại hiện tại, không phải quá khứ). Bảng hệ số do BHXH Việt Nam công bố + đăng tải trên Cổng TTĐT BHXH Việt Nam **hàng năm** (Điều 18.3/18.4) — đây chính là bảng `pension_price_index_coefficients` (§7.3), KHÔNG tính CPI trong module, chỉ NHẬP LẠI bảng đã công bố.

### 6.7 Mức bình quân tiền lương và thu nhập làm căn cứ đóng BHXH — trường hợp MIXED (Điều 11.4) — **MVP**

```
                Mbq_bắt_buộc × Tổng_tháng_bắt_buộc + Tổng_thu_nhập_tự_nguyện_đã_điều_chỉnh
Mbq_mixed  =   ──────────────────────────────────────────────────────────────────────────
                Tổng_tháng_bắt_buộc + Tổng_tháng_tự_nguyện
```
`Mbq_bắt_buộc` theo Điều 72/73 Luật BHXH 2024 (→ Luật, chưa có trong tài liệu — module KHÔNG tự tính nhánh bắt buộc, nhận làm **input trực tiếp** nếu người dùng có thời gian BHXH bắt buộc, xem §8 bước 2). `Tổng_thu_nhập_tự_nguyện_đã_điều_chỉnh` = tổng các mức thu nhập đã điều chỉnh theo §6.6.

Trường hợp **thuần tự nguyện** (không có thời gian bắt buộc): công thức rút gọn về trung bình cộng đơn giản của thu nhập đã điều chỉnh, chia cho tổng số tháng — quy định đầy đủ nằm ở **Điều 104 Luật BHXH 2024** (→ Luật, chưa có trong tài liệu, nhưng suy ra hợp lý từ việc Điều 11.4 chỉ là bản mở rộng "cộng thêm nhánh bắt buộc" của công thức thuần tự nguyện — **cần đối chiếu Điều 104 thật để xác nhận không có hệ số/ngoại lệ nào khác**, §14 mục 3).

### 6.8 Điều kiện hưởng lương hưu — 4 nhánh (Điều 11.2, Điều 13.1, +nhánh (d) v1.1) — **MVP**

| Nhánh | Điều kiện năm đóng | Điều kiện tuổi | Nguồn |
|---|---|---|---|
| (a) Mixed, có BHXH bắt buộc | ≥ 15 năm bắt buộc | Theo Điều 64 Luật (lộ trình tuổi nghỉ hưu chung) — (→ Luật, chưa có trong tài liệu) | Điều 11.2.a |
| (b) Mixed, suy giảm khả năng lao động | ≥ 20 năm bắt buộc + suy giảm ≥ 61% | Theo Điều 65 Luật (nghỉ hưu sớm) — (→ Luật, chưa có trong tài liệu) | Điều 11.2.b |
| (c) Thuần tự nguyện, tham gia trước 01/01/2021 | ≥ 20 năm tự nguyện | **Đủ 60 tuổi (nam), đủ 55 tuổi (nữ)** — có số cụ thể trong tài liệu | Điều 11.2.c / Điều 13.1 |
| (d) Thuần tự nguyện (hoặc mixed không rơi vào a/b/c), tham gia **từ 01/01/2021 trở đi** — bổ sung v1.1 | ≥ 15 năm đóng BHXH (tự nguyện, hoặc tự nguyện + bắt buộc cộng dồn) | Theo Điều 64 Luật (lộ trình tuổi nghỉ hưu chung — **cùng mốc tuổi với nhánh (a)**, không phải 60/55 cố định như nhánh (c)) | Điều 98 Luật (**→ Luật, chưa có trong tài liệu — suy luận, xem ghi chú dưới**) |

**Vì sao có nhánh (d), suy luận từ cấu trúc chính Nghị định (chưa xác minh trực tiếp bằng văn bản Luật):** Điều 13 Nghị định 159 tự đóng khung là **quy định RIÊNG cho người tham gia BHXH tự nguyện TRƯỚC 01/01/2021** ("Chế độ hưu trí đối với người tham gia bảo hiểm xã hội tự nguyện trước ngày 01/01/2021") — cách đặt tên Điều này ngụ ý đây là 1 điều khoản **chuyển tiếp/giữ quyền lợi cũ** cho nhóm đã tham gia theo luật cũ, khác với quy định CHUNG áp dụng cho người tham gia mới. Điều 14 Nghị định (trợ cấp hằng tháng cho người không đủ điều kiện hưu) trích thẳng **"Điều 98 Luật Bảo hiểm xã hội"** làm điều kiện hưởng lương hưu chung — cùng số Điều mà Điều 11.2.a dùng làm căn cứ tuổi (Điều 64) đi kèm. Suy luận hợp lý: người **KHÔNG** thuộc diện chuyển tiếp Điều 13 (tức bắt đầu tham gia tự nguyện từ 01/01/2021 trở đi) mặc định rơi vào điều kiện chung của Điều 98 — cùng mốc **15 năm** đã thấy lặp lại ở nhánh (a) (chứ không phải 20 năm như nhánh (c) áp dụng cho luật cũ). **Đây vẫn là suy luận cấu trúc, CHƯA đối chiếu trực tiếp với text Điều 98 Luật BHXH 2024** — cùng mức độ chưa-xác-minh như nhánh (a)/(b), không tệ hơn nhưng cũng không tốt hơn — xem §14 mục 4b.

**Sàn bảo vệ (Điều 11.3, đoạn 2):** nếu người mixed có thời gian đóng BHXH bắt buộc **≥ 20 năm** (thuộc nhóm đối tượng a,b,c,d,đ,g,i khoản 1 Điều 2 Luật, tham gia **trước 01/07/2025**) mà lương hưu tính ra **thấp hơn mức tham chiếu** → lấy bằng **mức tham chiếu** (hiện hành 2.340.000đ).

### 6.9 Mức lương hưu hằng tháng — **MVP, đã xác minh (v1.4)**

```
Lương hưu hằng tháng = Tỷ lệ hưởng lương hưu hằng tháng (%) × Mbq (§6.7)
```
Với nhánh (a)/(b): tỷ lệ theo **Điều 66 Luật Bảo hiểm xã hội 2024** (Điều 11.3 Nghị định 159 không trích số Điều Luật cụ thể, đã đối chiếu trực tiếp §14 mục 1). Với nhánh (c)/(d): tỷ lệ theo **Điều 99 Luật BHXH 2024** (Điều 13.2 Nghị định 159). **2 Điều dùng CHUNG 1 công thức**: 45% nền (nữ 15 năm/nam 20 năm) + 2%/năm tiếp theo, trần 75%; riêng nam có 15-19 năm dùng 40% nền (15 năm) + 1%/năm, nối liền mạch đúng ở mốc 20 năm. Đã seed `pension_rate_brackets` (1 bộ 3 dòng, dùng chung mọi nhánh) — xem §14 mục 1 (RESOLVED).

### 6.10 Trợ cấp hằng tháng cho người không đủ điều kiện hưu (Điều 14) — Phase 2, **có phần cần xác minh**

Áp dụng người đủ tuổi nghỉ hưu (BLLĐ Điều 169.2) nhưng KHÔNG đủ năm đóng để hưởng lương hưu (Điều 98 Luật) và CHƯA đủ tuổi hưởng trợ cấp hưu trí xã hội (Điều 21 Luật), không hưởng BHXH một lần, không bảo lưu, có đề nghị:

```
T_tt = (Mbq × 2 × N) / TC_htxh
```
`N` = số năm đóng (≥ 12 tháng; lẻ 1-6 tháng = nửa năm, 7-12 tháng = 1 năm). `TC_htxh` = mức trợ cấp hưu trí xã hội hằng tháng tại thời điểm giải quyết — **(→ Luật Điều 21, chưa có trong tài liệu)**.

Nếu `T_tt` tính ra **vượt** khoảng thời gian từ tháng đề nghị đến khi đủ tuổi hưởng trợ cấp hưu trí xã hội (`T_đt`) → hưởng mức **cao hơn**:
```
TC_tt = TC_htxh + [(T_tt − T_đt) × TC_htxh] / T_đt
```
Nếu `T_tt` **không đủ** và người tham gia muốn đóng bù thêm để hưởng tới khi đủ tuổi hưởng trợ cấp hưu trí xã hội:
```
ST_mlct = (T_đt − T_tt) × TC_htxh
```

### 6.11 Chế độ tử tuất (Điều 12, 15) — ngoài phạm vi (§12)

### 6.12 Quy định chuyển tiếp (Điều 16) — không ảnh hưởng công thức minh hoạ, chỉ ảnh hưởng thủ tục thật tại BHXH Việt Nam — ngoài phạm vi.

---

## 7. Luồng tính năng chính — "Ước tính lương hưu" (MVP)

Bước người dùng thao tác trên trang public (`resources/views/public/index.blade.php`):

**Bước 1 — Thông tin cơ bản:** giới tính (nam/nữ — quyết định mốc tuổi/năm ở §6.8), năm sinh, đã có thời gian đóng BHXH **bắt buộc** trước đó chưa (nếu có: nhập tổng số tháng đã đóng bắt buộc + mức bình quân tiền lương bắt buộc tự khai — module không tự tính nhánh này, xem §6.7).

**Bước 2 — Dòng thời gian đóng BHXH tự nguyện:** người dùng thêm nhiều dòng, mỗi dòng gồm `từ tháng/năm`, `đến tháng/năm`, `mức thu nhập chọn đóng` (đồng/tháng), `nhóm hỗ trợ` (nghèo/cận nghèo/dân tộc thiểu số/khác/không thuộc diện hỗ trợ) — UI/UX tham khảo cách RealEstate `selectorOverrides` của CoreIdeaExtractor (mỗi dòng nhập độc lập, thêm/xoá/sắp xếp tự do, xem `spec/CoreIdeaExtractor.md` v1.13). Với mỗi dòng, hiển thị ngay **mức đóng hằng tháng** ước tính (§6.1) — riêng cột hỗ trợ nhà nước, xem quy tắc đếm 120 tháng ở §10.6 (v1.1).

**Bước 3 — Tính mức bình quân:** với mỗi dòng, tra hệ số trượt giá đúng năm của dòng đó (bảng `pension_price_index_coefficients`, tra theo `settlement_year` = năm hiện tại người dùng đang xem trang — KHÔNG phải năm cố định như ví dụ 2017 trong ảnh) → nhân thu nhập gốc × hệ số → cộng dồn toàn bộ dòng → chia tổng số tháng → ra `Mbq` (thuần tự nguyện, §6.7 nhánh rút gọn) hoặc trộn với `Mbq_bắt_buộc` người dùng tự khai ở Bước 1 (§6.7 công thức mixed). Kết quả bước này hiển thị dưới dạng **bảng breakdown theo năm** (mẫu §13), không chỉ 1 con số cuối — xem §10.5 (v1.1).

**Bước 4 — Kiểm tra điều kiện hưởng lương hưu:** đối chiếu tổng số năm đóng (bắt buộc + tự nguyện) + tuổi tại thời điểm dự kiến nghỉ hưu với 4 nhánh (§6.8, đã thêm nhánh (d) v1.1). Nếu **đủ điều kiện** → Bước 5. Nếu **chưa đủ** → hiển thị số năm/tháng còn thiếu + gợi ý 3 hướng: (1) **tiếp tục đóng BHXH tự nguyện tới khi đủ năm** — CÓ tính số minh hoạ (v1.3, xem §7.1 dưới); (2) đóng bù 1 lần (Điều 7, nếu thiếu ≤ 60 tháng và đã đủ tuổi) hoặc (3) trợ cấp hằng tháng (Điều 14, nếu không muốn đóng bù) — 2 phương án này đã có công thức (§6.3/§6.10) nhưng **CHƯA tính được số cụ thể**, không phải giới hạn kỹ thuật mà vì `TC_htxh` (mức trợ cấp hưu trí xã hội hằng tháng, Điều 21 Luật Bảo hiểm xã hội 2024) chưa có nguồn xác minh (§14 mục 5) — không bịa số.

### 7.1 Bảng minh hoạ "nếu tiếp tục đóng đến khi đủ điều kiện" (v1.3, bổ sung)

Chỉ hiển thị khi **chưa đủ điều kiện** năm đóng (Bước 4). Cho 3 kịch bản thu nhập giả định (thấp hơn / giữ nguyên mức gần nhất đã nhập ở Bước 2 / cao hơn — người dùng sửa tay được), tính:

- Số tháng còn thiếu = `pensionEligibility.monthsRequired − pensionEligibility.monthsAccumulated` (theo đúng nhánh đang áp dụng ở Bước 4).
- Mbq minh hoạ = `(số đã tích luỹ (đã điều chỉnh, từ breakdown §10.5) + số tháng còn thiếu × mức thu nhập giả định × 1) / tổng số tháng`. Hệ số trượt giá cho các tháng **tương lai** cố định = **1** (dùng nguyên giá trị danh nghĩa hiện tại) — KHÔNG đoán hệ số chưa được BHXH Việt Nam công bố cho năm chưa tới, đúng nguyên tắc "không bịa số liệu pháp luật" đã áp dụng xuyên suốt spec này.
- Lương hưu minh hoạ = tra `pensionRateFor(gender, năm dự kiến đủ điều kiện)` × Mbq minh hoạ — nếu `pension_rate_brackets` rỗng (đúng thực trạng seed hiện tại, §14 mục 1) thì hiển thị "chưa xác minh tỷ lệ hưởng" thay vì số, cùng cách xử lý §10.4.
- Bị chặn (`blocked`) nếu bất kỳ dòng đóng góp nào trong Bước 2 thiếu hệ số trượt giá (đúng edge case §11 mục 4) hoặc chưa có `PensionParameterPeriod` hiện hành.
- Đây là **phương án thứ 3, khác** đóng bù 1 lần (Điều 7)/trợ cấp hằng tháng (Điều 14) — chỉ minh hoạ việc tiếp tục đóng tự nguyện bình thường, không liên quan `TC_htxh`.

**Bước 5 — Ước tính lương hưu:** áp dụng tỷ lệ hưởng (§6.9 — **hiển thị rõ nhãn "cần xác minh"** cho tới khi §14 mục 1 hoàn tất, xem §10.4) × `Mbq` → ra số ước tính hằng tháng. Áp sàn mức tham chiếu nếu thuộc trường hợp §6.8 sàn bảo vệ.

**Bước 6 (Phase 1.5, tab riêng) — Dự báo & Tối ưu:** xem §15.

Toàn bộ Bước 2-6 tính **tại client** (Alpine.js), server chỉ cấp dữ liệu tham chiếu 1 lần lúc tải trang (§8).

---

## 8. Kiến trúc dữ liệu

### 8.1 ERD

```
PensionParameterPeriod                          PensionSupportTier
  ├─ id                                            ├─ id
  ├─ effective_from (date, unique, index)          ├─ period_id (FK → PensionParameterPeriod)
  ├─ rural_poverty_line (decimal)   — CN            ├─ group_key (enum: poor/near_poor/ethnic_minority/other)
  ├─ reference_level (decimal)      — mức tham chiếu├─ support_percent (decimal)
  ├─ contribution_rate_percent (decimal, default 22.00)
  ├─ ceiling_multiplier (unsigned tinyint, default 20)  — trần = ceiling_multiplier × reference_level, KHÔNG hard-code số tuyệt đối
  ├─ source_document (string)       — VD "Nghị định 159/2025/NĐ-CP"
  ├─ notes, created_by, timestamps

PensionPriceIndexCoefficient                    PensionRateBracket   ⚠ xem §14 mục 1 trước khi seed
  ├─ id                                            ├─ id
  ├─ settlement_year (unsigned smallint)  — năm giải quyết/năm hưởng ├─ gender (enum: male/female)
  ├─ contribution_year (unsigned smallint) — năm đã đóng            ├─ min_years_for_base_rate (unsigned tinyint)
  ├─ coefficient (decimal 4,2)                     ├─ base_rate_percent (decimal)
  ├─ source_document (string)  — VD "Thông tư .../BNV"              ├─ increment_percent_per_year (decimal)
  ├─ created_by, timestamps                        ├─ max_rate_percent (decimal, default 75.00)
  unique(settlement_year, contribution_year)        ├─ effective_from (date)
                                                     ├─ source_document, notes
```

Không có bảng lưu input/kết quả của người dùng (§0 — quyết định không lưu dữ liệu cá nhân).

### 8.2 Migration (rút gọn — 4 bảng)

```php
Schema::create('pension_parameter_periods', function (Blueprint $table) {
    $table->id();
    $table->date('effective_from')->unique();
    $table->decimal('rural_poverty_line', 15, 2);
    $table->decimal('reference_level', 15, 2);
    $table->decimal('contribution_rate_percent', 5, 2)->default(22.00);
    $table->unsignedTinyInteger('ceiling_multiplier')->default(20);
    $table->string('source_document');
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});

Schema::create('pension_support_tiers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('period_id')->constrained('pension_parameter_periods')->cascadeOnDelete();
    $table->string('group_key'); // poor_household | near_poor_household | ethnic_minority | other
    $table->decimal('support_percent', 5, 2);
    $table->unique(['period_id', 'group_key']);
});

Schema::create('pension_price_index_coefficients', function (Blueprint $table) {
    $table->id();
    $table->unsignedSmallInteger('settlement_year');
    $table->unsignedSmallInteger('contribution_year');
    $table->decimal('coefficient', 4, 2);
    $table->string('source_document');
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->unique(['settlement_year', 'contribution_year']);
});

Schema::create('pension_rate_brackets', function (Blueprint $table) {
    $table->id();
    $table->string('gender'); // male | female
    $table->unsignedTinyInteger('min_years_for_base_rate');
    $table->decimal('base_rate_percent', 5, 2);
    $table->decimal('increment_percent_per_year', 5, 2);
    $table->decimal('max_rate_percent', 5, 2)->default(75.00);
    $table->date('effective_from');
    $table->string('source_document');
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

### 8.3 `PensionCalculatorDemoParameterSeeder` — seed ĐÚNG số đã xác minh (§2), KHÔNG seed `pension_rate_brackets`

```php
PensionParameterPeriod::create([
    'effective_from' => '2025-07-01',
    'rural_poverty_line' => 1_500_000,
    'reference_level' => 2_340_000,
    'contribution_rate_percent' => 22.00,
    'ceiling_multiplier' => 20,
    'source_document' => 'Nghị định 159/2025/NĐ-CP Điều 5; Luật Bảo hiểm xã hội 2024 Điều 141',
]);
// 4 dòng PensionSupportTier: poor_household=50, near_poor_household=40, ethnic_minority=30, other=20

// v1.2 — pension_price_index_coefficients: seed 19 dòng (settlement_year=2026, contribution_year
// 2008-2026) từ cột "thu nhập" của Công văn 340/BHXH-CSXH 03/02/2026 (khoản 2 Điều 10 Nghị định
// 159/2025/NĐ-CP, §6.6). Trước 2008 để trống — BHXH tự nguyện chỉ có hiệu lực từ 01/01/2008.

// KHÔNG insert pension_rate_brackets — bảng này để trống, trang admin (§9) hiển thị cảnh báo
// "Chưa cấu hình tỷ lệ hưởng lương hưu — xác minh Điều 66 Luật BHXH 2024 trước khi nhập số liệu"
// cho tới khi §14 mục 1 hoàn tất.
```

---

## 9. Admin quản lý tham số

### 9.1 CRUD

Chỉ **thêm giai đoạn hiệu lực mới** (`effective_from` mới) — KHÔNG sửa/xoá giai đoạn đã tồn tại (bất biến, giống nguyên tắc snapshot của `Modules/Assessment`, §0 bảng Assessment tham khảo). Lý do: 1 giai đoạn đã dùng để tính minh hoạ cho người dùng trong quá khứ không được đổi ngược, tránh lịch sử "tính lại ra số khác" nếu admin sửa nhầm.

### 9.2 Validate khi thêm giai đoạn mới

- `effective_from` phải sau giai đoạn gần nhất hiện có.
- `reference_level`, `rural_poverty_line` > 0.
- Tổng `support_percent` theo từng `group_key` không bắt buộc theo thứ tự giảm dần nhưng validate cảnh báo (không chặn) nếu nhóm "khác" > nhóm "nghèo" (khả năng gõ nhầm).
- `pension_price_index_coefficients`: nhập theo lô 1 `settlement_year` (giống cách BHXH Việt Nam công bố — 1 bảng hệ số áp dụng cho năm hưởng đó, liệt kê hệ số của TỪNG năm đã đóng trước đó) — form nhập dạng bảng nhiều dòng, không phải 1 dòng/lần.

### 9.3 Permission

`pension_calculator.manage` (System_Admin — sửa được) + `pension_calculator.view` (CEO — chỉ xem, dùng để CEO nắm được platform đang dùng tham số nào, không chỉnh sửa được số liệu tuân thủ pháp luật).

---

## 10. Giao diện & UX (trang public)

### 10.1 Alpine component (khuôn theo `anlandLoanCalculator` + `CoreIdeaExtractor`)

```js
Alpine.data('pensionCalculator', (referenceData) => ({
    gender: 'male',
    birthYear: null,
    hasMandatoryHistory: false,
    mandatoryMonths: 0,
    mandatoryAverageIncome: 0,
    contributionRows: [ /* { fromYear, fromMonth, toYear, toMonth, income, supportGroup } */ ],

    referenceData, // { parameterPeriods, supportTiers, priceIndexCoefficients, rateBrackets }

    get activeParameterPeriod() { /* period có effective_from mới nhất <= hôm nay */ },
    get monthlyContributionFor(row) { /* §6.1 */ },
    get adjustedIncomeFor(row) { /* §6.6 — tra priceIndexCoefficients theo settlement_year = năm hiện tại */ },
    get averageMonthlyIncome() { /* §6.7 */ },
    get pensionEligibility() { /* §6.8 — trả về { eligible, branch, yearsShort, monthsShort } */ },
    get estimatedMonthlyPension() { /* §6.9 — trả null + cờ needsVerifiedRateTable nếu pension_rate_brackets rỗng */ },

    // v1.1 — §10.5: breakdown từng dòng cho bảng hiển thị (mẫu §13), KHÔNG chỉ trả 1 số tổng.
    get averageIncomeBreakdown() {
        // Trả mảng { fromLabel, toLabel, months, coefficient, rawIncome, adjustedTotal } — 1 phần tử/dòng
        // đã nhập ở Bước 2, cộng thêm 1 dòng "Tổng" cuối cùng (Tổng tháng, Tổng thành tiền, Mbq = chia).
        // Nếu thiếu hệ số cho 1 contribution_year → phần tử đó có cờ missingCoefficient=true, KHÔNG tính
        // ngầm bằng 1 (§11 edge case 4) — bảng hiển thị dòng đó bằng màu cảnh báo, ô Mbq tổng disable.
    },

    // v1.1 — §10.6: cảnh báo hết hỗ trợ nhà nước sau 120 tháng (Điều 5.2 Nghị định 159).
    get supportMonthsUsed() {
        // Tổng số tháng (trên toàn bộ contributionRows, sắp theo thời gian) ĐÃ có supportGroup !== 'none'
        // và income nằm trong ràng buộc §6.1, cộng dồn theo đúng thứ tự thời gian — không phải theo
        // thứ tự nhập liệu, vì người dùng có thể thêm/sắp xếp lại dòng tự do (§7 Bước 2).
    },
    get supportMonthsRemaining() { return Math.max(0, 120 - this.supportMonthsUsed); },
    get isSupportExhausted() { return this.supportMonthsUsed >= 120; },
}));
```

### 10.2 API tham chiếu (1 lần lúc tải trang, không gửi input cá nhân)

`GET /api/pension-calculator/reference-data` trả về:
```json
{
  "parameter_periods": [ { "effective_from": "2025-07-01", "rural_poverty_line": 1500000, "reference_level": 2340000, "contribution_rate_percent": 22, "ceiling_multiplier": 20, "support_tiers": [ { "group_key": "poor_household", "support_percent": 50 }, ... ] } ],
  "price_index_coefficients": [ { "settlement_year": 2026, "contribution_year": 2020, "coefficient": 1.05 }, ... ],
  "rate_brackets": []
}
```

### 10.3 Disclaimer (bắt buộc, thường trực) — tăng cường v1.1

Dòng chữ cố định ngay dưới tiêu đề công cụ, cùng tinh thần `anlandLoanCalculator`, **v1.1 tách 2 câu** (câu 1 = phạm vi/quyền riêng tư giữ nguyên như v1.0, câu 2 = mới, nói rõ MỨC ĐỘ tin cậy số liệu — tránh người đọc hiểu lầm "công cụ chính thức của BHXH Việt Nam"):

> "Công cụ ước tính mang tính tham khảo dựa trên Nghị định 159/2025/NĐ-CP, tính toán ngay trên trình duyệt của bạn — không gửi hoặc lưu trữ thông tin thu nhập bạn nhập.
> Đây **không phải** công cụ của Bảo hiểm xã hội Việt Nam và **không thay thế** hồ sơ/quyết định hưởng chế độ chính thức — số liệu thực tế do cơ quan Bảo hiểm xã hội Việt Nam xác định khi giải quyết hồ sơ, có thể khác kết quả ước tính ở đây do thay đổi chính sách, hệ số trượt giá, hoặc sai lệch thông tin bạn tự nhập."

Câu 2 hiển thị **đậm/màu cảnh báo** (không phải chữ xám nhạt như ghi chú phụ thông thường) — do đây là công cụ tính tiền thật ảnh hưởng quyết định tài chính cá nhân (khác `anlandLoanCalculator` vốn chỉ ước tính khoản vay, ít nhạy cảm pháp lý hơn ước tính chế độ an sinh xã hội).

### 10.4 Khi `pension_rate_brackets` chưa có số liệu (đúng thực trạng seed §8.3)

Thay vì hiển thị số ước tính lương hưu sai/bịa, UI hiển thị: mức bình quân thu nhập (đã tính đúng, không phụ thuộc bảng còn thiếu) + dòng cảnh báo "Chưa thể ước tính số tiền lương hưu cụ thể — đang chờ xác minh tỷ lệ hưởng theo Luật Bảo hiểm xã hội 2024" thay vì che giấu bằng 1 con số suy đoán.

### 10.5 Bảng breakdown Mbq theo năm (v1.1)

Thay Bước 3 (§7) chỉ hiển thị 1 số `Mbq` cuối cùng, UI hiển thị **bảng breakdown từng giai đoạn đã nhập**, đúng mẫu đối chiếu ở §13:

| Giai đoạn | Số tháng | Hệ số trượt giá | Thu nhập gốc | Thành tiền (đã điều chỉnh) |
|---|---|---|---|---|
| *(1 dòng / giai đoạn người dùng nhập ở Bước 2)* | | | | |
| **Tổng** | **Σ tháng** | | | **Σ thành tiền** |
| **Mbq = Tổng thành tiền / Tổng tháng** | | | | |

Lý do hiển thị breakdown thay vì chỉ 1 số: (1) đúng tinh thần minh hoạ/giáo dục của công cụ (người đọc hiểu ĐƯỢC cơ chế, không chỉ nhận 1 kết quả hộp đen); (2) cho phép người dùng tự phát hiện sai sót nhập liệu (VD gõ nhầm năm khiến hệ số tra sai) trước khi tin vào kết quả cuối; (3) hàng nào `missingCoefficient=true` (§10.1) hiển thị rõ ràng thay vì âm thầm tính sai.

### 10.6 Cảnh báo hết hỗ trợ nhà nước sau 120 tháng (v1.1)

Điều 5.2 Nghị định 159 giới hạn thời gian hỗ trợ tối đa **120 tháng theo thời gian tham gia BHXH tự nguyện THỰC TẾ** (không phải 120 tháng đầu tiên theo lịch nếu có gián đoạn — chỉ tính tháng THỰC ĐÓNG, xem §11 edge case 1). UI hiển thị:

- Thanh tiến trình "Đã dùng X/120 tháng hỗ trợ" ngay tại bảng dòng thời gian (Bước 2), cập nhật theo `supportMonthsUsed`.
- Khi `isSupportExhausted = true`: các dòng contribution **sau** mốc tháng thứ 120 (theo đúng thứ tự thời gian, không phải thứ tự nhập) tự động hiển thị `Mức đóng` = `22% × TN` (KHÔNG trừ `HTr`) kèm chú thích "Đã hết thời hạn hỗ trợ nhà nước (120 tháng) — mức đóng đủ 22%, không còn phần hỗ trợ" ngay trên dòng đó, thay vì lặng lẽ đổi số khiến người dùng tưởng nhầm là lỗi tính toán.
- Cảnh báo này độc lập với nhóm hỗ trợ (`supportGroup`) — áp dụng cho MỌI nhóm, kể cả nhóm "khác" (20%).

---

## 11. Testing / edge cases cần cover

1. Dòng thời gian có **khoảng trống** (VD nghỉ đóng 1 năm giữa 2 giai đoạn) — không được tự động lấp bằng 0, chỉ tính trên các tháng THỰC ĐÓNG.
2. Thu nhập chọn đóng ở **biên** (đúng 1.500.000 hoặc đúng trần hiện hành) — không lệch do làm tròn số thực JS.
3. Đổi `effective_from` — dòng thời gian nhập vào **kéo dài qua 2 giai đoạn tham số khác nhau** (VD có giai đoạn tham gia trước 01/07/2025 dùng tham số Nghị định 134/2015/NĐ-CP cũ — **ngoài phạm vi seed hiện tại**, nhưng schema phải chịu được nếu sau này bổ sung giai đoạn cũ) — mỗi dòng contribution tra đúng `PensionParameterPeriod` theo `effective_from ≤ ngày của dòng đó`, KHÔNG luôn dùng giai đoạn mới nhất.
4. Thiếu hệ số trượt giá cho 1 `contribution_year` cụ thể trong bảng — KHÔNG mặc định = 1 âm thầm (sẽ làm sai `Mbq` mà người dùng không biết), phải cảnh báo rõ "thiếu hệ số năm X" thay vì tính tiếp.
5. Nhánh (c) Điều 13 (thuần tự nguyện trước 2021) — mốc "trước 01/01/2021" phải test đúng biên (31/12/2020 vs 01/01/2021).
6. Giới tính + tuổi ảnh hưởng cả điều kiện hưởng (§6.8) lẫn tỷ lệ hưởng (§6.9, khi đã xác minh) — 2 bảng tra cứu riêng, không gộp logic.
7. Trường hợp cross-check với ảnh `2.png`/`3.png`: viết 1 test THUẦN kiểm tra cơ chế "tra hệ số theo năm × số tháng × mức lương, cộng dồn, chia tổng tháng" cho ra đúng 86.057.800 / 43 = 2.001.344,19 — xác nhận công thức mức bình quân đúng cơ chế (test dữ liệu giả lập, không phải seed thật, xem §2.3).

---

## 12. Ngoài phạm vi (out of scope) — ghi rõ để tránh hiểu nhầm khi review

- Chế độ tử tuất, trợ cấp mai táng (Điều 12, 15) — không liên quan tới minh hoạ lương hưu, thuộc nhóm chế độ khác của BHXH.
- Nộp hồ sơ hưởng chế độ thật, tích hợp API BHXH Việt Nam — công cụ thuần minh hoạ, không thay thế thủ tục hành chính.
- Tài khoản người dùng, lưu/so sánh nhiều kịch bản, xuất PDF/chia sẻ kết quả — có thể cân nhắc sau nếu có nhu cầu thật, hiện không nằm trong yêu cầu ban đầu.
- Tính BHXH **bắt buộc** từ đầu (module chỉ nhận `mandatoryAverageIncome`/`mandatoryMonths` làm input tự khai cho nhánh mixed, không tự tính theo Điều 72/73 Luật) — phạm vi ban đầu là BHXH **tự nguyện**.
- Nhúng trực tiếp vào block bài viết (`post_content_blocks`) — chỉ link từ bài viết sang trang riêng (§0).
- Tự động cập nhật hệ số trượt giá/lãi suất đầu tư quỹ hàng năm (crawl từ Cổng TTĐT BHXH Việt Nam) — admin nhập tay theo Thông tư công bố (§9), không có job tự động ở v1.

---

## 13. Test case cơ chế (không phải seed data) — đối chiếu ảnh minh hoạ §2.3

Dùng để viết Unit test cho `averageMonthlyIncome` getter, KHÔNG dùng số liệu này làm seed production:

| Giai đoạn | Số tháng | Hệ số (năm giải quyết giả lập 2017) | Mức lương gốc | Thành tiền |
|---|---|---|---|---|
| 01/2013–12/2013 | 12 | 1,08 | 1.200.000 | 15.552.000 |
| 01/2014–12/2014 | 12 | 1,03 | 2.140.000 | 26.450.400 |
| 01/2015–12/2015 | 12 | 1,03 | 2.140.000 | 26.450.400 |
| 01/2016–07/2016 | 7 | 1,00 | 2.515.000 | 17.605.000 |
| **Tổng** | **43** | | | **86.057.800** |

`Mbq` kỳ vọng = 86.057.800 / 43 = **2.001.344,19** đồng — assert chính xác giá trị này trong test.

---

## 14. Việc cần làm trước khi triển khai production (xác minh pháp lý)

> **Cập nhật v1.4:** mục 1 (tỷ lệ hưởng lương hưu — mục **chặn go-live** duy nhất) đã **RESOLVED**. Người dùng cung cấp `spec/bhxh/41-2024-qh15.pdf` (Luật Bảo hiểm xã hội 2024, số 41/2024/QH15, Công báo số 987+988 ngày 25-8-2024). Đối chiếu trực tiếp: **Điều 66** (nhánh mixed/bắt buộc) và **Điều 99** (nhánh thuần tự nguyện) dùng **CÙNG một công thức từng chữ** — 45% nền (nữ tương ứng 15 năm / nam tương ứng 20 năm) + 2%/năm tiếp theo, tối đa 75%; **riêng nam có 15-19 năm đóng dùng công thức phụ 40% nền (15 năm) + 1%/năm**, nối liền mạch đúng ở mốc 20 năm (40%+1%×5=45%, khớp bậc chính). Cấu trúc "45%+2%/năm, trần 75%" mà spec từng nghi ngờ (§0) hoá ra CHÍNH XÁC — không phải suy đoán sai. Đã seed 3 dòng vào `pension_rate_brackets` (`PensionCalculatorDemoParameterSeeder::seedRateBrackets()`), dùng CHUNG cho cả 4 nhánh (a)/(b)/(c)/(d) vì schema chỉ phân biệt theo `gender`/`years`, không phân biệt nguồn Điều 66 hay Điều 99. Đã kiểm chứng bằng harness Node chạy trực tiếp code thật: `pensionRateFor` khớp đúng mọi mốc (nữ 20 năm=55%, nam 19 năm=44%, nam 20 năm=45%, trần 75% ở nữ 30/nam 35 năm), Bước 5 + bảng minh hoạ §7.1/§15.6 nay ra số lương hưu thật thay vì "chưa xác minh".
>
> Tiện thể đối chiếu luôn 3 mục khác trong cùng file: mục 3 (Điều 104 — Mbq thuần tự nguyện = bình quân TOÀN BỘ thời gian đóng, không ngoại lệ, khớp đúng công thức rút gọn §6.7) **RESOLVED**; mục 4/4b (Điều 64→Điều 98: "đủ tuổi theo khoản 2 Điều 169 BLLĐ + đủ 15 năm đóng", không điều kiện phụ) **RESOLVED** — xác nhận đúng suy luận nhánh (d) đã có ở §6.8, tuổi nghỉ hưu cụ thể theo BLLĐ Điều 169.2 (lộ trình tăng dần theo năm, KHÔNG phải 1 số cố định — công cụ vẫn chưa tự tính được tuổi cụ thể theo lộ trình này, chỉ xác nhận ĐÚNG công thức tham chiếu); mục 2 (Điều 102 — BHXH một lần, Phase 2) **RESOLVED** cho tương lai triển khai Phase 2 (mức hưởng 1,5 tháng/năm đóng trước 2014, 2 tháng/năm đóng từ 2014). Mục 5 (Điều 21/22 — trợ cấp hưu trí xã hội) **một phần**: xác nhận điều kiện (đủ 75 tuổi, hoặc 70-75 tuổi hộ nghèo/cận nghèo) nhưng **mức `TC_htxh` cụ thể do Chính phủ quy định qua 1 Nghị định RIÊNG** (không nằm trong Luật, không phải Nghị định 159) — Phase 2 mục 15b (trợ cấp hằng tháng) **vẫn treo**, cần thêm Nghị định đó. Mục 7 (`reference_level=2.530.000`/`2026-07-01`) **KHÔNG liên quan** tới Luật 2024 (là số điều chỉnh hành chính riêng) — vẫn đứng nguyên, chưa có nguồn.
>
> **Lịch sử:** v1.2 resolved mục 6 (hệ số trượt giá, Công văn 340/BHXH-CSXH). v1.1 lần đầu liệt kê đủ 7 mục sau khi người dùng yêu cầu 3 việc seed số liệu thật nhưng chưa có nguồn đính kèm.

1. ~~**Lấy văn bản Luật Bảo hiểm xã hội 2024 thật**, xác nhận số Điều và công thức tỷ lệ hưởng lương hưu hằng tháng~~ **(RESOLVED, v1.4)** — xem khối cập nhật ở trên. Đây từng là mục **chặn go-live** duy nhất của tính năng ước tính số tiền lương hưu (mục 8 §3.1); nay đã hết chặn.
2. ~~Lấy Điều 102 Luật BHXH 2024 (mức hưởng BHXH một lần, biến `TC` ở §6.5)~~ **(RESOLVED, v1.4)** — 1,5 tháng Mbq/năm đóng trước 2014, 2 tháng Mbq/năm đóng từ 2014 trở đi (Điều 102.2). Vẫn thuộc Phase 2 (chưa lên UI/API), nhưng công thức đã sẵn sàng triển khai khi cần.
3. ~~Lấy Điều 104 Luật BHXH 2024 (mức bình quân thu nhập thuần tự nguyện)~~ **(RESOLVED, v1.4)** — xác nhận công thức rút gọn §6.7 đúng, không ngoại lệ.
4. ~~Lấy Điều 64/65 Luật BHXH 2024 (lộ trình tuổi nghỉ hưu chung + nghỉ hưu sớm do suy giảm lao động)~~ **(RESOLVED một phần, v1.4)** — Điều 64.1.a dẫn chiếu "khoản 2 Điều 169 Bộ luật Lao động" (lộ trình tuổi tăng dần theo năm, KHÔNG phải số cố định) — công cụ vẫn CHƯA tự tính được tuổi cụ thể theo lộ trình đó (cần bảng lộ trình Điều 169 BLLĐ, ngoài phạm vi tài liệu Luật BHXH này), Bước 4 vẫn hiển thị cảnh báo "chưa xác minh tuổi" cho nhánh (a)/(b)/(d) như cũ.
4b. ~~Lấy Điều 98 Luật BHXH 2024 (điều kiện hưởng lương hưu chung — nhánh (d))~~ **(RESOLVED, v1.4)** — "đủ tuổi nghỉ hưu theo khoản 2 Điều 169 BLLĐ + đủ 15 năm đóng", đúng suy luận cấu trúc đã có ở §6.8, không điều kiện phụ nào khác (không phân biệt nam/nữ về số năm).
5. Lấy Điều 21 Luật BHXH 2024 (mức trợ cấp hưu trí xã hội `TC_htxh`) nếu triển khai Phase 2 mục 14. **(một phần, v1.4)** — Điều 21/22 xác nhận điều kiện hưởng (đủ 75 tuổi, hoặc 70-75 tuổi hộ nghèo/cận nghèo) nhưng mức tiền cụ thể **do Chính phủ quy định qua 1 Nghị định riêng** (chưa có trong tài liệu), không phải số cố định trong Luật — vẫn cần thêm nguồn.
6. ~~Xác nhận với BHXH Việt Nam/Bộ Nội vụ bảng hệ số trượt giá~~ **(RESOLVED, v1.2)** — `spec/bhxh/BHXHVN_340-BHXH-CSXH_03022026.pdf` (Công văn 340/BHXH-CSXH, 03/02/2026) cung cấp bảng hệ số điều chỉnh **thu nhập** tháng đã đóng BHXH (khoản 2 Điều 10 Nghị định 159/2025/NĐ-CP — đúng công thức §6.6), đã seed đủ `settlement_year=2026`, `contribution_year` 2008-2026 vào `pension_price_index_coefficients`. Lãi suất đầu tư quỹ (dùng cho Phase 2, Điều 6/§6.2) **vẫn chưa có nguồn** — công văn này không đề cập.
7. **(mới, v1.1)** Xác nhận `reference_level = 2.530.000` áp dụng từ `2026-07-01` là con số **chính thức** (Nghị định/Quyết định điều chỉnh mức lương cơ sở nào, số hiệu, ngày ban hành) trước khi thêm 1 `PensionParameterPeriod` mới — người dùng tự ghi "(nếu đã chính thức)" nên bản thân yêu cầu đã hàm ý chưa chắc chắn 100%; thêm nhầm 1 period với ngày hiệu lực sai sẽ làm SAI mọi phép tính cho các dòng thời gian rơi vào giai đoạn 2026 trở đi (khác việc chỉ thiếu 1 bảng — đây là rủi ro SAI, không phải rủi ro THIẾU). **Không liên quan** Luật BHXH 2024 (v1.4) — vẫn đứng nguyên.

---

## 15. Phase 1.5 (khuyến nghị) — Tab "Dự báo & Tối ưu mức đóng"

**Hoàn toàn nằm ngoài phạm vi MVP** (MVP §3.1 chỉ làm forward estimation: nhập dòng thời gian THẬT → ra ước tính lương hưu). Tab này làm NGƯỢC LẠI: nhập **mục tiêu lương hưu mong muốn** → tool tự tìm mức thu nhập chọn đóng (`TN`) hoặc số năm đóng tối thiểu để đạt mục tiêu đó. Khuyến nghị thêm vì khả thi kỹ thuật cao (thuần suy ra từ các getter đã có ở MVP, không cần API/bảng dữ liệu mới) và giữ nguyên mọi quyết định đã chốt ở §0 (client-side, không lưu dữ liệu, không auth, cùng 1 file Blade+Alpine, không phá kiến trúc).

### 15.1 Vì sao dùng vòng lặp/binary search thay vì giải phương trình trực tiếp

`Lương hưu = tỷ lệ hưởng(N) × Mbq(TN)` — cả `tỷ lệ hưởng` (bậc thang theo số năm N, §6.9) lẫn `Mbq` (phụ thuộc hệ số trượt giá khác nhau mỗi năm, §6.6) đều **không phải hàm tuyến tính đơn giản của `TN`**, nhưng **đơn điệu tăng** (monotonic) theo cả `TN` và `N` trong phạm vi hợp lệ — TN cao hơn hoặc đóng nhiều năm hơn thì lương hưu không bao giờ giảm. Tính chất đơn điệu này đủ để dùng **binary search** trên khoảng `[rural_poverty_line, ceiling]` (đã có sẵn từ `activeParameterPeriod`) thay vì suy ngược đại số — đơn giản hơn, không cần đảo ngược công thức bậc thang tỷ lệ hưởng (vốn có điểm gãy tại mỗi mốc năm, khó viết dạng closed-form gọn).

### 15.2 Input / Output

**Input:** giới tính, số năm dự kiến đóng (hoặc năm dự kiến nghỉ hưu — suy ra số năm từ năm sinh), mục tiêu lương hưu hằng tháng mong muốn, nhóm hỗ trợ, số tháng ĐÃ nhận hỗ trợ trước đó (để trừ vào trần 120 tháng, tái dùng `supportMonthsUsed` §10.1).

**Output:** `TN` tối thiểu cần chọn đóng để đạt mục tiêu, mức đóng ròng hằng tháng tương ứng (đã trừ hỗ trợ nếu còn), tổng chi phí đóng ước tính trong suốt thời gian, cảnh báo nếu trong quá trình đóng sẽ chạm mốc hết hỗ trợ 120 tháng (tái dùng §10.6).

### 15.3 Thuật toán (Alpine, thêm vào cùng component `pensionCalculator`)

```js
findMinimumIncomeForTarget(targetPension, years, gender, supportGroup, priorSupportMonths) {
    const period = this.activeParameterPeriod;
    let lo = period.rural_poverty_line;
    let hi = period.reference_level * period.ceiling_multiplier;
    const EPSILON = 1000; // đồng — dừng khi khoảng tìm kiếm hẹp hơn 1.000đ, đủ chính xác cho mục đích minh hoạ

    // Giả định đơn giản hoá MVP của tab này: Mbq ≈ TN không đổi suốt N năm (người dùng nhập 1 mức
    // thu nhập MỤC TIÊU cố định, không phải dòng thời gian nhiều giai đoạn như §7) — đúng khai báo
    // ở ví dụ minh hoạ "Mbq ≈ TN" trong yêu cầu gốc. Nếu sau này cho nhập dòng thời gian không đều
    // ở tab này, cần đổi hàm ước lượng Mbq(TN) sang gọi lại §6.6/§6.7 đầy đủ thay vì xấp xỉ thẳng.
    const estimatedPensionFor = (tn) => {
        const rate = this.pensionRateFor(gender, years); // §6.9 — CẦN pension_rate_brackets đã seed
        if (rate === null) return null; // chưa xác minh tỷ lệ hưởng — không đoán
        return rate * tn;
    };

    if (estimatedPensionFor(hi) === null) {
        return { needsVerifiedRateTable: true }; // §14 mục 1 chưa xong — không chạy binary search mù
    }
    if (estimatedPensionFor(hi) < targetPension) {
        return { achievable: false, maxPossiblePension: estimatedPensionFor(hi) }; // mục tiêu vượt trần, kể cả TN tối đa
    }

    while (hi - lo > EPSILON) {
        const mid = (lo + hi) / 2;
        if (estimatedPensionFor(mid) >= targetPension) hi = mid; else lo = mid;
    }

    const requiredIncome = hi;
    return {
        achievable: true,
        requiredIncome,
        monthlyContribution: this.monthlyContributionForIncome(requiredIncome, supportGroup, priorSupportMonths), // §6.1 + §10.6
        totalCostEstimate: this.monthlyContributionForIncome(requiredIncome, supportGroup, priorSupportMonths) * years * 12, // xấp xỉ tuyến tính, KHÔNG chiết khấu/lạm phát — nêu rõ trong UI là ước tính danh nghĩa (nominal), không quy đổi giá trị hiện tại
    };
},
```

### 15.4 Xử lý phi tuyến — 3 điểm phải làm đúng, không đơn giản hoá quá mức

1. **Tách giai đoạn "còn hỗ trợ" và "hết hỗ trợ"** trong `monthlyContributionForIncome`: nếu `priorSupportMonths + years×12 > 120`, phần tháng vượt mốc 120 tính `22% × TN` KHÔNG trừ hỗ trợ (§10.6) — `totalCostEstimate` phải cộng 2 đoạn riêng, không nhân đơn giá 1 mức đóng cho toàn bộ `years×12` tháng.
2. **Clamp trần/sàn:** `requiredIncome` phải nằm trong `[rural_poverty_line, reference_level × ceiling_multiplier]` — nếu binary search hội tụ ngoài khoảng do lỗi làm tròn, clamp lại trước khi hiển thị, không hiển thị số âm hoặc vượt trần.
3. **"Special rate nam 15-19 năm"** (nhắc trong yêu cầu gốc): nếu nhánh (d) (§6.8, mới v1.1) xác nhận nam chỉ cần 15 năm (giảm từ 20 năm luật cũ) thì tỷ lệ hưởng cho nam ở mốc **15-19 năm** rất có thể có 1 đường cong RIÊNG, không đơn giản là "45% + 2%/năm" áp dụng nguyên trạng từ mốc 20 năm cũ lùi xuống 15 — đây CHÍNH LÀ số liệu còn thiếu ở `pension_rate_brackets` (§14 mục 1). `pensionRateFor(gender, years)` phải tra bảng theo `(gender, years)` cụ thể — KHÔNG suy diễn tuyến tính giữa 2 mốc đã biết cho quãng 15-19 năm nam nếu bảng chưa có dữ liệu đúng quãng đó, phải trả `null` (chưa xác minh) thay vì nội suy phỏng đoán.
4. **Sàn mức tham chiếu khi mixed** (§6.8, "sàn bảo vệ"): nếu input Bước 1 (§7) khai có ≥20 năm BHXH bắt buộc, `estimatedPensionFor` phải áp `Math.max(rate × tn, reference_level)` — nếu không, binary search có thể trả `requiredIncome` thấp hơn mức cần thiết thật (vì tưởng lương hưu tăng theo `TN` trong khi thực ra bị chặn sàn tham chiếu ở khoảng thấp).

### 15.5 Ví dụ minh hoạ (đối chiếu, KHÔNG phải seed data — cùng cảnh báo §14 mục 1)

Bảng dưới đây do người dùng cung cấp làm ví dụ mong muốn đạt được (nữ, không thuộc diện hỗ trợ, giả định `Mbq ≈ TN` không đổi, mục tiêu 3.000.000đ/tháng) — **dùng để viết test case cho `findMinimumIncomeForTarget`, KHÔNG dùng làm căn cứ hiển thị số thật cho người dùng cuối cùng cho tới khi `pension_rate_brackets` được seed đúng (§14 mục 1)**, vì toàn bộ bảng này suy từ đúng cấu trúc "45% nền + 2%/năm, trần 75%" mà spec đã nhiều lần nhấn mạnh là CHƯA xác minh với Luật BHXH 2024 thật:

| Số năm đóng | Tỷ lệ (giả định, chưa xác minh) | TN tối thiểu cần (≈đ) | Mức đóng/tháng (≈đ) |
|---|---|---|---|
| 15 | 45% | ≈ 6.667.000 | ≈ 1.467.000 |
| 20 | 55% | ≈ 5.455.000 | ≈ 1.200.000 |
| 25 | 65% | ≈ 4.615.000 | ≈ 1.015.000 |
| 30 | 75% | ≈ 4.000.000 | ≈ 880.000 |

Assert trong test: với `pensionRateFor` giả lập trả đúng 4 cặp `(years, rate)` ở trên, `findMinimumIncomeForTarget(3_000_000, years, 'female', 'other', 0).requiredIncome` hội tụ về đúng giá trị cột 3 (sai số ≤ `EPSILON`).

### 15.6 Bảng minh hoạ theo tuổi bắt đầu tham gia (v1.3, bổ sung)

Người dùng cung cấp 1 ảnh minh hoạ mẫu của 1 đại lý BHXH ("MINH HOẠ LƯƠNG HƯU — NAM 37T, NỮ 37T") liệt kê nhiều mức `TN` và lương hưu tương ứng cho Nam/Nữ. Đối chiếu ngược tỷ lệ hưởng ngụ ý trong ảnh ra **~76,07% (Nữ) / ~72,89% (Nam)**, cả hai đều **vượt trần 75%** của cấu trúc phổ thông "45% nền + 2%/năm" — dấu hiệu rõ đây là tài liệu minh hoạ/marketing của đại lý (khớp dòng disclaimer cuối ảnh "có thể cao/thấp hơn thực tế"), KHÔNG phải số đã đối chiếu Điều 66/99 Luật BHXH 2024. Quyết định: **KHÔNG** copy nguyên tỷ lệ suy ngược từ ảnh vào `pension_rate_brackets` — vi phạm đúng nguyên tắc "không bịa số liệu pháp luật" đã áp dụng xuyên suốt spec này (§0, §14 mục 1).

Thay vào đó, xây **cơ chế thật**, chờ số liệu Điều 66/99 thật:

- UI (`illustrationResultFor`, tab Phase 1.5) tái dùng ĐÚNG `findMinimumIncomeForTarget` (§15.3) cho mỗi giới — KHÔNG viết công thức tỷ lệ hưởng riêng cho bảng này.
- Input: tuổi bắt đầu tham gia (chỉ để hiển thị, không dùng để tính), **số năm dự kiến đóng nhập tay riêng cho từng giới** (`yearsFemale`/`yearsMale`) — KHÔNG tự suy số năm từ "tuổi bắt đầu + tuổi nghỉ hưu" vì lộ trình tuổi nghỉ hưu (Điều 64/65) chưa xác minh (§14 mục 4/4b), cùng lý do Phase 1.5 gốc (§15.2) đã chọn nhận "số năm dự kiến đóng" trực tiếp thay vì suy từ năm sinh.
- Nhiều dòng mục tiêu lương hưu (thêm/xoá được, giống UX `contributionRows`), mỗi dòng ra 2 bộ kết quả (Nữ/Nam): TN cần đóng, phí/tháng, phí/năm (= phí/tháng × 12).
- Khi `pension_rate_brackets` rỗng (đúng thực trạng hiện tại) → mọi ô kết quả hiện **"Chưa xác minh tỷ lệ hưởng (§14 mục 1)"** thay vì số — đã kiểm chứng bằng cách tạm seed 1 bảng tỷ lệ giả lập (mirror bảng test §15.5) vào Node harness: cơ chế hội tụ đúng số (`5.454.545đ` cho nữ/20 năm/mục tiêu 3.000.000đ, khớp §15.5) — nghĩa là ngay khi có nguồn Điều 66/99 thật và seed qua trang admin (`/dashboard/pension-calculator/rate-brackets/create`), bảng này (và Bước 5) tự động ra số thật, không cần sửa code.
- **Sửa kèm:** `findMinimumIncomeForTarget` trước đó đọc trực tiếp `this.optimizer.hasMandatory20Years` (state của form "Dự báo & Tối ưu" phía trên) để áp sàn mức tham chiếu — gây dính chéo trạng thái nếu tái dùng cho bảng minh hoạ này. Đã đổi thành tham số `applyMandatoryFloor` tường minh (mặc định `false`), `runOptimizer()` truyền `this.optimizer.hasMandatory20Years` vào, còn `illustrationResultFor` luôn truyền `false` (cohort giả định thuần tự nguyện mới bắt đầu).
