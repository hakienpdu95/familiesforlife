# OcopRubric Module — Advanced Vertical Slice Architecture Specification

> **Pattern stack:** AVSA + CQRS-lite + Laravel Modules (NWIDART 13) + Laravel Actions (lorisleiva 2.x)
> **Layer:** Layer 4 — Survey & Assessment Engine (sibling của `Assessment`, không phải extension của nó)
> **Căn cứ pháp lý:** Quyết định số 26/2026/QĐ-TTg ngày 22/5/2026 — đối chiếu cả 2 nguồn: `docs/bnn_txng.html` (bản transcript HTML đầy đủ, 47.874 dòng, kể cả Phụ lục II) và `docs/Quyết-định-26-2026-QĐ-TTg.pdf` (bản scan có chữ ký số gốc, 154 trang) — đã đọc chéo Điều 1–9 giữa 2 bản, khớp 100%, không có sai lệch.
> **Spec version:** 1.0 — 2026-07-04
> **Module tham chiếu kiến trúc:** `Modules/Subscription` (xem `docs/SUBSCRIPTION_SPEC.md`) — module Feature-first mới nhất, dùng làm khuôn cho spec này.

---

## 1. Bối cảnh & Căn cứ pháp lý

### 1.1 Cấu trúc thật của Quyết định 26/2026/QĐ-TTg (đã kiểm chứng, không phải suy đoán)

Bản phân tích trước đây ước lượng "33 Bộ sản phẩm" — con số đúng sau khi đếm chéo Phụ lục I (danh mục phân loại) và Phụ lục II (bộ tiêu chí) là **26**:

```
6 Ngành (I–VI)                    17 Nhóm                      26 Bộ sản phẩm (= lá của Phụ lục II)
──────────────────────────────────────────────────────────────────────────────────────────────
I.   SẢN PHẨM THỰC PHẨM           6 nhóm                        13 bộ sản phẩm
II.  SẢN PHẨM ĐỒ UỐNG             2 nhóm                        4 bộ sản phẩm
III. DƯỢC LIỆU & SP TỪ DƯỢC LIỆU  3 nhóm (không chia phân nhóm)  3 bộ sản phẩm
IV.  HÀNG THỦ CÔNG MỸ NGHỆ        2 nhóm (không chia phân nhóm)  2 bộ sản phẩm
V.   SINH VẬT CẢNH                3 nhóm (không chia phân nhóm)  3 bộ sản phẩm
VI.  DỊCH VỤ DU LỊCH CỘNG ĐỒNG    1 nhóm (không chia phân nhóm)  1 bộ sản phẩm
                                                          TỔNG:  26
```

Đã đọc rubric chi tiết của 2 bộ sản phẩm khác biệt hoàn toàn nhau để kiểm tra độ biến thiên cấu trúc:
- **"Rau, củ, quả, hạt tươi"** (bộ #1): Phần A gồm mục *"1. TỔ CHỨC SẢN XUẤT"* → *"1.1 Nguồn gốc sản phẩm"* (yêu cầu tỷ lệ % nguyên liệu địa phương), *"1.2 Vùng nguyên liệu được cấp chứng nhận"* (VietGAP/GlobalGAP)...
- **"Dịch vụ du lịch cộng đồng..."** (bộ #26): Phần A gồm mục *"1. TỔ CHỨC DỊCH VỤ CỘNG ĐỒNG"* → *"1.1 Bộ phận điều phối quản lý dịch vụ"*, *"1.2 Cơ chế quản lý/quy định"*, *"1.3 Bảo vệ môi trường cộng đồng làm du lịch"*...

Hai bộ này dùng từ vựng hoàn toàn khác nhau nhưng **cùng một hình dạng cây**: Phần (A/B/C) → Mục có thể lồng nhiều cấp (1 → 1.1 → 1.2...) → phương án chọn (checkbox) có điểm số riêng. → **Kết luận thiết kế: schema phải 100% config-driven theo cây tự tham chiếu, không hard-code tên trường theo bất kỳ ngành nào.**

### 1.2 Các điều khoản chi phối trực tiếp thiết kế dữ liệu

| Điều | Nội dung | Ảnh hưởng thiết kế |
|---|---|---|
| Điều 2.2, Phụ lục I | 6 ngành → 17 nhóm → 26 bộ sản phẩm, mỗi bộ có "Cơ quan chủ trì quản lý" riêng | `ocop_product_groups` — 26 dòng, có `managing_agency` |
| Điều 3.1 | Bộ tiêu chí 100 điểm, 3 phần: A=40 (tổ chức sản xuất/phát triển sản phẩm/sức mạnh cộng đồng), B=25 (tiếp thị/câu chuyện sản phẩm), C=35 (cảm quan/dinh dưỡng/độc đáo/công bố CL/đảm bảo CL/HDSD/thị trường toàn cầu) | `ocop_rubric_sections` — mỗi version đúng 3 dòng A/B/C, `max_score` validate tổng = 100 |
| Điều 3.3 | 5 hạng sao theo khoảng điểm cố định (0–30, 30–50, 50–70, 70–90, 90–100) | `ocop_star_bands` — bảng tra cứu, KHÔNG lặp lại 26 lần (dùng chung mọi bộ sản phẩm) |
| **Điều 4.1, 4.3** | Chỉ có **2 cấp công nhận chính thức**: tỉnh (3★, 4★) và trung ương (5★). **Sản phẩm đạt 1★/2★ không có quyết định công nhận, không cấp giấy chứng nhận.** Giấy chứng nhận từ 3★ trở lên có hiệu lực **36 tháng** | `ocop_star_bands.is_certifiable` = true chỉ cho star_rank ≥ 3; `ocop_products` **không** có cột `certified_at`/`expires_at` — việc cấp giấy là hành vi pháp lý ngoài phạm vi module này (xem §3) |
| Điều 5 | Tỉnh tổ chức đánh giá ≥2 đợt/năm (mốc 30/6 và 30/12); Bộ NN&MT ≥2 đợt/năm (mốc 30/9 và 30/3) | Khái niệm "đợt đánh giá" (batch theo lịch) — ngoài phạm vi module này, thuộc vertical chứng nhận tương lai |
| Điều 6.1 | Hồ sơ sản phẩm gồm Mẫu 01 (Phiếu đăng ký) + **Mẫu 02 (Báo cáo tự đánh giá về sản phẩm theo Bộ tiêu chí)** — 3 biến thể theo lần đầu/nâng hạng/phân hạng lại | **Đây chính là mode `self_assessment` của module này** — một `OcopScoringSession` hoàn chỉnh = nội dung số hoá của Mẫu 02 |
| Điều 6.2.d | 05 sản phẩm mẫu kèm hồ sơ, **trừ nhóm Dịch vụ du lịch cộng đồng** | `ocop_product_groups.requires_sample_product` = false riêng cho bộ #26 |
| Điều 7.1 | Hồ sơ nộp gồm "01 bộ hồ sơ giấy và 01 bộ hồ sơ điện tử"; nếu dùng phần mềm chấm điểm, "hồ sơ trên hệ thống phần mềm có giá trị pháp lý như hồ sơ giấy" | Xác nhận rõ: `OcopScoringSession` (mode=`self_assessment`) là văn bản pháp lý số hoá thật, không phải toy — phải versioned/immutable sau khi `completed` |
| Điều 8 | Thu hồi giấy chứng nhận — 6 lý do, thẩm quyền theo hạng sao | Ngoài phạm vi (§3) |
| Điều 9 | Kiểm tra giám sát hậu kiểm, định kỳ + đột xuất | Ngoài phạm vi (§3) |

---

## 2. Đặt tên & vị trí module

**Tên module: `OcopRubric`** (`Modules/OcopRubric`).

Lý do không dùng lại module có sẵn:

| Ứng viên | Vì sao không phù hợp |
|---|---|
| `Assessment` | Là engine chấm điểm năng lực số nhân sự (TDWCF/5-Pillar), `SectionedAggregation` hiện có **chuẩn hoá điểm section về thang 0–100 và không cộng tổng** ("Không có overall score — mỗi section ra 1 điểm độc lập" — xem `Modules/Assessment/app/Engine/Aggregation/SectionedAggregation.php`). OCOP cần điều ngược lại: giữ nguyên điểm thô theo thang max cố định (40/25/35) rồi **cộng thẳng** thành tổng 100 — khác thuật toán, không thể tái dùng nguyên trạng. Kéo OCOP vào `Assessment` sẽ trộn domain "năng lực nhân sự" với domain "chấm sản phẩm nông nghiệp" — vi phạm ranh giới miền. |
| `Survey` | Form builder tự do (field/section/condition), không có khái niệm "điểm theo từng phương án + tổng phụ theo Mục + validate tổng con = điểm cha". Ép rubric pháp lý cố định 3 cấp vào đó sẽ gượng ép và mất khả năng validate toàn vẹn dữ liệu ở tầng DB. |
| `Vertical` template (Deployment) | Mẫu `VerticalTemplate` cho phép **clone theo từng tổ chức rồi tổ chức tự sửa** (đúng cho checklist triển khai nội bộ). Rubric OCOP thì **ngược lại tuyệt đối**: bắt buộc giống nhau 100% cho mọi tổ chức vì đây là quy định của Thủ tướng Chính phủ — không tổ chức nào được "tùy biến" bộ tiêu chí. Dùng nhầm pattern này sẽ vô tình cho phép sửa luật. |

→ Module độc lập, đặt ở **Layer 4** cạnh `Assessment` trong bản đồ kiến trúc (`docs/PLATFORM_DESIGN.md` §2.1), vì cùng là "engine chấm điểm có cấu hình", khác nhau về đối tượng chấm (nhân sự vs. sản phẩm) và thuật toán cộng điểm.

---

## 3. Phạm vi module (Scope Boundary)

### 3.1 Trong phạm vi (module này làm)

1. Lưu trữ **26 bộ sản phẩm** + cây tiêu chí chấm điểm theo từng bộ, có versioning theo văn bản pháp luật.
2. Tổ chức đăng ký **sản phẩm OCOP thật** của mình (`OcopProduct`), gắn với 1 bộ sản phẩm.
3. **Chấm điểm** — 2 chế độ dùng chung 1 engine:
   - `practice` — luyện tập tự do kiểu "bộ bài quiz", không ràng buộc, có thể làm lại nhiều lần.
   - `self_assessment` — số hoá đúng **Mẫu số 02 Phụ lục III** (Báo cáo tự đánh giá về sản phẩm theo Bộ tiêu chí) mà luật yêu cầu chủ thể OCOP phải làm trước khi nộp hồ sơ (Điều 6.1.a).
4. Tính điểm, tra hạng sao, gợi ý "quick win" (phương án điểm cao chưa chọn) — trả lời trực tiếp nỗi đau *"muốn lên hạng phải làm gì"*.
5. Bắn sự kiện domain để các module khác (tương lai) tiêu thụ.

### 3.2 Ngoài phạm vi (cố ý không làm ở đây — dành cho "OcopCertification Vertical" tương lai)

Đây là ranh giới quan trọng nhất của spec này — nếu không vạch rõ, module sẽ phình to quá mức cần thiết:

| Nghiệp vụ | Vì sao không làm ở đây | Sẽ thuộc về |
|---|---|---|
| Nộp hồ sơ giấy/điện tử lên UBND xã, quy trình xã→tỉnh→trung ương (Điều 7) | Là workflow phê duyệt đa cấp hành chính, không phải logic chấm điểm | Vertical tương lai, tái dùng `Sop.SopApprovalFlow` (chuỗi bước gate theo vai trò) làm nền, mở rộng gate theo **cấp hành chính** thay vì chỉ vai trò |
| Hội đồng cấp tỉnh/trung ương, Tổ tư vấn, biểu quyết | Nghiệp vụ tổ chức, không phải scoring | Vertical tương lai |
| Cấp/thu hồi giấy chứng nhận, hiệu lực 36 tháng, gia hạn 180 ngày (Điều 8, 12) | Là hành vi pháp lý của cơ quan nhà nước, không phải kết quả tự chấm | Vertical tương lai — nhưng sẽ **lắng nghe** `ScoringSessionCompleted` (mode=`self_assessment`) làm input |
| Kiểm tra giám sát hậu kiểm (Điều 9) | Ngoài scoring | Vertical tương lai |
| Lưu hồ sơ pháp lý đính kèm (ĐKKD, phiếu kiểm nghiệm, chứng nhận VietGAP...) | Đã có `KcItem` (kho tài liệu có duyệt/versioning) — không tạo file storage riêng | Mở rộng `KcItem` type enum, liên kết qua `ocop_product_id` |
| Đợt đánh giá theo lịch (Điều 5) | Là lịch làm việc hành chính | Vertical tương lai |

→ `OcopRubric` chỉ trả lời câu hỏi: **"Sản phẩm này, theo bộ tiêu chí hiện hành, đang được bao nhiêu điểm và tương đương hạng mấy sao?"** — không trả lời "hồ sơ đã nộp đến đâu".

---

## 4. Nguyên tắc kiến trúc

| Nguyên tắc | Áp dụng trong `OcopRubric` |
|---|---|
| **AVSA+CQRS-lite** | `Features/{Slice}/Actions` (write) + `Features/{Slice}/Queries` (read) — không có business logic trong Controller |
| **Rubric = system-level, không tenant-scoped** | `OcopProductGroup`, `OcopRubricVersion/Section/Criterion/Option/Disqualifier`, `OcopStarBand` **không extend `TenantAwareModel`**, không có cột `organization_id`. Đây là bảng cấu hình dùng chung toàn hệ thống, chỉ `system_admin` được sửa. |
| **Dữ liệu thật = tenant-scoped** | `OcopProduct`, `OcopScoringSession`, `OcopScoringAnswer` extend `TenantAwareModel` như mọi domain model khác |
| **No JSON storage** | Toàn bộ cây tiêu chí là bảng quan hệ chuẩn hoá (`ocop_rubric_criteria` tự tham chiếu + materialized path), không lưu JSON |
| **Immutable sau khi publish** | `OcopRubricVersion.status = active` → không sửa trực tiếp; muốn sửa phải `CloneRubricVersionAction` tạo bản `draft` mới, sửa xong `PublishRubricVersionAction` mới chuyển active và tự động `retired` bản cũ. Lý do: một `self_assessment` đã hoàn thành phải giữ nguyên vẹn giá trị pháp lý tại thời điểm làm, không được thay đổi ngầm khi luật sửa. |
| **Soft deletes** | Trên `OcopProductGroup`, `OcopProduct` |
| **UUID public** | `ocop_products.uuid`, `ocop_rubric_versions.uuid`, `ocop_scoring_sessions.uuid` — expose qua route/API |
| **Tên bảng có tiền tố `ocop_`** | Đây **không phải** "business prefix" bị cấm theo `docs/PLATFORM_DESIGN.md` §10.1 (cấm kiểu `thv_zones` — tiền tố công ty). `ocop_` là tên chương trình pháp lý của Nhà nước (giống cách `txng_`/`checkvn_` đã được dùng làm tiền tố miền trong hệ thống) — giữ nguyên để không nhầm với bảng nghiệp vụ khác. |

---

## 5. Directory Structure (AVSA)

```
Modules/OcopRubric/
│
├── app/
│   ├── Features/
│   │   ├── ProductGroupCatalog/            ← Slice: system_admin quản lý danh mục 26 bộ sản phẩm
│   │   │   ├── Actions/
│   │   │   │   ├── CreateProductGroupAction.php
│   │   │   │   └── UpdateProductGroupAction.php
│   │   │   ├── Queries/
│   │   │   │   ├── ListProductGroupsQuery.php
│   │   │   │   └── ListProductGroupsHandler.php
│   │   │   ├── Data/
│   │   │   │   └── ProductGroupData.php
│   │   │   └── Http/
│   │   │       └── ProductGroupController.php
│   │   │
│   │   ├── RubricAuthoring/                ← Slice: system_admin xây/sửa cây tiêu chí theo version
│   │   │   ├── Actions/
│   │   │   │   ├── CreateRubricVersionAction.php
│   │   │   │   ├── CloneRubricVersionAction.php
│   │   │   │   ├── PublishRubricVersionAction.php
│   │   │   │   ├── UpsertCriterionAction.php
│   │   │   │   ├── ReorderCriteriaAction.php
│   │   │   │   └── UpsertOptionAction.php
│   │   │   ├── Queries/
│   │   │   │   ├── GetRubricTreeQuery.php
│   │   │   │   ├── GetRubricTreeHandler.php
│   │   │   │   ├── ValidateRubricIntegrityQuery.php
│   │   │   │   └── ValidateRubricIntegrityHandler.php
│   │   │   ├── Data/
│   │   │   │   ├── RubricVersionData.php
│   │   │   │   ├── CriterionData.php
│   │   │   │   └── OptionData.php
│   │   │   ├── Events/
│   │   │   │   └── RubricVersionPublished.php
│   │   │   └── Http/
│   │   │       └── RubricAuthoringController.php
│   │   │
│   │   ├── ProductRegistry/                ← Slice: tổ chức đăng ký sản phẩm OCOP của mình
│   │   │   ├── Actions/
│   │   │   │   ├── RegisterProductAction.php
│   │   │   │   ├── UpdateProductAction.php
│   │   │   │   └── ArchiveProductAction.php
│   │   │   ├── Queries/
│   │   │   │   ├── ListProductsQuery.php
│   │   │   │   ├── ListProductsHandler.php
│   │   │   │   ├── GetProductQuery.php
│   │   │   │   └── GetProductHandler.php
│   │   │   ├── Data/
│   │   │   │   └── ProductData.php
│   │   │   ├── Events/
│   │   │   │   └── ProductRegistered.php
│   │   │   └── Http/
│   │   │       └── ProductController.php
│   │   │
│   │   ├── ScoringSession/                 ← Slice CỐT LÕI — dùng chung practice + self_assessment
│   │   │   ├── Actions/
│   │   │   │   ├── StartScoringSessionAction.php    ← resume nếu đã có session in_progress, guard rubric chưa cấu hình
│   │   │   │   ├── AbandonSessionAction.php         ← bỏ dở phiên treo, khoá như completed
│   │   │   │   ├── AnswerCriterionAction.php        ← guard is_locked/status + validate is_scorable trước khi ghi
│   │   │   │   ├── FlagDisqualifierAction.php       ← cùng guard
│   │   │   │   ├── SkipCriterionAction.php          ← cùng guard
│   │   │   │   ├── RecalculateSessionScoreAction.php ← tách riêng, dùng chung Answer + Duplicate, lockForUpdate() chống race
│   │   │   │   ├── CompleteScoringSessionAction.php  ← set is_locked=true, không thể đảo ngược
│   │   │   │   └── DuplicateScoringSessionAction.php ← nhân bản sang sản phẩm khác (§8.4)
│   │   │   ├── Queries/
│   │   │   │   ├── GetSessionProgressQuery.php
│   │   │   │   ├── GetSessionProgressHandler.php
│   │   │   │   ├── GetNextCriterionQuery.php
│   │   │   │   ├── GetNextCriterionHandler.php
│   │   │   │   ├── GetQuickWinsQuery.php
│   │   │   │   └── GetQuickWinsHandler.php
│   │   │   ├── Services/
│   │   │   │   ├── ScoringCalculator.php        ← thuần logic, không side-effect, dễ unit test
│   │   │   │   └── CrossVersionAnswerMapper.php ← Phase 4b — map theo code khi rubric_version đổi (§8.4.2)
│   │   │   ├── Data/
│   │   │   │   └── AnswerCriterionData.php
│   │   │   ├── Events/
│   │   │   │   ├── ScoringSessionCompleted.php
│   │   │   │   ├── ScoringSessionDuplicated.php
│   │   │   │   └── StarBandImproved.php
│   │   │   └── Http/
│   │   │       └── ScoringSessionController.php    ← + action duplicate()
│   │   │
│   │   └── PracticeDeck/                   ← Slice: viewmodel cho trải nghiệm "bộ bài luyện tập"
│   │       ├── Queries/
│   │       │   ├── GetPracticeHistoryQuery.php
│   │       │   ├── GetPracticeHistoryHandler.php
│   │       │   ├── GetLeaderboardQuery.php
│   │       │   └── GetLeaderboardHandler.php
│   │       └── Http/
│   │           └── PracticeDeckController.php
│   │
│   ├── Models/
│   │   ├── OcopProductGroup.php
│   │   ├── OcopRubricVersion.php
│   │   ├── OcopRubricSection.php
│   │   ├── OcopRubricCriterion.php
│   │   ├── OcopRubricOption.php
│   │   ├── OcopRubricDisqualifier.php
│   │   ├── OcopStarBand.php
│   │   ├── OcopProduct.php
│   │   ├── OcopScoringSession.php
│   │   ├── OcopScoringAnswer.php
│   │   └── OcopScoringDisqualifierFlag.php
│   │
│   ├── Enums/
│   │   ├── RubricVersionStatus.php          # draft | active | retired
│   │   ├── ScoringSessionMode.php           # practice | self_assessment
│   │   ├── ScoringSessionStatus.php         # in_progress | completed | abandoned
│   │   └── ProductStatus.php                # draft | practicing | self_assessed | archived
│   │
│   ├── Observers/
│   │   └── OcopProductObserver.php
│   ├── Policies/
│   │   ├── OcopProductPolicy.php
│   │   ├── OcopRubricVersionPolicy.php
│   │   └── OcopScoringSessionPolicy.php     ← view()/answer()/duplicate() — chặn sửa session đã locked
│   └── Providers/
│       ├── OcopRubricServiceProvider.php
│       ├── EventServiceProvider.php
│       └── RouteServiceProvider.php
│
├── database/
│   ├── migrations/
│   └── seeders/
│       ├── OcopStarBandSeeder.php           # 5 dòng, Điều 3.3
│       ├── OcopProductGroupSeeder.php       # 26 dòng, Phụ lục I + II
│       └── OcopRubricVersionSeeder.php      # đọc fixture data-driven, xem §11
│
└── resources/views/
    ├── admin/product-groups/
    ├── admin/rubric-authoring/
    ├── products/
    └── practice/
        ├── start.blade.php
        ├── deck.blade.php                  # giao diện lật "lá bài"
        └── summary.blade.php
```

---

## 6. Domain Models

### 6.1 Nhóm system-level (dùng chung mọi tổ chức — không `TenantAwareModel`)

| Model | Vai trò | Field chính |
|---|---|---|
| `OcopProductGroup` | 1 trong 26 "Bộ sản phẩm" | `code`, `name`, `industry_code`, `industry_name`, `managing_agency`, `requires_sample_product`, `is_active`, `sort_order` |
| `OcopRubricVersion` | 1 phiên bản bộ tiêu chí của 1 bộ sản phẩm | `product_group_id`, `version_no`, `status`, `effective_from/to`, `source_reference`, `total_max_score` |
| `OcopRubricSection` | Phần A/B/C | `rubric_version_id`, `code`, `label`, `max_score` |
| `OcopRubricCriterion` | Nút trong cây tiêu chí (Mục hoặc Tiêu chí lá), tự tham chiếu | `rubric_section_id`, `parent_id`, `path`, `depth`, `code`, `label`, `max_score`, `requirement_note`, `is_scorable` |
| `OcopRubricOption` | 1 phương án chọn của tiêu chí lá | `criterion_id`, `label`, `points` |
| `OcopRubricDisqualifier` | Điều kiện "hồ sơ bị loại khi..." — chỉ mang tính khuyến cáo | `rubric_version_id`, `label` |
| `OcopStarBand` | Bảng tra 5 hạng sao — **dùng chung cho mọi bộ sản phẩm**, không lặp lại theo version | `legal_version`, `star_rank`, `label`, `min_score`, `max_score`, `authority_level`, `is_certifiable` |

### 6.2 Nhóm tenant-scoped (`TenantAwareModel`)

| Model | Vai trò | Field chính |
|---|---|---|
| `OcopProduct` | 1 sản phẩm OCOP thật của 1 tổ chức | `organization_id`, `product_group_id`, `name`, `product_code`, `status`, `best_practice_score/star_rank`, `latest_self_assessment_score/star_rank`, `latest_self_assessment_session_id` |
| `OcopScoringSession` | 1 lần chấm điểm (practice hoặc self_assessment) — **bất biến sau khi `completed`** | `organization_id`, `ocop_product_id` (nullable), `rubric_version_id`, `duplicated_from_session_id` (nullable — lineage), `user_id`, `employee_id` (nullable), `mode`, `status`, `is_locked`, `score_section_a/b/c`, `total_score`, `star_rank`, `criteria_answered/total`, `duration_seconds` |
| `OcopScoringAnswer` | 1 câu trả lời cho 1 tiêu chí trong 1 session | `session_id`, `criterion_id`, `option_id`, `points_awarded`, `needs_review`, `evidence_note` |
| `OcopScoringDisqualifierFlag` | Tự-đánh dấu rủi ro loại hồ sơ trong 1 session (advisory) | `session_id`, `disqualifier_id`, `is_flagged` |

**Vì sao `OcopProduct` không có `certified_at`/`expires_at`:** đây là dữ liệu quan sát được từ quyết định công nhận thật của UBND tỉnh/Bộ NN&MT — một sự kiện pháp lý ngoài hệ thống này (xem §3.2). `best_practice_score`/`latest_self_assessment_score` chỉ là **ước lượng nội bộ**, phải hiển thị rõ ràng trên UI là "điểm tự chấm — không phải kết quả công nhận chính thức" để tránh hiểu lầm pháp lý.

**Helper method `OcopProduct::activeRubricVersion(): ?OcopRubricVersion`** — không phải cột DB, chỉ là accessor tiện dùng ở nhiều nơi (§8.4): `return OcopRubricVersion::where('product_group_id', $this->product_group_id)->where('status', 'active')->first();`. Luôn resolve "động" theo thời điểm gọi — nếu rubric vừa được publish version mới thì method này trả về version mới ngay, không cache, không lưu `rubric_version_id` cố định trên `OcopProduct` (vì 1 sản phẩm có thể có nhiều session ở nhiều version khác nhau theo thời gian — `rubric_version_id` chỉ tồn tại ở cấp `OcopScoringSession`, đúng nơi nó thuộc về).

---

## 7. Database Schema

> Quy ước chung theo `docs/PLATFORM_DESIGN.md` §10.1: `id` + `uuid` (bảng expose ra ngoài) + soft delete trên entity quan trọng + index theo cột lọc thường dùng + không JSON.

```php
// 2026_07_04_000001_create_ocop_product_groups_table.php
Schema::create('ocop_product_groups', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->string('code', 60)->unique();                    // 'rau-cu-qua-hat-tuoi'
    $table->string('name', 255);                             // "Rau, củ, quả, hạt tươi"
    $table->string('industry_code', 10);                     // 'I'..'VI' — Phụ lục I
    $table->string('industry_name', 255);                    // "SẢN PHẨM THỰC PHẨM"
    $table->string('group_label', 255)->nullable();          // "Nhóm: Thực phẩm tươi sống"
    $table->string('managing_agency', 255)->nullable();      // "Bộ Nông nghiệp và Môi trường"
    $table->boolean('requires_sample_product')->default(true); // false cho bộ #26 (Điều 6.2.d)
    $table->boolean('is_active')->default(true);
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();
    $table->softDeletes();

    $table->index(['industry_code', 'sort_order']);
});

// 2026_07_04_000002_create_ocop_rubric_versions_table.php
Schema::create('ocop_rubric_versions', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('product_group_id')->constrained('ocop_product_groups')->restrictOnDelete();
    $table->unsignedSmallInteger('version_no');
    $table->string('status', 20)->default('draft');          // RubricVersionStatus
    $table->date('effective_from')->nullable();
    $table->date('effective_to')->nullable();
    $table->string('source_reference', 255)->default('QĐ 26/2026/QĐ-TTg, Phụ lục II');
    $table->decimal('total_max_score', 5, 2)->default(100.00);
    $table->unsignedBigInteger('published_by')->nullable();
    $table->timestamp('published_at')->nullable();
    $table->timestamps();

    $table->unique(['product_group_id', 'version_no']);
    $table->index(['product_group_id', 'status']);
});

// 2026_07_04_000003_create_ocop_rubric_sections_table.php
Schema::create('ocop_rubric_sections', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rubric_version_id')->constrained('ocop_rubric_versions')->cascadeOnDelete();
    $table->string('code', 5);                                // 'A' | 'B' | 'C'
    $table->string('label', 255);
    $table->decimal('max_score', 5, 2);                       // 40.00 / 25.00 / 35.00
    $table->unsignedTinyInteger('sort_order')->default(0);
    $table->timestamps();

    $table->unique(['rubric_version_id', 'code']);
});

// 2026_07_04_000004_create_ocop_rubric_criteria_table.php
Schema::create('ocop_rubric_criteria', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rubric_section_id')->constrained('ocop_rubric_sections')->cascadeOnDelete();
    $table->foreignId('parent_id')->nullable()->constrained('ocop_rubric_criteria')->cascadeOnDelete();
    $table->string('path', 255)->default('/');                // materialized path — cùng pattern production_areas
    $table->unsignedTinyInteger('depth')->default(0);
    $table->string('code', 20);                               // '1' | '1.1' | '1.2.3'
    $table->string('label', 500);
    $table->decimal('max_score', 5, 2);
    $table->text('requirement_note')->nullable();              // "Yêu cầu: 100% sản phẩm được trồng..."
    $table->boolean('is_scorable')->default(false);            // true=lá có option, false=container cộng dồn
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();

    $table->unique(['rubric_section_id', 'code']);
    $table->index(['rubric_section_id', 'path']);
    $table->index(['parent_id', 'sort_order']);
});

// 2026_07_04_000005_create_ocop_rubric_options_table.php
Schema::create('ocop_rubric_options', function (Blueprint $table) {
    $table->id();
    $table->foreignId('criterion_id')->constrained('ocop_rubric_criteria')->cascadeOnDelete();
    $table->string('label', 1000);
    $table->decimal('points', 5, 2);
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();

    $table->index(['criterion_id', 'sort_order']);
});

// 2026_07_04_000006_create_ocop_rubric_disqualifiers_table.php
Schema::create('ocop_rubric_disqualifiers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rubric_version_id')->constrained('ocop_rubric_versions')->cascadeOnDelete();
    $table->text('label');
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();
});

// 2026_07_04_000007_create_ocop_star_bands_table.php
Schema::create('ocop_star_bands', function (Blueprint $table) {
    $table->id();
    $table->string('legal_version', 30)->default('QD26-2026'); // cho phép versioning khi có nghị định mới
    $table->unsignedTinyInteger('star_rank');                  // 1..5
    $table->string('label', 100);                              // "Hạng 3 sao (cấp tỉnh)"
    $table->decimal('min_score', 5, 2);
    $table->decimal('max_score', 5, 2);
    $table->string('authority_level', 20);                     // 'commune_screen_only'|'province'|'central'
    $table->boolean('is_certifiable')->default(false);         // true chỉ star_rank >= 3 (Điều 4.3)
    $table->timestamps();

    $table->unique(['legal_version', 'star_rank']);
});

// 2026_07_04_000008_create_ocop_products_table.php
Schema::create('ocop_products', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->foreignId('product_group_id')->constrained('ocop_product_groups')->restrictOnDelete();
    $table->string('name', 255);
    $table->string('product_code', 60)->nullable();            // (MãTỉnh)-(MãXã)-(STT)-(Năm) — tự điền khi có
    $table->string('status', 20)->default('draft');            // ProductStatus

    // Tách riêng "kỷ lục luyện tập" và "hiện trạng tự đánh giá" — KHÔNG dùng chung 1 cặp
    // best_score/best_star_rank (xem Key Design Decisions §18 — lý do tách).
    $table->decimal('best_practice_score', 5, 2)->nullable();
    $table->unsignedTinyInteger('best_practice_star_rank')->nullable();
    $table->decimal('latest_self_assessment_score', 5, 2)->nullable();
    $table->unsignedTinyInteger('latest_self_assessment_star_rank')->nullable();
    $table->unsignedBigInteger('latest_self_assessment_session_id')->nullable();
    // FK cho cột trên thêm ở migration riêng (2026_07_04_000012) SAU KHI ocop_scoring_sessions
    // tồn tại — 2 bảng tham chiếu vòng lẫn nhau (products → sessions và sessions → products),
    // không thể đặt cả 2 FK ràng buộc ngay trong migration tạo bảng đầu tiên.

    $table->unsignedBigInteger('created_by')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['organization_id', 'product_group_id']);
    $table->index(['organization_id', 'status']);
});

// 2026_07_04_000009_create_ocop_scoring_sessions_table.php
Schema::create('ocop_scoring_sessions', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->foreignId('ocop_product_id')->nullable()->constrained('ocop_products')->nullOnDelete();
    $table->foreignId('rubric_version_id')->constrained('ocop_rubric_versions')->restrictOnDelete();
    $table->foreignId('duplicated_from_session_id')->nullable()
        ->constrained('ocop_scoring_sessions')->nullOnDelete();  // lineage — xem §8.4
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('employee_id')->nullable();     // liên kết KpiGoal/PerformanceReview tương lai
    $table->string('mode', 20);                                 // ScoringSessionMode
    $table->string('status', 20)->default('in_progress');       // ScoringSessionStatus
    $table->boolean('is_locked')->default(false);               // true ngay khi completed — chặn sửa vĩnh viễn
    $table->decimal('score_section_a', 5, 2)->default(0);
    $table->decimal('score_section_b', 5, 2)->default(0);
    $table->decimal('score_section_c', 5, 2)->default(0);
    $table->decimal('total_score', 5, 2)->default(0);
    $table->unsignedTinyInteger('star_rank')->nullable();
    $table->unsignedSmallInteger('criteria_total')->default(0);
    $table->unsignedSmallInteger('criteria_answered')->default(0);
    $table->unsignedInteger('duration_seconds')->nullable();
    $table->timestamp('started_at')->useCurrent();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();

    $table->index(['organization_id', 'mode', 'status']);
    $table->index(['ocop_product_id', 'mode']);
    $table->index(['user_id', 'mode']);
    $table->index('duplicated_from_session_id');
});

// 2026_07_04_000010_create_ocop_scoring_answers_table.php
Schema::create('ocop_scoring_answers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('session_id')->constrained('ocop_scoring_sessions')->cascadeOnDelete();
    $table->foreignId('criterion_id')->constrained('ocop_rubric_criteria')->restrictOnDelete();
    $table->foreignId('option_id')->nullable()->constrained('ocop_rubric_options')->nullOnDelete();
    $table->decimal('points_awarded', 5, 2)->default(0);
    $table->boolean('needs_review')->default(false);   // true = câu trả lời do map chéo version, chưa người dùng xác nhận lại (§8.4.2)
    $table->text('evidence_note')->nullable();
    $table->timestamp('answered_at')->nullable();
    $table->timestamps();

    $table->unique(['session_id', 'criterion_id']);
    $table->index('option_id');
    $table->index(['session_id', 'needs_review']);
});

// 2026_07_04_000011_create_ocop_scoring_disqualifier_flags_table.php
Schema::create('ocop_scoring_disqualifier_flags', function (Blueprint $table) {
    $table->id();
    $table->foreignId('session_id')->constrained('ocop_scoring_sessions')->cascadeOnDelete();
    $table->foreignId('disqualifier_id')->constrained('ocop_rubric_disqualifiers')->cascadeOnDelete();
    $table->boolean('is_flagged')->default(false);
    $table->timestamps();

    $table->unique(['session_id', 'disqualifier_id']);
});

// 2026_07_04_000012_add_latest_self_assessment_fk_to_ocop_products_table.php
// Tách riêng vì phụ thuộc vòng: ocop_products cần trỏ tới ocop_scoring_sessions (bảng tạo SAU
// nó ở 000009) — không thể gộp FK này vào migration 000008 tạo bảng ocop_products.
Schema::table('ocop_products', function (Blueprint $table) {
    $table->foreign('latest_self_assessment_session_id')
        ->references('id')->on('ocop_scoring_sessions')
        ->nullOnDelete();
});
```

**11 bảng (10 bảng dữ liệu + 1 migration bổ sung FK), không bảng nào dùng JSON.** `ocop_rubric_criteria` là bảng duy nhất tự tham chiếu — dùng materialized path (`path`, `depth`) đúng pattern đã kiểm chứng ở `production_areas`/`Branch`, tránh recursive CTE trong SQLite dev.

---

## 8. Feature Slices — Chi tiết

### 8.1 Slice: RubricAuthoring — validate toàn vẹn cây tiêu chí

Đây là phần rủi ro nhất: nếu tổng điểm con ≠ điểm cha, hoặc tổng A+B+C ≠ 100, toàn bộ kết quả chấm điểm sai mà không ai biết. Validate phải chạy **mỗi lần publish**, không chỉ tin tưởng người nhập liệu.

```php
// Features/RubricAuthoring/Queries/ValidateRubricIntegrityHandler.php
namespace Modules\OcopRubric\Features\RubricAuthoring\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\OcopRubric\Models\OcopRubricVersion;

class ValidateRubricIntegrityHandler implements QueryHandlerInterface
{
    /** @return array{valid: bool, errors: string[]} */
    public function handle(QueryInterface $query): array
    {
        /** @var ValidateRubricIntegrityQuery $query */
        $version = OcopRubricVersion::with('sections.criteria.children', 'sections.criteria.options')
            ->findOrFail($query->rubricVersionId);

        $errors = [];

        // 1. Tổng max_score của 3 phần phải = 100
        $sectionTotal = $version->sections->sum('max_score');
        if (bccomp((string) $sectionTotal, (string) $version->total_max_score, 2) !== 0) {
            $errors[] = "Tổng điểm 3 phần ({$sectionTotal}) khác total_max_score ({$version->total_max_score}).";
        }

        // 2. Mỗi container: tổng max_score của con phải = max_score của cha
        foreach ($version->sections as $section) {
            foreach ($section->criteria->where('parent_id', null) as $root) {
                $this->validateSubtree($root, $errors);
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    private function validateSubtree($node, array &$errors): void
    {
        if ($node->is_scorable) {
            // Lá: tổng points các option KHÔNG bắt buộc = max_score (luật cho phép option thấp nhất = 0,
            // option cao nhất mới chạm max_score) — chỉ validate option cao nhất không vượt max_score.
            $highest = $node->options->max('points') ?? 0;
            if (bccomp((string) $highest, (string) $node->max_score, 2) > 0) {
                $errors[] = "Tiêu chí {$node->code} có option {$highest}đ vượt max_score {$node->max_score}đ.";
            }
            return;
        }

        // Container: tổng max_score của con phải khớp
        $childrenTotal = $node->children->sum('max_score');
        if (bccomp((string) $childrenTotal, (string) $node->max_score, 2) !== 0) {
            $errors[] = "Mục {$node->code}: tổng điểm con ({$childrenTotal}) khác max_score ({$node->max_score}).";
        }

        foreach ($node->children as $child) {
            $this->validateSubtree($child, $errors);
        }
    }
}
```

```php
// Features/RubricAuthoring/Actions/PublishRubricVersionAction.php
namespace Modules\OcopRubric\Features\RubricAuthoring\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Enums\RubricVersionStatus;
use Modules\OcopRubric\Events\RubricVersionPublished;
use Modules\OcopRubric\Features\RubricAuthoring\Queries\ValidateRubricIntegrityHandler;
use Modules\OcopRubric\Features\RubricAuthoring\Queries\ValidateRubricIntegrityQuery;
use Modules\OcopRubric\Models\OcopRubricVersion;

class PublishRubricVersionAction
{
    use AsAction;

    public function __construct(private readonly ValidateRubricIntegrityHandler $validator) {}

    public function handle(OcopRubricVersion $version, int $publishedByUserId): OcopRubricVersion
    {
        $result = $this->validator->handle(new ValidateRubricIntegrityQuery($version->id));

        if (!$result['valid']) {
            throw new \DomainException(
                "Không thể publish — bộ tiêu chí chưa hợp lệ: " . implode(' | ', $result['errors'])
            );
        }

        return DB::transaction(function () use ($version, $publishedByUserId) {
            // Retire bản active cũ của CÙNG bộ sản phẩm (chỉ 1 version active tại 1 thời điểm)
            OcopRubricVersion::where('product_group_id', $version->product_group_id)
                ->where('status', RubricVersionStatus::Active->value)
                ->update(['status' => RubricVersionStatus::Retired->value, 'effective_to' => now()]);

            $version->update([
                'status'        => RubricVersionStatus::Active->value,
                'published_by'  => $publishedByUserId,
                'published_at'  => now(),
                'effective_from'=> now(),
            ]);

            RubricVersionPublished::dispatch($version);

            return $version->fresh();
        });
    }
}
```

### 8.2 Slice: ScoringSession — engine cốt lõi

```php
// Features/ScoringSession/Actions/StartScoringSessionAction.php
namespace Modules\OcopRubric\Features\ScoringSession\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Enums\ScoringSessionStatus;
use Modules\OcopRubric\Models\OcopProduct;
use Modules\OcopRubric\Models\OcopScoringSession;

class StartScoringSessionAction
{
    use AsAction;

    public function handle(OcopProduct $product, string $mode, int $userId): OcopScoringSession
    {
        // Chống trùng phiên: 2 tab/2 nhân viên cùng bấm "bắt đầu luyện tập" cho CÙNG sản phẩm +
        // CÙNG mode trong khi đã có 1 phiên in_progress → resume phiên cũ, KHÔNG tạo phiên mới.
        // Nếu tạo mới vô tội vạ, 2 phiên chấm song song trên cùng sản phẩm sẽ gây nhầm lẫn "điểm
        // hiện tại" là của phiên nào khi 2 người cùng xem GetSessionProgressHandler.
        $existing = OcopScoringSession::where('ocop_product_id', $product->id)
            ->where('mode', $mode)
            ->where('status', ScoringSessionStatus::InProgress->value)
            ->first();

        if ($existing) {
            return $existing;
        }

        $rubricVersion = $product->activeRubricVersion();
        if (!$rubricVersion) {
            // Xảy ra thật trong giai đoạn Phase 2-6: tổ chức đã đăng ký sản phẩm thuộc 1 trong 26
            // Bộ sản phẩm nhưng system_admin CHƯA seed/publish rubric cho bộ đó — không được để
            // crash 500 khó hiểu, phải báo rõ nguyên nhân nghiệp vụ.
            throw new \DomainException(
                'Bộ tiêu chí cho nhóm sản phẩm này chưa được cấu hình — vui lòng liên hệ quản trị hệ thống.'
            );
        }

        $scorableCount = $rubricVersion->sections()
            ->withCount(['criteria' => fn ($q) => $q->where('is_scorable', true)])
            ->get()->sum('criteria_count');

        return OcopScoringSession::create([
            'organization_id'   => $product->organization_id,
            'ocop_product_id'   => $product->id,
            'rubric_version_id' => $rubricVersion->id,
            'user_id'           => $userId,
            'mode'              => $mode,
            'status'            => ScoringSessionStatus::InProgress->value,
            'criteria_total'    => $scorableCount,
        ]);
    }
}
```

```php
// Features/ScoringSession/Actions/AbandonSessionAction.php
namespace Modules\OcopRubric\Features\ScoringSession\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Enums\ScoringSessionStatus;
use Modules\OcopRubric\Models\OcopScoringSession;

/**
 * Cho phép DN chủ động "bỏ dở" 1 phiên đang thực hiện thay vì để treo mãi ở in_progress —
 * đây là lối thoát tường minh cho case StartScoringSessionAction resume nhầm phiên cũ mà
 * người dùng thật ra muốn làm lại từ đầu (vd. bấm nhầm hôm trước, giờ muốn huỷ hẳn).
 */
class AbandonSessionAction
{
    use AsAction;

    public function handle(OcopScoringSession $session): OcopScoringSession
    {
        if ($session->status !== ScoringSessionStatus::InProgress->value) {
            throw new \DomainException('Chỉ có thể bỏ dở phiên đang thực hiện.');
        }

        $session->update([
            'status'       => ScoringSessionStatus::Abandoned->value,
            'is_locked'    => true,   // khoá như completed — không cho sửa/resume phiên đã bỏ dở
            'completed_at' => now(),
        ]);

        return $session->fresh();
    }
}
```

```php
// Features/ScoringSession/Services/ScoringCalculator.php
namespace Modules\OcopRubric\Features\ScoringSession\Services;

use Modules\OcopRubric\Models\OcopRubricVersion;
use Modules\OcopRubric\Models\OcopScoringSession;
use Modules\OcopRubric\Models\OcopStarBand;

/**
 * Thuần logic tính điểm — không query DB ngoài dữ liệu đã load, dễ unit test.
 * Thuật toán: KHÔNG chuẩn hoá (khác SectionedAggregation của Assessment) —
 * cộng thẳng điểm option đã chọn, vì thang điểm mỗi Mục đã được luật định sẵn
 * theo đúng tỷ trọng (Phần A=40/B=25/C=35), cộng thẳng là đúng theo Điều 3.
 */
class ScoringCalculator
{
    /** @param array<int,float> $pointsByCriterionId criterion_id => points_awarded (chỉ tiêu chí is_scorable) */
    public function calculate(OcopRubricVersion $version, array $pointsByCriterionId): CalculationResult
    {
        $sectionScores = [];

        foreach ($version->sections as $section) {
            $leafIds = $section->criteria->where('is_scorable', true)->pluck('id');
            $sectionScores[$section->code] = round(
                collect($leafIds)->sum(fn ($id) => $pointsByCriterionId[$id] ?? 0.0),
                2
            );
        }

        $total = round(array_sum($sectionScores), 2);

        $band = OcopStarBand::where('legal_version', 'QD26-2026')
            ->where('min_score', '<=', $total)
            ->where('max_score', '>=', $total)
            ->orderByDesc('star_rank') // biên giới trùng (vd. đúng 30) ưu tiên hạng cao hơn theo tinh thần Điều 3.3
            ->first();

        return new CalculationResult(
            sectionScores: $sectionScores,
            totalScore: $total,
            starRank: $band?->star_rank,
            isCertifiable: $band?->is_certifiable ?? false,
        );
    }
}
```

```php
// Features/ScoringSession/Actions/RecalculateSessionScoreAction.php
// Tách riêng khỏi AnswerCriterionAction để DuplicateScoringSessionAction (§8.4) dùng lại được
// — không lặp lại logic tính điểm ở 2 nơi.
namespace Modules\OcopRubric\Features\ScoringSession\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Features\ScoringSession\Services\ScoringCalculator;
use Modules\OcopRubric\Models\OcopScoringSession;

class RecalculateSessionScoreAction
{
    use AsAction;

    public function __construct(private readonly ScoringCalculator $calculator) {}

    public function handle(OcopScoringSession $session): OcopScoringSession
    {
        // lockForUpdate: nếu 2 request answer() tới gần như đồng thời (2 tab, hoặc double-click
        // nhanh trên deck), request thứ 2 phải đợi request thứ 1 ghi xong rồi mới đọc lại answers
        // + ghi điểm — tránh "lost update" (request 2 tính điểm dựa trên dữ liệu answers cũ, rồi
        // ghi đè mất kết quả của request 1 dù answer của request 1 đã lưu đúng vào DB).
        return DB::transaction(function () use ($session) {
            $locked = OcopScoringSession::whereKey($session->id)->lockForUpdate()->firstOrFail();

            $version   = $locked->rubricVersion()->with('sections.criteria')->first();
            $pointsMap = $locked->answers()->pluck('points_awarded', 'criterion_id')->all();
            $result    = $this->calculator->calculate($version, $pointsMap);

            $locked->update([
                'score_section_a'   => $result->sectionScores['A'] ?? 0,
                'score_section_b'   => $result->sectionScores['B'] ?? 0,
                'score_section_c'   => $result->sectionScores['C'] ?? 0,
                'total_score'       => $result->totalScore,
                'star_rank'         => $result->starRank,
                'criteria_answered' => $locked->answers()->count(),
            ]);

            return $locked->fresh();
        });
    }
}
```

```php
// Features/ScoringSession/Actions/AnswerCriterionAction.php
namespace Modules\OcopRubric\Features\ScoringSession\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Enums\ScoringSessionStatus;
use Modules\OcopRubric\Features\ScoringSession\Data\AnswerCriterionData;
use Modules\OcopRubric\Models\OcopRubricCriterion;
use Modules\OcopRubric\Models\OcopRubricOption;
use Modules\OcopRubric\Models\OcopScoringAnswer;
use Modules\OcopRubric\Models\OcopScoringSession;

class AnswerCriterionAction
{
    use AsAction;

    public function __construct(private readonly RecalculateSessionScoreAction $recalculate) {}

    public function handle(OcopScoringSession $session, AnswerCriterionData $data): OcopScoringSession
    {
        // Bất biến sau khi hoàn thành — DN không thể sửa lại điểm của 1 phiên đã chấm xong,
        // kể cả gọi thẳng endpoint (guard ở tầng Action, không chỉ ẩn nút trên UI).
        if ($session->is_locked || $session->status !== ScoringSessionStatus::InProgress->value) {
            throw new \DomainException(
                'Phiên chấm điểm này đã hoàn thành và bị khoá — không thể sửa câu trả lời. '
                . 'Dùng chức năng "Nhân bản" để tạo phiên mới nếu muốn chấm lại hoặc chấm cho sản phẩm khác.'
            );
        }

        return DB::transaction(function () use ($session, $data) {
            // Chỉ tiêu chí lá (is_scorable=true) mới được phép có câu trả lời — chặn ngay ở đây
            // thay vì dựa vào việc ScoringCalculator lọc is_scorable khi tính tổng (đúng nhưng để
            // lọt 1 bản ghi answer "vô nghĩa" gắn vào 1 Mục container sẽ gây khó hiểu khi audit dữ liệu).
            $criterion = OcopRubricCriterion::findOrFail($data->criterionId);
            if (!$criterion->is_scorable) {
                throw new \DomainException("Tiêu chí '{$criterion->code}' là Mục tổng hợp, không nhận câu trả lời trực tiếp.");
            }

            // KHÔNG bao giờ nhận points_awarded từ client — nếu DTO cho phép client tự gửi số điểm,
            // đó là một cách "sửa tiêu chí" trá hình (khác gì sửa thẳng option.points).
            // Điểm luôn được tra lại từ chính bảng option do hệ thống sở hữu, request chỉ được chọn ID.
            $option = $data->optionId
                ? OcopRubricOption::where('criterion_id', $data->criterionId)->findOrFail($data->optionId)
                : null;

            OcopScoringAnswer::updateOrCreate(
                ['session_id' => $session->id, 'criterion_id' => $data->criterionId],
                [
                    'option_id'      => $option?->id,
                    'points_awarded' => $option?->points ?? 0,   // ← nguồn sự thật duy nhất: OcopRubricOption
                    'needs_review'   => false,   // luôn false khi con người trực tiếp trả lời (khác nhánh Duplicate §8.4.2)
                    'evidence_note'  => $data->evidenceNote,
                    'answered_at'    => now(),
                ]
            );

            // Tính lại running score ngay — quiz UI cần phản hồi tức thì
            return $this->recalculate->handle($session);
        });
    }
}
```

`SkipCriterionAction` và `FlagDisqualifierAction` áp cùng guard đầu hàm y hệt trên (không lặp lại code ở đây cho gọn — cả 3 Action ghi vào session đều bắt buộc qua guard này).

```php
// Features/ScoringSession/Actions/CompleteScoringSessionAction.php
namespace Modules\OcopRubric\Features\ScoringSession\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Enums\ScoringSessionStatus;
use Modules\OcopRubric\Events\ScoringSessionCompleted;
use Modules\OcopRubric\Events\StarBandImproved;
use Modules\OcopRubric\Models\OcopScoringSession;

class CompleteScoringSessionAction
{
    use AsAction;

    public function handle(OcopScoringSession $session): OcopScoringSession
    {
        if ($session->status !== ScoringSessionStatus::InProgress->value) {
            throw new \DomainException('Phiên này đã hoàn thành hoặc đã bị huỷ trước đó.');
        }

        // Chặn hoàn thành khi còn câu trả lời map chéo rubric_version chưa được xác nhận lại
        // (chỉ phát sinh từ Trường hợp 2 của DuplicateScoringSessionAction — xem §8.4.2, Phase 4b)
        if ($session->answers()->where('needs_review', true)->exists()) {
            throw new \DomainException(
                'Còn tiêu chí được nhân bản chéo phiên bản chưa xác nhận lại — '
                . 'vui lòng xem lại từng tiêu chí được đánh dấu trước khi hoàn thành.'
            );
        }

        return DB::transaction(function () use ($session) {
            $session->update([
                'status'          => ScoringSessionStatus::Completed->value,
                'is_locked'       => true,   // khoá vĩnh viễn — xem AnswerCriterionAction guard ở trên
                'completed_at'    => now(),
                'duration_seconds'=> $session->started_at->diffInSeconds(now()),
            ]);

            if ($session->ocop_product_id) {
                $product = $session->product;

                if ($session->mode === 'practice') {
                    // "practice": theo dõi KỶ LỤC cao nhất — mục tiêu luyện tập là cải thiện dần,
                    // không ghi đè xuống thấp hơn nếu lần sau chấm optimistic hơn lần trước tệ hơn.
                    $isNewBest = $product->best_practice_score === null
                        || $session->total_score > $product->best_practice_score;

                    if ($isNewBest) {
                        $previousBest = $product->best_practice_star_rank;
                        $product->update([
                            'best_practice_score'     => $session->total_score,
                            'best_practice_star_rank' => $session->star_rank,
                            'status'                  => $product->status === 'self_assessed' ? 'self_assessed' : 'practicing',
                        ]);

                        if ($previousBest !== null && $session->star_rank > $previousBest) {
                            StarBandImproved::dispatch($product, $previousBest, $session->star_rank);
                        }
                    }
                } else {
                    // "self_assessment": luôn GHI ĐÈ bằng lần MỚI NHẤT, không so sánh cao/thấp — đây
                    // là hiện trạng thật của sản phẩm tại thời điểm này (đúng tinh thần Mẫu 02: phản
                    // ánh đúng thực tế hiện tại, không phải "thành tích tốt nhất từng đạt").
                    $product->update([
                        'latest_self_assessment_score'      => $session->total_score,
                        'latest_self_assessment_star_rank'  => $session->star_rank,
                        'latest_self_assessment_session_id' => $session->id,
                        'status'                             => 'self_assessed',
                    ]);
                }
            }

            // Sự kiện quan trọng nhất module này bắn ra — vertical chứng nhận tương lai lắng nghe
            ScoringSessionCompleted::dispatch($session);

            return $session->fresh();
        });
    }
}
```

### 8.3 Slice: PracticeDeck — "quick wins" trả lời "muốn lên hạng làm gì"

```php
// Features/ScoringSession/Queries/GetQuickWinsHandler.php
namespace Modules\OcopRubric\Features\ScoringSession\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\OcopRubric\Models\OcopScoringSession;

class GetQuickWinsHandler implements QueryHandlerInterface
{
    /** @return array{criterion_label:string, current_points:float, best_points:float, gain:float}[] */
    public function handle(QueryInterface $query): array
    {
        /** @var GetQuickWinsQuery $query */
        $session = OcopScoringSession::with([
            'rubricVersion.sections.criteria' => fn ($q) => $q->where('is_scorable', true)->with('options'),
            'answers',
        ])->findOrFail($query->sessionId);

        $answered = $session->answers->keyBy('criterion_id');
        $wins = [];

        foreach ($session->rubricVersion->sections as $section) {
            foreach ($section->criteria as $criterion) {
                $current = (float) ($answered[$criterion->id]->points_awarded ?? 0);
                $best    = (float) ($criterion->options->max('points') ?? 0);
                $gain    = round($best - $current, 2);

                if ($gain > 0) {
                    $wins[] = [
                        'criterion_label' => $criterion->label,
                        'current_points'  => $current,
                        'best_points'     => $best,
                        'gain'            => $gain,
                    ];
                }
            }
        }

        // Gợi ý "ăn điểm nhanh nhất" trước — gain cao nhất lên đầu
        usort($wins, fn ($a, $b) => $b['gain'] <=> $a['gain']);

        return array_slice($wins, 0, $query->limit ?? 5);
    }
}
```

### 8.4 Slice: ScoringSession — nhân bản phiên đã hoàn thành sang sản phẩm khác

Tình huống thật của HTX có 2–3 sản phẩm OCOP: chấm xong "Cam Cao Phong loại 1", muốn chấm tiếp "Cam Cao Phong loại 2" mà không phải lật lại từ đầu toàn bộ tiêu chí giống nhau (nguồn gốc, tổ chức sản xuất, sức mạnh cộng đồng...) — chỉ cần sửa những tiêu chí thật sự khác (cảm quan, sản lượng...).

Có đúng **3 trường hợp** khi nhân bản, phân biệt bằng `product_group_id` và `rubric_version_id` của sản phẩm đích so với phiên nguồn — mỗi trường hợp xử lý khác nhau, không được gộp chung:

| # | Điều kiện | Có map câu trả lời không | Độ tin cậy |
|---|---|---|---|
| 1 | Cùng `product_group_id` **và** cùng `rubric_version_id` | Copy toàn bộ, 1:1 theo `criterion_id`/`option_id` | Tuyệt đối chính xác — không phải "ánh xạ", là tái dùng thẳng cùng 1 hàng dữ liệu |
| 2 | Cùng `product_group_id` **nhưng** `rubric_version_id` khác (rubric đã publish bản mới ở giữa) | Map theo `code` + so nội dung, chỉ copy tiêu chí **nội dung y hệt**, còn lại để trống | Có kiểm soát — mỗi câu trả lời map được đánh dấu `needs_review=true`, bắt buộc người dùng xác nhận lại trước khi hoàn thành |
| 3 | Khác `product_group_id` | Không copy gì | Không áp dụng — 2 cây tiêu chí không tương ứng (đã phân tích ở câu hỏi trước, ví dụ Bộ #1 vs Bộ #12) |

#### 8.4.1 Trường hợp 1 — cùng `rubric_version_id` (ca phổ biến nhất, ví dụ đúng câu hỏi của bạn)

Đây là lý do thiết kế "an toàn tuyệt đối": `ocop_rubric_criteria`/`ocop_rubric_options` là bảng **system-level dùng chung**, không copy riêng theo từng session hay từng sản phẩm. Khi 2 sản phẩm A và B **cùng Bộ sản phẩm #1** và cả hai đang chấm theo **cùng 1 rubric_version đang active**, câu trả lời của session A tham chiếu `criterion_id`/`option_id` là **chính xác cùng một hàng trong DB** mà session B cũng sẽ dùng — không có "ánh xạ" nào cần tính toán, chỉ là copy y nguyên cặp khoá ngoại.

**Tính toán cụ thể** với dữ liệu thật của Bộ sản phẩm #1 (đã seed ở Phase 6, xem §1.1):

```
Phần A — Mục 1. TỔ CHỨC SẢN XUẤT (max 18đ)
  1.1 Nguồn gốc sản phẩm (max 3đ)
      option_id=101 "trồng cấp xã <50%"        → 1đ
      option_id=102 "trồng cấp xã 50–75%"      → 2đ
      option_id=103 "trồng cấp xã 75–100%"     → 3đ
  1.2 Vùng nguyên liệu được cấp chứng nhận (max 2đ)
      option_id=104 "không chứng nhận"          → 0đ
      option_id=105 "chứng nhận quốc gia"       → 1đ
      option_id=106 "chứng nhận quốc tế"        → 2đ

Session A ("Cam Cao Phong loại 1") đã hoàn thành:
  criterion_id=11 (1.1) → option_id=103 → 3đ
  criterion_id=12 (1.2) → option_id=106 → 2đ
  ... (các tiêu chí còn lại của A/B/C) → total_score = 78 → star_rank = 4

Nhân bản sang Session B ("Cam Cao Phong loại 2"), CÙNG rubric_version:
  criterion_id=11 → option_id=103 → 3đ   (copy y nguyên khoá ngoại, KHÔNG suy luận lại)
  criterion_id=12 → option_id=106 → 2đ   (copy y nguyên)
  → Session B khởi tạo với total_score = 78 (bằng A), needs_review = false trên mọi answer

DN vào deck của B, phát hiện "1.1 Nguồn gốc sản phẩm" của loại 2 chỉ đạt 50-75% (khác loại 1)
  → chọn lại option_id=102 cho criterion_id=11 qua AnswerCriterionAction bình thường
  → RecalculateSessionScoreAction tính lại: total_score giảm còn 77
  → Các criterion_id khác (12, ...) vẫn giữ nguyên vì DN không đụng vào
```

Không có bước "dịch"/"quy đổi" nào cả — đây chính là lý do trường hợp 1 được xếp vào "an toàn tuyệt đối": *bản chất nó không phải nhân bản dữ liệu, mà là tái sử dụng cùng 1 tham chiếu.*

#### 8.4.2 Trường hợp 2 — cùng Bộ sản phẩm, nhưng `rubric_version` đã đổi

Xảy ra khi: HTX chấm sản phẩm A lúc rubric Bộ #1 đang ở version 1; sau đó system_admin publish version 2 (sửa lại thang điểm theo văn bản mới); rồi HTX mới nhân bản sang sản phẩm B. Lúc này `PublishRubricVersionAction` đã tạo **toàn bộ criterion/option MỚI** cho version 2 (khác `id` với version 1, dù `code`/`label` có thể giữ nguyên — xem §16 "Immutable sau khi publish"). Copy thẳng theo `criterion_id` cũ sẽ **lỗi ngay lập tức** (foreign key không tồn tại ở version 2, hoặc tệ hơn là trỏ nhầm sang tiêu chí khác nếu ID trùng ngẫu nhiên) — bắt buộc phải map theo `code` và so nội dung trước khi copy.

```php
// Features/ScoringSession/Services/CrossVersionAnswerMapper.php
namespace Modules\OcopRubric\Features\ScoringSession\Services;

use Modules\OcopRubric\Models\OcopRubricVersion;

/**
 * CHỈ dùng khi source.rubric_version_id !== target rubric_version_id NHƯNG cùng product_group_id.
 * Không bao giờ map theo `id` (id chắc chắn khác nhau giữa 2 version — mỗi lần publish tạo cây
 * criterion/option hoàn toàn mới, xem PublishRubricVersionAction). Map theo `code`, và CHỈ chấp
 * nhận map khi nội dung (max_score + tập option label/points) giống hệt nhau — khác 1 ly cũng loại.
 */
class CrossVersionAnswerMapper
{
    /** @return array<int, array{criterion_id:int, option_id:?int, points:float}> keyed theo SOURCE criterion_id */
    public function map(OcopRubricVersion $sourceVersion, OcopRubricVersion $targetVersion, iterable $sourceAnswers): array
    {
        // flattenCriteria(): helper trên OcopRubricVersion, đệ quy toàn bộ cây (mọi độ sâu) thành 1
        // Collection phẳng — dùng lại đúng logic load cây của GetRubricTreeHandler (§5, RubricAuthoring),
        // không viết lại truy vấn đệ quy lần thứ 2 ở đây.
        $targetByCode = $targetVersion->flattenCriteria()->keyBy('code');
        $sourceByCode = $sourceVersion->flattenCriteria()->keyBy('id');
        $mapped = [];

        foreach ($sourceAnswers as $answer) {
            $sourceCriterion = $sourceByCode->get($answer->criterion_id);
            if (!$sourceCriterion || !$sourceCriterion->is_scorable) {
                continue; // không map — để trống, bắt DN tự chấm lại tiêu chí này
            }

            $targetCriterion = $targetByCode->get($sourceCriterion->code);
            if (!$targetCriterion || !$this->contentIdentical($sourceCriterion, $targetCriterion)) {
                continue; // tiêu chí bị xoá HOẶC nội dung đã đổi ở bản mới → không map, an toàn hơn map sai
            }

            $sourceOption = $sourceCriterion->options->firstWhere('id', $answer->option_id);
            $targetOption = $sourceOption
                ? $targetCriterion->options->first(fn ($o) => $o->label === $sourceOption->label
                    && bccomp((string) $o->points, (string) $sourceOption->points, 2) === 0)
                : null;

            $mapped[$answer->criterion_id] = [
                'criterion_id' => $targetCriterion->id,
                'option_id'    => $targetOption?->id,
                'points'       => $targetOption?->points ?? 0,
            ];
        }

        return $mapped;
    }

    /** So nội dung, không so id — 2 tiêu chí "giống nhau" khi cùng max_score và cùng tập (label, points) của option */
    private function contentIdentical($source, $target): bool
    {
        if (bccomp((string) $source->max_score, (string) $target->max_score, 2) !== 0) return false;
        if ($source->options->count() !== $target->options->count()) return false;

        $sourceSig = $source->options->map(fn ($o) => $o->label . '|' . number_format((float) $o->points, 2))->sort()->values();
        $targetSig = $target->options->map(fn ($o) => $o->label . '|' . number_format((float) $o->points, 2))->sort()->values();

        return $sourceSig->all() === $targetSig->all();
    }
}
```

Mọi câu trả lời map được ở nhánh này **luôn ghi `needs_review = true`** (khác hẳn Trường hợp 1) — vì dù nội dung đã kiểm tra khớp 100%, đây vẫn là suy luận qua `code` chứ không phải tái dùng trực tiếp cùng 1 hàng, nên bắt buộc con người xác nhận lại trước khi phiên được phép hoàn thành (xem guard trong `CompleteScoringSessionAction` bên dưới).

#### 8.4.3 Trường hợp 3 — khác Bộ sản phẩm

Đã phân tích chi tiết ở câu trả lời trước (ví dụ Bộ #1 vs Bộ #12: cùng đánh số "Mục 1.1" nhưng một bên tính theo % cấp xã, một bên theo % cấp tỉnh) — không map, phiên mới hoàn toàn trống.

#### DuplicateScoringSessionAction — gộp cả 3 trường hợp

```php
// Features/ScoringSession/Actions/DuplicateScoringSessionAction.php
namespace Modules\OcopRubric\Features\ScoringSession\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Enums\ScoringSessionStatus;
use Modules\OcopRubric\Events\ScoringSessionDuplicated;
use Modules\OcopRubric\Features\ScoringSession\Services\CrossVersionAnswerMapper;
use Modules\OcopRubric\Models\OcopProduct;
use Modules\OcopRubric\Models\OcopScoringAnswer;
use Modules\OcopRubric\Models\OcopScoringSession;

class DuplicateScoringSessionAction
{
    use AsAction;

    public function __construct(
        private readonly RecalculateSessionScoreAction $recalculate,
        private readonly CrossVersionAnswerMapper $mapper,
    ) {}

    public function handle(OcopScoringSession $source, OcopProduct $targetProduct, string $mode): OcopScoringSession
    {
        if ($source->status !== ScoringSessionStatus::Completed->value) {
            throw new \DomainException('Chỉ được nhân bản từ 1 phiên đã hoàn thành.');
        }

        if ($targetProduct->organization_id !== $source->organization_id) {
            // Phòng vệ chiều sâu — route/policy đã chặn trước, nhưng Action không tin tưởng lớp trên
            throw new \DomainException('Không thể nhân bản sang sản phẩm của tổ chức khác.');
        }

        $sourceGroupId       = $source->rubricVersion->product_group_id;
        $sameGroup           = $targetProduct->product_group_id === $sourceGroupId;
        $targetRubricVersion = $targetProduct->activeRubricVersion(); // luôn version ĐANG ACTIVE của bộ sản phẩm đích
        $exactSameVersion    = $sameGroup && $targetRubricVersion?->id === $source->rubric_version_id;

        return DB::transaction(function () use ($source, $targetProduct, $mode, $sameGroup, $exactSameVersion, $targetRubricVersion) {
            $newSession = OcopScoringSession::create([
                'organization_id'            => $targetProduct->organization_id,
                'ocop_product_id'            => $targetProduct->id,
                'rubric_version_id'          => $sameGroup ? $targetRubricVersion->id : $targetRubricVersion->id,
                'duplicated_from_session_id' => $source->id,
                'user_id'                    => auth()->id(),
                'employee_id'                => $source->employee_id,
                'mode'                       => $mode,
                'status'                     => ScoringSessionStatus::InProgress->value,
            ]);

            if ($exactSameVersion) {
                // Trường hợp 1 (§8.4.1) — copy y nguyên khoá ngoại, không cần review
                foreach ($source->answers as $answer) {
                    OcopScoringAnswer::create([
                        'session_id'     => $newSession->id,
                        'criterion_id'   => $answer->criterion_id,
                        'option_id'      => $answer->option_id,
                        'points_awarded' => $answer->points_awarded,
                        'needs_review'   => false,
                        'evidence_note'  => $answer->evidence_note,
                        'answered_at'    => now(),
                    ]);
                }
            } elseif ($sameGroup) {
                // Trường hợp 2 (§8.4.2) — map theo code + nội dung, bắt buộc needs_review=true
                $mapped = $this->mapper->map($source->rubricVersion, $targetRubricVersion, $source->answers);

                foreach ($mapped as $sourceCriterionId => $m) {
                    OcopScoringAnswer::create([
                        'session_id'     => $newSession->id,
                        'criterion_id'   => $m['criterion_id'],
                        'option_id'      => $m['option_id'],
                        'points_awarded' => $m['points'],
                        'needs_review'   => true,   // bắt buộc xác nhận lại — xem CompleteScoringSessionAction
                        'answered_at'    => now(),
                    ]);
                }
            }
            // else: Trường hợp 3 (§8.4.3) — khác Bộ sản phẩm, cố tình để trống

            $this->recalculate->handle($newSession);

            ScoringSessionDuplicated::dispatch($source, $newSession, $exactSameVersion, $sameGroup);

            return $newSession->fresh('answers');
        });
    }
}
```

*(Guard `needs_review` đã gộp thẳng vào bản đầy đủ của `CompleteScoringSessionAction` ở §8.2 — không lặp lại code ở đây.)*

`AnswerCriterionAction` (§8.2) khi ghi đè 1 câu trả lời luôn set `'needs_review' => false` — bất kỳ lần con người chủ động xác nhận/chọn lại tiêu chí nào (kể cả chọn lại đúng option cũ) đều coi là đã xác nhận, gỡ cờ review.

```php
// Features/ScoringSession/Http/ScoringSessionController.php (trích đoạn liên quan)
public function duplicate(DuplicateSessionRequest $request, OcopScoringSession $session, DuplicateScoringSessionAction $action)
{
    $this->authorize('duplicate', $session);

    // Cho phép 2 lối vào: chọn sản phẩm CÓ SẴN, hoặc tạo sản phẩm MỚI ngay trong bước này
    $targetProduct = $request->filled('target_product_id')
        ? OcopProduct::findOrFail($request->integer('target_product_id'))
        : RegisterProductAction::run(ProductData::from([
              'organization_id'  => $session->organization_id,
              'product_group_id' => $request->integer('product_group_id') ?? $session->rubricVersion->product_group_id,
              'name'             => $request->string('new_product_name'),
          ]));

    $newSession = $action->handle($session, $targetProduct, $request->string('mode', $session->mode));

    // KHÔNG dùng wasRecentlyCreated (luôn true ở cả 3 nhánh) — phân biệt bằng answers đã copy
    // được bao nhiêu và bao nhiêu cần review, để hiện đúng thông báo cho từng trong 3 trường hợp §8.4.
    $needsReview = $newSession->answers->where('needs_review', true)->count();
    $carried     = $newSession->answers->count();

    $message = match (true) {
        $carried === 0            => 'Khác bộ tiêu chí (hoặc nội dung tiêu chí đã đổi hoàn toàn) — phiên mới trống, cần chấm lại từ đầu.',
        $needsReview > 0          => "Đã nhân bản {$carried} tiêu chí, trong đó {$needsReview} tiêu chí map từ phiên bản rubric cũ — vui lòng xác nhận lại trước khi hoàn thành.",
        default                   => 'Đã nhân bản toàn bộ câu trả lời — kiểm tra lại các tiêu chí khác biệt rồi hoàn thành.',
    };

    return redirect()->route('ocop.practice.deck', $newSession)->with('success', $message);
}
```

---

## 9. Trải nghiệm "Bộ bài luyện tập" (Practice Deck)

Không dùng Tabulator (đó là view layer cho danh sách server-side, không hợp cho trải nghiệm tuần tự từng thẻ). Dùng **Alpine.js component riêng**, theo đúng nguyên tắc tách state trong `docs/module-list-pattern.md` §7 (lib instance ngoài reactive state, chỉ dữ liệu cần re-render mới vào Alpine state):

```
Bước 1 — /ocop/practice/start
  Chọn OcopProduct (hoặc "luyện tập chay" không gắn sản phẩm)
  → StartScoringSessionAction: tạo OcopScoringSession(mode=practice), lấy rubric_version đang active

Bước 2 — /ocop/practice/{session}/deck   (giao diện lật lá bài)
  Mỗi lần hiện 1 OcopRubricCriterion (is_scorable=true), theo thứ tự path:
    - Mặt trước: label + requirement_note (nếu có)
    - Các option hiện thành nút bấm lớn — chọn xong POST → AnswerCriterionAction
    - Response trả về ngay: running score theo A/B/C, progress (x/y), star_rank hiện tại
    - Thanh tiến độ hạng sao: "Đang 3★ (62đ) — cần thêm 8đ để chạm 4★ (70đ)"
  Nút "Bỏ qua" → SkipCriterionAction (không tính điểm, đánh dấu đã xem)
  Nút "Đánh dấu rủi ro loại hồ sơ" cho từng OcopRubricDisqualifier → FlagDisqualifierAction

Bước 3 — /ocop/practice/{session}/summary
  CompleteScoringSessionAction đã chạy (is_locked=true, KHÔNG còn nút sửa câu trả lời) → hiện:
    - Tổng điểm + hạng sao dự đoán + breakdown A/B/C — chỉ xem, không sửa được nữa
    - GetQuickWinsHandler: top 5 "quick win" — tiêu chí nào cải thiện nhanh nhất
    - Nếu mode=self_assessment: banner rõ ràng "Đây là ước lượng tự chấm theo Mẫu 02 —
      kết quả công nhận chính thức do UBND tỉnh/Bộ NN&MT quyết định"
    - Nếu star_rank tăng so với best trước đó → hiệu ứng nhẹ (không rình rang) + lưu lịch sử luyện tập
    - Nút "Nhân bản sang sản phẩm khác" (§8.4) — chọn 1 OcopProduct có sẵn của tổ chức hoặc tạo mới
      ngay tại chỗ; nếu cùng Bộ sản phẩm → phiên mới có sẵn toàn bộ câu trả lời để chỉnh, nếu khác
      Bộ sản phẩm → hệ thống báo rõ "khác bộ tiêu chí, cần chấm lại từ đầu" thay vì âm thầm map sai

Muốn xem lại 1 phiên đã hoàn thành trước đó (không sửa được) → GET /ocop/practice/{session}/summary
vẫn truy cập được bất kỳ lúc nào — đây là nguồn "lịch sử mỗi lần thực hành" cho GetPracticeHistoryHandler.
```

`GetPracticeHistoryHandler`/`GetLeaderboardHandler` (Slice `PracticeDeck`) chỉ là query thuần trên `ocop_scoring_sessions` (mode=`practice`, group by `employee_id`) — không cần bảng mới, dùng lại dữ liệu session đã có (đúng tinh thần "không có logic trong Controller", chỉ đọc).

---

## 10. Seeding nội dung 26 Bộ sản phẩm

Nội dung Phụ lục II quá lớn để viết tay 26 file PHP riêng (mỗi bộ có hàng chục tiêu chí lồng nhau). Chiến lược:

1. **1 fixture PHP array duy nhất** `database/seeders/fixtures/ocop_rubrics.php` — mỗi phần tử mảng mô tả 1 bộ sản phẩm dưới dạng cây lồng nhau (`code`, `sections: [A/B/C => [criteria: [...]]]`), chuyển thể trực tiếp từ Phụ lục II.
2. `OcopRubricVersionSeeder` đọc fixture, với mỗi bộ sản phẩm: tạo `OcopProductGroup` (nếu chưa có) → tạo `OcopRubricVersion` (version_no=1, status=draft) → đệ quy tạo `OcopRubricSection` → `OcopRubricCriterion` (dùng lại `UpsertCriterionAction` để tận dụng logic tính `path`/`depth`) → `OcopRubricOption` → cuối cùng gọi `PublishRubricVersionAction` (validate + activate).
3. Nhập liệu 26 bộ **không làm trong 1 lần** — xem Phase 6 ở §17: ưu tiên 3–5 bộ phổ biến nhất với HTX nông nghiệp trước (rau củ quả tươi, gạo ngũ cốc, chế biến rau củ quả, mật ong, chè), phần còn lại nhập dần — vì `PublishRubricVersionAction` tự validate nên bộ nào sai sẽ báo lỗi ngay, không sợ "nhập ẩu 26 bộ cùng lúc rồi mới phát hiện sai".

---

## 11. Permissions

### Thêm vào `PermissionEnum.php`

```php
case OCOP_RUBRIC_MANAGE   = 'ocop_rubric.manage';    // system_admin — sửa cây tiêu chí, publish version
case OCOP_PRODUCT_VIEW    = 'ocop_product.view';     // xem sản phẩm OCOP của tổ chức mình
case OCOP_PRODUCT_MANAGE  = 'ocop_product.manage';   // tạo/sửa sản phẩm
case OCOP_PRACTICE_USE    = 'ocop_practice.use';     // luyện tập (mode=practice)
case OCOP_SELF_ASSESS_USE = 'ocop_self_assess.use';  // làm Mẫu 02 số hoá (mode=self_assessment)
```

### Thêm vào `config/permissions.php`

```php
R::ADMIN->value => [/* existing */ P::OCOP_RUBRIC_MANAGE->value],

R::CEO->value => [/* existing */
    P::OCOP_PRODUCT_VIEW->value, P::OCOP_PRODUCT_MANAGE->value,
    P::OCOP_PRACTICE_USE->value, P::OCOP_SELF_ASSESS_USE->value,
],

R::OPS->value => [/* existing */
    P::OCOP_PRODUCT_VIEW->value, P::OCOP_PRODUCT_MANAGE->value, P::OCOP_PRACTICE_USE->value,
],

R::HR->value => [/* existing */ P::OCOP_PRACTICE_USE->value], // nhân viên luyện tập, không sửa sản phẩm

R::VIEWER->value => [/* existing */ P::OCOP_PRODUCT_VIEW->value],
```

`OCOP_SELF_ASSESS_USE` cố ý tách khỏi `OCOP_PRACTICE_USE` — self_assessment là văn bản có giá trị chuẩn bị hồ sơ pháp lý (Điều 6.1.a), không nên để mọi nhân viên tuỳ ý tạo, chỉ vai trò có trách nhiệm (CEO/Ops) mới nên khởi tạo.

### `OcopScoringSessionPolicy` — 3 ability, không chỉ chặn sửa

```php
// Policies/OcopScoringSessionPolicy.php
namespace Modules\OcopRubric\Policies;

use App\Models\User;
use Modules\OcopRubric\Enums\ScoringSessionStatus;
use Modules\OcopRubric\Models\OcopScoringSession;

class OcopScoringSessionPolicy
{
    /** Xem lịch sử/leaderboard: mọi thành viên CÙNG tổ chức có quyền luyện tập đều xem được,
     *  không giới hạn "chỉ xem session của chính mình" — luyện tập là hoạt động tập thể của HTX. */
    public function view(User $user, OcopScoringSession $session): bool
    {
        return $user->currentOrganizationId() === $session->organization_id
            && ($user->can('ocop_practice.use') || $user->can('ocop_self_assess.use'));
    }

    /** Trả lời/sửa: chỉ khi phiên còn in_progress — is_locked là nguồn sự thật cuối cùng,
     *  không suy luận qua status để tránh 2 nơi định nghĩa "đã khoá chưa" lệch nhau. */
    public function answer(User $user, OcopScoringSession $session): bool
    {
        return $this->view($user, $session)
            && !$session->is_locked
            && $session->status === ScoringSessionStatus::InProgress->value;
    }

    /** Nhân bản: chỉ từ phiên đã hoàn thành thật sự (không phải abandoned). */
    public function duplicate(User $user, OcopScoringSession $session): bool
    {
        return $this->view($user, $session)
            && $session->status === ScoringSessionStatus::Completed->value;
    }
}
```

---

## 12. Routes

```php
// Modules/OcopRubric/routes/web.php
use App\Enums\PermissionEnum as P;

// ── System Admin: catalog + rubric authoring ──────────────────────────────
Route::middleware(['web', 'auth', 'can:' . P::OCOP_RUBRIC_MANAGE->value])
    ->prefix('dashboard/ocop-rubric/admin')
    ->name('ocop_rubric.admin.')
    ->group(function () {
        Route::resource('product-groups', ProductGroupController::class)->except(['show']);
        Route::get('product-groups/{productGroup}/versions/{version}/tree', [RubricAuthoringController::class, 'tree'])
            ->name('versions.tree');
        Route::post('product-groups/{productGroup}/versions/{version}/publish', [RubricAuthoringController::class, 'publish'])
            ->name('versions.publish');
        Route::post('product-groups/{productGroup}/versions/{version}/clone', [RubricAuthoringController::class, 'clone'])
            ->name('versions.clone');
        Route::post('criteria', [RubricAuthoringController::class, 'storeCriterion'])->name('criteria.store');
        Route::put('criteria/{criterion}', [RubricAuthoringController::class, 'updateCriterion'])->name('criteria.update');
    });

// ── Tổ chức: sản phẩm + luyện tập + tự chấm ────────────────────────────────
Route::middleware(['web', 'auth', 'tenant', 'can:' . P::OCOP_PRODUCT_VIEW->value])
    ->prefix('dashboard/ocop')
    ->name('ocop.')
    ->group(function () {
        Route::resource('products', ProductController::class);

        Route::middleware('can:' . P::OCOP_PRACTICE_USE->value)->group(function () {
            Route::get('practice/start',                     [PracticeDeckController::class, 'start'])->name('practice.start');
            Route::post('practice',                           [ScoringSessionController::class, 'store'])->name('practice.create');
            Route::get('practice/{session}/deck',             [ScoringSessionController::class, 'deck'])->name('practice.deck');
            Route::post('practice/{session}/answer',          [ScoringSessionController::class, 'answer'])->name('practice.answer');
            Route::post('practice/{session}/skip',            [ScoringSessionController::class, 'skip'])->name('practice.skip');
            Route::post('practice/{session}/flag-disqualifier', [ScoringSessionController::class, 'flagDisqualifier'])->name('practice.flag');
            Route::post('practice/{session}/complete',        [ScoringSessionController::class, 'complete'])->name('practice.complete');
            Route::get('practice/{session}/summary',          [ScoringSessionController::class, 'summary'])->name('practice.summary');
            Route::post('practice/{session}/duplicate',       [ScoringSessionController::class, 'duplicate'])->name('practice.duplicate');
            Route::get('practice/history',                    [PracticeDeckController::class, 'history'])->name('practice.history');
            Route::get('practice/leaderboard',                [PracticeDeckController::class, 'leaderboard'])->name('practice.leaderboard');
        });

        Route::middleware('can:' . P::OCOP_SELF_ASSESS_USE->value)->group(function () {
            Route::post('products/{product}/self-assessment', [ScoringSessionController::class, 'startSelfAssessment'])
                ->name('self_assessment.start');
        });
    });
```

---

## 13. Events & Listeners

```
EventServiceProvider:

RubricVersionPublished    → (không listener bắt buộc — log qua Observer nếu cần audit)
ProductRegistered         → (không listener bắt buộc ở phiên bản đầu)
ScoringSessionCompleted   → (mode=official/self_assessment: KHÔNG có listener trong module này —
                              đây là "extension point" cố ý để lại cho OcopCertification vertical
                              tương lai lắng nghe và tự tạo hồ sơ nháp)
StarBandImproved          → (tuỳ chọn: NotifyStarBandImprovedListener — thông báo nhẹ cho CEO/Ops,
                              không bắt buộc ở Phase đầu)
ScoringSessionDuplicated  → (không listener bắt buộc — mang theo $exactSameVersion/$sameGroup để nơi
                              khác tuỳ chọn thống kê, vd. Report module đếm bao nhiêu % lần nhân bản
                              rơi vào Trường hợp 2/3 (§8.4) — tín hiệu cho biết rubric có nên gộp bớt
                              số version hay không)
```

Tất cả listener (nếu có) đều **đồng bộ** — không `ShouldQueue`, nhất quán với `Subscription`/`Sop` (khối lượng thao tác nhỏ, không cần hàng đợi).

---

## 14. ServiceProvider

```php
// Providers/OcopRubricServiceProvider.php
class OcopRubricServiceProvider extends ModuleServiceProvider
{
    protected $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        Gate::policy(OcopProduct::class, OcopProductPolicy::class);
        Gate::policy(OcopRubricVersion::class, OcopRubricVersionPolicy::class);
        Gate::policy(OcopScoringSession::class, OcopScoringSessionPolicy::class);

        OcopProduct::observe(OcopProductObserver::class);
    }
}
```

---

## 15. Ranh giới với các module khác (đối chiếu nhanh)

| Module | Quan hệ với `OcopRubric` |
|---|---|
| `Assessment` | Không quan hệ trực tiếp. Cùng Layer 4, khác miền (nhân sự vs sản phẩm), khác thuật toán cộng điểm (§2). |
| `Survey` | Không dùng. Có thể tương lai dùng `Survey` để làm khảo sát "mức độ sẵn sàng OCOP" (khác với chấm điểm sản phẩm) — không thuộc phạm vi spec này. |
| `Sop` | Chưa tích hợp ở Phase hiện tại. `SopApprovalFlow` là ứng viên hạ tầng cho quy trình phê duyệt xã→tỉnh→trung ương của vertical chứng nhận tương lai (§3.2). |
| `KcItem` | Chưa tích hợp ở Phase hiện tại. Là nơi hồ sơ pháp lý đính kèm (ĐKKD, phiếu kiểm nghiệm...) sẽ được lưu trong vertical tương lai, liên kết qua `ocop_product_id`. |
| `Deployment`/`Vertical` | Không dùng chung bảng. `VerticalTemplate` cho phép clone theo tổ chức — sai bản chất với rubric pháp lý bất biến (§2). |
| `WorkflowAutomation` | Điểm nối tương lai: lắng nghe `ScoringSessionCompleted` để tự tạo Task "hoàn thiện hồ sơ pháp lý" khi tự chấm xong nhưng dưới điểm mục tiêu — **không xây ở Phase hiện tại**, chỉ đảm bảo event đã bắn sẵn để dễ nối dây sau. |
| `Subscription` | `OCOP_PRACTICE_USE`/`OCOP_SELF_ASSESS_USE` nên là `feature slug` (`module.ocop_rubric`) để gate theo gói dịch vụ — thêm vào `config/subscription.php` khi thương mại hoá. |

---

## 16. Phased Implementation

### Phase 0 — Chuẩn bị dữ liệu pháp lý `[P0 — trước khi viết code]`

- [ ] Số hoá đầy đủ Phụ lục I (26 bộ sản phẩm + ngành + cơ quan quản lý) thành fixture PHP/JSON
- [ ] Số hoá **3–5 bộ tiêu chí ưu tiên** (rau củ quả tươi, gạo ngũ cốc, chế biến rau củ quả, mật ong, chè) thành cây criteria/option đầy đủ — làm mẫu để validate schema trước khi cam kết cấu trúc
- [ ] Xác nhận với KienDH: danh sách 3–5 bộ ưu tiên có đúng nhu cầu khách hàng hiện tại không (xem §18 Open Questions)

### Phase 1 — Core Schema + Catalog `[P0]`

- [ ] `php artisan module:make OcopRubric`
- [ ] Viết 11 migrations theo §7
- [ ] Models: `OcopProductGroup`, `OcopRubricVersion/Section/Criterion/Option/Disqualifier`, `OcopStarBand` (system-level, không `TenantAwareModel`)
- [ ] `OcopStarBandSeeder` — 5 dòng theo Điều 3.3
- [ ] `OcopProductGroupSeeder` — 26 dòng theo Phụ lục I
- [ ] Slice `ProductGroupCatalog` đầy đủ (CQRS list theo `docs/module-list-pattern.md`)
- [ ] Thêm `OCOP_RUBRIC_MANAGE` vào `PermissionEnum` + `config/permissions.php`, `php artisan permissions:sync`
- [ ] Unit test: `OcopProductGroupSeederTest` — đếm đúng 26 dòng, đúng 6 industry_code

### Phase 2 — Rubric Authoring `[P0]`

- [ ] Models tenant-agnostic còn lại (`OcopRubricSection/Criterion/Option/Disqualifier`) + quan hệ tự tham chiếu
- [ ] `UpsertCriterionAction` — tự tính `path`/`depth` khi tạo/di chuyển node (theo pattern `production_areas`)
- [ ] `ValidateRubricIntegrityHandler` (§8.1) — unit test kỹ với ít nhất 3 case: đúng, sai tổng con≠cha, sai tổng A+B+C≠100
- [ ] `CreateRubricVersionAction`, `CloneRubricVersionAction`, `PublishRubricVersionAction`
- [ ] `RubricAuthoringController` + view cây kéo-thả (system_admin only)
- [ ] Nhập liệu 3–5 bộ ưu tiên từ Phase 0 → publish thật, xác nhận validate pass

### Phase 3 — Product Registry `[P1]`

- [ ] `OcopProduct` (`TenantAwareModel`) + migration (gồm cặp `best_practice_*` và `latest_self_assessment_*` tách riêng — xem §18)
- [ ] Slice `ProductRegistry` đầy đủ (Actions + CQRS list + `ProductData`)
- [ ] `OcopProductObserver` — log activity theo `getActivitylogOptions()` chuẩn `TenantAwareModel`
- [ ] `OCOP_PRODUCT_VIEW`/`OCOP_PRODUCT_MANAGE` vào permissions
- [ ] Thêm mục "Sản phẩm OCOP" vào sidebar (theo `config/permissions.php` `visibleModules()`)

### Phase 4 — Scoring Engine + Practice Deck `[P1]`

- [ ] `OcopScoringSession`/`OcopScoringAnswer`/`OcopScoringDisqualifierFlag` (migrations + models, gồm cột `is_locked`, `needs_review`, `duplicated_from_session_id`) + migration bổ sung FK `latest_self_assessment_session_id` lên `ocop_products` (§7, tách riêng vì phụ thuộc vòng)
- [ ] `ScoringCalculator` (§8.2) — **unit test độc lập, không cần DB thật**, dùng version giả lập trong bộ nhớ
- [ ] `RecalculateSessionScoreAction` (tách riêng, dùng chung Answer + Duplicate — §8.2/§8.4), **dùng `lockForUpdate()`** chống lost-update khi 2 request answer() gần như đồng thời
- [ ] `StartScoringSessionAction` — resume session `in_progress` có sẵn thay vì tạo trùng, throw rõ ràng khi bộ sản phẩm chưa có rubric active
- [ ] `AbandonSessionAction` — lối thoát tường minh khi DN muốn bỏ hẳn 1 phiên đang treo
- [ ] `AnswerCriterionAction` — validate `criterion.is_scorable` trước khi ghi (từ chối ghi answer vào node container), luôn set `needs_review=false`
- [ ] `SkipCriterionAction`, `FlagDisqualifierAction`, `CompleteScoringSessionAction` (đã gồm sẵn guard `needs_review`, no-op cho tới khi Phase 4b có dữ liệu thật) — **tất cả Action ghi vào session đều guard `is_locked`/`status` ở đầu hàm**, không tin tưởng UI đã ẩn nút
- [ ] `OcopScoringSessionPolicy` — `view()`/`answer()`/`duplicate()` (§11)
- [ ] `DuplicateScoringSessionAction` (§8.4) — **chỉ Trường hợp 1 và 3** (cùng version → copy thẳng; khác Bộ sản phẩm → để trống). Trường hợp 2 (khác version) tạm throw "chưa hỗ trợ, vui lòng chấm lại thủ công" — tần suất xảy ra gần như bằng 0 cho tới khi có Bộ sản phẩm nào thật sự cần publish version 2
- [ ] `GetSessionProgressHandler`, `GetNextCriterionHandler`, `GetQuickWinsHandler` (§8.3)
- [ ] Events: `ScoringSessionCompleted`, `StarBandImproved`, `ScoringSessionDuplicated` (dispatch nhưng chưa cần listener)
- [ ] Alpine.js component `ocop-practice-deck` (lật thẻ, progress bar, running score) — theo pattern `docs/module-list-pattern.md` §7 (lib instance ngoài reactive state)
- [ ] Views: `practice/start.blade.php`, `deck.blade.php`, `summary.blade.php` (nút "Nhân bản sang sản phẩm khác" + "Bỏ dở phiên này" trên summary/deck)
- [ ] `OCOP_PRACTICE_USE`/`OCOP_SELF_ASSESS_USE` vào permissions
- [ ] Integration test: hoàn thành 1 session end-to-end → đúng tổng điểm, đúng star_rank
- [ ] Integration test: `SessionImmutabilityTest` + `DuplicateScoringSessionTest` (§17, phần Trường hợp 1 & 3) pass đầy đủ
- [ ] Integration test: mở 2 request `answer()` song song cho 2 tiêu chí khác nhau cùng session → cả 2 điểm đều được cộng đúng, không mất dữ liệu nào (kiểm chứng `lockForUpdate`)

### Phase 4b — Nhân bản chéo phiên bản rubric (Trường hợp 2, §8.4.2) `[P2 — làm khi thật sự có version 2 đầu tiên]`

- [ ] `CrossVersionAnswerMapper` (§8.4.2) — map theo `code` + so nội dung (`max_score` + tập option label/points), **không bao giờ map theo `id`**
- [ ] `DuplicateScoringSessionAction` gọi `CrossVersionAnswerMapper` thay vì throw "chưa hỗ trợ" ở Trường hợp 2
- [ ] UI: badge "cần xác nhận lại" trên các tiêu chí có `needs_review=true` trong deck
- [ ] Unit test `CrossVersionAnswerMapperTest` (§17) — case nội dung y hệt, case đổi max_score, case đổi option, case tiêu chí bị xoá

### Phase 5 — Practice History & Leaderboard `[P2]`

- [ ] `GetPracticeHistoryHandler` — lịch sử luyện tập theo nhân viên/sản phẩm
- [ ] `GetLeaderboardHandler` — điểm cao nhất theo nhân viên trong tổ chức (nhẹ nhàng, không ép buộc)
- [ ] `PracticeDeckController::history/leaderboard` + views

### Phase 6 — Seed đầy đủ 26 Bộ sản phẩm `[P2]`

- [ ] Số hoá 21 bộ sản phẩm còn lại (fixture `ocop_rubrics.php` đầy đủ)
- [ ] Chạy `OcopRubricVersionSeeder` cho toàn bộ 26 bộ, publish từng bộ, xác nhận `ValidateRubricIntegrityHandler` pass 100%
- [ ] Đối chiếu tay ít nhất 5 bộ ngẫu nhiên với Phụ lục II gốc (đọc lại `docs/bnn_txng.html`/PDF) để bắt lỗi nhập liệu

### Phase 7 — (Ngoài phạm vi, ghi nhận roadmap) OcopCertification Vertical `[P3 — tương lai]`

- [ ] `OcopCertification` vertical mới: hồ sơ điện tử (dựa trên `OcopScoringSession` mode=`self_assessment` đã hoàn thành), quy trình xã→tỉnh→trung ương (mở rộng `Sop.SopApprovalFlow` theo cấp hành chính), Hội đồng/Tổ tư vấn, cấp/thu hồi giấy chứng nhận (36 tháng), đợt đánh giá theo lịch (Điều 5), kiểm tra giám sát hậu kiểm (Điều 9)
- [ ] Listener `CreateDraftDossierOnSelfAssessment` lắng nghe `ScoringSessionCompleted`
- [ ] Tích hợp `KcItem` cho hồ sơ pháp lý đính kèm

---

## 17. Testing Strategy

```
Tests/Unit/Features/RubricAuthoring/ValidateRubricIntegrityHandlerTest.php
    ✓ Bộ tiêu chí hợp lệ → valid=true, errors=[]
    ✓ Tổng điểm con của 1 Mục ≠ max_score cha → valid=false, có message đúng mã Mục
    ✓ Tổng A+B+C ≠ 100 → valid=false
    ✓ Option cao nhất vượt max_score tiêu chí lá → valid=false

Tests/Unit/Features/ScoringSession/ScoringCalculatorTest.php
    ✓ Chọn option ở mọi tiêu chí lá điểm cao nhất → total = 100, star_rank = 5, is_certifiable = true
    ✓ Không chọn gì → total = 0, star_rank = 1, is_certifiable = false
    ✓ Total = 69 → star_rank = 3 (không phải 4, đúng biên "50 đến dưới 70")
    ✓ Total = 70 đúng biên → star_rank = 4 (không phải 3)
    ✓ Total = 30 đúng biên → star_rank = 3 theo Điều 3.3 (biên dưới thuộc hạng cao hơn)

Tests/Feature/ScoringSession/PracticeSessionFlowTest.php
    ✓ Start session → status=in_progress, criteria_total đúng số tiêu chí is_scorable trong version
    ✓ Start session lần 2 khi đã có session in_progress cùng product+mode → trả về ĐÚNG session cũ,
      không tạo bản ghi mới (chống trùng khi 2 tab/2 người cùng bấm "bắt đầu")
    ✓ Start session cho sản phẩm thuộc Bộ sản phẩm chưa publish rubric nào → throw DomainException
      rõ ràng, không phải lỗi 500 khó hiểu
    ✓ Answer từng tiêu chí → running score cập nhật đúng ngay sau mỗi câu
    ✓ Answer vào 1 criterion_id có is_scorable=false → throw DomainException, không tạo answer
    ✓ Complete → status=completed, is_locked=true, duration_seconds > 0, event ScoringSessionCompleted đã dispatch
    ✓ mode=practice: session thứ 2 điểm cao hơn best_practice_score cũ → cập nhật + StarBandImproved
      dispatch; điểm thấp hơn → KHÔNG bị ghi đè xuống thấp hơn
    ✓ mode=self_assessment: LUÔN ghi đè latest_self_assessment_score/star_rank bằng lần mới nhất,
      kể cả khi thấp hơn lần trước — không so sánh cao/thấp như practice
    ✓ Product vừa có best_practice_score cao (vd. 90 từ practice) vừa có
      latest_self_assessment_score thấp hơn (vd. 72 từ self_assessment) → 2 cặp cột độc lập, không
      cái nào ghi đè cái kia
    ✓ AbandonSessionAction trên session in_progress → status=abandoned, is_locked=true
    ✓ AbandonSessionAction trên session đã completed → throw DomainException
    ✓ Sau khi abandon, StartScoringSessionAction gọi lại cho cùng product+mode → tạo session MỚI
      (không resume nhầm session đã abandoned, vì query chỉ lọc status=in_progress)

Tests/Unit/Features/ScoringSession/RecalculateSessionScoreActionConcurrencyTest.php
    ✓ 2 request AnswerCriterionAction chạy gần như đồng thời cho 2 criterion_id khác nhau cùng 1
      session → cả 2 answer đều được lưu, total_score cuối cùng phản ánh đủ cả 2 (không mất câu nào
      do lost-update nhờ lockForUpdate trong RecalculateSessionScoreAction)

Tests/Feature/ScoringSession/SessionViewPolicyTest.php
    ✓ Nhân viên khác trong CÙNG tổ chức, có OCOP_PRACTICE_USE → xem được summary/history của
      session do đồng nghiệp khác tạo (luyện tập là hoạt động tập thể, không riêng tư theo user_id)
    ✓ User thuộc tổ chức KHÁC → 403 khi cố xem session không thuộc org mình

Tests/Feature/RubricAuthoring/PublishRubricVersionTest.php
    ✓ Publish version hợp lệ → status=active, version active cũ (nếu có) tự chuyển retired
    ✓ Publish version không hợp lệ → throw DomainException, KHÔNG đổi status

Tests/Feature/ProductRegistry/ProductPermissionTest.php
    ✓ HR không có OCOP_PRODUCT_MANAGE → 403 khi tạo sản phẩm
    ✓ CEO có đủ quyền → tạo/luyện tập/tự chấm được cả 3

Tests/Feature/ScoringSession/SessionImmutabilityTest.php
    ✓ Session status=completed → gọi AnswerCriterionAction → throw DomainException, DB không đổi
    ✓ Session status=completed → gọi SkipCriterionAction / FlagDisqualifierAction → throw DomainException
    ✓ Session status=completed → gọi CompleteScoringSessionAction lần 2 → throw DomainException
    ✓ Session status=in_progress → answer bình thường vẫn hoạt động (guard không chặn nhầm)
    ✓ Policy `answer` trả false khi is_locked=true — API trả 403 trước khi Action kịp throw

Tests/Feature/ScoringSession/DuplicateScoringSessionTest.php
    ✓ Nhân bản từ session chưa completed → throw DomainException
    ✓ [Trường hợp 1 — §8.4.1] Cùng product_group_id, cùng rubric_version_id → answers copy đủ 1:1
      theo đúng criterion_id/option_id, total_score phiên mới = total_score phiên nguồn, mọi
      answer có needs_review=false
    ✓ [Trường hợp 1] Sau khi copy, sửa lại 1 tiêu chí trên phiên mới → chỉ tiêu chí đó đổi, phiên
      nguồn không đổi (2 bản ghi độc lập, không phải reference dùng chung)
    ✓ [Trường hợp 3 — §8.4.3] Nhân bản sang sản phẩm Bộ sản phẩm KHÁC (vd. Bộ #1 → Bộ #12) → answers
      KHÔNG được copy, session mới rỗng, rubric_version_id trỏ đúng bộ tiêu chí của sản phẩm đích
    ✓ Nhân bản sang sản phẩm của TỔ CHỨC KHÁC → throw DomainException dù bypass qua Action trực tiếp
    ✓ `duplicated_from_session_id` lưu đúng ID phiên nguồn — truy vết lineage được
    ✓ Controller message phân biệt đúng cả 3 trường hợp theo `answers->count()` + `needs_review`
      count, KHÔNG dùng `wasRecentlyCreated` (session mới luôn "vừa tạo" ở mọi nhánh — dùng sai cờ
      này từng là bug ở bản nháp đầu)

Tests/Unit/Features/ScoringSession/CrossVersionAnswerMapperTest.php
    ✓ [Trường hợp 2 — §8.4.2] Tiêu chí giữ nguyên `code`, `max_score`, và tập (label, points) của
      option y hệt giữa 2 version → map thành công, đúng `option_id` của version đích
    ✓ Tiêu chí đổi `max_score` giữa 2 version (dù cùng code) → KHÔNG map, bỏ qua tiêu chí đó
    ✓ Tiêu chí giữ nguyên max_score nhưng đổi points của 1 option → KHÔNG map (an toàn hơn map sai)
    ✓ Tiêu chí bị xoá ở version mới (code không còn tồn tại) → KHÔNG map, không throw exception
    ✓ Tiêu chí thêm mới ở version đích (không có trong source) → không xuất hiện trong kết quả map,
      tự động cần DN trả lời như tiêu chí mới hoàn toàn
    ✓ Tất cả kết quả map trả về (dù nội dung khớp 100%) đều phải được caller gán needs_review=true —
      mapper không tự set cờ này, đây là trách nhiệm của DuplicateScoringSessionAction

Tests/Feature/ScoringSession/CrossVersionCompleteGuardTest.php
    ✓ Session còn ít nhất 1 answer needs_review=true → CompleteScoringSessionAction throw DomainException
    ✓ Sau khi AnswerCriterionAction ghi đè answer đó (kể cả chọn lại đúng option cũ) → needs_review=false
    ✓ Toàn bộ needs_review đã về false → CompleteScoringSessionAction chạy bình thường
```

---

## 18. Key Design Decisions

| Quyết định | Lý do | Tradeoff |
|---|---|---|
| Rubric là bảng system-level, không tenant-scoped, không cho clone theo tổ chức | Bộ tiêu chí do Thủ tướng ban hành, giống nhau bắt buộc cho mọi tổ chức — khác hẳn triết lý "Vertical Template" (vốn cho phép mỗi tổ chức tùy biến checklist riêng) | Muốn sửa rubric phải qua UI admin hệ thống, tổ chức không tự thêm/sửa tiêu chí được — đúng ý muốn, không phải hạn chế |
| `ocop_rubric_criteria` tự tham chiếu + materialized path, không phải 3 bảng cứng Section/Group/Criterion | Đã kiểm chứng 2 bộ sản phẩm khác hẳn nhau (rau củ quả vs dịch vụ du lịch) đều cần độ sâu lồng nhau khác nhau — cây linh hoạt xử lý được mọi trường hợp, 3 bảng cứng thì không | Query lấy toàn bộ cây cần load quan hệ `children` đệ quy (chấp nhận được vì mỗi version tối đa vài chục node) |
| Không chuẩn hoá điểm section về thang 0–100 như `SectionedAggregation` của `Assessment` | Luật định điểm thô theo thang cố định 40/25/35 rồi cộng thẳng thành 100 — chuẩn hoá sẽ làm sai ý nghĩa "40 điểm Phần A nặng hơn 25 điểm Phần B" | Không tái dùng được `Modules\Assessment\Engine\Aggregation\*` — chấp nhận viết `ScoringCalculator` riêng, đơn giản hơn nhiều so với engine kia |
| Tách `practice` và `self_assessment` là 2 giá trị của cùng 1 cột `mode`, không phải 2 bảng | Cùng một chuỗi thao tác (chọn option → tính điểm → tra hạng), khác nhau về ý nghĩa pháp lý và quyền hạn — tách bảng sẽ nhân đôi code vô ích | Phải luôn nhớ lọc theo `mode` khi query — chấp nhận được, đã có index `(organization_id, mode, status)` |
| `OcopProduct` không lưu `certified_at`/`expires_at` | Cấp giấy chứng nhận là hành vi pháp lý của UBND tỉnh/Bộ NN&MT, ngoài phạm vi module — lưu nhầm sẽ tạo ảo giác hệ thống "tự cấp giấy OCOP" | Khi vertical chứng nhận tương lai ra đời, cần bảng `ocop_certificates` riêng, không mở rộng `ocop_products` |
| `OcopStarBand` là bảng dùng chung (theo `legal_version`), không lặp lại theo từng `rubric_version` | 5 khoảng điểm 0-30/30-50/50-70/70-90/90-100 là quy định chung cho **mọi** bộ sản phẩm (Điều 3.3), không phải đặc thù riêng bộ nào | Nếu tương lai có bộ sản phẩm với thang điểm khác biệt (hiện luật chưa cho phép), phải thêm cột liên kết band theo product_group — hiện chưa cần |
| Validate toàn vẹn cây (`ValidateRubricIntegrityHandler`) bắt buộc chạy trước `Publish`, không tin tưởng người nhập | 26 bộ sản phẩm × hàng chục tiêu chí — sai sót nhập liệu gần như chắc chắn xảy ra, phát hiện sau khi tổ chức đã tự chấm hàng trăm lần sẽ rất tốn kém sửa | Thêm bước chặn ở Action — người nhập liệu (thường là system_admin/nhân viên nghiệp vụ) phải sửa ngay khi bị chặn thay vì publish "tạm" rồi sửa sau |
| `AnswerCriterionAction` tra `points_awarded` lại từ `OcopRubricOption.points` theo `option_id` client gửi lên, **không bao giờ nhận số điểm trực tiếp từ request** | Nếu DTO nhận thẳng `points_awarded` từ client, tổ chức có thể sửa điểm bằng cách sửa payload request mà không cần đụng vào bảng tiêu chí — vẫn là một dạng "tự sửa tiêu chí" trá hình, chỉ khác chỗ đứng | Action phải query thêm 1 lần `OcopRubricOption::where('criterion_id', ...)->findOrFail(...)` mỗi lần trả lời — chấp nhận được, đảm bảo nguồn sự thật điểm số duy nhất luôn là bảng do hệ thống sở hữu |
| Session `completed` → `is_locked=true` vĩnh viễn, guard chặn ngay đầu `AnswerCriterionAction`/`SkipCriterionAction`/`FlagDisqualifierAction`/`CompleteScoringSessionAction`, không chỉ ẩn nút trên UI | Kết quả 1 lần chấm điểm phải là lịch sử đáng tin cậy — nếu cho sửa ngầm sau khi "hoàn thành", `best_score`/lịch sử luyện tập và (sau này) hồ sơ tự đánh giá Mẫu 02 đều mất giá trị làm bằng chứng | Muốn sửa/chấm lại phải tạo phiên mới (làm lại từ đầu hoặc `DuplicateScoringSessionAction`) — đúng ý muốn, không phải hạn chế kỹ thuật |
| `DuplicateScoringSessionAction` chỉ copy câu trả lời khi **cùng `product_group_id` và cùng `rubric_version_id`** đang active; khác đi thì cố tình để trống thay vì ánh xạ | Ánh xạ câu trả lời giữa 2 cây tiêu chí có `criterion_id` khác nhau hoàn toàn (khác bộ sản phẩm) không có cách nào đúng về mặt ngữ nghĩa — thà không có tính năng còn hơn tạo dữ liệu sai trông như đúng | Người dùng nhân bản sang bộ sản phẩm khác sẽ thấy phiên trống, phải tự làm lại — chấp nhận được vì minh bạch hơn "tự động điền sai" |
| Trường hợp cùng Bộ sản phẩm nhưng khác `rubric_version` (§8.4.2): map theo **`code` + so nội dung** (`max_score` + tập label/points của option), tuyệt đối **không map theo `id`** | Mỗi lần `PublishRubricVersionAction` chạy tạo ra criterion/option với `id` hoàn toàn mới (kể cả khi nội dung không đổi) — map theo `id` cũ chắc chắn sai (record không tồn tại) hoặc nguy hiểm hơn là trỏ nhầm nếu `id` trùng ngẫu nhiên với 1 tiêu chí khác ở version mới | Phải load toàn bộ cây cả 2 version để so sánh nội dung (`CrossVersionAnswerMapper`) — tốn thêm 1-2 query so với map thẳng `id`, chấp nhận được vì tần suất xảy ra rất thấp (chỉ khi có version mới) |
| Mọi câu trả lời map chéo version (Trường hợp 2) đều bắt buộc `needs_review=true`, `CompleteScoringSessionAction` chặn hoàn thành khi còn cờ này | Dù nội dung đã so khớp 100% tự động, đây vẫn là suy luận qua `code` chứ không phải tái dùng thẳng cùng 1 hàng dữ liệu (khác hẳn Trường hợp 1) — máy có thể so sai một chi tiết nhỏ ngoài dự kiến, nên bắt buộc 1 lượt xác nhận của con người trước khi tính là "đã chấm xong" | Người dùng phải duyệt lại từng tiêu chí được đánh dấu trước khi hoàn thành phiên — chấp nhận được, đổi lại vẫn tiết kiệm hầu hết công sức so với chấm lại từ đầu toàn bộ |
| Trường hợp 2 (map chéo version) tách thành Phase 4b riêng, không bắt buộc có ngay ở Phase 4 | Chỉ phát sinh khi rubric của 1 Bộ sản phẩm đã có từ 2 version publish trở lên — với 3-5 bộ ưu tiên ở Phase 0-2, tình huống này gần như không xảy ra trong giai đoạn đầu | Nếu chưa làm Phase 4b, nhân bản cùng Bộ sản phẩm nhưng khác version sẽ throw thông báo rõ ràng "chưa hỗ trợ" thay vì im lặng bỏ qua hoặc lỗi khó hiểu |
| Tách `best_practice_score/star_rank` và `latest_self_assessment_score/star_rank` thành 2 cặp cột độc lập trên `OcopProduct`, không dùng chung 1 cặp `best_score` | `practice` nên theo dõi KỶ LỤC cao nhất (mục tiêu luyện tập là cải thiện dần); `self_assessment` phải phản ánh HIỆN TRẠNG THẬT mới nhất theo đúng tinh thần Mẫu 02 (Điều 6.1.a) — nếu dùng chung 1 cặp cột, 1 lần luyện tập optimistic điểm cao sẽ che mất kết quả tự đánh giá nghiêm túc thấp hơn, hoặc ngược lại tự đánh giá cũ sẽ chặn không cho practice ghi nhận tiến bộ mới | `OcopProduct.status` phải suy ra cẩn thận từ cả 2 nguồn (không để `practice` sau khi đã `self_assessed` vô tình lùi status về `practicing`) |
| `StartScoringSessionAction` resume session `in_progress` có sẵn thay vì luôn tạo mới | Nếu tạo mới vô điều kiện, 2 tab/2 nhân viên cùng bấm "bắt đầu luyện tập" cho cùng 1 sản phẩm sẽ có 2 phiên chấm song song — không ai biết "điểm hiện tại" đang tính theo phiên nào | Cần thêm `AbandonSessionAction` làm lối thoát tường minh cho trường hợp người dùng thật sự muốn bỏ phiên cũ làm lại từ đầu, thay vì bị kẹt resume mãi 1 phiên không muốn dùng |
| `RecalculateSessionScoreAction` dùng `lockForUpdate()` trong transaction | Quiz UI phản hồi điểm ngay sau mỗi câu trả lời — nếu 2 request answer() (2 tiêu chí khác nhau, cùng session) chạy gần như đồng thời mà không khoá, request nào ghi sau sẽ đọc `answers` cũ (chưa thấy answer của request kia) rồi ghi đè, làm mất 1 phần điểm đã lưu | Thêm 1 lần `SELECT ... FOR UPDATE` mỗi lần recalculate — chi phí nhỏ, chấp nhận được vì tần suất ghi trên 1 session không cao (người dùng trả lời tuần tự trong thực tế, race chỉ xảy ra khi có bug UI double-submit) |
| `AnswerCriterionAction` validate `criterion.is_scorable=true` trước khi ghi, từ chối ghi answer vào node container | `ScoringCalculator` đã lọc `is_scorable` khi tính tổng nên 1 answer "lạc" vào node container không làm sai điểm số — nhưng vẫn là dữ liệu rác gây khó hiểu khi audit/debug sau này, chặn từ đầu rẻ hơn dọn dẹp sau | Thêm 1 query `findOrFail` mỗi lần answer — chấp nhận được |
| Migration `ocop_products` và `ocop_scoring_sessions` phụ thuộc vòng (`products.latest_self_assessment_session_id` → `sessions.id`, `sessions.ocop_product_id` → `products.id`) | Laravel/MySQL không cho phép tạo 2 FK constraint tham chiếu vòng ngay trong 2 migration tạo bảng theo tuần tự thông thường | Tách FK của `latest_self_assessment_session_id` ra 1 migration riêng (`000012`) chạy SAU khi cả 2 bảng đã tồn tại — pattern chuẩn, không phải workaround tạm |

---

## 19. Open Questions — cần xác nhận với KienDH trước khi code Phase 0

1. **Ưu tiên 3–5 bộ sản phẩm nào trước?** Đề xuất mặc định (rau củ quả tươi, gạo ngũ cốc, chế biến rau củ quả, mật ong, chè) dựa trên phổ biến với HTX nông nghiệp Việt Nam nói chung — cần xác nhận có khớp với khách hàng THUCHOCVN đang nhắm tới không (vd. nếu khách hàng đầu tiên là vùng cà phê Tây Nguyên thì nên ưu tiên "Cà phê, ca cao" thay vì "Chè").
2. **Ai được phép sửa cây tiêu chí?** Spec giả định chỉ `system_admin` (đúng vì đây là quy định pháp luật cố định) — xác nhận không có nhu cầu cho phép "chuyên viên nghiệp vụ OCOP" (một vai trò mới, không phải system_admin) tự nhập liệu Phase 6.
3. **`self_assessment` có cần xuất PDF theo đúng mẫu Mẫu số 02 Phụ lục III không ở Phase 4, hay để dành Phase 7 (khi có vertical chứng nhận)?** Spec hiện để dành Phase 7 vì xuất PDF đúng form Nhà nước là công việc riêng biệt (không phải scoring).
4. **Leaderboard (Phase 5) có rủi ro tạo áp lực không lành mạnh giữa nhân viên không?** Đề xuất mặc định: opt-in per tổ chức (bật/tắt qua cấu hình), không bật mặc định.
5. **`evidence_note` hiện chỉ là text tự do — có cần đính kèm ảnh/file minh chứng (vd. ảnh chứng nhận VietGAP) ngay ở bước tự chấm không, hay để dành khi tích hợp `KcItem` ở Phase 7?** Đề xuất mặc định: để dành Phase 7, vì lưu trữ tài liệu có versioning/duyệt là đúng việc của `KcItem`, không nên xây storage riêng trong `OcopRubric`.
6. **Ai được phép `AbandonSessionAction` một phiên đang treo?** Spec hiện chưa giới hạn thêm ngoài `OcopScoringSessionPolicy::answer()` (chính người tạo hoặc đồng nghiệp cùng tổ chức có quyền `OCOP_PRACTICE_USE`) — cần xác nhận có nên giới hạn chỉ người tạo session mới được abandon phiên của chính họ hay không, tránh 1 nhân viên xoá dở phiên luyện tập của người khác.
