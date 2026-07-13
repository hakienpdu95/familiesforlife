# Platform RBAC — Đặc tả Phase 2 (Phần còn lại ngoài phạm vi §8)

**Phiên bản:** 2.0 (13/07/2026 — SỬA HIỂU SAI CĂN BẢN ở §3: `Modules/Post` là tài sản của NỀN TẢNG, không phải của từng doanh nghiệp. 7 role toà soạn thuộc Lớp A, không phải Lớp B mới như bản 1.2 từng viết. Rà lại chỉ còn đúng 2 role thật sự mới (`platform_reporter`, `platform_media`) + 1 role chờ category-scoping (`platform_section_editor`); 3 role còn lại trùng role Platform đã có sẵn. §4 đổi từ "category theo tổ chức" sang "category dùng chung toàn nền tảng". Thêm yêu cầu thu hồi quyền Post khỏi mọi role Lớp B — còn 2 câu hỏi mở cần xác nhận (giữ `post_article.view` cho doanh nghiệp hay không; tên tổ chức placeholder cho tin tức không gắn với doanh nghiệp cụ thể).

## Trạng thái triển khai §2

✅ Đã code + verify đầy đủ trên dev: `PlatformUserController` (index/create/store/edit/update/destroy), `StorePlatformUserData`/`UpdatePlatformUserData`, 3 Blade view, route `backend.platform-users.*`, mục sidebar gate `super-admin`, 5 test PHPUnit (`PlatformUserAdminTest.php`, chạy cùng bộ 6 test cũ = 11 test pass).

Đã verify qua HTTP thật: tạo user Platform mới qua form → login được, đổi role qua form (`syncRoles`), vô hiệu hoá KHÔNG xoá cứng (`is_active=false`, record vẫn còn), non-super-admin bị 403, form từ chối tạo `super-admin`, không tự vô hiệu hoá được chính mình.
**Mục đích:** đặc tả 5 hạng mục đã ghi nhận là "ngoài phạm vi" ở `spec/Platform_RBAC_Technical_Specification.md` §8, để thống nhất nội dung trước khi triển khai từng phần theo đúng mức độ ưu tiên.
**Tài liệu liên quan:** `spec/Platform_RBAC_Technical_Specification.md` (đã triển khai xong Lớp A — xem "Trạng thái triển khai" trong đó).

---

## 0. Quyết định đã chốt

| Chủ đề | Quyết định | Lý do |
|---|---|---|
| Thứ tự ưu tiên | **UI Quản trị Platform triển khai NGAY** (§2). 4 hạng mục còn lại (§3-§6) **chỉ đặc tả, chưa code** | Đã xác nhận: hiện KHÔNG có màn hình quản trị nào cho user Platform (`organization_id=null`) — chỉ có CLI `platform:user-create` (giải pháp tạm, spec cũ §3.8/§3.9). Đây là khoảng trống vận hành thật cần lấp ngay, không thể để "luôn chỉ có CLI" |
| Nguyên tắc kiến trúc cho MỌI bộ role (đã chốt, áp dụng xuyên suốt) | **Role của hệ thống vận hành nền tảng (Lớp A) phải tách biệt và độc lập hoàn toàn với role của tài khoản doanh nghiệp (Lớp B)** — không xung đột, không phụ thuộc lẫn nhau. Mọi bộ role mới (kể cả `org_*` ở §3) đều phải tuân theo nguyên tắc này | Đây chính xác là nguyên tắc Dual-Layer đã áp dụng và verify xong cho Lớp A (`platform_*`, `organization_id=null`, tách biệt hoàn toàn khỏi 8 role CRM `organization_id`=tổ chức — xem `spec/Platform_RBAC_Technical_Specification.md` §2). Quyết định của người dùng: áp dụng CÙNG nguyên tắc này khi mở rộng Lớp B (§3), để dễ quản lý, không lặp lại rủi ro xung đột đã tránh được ở Lớp A |
| §3.2 câu hỏi 1 — thêm mới hay thay thế 8 role CRM? | **Đã chốt: THÊM MỚI, độc lập hoàn toàn, KHÔNG thay thế** — xem §3.2 đã cập nhật | Đúng nguyên tắc trên: 2 bộ role (CRM hiện có vs `org_*` mới) phải là 2 tập độc lập, không xung đột, 1 user có thể giữ đồng thời role ở cả 2 bộ nếu cần (Spatie hỗ trợ multi-role) mà không ảnh hưởng lẫn nhau |
| Vị trí code UI Platform | Đặt trong `Modules/Approval` (không tạo module mới) | Nhất quán với `CreatePlatformUserCommand`/`AuditPlatformRoleScopeCommand` đã đặt ở đây — toàn bộ hạ tầng Platform Role đã tập trung 1 chỗ |
| Không tái dùng `Modules/User/UserController` | Viết controller riêng cho Platform user | Xác nhận qua code: `UserController` gắn chặt team-scoping (`setPermissionsTeamId($organization_id)` xuyên suốt `resolveUserRole()`/`getOrganizationsFor()`) — không áp dụng được cho user `organization_id=null` |
| `super-admin` vẫn KHÔNG tạo được qua UI mới | Giữ nguyên quyết định ở CLI (spec cũ §3.8) | UI mới chỉ là "màn hình hoá" cho đúng use case CLI đang giải quyết (5 role biên tập/vận hành), không mở rộng thêm phạm vi rủi ro |
| 4 hạng mục còn lại (Organization Roles Lớp B, category-scoping, Độc giả VIP, `post.legal_review`) | **Chỉ đặc tả** (§3-§6), triển khai khi có yêu cầu "làm phase X" cụ thể | Đúng mức độ chín muồi nghiệp vụ hiện tại — chưa có xác nhận business cho từng hạng mục, tránh code thừa |

---

## 1. Giới thiệu

`spec/Platform_RBAC_Technical_Specification.md` đã triển khai xong Lớp A (6 Platform Role, rename, 2 role mới, test, verify). §8 của tài liệu đó liệt kê 5 hạng mục "ngoài phạm vi đợt này":

1. Bộ Organization Roles mới cho toà soạn (Lớp B) — *đã sửa lại ở §3 v2.0: thực ra là Lớp A, xem §3.0.*
2. Category-scoping.
3. Độc giả VIP / subscription cá nhân (B2C).
4. `post.legal_review` cho `platform_content_moderator`.
5. Màn hình UI đầy đủ "Quản lý nhân sự Platform".

Tài liệu này đặc tả cả 5, nhưng **chỉ triển khai ngay hạng mục 5** theo yêu cầu — vì đây là khoảng trống vận hành có thật (không phải tính năng mới chưa cần), 4 hạng mục còn lại vẫn đang ở dạng ý tưởng chưa xác nhận nhu cầu nghiệp vụ cụ thể.

---

## 2. UI Quản trị Platform — TRIỂN KHAI NGAY

### 2.1 Vị trí & kiến trúc

- Controller: `Modules/Approval/app/Http/Controllers/PlatformUserController.php`
- Data: `Modules/Approval/app/Data/StorePlatformUserData.php`, `UpdatePlatformUserData.php` (Spatie Laravel-Data, cùng convention với `Modules/User/app/Data/StoreUserData.php`)
- View: `Modules/Approval/resources/views/platform-users/{index,create,edit}.blade.php`
- Routes: thêm vào `Modules/Approval/routes/web.php` (cùng file với `dashboard/approvals`)

### 2.2 Routes

```php
// Modules/Approval/routes/web.php — thêm vào group hiện có
Route::middleware(['auth'])
    ->prefix('dashboard')
    ->name('backend.')
    ->group(function () {
        Route::resource('platform-users', PlatformUserController::class)->except(['show']);
    });
```

Route name: `backend.platform-users.{index,create,store,edit,update,destroy}` — đúng convention `backend.users.*` hiện có cho Lớp B.

### 2.3 Gate/Policy

Chỉ `super-admin` được truy cập toàn bộ (mọi thao tác) — không có ai khác. Không dùng `authorizeResource()` (đó là convention cho model có Policy riêng theo resource thật); thay vào đó check thẳng trong constructor, giống mức độ đơn giản của vấn đề (không cần Policy class riêng cho 1 gate boolean duy nhất):

```php
public function __construct()
{
    $this->middleware(function ($request, $next) {
        abort_unless($request->user()?->hasRole('super-admin'), 403);
        return $next($request);
    });
}
```

`super-admin` tự bypass Gate::before nên dùng `hasRole()` trực tiếp ở đây là đủ (không cần Gate riêng).

### 2.4 `StorePlatformUserData` — validation

```php
namespace Modules\Approval\Data;

use App\Models\User;
use Spatie\LaravelData\Attributes\Validation\{Email, In, Max, Required, Unique};
use Spatie\LaravelData\Data;

class StorePlatformUserData extends Data
{
    public function __construct(
        #[Required, Max(255)]
        public string $name,

        #[Required, Email, Max(255), Unique('users', 'email')]
        public string $email,

        #[Required, In([
            'platform_content_head',
            'platform_content_editor',
            'platform_content_moderator',
            'platform_ops',
            'platform_viewer',
        ])] // CỐ Ý không có 'super-admin' — giữ nguyên quyết định ở CLI (spec cũ §3.8)
        public string $role,

        #[Required, Max(255)]
        public string $password,
    ) {}
}
```

`UpdatePlatformUserData` — chỉ cho đổi `name`/`role`, KHÔNG cho đổi `email` (giữ nguyên convention `UpdateUserData` không cho đổi định danh), không có `password` (đổi mật khẩu là luồng riêng, không gộp vào form sửa role).

### 2.5 Controller — action chính

```php
namespace Modules\Approval\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Approval\Data\{StorePlatformUserData, UpdatePlatformUserData};
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PlatformUserController extends Controller
{
    public function __construct()
    {
        $this->middleware(fn ($request, $next) => tap($next($request), fn () =>
            abort_unless($request->user()?->hasRole('super-admin'), 403)
        ));
        // (viết đúng thành middleware closure abort TRƯỚC khi vào action — xem §2.3,
        // ví dụ rút gọn ở đây chỉ minh hoạ vị trí, code thật đặt abort trước $next()).
    }

    public function index()
    {
        $users = User::withoutGlobalScopes()
            ->whereNull('organization_id')
            ->with('roles:id,name')
            ->orderBy('name')
            ->paginate(20);

        return view('approval::platform-users.index', [
            'users'  => $users,
            'labels' => User::platformRoleLabels(),
        ]);
    }

    public function create()
    {
        return view('approval::platform-users.create', [
            'labels' => collect(User::platformRoleLabels())->except('super-admin'),
        ]);
    }

    public function store(StorePlatformUserData $data): RedirectResponse
    {
        if (! Role::where('name', $data->role)->where('guard_name', 'web')->exists()) {
            return back()->withInput()->withErrors(['role' => "Role \"{$data->role}\" chưa tồn tại — chạy seeder tương ứng trước."]);
        }

        $user = new User();
        $user->forceFill([
            'name'              => $data->name,
            'email'             => $data->email,
            'password'          => Hash::make($data->password),
            'organization_id'   => null,
            'email_verified_at' => now(),
        ])->save();

        setPermissionsTeamId(null);
        $user->assignRole($data->role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        ActivityLogger::info('User', 'platform_user_created', $user, [
            'email' => $data->email, 'role' => $data->role, 'created_via' => 'admin_ui',
        ]);

        return redirect()->route('backend.platform-users.index')
            ->with('success', "Đã tạo user Platform: {$data->email}.");
    }

    public function edit(User $platformUser)
    {
        abort_if($platformUser->organization_id !== null, 404); // không cho sửa user Lớp B qua đây

        return view('approval::platform-users.edit', [
            'platformUser' => $platformUser,
            'labels'       => collect(User::platformRoleLabels())->except('super-admin'),
        ]);
    }

    public function update(UpdatePlatformUserData $data, User $platformUser): RedirectResponse
    {
        abort_if($platformUser->organization_id !== null, 404);
        abort_if($platformUser->hasRole('super-admin'), 403); // không đổi role của chính super-admin qua đây

        $platformUser->update(['name' => $data->name]);

        setPermissionsTeamId(null);
        $platformUser->syncRoles([$data->role]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        ActivityLogger::info('User', 'platform_user_role_changed', $platformUser, ['role' => $data->role]);

        return redirect()->route('backend.platform-users.index')->with('success', 'Đã cập nhật.');
    }

    public function destroy(User $platformUser): RedirectResponse
    {
        abort_if($platformUser->organization_id !== null, 404);
        abort_if($platformUser->hasRole('super-admin'), 403);
        abort_if($platformUser->id === auth()->id(), 403); // không tự xoá chính mình — cùng convention UserPolicy

        $platformUser->update(['is_active' => false]); // vô hiệu hoá, KHÔNG xoá cứng — giữ audit trail

        ActivityLogger::info('User', 'platform_user_deactivated', $platformUser);

        return redirect()->route('backend.platform-users.index')->with('success', 'Đã vô hiệu hoá tài khoản.');
    }
}
```

**Lưu ý quan trọng khi code thật (không phải giả định):** `destroy()` không xoá cứng — đổi `is_active=false` (đã có sẵn cột này, dùng bởi `EnsureUserIsActive` middleware — xem lỗi `MissingAttributeException` đã gặp ở Phase 4 test, xác nhận cột này tồn tại và được kiểm tra thật khi login).

### 2.6 Blade — mức tối thiểu, mirror `Modules/User/resources/views/index.blade.php`

Không cần bê nguyên cấu trúc Tabulator/Alpine phức tạp của `index.blade.php` gốc (đó là cho danh sách lớn nhiều cột lọc theo tổ chức — không áp dụng vì Platform user luôn rất ít, giả định #4 ở spec cũ §0b). Bảng tĩnh đơn giản đủ dùng:

```blade
{{-- Modules/Approval/resources/views/platform-users/index.blade.php --}}
@extends('layouts.backend')
@section('title', 'Quản lý nhân sự Platform')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-base-content">Quản lý nhân sự Platform</h1>
    <a href="{{ route('backend.platform-users.create') }}" class="btn btn-primary btn-sm">Thêm nhân sự</a>
</div>

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-0">
        <table class="table table-sm">
            <thead>
                <tr class="text-xs text-base-content/40 uppercase">
                    <th>Tên</th><th>Email</th><th>Vai trò</th><th>Trạng thái</th><th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $u)
                <tr class="hover">
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                    <td>{{ $labels[$u->roles->first()?->name] ?? $u->roles->first()?->name ?? '—' }}</td>
                    <td>
                        @if ($u->is_active)
                            <span class="badge badge-success badge-sm">Hoạt động</span>
                        @else
                            <span class="badge badge-ghost badge-sm">Đã vô hiệu hoá</span>
                        @endif
                    </td>
                    <td>
                        @unless ($u->hasRole('super-admin'))
                        <a href="{{ route('backend.platform-users.edit', $u) }}" class="btn btn-ghost btn-xs">Sửa</a>
                        @endunless
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
{{ $users->links() }}
@endsection
```

`create.blade.php`/`edit.blade.php` — form đơn giản (name/email/password cho create; name/role cho edit), dropdown role dùng `$labels` (đã loại `super-admin`).

### 2.7 Sidebar

Thêm mục "Quản lý nhân sự Platform" trong `resources/views/layouts/partials/sidebar.blade.php`, gate bằng `@can('hasRole', ...)` — thực tế đơn giản hơn: `@if(auth()->user()?->hasRole('super-admin'))`, đặt cạnh "Chờ duyệt của tôi"/"Lịch sử duyệt".

### 2.8 Test cần viết (theo đúng convention `Modules/Approval/tests/Feature/` đã có)

`PlatformUserAdminTest.php`:
- `super-admin` tạo được user Platform mới qua form (POST store) → assert role gán đúng, login được.
- User KHÔNG phải `super-admin` (kể cả `platform_ops`) bị 403 khi vào `/dashboard/platform-users`.
- Không tạo được user với role `super-admin` qua form (validation reject).
- `destroy()` không xoá cứng — chỉ `is_active=false`, user vẫn còn trong DB.
- Không tự xoá được chính mình.

---

## 3. Đội biên tập nội dung Platform (mở rộng Lớp A) — ĐẶC TẢ, CHƯA TRIỂN KHAI

### 3.0 Chẩn đoán lại — đã sửa hiểu sai so với bản v1.2

**Bản v1.2 (trước đó) hiểu SAI:** coi 7 role toà soạn (Tổng biên tập, Biên tập viên, Phóng viên...) là role **Lớp B** — tức role riêng của TỪNG doanh nghiệp, mỗi tổ chức tự có đội biên tập của mình. **Đã xác nhận lại: SAI.**

**Đúng theo xác nhận của người dùng:** `Modules/Post` là **tài sản của nền tảng** — do chính Hà Kiên quản lý/vận hành toàn bộ nội dung, **tài khoản doanh nghiệp không có quyền thao tác** (tạo/sửa/xoá/duyệt bài) trên module này nữa. Toàn bộ 7 role toà soạn vì vậy thuộc **Lớp A (Platform)**, mở rộng thêm cho 6 Platform Role đã có (`super-admin`, `platform_content_head`, `platform_content_editor`, `platform_content_moderator`, `platform_ops`, `platform_viewer`) — không phải 1 bộ role Lớp B mới.

### 3.1 Rà lại — chỉ cần 2 role MỚI, không phải 7

Đối chiếu 7 role dự kiến (từ `RBAC_Platform_Operator_Specification.docx`) với 6 Platform Role **đã có sẵn**:

| Role dự kiến (docx) | Đối chiếu với Platform Role đã có | Kết luận |
|---|---|---|
| Tổng biên tập | = `platform_content_head` (đã có, duyệt cuối + xuất bản) | Không tạo mới — dùng lại |
| Biên tập viên | = `platform_content_editor` (đã có, duyệt sơ bộ) | Không tạo mới — dùng lại |
| Biên tập viên trưởng chuyên mục | Gần giống `platform_content_editor` nhưng **giới hạn theo 1 vài chuyên mục** — chưa có tương đương | **Role mới**: `platform_section_editor` — phụ thuộc §4 (category dùng chung toàn nền tảng) |
| Phóng viên | Hiện KHÔNG có role Platform nào tạo được bài viết (chỉ có role DUYỆT) — trước đây việc "tạo bài" là của `marketing` (Lớp B), giờ bị thu hồi (xem §3.2) | **Role mới**: `platform_reporter` — tạo/sửa bài, gửi duyệt |
| Nhiếp ảnh/Video | Chưa có | **Role mới**: `platform_media` — chỉ upload media (`post_media.upload`, permission mới) |
| Cộng tác viên xem | = `platform_viewer` (đã có, chỉ xem) | Không tạo mới — mở rộng phạm vi xem sang cả Post |
| Chủ sở hữu/CEO | Không áp dụng — đây là khái niệm của DOANH NGHIỆP (`ceo`, Lớp B), không liên quan gì tới việc ai viết/duyệt bài trên nền tảng | **Bỏ hẳn** khỏi danh sách — nhầm lẫn từ bản đọc docx gốc trước đó |

**Tóm lại: chỉ cần dựng mới `platform_reporter` và `platform_media` ngay** (không phụ thuộc gì); `platform_section_editor` chờ §4.

### 3.2 Permission — thu hồi quyền của Lớp B trên Post, cấp lại cho Lớp A

Xác nhận qua code (`Modules/Post/database/seeders/PostPermissionSeeder.php`): hiện **6/8 role Lớp B** đang có quyền trên `post_article.*` — `marketing` (create/edit/delete/manage_sponsorship), `ceo` (publish/unpublish/manage_sponsorship), `ops` (publish), `system_admin` (create + các quyền khác), `sales`/`hr`/`ai_operator`/`viewer` (chỉ view). Theo đúng chỉ đạo "doanh nghiệp không có quyền thao tác":

- **Thu hồi toàn bộ quyền GHI** (`create/edit/delete/publish/unpublish/manage_sponsorship`) khỏi **mọi role Lớp B**, không chỉ `marketing`.
- **`post_article.view` — cần bạn xác nhận thêm:** giữ lại cho doanh nghiệp xem bài viết (kể cả bài sponsor về chính họ), hay thu hồi luôn (doanh nghiệp không thấy Post ở dashboard nữa, chỉ xem qua cổng thông tin công khai như độc giả bình thường)? **Câu hỏi mở — chưa chốt.**
- Cấp `post_article.create/edit/delete` cho `platform_reporter`; giữ nguyên `post_article.publish/unpublish` chỉ `platform_content_head` (đã đúng từ trước — không đổi).
- Permission mới `post_media.upload` — cấp cho `platform_reporter` (phóng viên cũng tự upload ảnh bài mình) và `platform_media` (chuyên biệt, không có quyền sửa nội dung chữ).

### 3.3 Cơ chế `organization_id` trên `PostArticle` — đổi Ý NGHĨA, KHÔNG đổi schema

Xác nhận qua code: cột `organization_id` trên `post_articles` là FK bắt buộc (`->constrained()->restrictOnDelete()`, không nullable) — không có migration nào cần chạy. Chỉ đổi cách hiểu:

- **Trước đây:** `organization_id` = "tổ chức nào TẠO bài viết này" (tự động gán qua `TenantContext` khi `marketing` của tổ chức đó tạo bài).
- **Từ giờ:** `organization_id` = "bài viết này thuộc về/nói về tổ chức nào" (dùng để lọc/hiển thị/gắn nhãn sponsor) — người **thực sự viết/duyệt** là `platform_reporter`/`platform_content_editor`/`platform_content_head` (`organization_id=null`), ghi nhận qua `created_by`/`updated_by`/`approved_by` (cột trỏ `users`, vốn không ràng buộc phải cùng `organization_id` với bài viết — không cần đổi migration).

**Hệ quả kỹ thuật cần làm khi code (Phase 2A):**
1. `platform_reporter` (org-less) tạo bài **phải chọn tường minh** tổ chức mà bài viết nói về/thuộc về — hiện `CreateArticleAction` không nhận `organization_id` từ input, mà dựa vào `TenantContext` ambient (tự động đúng khi 1 nhân sự CỦA tổ chức tạo bài). Cần thêm 1 dropdown "Chọn tổ chức" trong form tạo bài + bọc `TenantContext::runForOrganization($selectedOrg, fn () => ...)` khi gọi Action — đúng pattern đã dùng cho mọi Platform Role thao tác xuyên tổ chức trong Phase 1-6.
2. **Bài viết KHÔNG nói riêng về 1 doanh nghiệp nào** (tin tức chung của nền tảng) — cần 1 tổ chức placeholder vì cột `organization_id` không nullable. Đề xuất: seed 1 `Organization` đặc biệt "Hà Kiên / Cổng thông tin chung" (giống quy ước `super-admin`/Platform Role đã dùng `organization_id=null` cho **User**, nhưng **Organization** không có khái niệm "null" vì chính nó là gốc — nên phải là 1 record thật, không phải `null`). **Cần bạn xác nhận tên/cách đặt tổ chức này trước khi seed.**

### 3.4 Thứ tự triển khai — giữ nguyên "Phase 2A", gộp chung với §4

`platform_section_editor` vẫn phụ thuộc category dùng chung toàn nền tảng (§4). Quyết định giữ nguyên: §3 + §4 làm chung 1 đợt (Phase 2A). `platform_reporter`/`platform_media` không phụ thuộc §4, có thể code trước nếu muốn tách nhỏ hơn nữa — nhưng khuyến nghị vẫn gộp 1 đợt để tránh 2 lần rà soát permission Post riêng lẻ.

---

## 4. Category dùng chung toàn nền tảng (Platform-wide category-scoping) — ĐẶC TẢ, CHƯA TRIỂN KHAI

### 4.1 Đổi hướng so với bản v1.2

Bản trước giả định category vẫn theo từng tổ chức (`post_categories.organization_id`) là "đã đúng sẵn" cho Lớp B — giả định đó **hết hiệu lực** vì Post giờ là tài sản nền tảng, không phải của từng doanh nghiệp. Cần **chuyên mục dùng chung toàn nền tảng** (1 bộ chuyên mục duy nhất, do đội biên tập Platform tự quản lý, không phụ thuộc tổ chức nào) — đúng khoảng trống kiến trúc đã phát hiện từ đầu dự án (`RBAC_NewsPortal_Gap_Analysis.md` §2.4).

### 4.2 Thiết kế dự kiến

**Lựa chọn A (khuyến nghị) — thêm cột `organization_id` nullable trên `post_categories`:**
```php
Schema::table('post_categories', function (Blueprint $table) {
    $table->foreignId('organization_id')->nullable()->change();
});
```
Chuyên mục do Platform tạo (`organization_id = null`) dùng chung cho MỌI bài viết bất kể `post_articles.organization_id` là tổ chức nào. Không xoá cơ chế category theo tổ chức cũ (nếu còn dữ liệu cũ) — chỉ thêm khả năng tạo category dùng chung.

**Bảng gán biên tập viên theo chuyên mục:**
```php
Schema::create('post_category_editors', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_category_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->timestamps();

    $table->unique(['post_category_id', 'user_id']);
});
```
(Bỏ cột `organization_id` khỏi bảng này so với bản v1.2 — vì user ở đây luôn là `platform_section_editor`, `organization_id=null`, gán theo category không theo tổ chức nào.)

`PostArticlePolicy::approve()`/`update()` thêm điều kiện: `platform_section_editor` chỉ đúng khi bài viết thuộc 1 category mà user được gán trong `post_category_editors`.

### 4.3 Không triển khai — gộp chung Phase 2A với §3, chờ lệnh triển khai

---

## 5. Độc giả VIP / Subscription cá nhân (B2C) — ĐẶC TẢ, CHƯA TRIỂN KHAI

### 5.1 Xác nhận nền tảng hiện có

`app/Enums/AccountType.php` hiện có đúng 3 case: `Free`, `OrgMember`, `Suspended`. `canLogin()`: true trừ `Suspended`. `canAccessOrgWorkspace()`: chỉ `OrgMember`. Chưa có `Vip`.

### 5.2 Thiết kế dự kiến

1. Thêm case `AccountType::Vip`.
2. `Modules/Subscription` hiện chỉ có `subscriber_type = 'organization'` (polymorphic nhưng chỉ dùng cho Organization) — cần xác nhận bảng `subscriptions` (Laravelcm package) có thực sự hỗ trợ `subscriber_type = 'user'` polymorphic không (nhiều khả năng có, do dùng Laravelcm/laravel-subscriptions vốn polymorphic sẵn — cần đọc lại package trước khi code, chưa xác nhận trong đợt nghiên cứu này).
3. Gate nội dung trả phí: thêm cột `is_premium` trên `PostArticleTranslation` (chưa có), check `$user->account_type === AccountType::Vip` trước khi render full nội dung ở trang public.

### 5.3 Không triển khai — chưa có xác nhận kế hoạch kinh doanh (đã ghi nhận ở spec cũ: "chưa có kế hoạch bán gói cá nhân trong ngắn hạn")

---

## 6. `post.legal_review` cho `platform_content_moderator` — ĐẶC TẢ, CHƯA TRIỂN KHAI

(Đã sơ phác ở `spec/Platform_RBAC_Technical_Specification.md` §6 — nhắc lại có bổ sung chi tiết migration)

### 6.1 Migration dự kiến

```php
Schema::table('post_article_translations', function (Blueprint $table) {
    $table->boolean('needs_legal_review')->default(false)->after('status');
    $table->foreignId('legal_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('legal_reviewed_at')->nullable();
});
```

Xác nhận qua nghiên cứu: `PostArticleTranslation` hiện KHÔNG có cột nào tái dùng được — bắt buộc migration mới, không phải chỉnh sửa cột có sẵn.

### 6.2 Luồng

`platform_content_editor`/`platform_content_head` đánh dấu `needs_legal_review=true` (nút mới trong UI duyệt bài) → xuất hiện trong hàng đợi riêng của `platform_content_moderator` (query mới trong `ApprovalDashboardService` hoặc `ListPendingReviewTranslationsHandler`) → `platform_content_moderator` duyệt/từ chối, ghi `legal_reviewed_by/at`, KHÔNG đổi `status` chính (độc lập với luồng biên tập).

### 6.3 Không triển khai — Phase 2, chưa có yêu cầu nghiệp vụ cụ thể kích hoạt

---

## 7. Kế hoạch triển khai tổng thể

| Phase | Nội dung | Điều kiện bắt đầu | Trạng thái |
|---|---|---|---|
| Phase 2 | §2 UI Quản trị Platform | — | ✅ Đã xong |
| **Phase 2A** | §3 (đội biên tập Platform: `platform_reporter`, `platform_media`, `platform_section_editor`) + §4 (category dùng chung toàn nền tảng) — làm chung 1 đợt | Cần bạn trả lời 2 câu hỏi mở ở §3.2/§3.3 trước khi bắt đầu code: (1) giữ `post_article.view` cho Lớp B hay thu hồi luôn, (2) tên tổ chức placeholder cho tin tức không gắn 1 doanh nghiệp cụ thể | ⏳ Chờ xác nhận |
| Phase 2B | §5 Độc giả VIP | Có kế hoạch kinh doanh xác nhận bán gói cá nhân | ⏳ Chưa có lịch |
| Phase 2C | §6 `post.legal_review` | Có nhu cầu pháp lý cụ thể phát sinh | ⏳ Chưa có lịch |

Chưa gán mốc thời gian cụ thể (tuần/tháng) cho Phase 2A vì còn phụ thuộc 2 câu hỏi mở — sẽ chốt ngay khi có câu trả lời, không ước lượng trước để tránh cam kết sai.
