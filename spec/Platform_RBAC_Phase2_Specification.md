# Platform RBAC — Đặc tả Phase 2 (Phần còn lại ngoài phạm vi §8)

**Phiên bản:** 1.1 (13/07/2026 — §2 UI Quản trị Platform ĐÃ TRIỂN KHAI XONG và verify qua HTTP thật + 5 test PHPUnit, xem "Trạng thái triển khai" ngay dưới)

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
| Vị trí code UI Platform | Đặt trong `Modules/Approval` (không tạo module mới) | Nhất quán với `CreatePlatformUserCommand`/`AuditPlatformRoleScopeCommand` đã đặt ở đây — toàn bộ hạ tầng Platform Role đã tập trung 1 chỗ |
| Không tái dùng `Modules/User/UserController` | Viết controller riêng cho Platform user | Xác nhận qua code: `UserController` gắn chặt team-scoping (`setPermissionsTeamId($organization_id)` xuyên suốt `resolveUserRole()`/`getOrganizationsFor()`) — không áp dụng được cho user `organization_id=null` |
| `super-admin` vẫn KHÔNG tạo được qua UI mới | Giữ nguyên quyết định ở CLI (spec cũ §3.8) | UI mới chỉ là "màn hình hoá" cho đúng use case CLI đang giải quyết (5 role biên tập/vận hành), không mở rộng thêm phạm vi rủi ro |
| 4 hạng mục còn lại (Organization Roles Lớp B, category-scoping, Độc giả VIP, `post.legal_review`) | **Chỉ đặc tả** (§3-§6), triển khai khi có yêu cầu "làm phase X" cụ thể | Đúng mức độ chín muồi nghiệp vụ hiện tại — chưa có xác nhận business cho từng hạng mục, tránh code thừa |

---

## 1. Giới thiệu

`spec/Platform_RBAC_Technical_Specification.md` đã triển khai xong Lớp A (6 Platform Role, rename, 2 role mới, test, verify). §8 của tài liệu đó liệt kê 5 hạng mục "ngoài phạm vi đợt này":

1. Bộ Organization Roles mới cho toà soạn (Lớp B).
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

## 3. Bộ Organization Roles mới (Lớp B) — ĐẶC TẢ, CHƯA TRIỂN KHAI

### 3.1 Danh sách role dự kiến (theo tài liệu gốc `RBAC_Platform_Operator_Specification.docx`)

| Role | Tên hiển thị | Trách nhiệm |
|---|---|---|
| `org_owner` | Chủ sở hữu / CEO | Đã có tương đương (`ceo`) — KHÔNG tạo mới, dùng lại |
| `org_editor_in_chief` | Tổng biên tập (tổ chức) | Duyệt tất cả bài trong tổ chức, quản lý biên tập viên |
| `org_section_editor` | Biên tập viên trưởng chuyên mục | Quản lý 1-n chuyên mục được gán — **phụ thuộc §4 (category-scoping)** |
| `org_editor` | Biên tập viên (tổ chức) | Duyệt/sửa bài trong chuyên mục được gán — **phụ thuộc §4** |
| `org_reporter` | Phóng viên / Cộng tác viên | Tạo bài, sửa bài của mình — map gần nhất hiện có: `marketing` |
| `org_media` | Nhiếp ảnh / Video | Chỉ upload media — permission mới `post_media.upload` |
| `org_viewer` | Cộng tác viên xem | Chỉ xem |

### 3.2 Câu hỏi cần chốt trước khi code (chưa có câu trả lời)

1. **Thêm mới hay thay thế 8 role CRM hiện có?** Quyết định trước đó (spec cũ §0): "giữ nguyên hoàn toàn". Nếu giờ thêm bộ này, cần làm rõ: 1 tổ chức có CẢ 2 bộ role song song (vd 1 user vừa `marketing` vừa `org_reporter`?), hay tổ chức tự chọn "loại hình" (CRM thường / Toà soạn) rồi chỉ dùng 1 bộ?
2. **`org_section_editor`/`org_editor` phụ thuộc category-scoping (§4)** — nếu làm role này TRƯỚC khi có category-scoping, chúng sẽ không khác gì `org_editor_in_chief`/toàn quyền trong tổ chức — không có giá trị phân quyền thật. Khuyến nghị: làm §4 trước, hoặc làm 2 role này CÙNG LÚC với §4.
3. Permission domain nào áp dụng — dùng lại `post_article.*` hiện có hay tạo permission mới riêng cho org roles?

### 3.3 Không triển khai cho tới khi có câu trả lời cho 3.2

---

## 4. Category-scoping — ĐẶC TẢ, CHƯA TRIỂN KHAI

### 4.1 Thiết kế dự kiến

```php
Schema::create('post_category_editors', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
    $table->foreignId('post_category_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('role_in_category', 20); // 'section_editor' | 'editor'
    $table->timestamps();

    $table->unique(['post_category_id', 'user_id']);
});
```

`PostArticlePolicy::approve()`/`update()` cần thêm điều kiện: nếu user có role `org_section_editor`/`org_editor`, chỉ đúng khi bài viết thuộc 1 category mà user được gán trong `post_category_editors` (JOIN qua `post_article_categories` — bảng pivot bài viết ↔ category đã có sẵn).

### 4.2 Rủi ro đã ghi nhận trước đó (từ `RBAC_NewsPortal_Gap_Analysis.md`)

`post_categories` hiện là `organization_id`-scoped (mỗi tổ chức có bộ chuyên mục riêng) — điều này thực ra **phù hợp** với category-scoping ở mức Lớp B (biên tập viên của 1 tổ chức chỉ quản category của tổ chức đó), khác với lo ngại trước đây (lúc đó đang lẫn giữa Lớp A xuyên tổ chức và Lớp B trong 1 tổ chức). Ở mức Lớp B, không cần category dùng chung toàn nền tảng — chỉ cần dùng chung TRONG 1 tổ chức, đã đúng sẵn.

### 4.3 Không triển khai cho tới khi có §3 (cần role `org_section_editor`/`org_editor` tồn tại trước)

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

1. **Ngay bây giờ:** §2 (UI Quản trị Platform) — code + test + verify theo đúng flow đã dùng cho Lớp A (Prepare → Test → Verify).
2. **Khi có câu trả lời cho §3.2:** §3 (Organization Roles Lớp B) — làm cùng lúc với §4 nếu cần `org_section_editor`/`org_editor` có giá trị thật.
3. **Khi có kế hoạch kinh doanh xác nhận:** §5 (Độc giả VIP).
4. **Khi có nhu cầu pháp lý cụ thể phát sinh:** §6 (`post.legal_review`).
