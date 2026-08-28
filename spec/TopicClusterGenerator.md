Dưới đây là tài liệu Đặc tả Kỹ thuật (Technical Specification) chi tiết cho module sinh prompt xây dựng cụm chủ đề (Topic Cluster).

> **Ghi chú triển khai (2026-08-28):** tài liệu dưới đây mô tả thiết kế GỐC (module/bảng riêng). Khi triển khai thực tế, tính năng này KHÔNG được xây thành module riêng trong `CoreIdeaExtractor` như §1-3 mô tả — codebase đã có sẵn `Modules\PromptFrameworkStudio` giải quyết đúng bài toán "form sinh prompt tĩnh + tự chèn ngữ cảnh biên tập theo chuyên mục từ `ContentFoundation` + copy-paste, không gọi AI, có audit lịch sử qua bảng `generated_prompts`". Tính năng được thêm dưới dạng 1 template mới `topiccluster` trong nhóm "Chiến lược nội dung" của `Modules/PromptFrameworkStudio/config/prompt_framework_studio.php` (chỉ 2 field `seed_keyword`/`specific_context` — KHÔNG có field category/ICP/brand_voice/product_service_docs riêng như §2 vì `PromptFrameworkStudio` đã tự inject khối "Bối cảnh biên tập" từ `ContentFoundation` cho MỌI framework khi người dùng chọn chuyên mục, xem `BuildEditorialContextBlockAction`). Vì vậy: KHÔNG có bảng `topic_cluster_logs` riêng (§3) — lịch sử dùng chung bảng `generated_prompts` đã có; KHÔNG có route/controller/view/menu riêng cho khâu SINH prompt — dùng chung route/menu "Prompt Studio" đã có. Nội dung §2-5 bên dưới chỉ còn giá trị tham khảo về Ý ĐỊNH sản phẩm (4 bước quy trình, ví dụ), không phản ánh đúng cấu trúc code thật.
>
> **Cập nhật (2026-08-28, phản hồi review):** bổ sung field `business_goal` (điểm chạm thương mại), nhãn EEAT, sơ đồ liên kết nội bộ, và guardrail 4 giá trị gia đình gắn thẳng vào bước Query Fan-out. Đồng thời thêm 1 khâu MỚI ngoài phạm vi thiết kế gốc: "duyệt kết quả AI theo từng mục (Pillar/Cluster) rồi đẩy sang `Modules\ContentOutlines`" — khác nguyên tắc "không gọi AI trong app" nên KHÔNG dùng AI Provider để trích xuất cấu trúc, mà ép prompt sinh kèm 1 khối máy đọc được (`PILLAR: ... | ...` / `CLUSTER: ... | ...`) để người dùng dán kết quả AI vào, hệ thống dùng regex tách (`ParseTopicClusterAiResultAction`) — người dùng tick chọn mục muốn giữ rồi mới tạo `ContentOutline` nháp (`PushTopicClusterItemsToContentOutlinesAction`). Khâu này CÓ bảng riêng `topic_cluster_results` (1-1 với `generated_prompts`) và route/controller/view riêng (`TopicClusterResultController`, `prompts/{prompt}/topic-cluster-result`) vì đây là nghiệp vụ CHỈ framework `topiccluster` mới có (Pillar/Cluster, đẩy sang module khác) — không tổng quát hoá được vào cơ chế config-driven chung của `PromptFrameworkStudio` như phần sinh prompt.

---

# ĐẶC TẢ KỸ THUẬT: MODULE `TopicClusterPromptStudio`

## 1. Mục tiêu Module

Tạo ra một công cụ nội bộ không trạng thái (stateless tool) cho phép người dùng nhập từ khóa/ngữ cảnh và hệ thống sẽ tự động lắp ghép với các hằng số (Constants) từ `ContentFoundation` để xuất ra một câu lệnh (Prompt) hoàn chỉnh. Người dùng sẽ copy câu lệnh này dán vào ChatGPT hoặc Claude để nhận về cấu trúc Cụm chủ đề (Topic Cluster) chuẩn SEO AI.

## 2. Thiết kế Giao diện Đầu vào (Input UI)

Giao diện (View) cần có form bao gồm các trường sau:

* **Chuyên mục / Hệ quy chiếu (Category):** Dropdown select (Lấy từ bảng `ContentFoundation` để kế thừa `ICP`, `brand_voice`, `product_service_docs`).
* **Từ khóa hạt giống (Seed Keyword):** Text Input (Bắt buộc). Ví dụ: *bỉm cho bé, ăn dặm, đi đẻ*.
* **Ngữ cảnh / Nỗi đau đặc thù (Specific Context):** Textarea (Tùy chọn). Ví dụ: *Tập trung vào các mẹ bỉm sữa bận rộn, không có thời gian, sợ con dị ứng*.
* **Nút "Tạo Prompt":** Bấm vào sẽ render ra chuỗi văn bản (String) theo template bên dưới.

## 3. Cấu trúc Database (Tùy chọn lưu lịch sử)

Mặc dù công cụ chủ yếu sinh text để copy, bạn nên có một bảng `topic_cluster_logs` đơn giản để lưu vết (audit) xem nhân viên đã tạo các cụm chủ đề nào.

* `id`: bigIncrements
* `category_id`: foreign key (liên kết `ContentFoundation`)
* `seed_keyword`: string
* `specific_context`: text (nullable)
* `generated_prompt`: longText
* `created_at`, `updated_at`: timestamps

## 4. Cấu trúc Prompt Template (Backend Logic)

Dưới đây là nội dung prompt cốt lõi. Developer sẽ sử dụng Laravel Blade hoặc cơ chế String Replace để thay thế các biến `{{...}}` bằng dữ liệu thực tế từ Input và Database.

*Định dạng để Dev đưa vào code (Ví dụ: `resources/views/prompts/topic_cluster.blade.php`):*

```text
Bạn là một chuyên gia Content SEO & AI Search Strategist xuất sắc. Nhiệm vụ của bạn là xây dựng một mạng lưới Cụm chủ đề (Topic Cluster) toàn diện dựa trên triết lý "Query Fan-out" để tối ưu hóa khả năng được trích dẫn bởi các công cụ tìm kiếm AI (Google AI Overview, ChatGPT, Perplexity).

## THÔNG TIN ĐẦU VÀO CỐT LÕI
- Từ khóa/Chủ đề hạt giống: "{{ $seedKeyword }}"
- Ngữ cảnh bổ sung/Vấn đề trọng tâm: "{{ $specificContext ?? 'Tự do khai thác theo dữ liệu người dùng tìm kiếm thực tế' }}"

## BỐI CẢNH THƯƠNG HIỆU (KHÔNG được đổi)
- Đối tượng khách hàng mục tiêu (ICP): "{{ $contentFoundation->icp }}"
- Giọng văn thương hiệu: "{{ $contentFoundation->brand_voice }}"
- Thông tin sản phẩm/dịch vụ cốt lõi: "{{ $contentFoundation->product_service_docs }}"

## QUY TRÌNH THỰC HIỆN BẮT BUỘC
Hãy phân tích và trả về kết quả tuân thủ NGHIÊM NGẶT 4 bước sau:

**Bước 1: Dự đoán Xu hướng & Khoảng trống (Emerging Topics)**
- Bỏ qua các từ khóa tĩnh nhàm chán. Hãy suy luận 3-4 nỗi đau (pain points) MỚI NHẤT hoặc sâu kín nhất của đối tượng mục tiêu liên quan đến chủ đề hạt giống mà các công cụ nghiên cứu từ khóa (Ahrefs, Semrush) có thể chưa kịp hiển thị.

**Bước 2: Phân rã truy vấn (Query Fan-out)**
- Từ các khoảng trống ở Bước 1 và chủ đề hạt giống, phân rã thành một Mạng lưới ít nhất 10-15 câu hỏi NGÁCH thực tế. Viết dưới dạng ngôn ngữ tự nhiên mà người dùng thực sự gõ/nói với AI (Ví dụ: thay vì "cách trị hăm", hãy dùng "đóng bỉm ban đêm mấy tiếng thì bé bị hăm").

**Bước 3: Gom Cụm Chủ Đề (Clustering)**
Tổ chức các câu hỏi ở Bước 2 thành 1 Cây Nội Dung (Content Tree) bao gồm:
- 1 Chủ đề Trụ cột (Pillar Content): Bài viết cốt lõi, dài, bao quát toàn bộ vấn đề. Đề xuất Tiêu đề H1.
- 3-5 Chủ đề Nhánh (Cluster Content): Các bài viết chi tiết, khoét sâu vào từng nhánh của Pillar. Đề xuất Tiêu đề H1 cho mỗi nhánh.
- Gắn từng câu hỏi ở Bước 2 vào đúng bài Pillar hoặc bài Cluster tương ứng để làm H2/H3.

**Bước 4: Thiết kế trích xuất AI (Format for Extractability)**
- Với mỗi bài Pillar và Cluster ở Bước 3, HÃY CHỈ ĐỊNH ĐỊNH DẠNG TRÌNH BÀY (Format) bắt buộc cho 1-2 thẻ H2 quan trọng nhất để AI dễ trích xuất nhất. (Ví dụ: Chỗ nào bắt buộc dùng Bảng so sánh, chỗ nào dùng Danh sách Bullet, chỗ nào dùng quy tắc Answer-First / BLUF).

## ĐỊNH DẠNG ĐẦU RA
Trả về 1 khối Markdown duy nhất, sử dụng định dạng rõ ràng, scannable (dễ đọc lướt) với cấu trúc:
### 1. Khoảng trống nội dung (Gap Analysis)
### 2. Sơ đồ Query Fan-out
### 3. Cấu trúc Cụm chủ đề (Pillar & Cluster Mapping) kèm Gợi ý Định dạng Trích xuất (Extractability Format)
Không cần giải thích dài dòng, hãy đi thẳng vào kết quả phân tích.

```

## 5. Hướng dẫn sử dụng cho Luồng Vận hành

1. **Nhân viên Content** truy cập vào Module trên hệ thống nội bộ.
2. Chọn chuyên mục tương ứng (ví dụ: `Thai kỳ` hoặc `Chăm sóc trẻ nhỏ`).
3. Nhập từ khóa (ví dụ: `bỉm cho bé`).
4. Bấm nút. Hệ thống sẽ kết xuất khối văn bản Prompt ở mục 4 (đã điền sẵn các thông số ngầm của thương hiệu).
5. Nhân viên bấm nút "Copy to Clipboard", sau đó dán vào ChatGPT (bản Plus/Team) hoặc Claude (bản Pro) để nhận về bản kế hoạch Topic Cluster cực kỳ chi tiết, vượt xa mức độ của các bài SEO truyền thống.

Cấu trúc này giữ cho hệ thống của bạn hoàn toàn tách biệt khỏi phí API và rủi ro gián đoạn dịch vụ từ bên thứ ba, đồng thời nâng cấp ngay lập tức chất lượng tư duy dàn ý của đội ngũ biên tập.