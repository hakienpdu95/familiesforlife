# OcopSubject — Chủ thể OCOP

Module `Modules/OcopSubject`. Tách "Nhà sản xuất" (trước là text nhập tay trên từng sản phẩm OCOP) thành thực thể riêng; 1 chủ thể có nhiều sản phẩm (1-N).

Kiến trúc: AVSA + CQRS-lite — `app/Features/OcopSubjectManagement/{Actions,Data,Http,Queries}`.

## 1. Loại hình (`OcopSubjectOrganizationType`)

| value | label |
|---|---|
| `enterprise` | Doanh nghiệp |
| `cooperative` | Hợp tác xã |
| `household` | Hộ kinh doanh |

## 2. Bảng `ocop_subjects`

| Nhóm | Cột |
|---|---|
| Cơ bản | `name`, `name_en`, `tax_code` (MST 10 số / 10 số-XXX / CCCD 12 số), `organization_type`, `legal_representative`, `position` |
| Địa chỉ & định vị | `address`, `province_code` + `province_name`, `ward_code` + `ward_name`, `gps_coordinates` ("lat, lng", bắt buộc mọi loại hình), `factory_code`, `is_food_business` |
| OCOP | `ocop_star` (3–5), `ocop_cert_expiry` |
| Liên hệ | `hotline`, `email`, `website` |
| Khác | `is_active`, `created_by`, `updated_by`, timestamps, soft delete |

Địa giới dùng `province_code`/`ward_code` (bảng `provinces`/`wards` khoá theo mã), tên tỉnh/phường tra lại ở `BuildOcopSubjectAttributesAction`, không nhận từ client.

`tax_code` unique bỏ qua bản ghi đã soft-delete (enforce ở `OcopSubjectRequest`, không unique ở DB).

## 3. Ảnh & hồ sơ (1-N tệp, Spatie Media)

| Collection | Nội dung | Upload |
|---|---|---|
| `ocop_subject_gallery` | Ảnh cơ sở sản xuất (public, tối đa 10) | FilePond (`X-Context-Type: ocop_subject`) |
| `ocop_subject_business_licenses` | Giấy ĐKKD | `documents[...][]` multiple |
| `ocop_subject_ocop_certs` | Quyết định phê duyệt hạng sao OCOP | như trên |
| `ocop_subject_quality_certs` | ISO / HACCP / VietGAP... | như trên |
| `ocop_subject_food_safety_certs` | Giấy chứng nhận ATTP | như trên |

Hồ sơ lưu disk `local` (private), PDF/JPG/PNG/WEBP, 20MB/tệp, tối đa 10 tệp mỗi loại.

## 4. Luật validate theo loại hình (`OcopSubjectRequest::after()`)

- Hộ kinh doanh: bắt buộc `ocop_star` và ít nhất 1 tệp `ocop_subject_ocop_certs`. Doanh nghiệp/HTX: tuỳ chọn.
- `is_food_business = true`: bắt buộc ít nhất 1 tệp `ocop_subject_food_safety_certs`.
- Khi sửa, số tệp tính = tệp đang có − tệp đánh dấu xoá + tệp mới.
- `ocop_cert_expiry` bắt buộc khi có `ocop_star`.
- `ward_code` phải thuộc `province_code` đã chọn.

## 5. Quan hệ với OCOP

- `ocop_products.ocop_subject_id` → `ocop_subjects.id` (restrictOnDelete). `OcopSubject::products()` HasMany, `OcopProduct::ocopSubject()` BelongsTo.
- Form sản phẩm OCOP: tab "Nhà sản xuất" chỉ còn 1 `<select>` (TomSelect) chọn chủ thể + card Alpine tóm tắt tên/loại hình/MST/địa chỉ. `ocop_subject_id` bắt buộc.
- `producer_name`, `producer_address`, `province_*`, `ward_*` trên `ocop_products` giữ lại làm snapshot (trang công khai, Meilisearch, lọc theo tỉnh), ghi bởi `SyncOcopSubjectSnapshotAction`: khi tạo/sửa sản phẩm, và khi chủ thể đổi tên/địa chỉ.
- Không xoá được chủ thể còn sản phẩm.
- Sản phẩm cũ chưa có `ocop_subject_id` vẫn hiển thị bình thường; lần sửa tiếp theo bắt buộc chọn chủ thể.

## 6. Phân quyền & route

- Permission `ocop_subject.manage` (`PermissionEnum::OCOP_SUBJECT_MANAGE`), cấp cho `platform_ops`, `platform_content_head` qua `OcopSubjectPermissionSeeder`.
- `dashboard/ocop-subjects` (resource, except show) — `backend.ocop-subjects.*`; `backend/api/ocop-subjects` — JSON cho Tabulator.
