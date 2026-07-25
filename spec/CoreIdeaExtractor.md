# CoreIdeaExtractor

**Version:** 1.4  
**Last Updated:** 2026-07-25  
**Status:** Design Specification (Ready for Implementation)

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
  "notes": "string | null"
}
```

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
  "error": "boolean"
}
```

- `error`: luôn có mặt, mặc định `false`. Chỉ `true` khi không thể trích xuất được nội dung tối thiểu (xem §9).
- Khi `extraction_confidence = "low"`: `article_type`, `thesis`, `main_sections`, `core_ideas`, `writing_inspiration` LUÔN là `null` (Layer 2 không chạy) — `title`/`language` vẫn giữ giá trị nếu Layer 1 lấy được (chỉ `null` khi hoàn toàn không lấy được gì, xem §9).

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
- Field: `core_focus`, `unique_angle`, `content_goals` (3 thành phần Business Foundation ánh xạ sang ngữ cảnh biên tập) + `audience`, `constraints`, `style_sample` (persist hoá field ad-hoc đã có).
- **Không đổi Layer 1/Layer 2 JSON schema (§5, §7)** — foundation chỉ dùng để prefill form và dựng prompt ở tầng UI, không bao giờ chèn vào JSON output.
- Quyền sửa foundation của 1 category: `platform_content_editor`/`platform_content_head` sửa được mọi category (giữ nguyên quyền `core_idea_extractor.use` không giới hạn hiện có); `platform_section_editor` chỉ sửa được category mình được gán qua `post_category_editors` — cùng pattern với `PostArticlePolicy::approve()`. Implement bằng `Gate::define('core_idea_extractor.manage_category_foundation', ...)` (KHÔNG phải `Policy` gắn vào `PostCategory`, vì `Post` module đã đăng ký `PostCategoryPolicy` cho chính model đó — đăng ký thêm 1 policy nữa sẽ ghi đè lẫn nhau).
- UI: trang quản lý riêng (`/dashboard/core-idea-extractor/category-foundations`) để CRUD foundation theo từng category; trang trích xuất chính thêm `<select>` chuyên mục — khi chọn, tự prefill `audience/goal/constraints/style_sample` (vẫn tự sửa được, không khoá field) + nút "Copy prompt cho AI" bọc JSON + foundation + 3 câu hỏi lọc ý tưởng (bản dịch sang ngữ cảnh biên tập) thành 1 prompt dán thẳng vào chat AI.

### 12.3 Ngoài phạm vi (v1.4)

- Không tự động kéo tag/bài viết hiện có của category vào prompt (tránh trùng nội dung đã viết) — để dành cho lần lặp sau (§11).
- Không tự động hoá Layer 2 (gọi AI Provider thật) — module vẫn là công cụ nghiên cứu, copy tay vào chat AI, đúng triết lý hiện có.

---

**End of Specification**
