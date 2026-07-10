# AICEM — Demo & Walkthrough

Tài liệu này hướng dẫn **chạy dữ liệu demo** và **đi qua từng bước** để hiểu trọn luồng module
AICEM (AI Context Engineering Module). Dữ liệu demo được tạo bởi
`Modules\Aicem\Database\Seeders\AicemDemoDataSeeder` — đi qua **đúng các Action thật** của hệ
thống (không insert thẳng DB), nên những gì bạn thấy phản ánh đúng hành vi production.

> Tham chiếu đặc tả: `spec/AICEM_Technical_Specification.md`. Các số mục (VD "mục 6.8") bên dưới
> trỏ tới tài liệu đó.

---

## 1. Chạy demo

```bash
# 1) Seed dữ liệu demo (idempotent — chạy lại không tạo trùng)
php artisan db:seed --class="Modules\Aicem\Database\Seeders\AicemDemoDataSeeder"

# 2) BẮT BUỘC nếu muốn nút "Chạy AI" hoạt động: chạy queue worker ở terminal riêng
#    (QUEUE_CONNECTION=database → job phải có worker xử lý, không tự chạy)
php artisan queue:listen --tries=1

# hoặc chạy nguyên bộ dev (server + queue + logs + vite) bằng 1 lệnh:
composer dev
```

Seeder tự in ra một **bảng hướng dẫn thao tác** kèm URL trực tiếp sau khi chạy — tài liệu này là
bản chi tiết hơn của bảng đó.

### Điều kiện để "Chạy AI" hoạt động thật

| Thiếu gì | Hậu quả |
|---|---|
| Không có queue worker | Bấm "Chạy AI" → panel poll mãi trạng thái `pending`/`running`, **treo vô tận** |
| Có worker, chưa có API key | Run chạy xong nhanh và chuyển `failed` với thông báo lỗi rõ ràng (không treo) |
| Có worker + API key thật | Run `succeeded`, nhận đề xuất mới trên panel |

Nhập API key thật tại trang **Cấu hình AICEM** (`admin@demo.test`) hoặc set
`AI_DEFAULT_PROVIDER` / `AI_DEFAULT_MODEL` / `AI_DEFAULT_API_KEY` trong `.env`.

---

## 2. Tài khoản demo

Tất cả mật khẩu: **`password`**. Seeder tự gán role Spatie khớp tên email nếu user chưa có role.

| Email | Role | Quyền AICEM (mục 12) | Dùng để xem gì |
|---|---|---|---|
| `marketing@demo.test` | Marketing | `aicem.use` | Chạy workflow, accept/reject đề xuất, panel AI trên bài viết/sản phẩm |
| `ai_op@demo.test` | AI Operator | `aicem.config_prompt` | Sửa Knowledge Base + version/rollback, duyệt ví dụ mẫu |
| `admin@demo.test` | System Admin | `aicem.config` (+ tất cả) | Cấu hình provider/API key/hạn mức |
| `ceo@demo.test` | CEO | `aicem.view` | Dashboard tổng quan (read-only) |
| `ops@demo.test` | Ops | `aicem.view` | Xem read-only |
| `sales@demo.test`, `hr@demo.test`, `viewer@demo.test` | — | (không có quyền AICEM) | Không thấy menu AICEM — minh hoạ permission gating |

---

## 3. Bản đồ dữ liệu demo

| Nhóm | Số lượng | Chi tiết |
|---|---|---|
| Knowledge documents | 14 | Đủ 3 tầng context + example_good/bad + custom_note + tri thức Product |
| Post categories | 2 | `an-toan-giac-ngu`, `hoat-dong-gia-dinh` |
| Bài viết | 5 | 2 bài minh hoạ scope (mục 6.8), 1 bài run-failed, 2 bài sinh example_candidate |
| Sản phẩm | 2 | 1 premium, 1 budget (minh hoạ `price_tier` taxonomy) |
| Workflow | 3 | `headline`, `seo_audit`, `full_optimization` (từ `AicemDefaultWorkflowSeeder`) |
| Generation runs | 4 | Đủ trạng thái: succeeded (cache miss/hit), failed |
| Suggestions | 4 | pending, accepted, rejected, stale |
| Example candidates | 3 | pending, approved, rejected |
| Budget | $20/tháng | `settled_usd ≈ 0.05` để dashboard có số |

### 3 tầng Knowledge Base (mục 5.1) trong demo

- **Tầng 1 — DNA chung toàn tổ chức** (`scope = null`, không gắn `subject_type`):
  `Giọng văn biên tập`, `Quy chuẩn thương hiệu`, `Đối tượng đọc chính`.
- **Tầng 2 — chuyên môn theo `subject_type`** (`scope = null`): `Checklist E-E-A-T chung`,
  `Quy tắc SEO chung` (cho `post_article`); `Quy định quảng cáo sản phẩm` (cho `product`).
- **Tầng 3 — theo `scope` cụ thể** (khác nhau theo từng bài/sản phẩm):
  - `E-E-A-T bổ sung: an toàn giấc ngủ` → `scope: {category_slugs: [an-toan-giac-ngu]}`
  - `Văn phong định dạng Mẹo hay (tip)` → `scope: {format: [tip]}`
  - `Copy chuyển đổi cho sản phẩm cao cấp` → `scope: {price_tier: [premium]}`
  - `Hiển thị giá cho sản phẩm giá tốt` → `scope: {price_tier: [budget]}`

---

## 4. Luồng module (nhìn tổng thể)

```
[Marketing mở trang sửa bài] 
        │  panel AI hiển thị workflow khả dụng + dòng "bối cảnh" (taxonomy) của bài
        ▼
[Bấm 1 workflow] ──► AicemGenerationController@run
        │  tạo AicemGenerationRun(status=pending) + dispatch RunAicemWorkflowJob (queue)
        ▼
[Queue worker xử lý job nền]
        │  BuildPromptAction  ── ResolveApplicableKnowledgeAction (chọn KB theo scope + taxonomy)
        │  CheckAndReserveBudgetAction (reserve trần chi phí — mục 13.1)
        │  AIProviderManager::complete()  (gọi model; đánh dấu khối DNA cacheable — Phase 6)
        │  ValidateSuggestionsAction (lọc đề xuất rác — mục 6.9.2)
        │  PersistSuggestionsAction + reconcile budget
        ▼
[Panel poll status mỗi 4s] ──► khi succeeded, hiện danh sách đề xuất (diff gốc↔AI)
        │
        ▼
[Editor bấm Chấp nhận] ──► AcceptSuggestionAction
        │  guard staleness (mục 9.1): nội dung hiện tại phải khớp original_text
        │  ghi qua Action gốc của module (UpdateArticleAction / UpdateProductAction)
        ▼
[Nội dung bài viết/sản phẩm đổi thật]
```

Điểm cốt lõi: **AICEM chỉ advisory** — không action nào tự ghi đè bài viết; editor duyệt từng đề
xuất. Mọi thao tác ghi đi qua Action gốc của module chỉ định (Post/Product), không phải AICEM tự
viết logic cập nhật.

---

## 5. Kịch bản đi qua từng bước

### Kịch bản 1 — Cơ chế scope: cùng loại bài, khác Knowledge Base (mục 6.8)

> Login `marketing@demo.test`. **Không cần chạy AI** — chỉ quan sát dòng bối cảnh.

1. Mở bài **"Cách chọn nệm an toàn cho trẻ sơ sinh"** (category `an-toan-giac-ngu`, format
   `article`). Cuộn xuống panel **Trợ lý AI (AICEM)** → dòng bối cảnh hiện
   `category_slugs: an-toan-giac-ngu · format: article`.
2. Mở bài **"5 hoạt động vui chơi cuối tuần cho cả nhà"** (format `tip`) → dòng bối cảnh khác.
3. **Ý nghĩa:** khi chạy workflow, `ResolveApplicableKnowledgeAction` đối chiếu `scope` của từng
   tài liệu với `taxonomy()` của bài:
   - Bài "nệm an toàn" **nhận thêm** doc `E-E-A-T bổ sung: an toàn giấc ngủ` (khớp
     `category_slugs`) → prompt yêu cầu dẫn nguồn AAP + cảnh báo SIDS.
   - Bài "5 hoạt động" **nhận thêm** doc `Văn phong Mẹo hay (tip)` (khớp `format`) → prompt yêu
     cầu liệt kê dạng số, câu ngắn.
   - Cùng 1 workflow, cùng `subject_type`, nhưng 2 bài ra 2 bộ ngữ cảnh khác nhau — **tự động**.

> Kiểm chứng nhanh bằng code (tùy chọn): xem `ResolveApplicableKnowledgeAction` trả về mấy doc
> `eeat_checklist` cho từng bài — bài "nệm" ra 2 (chung + scope), bài "hoạt động" ra 1 (chỉ chung).

### Kịch bản 2 — Accept / Reject / Stale / Failed (Phase 3 + mục 9.1)

> Login `marketing@demo.test`. Dữ liệu suggestion **đã seed sẵn** — bấm thử ngay, không cần chạy AI.

- Bài **"nệm an toàn"** → có 1 đề xuất **PENDING** cho `title`. Bấm **Chấp nhận** → tiêu đề bài
  đổi thật (qua `UpdateArticleAction`); hoặc **Từ chối**. (Bài này cũng đã có sẵn 1 đề xuất
  `seo_description` trạng thái **ACCEPTED** ở lịch sử run trước.)
- Bài **"5 hoạt động"** → có 1 đề xuất **REJECTED** (block text) và 1 đề xuất **STALE** cho
  `excerpt`. Badge *"Đã thay đổi — chạy lại AI"* minh hoạ guard staleness: nội dung thật đã đổi so
  với lúc AI phân tích nên **không cho accept trực tiếp** (mục 9.1).
- Bài **"Có nên cho trẻ dùng thiết bị điện tử trước 2 tuổi?"** → run **FAILED**, panel hiển thị
  thông báo lỗi API key rõ ràng thay vì treo.

### Kịch bản 3 — Knowledge Base CRUD + version/rollback (Phase 2)

> Login `ai_op@demo.test` → menu **AICEM → Knowledge Base**.

1. Xem 14 tài liệu (lọc theo type/subject_type). Nhận ra 3 tầng ở mục 3.
2. Mở 1 tài liệu bất kỳ → sửa nội dung → **Lưu thay đổi**.
3. Mở lại → khối **"Lịch sử phiên bản"** liệt kê version cũ → bấm **Khôi phục**. Lưu ý: khôi phục
   *tạo thêm 1 version mới* (không xoá lịch sử) — chính thao tác rollback cũng được audit.
4. Thử tạo tài liệu mới với `type`/`subject_type` sai cặp (VD `eeat_checklist` + `product`) → bị
   chặn kèm thông báo rõ (registry validate — mục 6.3.1).

### Kịch bản 4 — Duyệt ví dụ mẫu tự động (Phase 5)

> Login `ai_op@demo.test` → menu **AICEM → Duyệt ví dụ mẫu**.

- Mỗi khi 1 bài viết `is_featured=true` được publish, listener thật tự sinh 1 **candidate**
  `example_good` ở trạng thái **pending** (không ghi thẳng vào Knowledge Base).
- Demo có 3 candidate: **pending**, **approved**, **rejected** (đổi bộ lọc trạng thái để xem cả 3).
- Bấm **Duyệt** trên candidate pending → tạo ra 1 Knowledge Document `example_good` **thật** (qua
  `CreateKnowledgeDocumentAction`) → quay lại Knowledge Base sẽ thấy tài liệu mới.

### Kịch bản 5 — Dashboard + Cấu hình (Phase 4 + 6)

- **CEO** (`ceo@demo.test`) → **AICEM → Tổng quan**: số run tháng này, chi phí, **prompt-cache
  tokens** (token đọc từ cache Anthropic — Phase 6), top workflow, 20 run gần nhất.
- **System Admin** (`admin@demo.test`) → **AICEM → Cấu hình**:
  - Provider AI **BYOK** (chọn Anthropic/OpenAI + model + API key riêng của tổ chức).
  - **Hạn mức chi phí** $20/tháng (để trống = không giới hạn) — cơ chế reserve/reconcile chống
    race ở mục 13.1.
  - **Rate limit** theo user (để trống = dùng mặc định config).

### Kịch bản 6 — Panel AI trên Sản phẩm (module chỉ định thứ 2)

> Login `marketing@demo.test` → **Sản phẩm & Dịch vụ → Danh sách → sửa 1 sản phẩm**.

- Panel AI dùng chung **cùng 1 component** cũng xuất hiện dưới trang sửa sản phẩm — minh hoạ AICEM
  không viết cứng theo Post: chỉ cần 1 resolver + 1 dòng registry là gắn được module mới.
- Taxonomy của sản phẩm là `price_tier` (suy ra từ giá): SP **premium** (2.5tr) nhận doc
  `Copy chuyển đổi cao cấp`, SP **budget** (89k) nhận doc `Hiển thị giá cho sản phẩm giá tốt`.

### Kịch bản 7 — Chạy AI thật đầu-cuối (cần queue + API key)

1. Chạy `php artisan queue:listen --tries=1` ở terminal riêng.
2. Login `admin@demo.test` → **Cấu hình AICEM** → nhập API key thật (Anthropic hoặc OpenAI).
3. Login `marketing@demo.test` → mở 1 bài viết → panel AI → bấm 1 workflow (VD **Tối ưu tiêu đề**).
4. Banner *"Đang xử lý AI..."* → panel tự poll → khi xong hiện đề xuất mới → Chấp nhận/Từ chối.
5. Quay lại Dashboard xem run mới + chi phí + token cache tăng lên.

---

## 6. Ghi chú

- **Idempotent:** seeder kiểm tra tồn tại theo tiêu đề/tên trước khi tạo — chạy lại nhiều lần
  không nhân đôi dữ liệu.
- **Không gọi AI thật khi seed:** các generation run/suggestion demo được tạo sẵn với token/chi
  phí là số minh hoạ (tính qua `CostCalculator` thật để nhất quán công thức). Chỉ khi bạn tự bấm
  "Chạy AI" trên UI (Kịch bản 7) mới thực sự gọi model.
- **Dọn dữ liệu demo** (nếu cần chạy lại từ đầu) — xoá theo Organization demo:
  ```php
  // tinker, chạy trong TenantContext của org demo:
  //   $org = \App\Shared\Tenancy\Models\Organization::where('is_system',false)->orderBy('id')->first();
  //   \App\Shared\Tenancy\TenantContext::runForOrganization($org, function () { /* các lệnh dưới */ });
  \Modules\Aicem\Models\AicemSuggestion::query()->delete();
  \Modules\Aicem\Models\AicemGenerationRun::withTrashed()->get()->each->forceDelete();
  \Modules\Aicem\Models\AicemExampleCandidate::withTrashed()->get()->each->forceDelete();
  \Modules\Aicem\Models\AicemKnowledgeDocument::withTrashed()->get()->each(function ($d) {
      $d->versions()->delete();
      $d->forceDelete();
  });
  // (bài viết/sản phẩm/workflow demo có thể giữ lại hoặc xoá theo tiêu đề/tên ở mục 3)
  ```
