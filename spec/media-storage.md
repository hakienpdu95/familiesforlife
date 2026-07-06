# Đặc Tả: Media Storage System — Multi-Tenant ADR + ERD

> **Phiên bản:** 1.2.0  
> **Ngày:** 2026-06-09  
> **Phạm vi:** Hệ thống lưu trữ file/ảnh tập trung cho toàn bộ module  
> **Stack:** Laravel 13 + MySQL 8 + Spatie Media Library v11 + Intervention Image v3 + FilePond v4  
> **Nguyên tắc:** Multi-tenant · Không hardcode URL · Disk-agnostic · Xử lý đồng bộ (no queue)

### Thay đổi v1.1.0 → v1.2.0

| # | Mục | Thay đổi | Lý do |
|---|-----|---------|-------|
| C1 | ADR-08 | **Thêm mới**: FilePond Upload Pattern — tách rõ 2 đường upload (FilePond vs Jodit) | FilePond dùng cho form field (avatar, logo, attachment); Jodit dùng cho inline rich-text |
| C2 | §10B | **Thêm mới**: FilePond endpoint `POST /api/v1/media/upload` + revert + controller | FilePond cần server riêng với response format khác Jodit |
| C3 | §10B | **FilePond JS wrapper** chuẩn hóa: `initFilePondUpload()` tích hợp MediaUploadService | Hiện tại filepond.js chưa kết nối backend media system |
| C4 | §15 | **Thêm mới**: Frontend Display Patterns — cách gọi ảnh trong Blade và JS | Cần chuẩn hóa để tránh dùng lẫn `avatar_url` vs `getMediaUrl()` |
| C5 | §16 | **Thêm mới**: Module-by-Module Guide — trạng thái từng model và roadmap | Employee/Organization đã có HasTenantMedia nhưng vẫn fallback về cột cũ |
| H1 | config | Sửa `jodit_orphan_ttl_hours` 24 → **72** | Bug: config sai so với spec ADR-05 |
| H2 | MediaUploadService | Thêm crop upscaling guard — skip khi ảnh < crop target size | Tránh upscale ảnh nhỏ hơn 150×150 |
| H3 | MediaUploadService | Extract `pruneEmptyAncestors()` public — prune 4 cấp thư mục sau xóa | Dọn sạch entity_type và module dirs khi empty |
| H4 | MediaCleanupOrphansCommand | Dùng `MediaUploadService::delete()` thay manual disk ops | Thống nhất logic xóa file + prune dirs |
| H5 | jodit.js | CSRF token đọc lazy tại editor-init (không tại module load) | Tránh stale token trên long-lived page |

### Thay đổi v1.0.0 → v1.1.0

| # | Mục | Thay đổi | Lý do |
|---|-----|---------|-------|
| C1 | ADR-01 | Custom `Media` phải extend **Spatie trực tiếp**, KHÔNG qua `TenantAwareModel` | Tránh kế thừa `SoftDeletes` làm vỡ Spatie schema |
| C1 | ADR-01 | Thêm bắt buộc override `newQuery()` xử lý scope khi context null | `OrganizationScope` WHERE 0=1 chặn Spatie internal queries |
| C2 | ADR-03 | **Bỏ queue** — conversion chạy **đồng bộ** inline trong upload service | Chưa dùng queue ở hiện tại; tránh phức tạp TenantAwareJob |
| C3 | §12.1 | Sửa index: thêm `collection_name` vào compound, bỏ 2 index premature | Index thiếu `collection_name` → Spatie core query không optimal |
| H1 | §10.5 | Orphan TTL tăng 24h → **72h**, thêm `last_touched_at` column | 24h không đủ cho session edit dài |
| H1 | §10.5 | Thêm **lazy cleanup** khi save content | Giảm stale orphan thay vì chờ cron |
| M1 | §5.1 | Thêm cột `uploaded_at` vào schema `media` | Không để mất audit timestamp từ 3 bảng cũ |
| M1 | §13.1 | Cập nhật mapping `uploaded_at` → `media.uploaded_at` | Trước thiếu mapping, dữ liệu audit bị mất |
| M2 | §5.3, §9.4 | Tách `attachments` thành `attachments` (public) + `attachments_private` | SOP và Recruitment cùng tên collection nhưng khác access policy |
| M3 | §14 | Thêm `intervention/image-laravel ^3.0` vào task Phase 1 bắt buộc | Package chưa có trong `composer.json` |

---

## Mục lục

1. [Phân tích hiện trạng](#1-phân-tích-hiện-trạng)
2. [Quyết định kiến trúc (ADR)](#2-quyết-định-kiến-trúc-adr)
3. [Kiến trúc tổng thể](#3-kiến-trúc-tổng-thể)
4. [ERD — Sơ đồ quan hệ](#4-erd--sơ-đồ-quan-hệ)
5. [Đặc tả bảng `media`](#5-đặc-tả-bảng-media)
6. [Folder Structure Convention](#6-folder-structure-convention)
7. [Image Processing Pipeline](#7-image-processing-pipeline)
8. [Upload Service Architecture](#8-upload-service-architecture)
9. [URL Resolution Strategy](#9-url-resolution-strategy)
10. [Upload Endpoints — Jodit & FilePond](#10-upload-endpoints--jodit--filepond)
11. [Migration Path: Local → S3 → CDN](#11-migration-path-local--s3--cdn)
12. [Indexes & Performance](#12-indexes--performance)
13. [Xung đột & Lộ trình hợp nhất](#13-xung-đột--lộ-trình-hợp-nhất)
14. [Lộ trình triển khai](#14-lộ-trình-triển-khai)
15. [Frontend Patterns — Display & Upload](#15-frontend-patterns--display--upload)
16. [Module Guide — Trạng thái & Cách dùng](#16-module-guide--trạng-thái--cách-dùng)

---

## 1. Phân tích hiện trạng

### 1.1 Các hệ thống upload hiện có (3 hệ thống độc lập)

| Module | Bảng | Controller | Action | Disk | Giới hạn |
|--------|------|-----------|--------|------|-----------|
| Recruitment | `rc_candidate_attachments` | `CandidateAttachmentController` | `StoreCandidateAttachmentAction` | `public` | 10 MB |
| SOP | `sop_step_attachments` | `SopStepAttachmentController` | `StoreSopStepAttachmentAction` | `local` / S3 | 20 MB |
| KcItem | `kc_item_attachments` | `KcAttachmentApiController` | `StoreKcAttachmentAction` | `local` | 50 MB/file, 200 MB/item |

**Schema chung của cả 3 bảng:**
```
id, uuid, {parent_fk}, file_name, file_url, file_type, file_size_kb,
storage_provider, storage_key, [alt_text], sort_order, uploaded_by, uploaded_at
```

### 1.2 Model dùng URL trực tiếp (chưa có upload endpoint)

| Model | Cột | Kiểu | Trạng thái |
|-------|-----|------|-----------|
| `employees` | `avatar_url` | varchar(500) | Chuỗi URL lưu thẳng |
| `organizations` | `logo_path` | varchar(500) | Chuỗi URL lưu thẳng |
| `mkt_applicants` | `avatar_url` | text | Chuỗi URL lưu thẳng |
| `mkt_applicant_portfolios` | `thumbnail_url` | text | Chuỗi URL lưu thẳng |

### 1.3 Jodit Editor — hiện trạng nguy hiểm

```
insertImageAsBase64URI: true   ← ảnh nhúng thẳng vào HTML content dưới dạng base64
```

**Hệ quả:** Mỗi ảnh ~100–500 KB base64 nhúng vào cột `content` TEXT → cột phình to, không cache được, không resize được, không xóa được khi ảnh không dùng nữa.

### 1.4 Những gì đã có — tận dụng được

| Asset | Trạng thái | Kế hoạch |
|-------|-----------|---------|
| Spatie Media Library v11.22 | Installed, migration tồn tại, **chưa dùng** | Sử dụng làm engine |
| `media` table migration | Tồn tại tại `vendor/...` | Extend thêm `organization_id` + `uploaded_at` |
| S3 config | Cấu hình sẵn trong `filesystems.php` | Bật khi cần |
| Intervention Image | **Chưa cài** | Cần thêm `intervention/image-laravel ^3.0` |
| `TenantAwareJob` | Tồn tại, đúng pattern | **Không dùng** cho Media — bỏ queue |

### 1.5 Xung đột & trùng lặp

| # | Vấn đề | Mức độ |
|---|--------|--------|
| 1 | 3 bảng attachment riêng, cùng schema nhưng không thể cross-query | Trung bình |
| 2 | `storage_provider` trong DB nhưng URL lại hardcode domain → đổi CDN phải UPDATE hàng triệu row | **Cao** |
| 3 | Jodit base64 nhúng vào content TEXT → không kiểm soát dung lượng | **Cao** |
| 4 | `media` table của Spatie thiếu `organization_id` → không tenant-safe | Cao |
| 5 | Không có image resize → ảnh gốc 5MB serve thẳng ra browser | Trung bình |
| 6 | Giới hạn file size mỗi module khác nhau (10/20/50 MB) → không nhất quán | Thấp |

---

## 2. Quyết định kiến trúc (ADR)

### ADR-01: Engine — Spatie Media Library + Tenant Extension

**Quyết định:** Dùng Spatie Media Library v11 làm engine, extend với custom `Media` model tenant-aware.

**Lý do chọn Spatie (không tự build):**
- Đã cài sẵn, migration tồn tại → không cần viết conversion pipeline từ đầu
- Hỗ trợ polymorphic (`model_type` / `model_id`), collections, disk-agnostic URL
- Có cột `generated_conversions` (JSON) track trạng thái WebP variants
- Có cột `conversions_disk` → có thể serve variants từ CDN, original từ S3

**Extension cần thiết:**

```
App\Models\Media extends Spatie\MediaLibrary\MediaCollections\Models\Media
    + use BelongsToOrganization   ← CHỈ trait này, không có gì khác
```

> ⚠️ **CRITICAL — KHÔNG extend `TenantAwareModel`:**  
> `TenantAwareModel` kế thừa `SoftDeletes`. Spatie's `media` table **không có** `deleted_at` và không kỳ vọng soft-delete behavior. Nếu extend `TenantAwareModel` → schema conflict, query behavior vỡ, Spatie's cleanup logic fail.  
> **Chỉ dùng trait `BelongsToOrganization` độc lập.**

**Xử lý `OrganizationScope` khi context null:**  
`OrganizationScope` trả `WHERE 0=1` khi `TenantContext` không có (cron, artisan command). Spatie's `MediaRepository` gọi `Media::query()` nội bộ — nếu scope active và context null → mọi conversion query trả empty, conversion silently fail.

Custom `App\Models\Media` phải override `newQuery()`:
- Nếu `TenantContext::getOrganizationId()` !== null → apply scope bình thường
- Nếu null → `withoutGlobalScope(OrganizationScope::class)` — cho phép Spatie internal operations chạy, không leak data vì Spatie chỉ query by `model_type + model_id` (đã biết exact record)

**Rejected alternatives:**
- Tự build `media_files` table: reinventing the wheel, mất conversion pipeline
- Spatie không extend: `media` table không có `organization_id` → data leak giữa các org
- Extend `TenantAwareModel`: kế thừa `SoftDeletes` → vỡ Spatie schema

---

### ADR-02: Folder Structure — Org-scoped + Module-scoped + UUID leaf

**Quyết định:**
```
{disk}:media/{org_id}/{module}/{entity_type}/{entity_id}/{uuid}/
```

**Lý do:**
- `org_id` ở level 2 → IAM bucket policy đơn giản: org chỉ được đọc prefix của mình
- `module` ở level 3 → dễ clean khi xóa toàn bộ một module
- `entity_id` ở level 4 → batch delete khi xóa entity
- `uuid` ở level 5 → chặn enumeration tấn công (không thể đoán đường dẫn)

**Rejected:** Flat structure `org_id/uuid/` — không xóa batch, không audit được

---

### ADR-03: Image Processing — Đồng bộ, Không Queue

**Quyết định:** Intervention Image v3 chạy **đồng bộ inline** trong `MediaUploadService::upload()`. **Không dùng queue** ở giai đoạn hiện tại.

**Lý do bỏ queue:**
- Hệ thống chưa có Redis/queue worker ổn định
- `TenantAwareJob` phức tạp không cần thiết ở Phase 1
- Conversion đồng bộ đơn giản hơn, dễ debug, dễ rollback
- File ảnh thường < 5MB → thời gian resize < 500ms, chấp nhận được trong HTTP request
- Khi scale cần thiết (Phase 3) → migrate sang queue dễ dàng (chỉ wrap conversion block vào job)

**Variants chuẩn cho ảnh:**

| Variant | Kích thước | Format | Method | Dùng cho |
|---------|----------|--------|--------|---------|
| `thumb` | 150 × 150 px | WebP | crop center | Avatar, grid thumbnail |
| `medium` | 800 px wide (ratio) | WebP | resize | Card image, preview |
| `preview` | 1200 px wide (ratio) | WebP | resize | Lightbox, full view |
| `original` | Giữ nguyên | Giữ nguyên | — | Download, backup |

**Quy tắc:**
- Chỉ tạo variant cho `image/jpeg`, `image/png`, `image/gif`, `image/webp`
- Các file khác (PDF, DOCX, video): chỉ lưu original
- Nếu ảnh gốc < 800px wide → không tạo `medium` (tránh upscale)
- Conversion fail → log error, giữ nguyên original, `generated_conversions` = `{}`; frontend fallback về original

---

### ADR-04: URL Resolution — Disk-agnostic Runtime URL

**Quyết định:** Không lưu URL vào DB. Chỉ lưu `disk` + `storage_key` (relative path). URL tạo tại runtime qua `MediaUrlService`.

```
DB: disk = "s3", storage_key = "media/5/sop/sop_step/42/a1b2/thumb.webp"
Runtime: Storage::disk("s3")->url("media/5/sop/sop_step/42/a1b2/thumb.webp")
CDN overlay: config("media.cdn_url") . "/" . storage_key   ← nếu có CDN
```

**Lợi ích:** Đổi CDN domain → chỉ sửa 1 dòng config, 0 DB UPDATE.

---

### ADR-05: Jodit Endpoint — Real File Upload Thay Base64

**Quyết định:** Tắt `insertImageAsBase64URI`, tạo endpoint `POST /api/media/jodit-upload` trả response format Jodit v4.

**Orphan strategy:**
- Ảnh upload từ Jodit gắn tạm vào `JoditDraft` model với `last_touched_at` = now()
- `last_touched_at` được update mỗi khi frontend gọi bất kỳ API nào của cùng content session
- Khi content được save → re-associate media sang entity thật (update `model_type`, `model_id`)
- Artisan command `media:cleanup-orphans` cleanup orphan TTL > **72h** kể từ `last_touched_at`
- Lazy cleanup: khi save content, xóa ngay orphan của session đó chưa được associate

---

### ADR-06: Access Policy — Per Collection, Không Dùng Chung Collection Name

**Quyết định:** Mỗi collection có access policy riêng (public/private) được khai báo trong `config/media.php`. Không để 2 module khác nhau về privacy dùng chung tên collection.

**Lý do:** `attachments` collection của SOP là public (staff xem được), của Recruitment là private (chỉ HR + candidate). Cùng tên → không phân biệt được access policy, `MediaUrlService` không biết khi nào dùng presigned URL.

| Module | Collection | Access |
|--------|-----------|--------|
| SOP | `attachments` | public |
| KcItem | `attachments` | public |
| Recruitment | `attachments_private` | private (presigned URL) |

---

### ADR-07: Migration Path — Không Big Bang

**Quyết định:** Migration 3 phase, backward-compatible, không downtime.

---

### ADR-08: FilePond Upload Pattern — Form Field Upload

**Quyết định:** FilePond là công cụ upload cho mọi form field có ảnh/file rời (avatar, logo, thumbnail, cover, attachment). Jodit chỉ dùng cho inline images trong rich-text content.

**Phân biệt rõ 2 đường upload:**

| Tiêu chí | FilePond | Jodit |
|---------|---------|-------|
| Mục đích | Form field: avatar, logo, file đính kèm | Inline image nhúng vào rich-text HTML |
| Upload timing | Ngay khi user chọn file (immediate) | Ngay khi user paste/kéo ảnh vào editor |
| Association | Direct (context đã biết) hoặc UUID trả về form | Orphan → reassociate khi save content |
| Revert | Có — DELETE trước khi form submit | Có — DELETE khi xóa ảnh khỏi editor |
| Response | `{ uuid, url, thumb_url }` JSON | `{ error, data: { files: [{url, uuid}] } }` JSON |
| Endpoint | `POST /api/v1/media/upload` | `POST /api/v1/media/jodit-upload` |
| JS wrapper | `resources/js/modules/filepond.js` | `resources/js/modules/jodit.js` |

**Orphan strategy cho FilePond:**
- Nếu `X-Context-Id` được truyền (edit form) → gắn Media trực tiếp vào entity ngay khi upload
- Nếu không có `X-Context-Id` (create form) → gắn vào `JoditDraft` tạm, TTL 72h, reassociate khi form save
- Frontend truyền UUID về form qua hidden input `<input name="media_uuid[collection]" value="uuid">`

**FilePond server protocol:**
- `process` URL: `POST /api/v1/media/upload` → trả UUID plain text (FilePond native) + JSON qua header/body
- `revert` URL: `DELETE /api/v1/media/upload/{uuid}` → xóa nếu chưa associate với entity thật
- Không cần `load` hay `restore` endpoint ở Phase 1

**Rejected alternatives:**
- Upload trong form submit: UX kém, không có preview, không validate realtime
- Dùng Jodit endpoint cho FilePond: response format khác, không có revert, orphan logic khác
- Base64 trong hidden input: quá nặng, không resize

---

## 3. Kiến trúc tổng thể

```
                        ┌─────────────────────────────────────────┐
                        │            organizations                 │
                        │         (tenant root)                    │
                        └──────────────────┬──────────────────────┘
                                           │ organization_id
                                           ▼
┌──────────────────────────────────────────────────────────────────────┐
│                        media  (Spatie + extended)                    │
│                                                                      │
│  organization_id  ←── tenant scope (global, null-safe)               │
│  model_type       ──► "Modules\Sop\Models\SopStep"                   │
│  model_id         ──► 42                                             │
│  collection_name  ──► "attachments" | "avatar" | "jodit_content"     │
│  disk             ──► "public" | "s3" | "r2"                         │
│  storage_key      ──► "media/5/sop/sop_step/42/uuid/original.jpg"    │
│  conversions_disk ──► "public" | "s3" | "cdn"                        │
│  generated_conversions ──► {"thumb": true, "medium": true}           │
│  uploaded_at      ──► timestamp gốc từ user action                   │
│  last_touched_at  ──► dùng cho jodit orphan TTL                      │
└────────────────────────┬─────────────────────────────────────────────┘
                         │ morphTo (model_type / model_id)
          ┌──────────────┼─────────────────────────────────┐
          ▼              ▼                         ▼         ▼
   ┌────────────┐  ┌──────────┐            ┌──────────┐  ┌───────────┐
   │  Employee  │  │ SopStep  │    ...      │ KcItem   │  │JoditDraft │
   │ (avatar)   │  │(attach.) │            │(attach.) │  │(orphan)   │
   └────────────┘  └──────────┘            └──────────┘  └───────────┘

                         Upload Flow (đồng bộ, no queue)
                         ─────────────────────────────────
  HTTP Request ──► MediaUploadService::upload()
                      │
                      ├─► Validate (MIME, size, org quota)
                      ├─► Store original → Storage::disk($disk)->put($key, $file)
                      ├─► Create Media record (Spatie)
                      └─► [nếu MIME là image/*] Chạy conversion ĐỒNG BỘ
                              ├─► thumb.webp  (150×150, crop)
                              ├─► medium.webp (800w, resize)
                              └─► [nếu collection = cover] preview.webp (1200w)
                              → Update generated_conversions → return Media

                         URL Resolution
                         ─────────────
  $media->getUrl('thumb')
      └─► MediaUrlService::url($media, 'thumb')
              ├─► generated_conversions['thumb'] = false? → fallback original
              ├─► custom_properties.is_public = false? → temporaryUrl (presigned)
              ├─► CDN configured? → cdn_url / storage_key
              └─► No CDN → Storage::disk($disk)->url($key)
```

---

## 4. ERD — Sơ đồ quan hệ

```
organizations
    │
    │ organization_id
    ▼
  media
    │  (polymorphic: model_type + model_id)
    │
    ├──── Employee.avatar               (collection: avatar,              public)
    ├──── Organization.logo             (collection: logo,                public)
    ├──── SopStep.attachments           (collection: attachments,         public)
    ├──── KcItem.attachments            (collection: attachments,         public)
    ├──── RcCandidate.attachments       (collection: attachments_private, private ← presigned)
    ├──── MktApplicant.avatar           (collection: avatar,              public)
    ├──── MktApplicantPortfolio.thumb   (collection: thumbnail,           public)
    ├──── JpJobPost.cover               (collection: cover,               public)
    └──── JoditDraft.content_images     (collection: jodit_content,       public, orphan tạm)


BẢNG BỊ XÓA SAU MIGRATION (Phase 2):
    rc_candidate_attachments  ──► hợp nhất vào media (collection: attachments_private)
    sop_step_attachments      ──► hợp nhất vào media (collection: attachments)
    kc_item_attachments       ──► hợp nhất vào media (collection: attachments)

CỘT BỊ XÓA SAU MIGRATION (Phase 2):
    employees.avatar_url                  ──► media collection "avatar"
    organizations.logo_path               ──► media collection "logo"
    mkt_applicants.avatar_url             ──► media collection "avatar"
    mkt_applicant_portfolios.thumbnail_url ──► media collection "thumbnail"
```

---

## 5. Đặc tả bảng `media`

> Bảng gốc của Spatie + thêm cột via ALTER migration.  
> File migration gốc: `database/migrations/vendor/2026_05_13_020107_create_media_table.php`  
> File migration extend: `database/migrations/generated/XXXX_extend_media_table_for_tenant.php`

### 5.1 Schema đầy đủ sau extend

| Cột | Kiểu | Nullable | Default | Ghi chú |
|-----|------|----------|---------|---------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INC | PK |
| `uuid` | CHAR(36) | NO | — | Public identifier, unique |
| `organization_id` | BIGINT UNSIGNED | YES | NULL | **[Thêm]** FK → organizations; NULL = media hệ thống |
| `model_type` | VARCHAR(255) | NO | — | e.g. `Modules\Sop\Models\SopStep` |
| `model_id` | BIGINT UNSIGNED | NO | — | FK polymorphic |
| `collection_name` | VARCHAR(255) | NO | `default` | avatar \| attachments \| attachments_private \| thumbnail \| logo \| cover \| jodit_content |
| `name` | VARCHAR(255) | NO | — | Tên file gốc (không có extension) |
| `file_name` | VARCHAR(255) | NO | — | Tên file lưu trên disk |
| `mime_type` | VARCHAR(255) | YES | NULL | image/webp, application/pdf... |
| `disk` | VARCHAR(255) | NO | — | public \| s3 \| r2 |
| `conversions_disk` | VARCHAR(255) | YES | NULL | Disk lưu variants — NULL = same as disk |
| `size` | BIGINT UNSIGNED | NO | — | Bytes — file gốc |
| `manipulations` | JSON | NO | `[]` | Spatie internal |
| `custom_properties` | JSON | NO | `{}` | alt_text, caption, uploaded_by, module, is_public |
| `generated_conversions` | JSON | NO | `{}` | `{"thumb": true, "medium": false}` |
| `responsive_images` | JSON | NO | `{}` | Spatie responsive srcset |
| `order_column` | INT UNSIGNED | YES | NULL | Sort order trong collection |
| `uploaded_at` | TIMESTAMP | YES | NULL | **[Thêm]** Timestamp user thực sự upload — bảo toàn từ bảng cũ |
| `last_touched_at` | TIMESTAMP | YES | NULL | **[Thêm]** Dùng cho Jodit orphan TTL; NULL = không phải jodit orphan |
| `created_at` | TIMESTAMP | YES | NULL | Record creation time |
| `updated_at` | TIMESTAMP | YES | NULL | — |

> **Không có `deleted_at`:** Media không soft-delete. Khi xóa → xóa vật lý file trên disk và xóa record. Lý do: Spatie không hỗ trợ soft-delete trên `media` table; tránh orphaned files tồn tại vô thời hạn.

> **`uploaded_at` vs `created_at`:**  
> `uploaded_at` = thời điểm user thực sự thực hiện hành động upload (lấy từ request hoặc migrate từ bảng cũ).  
> `created_at` = thời điểm record DB được tạo (có thể là lúc chạy data migration, khác `uploaded_at`).  
> Dùng `uploaded_at` cho mọi hiển thị audit; dùng `created_at` cho internal ordering.

### 5.2 `custom_properties` — cấu trúc chuẩn

```json
{
  "alt_text": "Ảnh bìa Job Post Senior Developer",
  "caption": "",
  "uploaded_by": 12,
  "module": "sop",
  "is_public": true
}
```

> `is_public` được đặt tự động bởi `MediaUploadService` dựa theo config collection, không do caller truyền vào.

### 5.3 Collection names chuẩn hóa toàn hệ thống

| Collection | Model áp dụng | Access | Variants | Giới hạn |
|-----------|--------------|--------|---------|---------|
| `avatar` | Employee, MktApplicant, User | **public** | thumb, medium | 5 MB, image only |
| `logo` | Organization | **public** | thumb, medium | 5 MB, image only |
| `thumbnail` | MktApplicantPortfolio, JpJobPost | **public** | thumb, medium | 5 MB, image only |
| `cover` | JpJobPost, KcCategory | **public** | medium, preview | 10 MB, image only |
| `attachments` | SopStep, KcItem | **public** | — (no resize) | 50 MB, any MIME |
| `attachments_private` | RcCandidate | **private** (presigned) | — (no resize) | 50 MB, any MIME |
| `jodit_content` | JoditDraft | **public** | medium | 10 MB, image only |
| `documents` | Tương lai | TBD | — | 50 MB, PDF/DOC |

---

## 6. Folder Structure Convention

### 6.1 Pattern

```
{disk}:
  media/
    {organization_id}/          ← tenant isolation (IAM policy boundary)
      {module}/                 ← e.g. sop, recruitment, kc, employee, jp
        {entity_type}/          ← snake_case class basename: sop_step, rc_candidate
          {entity_id}/          ← numeric ID của entity
            {uuid}/             ← UUID của media record (chặn enumeration)
              original.{ext}    ← file gốc, giữ extension
              thumb.webp        ← 150×150
              medium.webp       ← 800px wide
              preview.webp      ← 1200px wide (nếu có)
```

### 6.2 Ví dụ thực tế

```
# Avatar nhân viên (org 5, employee 12)
public:media/5/employee/employee/12/e5f6a7b8-1234/original.jpg
public:media/5/employee/employee/12/e5f6a7b8-1234/thumb.webp
public:media/5/employee/employee/12/e5f6a7b8-1234/medium.webp

# File đính kèm bước SOP (org 5, step 42) — public
public:media/5/sop/sop_step/42/a1b2c3d4-5678/original.pdf

# File đính kèm CV ứng viên (org 5, candidate 7) — PRIVATE, không serve trực tiếp
local:media/5/recruitment/rc_candidate/7/c9d8e7f6-3210/original.pdf

# Ảnh trong Jodit content (orphan, gắn draft ID 99)
public:media/5/jodit/jodit_draft/99/f9e8d7c6-abcd/original.png
public:media/5/jodit/jodit_draft/99/f9e8d7c6-abcd/medium.webp

# Logo org (org 5)
public:media/5/organization/organization/5/b3c4d5e6-7890/original.png
public:media/5/organization/organization/5/b3c4d5e6-7890/thumb.webp
```

### 6.3 Quy tắc `entity_type`

`entity_type` = `Str::snake(class_basename($model))` của model được attach vào.

| Model class | entity_type |
|-------------|------------|
| `Employee` | `employee` |
| `SopStep` | `sop_step` |
| `RcCandidate` | `rc_candidate` |
| `JpJobPost` | `jp_job_post` |
| `Organization` | `organization` |
| `KcItem` | `kc_item` |
| `JoditDraft` | `jodit_draft` |

---

## 7. Image Processing Pipeline

### 7.1 Luồng xử lý (đồng bộ, no queue)

```
[1] HTTP Upload Request
    │
    ▼
[2] MediaUploadService::upload()
    ├─ Validate MIME (allowlist per collection)
    ├─ Validate size (per collection limit)
    │
    ▼
[3] Storage::disk($disk)->put($key, $fileContents)
    → Lưu file gốc vào disk
    │
    ▼
[4] Tạo record Media (Spatie)
    → organization_id, model_type, model_id, collection_name
    → storage_key (path), disk, size, mime_type
    → custom_properties (is_public, uploaded_by, module)
    → uploaded_at = now()
    │
    ▼
[5] MIME là image/* ?
    ├─ KHÔNG → generated_conversions = {}, return Media (done)
    │
    └─ CÓ → Chạy conversion ĐỒNG BỘ (Intervention Image v3):
                │
                ├─── Thumb (nếu collection có thumb)
                │       Image::read($originalStream)
                │       ->cover(150, 150) → encode('webp', quality: 85)
                │       Storage::put("{path}/thumb.webp")
                │
                ├─── Medium (nếu collection có medium VÀ ảnh gốc > 800px)
                │       ->scale(width: 800) → encode('webp', quality: 82)
                │       Storage::put("{path}/medium.webp")
                │
                └─── Preview (nếu collection có preview VÀ ảnh gốc > 1200px)
                        ->scale(width: 1200) → encode('webp', quality: 80)
                        Storage::put("{path}/preview.webp")

                → Update media.generated_conversions = {"thumb": true, "medium": true, ...}
                → return Media
```

> **Exception handling:** Nếu bất kỳ conversion nào fail → catch, log error, tiếp tục với những conversion còn lại. Original luôn accessible. Frontend fallback về original khi `generated_conversions[variant] = false`.

### 7.2 Cấu hình conversion theo collection

| Collection | thumb | medium | preview | Ghi chú |
|-----------|-------|--------|---------|---------|
| `avatar` | ✓ (crop) | ✓ | — | Crop center cho thumb, contain cho medium |
| `logo` | ✓ (contain) | ✓ | — | Không crop, padding transparent |
| `thumbnail` | ✓ (crop) | ✓ | — | — |
| `cover` | ✓ | ✓ | ✓ | Preview cho lightbox |
| `attachments` | — | — | — | Không xử lý ảnh — trả original |
| `attachments_private` | — | — | — | Không resize; private — presigned URL |
| `jodit_content` | — | ✓ | — | Không thumb; medium phục vụ trực tiếp trong editor |
| `documents` | — | — | — | PDF/DOC không xử lý |

### 7.3 Lộ trình bổ sung Queue (Phase 3 khi cần)

Khi tải upload tăng cao và cần async processing:
1. Wrap conversion block (bước [5]) vào `ProcessMediaConversions extends TenantAwareJob`
2. Job gọi `$this->withTenant(fn() => /* conversion logic */)` để restore `OrganizationScope`
3. `MediaUploadService::upload()` trả ngay Media record sau bước [4], conversion chạy background
4. Frontend nhận URL original ngay lập tức; thumb/medium xuất hiện sau vài giây
5. `MediaUrlService` fallback tự động về original khi conversion chưa xong

> Bổ sung queue **không yêu cầu thay đổi schema hay API** — chỉ thay đổi nội bộ `MediaUploadService`.

---

## 8. Upload Service Architecture

### 8.1 Class hierarchy

```
App\Services\Media\
    ├── MediaUploadService          ← Entry point cho mọi module
    │       upload(file, model, collection, options[])  → Media
    │       uploadFromUrl(url, model, collection)       → Media
    │       delete(Media)                               → void
    │       bulkDelete(model, collection)               → void
    │       reassociateOrphans(model, uuids[])          → void  ← Jodit + FilePond re-association
    │       pruneEmptyAncestors(disk, leafDir)          → void  ← public, tái dùng trong command
    │
    ├── MediaUrlService             ← URL resolution tập trung
    │       url(media, conversion='')                   → string
    │       temporaryUrl(media, conversion='', ttl=30)  → string  ← private files
    │       cdnUrl(media, conversion='')                → string
    │
    └── MediaMigrateService         ← Di chuyển file giữa các disk (artisan)
            migrateToDisk(Media, targetDisk)
            migrateModelToDisk(model, collection, targetDisk)

App\Models\
    └── Media                       ← extends Spatie\MediaLibrary\...\Media
            + use BelongsToOrganization
            + override newQuery(): withoutGlobalScope khi TenantContext null

App\Traits\
    └── HasTenantMedia              ← Gắn vào domain model
            Wraps HasMedia + auto-inject organization_id
            getMediaUrl(collection, conversion)       → string ('' nếu không có)
            getFirstMediaUrl(collection, conversion)  → string (alias)

App\Http\Controllers\Api\
    ├── MediaJoditUploadController  ← Jodit inline images (orphan + uuid tracking)
    │       store()    POST   /api/v1/media/jodit-upload
    │       destroy()  DELETE /api/v1/media/jodit-upload/{uuid}
    │       discard()  POST   /api/v1/media/jodit-discard
    │       touch()    PATCH  /api/v1/media/jodit-touch
    │
    └── MediaUploadController       ← FilePond form field uploads
            store()    POST   /api/v1/media/upload
            destroy()  DELETE /api/v1/media/upload/{uuid}

App\Console\Commands\
    └── MediaCleanupOrphansCommand  ← php artisan media:cleanup-orphans
            Tìm jodit_content orphan last_touched_at > 72h → xóa file + record
            Dùng MediaUploadService::delete() (không manual disk ops)
```

### 8.2 Cách module dùng service

```php
// Controller của SOP — gắn attachment vào SopStep
$media = MediaUploadService::upload(
    file:       $request->file('attachment'),
    model:      $step,
    collection: 'attachments',
    options:    ['alt_text' => $request->alt_text]
);

// Controller của Recruitment — attachment private
$media = MediaUploadService::upload(
    file:       $request->file('cv'),
    model:      $candidate,
    collection: 'attachments_private',   // ← private → presigned URL tự động
);

// Lấy URL trong Blade (public)
$step->getFirstMediaUrl('attachments')
$employee->getMediaUrl('avatar', 'thumb')

// Lấy URL private (tự động presigned)
$candidate->getFirstMediaUrl('attachments_private')   // → temporaryUrl 30min

// Xóa
MediaUploadService::delete($media)

// Re-associate orphan Jodit khi save content
MediaUploadService::reassociateOrphans($sopProcess, $request->jodit_media_uuids)
```

### 8.3 Config tập trung

```php
// config/media.php
return [
    'disk'    => env('MEDIA_DISK', 'public'),
    'cdn_url' => env('MEDIA_CDN_URL'),      // null = dùng disk URL

    'collections' => [
        'avatar' => [
            'max_size_kb'  => 5120,
            'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            'is_public'    => true,
            'conversions'  => ['thumb', 'medium'],
        ],
        'logo' => [
            'max_size_kb'  => 5120,
            'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp'],
            'is_public'    => true,
            'conversions'  => ['thumb', 'medium'],
        ],
        'thumbnail' => [
            'max_size_kb'  => 5120,
            'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp'],
            'is_public'    => true,
            'conversions'  => ['thumb', 'medium'],
        ],
        'cover' => [
            'max_size_kb'  => 10240,
            'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp'],
            'is_public'    => true,
            'conversions'  => ['thumb', 'medium', 'preview'],
        ],
        'attachments' => [
            'max_size_kb'  => 51200,
            'allowed_mime' => ['*'],
            'is_public'    => true,
            'conversions'  => [],
        ],
        'attachments_private' => [
            'max_size_kb'  => 51200,
            'allowed_mime' => ['*'],
            'is_public'    => false,        // ← presigned URL
            'disk'         => 'local',      // ← lưu local, không public web
            'conversions'  => [],
        ],
        'jodit_content' => [
            'max_size_kb'  => 10240,
            'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            'is_public'    => true,
            'conversions'  => ['medium'],
        ],
    ],

    'conversion_settings' => [
        'thumb'   => ['width' => 150,  'height' => 150,  'method' => 'crop',   'quality' => 85],
        'medium'  => ['width' => 800,  'height' => null, 'method' => 'scale',  'quality' => 82],
        'preview' => ['width' => 1200, 'height' => null, 'method' => 'scale',  'quality' => 80],
    ],

    'jodit_orphan_ttl_hours' => 72,   // ← 72h kể từ last_touched_at
];
```

---

## 9. URL Resolution Strategy

### 9.1 Nguyên tắc cốt lõi

> **Không bao giờ lưu URL đầy đủ vào DB.**  
> DB chỉ lưu `disk` + `storage_key` (relative path).  
> URL tạo tại runtime bởi `MediaUrlService`.

### 9.2 Resolution logic

```
MediaUrlService::url($media, $conversion = '')
    │
    ├─ $conversion != '' ?
    │       └─ key = "{base_path}/{conversion}.webp"
    │          generated_conversions[$conversion] = false? → key = original path (fallback)
    │
    ├─ custom_properties.is_public = false ?
    │       └─ return Storage::disk($media->disk)->temporaryUrl($key, now()->addMinutes(30))
    │                                              ← presigned URL, không cache
    │
    ├─ CDN configured? (config('media.cdn_url') != null)
    │       └─ return rtrim(config('media.cdn_url'), '/') . '/' . $key
    │
    └─ Không có CDN:
            └─ return Storage::disk($media->disk)->url($key)
```

### 9.3 Scenario thay đổi provider

| Action | Việc cần làm |
|--------|-------------|
| Đổi domain CDN | Sửa 1 biến env `MEDIA_CDN_URL` → deploy — 0 DB UPDATE |
| Chuyển từ local sang S3 | Chạy `php artisan media:migrate-disk --from=public --to=s3` |
| Thêm Cloudflare CDN | Set `MEDIA_CDN_URL=https://cdn.example.com` |
| Đổi bucket S3 | Sửa `AWS_BUCKET`, re-upload, cập nhật `disk` column |

### 9.4 Private file — Presigned URL

- Collection `attachments_private` có `is_public = false` trong config và `custom_properties`
- `disk` của collection này = `local` (Phase 1) hoặc `s3` với private bucket (Phase 2)
- `MediaUrlService` tự detect `is_public = false` → dùng `temporaryUrl()` (30 phút)
- **Presigned URL không lưu vào session** — frontend dùng trực tiếp
- Nếu URL expire (edge case) → refresh qua `GET /api/media/{uuid}/url`

---

## 10. Upload Endpoints — Jodit & FilePond

### 10A. Jodit Upload Endpoint

### 10.1 Cấu hình Jodit (thay base64)

```javascript
// resources/js/modules/jodit.js — standard và full preset
imageDefaultWidth: 800,
uploader: {
    url: '/api/media/jodit-upload',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'X-Context-Type': joditEl.dataset.contextType ?? '',   // e.g. "sop_process"
        'X-Context-Id':   joditEl.dataset.contextId   ?? '',   // e.g. "42"
    },
    format:    'json',
    method:    'POST',
    isSuccess: (resp) => !resp.error,
    getMsg:    (resp) => resp.message,
    process:   (resp) => ({
        files:   resp.data.files,
        baseurl: resp.data.baseurl,
        error:   resp.error,
        message: resp.message,
    }),
},
// insertImageAsBase64URI: false   ← TẮT
```

### 10.2 Route & Controller

```
POST   /api/media/jodit-upload            MediaJoditUploadController@store
DELETE /api/media/jodit-upload/{uuid}     MediaJoditUploadController@destroy
GET    /api/media/{uuid}/url              MediaUrlRefreshController@show   ← refresh presigned
```

Auth: `auth:sanctum` + `web` middleware — lấy organization từ TenantContext.

### 10.3 Request validation

```
files[]:      required, array, max:10
files[]*:     file, mimes:jpeg,jpg,png,gif,webp, max:10240 (KB)
```

`context_type` và `context_id` đọc từ headers `X-Context-Type`, `X-Context-Id`.

### 10.4 Response format (Jodit v4 expected)

**Success:**
```json
{
    "error": false,
    "message": "Uploaded successfully",
    "data": {
        "baseurl": "",
        "files": [
            "https://example.com/storage/media/5/jodit/jodit_draft/99/uuid1/medium.webp",
            "https://example.com/storage/media/5/jodit/jodit_draft/99/uuid2/medium.webp"
        ]
    }
}
```

> Trả về URL của `medium` variant (800px) — đây là kích thước hiển thị trong editor. Original giữ nguyên cho download.

**Error:**
```json
{
    "error": true,
    "message": "File quá lớn (max 10 MB)",
    "data": { "baseurl": "", "files": [] }
}
```

### 10.5 Orphan lifecycle — Jodit content images

```
[Upload] POST /api/media/jodit-upload
    → Tạo Media với model_type = 'jodit_draft', model_id = {draft_id}
    → last_touched_at = now()
    → Trả URL medium về Jodit

[Tiếp tục edit] Mỗi khi frontend gọi bất kỳ API nào của cùng content:
    → PATCH /api/media/jodit-touch  {uuids: [...]}
    → Update last_touched_at = now() cho tất cả UUID đó

[Save content] POST /api/sop-processes hoặc PUT /api/kc-items/...
    → Body chứa trường jodit_media_uuids: ["uuid1", "uuid2", ...]
    → MediaUploadService::reassociateOrphans($entity, $uuids)
        └─ UPDATE media SET model_type = 'SopProcess', model_id = 123
               WHERE uuid IN (...) AND collection_name = 'jodit_content'
    → Xóa orphan cũ của entity này (jodit_content + model_id = 123 nhưng uuid KHÔNG trong list)

[Cleanup cron — daily] php artisan media:cleanup-orphans
    → DELETE FROM media
      WHERE collection_name = 'jodit_content'
        AND model_type = 'jodit_draft'
        AND last_touched_at < NOW() - INTERVAL 72 HOUR
    → Xóa file trên disk cho mỗi record bị xóa
```

**TTL là 72h kể từ `last_touched_at`** (không phải `created_at`) — đảm bảo session edit dài 3–6 tiếng không bị cleanup nếu user vẫn còn active.

---

### 10B. FilePond Upload Endpoint

### 10B.1 Route & Controller

```
POST   /api/v1/media/upload            MediaUploadController@store
DELETE /api/v1/media/upload/{uuid}     MediaUploadController@destroy
```

Auth: `middleware(['auth', 'tenant'])` — lấy organization từ `TenantContext`.

### 10B.2 Request validation — store()

```
Headers:
  X-CSRF-TOKEN:    (bắt buộc)
  X-Collection:    avatar | logo | thumbnail | cover | attachments | attachments_private
                   (bắt buộc — quyết định disk, conversion, access policy)
  X-Context-Type:  snake_case entity (employee, organization, ...) — optional
  X-Context-Id:    numeric entity ID — optional (nếu có → gắn trực tiếp vào entity)

Body (multipart):
  file:  required, file, max theo collection config
```

> **Không truyền `collection` qua body** — tránh caller override collection name ngoài allowlist.  
> `X-Collection` được validate trong controller: chỉ chấp nhận key có trong `config/media.php`.

### 10B.3 Response format

**Success:**
```json
{
    "uuid":      "a1b2c3d4-...",
    "url":       "https://example.com/storage/media/5/employee/employee/12/uuid/medium.webp",
    "thumb_url": "https://example.com/storage/media/5/employee/employee/12/uuid/thumb.webp"
}
```

> FilePond nhận `uuid` từ response để lưu làm file "server ID".  
> `thumb_url` dùng cho preview trong FilePond image preview plugin.  
> Nếu collection không có conversion → `thumb_url` = `url` = original URL.

**Error:**
```json
{
    "message": "File quá lớn (max 5 MB)",
    "errors":  { "file": ["File quá lớn (max 5 MB)"] }
}
```
HTTP 422 — FilePond hiển thị error dưới file item.

### 10B.4 destroy() — revert trước khi save form

Chỉ xóa được nếu media **chưa được re-associate** khỏi `JoditDraft` (tức là `model_type = JoditDraft`).  
Sau khi form save và `reassociateOrphans()` chạy, endpoint này trả 403.

### 10B.5 FilePond JS wrapper — `initFilePondUpload()`

```javascript
// resources/js/modules/filepond.js — hàm bổ sung (không thay initFilePond cũ)

/**
 * initFilePondUpload — FilePond gắn với MediaUploadService backend.
 *
 * @param {string|HTMLElement} selector   - input[type=file]
 * @param {object}             options
 *   collection   string  bắt buộc: 'avatar' | 'logo' | 'thumbnail' | 'cover' | 'attachments'
 *   contextType  string  optional: 'employee' | 'organization' | ...
 *   contextId    number  optional: entity ID nếu là edit form
 *   maxFiles     number  default: 1 cho avatar/logo/thumbnail/cover, 10 cho attachments
 *   onUploaded   fn(uuid, url, thumbUrl)   callback khi upload xong — gọi để gán UUID vào hidden input
 *   onReverted   fn(uuid)                  callback khi user revert
 *   existingUrl  string  URL ảnh hiện tại (edit form) — hiển thị làm initial preview
 *
 * Tự động:
 *  · Đặt maxFileSize theo collection config (5MB/10MB/50MB)
 *  · Đặt acceptedFileTypes theo collection config
 *  · Gắn CSRF token lazy tại init time
 *  · Gắn X-Collection, X-Context-Type, X-Context-Id vào headers
 *  · Trả UUID về hidden input [name="media_uuid[{collection}]"] hoặc qua onUploaded callback
 *
 * Ví dụ trong Blade:
 *   <input type="file" id="avatar-upload" accept="image/*">
 *   <input type="hidden" name="media_uuid[avatar]" id="avatar-uuid" value="{{ $employee->getFirstMedia('avatar')?->uuid }}">
 *   <script>
 *     initFilePondUpload('#avatar-upload', {
 *       collection: 'avatar',
 *       contextType: 'employee',
 *       contextId: {{ $employee->id }},
 *       existingUrl: '{{ $employee->getMediaUrl('avatar', 'medium') }}',
 *       onUploaded: (uuid) => document.getElementById('avatar-uuid').value = uuid,
 *     });
 *   </script>
 */
function initFilePondUpload(selector, options = {}) {
    const el = typeof selector === 'string'
        ? document.querySelector(selector)
        : selector;
    if (!el) { console.warn('[FilePond] Element not found:', selector); return null; }

    const {
        collection,
        contextType,
        contextId,
        maxFiles    = (collection === 'attachments' || collection === 'attachments_private') ? 10 : 1,
        onUploaded,
        onReverted,
        existingUrl,
        ...rest
    } = options;

    if (!collection) throw new Error('[FilePondUpload] collection option is required');

    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // Collection → maxFileSize mapping (mirrors config/media.php)
    const MAX_SIZE_MAP = {
        avatar: '5MB', logo: '5MB', thumbnail: '5MB',
        cover: '10MB', jodit_content: '10MB',
        attachments: '50MB', attachments_private: '50MB',
    };

    const serverHeaders = () => ({
        'X-CSRF-TOKEN':   csrfToken(),
        'Accept':         'application/json',
        'X-Collection':   collection,
        ...(contextType ? { 'X-Context-Type': contextType } : {}),
        ...(contextId   ? { 'X-Context-Id':   String(contextId) } : {}),
    });

    const pond = FilePond.create(el, {
        ...DEFAULTS,
        allowMultiple: maxFiles > 1,
        maxFiles,
        maxFileSize:   MAX_SIZE_MAP[collection] ?? '10MB',
        server: {
            process: {
                url:     '/api/v1/media/upload',
                method:  'POST',
                headers: serverHeaders,
                onload:  (response) => {
                    const data = JSON.parse(response);
                    onUploaded?.(data.uuid, data.url, data.thumb_url);
                    return data.uuid;       // FilePond stores này làm server ID
                },
                onerror: (response) => {
                    const data = JSON.parse(response);
                    return data.message ?? 'Upload thất bại';
                },
            },
            revert: {
                url:     '/api/v1/media/upload',    // DELETE /{uuid}
                headers: serverHeaders,
                onload: (uuid) => {
                    onReverted?.(uuid);
                },
            },
        },
        // Nếu có ảnh hiện tại (edit form), hiển thị làm preview không-upload
        ...(existingUrl ? {
            files: [{
                source:  existingUrl,
                options: { type: 'local' },
            }],
        } : {}),
        ...rest,
    });

    FilePondInstances.set(typeof selector === 'string' ? selector : (el.id || collection), pond);
    return pond;
}

window.initFilePondUpload = initFilePondUpload;
export { initFilePondUpload };
```

### 10B.6 Xử lý form save — re-association

**Create form (không biết entity ID khi upload):**

```html
{{-- Blade: hidden input chứa UUID trả về từ FilePond --}}
<input type="hidden" name="media_uuid[avatar]" id="avatar-uuid">
```

```php
// Controller store() — sau khi tạo entity
$employee = Employee::create($validated);

// Gắn avatar nếu có UUID
if ($uuid = $request->input('media_uuid.avatar')) {
    app(MediaUploadService::class)->reassociateOrphans($employee, [$uuid], 'avatar');
}
```

**Edit form (biết entity ID, upload gắn trực tiếp):**

Vì `X-Context-Id` được truyền từ FilePond, controller `MediaUploadController::store()` sẽ gắn trực tiếp Media vào entity mà không tạo JoditDraft. UUID vẫn trả về để frontend biết (và có thể revert).

> **Lưu ý `reassociateOrphans` cho FilePond:** cùng method với Jodit nhưng thêm tham số `collection`:
> ```php
> MediaUploadService::reassociateOrphans($model, $uuids, $collection = 'jodit_content')
> ```
> Cho avatar/logo chỉ cần 1 UUID; collection `avatar`/`logo` tự động old-media cleanup (single media collection).

---

## 11. Migration Path: Local → S3 → CDN

### 11.1 Tổng quan 3 phase

```
Phase 1: Nền tảng (hiện tại)
    ├─ media table + organization_id + uploaded_at + last_touched_at
    ├─ MediaUploadService thay thế 3 action cũ
    ├─ Conversion đồng bộ inline
    ├─ disk = "public" (local web-accessible) cho hầu hết
    ├─ disk = "local"  (private) cho attachments_private
    └─ URL: Storage::disk('public')->url()

Phase 2: S3 rollout
    ├─ Set MEDIA_DISK=s3 trong .env
    ├─ Uploads mới → S3 public bucket (public collections) / S3 private bucket (attachments_private)
    ├─ Files cũ trên local → php artisan media:migrate-disk --from=public --to=s3 (batch off-peak)
    │       → verify checksum → update disk = "s3" per record → xóa local
    └─ URL: Storage::disk('s3')->url() hoặc presigned

Phase 3: CDN overlay
    ├─ Cấu hình CloudFront/Cloudflare R2/BunnyCDN trỏ vào S3 public bucket
    ├─ Set MEDIA_CDN_URL=https://cdn.example.com
    ├─ conversions_disk = "cdn" cho thumb/medium/preview
    ├─ original vẫn trên S3 (presigned nếu private)
    └─ URL: config('media.cdn_url') / storage_key (0 DB UPDATE khi đổi domain)
```

### 11.2 Artisan command migrate disk

```
php artisan media:migrate-disk
    --from=public         disk nguồn
    --to=s3               disk đích
    --collection=avatar   (optional) chỉ migrate 1 collection
    --batch=100           số record mỗi batch
    --dry-run             in ra danh sách không thực sự copy

Logic:
1. SELECT media WHERE disk = from [AND collection = ...] ORDER BY id LIMIT batch
2. For each: download → upload → verify checksum → UPDATE disk = to → delete from source
3. Log progress, dừng lại nếu verify fail (không rollback records đã migrate)
```

### 11.3 Backward compatibility

- `MediaUrlService` handle đúng từng `disk` per record trong suốt quá trình migration
- Không cần feature flag — 2 disk cùng hoạt động song song

---

## 12. Indexes & Performance

### 12.1 Indexes trên bảng `media`

```sql
-- [1] Core Spatie lookup: lấy media của 1 entity theo collection
--     Query: WHERE model_type = ? AND model_id = ? AND collection_name = ?
INDEX idx_media_polymorphic (model_type, model_id, collection_name)

-- [2] Storage migration: scan theo disk
--     Query: WHERE disk = ? ORDER BY created_at LIMIT ?
INDEX idx_media_disk (disk, created_at)

-- [3] Jodit orphan cleanup: scan jodit orphan hết TTL
--     Query: WHERE collection_name = 'jodit_content' AND model_type = 'jodit_draft'
--            AND last_touched_at < ?
INDEX idx_media_orphan (collection_name, model_type, last_touched_at)

-- [4] Tenant FK (tự động qua FOREIGN KEY declaration)
--     organization_id đã có index từ FK constraint
```

> **Indexes bị loại bỏ so với v1.0.0:**
> - `idx_media_model (model_type, model_id)` — thay bằng compound có `collection_name`
> - `idx_media_org (organization_id, created_at)` — defer, không có query thực tế nào dùng hiện tại
> - `idx_media_org_collection (organization_id, model_type, collection_name)` — defer, premature optimization

### 12.2 N+1 prevention — bắt buộc khi migrate

Các query listing cần thêm eager load **trước khi** drop cột URL cũ:

| File | Dòng | Thay đổi |
|------|------|---------|
| `Modules/Employee/app/Queries/ListEmployeesHandler.php` | 31 | Thêm `'media'` vào `with([...])` |
| `Modules/Employee/app/Http/Resources/EmployeeListResource.php` | 22 | Đổi `$this->avatar_url` → `$this->getFirstMediaUrl('avatar', 'thumb')` |
| `Modules/Recruitment/resources/views/candidates/show.blade.php` | — | Thêm `->load('media')` nếu chưa eager |

### 12.3 Chiến lược hiệu suất

| Vấn đề | Giải pháp |
|--------|-----------|
| N+1 khi list employees + avatar | Eager load `with('media')` — bắt buộc trước Phase 2 |
| Conversion làm chậm upload response | File ảnh < 5MB resize < 500ms — chấp nhận được; nếu cần async → Phase 3 queue |
| S3 latency cho ảnh public | CDN overlay Phase 3 → serve từ edge node |
| Không query by storage_key | Luôn query by `(model_type, model_id, collection_name)` |
| Jodit base64 làm phình content column | Migrate sang file upload → content chỉ lưu `<img src="url">` |
| Presigned URL expire khi email | `GET /api/media/{uuid}/url` để refresh URL trước khi gửi |

### 12.4 Quota management (tùy chọn Phase 2)

```
org_media_quotas (bảng phụ, thêm khi cần billing)
    organization_id  BIGINT FK
    total_size_bytes BIGINT   ← tổng bytes đang dùng (cập nhật bởi observer)
    max_size_bytes   BIGINT   ← giới hạn gói
```

---

## 13. Xung đột & Lộ trình hợp nhất

### 13.1 3 bảng attachment cũ → `media`

| Bảng cũ | Collection mới | Disk |
|---------|---------------|------|
| `rc_candidate_attachments` | `attachments_private` trên model `RcCandidate` | `local` |
| `sop_step_attachments` | `attachments` trên model `SopStep` | `public` |
| `kc_item_attachments` | `attachments` trên model `KcItem` | `public` |

**Mapping cột đầy đủ:**

| Cột cũ | Cột mới `media` | Ghi chú |
|--------|----------------|---------|
| `file_name` | `name` + `file_name` | `name` = tên không extension, `file_name` = tên đầy đủ |
| `file_url` | Không lưu | Derive từ `disk` + `storage_key` tại runtime |
| `storage_key` | `storage_key` | Giữ nguyên path — không đổi vật lý file |
| `storage_provider` | `disk` | Rename: `'local'` → `'public'` hoặc `'local'` tùy module |
| `file_size_kb` | `size` | Đổi đơn vị: `size = file_size_kb * 1024` |
| `file_type` | `mime_type` | — |
| `alt_text` | `custom_properties.alt_text` | JSON |
| `uploaded_by` | `custom_properties.uploaded_by` + giữ tham chiếu | JSON, không index |
| `uploaded_at` | **`uploaded_at`** (cột riêng) | **Giữ nguyên timestamp gốc** — không dùng `created_at` |
| `sort_order` | `order_column` | — |

> **`uploaded_at` phải được populate từ giá trị cũ**, không dùng `now()` khi migrate. Đây là dữ liệu audit không được mất.

### 13.2 Direct URL columns trên model

**Quy trình migration an toàn:**
1. Deploy Phase 1 → media table hoạt động
2. Data migration script: với mỗi URL cũ → tạo Media record với `disk = 'external'`, `storage_key = URL cũ`, `generated_conversions = {}`
3. `MediaUrlService`: nếu `disk = 'external'` → trả thẳng `storage_key` (backward compat)
4. Test kỹ trên staging — cả 2 cột cũ và media record cùng tồn tại
5. Drop cột cũ trong migration riêng sau khi verify Phase 2

### 13.3 Action classes cũ → refactor gọi MediaUploadService

| File | Action |
|------|--------|
| `StoreSopStepAttachmentAction` | Thay bằng `MediaUploadService::upload($file, $step, 'attachments')` |
| `StoreKcAttachmentAction` | Thay bằng `MediaUploadService::upload($file, $kcItem, 'attachments')` |
| `StoreCandidateAttachmentAction` | Thay bằng `MediaUploadService::upload($file, $candidate, 'attachments_private')` |
| `DestroySopStepAttachmentAction` | Thay bằng `MediaUploadService::delete($media)` |
| `DestroyKcAttachmentAction` | Thay bằng `MediaUploadService::delete($media)` |

### 13.4 config/sop.php và config/kc.php

Sau khi `config/media.php` có hiệu lực, xóa các key storage khỏi module config:
- `config/sop.php`: xóa block `attachments` (max_size, disk, prefix, mimes)
- `config/kc.php`: xóa block `storage` và attachment limits

---

## 14. Lộ trình triển khai

### Phase 1 — Foundation ✅ DONE

| # | Task | Trạng thái |
|---|------|-----------|
| 1 | `composer require intervention/image-laravel:^3.0` | ✅ Done |
| 2 | Chạy vendor migration tạo `media` table | ✅ Done |
| 3 | Migration ALTER `media`: thêm `organization_id`, `uploaded_at`, `last_touched_at` | ✅ Done |
| 4 | `App\Models\Media` extends Spatie + `BelongsToOrganization` + override `newQuery()` | ✅ Done |
| 5 | Publish + override `config/media-library.php`: `media_model = App\Models\Media::class` | ✅ Done |
| 6 | `config/media.php` với đầy đủ collection definitions | ✅ Done |
| 7 | `MediaUploadService` — upload, validate, sync conversion, delete, pruneEmptyAncestors | ✅ Done |
| 8 | `MediaUrlService` — url(), temporaryUrl(), CDN overlay, is_public check | ✅ Done |
| 9 | `HasTenantMedia` trait — getMediaUrl(), getFirstMediaUrl() | ✅ Done |
| 10 | Jodit upload endpoint + route + `MediaJoditUploadController` | ✅ Done |
| 11 | `MediaCleanupOrphansCommand` (`media:cleanup-orphans`) — dùng service delete() | ✅ Done |
| 12 | `jodit.js`: all plugins, CSRF lazy, imageDefaultWidth, popup toolbar | ✅ Done |
| **13** | **`MediaUploadController` — FilePond endpoint** `POST/DELETE /api/v1/media/upload` | **⬜ TODO** |
| **14** | **`filepond.js`: thêm `initFilePondUpload()` tích hợp MediaUploadController** | **⬜ TODO** |

### Phase 2 — Consolidation (Sprint 2)

| # | Task | Ghi chú |
|---|------|---------|
| 15 | Gắn `HasTenantMedia` vào Employee, Organization, MktApplicant, MktApplicantPortfolio | Models (đã có, cần verify) |
| 16 | Thêm eager load `with('media')` vào `ListEmployeesHandler` và các listing query | **Trước khi drop cột cũ** |
| 17 | Triển khai FilePond trên form avatar Employee, logo Organization | Frontend — dùng `initFilePondUpload()` |
| 18 | Refactor 5 action class (Sop, KC, Recruitment) gọi `MediaUploadService` | Backend |
| 19 | Viết data migration: copy records 3 bảng cũ → `media` (giữ `uploaded_at` gốc) | Script |
| 20 | Viết data migration: URL cũ của models → `media` với `disk = 'external'` | Script |
| 21 | Dọn dẹp `config/sop.php` và `config/kc.php`: xóa storage-related keys | Config cleanup |
| 22 | Test toàn bộ upload flow + URL resolution + orphan cleanup trên staging | QA |
| 23 | Drop cột URL trực tiếp trên models sau verify | Migration |

### Phase 3 — S3 + CDN + Queue (khi ready)

| # | Task | Ghi chú |
|---|------|---------|
| 24 | Set `MEDIA_DISK=s3`, bật S3 credentials trong .env | Config |
| 25 | Viết artisan command `media:migrate-disk` — batch migrate local → S3 | Command |
| 26 | Cấu hình CDN, set `MEDIA_CDN_URL` | Config |
| 27 | Wrap conversion block thành `ProcessMediaConversions extends TenantAwareJob` (nếu cần async) | Optional |
| 28 | Rename 3 bảng cũ → `_deprecated`, drop sau 30 ngày verify | Cleanup |

---

---

## 15. Frontend Patterns — Display & Upload

### 15.1 Quy tắc vàng về hiển thị ảnh

> **Không bao giờ lưu hoặc render URL cứng vào DB/template.**  
> Luôn gọi `getMediaUrl()` / `getFirstMediaUrl()` hoặc `MediaUrlService::url()` tại runtime.

```php
// ✅ ĐÚNG — URL resolve tại runtime, disk-agnostic
$employee->getMediaUrl('avatar', 'thumb')

// ❌ SAI — hardcode path, vỡ khi đổi disk/CDN
'/storage/media/5/employee/employee/12/uuid/thumb.webp'

// ❌ SAI — dùng cột cũ trực tiếp không fallback
$employee->avatar_url
```

### 15.2 Fallback pattern trong Blade

```blade
{{-- Avatar với DiceBear fallback --}}
@php
    $avatarUrl = $employee->getMediaUrl('avatar', 'thumb')
        ?: 'https://api.dicebear.com/9.x/initials/svg?seed=' . urlencode($employee->full_name);
@endphp
<img src="{{ $avatarUrl }}"
     alt="{{ $employee->full_name }}"
     class="w-10 h-10 rounded-full object-cover"
     loading="lazy">

{{-- Logo org --}}
@php $logoUrl = $organization->getMediaUrl('logo', 'medium') @endphp
@if($logoUrl)
    <img src="{{ $logoUrl }}" alt="{{ $organization->name }}" class="h-8">
@else
    <span class="font-bold text-lg">{{ $organization->name }}</span>
@endif

{{-- Cover với placeholder --}}
<img src="{{ $entity->getMediaUrl('cover', 'medium') ?: asset('images/cover-placeholder.svg') }}"
     class="w-full aspect-video object-cover">
```

### 15.3 Eager loading — tránh N+1

**Bắt buộc** thêm `with('media')` vào mọi query có dùng `getMediaUrl()` trong vòng lặp:

```php
// ✅ ĐÚNG
Employee::query()->with('media')->get()->each(fn($e) => $e->getMediaUrl('avatar', 'thumb'));

// ❌ SAI — N+1: mỗi getMediaUrl() chạy 1 query riêng
Employee::query()->get()->each(fn($e) => $e->getMediaUrl('avatar', 'thumb'));
```

Trong API Resource:
```php
// EmployeeListResource.php — luôn giả định media đã được eager loaded
'avatar_url' => $this->whenLoaded('media',
    fn() => $this->getMediaUrl('avatar', 'thumb') ?: $this->avatar_url,
    $this->avatar_url  // fallback nếu relation chưa load
),
```

Trong Tabulator (ListEmployeesHandler):
```php
->with(['department', 'branch', 'media'])  // ← thêm 'media'
```

### 15.4 Upload form với FilePond — single image (avatar/logo)

```blade
{{-- Form upload avatar Employee --}}
<div x-data="{ uuid: '{{ $employee->getFirstMedia('avatar')?->uuid ?? '' }}' }">
    {{-- FilePond input --}}
    <input type="file" id="avatar-upload" accept="image/jpeg,image/png,image/webp">

    {{-- Hidden input truyền UUID khi form submit --}}
    <input type="hidden" name="media_uuid[avatar]" :value="uuid">

    {{-- Preview hiện tại --}}
    @if($currentUrl = $employee->getMediaUrl('avatar', 'medium'))
        <img src="{{ $currentUrl }}" id="current-avatar" class="w-20 h-20 rounded-full">
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    initFilePondUpload('#avatar-upload', {
        collection:  'avatar',
        contextType: 'employee',
        contextId:   {{ $employee->id }},
        existingUrl: '{{ $employee->getMediaUrl('avatar', 'medium') }}',
        onUploaded:  (uuid) => {
            document.querySelector('[name="media_uuid[avatar]"]').value = uuid;
        },
    });
});
</script>
@endpush
```

### 15.5 Upload form với FilePond — multiple files (attachments)

```blade
{{-- Attachments cho SopStep --}}
<input type="file" id="step-attachments" multiple>

{{-- Multiple UUIDs: PHP array notation --}}
<div id="attachment-uuids"></div>

@push('scripts')
<script>
initFilePondUpload('#step-attachments', {
    collection:  'attachments',
    contextType: 'sop_step',
    contextId:   {{ $step->id }},
    maxFiles:    10,
    onUploaded: (uuid) => {
        const input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = 'media_uuids[]';
        input.value = uuid;
        document.getElementById('attachment-uuids').appendChild(input);
    },
    onReverted: (uuid) => {
        document.querySelectorAll('[name="media_uuids[]"]')
            .forEach(el => { if (el.value === uuid) el.remove(); });
    },
});
</script>
@endpush
```

### 15.6 Displaying media variants — khi nào dùng variant nào

| Ngữ cảnh | Collection | Variant | Lý do |
|---------|-----------|---------|-------|
| Danh sách (table, grid) | avatar | `thumb` (150×150) | Nhỏ, tải nhanh |
| Profile page | avatar | `medium` (800px) | Đủ rõ mà không quá nặng |
| Logo trong header | logo | `medium` (800px) | Đủ sharp cho retina |
| Logo thumbnail | logo | `thumb` (150×150) | List org, badge |
| Card preview | thumbnail/cover | `medium` | Đủ cho card |
| Lightbox / full view | cover | `preview` (1200px) | Chất lượng cao |
| Jodit editor content | jodit_content | `medium` | Hiển thị trong editor |
| Download link | bất kỳ | `''` (original) | Giữ chất lượng gốc |

```blade
{{-- Selector kép: medium cho desktop, thumb cho mobile --}}
<img srcset="{{ $employee->getMediaUrl('avatar', 'thumb') }} 150w,
             {{ $employee->getMediaUrl('avatar', 'medium') }} 800w"
     sizes="(max-width: 640px) 150px, 800px"
     src="{{ $employee->getMediaUrl('avatar', 'medium') }}"
     loading="lazy"
     alt="{{ $employee->full_name }}">
```

### 15.7 Private files — attachment_private

```blade
{{-- URL presigned tự động (30 phút), không cache --}}
<a href="{{ $candidate->getFirstMediaUrl('attachments_private') }}"
   target="_blank" rel="noopener">
   Tải CV
</a>

{{-- Nếu link sắp expire → refresh via API --}}
<a href="#" data-media-uuid="{{ $media->uuid }}"
   @click.prevent="refreshAndOpen($event.target.dataset.mediaUuid)">
   Tải CV
</a>
```

```javascript
// Alpine component cho presigned URL refresh
async function refreshAndOpen(uuid) {
    const resp = await fetch(`/api/v1/media/${uuid}/url`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
    });
    const { url } = await resp.json();
    window.open(url, '_blank');
}
```

---

## 16. Module Guide — Trạng thái & Cách dùng

### 16.1 Trạng thái hiện tại từng model

| Model | Collection | HasTenantMedia | Cột cũ | Trạng thái | Action |
|-------|-----------|---------------|--------|-----------|--------|
| `Employee` | `avatar` | ✅ | `avatar_url` | ⚠️ Fallback sang cột cũ | Phase 2: FilePond + data migration |
| `Organization` | `logo` | ✅ | `logo_path` | ⚠️ Fallback sang cột cũ | Phase 2: FilePond + data migration |
| `MktApplicant` | `avatar` | ✅ | `avatar_url` | ⚠️ Fallback sang cột cũ | Phase 2 |
| `MktApplicantPortfolio` | `thumbnail` | ✅ | `thumbnail_url` | ⚠️ Fallback sang cột cũ | Phase 2 |
| `JpJobPost` | `cover`, `thumbnail` | ⬜ Chưa | — | ❌ Chưa implement | Phase 2 |
| `KcCategory` | `cover` | ⬜ Chưa | — | ❌ Chưa implement | Phase 2 |
| `SopStep` | `attachments` | ✅ | `sop_step_attachments` | ⚠️ Bảng cũ riêng | Phase 2: migrate bảng |
| `KcItem` | `attachments` | ✅ | `kc_item_attachments` | ⚠️ Bảng cũ riêng | Phase 2: migrate bảng |
| `RcCandidate` | `attachments_private` | ✅ | `rc_candidate_attachments` | ⚠️ Bảng cũ riêng | Phase 2: migrate bảng |
| `JoditDraft` | `jodit_content` | ✅ | — | ✅ Done | — |

> **Trạng thái ⚠️ Fallback** = model đã có `HasTenantMedia` nhưng view/resource vẫn fallback về cột cũ.  
> Pattern an toàn khi cả 2 song song: `getMediaUrl('avatar', 'thumb') ?: $this->avatar_url`

### 16.2 Thứ tự implement cho từng module

**Bước chuẩn cho mỗi module (ví dụ Employee avatar):**

```
1. Verify model có HasTenantMedia + implements HasMedia
   class Employee extends TenantAwareModel implements HasMedia { use HasTenantMedia; }

2. Thêm eager load vào query handler
   ->with(['department', 'branch', 'media'])

3. Tạo MediaUploadController (nếu chưa có) + route
   POST   /api/v1/media/upload
   DELETE /api/v1/media/upload/{uuid}

4. Thêm initFilePondUpload() vào edit/create form

5. Trong controller store/update:
   if ($uuid = $request->input('media_uuid.avatar')) {
       $media = Media::where('uuid', $uuid)->first();
       if ($media && $media->model_type === JoditDraft::class) {
           app(MediaUploadService::class)->reassociateOrphans($employee, [$uuid], 'avatar');
       }
       // Nếu contextId đã biết khi upload → media đã gắn trực tiếp, không cần reassociate
   }

6. Update Resource/Blade để dùng getMediaUrl() với fallback về cột cũ
   'avatar_url' => $this->getMediaUrl('avatar', 'thumb') ?: $this->avatar_url

7. Data migration: copy avatar_url cũ → media record (disk='external', storage_key=url)
   Sau đó test kỹ → Drop cột cũ
```

### 16.3 Employee Avatar — chi tiết

```php
// Employee model — đã đúng
class Employee extends TenantAwareModel implements HasMedia {
    use HasTenantMedia;
    // Giữ $fillable avatar_url trong quá trình migration
}

// EmployeeListResource — fallback pattern hiện tại (đúng)
'avatar_url' => $this->getMediaUrl('avatar', 'thumb') ?: $this->avatar_url,

// Blade show.blade.php
$avatarUrl = $employee->getMediaUrl('avatar', 'medium')
    ?: $employee->avatar_url
    ?: 'https://api.dicebear.com/9.x/initials/svg?seed=' . urlencode($employee->full_name);
```

FilePond config cho Employee form:
- `collection`: `avatar`
- `maxFiles`: 1
- `allowMultiple`: false
- `acceptedFileTypes`: `['image/jpeg', 'image/png', 'image/webp']`

### 16.4 Organization Logo — chi tiết

```php
// Organization model — đã đúng
class Organization extends BaseOrganization implements HasMedia {
    use HasTenantMedia;
    // Giữ $fillable logo_path trong quá trình migration
}

// Blade header
$logoUrl = $organization->getMediaUrl('logo', 'medium') ?: $organization->logo_path;
```

FilePond config cho Organization form:
- `collection`: `logo`
- `maxFiles`: 1
- `contextType`: `organization`, `contextId`: `$organization->id`
- `acceptedFileTypes`: `['image/jpeg', 'image/png', 'image/webp']`

> Logo collection dùng `contain` thay `crop` cho thumb để không cắt xén logo.  
> **TODO**: Thêm `logo_method: 'contain'` vào config và handle trong `runConversions()`.

### 16.5 Jodit Content Images — summary

Xem §10A đầy đủ. Tóm tắt cách dùng trong controller:

```php
// Sau khi save content có Jodit editor
$process = SopProcess::create([...]);

// Extract UUIDs từ content HTML (hoặc frontend truyền về)
$uuids = extractMediaUuidsFromHtml($request->content);
// Hoặc frontend truyền: $uuids = $request->input('jodit_media_uuids', []);

app(MediaUploadService::class)->reassociateOrphans($process, $uuids);
// → Media với uuid trong $uuids: model_type đổi từ JoditDraft → SopProcess
// → Media cũ của entity này không có trong $uuids: bị xóa (lazy cleanup)
```

Helper extract UUIDs từ HTML:
```php
function extractMediaUuidsFromHtml(string $html): array
{
    preg_match_all('/data-media-uuid="([a-f0-9\-]{36})"/', $html, $matches);
    return $matches[1] ?? [];
}
```

### 16.6 Ràng buộc bổ sung (v1.2.0)

| # | Ràng buộc | Lý do |
|---|----------|-------|
| B8 | `initFilePondUpload()` — truyền `X-Collection` qua header, không qua body | Body có thể bị forge; header validate trong controller |
| B9 | FilePond `revert` chỉ được xóa media còn `model_type = JoditDraft` | Tránh xóa file đã gắn vào entity thật |
| B10 | Mọi listing query có `getMediaUrl()` trong loop phải có `with('media')` | N+1 prevention |
| B11 | Blade không render URL cứng — luôn qua `getMediaUrl()` với fallback | Không vỡ khi đổi disk/CDN |
| B12 | Single-image collections (avatar, logo, thumbnail, cover): `maxFiles=1`, dùng Spatie `singleFile()` hoặc xóa media cũ trước khi thêm mới | Tránh accumulate nhiều file cùng collection |

---

## Phụ lục A: Packages

| Package | Version | Lý do | Trạng thái |
|---------|---------|-------|-----------|
| `intervention/image-laravel` | `^3.0` | Image resize + WebP conversion | ✅ Đã có |
| `spatie/laravel-medialibrary` | `^11` | Media engine | ✅ Đã có |
| `filepond` | `^4.32` | File upload UI | ✅ Đã có (npm) |
| `filepond-plugin-image-preview` | `^4.6` | Preview ảnh trong FilePond | ✅ Đã có (npm) |
| `filepond-plugin-file-validate-size` | `^2.2` | Validate size phía client | ✅ Đã có (npm) |
| `filepond-plugin-file-rename` | `^1.1` | Rename file trước upload | ✅ Đã có (npm) |
| `filepond-plugin-image-exif-orientation` | `^1.0` | Fix EXIF rotation mobile | ✅ Đã có (npm) |

> Không cần thêm package nào khác ở Phase 1–2.

---

## Phụ lục B: Tóm tắt ràng buộc bắt buộc

| # | Ràng buộc | Lý do |
|---|----------|-------|
| B1 | `App\Models\Media` extends Spatie TRỰC TIẾP, không qua `TenantAwareModel` | Tránh SoftDeletes conflict |
| B2 | Override `newQuery()` trong `App\Models\Media` — bypass scope khi context null | Spatie internal queries không được trả empty |
| B3 | `is_public` config per collection — không để caller set | Access policy nhất quán, không bị override nhầm |
| B4 | `uploaded_at` lấy từ data cũ, không dùng `now()` | Audit trail không được mất |
| B5 | Jodit orphan TTL = 72h từ `last_touched_at` (không phải `created_at`) | Session edit dài không bị cleanup sớm |
| B6 | Eager load `with('media')` trước khi drop cột URL cũ | Tránh N+1 trên listing pages |
| B7 | `RcCandidate` dùng `attachments_private`, không phải `attachments` | Access policy khác nhau giữa SOP và Recruitment |
| B8 | `initFilePondUpload()` — truyền `X-Collection` qua header, không qua body | Body có thể bị forge; header validate trong controller |
| B9 | FilePond `revert` chỉ được xóa media còn `model_type = JoditDraft` | Tránh xóa file đã gắn vào entity thật |
| B10 | Mọi listing query có `getMediaUrl()` trong loop phải có `with('media')` | N+1 prevention — xem §15.3 |
| B11 | Blade không render URL cứng — luôn qua `getMediaUrl()` với fallback | Không vỡ khi đổi disk/CDN |
| B12 | Single-image collections (avatar, logo, thumbnail, cover): `maxFiles=1`, xóa media cũ trước khi thêm mới | Tránh accumulate nhiều file cùng collection |

---

> **Tài liệu liên quan:**
> - `spec/sop.md` — SopStepAttachment (hợp nhất vào `media.attachments`)
> - `spec/recruitment.md` — RcCandidateAttachment (hợp nhất vào `media.attachments_private`)
> - `spec/kc.md` — KcItemAttachment (hợp nhất vào `media.attachments`)
> - `docs/migration-pattern.md` — pattern migration chuẩn của hệ thống
>
> **Implementation files:**
> - `app/Services/Media/MediaUploadService.php` — core upload/delete/conversion
> - `app/Services/Media/MediaUrlService.php` — URL resolution
> - `app/Services/Media/MediaPathGenerator.php` — path convention
> - `app/Http/Controllers/Api/MediaJoditUploadController.php` — Jodit endpoint ✅
> - `app/Http/Controllers/Api/MediaUploadController.php` — FilePond endpoint ⬜ TODO
> - `app/Models/Media.php` — tenant-aware Spatie extension
> - `app/Models/JoditDraft.php` — orphan holder
> - `app/Traits/HasTenantMedia.php` — model trait
> - `app/Console/Commands/MediaCleanupOrphansCommand.php` — orphan cleanup
> - `config/media.php` — tất cả collection definitions
> - `resources/js/modules/jodit.js` — Jodit wrapper ✅
> - `resources/js/modules/filepond.js` — FilePond wrapper (cần thêm `initFilePondUpload`) ⬜
