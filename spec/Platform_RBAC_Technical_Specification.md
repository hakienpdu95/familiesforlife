# Platform RBAC — Đặc tả Kỹ thuật (Lớp A: Platform Roles)

**Phiên bản:** 1.5 (13/07/2026 — ĐÃ TRIỂN KHAI đầy đủ 6 phase ở §7 trên môi trường dev; xem "Trạng thái triển khai" ngay dưới đây)
**Mục đích:** thống nhất kiến trúc phân quyền cho đội vận hành trung ương (Hà Kiên) trước khi triển khai — chốt lại toàn bộ quyết định đã thảo luận, làm cơ sở duy nhất để code theo.

---

## Trạng thái triển khai (13/07/2026)

Toàn bộ 6 phase ở §7 đã triển khai và verify xong trên môi trường dev (`vigiadinh`):

| Phase | Trạng thái | Bằng chứng |
|---|---|---|
| 1. Prepare | ✅ Xong | Migration + `platform:user-create` + guard `super-admin` + method/Policy `platform_ops`/`platform_viewer` |
| 2. Migration | ✅ Xong | `roles.name` đổi đúng 3 role, không tạo trùng |
| 3. Refactor | ✅ Xong | 12 file bắt buộc + 2 seeder (phát hiện thêm khi rà checklist) đã đổi tên method/role string |
| 4. Test | ✅ Xong | 6 test PHPUnit pass (`Modules/Approval/tests/Feature/`), phát hiện + sửa 2 bug thật (`OrganizationPolicy::view()`, `ProductPolicy::update()` chưa mở cho `isPlatformViewer()`) |
| 5. Verify | ✅ Xong | 8/8 mục Post-deployment Checklist §7b pass |
| 6. Cleanup | ✅ Xong | 11 file comment đã cập nhật; thêm `User::platformRoleLabels()` dùng trong output CLI |

**Phát hiện phụ trong lúc triển khai (ngoài phạm vi §7 gốc, đã sửa luôn vì chặn tiến độ):**
- `phpunit.xml` sai `DB_PASSWORD` — chặn MỌI test trong repo chạy được, không riêng Approval. Đã sửa.
- DB test `minhan` (khai báo sẵn trong `phpunit.xml`) bị stale, thiếu bảng `products`/`approval_subjects` — đã fresh-migrate (an toàn, DB chỉ dùng cho test).

**Chưa làm (đúng theo §8 — ngoài phạm vi, để lại cho sau):** bộ Organization Roles mới, category-scoping, Độc giả VIP, `post.legal_review`, màn hình UI đầy đủ "Quản lý nhân sự Platform".

---
**Tài liệu liên quan:** `spec/Workflow_Approval_Technical_Specification.md` (Platform Approval Gateway hiện có), `spec/RBAC_NewsPortal_Gap_Analysis.md` (vướng mắc đã phát hiện), `spec/table/RBAC_Platform_Operator_Specification.docx` (đề xuất gốc — tài liệu này là bản đã điều chỉnh sau khi đối chiếu với codebase thật)

---

## 0. Quyết định đã chốt

| Chủ đề | Quyết định | Lý do |
|---|---|---|
| Phạm vi đợt này | **Chỉ xây Lớp A — Platform Roles** (đội biên tập/duyệt tin trung ương của Hà Kiên) | Lớp B (role nội bộ từng tổ chức) giữ nguyên 8 role CRM hiện có, để phát triển sau |
| Lớp B — Organization Roles | **Giữ nguyên hoàn toàn** `ceo/sales/ops/marketing/hr/ai_operator/system_admin/viewer` (`app/Enums/RoleEnum.php`) | Nền tảng phục vụ cả CRM/Sales/HR/Workflow, không chỉ báo chí — không thay bằng bộ role toà soạn (`org_reporter`, `org_editor`...) ở đợt này |
| Đổi tên slug role platform | **Đổi** `content_moderator/content_editor/content_head` → `platform_content_moderator/platform_content_editor/platform_content_head`. **KHÔNG đổi** `super-admin` | `content_*` chỉ xuất hiện ở 23 file (xem §3.2), gói gọn 4 module (Approval/Post/Product/Organization) — phạm vi nhỏ, tên hiện tại dễ hiểu nhầm là role nội bộ 1 tổ chức. `super-admin` xuất hiện ở 72 file trải khắp gần mọi module — đổi tên rủi ro cao, lợi ích thấp (tên đã đủ rõ là toàn cục) |
| Đổi tên method trên `User` | **Đổi luôn** `isContentModerator()/isContentEditor()/isContentHead()` → `isPlatformContentModerator()/isPlatformContentEditor()/isPlatformContentHead()` | Xử lý dứt điểm technical debt "method name không khớp role name", không chỉ ghi comment giải thích — vì cùng đợt rename đã phải sửa các file gọi method rồi, đổi thêm tên method không tốn thêm chi phí đáng kể |
| Ràng buộc `super-admin` với `organization_id=null` | **Bổ sung guard tường minh** (xem §3.6) — hiện tại `Gate::before` (`app/Providers/AppServiceProvider.php`) chỉ check `hasRole('super-admin')`, KHÔNG check `organization_id === null` | Xác nhận qua code: không có gì ngăn 1 user thuộc tổ chức bị gán nhầm role `super-admin` rồi bypass luôn tenant scope của chính họ — dù hiện tại chưa có đường nào trong code gán nhầm được (UI tạo user chỉ cho 8 role RoleEnum), vẫn nên có lớp phòng vệ tường minh |
| Bypass kiểm duyệt trung ương | **KHÔNG thêm** cờ cho phép tổ chức tự bật/tắt yêu cầu duyệt trung ương (loại bỏ ý UC-05 trong tài liệu gốc) | Giữ đúng 1 luật duy nhất, áp dụng đều cho Organization/Product/Post: "mọi nội dung đều phải qua Hà Kiên duyệt, không ngoại lệ" — đã chốt và đã verify thật ở §18.1 spec Approval. Thêm ngoại lệ theo từng tổ chức làm luật khó audit hơn mà chưa có nhu cầu nghiệp vụ rõ ràng |
| Cơ chế check quyền | **Giữ nguyên kiểu hiện tại** — Policy gọi thẳng method boolean trên `User` (`isPlatformContentModerator()`...), KHÔNG đổi sang Spatie permission string (`organization.approve`...) | Đã hoạt động đúng, đã verify thật; đổi sang permission string là refactor thêm không bắt buộc, không phục vụ mục tiêu đợt này |
| `platform_content_moderator` duyệt bài viết (Post) | **Cho phép, dạng tuỳ chọn** qua cờ đánh dấu "cần kiểm duyệt pháp lý", không phải bước bắt buộc cho mọi bài | Post vẫn do `platform_content_editor`/`platform_content_head` duyệt chính; pháp lý chỉ tham gia khi được gắn cờ — Phase 2, chưa làm ngay |
| Category-scoping (chuyên mục) | **Hoãn** | Đội biên tập hiện tại quy mô nhỏ, chưa cần độ chi tiết theo chuyên mục — thuộc Lớp B, ngoài phạm vi đợt này |
| Độc giả VIP / subscription cá nhân | **Hoãn**, chỉ ghi nhận là hướng phát triển dài hạn | Chưa có kế hoạch bán gói cá nhân trong ngắn hạn |

---

## 0b. Rủi ro đã biết & Giả định

| # | Rủi ro/giả định | Mức độ | Xử lý |
|---|---|---|---|
| 1 | Log audit lịch sử (`activity_log`) sẽ mãi hiển thị tên role cũ (`content_moderator`...) cho các bản ghi trước ngày rename | Thấp, chấp nhận | Không sửa retroactive — xem §3.2 |
| 2 | `super-admin` hiện không bị ràng buộc cứng `organization_id = null` ở tầng code (chỉ đúng nhờ quy ước vận hành, chưa có guard) | Trung bình | Thêm guard ở §3.6, chưa có DB constraint thật |
| 3 | Chưa có UI quản trị tạo user Platform — chỉ có CLI tạm thời (§3.8) | Trung bình, đã có giải pháp tạm | Artisan command triển khai đợt này, UI đầy đủ hoãn |
| 4 | Giả định: khối lượng user Platform rất nhỏ (dưới 10 người), tần suất tạo mới thấp | — | Nếu giả định sai (cần tạo hàng loạt/thường xuyên), phải làm UI đầy đủ sớm hơn dự kiến |
| 5 | Giả định: chưa cần category-scoping, chưa cần Độc giả VIP trong ngắn hạn (đã chốt ở §0) | — | Nếu business đổi ý, đây là 2 hạng mục chi phí cao nhất, cần lên kế hoạch riêng |
| 6 | Rollback migration (§3.5) bắt buộc đi cùng rollback code — nếu tách rời sẽ gãy | Trung bình nếu quy trình deploy không kiểm soát chặt | Ghi rõ trong runbook deploy + Post-deployment Checklist §7b |

---

## 1. Bối cảnh

Hệ thống hiện dùng chung 1 cơ chế Role (Spatie, team-scoped theo `organization_id`) cho 2 loại đối tượng khác hẳn nhau:

- **Tổ chức Gốc (Platform Operator)** — đội vận hành hệ thống của Hà Kiên. Cần quyền xuyên tổ chức, duyệt tập trung, kiểm soát toàn nền tảng.
- **Tổ chức Doanh nghiệp (Tenant Organization)** — khách hàng đăng ký dùng nền tảng. Chỉ cần quyền nội bộ trong tổ chức của mình.

4 role hiện có (`content_moderator`, `content_editor`, `content_head`, `super-admin` — tên TRƯỚC rename) **đã ngầm đóng vai trò "Platform Roles"** (bắt buộc `organization_id = null`, kiểm tra cứng trong `User::hasGlobalRole()`) — nhưng chưa được đặt tên/tài liệu hoá rõ ràng như 1 lớp riêng biệt. Tài liệu này chính thức hoá lớp đó.

---

## 2. Kiến trúc Dual-Layer RBAC

```
Lớp A — Platform Roles (organization_id = null, xuyên mọi tổ chức)
  ├─ super-admin                      (giữ nguyên tên — bypass toàn cục)
  ├─ platform_content_head            (đổi tên từ content_head)
  ├─ platform_content_editor          (đổi tên từ content_editor)
  ├─ platform_content_moderator       (đổi tên từ content_moderator)
  ├─ platform_ops                     (MỚI)
  └─ platform_viewer                  (MỚI)

Lớp B — Organization Roles (organization_id = của tổ chức, KHÔNG đổi đợt này)
  └─ ceo, sales, ops, marketing, hr, ai_operator, system_admin, viewer
     (app/Enums/RoleEnum.php — giữ nguyên 100%)
```

2 lớp này **không tách bảng DB riêng** — vẫn dùng chung 1 bảng Spatie `roles`, phân biệt bằng quy ước tên (`platform_*` vs tên phẳng hiện có) + kiểm tra `organization_id === null` (`User::hasGlobalRole()`, giữ nguyên cơ chế). Tách bảng thật sẽ phải bỏ Spatie teams — không cần thiết, rủi ro cao.

---

## 3. Lớp A — Platform Roles: chi tiết

### 3.1 Danh sách role

| Role slug | Tên hiển thị | Trạng thái | Trách nhiệm chính |
|---|---|---|---|
| `super-admin` | Super Admin | Có sẵn, không đổi | Bypass toàn bộ gate hệ thống |
| `platform_content_head` | Tổng biên tập | **Đổi tên** từ `content_head` | Duyệt cuối cùng Post (Approved→Published/Archived), xem báo cáo toàn cục |
| `platform_content_editor` | Biên tập viên | **Đổi tên** từ `content_editor` | Duyệt sơ bộ Post (Submitted→Approved) |
| `platform_content_moderator` | Kiểm duyệt viên (Legal) | **Đổi tên** từ `content_moderator` | Duyệt/từ chối Organization & Product; tham gia duyệt pháp lý cho Post khi được gắn cờ (Phase 2, xem §6) |
| `platform_ops` | Vận hành Platform | **Mới** | Quản lý subscription tổ chức, hỗ trợ kỹ thuật, xem log hệ thống — không đụng nội dung biên tập |
| `platform_viewer` | Giám sát / Viewer | **Mới** | Chỉ xem báo cáo/dashboard/trạng thái duyệt, không có quyền thay đổi dữ liệu |

### 3.2 Việc cần làm khi đổi tên (rename `content_*` → `platform_content_*`)

Đã rà soát toàn diện (không chỉ grep tên role trong code) — kiểm tra thêm factory, test suite, cache Spatie, audit log, email/template, dữ liệu production trước khi kết luận phạm vi rename:

| Vùng kiểm tra | Kết quả xác nhận | Việc cần làm |
|---|---|---|
| **Factory** (`database/factories`, `Modules/*/database/factories`) | Không có factory nào tham chiếu 4 role này | Không cần sửa gì |
| **Test suite** | Toàn repo chỉ có 3 file test (`tests/TestCase.php`, `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php` — đều là stub mặc định), không file nào tham chiếu 4 role | Không có test nào bị vỡ, nhưng cũng có nghĩa là **chưa có test nào bảo vệ luồng Approval/Post** — xem thêm §3.7 |
| **Cache Spatie** (`config/permission.php`) | Cache 24h, key `spatie.permission.cache`. Repo **không dùng** `php artisan permission:cache-reset` ở đâu cả — quy ước hiện có là gọi thẳng `PermissionRegistrar::forgetCachedPermissions()` trong seeder/action (15 file đã dùng cách này, gồm chính 2 seeder sắp sửa) | **Bắt buộc** gọi `app(PermissionRegistrar::class)->forgetCachedPermissions()` ngay sau khi update `roles.name` trong migration — update bằng raw SQL hoặc Eloquent mass-update (`Role::where(...)->update()`) đều **không** tự bắn Eloquent model event nên **không** tự flush cache, phải gọi tay |
| **Audit log** (`Modules/User/app/Listeners/LogUserRoleAssigned.php`) | **Rủi ro thật, không sửa được triệt để**: khi gán role, `UserRoleAssigned` event mang `$role` dạng **chuỗi thô** tại thời điểm gán, ghi thẳng vào `activity_log` qua `ActivityLogger::info(...)`. Log lịch sử trước khi rename sẽ **mãi mãi hiển thị tên cũ** (`content_moderator`...) | Chấp nhận như 1 giới hạn đã biết — **không** cố sửa retroactive log cũ (phá tính bất biến của audit trail là sai nguyên tắc hơn việc tên cũ còn xuất hiện trong log lịch sử). Ghi rõ trong changelog/release note để người đọc log không bối rối khi thấy tên cũ ở log trước ngày rename |
| **Email/notification template** | 3 chỗ tìm thấy đều nằm trong **comment Blade** (`{{-- ... --}}`), không phải chuỗi hiển thị thật cho user | Chỉ cần cập nhật comment cho khỏi lạc hậu, không ảnh hưởng chức năng |
| **Dữ liệu production** (`roles` table) | Spatie match role theo `name`+`guard_name` (không phải ID cố định trong code) | Update trực tiếp cột `name` của các row đã tồn tại — xem migration cụ thể ở §3.5, **không tạo role trùng** |

**Danh sách file chính xác — 23 file** (số liệu đã thống nhất xuyên suốt tài liệu này, tính cả chỗ gọi method lẫn chỗ chỉ có chuỗi role/comment):

**Bắt buộc sửa code (12 file — không sửa thì rename vỡ chức năng):**

| File | Việc cần sửa |
|---|---|
| `app/Models/User.php` | Đổi tên 3 method + chuỗi role check bên trong |
| `Modules/Approval/database/seeders/ContentModeratorSeeder.php` | Đổi `'name' => 'content_moderator'` |
| `Modules/Approval/database/seeders/ContentReviewHierarchySeeder.php` | Đổi 2 dòng `Role::firstOrCreate(['name' => 'content_editor'/'content_head', ...])` |
| `Modules/Approval/app/Http/Controllers/ApprovalDashboardController.php` | Đổi lời gọi `$user->isContentModerator()` |
| `Modules/Approval/app/Http/Controllers/ApprovalHistoryController.php` | Đổi lời gọi `$request->user()->isContentModerator()` |
| `Modules/Approval/app/Providers/ApprovalServiceProvider.php` | Đổi lời gọi trong 2 `Gate::define()` (`viewDashboard`, `viewApprovalHistory`) |
| `Modules/Approval/app/Services/ApprovalDashboardService.php` | Đổi lời gọi `$user->isContentModerator()` |
| `Modules/Organization/app/Policies/OrganizationPolicy.php` | Đổi lời gọi `$user->isContentModerator()` |
| `Modules/Post/app/Policies/PostArticlePolicy.php` | Đổi lời gọi `isContentEditor()/isContentHead()` (2 chỗ) |
| `Modules/Post/app/Features/ArticleAuthoring/Http/ArticleAdminController.php` | Đổi lời gọi `isContentEditor()/isContentHead()` |
| `Modules/Product/app/Policies/ProductPolicy.php` | Đổi lời gọi `$user->isContentModerator()` (2 chỗ) |
| `resources/views/layouts/partials/sidebar.blade.php` | Đổi lời gọi `isContentEditor()/isContentHead()` trực tiếp trong Blade |

**Chỉ có comment tham chiếu tên cũ (11 file — KHÔNG bắt buộc để rename chạy đúng, nhưng nên cập nhật cùng đợt để tài liệu khỏi lạc hậu):**

`Modules/Organization/app/Http/Controllers/OrganizationController.php`, `Modules/Organization/app/Providers/OrganizationServiceProvider.php`, `Modules/Organization/resources/views/{edit,show}.blade.php`, `Modules/Organization/routes/web.php`, `Modules/Post/app/Features/ArticleAuthoring/Http/TranslationController.php`, `Modules/Post/app/Features/ArticleAuthoring/Queries/ListPendingReviewTranslationsHandler.php`, `Modules/Post/database/seeders/PostReviewDemoSeeder.php`, `Modules/Post/routes/web.php`, `Modules/Product/app/Features/CatalogManagement/Http/ProductAdminController.php`, `Modules/Product/database/seeders/ProductPermissionSeeder.php`.

**12 + 11 = 23 file. Checklist xác nhận không sót:** sau khi sửa xong, chạy lại đúng lệnh grep đã dùng để lập danh sách này — phải trả về 0 kết quả (trừ các dòng đã chủ động đổi sang tên mới):
```bash
grep -rlE "content_moderator|content_editor|content_head|isContentModerator|isContentEditor|isContentHead" --include="*.php" --include="*.blade.php" app Modules resources
```

### 3.3 2 role mới: `platform_ops`, `platform_viewer` — đặc tả đầy đủ

**`platform_ops` — Vận hành Platform**

| Mục | Chi tiết |
|---|---|
| Method trên `User` | `isPlatformOps(): bool` — cùng khuôn với `isPlatformContentModerator()` (check `hasGlobalRole('platform_ops')`) |
| Ability/Policy | `SubscriptionPolicy` (mới hoặc mở rộng `Modules/Subscription/app/Features/AdminSubscriptions`): cho phép `viewAny`/`assign`/`extend`/`override` subscription của MỌI tổ chức. **Không** có ability nào trên `approve/reject/publishApproval/archiveApproval` của Organization/Product/Post |
| Route đã có sẵn để gắn quyền | `dashboard/subscription/admin/*` (`Modules/Subscription/app/Features/AdminSubscriptions/Http/AdminSubscriptionController.php`) — hiện route này gate bằng permission gì cần rà lại khi triển khai, dự kiến đổi/bổ sung sang check `isPlatformOps()` |
| Menu/sidebar | Mục "Quản lý Subscription" trong sidebar, gate bằng `@if(auth()->user()?->isPlatformOps())` — vị trí đặt cùng nhóm với "Chờ duyệt của tôi"/"Lịch sử duyệt" hiện có |

**`platform_viewer` — Giám sát / Viewer**

| Mục | Chi tiết |
|---|---|
| Method trên `User` | `isPlatformViewer(): bool` |
| Ability/Policy | Chỉ gate `viewDashboard`/`viewApprovalHistory` (2 Gate đã có sẵn ở `ApprovalServiceProvider::boot()`) — **thêm** `isPlatformViewer()` vào OR-condition của 2 Gate này, giống cách `isPlatformContentModerator()` đang được OR vào. **Không** có ability `approve/reject/publishApproval/archiveApproval` nào (chỉ xem, không sửa) |
| Menu/sidebar | Thấy "Chờ duyệt của tôi"/"Lịch sử duyệt" như `platform_content_moderator`, nhưng nút Duyệt/Từ chối/Xuất bản/Lưu trữ trong `edit.blade.php`/`show.blade.php` **phải ẩn** — vì các nút đó đã gate theo đúng `@can('approve', ...)` (Policy), không phải chỉ theo Gate `viewDashboard`, nên tự động đúng mà không cần sửa Blade thêm |
| Lưu ý triển khai | Đây là role **read-only đầu tiên** ở Lớp A — cần test riêng xác nhận không vô tình có quyền ghi nào lọt qua (test §3.7 mục 3) |

### 3.4 Scope bắt buộc — lớp bug đã phát hiện, PHẢI áp dụng cho mọi trang mới

Bất kỳ Controller GET nào mà 1 Platform Role cần xem dữ liệu thuộc về 1 Organization cụ thể **bắt buộc** phải bọc `TenantContext::runForOrganization($org, fn () => view(...)->render())` — gọi `->render()` ngay trong closure, không trả `View` chưa render (xem chi tiết kỹ thuật + lý do ở `ProductAdminController::edit()`, `OrganizationController::show()/edit()` — đã sửa). Đây là lớp bug đã xảy ra thật (badge/nút duyệt ẩn mất với `platform_content_moderator` dù trang tải được), cần checklist lại khi thêm bất kỳ trang platform-facing mới nào (đặc biệt các trang Post: `TranslationController`, `ArticleAdminController`, `ListPendingReviewTranslationsHandler` — spec §18.10 cũ có nhắc xử lý tương tự nhưng **chưa được verify lại bằng thao tác thật** trong đợt kiểm thử này, cần làm khi đụng tới).

### 3.5 Migration + rollback cụ thể (đổi tên role trong DB)

```php
// database/migrations/2026_07_13_xxxxxx_rename_platform_content_roles.php
public function up(): void
{
    $map = [
        'content_moderator' => 'platform_content_moderator',
        'content_editor'    => 'platform_content_editor',
        'content_head'      => 'platform_content_head',
    ];

    foreach ($map as $old => $new) {
        DB::table('roles')->where('name', $old)->where('guard_name', 'web')->update(['name' => $new]);
    }

    // BẮT BUỘC — update bằng query builder không tự bắn Eloquent event nên không tự flush
    // cache Spatie (config/permission.php: cache 24h) — xem §3.2.
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
}

public function down(): void
{
    $map = [
        'platform_content_moderator' => 'content_moderator',
        'platform_content_editor'    => 'content_editor',
        'platform_content_head'      => 'content_head',
    ];

    foreach ($map as $new => $old) {
        DB::table('roles')->where('name', $new)->where('guard_name', 'web')->update(['name' => $old]);
    }

    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
}
```

**Rollback đi kèm bắt buộc:** nếu chạy `down()`, phải deploy lại đồng thời bản code CŨ (method `isContentModerator()`...) — không được chạy `down()` một mình mà giữ code đã đổi tên method mới, vì lúc đó method sẽ tìm role `platform_content_moderator` không còn tồn tại trong DB nữa. Rollback migration + rollback code phải đi cùng nhau trong 1 lần deploy.

### 3.6 Guard tường minh cho `super-admin` (không lơ lửng giữa 2 lớp)

Xác nhận qua code: `Gate::before` (`app/Providers/AppServiceProvider.php`) hiện chỉ check `$user->hasRole('super-admin')`, **không** kiểm tra `organization_id === null`. Hiện tại chưa có đường nào trong code gán nhầm role này cho user thuộc tổ chức (UI tạo user — `Modules/User/app/Data/StoreUserData.php` — chỉ cho chọn 1 trong 8 role của `RoleEnum`, không hề expose `super-admin`), nhưng đây là lớp phòng vệ nên có thêm, không dựa hoàn toàn vào "hiện chưa có đường nào gán nhầm":

- Thêm assertion vào chính seeder tạo tài khoản `super-admin` (`Modules/Auth/database/seeders/AuthDatabaseSeeder.php`): `abort_if($user->organization_id !== null, ...)` trước khi `assignRole('super-admin')`.
- Cân nhắc thêm 1 lệnh artisan kiểm tra định kỳ/CI: liệt kê mọi user có role `super-admin` hoặc `platform_*` mà `organization_id !== null` → cảnh báo nếu có (audit đơn giản, không cần Observer phức tạp).

### 3.7 "Cứng hoá" luật TenantContext thành quy tắc kiến trúc, không chỉ ghi trong spec

Vì hiện chưa có test suite thật cho Approval/Product/Organization (đã xác nhận ở §3.2), quy tắc "mọi GET Controller phục vụ Platform Role phải bọc `TenantContext::runForOrganization()`" hiện chỉ là **văn bản**, không có gì chặn nếu ai đó quên. Đề xuất 2 bước, làm được ngay cả khi chưa có test suite lớn:

1. **Test hồi quy TenantContext** (mới, đặt tại `Modules/Approval/tests/Feature/PlatformRoleCanViewSubjectTest.php` hoặc tương đương): login 1 user role `platform_content_moderator`, GET `dashboard/products/{uuid}/edit` và `dashboard/organizations/{id}` của 1 tổ chức KHÁC, assert response chứa chuỗi `"Duyệt nội dung"` (hoặc badge tương ứng) — test này sẽ đỏ ngay nếu ai đó thêm 1 trang platform-facing mới mà quên bọc TenantContext, đúng lớp bug đã xảy ra thật trong đợt kiểm thử trước.
2. **Test rename không phá vỡ permission** (`RenamedPlatformRolesStillWorkTest.php`): sau khi chạy migration đổi tên (§3.5), login user seed sẵn (`moderator@system.local`) → assert `$user->isPlatformContentModerator() === true` → gọi thật route `approve-content` trên 1 Product đang `pending` → assert 302 + DB status đổi thành `approved` — xác nhận rename không chỉ "đổi chữ" mà luồng thật vẫn chạy đúng end-to-end.
3. **Test `platform_viewer` chỉ xem, không sửa** (`PlatformViewerIsReadOnlyTest.php`): login user role `platform_viewer` → GET `dashboard/organizations/{id}` phải trả 200 và thấy badge trạng thái duyệt → nhưng **không** thấy nút "Duyệt"/"Từ chối"/"Xuất bản"/"Lưu trữ" trong response HTML → và POST thẳng `approve-content` (bỏ qua UI, giả lập cố tình gọi API) phải trả **403** — xác nhận read-only được chặn ở cả 2 tầng (UI ẩn nút + Policy chặn thật), không chỉ ẩn nút mà quyền vẫn lọt.
4. Ghi quy tắc TenantContext thành 1 mục trong CLAUDE.md (checklist review code) — không cần chờ có công cụ phân tích tĩnh riêng.

### 3.8 Use Case tạo user Platform — hiện KHÔNG có đường nào, giải pháp tạm bằng artisan command

Xác nhận qua code: `StoreUserData.php` bắt buộc `organization_id` (non-nullable, `exists:organizations,id`) và `system_role` chỉ nhận 1 trong 8 giá trị `RoleEnum` — **không có UI/API nào tạo được user `organization_id=null` mang role `platform_*`/`super-admin`**. Đường DUY NHẤT hiện nay là 2 seeder hardcode email/password (`moderator@system.local`, `editor@system.local`, `content-head@system.local`).

**Đây là khoảng trống vận hành thật** (không chỉ lý thuyết): nếu Hà Kiên cần thêm 1 biên tập viên platform mới trong lúc vận hành production, hiện tại chỉ có thể SSH vào server và chạy seeder tay/tinker — không có màn hình quản trị nào cho việc này.

**Quyết định:** KHÔNG xây màn hình quản trị đầy đủ trong đợt này (chi phí không nhỏ: route, form, validation, danh sách/sửa/xoá...), nhưng CŨNG không chấp nhận để trống hoàn toàn. **Giải pháp tạm thời — làm ngay trong đợt này:** artisan command `platform:user-create`, đặc tả đầy đủ:

**Xem lại đúng mục đích (quan trọng — thu hẹp phạm vi so với bản trước):** vấn đề cần giải là "không có cách tạo mới **nhân sự biên tập/vận hành** (`platform_content_*`, `platform_ops`, `platform_viewer`)" — **không phải** "cần 1 cách tổng quát để tạo bất kỳ Platform Role nào kể cả `super-admin`". Đưa `super-admin` vào danh sách role được phép của command này mâu thuẫn với chính quyết định ở §0/§3.6 (coi `super-admin` là role nhạy cảm nhất, cần guard riêng) — 1 dòng lệnh CLI không kiểm tra được "ai đang chạy nó" (không có current-user như HTTP request), nên không nên là đường tạo `super-admin` mới. **`super-admin` bị loại khỏi danh sách role được phép** — muốn tạo `super-admin` mới vẫn phải qua `AuthDatabaseSeeder` (thủ công, có review), không qua command này.

**Signature:**
```php
protected $signature = 'platform:user-create
    {email : Email đăng nhập}
    {name : Họ và tên hiển thị}
    {role : platform_content_head|platform_content_editor|platform_content_moderator|platform_ops|platform_viewer — KHÔNG nhận super-admin, xem lý do ở trên}
    {--password= : Mật khẩu — bỏ trống sẽ tự sinh ngẫu nhiên và in ra màn hình}';
```

**Class đầy đủ (dự kiến `Modules/Approval/app/Console/Commands/CreatePlatformUserCommand.php`):**

```php
<?php

namespace Modules\Approval\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\ActivityLog\Core\ActivityLogger;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Giải pháp tạm thời thay UI quản trị "Quản lý nhân sự Platform" (chưa xây, xem
 * spec/Platform_RBAC_Technical_Specification.md §3.8) — CHỈ để tạo nhân sự biên tập/vận
 * hành (platform_content_*/platform_ops/platform_viewer), có audit log, thay vì chạy tinker
 * tay không để lại vết.
 *
 * CỐ Ý KHÔNG cho tạo `super-admin` qua đây — role đó nhạy cảm nhất (§0/§3.6, bypass toàn
 * cục), trong khi command CLI không kiểm tra được "ai đang chạy nó". Cần `super-admin` mới
 * thì đi qua `Modules/Auth/database/seeders/AuthDatabaseSeeder.php` (thủ công, có review),
 * không qua tool tự động này.
 */
class CreatePlatformUserCommand extends Command
{
    protected $signature = 'platform:user-create
        {email : Email đăng nhập}
        {name : Họ và tên hiển thị}
        {role : platform_content_head|platform_content_editor|platform_content_moderator|platform_ops|platform_viewer}
        {--password= : Mật khẩu — bỏ trống sẽ tự sinh ngẫu nhiên và in ra màn hình}';

    protected $description = 'Tạo tài khoản nhân sự biên tập/vận hành Platform (organization_id=null) — không tạo được super-admin';

    private const ALLOWED_ROLES = [
        'platform_content_head',
        'platform_content_editor',
        'platform_content_moderator',
        'platform_ops',
        'platform_viewer',
    ];

    public function handle(): int
    {
        $email    = $this->argument('email');
        $name     = $this->argument('name');
        $role     = $this->argument('role');
        $password = $this->option('password') ?: Str::random(16);

        $validator = Validator::make(compact('email', 'name', 'role'), [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'name'  => ['required', 'string', 'max:255'],
            'role'  => ['required', 'in:' . implode(',', self::ALLOWED_ROLES)],
        ]);

        if ($validator->fails()) {
            $this->error('Dữ liệu không hợp lệ:');
            foreach ($validator->errors()->all() as $message) {
                $this->line("  - {$message}");
            }
            return self::FAILURE;
        }

        if (! Role::where('name', $role)->where('guard_name', 'web')->exists()) {
            $this->error("Role \"{$role}\" chưa tồn tại trong bảng roles — chạy seeder tương ứng trước.");
            return self::FAILURE;
        }

        $user = User::create([
            'name'              => $name,
            'email'             => $email,
            'password'          => Hash::make($password),
            'organization_id'   => null,
            'email_verified_at' => now(),
        ]);

        // Platform role KHÔNG team-scoped — setPermissionsTeamId(null) tường minh trước khi
        // gán, đúng quy ước ContentModeratorSeeder đang dùng (không dựa vào ambient context).
        setPermissionsTeamId(null);
        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        ActivityLogger::info('User', 'platform_user_created', $user, [
            'email'       => $email,
            'role'        => $role,
            'created_via' => 'artisan platform:user-create',
        ]);

        $this->info("✓ Đã tạo user Platform: {$email} (role: {$role}).");

        if (! $this->option('password')) {
            $this->warn("Mật khẩu tự sinh — đổi ngay sau khi đăng nhập lần đầu: {$password}");
        }

        return self::SUCCESS;
    }
}
```

**Ví dụ chạy:**
```bash
php artisan platform:user-create editor2@hakien.internal "Trần Thị B" platform_content_editor --password="TempPass@2026"
```

**Output thành công:**
```
✓ Đã tạo user Platform: editor2@hakien.internal (role: platform_content_editor).
```

**Output lỗi (email trùng):**
```
Dữ liệu không hợp lệ:
  - The email has already been taken.
```

**Output lỗi (role không hợp lệ — kể cả role Lớp B như `ceo` LẪN `super-admin`, cả 2 đều bị từ chối):**
```
Dữ liệu không hợp lệ:
  - The selected role is invalid.
```

**UC-01 (đầy đủ, đánh dấu rõ "Phase sau" cho phần UI, "Đợt này" cho phần CLI):**
> Actor: người vận hành có quyền truy cập server (Đợt này) / `super-admin` qua màn hình quản trị (Phase sau). Chạy `platform:user-create` (Đợt này, chỉ 5 role biên tập/vận hành) hoặc vào "Quản lý nhân sự Platform" — route/controller mới, ngoài phạm vi `Modules/User` hiện có vì đó đang ràng buộc `organization_id` bắt buộc (Phase sau) → tạo user mới với `organization_id = null` → gán 1 trong 5 Platform Role không tính `super-admin` (§3.1) → user có thể đăng nhập và thao tác đúng theo role được gán. Riêng nhu cầu tạo thêm `super-admin` KHÔNG nằm trong UC này — luôn đi qua `AuthDatabaseSeeder`, dù ở Đợt này hay Phase sau.

Command CLI này **nằm trong phạm vi triển khai đợt này** (xem §7, Phase Prepare) — chỉ riêng màn hình UI đầy đủ mới hoãn sang phase sau. Việc "ai được phép chạy lệnh này" vẫn phụ thuộc vào quyền truy cập server (SSH/deploy pipeline, không có current-user để kiểm tra ở tầng CLI) — nhưng vì đã loại `super-admin` khỏi phạm vi, rủi ro cao nhất (mint 1 tài khoản bypass toàn cục chỉ bằng 1 dòng lệnh) đã được loại bỏ; rủi ro còn lại (tạo nhầm/thừa tài khoản biên tập) thấp hơn nhiều và chấp nhận được.

---

## 4. Lớp B — Organization Roles (không đổi đợt này)

Giữ nguyên 100% `app/Enums/RoleEnum.php` (`ceo, sales, ops, marketing, hr, ai_operator, system_admin, viewer`) và toàn bộ permission domain hiện có (`post_article.*`, `product.*`...). Không tạo `org_owner/org_reporter/org_section_editor/...`. Việc này để dành cho lộ trình phát triển sau, khi có nhu cầu thật (đội biên tập nội bộ từng tổ chức tăng quy mô).

---

## 5. Ma trận phân quyền (Lớp A, đầy đủ)

| Hành động | `super-admin` | `platform_content_head` | `platform_content_editor` | `platform_content_moderator` | `platform_ops` | `platform_viewer` |
|---|---|---|---|---|---|---|
| Duyệt Organization/Product | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Duyệt bài viết (mọi tổ chức) | ✅ | ✅ | ✅ | ✅ (chỉ khi gắn cờ legal, §6) | ❌ | ❌ |
| Xuất bản/Gỡ bài | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Xem "Chờ duyệt của tôi" / "Lịch sử duyệt" | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| Xem báo cáo toàn cục | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| Subscription — xem (`viewAny`, mọi tổ chức) | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Subscription — gán gói mới (`assign`) | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Subscription — gia hạn (`extend`) | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Subscription — ghi đè thủ công (`override`) | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Xem log hệ thống / hỗ trợ kỹ thuật | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |

*(Không có hàng "tổ chức tự bypass duyệt" — đã loại bỏ theo quyết định §0. 4 hàng Subscription tách theo đúng độ chi tiết ability đã đặc tả ở §3.3, thay vì gộp chung 1 hàng.)*

**Ghi chú riêng cho việc tạo user Platform (không đưa vào bảng vì không phải ability theo role — xem §3.8):** tạo `super-admin` mới luôn đi qua `AuthDatabaseSeeder` (thủ công, có review), **không role nào kể cả `super-admin`** tạo được `super-admin` khác qua `platform:user-create`. Tạo 5 role còn lại (`platform_content_*`/`platform_ops`/`platform_viewer`) qua `platform:user-create` không kiểm tra theo role vì CLI không có current-user — giới hạn thực tế nằm ở quyền truy cập server, không phải Policy trong ứng dụng.

---

## 6. `platform_content_moderator` tham gia duyệt bài viết — Phase 2, chưa làm ngay

Thiết kế dự kiến (chưa triển khai): thêm khả năng `platform_content_editor`/`platform_content_head` đánh dấu 1 `PostArticleTranslation` là "cần kiểm duyệt pháp lý" → xuất hiện trong hàng đợi riêng của `platform_content_moderator` → duyệt/từ chối độc lập với luồng biên tập thường. Không đánh dấu thì bỏ qua bước này hoàn toàn (không bắt buộc cho mọi bài). Cần thiết kế thêm: cột/trạng thái đánh dấu, Policy mới, UI hàng đợi riêng — **ngoài phạm vi triển khai đợt này**.

---

## 7. Kế hoạch triển khai (đợt này — chỉ Lớp A), nhóm theo phase

**Lưu ý thứ tự:** Migration (đổi `roles.name` trong DB) và Refactor (đổi code gọi theo tên mới) phải **cùng nằm trong 1 lần deploy** — không thể tách deploy migration riêng rồi deploy code riêng sau, vì lúc đó sẽ có 1 khoảng thời gian code cũ gọi tên role đã không còn tồn tại trong DB. Chia phase dưới đây phục vụ mục đích **tổ chức sprint/review code**, không phải trình tự chạy tách rời trên production.

### Phase 1 — Prepare (viết code, chưa chạy migration thật)
1. Viết migration đổi tên `roles.name` theo §3.5 (kèm `forgetCachedPermissions()`, kèm `down()` rollback) — **chưa chạy**.
2. Viết artisan command `platform:user-create` (§3.8) đầy đủ theo class mẫu đã cho.
3. Thêm guard `organization_id === null` cho `super-admin` theo §3.6.
4. Viết code 2 role mới `platform_ops`, `platform_viewer` — method/Policy/menu theo §3.3.

### Phase 2 — Migration (deploy)
5. Chạy migration §3.5 trên môi trường dev → staging → production (theo đúng quy trình deploy hiện có).

### Phase 3 — Refactor (chỉ phần bắt buộc để rename chạy đúng, đi cùng 1 release với Phase 2)
6. Đổi tên method `User::isContentModerator()/isContentEditor()/isContentHead()` → `isPlatformContentModerator()/isPlatformContentEditor()/isPlatformContentHead()`.
7. Cập nhật 2 seeder (`ContentModeratorSeeder`, `ContentReviewHierarchySeeder`) dùng tên role mới.
8. Sửa 12 file bắt buộc đã liệt kê ở §3.2 — đổi lời gọi method theo tên mới.

### Phase 4 — Test
9. Viết 3 test theo §3.7 (TenantContext hồi quy, rename không phá permission, `platform_viewer` read-only).
10. Test lại toàn bộ luồng đã verify trước đó (đăng ký org → auto pending → moderator duyệt → publish → sửa nội dung → revise) với tên role mới, xác nhận không có gì gãy.

### Phase 5 — Verify
11. Chạy Post-deployment Checklist §7b trên production (đã gồm sẵn bước grep xác nhận 0 sót tên cũ — không lặp lại riêng ở đây).

### Phase 6 — Cleanup (không blocking release, làm sau khi Phase 5 xanh)
12. Cập nhật 11 file chỉ có comment (§3.2) cho đồng bộ tài liệu.
13. Cập nhật label hiển thị (Blade/dashboard) theo tên tiếng Việt ở bảng §3.1.
14. Ghi nhận vào backlog — xem danh sách đầy đủ ở §8 (không lặp lại ở đây): `post.legal_review` cho `platform_content_moderator`, màn hình UI đầy đủ cho UC-01.

---

## 7b. Post-deployment Checklist (chạy trên production ngay sau khi deploy)

- [ ] Chạy `php artisan permission:cache-reset` (hoặc xác nhận migration đã tự gọi `forgetCachedPermissions()`) — không tin tưởng cache tự hết hạn trong lúc theo dõi.
- [ ] Đăng nhập thật bằng 3 tài khoản seed sẵn (`moderator@system.local`, `editor@system.local`, `content-head@system.local`) — xác nhận vẫn login được, dashboard "Chờ duyệt của tôi"/"Lịch sử duyệt" vẫn hiển thị đúng.
- [ ] Thao tác thật 1 vòng Approve→Publish trên 1 Organization hoặc Product thật (không phải dữ liệu test) — xác nhận luồng vẫn chạy đúng sau rename.
- [ ] Chạy lại lệnh grep checklist ở §3.2 trên **bản code đã deploy thật** (không chỉ trên máy dev) — 0 kết quả.
- [ ] Kiểm tra `roles` table trên production: đúng 3 row đã đổi tên (`platform_content_moderator/editor/head`), không có row trùng/rác.
- [ ] Theo dõi `storage/logs/laravel.log` (hoặc kênh log tập trung) trong **24h đầu** sau deploy — tìm lỗi dạng "role not found"/"call to undefined method" (dấu hiệu còn sót chỗ chưa sửa).
- [ ] Xác nhận guard `super-admin` (§3.6) không chặn nhầm tài khoản `super-admin` thật đang dùng hiện tại (chạy audit command trước khi deploy, không phải sau).
- [ ] Test thử `platform:user-create` trên staging (không phải production) 1 lần trước khi coi là "sẵn sàng dùng thật".

---

## 8. Ngoài phạm vi đợt này (ghi nhận, không triển khai)

- Bộ Organization Roles mới cho toà soạn (`org_owner/org_reporter/org_section_editor/org_editor/org_media/org_viewer`).
- Category-scoping (chuyên mục dùng chung toàn nền tảng + gán biên tập viên theo chuyên mục).
- Độc giả VIP / subscription cá nhân (B2C).
- Cờ cho phép tổ chức tự bypass kiểm duyệt trung ương (đã quyết định KHÔNG làm — xem §0).
- Màn hình UI đầy đủ "Quản lý nhân sự Platform" (§3.8) — thay thế cho `platform:user-create`.
- `post.legal_review` cho `platform_content_moderator` tham gia duyệt bài viết (§6).
