# Platform RBAC — Đặc tả Phase 2 (Phần còn lại ngoài phạm vi §8)

**Phiên bản:** 3.0 (13/07/2026) — **BẢN THIẾT KẾ THỐNG NHẤT, CUỐI CÙNG cho Phase 2A.** Sau nhiều vòng đảo ngược quyết định về `organization_id` (v2.2→v2.3→v2.4), bản này rà soát toàn diện cả 3 module liên quan (`Post`, `Aicem`, `Approval`) và chốt 1 thiết kế duy nhất, không đảo ngược thêm. Xem §3.3-§3.6 để biết đầy đủ.

**Changelog:**
- **v2.4 → v3.0 (hiện tại):** rà soát toàn diện `Modules/Post` + `Modules/Aicem` + `Modules/Approval` theo yêu cầu "thống nhất luồng ổn định, hiệu quả, hiệu suất cao". Phát hiện & chốt:
  - `organization_id` trên `post_articles`: **quay lại DROP HẲN** (huỷ phương án rename `sponsor_organization_id` ở v2.4) — xác nhận qua UI thật: form bài viết chưa từng có ô chọn tổ chức, cột đó sẽ luôn NULL nếu giữ lại. `ExpireSponsoredArticlesJob`/`TakeDownArticleTranslationAction` đổi sang thông báo cho **nhân sự nền tảng** (`platform_content_head`/`platform_ops`) thay vì tìm đúng tổ chức — chấp nhận đánh đổi để không phải thêm UI mới.
  - **Aicem** (phát hiện quan trọng, suýt bị bỏ sót): toàn bộ pipeline AI hỗ trợ viết bài bắt buộc mọi nội dung phải thuộc 1 Organization thật (fail cứng nếu không) — giải pháp: seed 1 Organization cố định riêng cho mục đích này (`is_system=true`, "Vì Gia Đình") + thêm `organizationId()` vào `AicemSubjectResolver` (dọn luôn 3 chỗ code trùng lặp Aicem đã tự cảnh báo là rủi ro bug). Xem §3.4.
  - **Đọc bài công khai** (ảnh hưởng người dùng cuối, ưu tiên cao, suýt bị bỏ sót): route đọc bài công khai đang yêu cầu resolve tổ chức theo subdomain (`'tenant'` middleware) — không còn ý nghĩa khi bài không thuộc tổ chức nào, bắt buộc phải gỡ. Xem §3.5.
  - **`Modules/Approval`**: xác nhận không cần sửa gì — module này chỉ phục vụ `Product`/`Organization`, không đụng Post. Xem §3.6.
  - Nhiều đơn giản hoá dây chuyền có lợi: bỏ hẳn `TenantContext::runForOrganization()` wrap ở `TranslationController`/`PublishDueTranslationsJob`/`ExpireSponsoredArticlesJob` (root cause biến mất, không chỉ vá); `ListPendingReviewTranslationsHandler` bỏ hẳn workaround `withoutGlobalScope`+`setRelation` thủ công.
- **v1.2 → v2.0:** sửa hiểu sai căn bản ở §3 — `Modules/Post` là tài sản của NỀN TẢNG, không phải của từng doanh nghiệp. 7 role toà soạn thuộc Lớp A, không phải Lớp B mới như bản 1.2 từng viết. Rà lại chỉ còn đúng 2 role thật sự mới (`platform_reporter`, `platform_media`) + 1 role chờ category-scoping (`platform_section_editor`). §4 đổi từ "category theo tổ chức" sang "category dùng chung toàn nền tảng". Thêm yêu cầu thu hồi quyền Post khỏi mọi role Lớp B.
- **v2.0 → v2.1:** thêm khái niệm **loại tài khoản nền tảng** tường minh — cột `account_type` trên `users` (vốn chỉ có `free`/`org_member`/`suspended`) có thêm case `AccountType::Platform`, gán cho mọi user `organization_id=null`. Đây là trục thứ 2, độc lập với Spatie role: `account_type` = phạm vi tài khoản, role = quyền hạn cụ thể trong phạm vi đó. Gộp `platform_reporter`+`platform_media` thành 1 role mới duy nhất `platform_content_creator` (viết/sửa bài + upload media, không publish/duyệt) — giữ tách biệt viết vs duyệt để tránh tự duyệt bài của chính mình.
- **v2.1 → v2.2:** chốt 2 câu hỏi mở còn lại của Phase 2A: (1) `post_article.view` — thu hồi luôn khỏi mọi role Lớp B, không giữ lại; (2) tổ chức placeholder cho tin không gắn doanh nghiệp cụ thể — tạo org mới riêng `is_system=true`, `name="Vì Gia Đình"`.
- **v2.2 → v2.3:** **đảo ngược quyết định org placeholder ở v2.2** — `organization_id` trên `post_articles` bị **DROP hẳn** khỏi schema, không nullable-hoá, không seed org "Vì Gia Đình" nữa. Lý do: thông tin sponsor/gắn nhãn doanh nghiệp vốn đã nằm ở các cột string riêng (`sponsor_name`/`sponsor_label`/...), không cần `organization_id` hỗ trợ — bài viết nền tảng không thuộc về `organization_id` nào cả. Xem §3.3.

## Trạng thái triển khai §2

✅ Đã code trên dev: `PlatformUserController` (index/create/store/edit/update/destroy), `StorePlatformUserData`/`UpdatePlatformUserData`, 3 Blade view, route `backend.platform-users.*`, mục sidebar gate `super-admin`.

Đã verify qua HTTP thật: tạo user Platform mới qua form → login được, đổi role qua form (`syncRoles`), vô hiệu hoá KHÔNG xoá cứng (`is_active=false`, record vẫn còn), non-super-admin bị 403, form từ chối tạo `super-admin`, không tự vô hiệu hoá được chính mình.

⚠️ **Test PHPUnit: cần viết lại trước khi merge.** Tài liệu từng ghi nhận "5 test PHPUnit (`PlatformUserAdminTest.php`), chạy cùng bộ 6 test cũ = 11 test pass" — rà soát lại không tìm thấy file test này trong cây thư mục hiện tại (`Modules/Approval/tests/Feature/` chỉ có `.gitkeep`). Coi như CHƯA có test tự động cho §2 — phải viết lại đúng 5 kịch bản đã liệt kê ở §2.8 trước khi merge nhánh này.
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
| Loại tài khoản nền tảng (`account_type`) | Thêm case `AccountType::Platform` trên cột `account_type` (đã có sẵn trên `users`, trước đó chỉ dùng cho `free`/`org_member`/`suspended`), gán cho mọi user `organization_id=null` — độc lập với `AccountType::OrgMember` (tài khoản doanh nghiệp) | Tách bạch rõ 2 trục: `account_type` = phạm vi tài khoản (nền tảng / doanh nghiệp / độc giả tự do), Spatie role = quyền hạn cụ thể trong phạm vi đó. Không thay thế role — tài khoản nền tảng vẫn phải gán 1 trong các Platform Role mới có quyền làm gì |
| Có cần tạo role riêng để "viết bài" không, hay dùng chung role duyệt? | **Vẫn cần role riêng** (`platform_content_creator`) — không gộp vào role duyệt (`platform_content_editor`/`platform_content_head`) | Nếu 1 tài khoản vừa có quyền viết vừa có quyền duyệt, tài khoản đó tự duyệt được bài của chính mình — phá vỡ mục đích tồn tại của `Modules/Approval` (kiểm soát chéo). Tách biệt viết vs duyệt là ranh giới BẮT BUỘC giữ, khác với ranh giới "viết chữ" vs "chỉ upload ảnh" (ranh giới đó không mang tính kiểm soát rủi ro nên gộp được — xem §3.1 v2.1) |
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

**Cập nhật hướng (v2.1): gộp `platform_reporter` + `platform_media` thành 1 role duy nhất `platform_content_creator`** — viết/sửa bài (`post_article.create/edit`) + upload media (`post_media.upload`), KHÔNG có quyền publish/duyệt. Lý do gộp: ranh giới "viết chữ" vs "chỉ upload ảnh" không mang tính kiểm soát rủi ro (ai cũng có thể vừa viết vừa tự chèn ảnh bài mình, không cần tách quyền); khác hẳn ranh giới viết vs duyệt — ranh giới đó BẮT BUỘC giữ tách biệt (xem §0) vì nếu gộp, tài khoản đó tự duyệt được bài của chính mình, phá vỡ mục đích tồn tại của `Modules/Approval`.

`platform_section_editor` (category-scoped) vẫn hoãn, phụ thuộc §4 (category dùng chung toàn nền tảng) — không nằm trong phần gộp này.

### 3.2 Permission — thu hồi quyền của Lớp B trên Post, cấp lại cho Lớp A

Xác nhận qua code (`Modules/Post/database/seeders/PostPermissionSeeder.php`): hiện **6/8 role Lớp B** đang có quyền trên `post_article.*` — `marketing` (create/edit/delete/manage_sponsorship), `ceo` (publish/unpublish/manage_sponsorship), `ops` (publish), `system_admin` (create + các quyền khác), `sales`/`hr`/`ai_operator`/`viewer` (chỉ view). Theo đúng chỉ đạo "doanh nghiệp không có quyền thao tác":

- **Thu hồi toàn bộ quyền GHI** (`create/edit/delete/publish/unpublish/manage_sponsorship`) khỏi **mọi role Lớp B**, không chỉ `marketing`.
- **`post_article.view` — ĐÃ CHỐT (v2.2): thu hồi luôn khỏi mọi role Lớp B.** Doanh nghiệp không còn thấy Post ở dashboard nữa — muốn xem bài viết/sponsor về chính họ thì xem qua cổng thông tin công khai như độc giả bình thường, đúng nguyên tắc "Post hoàn toàn là tài sản nền tảng, doanh nghiệp không thao tác/không có mục Post nào trong dashboard". Cần xoá `post_article.view` khỏi cả 8 role trong `PostPermissionSeeder::ROLE_MAP`, và gỡ mọi link/mục sidebar dashboard doanh nghiệp trỏ tới Post.
- Cấp `post_article.create/edit/delete` + `post_media.upload` (permission mới) cho **`platform_content_creator`** (role gộp, thay cho 2 role `platform_reporter`/`platform_media` dự kiến ở bản 2.0 — xem §3.1 v2.1); giữ nguyên `post_article.publish/unpublish` chỉ `platform_content_head` (đã đúng từ trước — không đổi).

### 3.3 Cơ chế `organization_id` trên `PostArticle` — ĐÃ CHỐT LẠI LẦN CUỐI (v3.0): DROP HẲN, không rename, không giữ dưới bất kỳ hình thức nào

**Quyết định v3.0 (thay thế hoàn toàn v2.4 ở mục này — đây là quyết định CUỐI, không đảo ngược thêm nữa):** phương án "đổi tên `sponsor_organization_id`, nullable" ở v2.4 **bị huỷ**. Lý do: rà lại UI thật thì **form tạo/sửa bài chưa bao giờ có ô chọn Organization** (`Modules/Post/resources/views/admin/articles/edit.blade.php` — sponsor chỉ có `sponsor_name`/`sponsor_logo_url` dạng text tự gõ, `sponsor_label` là dropdown enum, KHÔNG phải chọn tổ chức thật). `organization_id` cũ được gán tự động thuần tuý qua `TenantContext` (vì trước đây chỉ nhân sự CỦA 1 tổ chức mới tạo được bài, nên "ai tạo" ngẫu nhiên trùng "ai tài trợ") — chưa từng là 1 lựa chọn tường minh của người dùng. Giữ lại cột (dù đổi tên/nullable) mà không có UI để set nó thì cột đó **sẽ luôn NULL vĩnh viễn, chết ngay từ đầu** — đúng là thừa. Không thêm dropdown chọn tổ chức tài trợ (chấp nhận không có, xem hệ quả ở mục 3 bên dưới) — **quyết định: DROP HẲN `organization_id` khỏi `post_articles`**, giống hệt hướng ban đầu ở v2.3, nhưng lần này lý do đúng và đã tính hết hệ quả (xem mục 3, 7).

**Tóm tắt 3 khái niệm tách bạch — đây là nguyên tắc bao trùm toàn bộ thiết kế Phase 2A, Dev phải hiểu trước khi đọc chi tiết bên dưới:**

| Khái niệm | Cơ chế | Vì sao tách riêng |
|---|---|---|
| **Sở hữu/vận hành** — ai viết, ai duyệt | `created_by`/`updated_by`/`approved_by` (trỏ `users`, không đổi) | Luôn là nền tảng (`platform_content_creator`/`editor`/`head`), không cần cột `organization_id` nào hỗ trợ |
| **Tài trợ** — bài này quảng cáo cho ai (hiếm) | `sponsor_name`/`sponsor_logo_url`/`sponsor_label` (string/enum, đã có sẵn, KHÔNG đổi) | Chỉ để HIỂN THỊ trên trang bài viết — không cần liên kết thật tới `Organization` vì không có UI chọn, và không cần thiết (xem mục 3 — thông báo hết hạn tài trợ đổi sang báo nhân sự nền tảng thay vì báo đúng doanh nghiệp) |
| **Tenant-context cho AI (Aicem)** — bài này dùng ngân sách/knowledge-base/workflow AI của tổ chức nào | 1 Organization **cố định**, seed riêng, KHÔNG lưu trên bất kỳ cột nào của Post — xem §3.4 | Aicem bắt buộc mọi nội dung phải resolve về đúng 1 Organization thật (thiết kế gốc, không đổi được trong phạm vi Phase 2A) — nhưng đây là nhu cầu nội bộ của Aicem, không phải của Post, nên không cần lộ ra ở schema Post |

**Hệ quả kỹ thuật cần làm khi code (Phase 2A) — Dev KHÔNG được đoán, làm đúng theo danh sách này:**

0. **Lưu ý về schema thật khác với migration gốc:** migration tạo bảng ban đầu (`database/migrations/generated/..._000064_create_post_articles_table.php`) mô tả `organization_id` NOT NULL + unique `(organization_id, slug)` + index `(organization_id, status, published_at)` + index `(organization_id, format)` — nhưng 2 migration sau đó đã âm thầm đổi: `..._000155_add_main_locale_and_is_sponsored_and_sponsor_name...` thêm index mới `idx_post_article_sponsored` (organization_id, is_sponsored, sponsored_end_date); `..._000156_drop_title_and_slug_and_excerpt...` đã **drop sẵn** `uq_post_article_org_slug` và `idx_post_article_org_status_pub` từ trước (title/slug/status/published_at đã dời sang `post_article_translations`). **Xác nhận qua schema DB thật:** `post_articles` hiện chỉ còn 1 FK (`organization_id → organizations`) + 2 index: `idx_post_article_org_format` (organization_id, format), `idx_post_article_sponsored` (organization_id, is_sponsored, sponsored_end_date). Migration phải xử lý đúng 2 index này.
1. **Migration trên `post_articles`:** drop FK `organization_id` + drop cột. Drop `idx_post_article_org_format` — thay bằng `index('format')` nếu còn nơi nào lọc theo format riêng (Dev kiểm tra `ListArticlesForAdminHandler`/`ListPublishedArticlesHandler` trước khi quyết định giữ hay bỏ hẳn, không suy đoán). Drop `idx_post_article_sponsored` (organization_id, is_sponsored, sponsored_end_date) — thay bằng **`index(['is_sponsored', 'sponsored_end_date'])`** (bỏ hẳn `organization_id` khỏi index — đúng với query thật của `ExpireSponsoredArticlesJob`, vốn chỉ `WHERE is_sponsored=1 AND sponsored_end_date < today`, chưa bao giờ lọc theo tổ chức trong WHERE — cải thiện hiệu suất so với cả bản gốc, vì index cũ có cột dẫn đầu không hề được dùng để lọc).
2. **`Modules/Post/app/Models/PostArticle.php`** — thôi `extends TenantAwareModel`, đổi sang **extend `Illuminate\Database\Eloquent\Model` trực tiếp** + tự thêm `use HasFactory, SoftDeletes, LogsActivity` (bỏ hẳn `BelongsToOrganization`, không còn relation `organization()` nào — không có gì thay thế, vì không còn khái niệm tổ chức sở hữu/tài trợ liên kết thật).
3. **`ExpireSponsoredArticlesJob.php`** — sửa, KHÔNG xoá job, đổi hẳn logic tìm người nhận thông báo (theo quyết định: không còn liên kết Organization nên không thể báo đúng doanh nghiệp — báo cho nhân sự nền tảng thay thế):
   - Dòng 35: bỏ `PostArticle::withoutTenant()` → `PostArticle::where(...)` (không còn global scope để bypass).
   - Dòng 44-56: bỏ hẳn `Organization::withoutGlobalScopes()->find($article->organization_id)` + wrapper `TenantContext::runForOrganization(...)` — không còn cần thiết, gọi thẳng `$article->update(['is_sponsored' => false])` + `$this->notifyExpired($article)`.
   - `notifyExpired()` (dòng 71-79): đổi từ `User::where('organization_id', $article->organization_id)->role(['marketing','ceo'])` sang **`User::role(['platform_content_head', 'platform_ops'])->get()`** (nhân sự nền tảng, dùng Spatie `role()` scope bình thường vì các role này không team-scoped theo tổ chức — xem cách `hasGlobalRole()` xử lý ở `app/Models/User.php`). Nội dung `SponsorshipExpiredNotification` nên bổ sung `sponsor_name` (đọc từ `$article->sponsor_name`) để nhân sự nền tảng biết cần liên hệ lại đúng doanh nghiệp nào qua kênh sales/CRM riêng — hệ thống không tự động biết user nào của doanh nghiệp đó.
   - Comment đầu file (dòng 18-22, trích `spec/dac-ta-ky-thuat-bai-viet-tai-tro.md` §8) cần viết lại — file spec đó được trích dẫn nhưng **hiện không tồn tại** trong `spec/` (đã xác nhận qua `find`, có thể đã đổi tên/chưa từng commit — nợ tài liệu cần dọn riêng, không chặn Phase 2A).
4. **`TakeDownArticleTranslationAction.php:48,55`** — cùng vấn đề: hiện `User::where('organization_id', $translation->organization_id)->role(['ceo','ai_operator'])` để báo gỡ bài. Đổi sang báo nhân sự nền tảng tương ứng (`platform_content_head`/`platform_ops`, cùng pattern mục 3) — áp dụng nhất quán 1 quy tắc cho mọi thông báo Post: **người nhận luôn là nhân sự nền tảng, không còn khái niệm "đúng tổ chức sở hữu bài" để báo nữa.**
5. **`pending-review.blade.php:45`**: `{{ $translation->article->organization->name ?? '—' }}` — không còn `article->organization` để đọc (đã drop). Đổi thành hiển thị thông tin tài trợ (nếu có) thay vì tổ chức: `{{ $translation->article->is_sponsored ? $translation->article->sponsor_name : '—' }}` — cột này trong bảng duyệt bài xuyên tổ chức giờ có ý nghĩa mới: "bài này có tài trợ không, tài trợ cho ai" (text hiển thị), không phải "thuộc tổ chức nào" nữa (vì mọi bài đều thuộc nền tảng).
6. **`ListPendingReviewTranslationsHandler.php`**: đơn giản hoá TẬN GỐC — không còn `OrganizationScope` nào để bypass nữa (root cause của toàn bộ workaround `withoutGlobalScope`+`setRelation` thủ công đã biến mất). Viết lại thành query thường:
   ```php
   PostArticleTranslation::whereIn('status', [TranslationStatus::Submitted, TranslationStatus::Approved])
       ->with('article') // an toàn eager-load bình thường — PostArticle không còn tenant-scoped nên không còn rủi ro OrganizationScope re-apply
       ->orderByDesc('updated_at')
       ->get();
   ```
   **Khuyến nghị thêm (không bắt buộc, nhưng nên làm nhân lúc sửa):** query này hiện không phân trang/không giới hạn (`->get()` tải toàn bộ Submitted+Approved của TOÀN NỀN TẢNG vào memory) — đã là khoảng trống về khả năng mở rộng trước cả redesign này (ghi nhận từ khảo sát `spec/Workflow_Approval_Technical_Specification.md`), nên cân nhắc thêm `paginate()` nhân lúc đang viết lại hàm này.
7. **4 bảng con denormalize `organization_id` — DROP HẲN ở cả 4 bảng** (không đổi tên, không thay thế bằng gì — không còn khái niệm tổ chức nào để gắn vào các bảng con này nữa):
   - `post_article_translations` — drop `organization_id`; drop unique `uq_post_trans_org_locale_slug` (organization_id, locale, slug) → thay `unique(['locale', 'slug'])` (**slug giờ unique toàn cục theo locale** — dev hiện 0 dòng nên không phát sinh xung đột khi deploy); drop `idx_post_trans_org_status_pub` → `index(['status', 'published_at'])`. Model bỏ `BelongsToOrganization`.
   - `post_content_blocks`, `post_product_blocks` — drop cột `organization_id` + bỏ `BelongsToOrganization`; `post_product_blocks` đổi `idx_post_pb_org_translation` (organization_id, translation_id) → `index('translation_id')`.
   - `post_publishing_logs` — drop cột `organization_id` (chỉ set 1 chiều, không nơi nào đọc lại — an toàn để drop).
   - `CreateTranslationAction.php` (dòng 33, 36, 65, 82, 98, 112): xoá toàn bộ logic đọc `$article->organization_id` để lan truyền — không còn cột đích. `uniqueSlug()` (dòng 58-75) đổi từ `where('organization_id', $organizationId)` sang chỉ `where('locale', ...)->where('slug', ...)` (unique toàn cục theo locale).
   - `LogsPublishingActions.php:13`: bỏ dòng gán `organization_id`.
   - `TranslationController.php:167-231`: bỏ hẳn wrapper `TenantContext::runForOrganization($translation->organization, ...)` ở `runTransition()` (dòng 179-193) — lý do tồn tại (tránh `$translation->article` lazy-load ra `null` vì tenant-scope mismatch cho tài khoản nền tảng) **biến mất hoàn toàn** vì `PostArticle`/`PostArticleTranslation` không còn tenant-scoped — đây là 1 lớp bug đã được tài liệu hoá kỹ ở `spec/Workflow_Approval_Technical_Specification.md` §18.10, và redesign này xoá tận gốc thay vì tiếp tục vá. Sửa lại rule unique slug (dòng ~197-204) sang unique toàn cục theo `locale`, bỏ `organization_id`.
   - Factory: `PostArticleFactory.php:24-28,58` bỏ `organization_id`; `PostArticleTranslationFactory` bỏ hẳn `organization_id`.
   - `BackfillPostTranslationsCommand.php:60`: bỏ dòng đọc `organization_id`.
8. `CreateArticleAction`/form tạo bài: không cần dropdown chọn tổ chức, không cần `TenantContext::runForOrganization` — `platform_content_creator` tạo bài bình thường, không có bước chọn tổ chức nào cả, kể cả khi đánh dấu tài trợ (sponsor vẫn chỉ là text như hiện tại, không đổi UI này).
9. `post_categories`/`post_tags` **không nằm trong phạm vi thay đổi này** ở mức migration — 2 bảng đó có `organization_id` độc lập; việc chuyển category sang "dùng chung toàn nền tảng" là quyết định riêng ở §4. **Khuyến nghị nhất quán (chưa quyết, gắn cờ để cân nhắc):** `post_tags` hiện vẫn tenant-scoped theo tổ chức giống `post_categories` trước khi sửa — nếu Post đã hoàn toàn thuộc nền tảng, tag cũng nên theo cùng hướng "dùng chung toàn nền tảng" như category ở §4 cho nhất quán, nhưng đây là quyết định riêng, không tự ý gộp vào Phase 2A nếu chưa xác nhận.
10. Rà lại mọi nơi khác từng dùng `post_articles.organization_id`/`post_article_translations.organization_id` để lọc/hiển thị trước khi merge — theo khảo sát đã làm, không còn dashboard/sidebar doanh nghiệp nào query trực tiếp các bảng này (đã gỡ theo §3.2); chỉ còn đúng các call site đã liệt kê ở mục 3-7.

### 3.4 Tích hợp Aicem — Post không còn `organization_id` nhưng Aicem bắt buộc phải có

**Bối cảnh (phát hiện qua khảo sát sâu `Modules/Aicem`, không phải suy đoán):** toàn bộ pipeline AI hỗ trợ viết bài (chọn workflow, knowledge-base 3 tầng, ngân sách AI theo tháng) được thiết kế xoay quanh giả định nền tảng "mọi nội dung luôn thuộc đúng 1 Organization" (`spec/AICEM_Technical_Specification.md` §1, §3, §7 xác nhận đây là nguyên tắc xuyên suốt, không phải chi tiết vặt). `RunAicemWorkflowJob` **fail cứng ngay lập tức** nếu `organization_id` của generation run là null hoặc không resolve được Organization thật ("Organization #{id} không còn tồn tại"). Việc Post bỏ hẳn `organization_id` (§3.3) sẽ làm toàn bộ tính năng AI hỗ trợ viết bài ngừng hoạt động nếu không xử lý.

**Quyết định (đã chốt):** seed **1 Organization cố định, riêng biệt** (`is_system = true`, tên gợi ý "Vì Gia Đình" — resurrect đúng ý tưởng org placeholder đã bỏ ở v2.2, nhưng lần này dùng cho mục đích khác hẳn: **không liên quan gì tới schema Post**, chỉ là tenant-context nội bộ mà Aicem dùng khi xử lý nội dung Post). Tổ chức này có ngân sách AI/cấu hình provider/knowledge-base riêng của chính đội biên tập nền tảng — hoàn toàn hợp lý về nghiệp vụ (đội biên tập nền tảng cũng cần "giọng văn"/knowledge-base riêng như bất kỳ toà soạn nào, đúng tinh thần thiết kế gốc của Aicem).

**Việc kỹ thuật cần làm:**

1. Seed 1 `Organization` mới: `is_system = true`, tên theo quyết định trên. Cấu hình `ai_monthly_budget_usd`/`ai_provider_config`/`aicem_content_vertical` cho tổ chức này như 1 tổ chức bình thường (việc setup, không phải code).
2. Thêm method `organizationId($subject): int` vào interface `Modules\Aicem\Contracts\AicemSubjectResolver` — đây là **sửa tận gốc**, không phải chỉ patch riêng cho Post: hiện có 3 chỗ (`ListRunnableWorkflowsHandler.php:47`, `AicemGenerationController.php` — logic tương tự dòng ~42-48, `StartGenerationRunAction.php:32`) tự đọc `$subject->organization_id` giống hệt nhau, và chính comment trong code đã cảnh báo đây là chỗ từng gây bug thật (workflow của tổ chức A chạy nhầm cho nội dung tổ chức B). Gộp về 1 chỗ vừa giải quyết redesign vừa dọn nợ kỹ thuật đã biết.
   - `PostArticleSubjectResolver::organizationId()` → trả về hằng số ID của Organization "Vì Gia Đình" ở mục 1 (đọc qua config, ví dụ `config('platform.aicem_organization_id')`, KHÔNG hardcode số — seed 1 lần, lưu ID vào config/`.env`).
   - `ProductSubjectResolver::organizationId()` → giữ nguyên hành vi cũ, trả về `$subject->organization_id` thật (Product không đổi gì — vẫn thuộc đúng tổ chức của nó).
3. Sửa 3 call site ở mục 2 để gọi `$resolver->organizationId($subject)` thay vì tự đọc `$subject->organization_id` trực tiếp.
4. Toàn bộ phần còn lại của pipeline Aicem (`RunAicemWorkflowJob`, `CheckAndReserveBudgetAction`, `ReconcileBudgetAction`, `GetAicemUsageStatsHandler`) **không cần sửa gì** — vì luôn nhận được 1 `organization_id` hợp lệ, trỏ đúng 1 Organization thật tồn tại (tổ chức "Vì Gia Đình"), y hệt như mọi Organization khác — không phá vỡ bất kỳ bất biến kiến trúc nào của Aicem (`spec/AICEM_Technical_Specification.md` §3 "Absolute tenant isolation" vẫn đúng nguyên).
5. `CreateExampleCandidateFromArticleAction`/`SuggestExampleGoodFromPublishedArticle` (module Learning-from-examples) hiện dựa vào `TenantContext` ambient đã đúng sẵn lúc tạo `AicemExampleCandidate` (không đọc `organization_id` tường minh) — đổi sang set tường minh `organization_id` = hằng số ở mục 1 khi tạo candidate cho `subject_type=post_article`, giống cách `AicemGenerationRun`/`AicemSuggestion` đã làm (đọc rõ trong docblock của các model đó: "override tường minh, không dựa vào ambient" — đúng pattern đã có sẵn trong codebase cho chính vấn đề này, không phải phát minh mới).

### 3.5 Đọc bài công khai — bỏ yêu cầu resolve theo tổ chức (ảnh hưởng người dùng cuối, ưu tiên cao)

**Phát hiện quan trọng nhất về mức độ ảnh hưởng người dùng thật:** route đọc bài công khai (`Modules/Post/app/Features/PublicReading/*` — `PublicArticleController`, `PublicCategoryController`, `SitemapController`) hiện đăng ký trong route group dùng middleware `'tenant'` (`app/Http/Middleware/IdentifyOrganization.php` — resolve tổ chức theo subdomain/header/session, set `TenantContext`, mọi query sau đó lọc theo tổ chức đó). Vì `PostArticleTranslation` không còn tenant-scoped (§3.3), việc resolve tổ chức theo subdomain **không còn ý nghĩa gì** cho các route này — nếu để nguyên, độc giả có thể không xem được bài đúng cách (tuỳ hành vi cụ thể của `IdentifyOrganization` khi không có org nào khớp — cần Dev đọc lại middleware này để biết chính xác điều gì xảy ra: lỗi 404, danh sách rỗng, hay rơi vào tổ chức mặc định sai).

**Việc cần làm:** gỡ route group `PublicReading` của Post ra khỏi middleware `'tenant'` — bài viết nền tảng phục vụ đồng nhất cho mọi domain/subdomain, không cần resolve tổ chức nào trước khi trả về nội dung. Nếu domain/subdomain đó còn phục vụ nội dung khác thật sự thuộc 1 tổ chức cụ thể (vd trang catalog sản phẩm của `Modules/Product`), route group đó vẫn giữ `'tenant'` bình thường — chỉ tách riêng nhóm route Post ra khỏi yêu cầu này. Đây là việc bắt buộc phải làm trong Phase 2A, không phải tuỳ chọn — thiếu bước này thì phần còn lại của redesign (viết bài không cần chọn tổ chức) vô nghĩa vì độc giả không xem được kết quả.

### 3.6 Xác nhận phạm vi `Modules/Approval` — KHÔNG cần sửa gì

Khảo sát xác nhận: `Modules/Approval` (bao gồm `ApprovalSubject`/`ApprovalLog`/`ApprovalDashboardService`/`ApprovalHistoryController`/`HasApproval`/`BackfillApprovalSubjectsCommand`) chỉ phục vụ 2 loại subject: `Product` và `Organization` — **không đụng tới Post ở bất kỳ đâu** (`Modules/Post` cố tình KHÔNG dùng cơ chế `HasApproval`/`ApprovalSubject`, giữ `TranslationStatus` riêng — xác nhận qua `spec/Workflow_Approval_Technical_Specification.md` §0/§18.10). Riêng `ListPendingReviewTranslationsHandler`/`pending-review.blade.php` (xử lý ở §3.3 mục 5-6) tuy phục vụ đúng mục đích "duyệt bài xuyên tổ chức" của Platform Approval Gateway, nhưng về mặt code lại nằm trong `Modules/Post`, không phải `Modules/Approval` — không có file nào trong `Modules/Approval` cần sửa cho Phase 2A.

### 3.7 Thứ tự triển khai — giữ nguyên "Phase 2A", gộp chung với §4

`platform_section_editor` vẫn phụ thuộc category dùng chung toàn nền tảng (§4). Quyết định giữ nguyên: §3 + §4 làm chung 1 đợt (Phase 2A). `platform_content_creator` không phụ thuộc §4, có thể code trước nếu muốn tách nhỏ hơn nữa — nhưng khuyến nghị vẫn gộp 1 đợt để tránh 2 lần rà soát permission Post riêng lẻ.

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

| Phase | Nội dung | Điều kiện bắt đầu | Trạng thái | Ước tính effort |
|---|---|---|---|---|
| Phase 2 | §2 UI Quản trị Platform | — | ✅ Đã xong (code); ⚠️ test PHPUnit cần viết lại — xem §2 | — |
| **Phase 2A** | §3 (đội biên tập Platform: `platform_content_creator` [gộp `platform_reporter`+`platform_media`], `platform_section_editor`) + §3.4 (tích hợp Aicem) + §3.5 (bỏ tenant khỏi đọc bài công khai) + §4 (category dùng chung toàn nền tảng) — làm chung 1 đợt | Tất cả câu hỏi mở đã chốt (v3.0, không còn điểm nào cần xác nhận thêm): (1) thu hồi `post_article.view` khỏi Lớp B; (2) drop hẳn `organization_id` khỏi `post_articles` + 4 bảng con, không rename, không giữ; (3) thông báo hết hạn tài trợ/gỡ bài đổi sang báo nhân sự nền tảng; (4) Aicem dùng 1 Organization cố định riêng (seed mới) qua `AicemSubjectResolver::organizationId()`; (5) gỡ `'tenant'` middleware khỏi route đọc bài công khai của Post; (6) xác nhận `Modules/Approval` không cần sửa gì | ✅ Sẵn sàng triển khai — chờ lệnh code, không còn điểm nào phải hỏi thêm | **~4–5 tuần** (1 dev) — tăng so với ước tính v2.4 (~3-4 tuần) sau khi rà soát toàn diện Aicem+Approval phát hiện thêm việc thật: (a) migration drop `organization_id` ở `post_articles` + 4 bảng con + sửa base class 5 model + `CreateTranslationAction`/`SyncContentBlocksAction`/`LogsPublishingActions`/`TranslationController`/factories — chi tiết §3.3; (b) đơn giản hoá `ExpireSponsoredArticlesJob`/`PublishDueTranslationsJob`/`TakeDownArticleTranslationAction` (bỏ TenantContext wrap, đổi người nhận thông báo) — §3.3 mục 3-4; (c) viết lại `ListPendingReviewTranslationsHandler` + `pending-review.blade.php` — §3.3 mục 5-6; (d) **tích hợp Aicem**: seed Organization mới + thêm `organizationId()` vào `AicemSubjectResolver` + sửa 3 call site — §3.4 (việc mới phát sinh, không có trong ước tính trước); (e) **gỡ `'tenant'` middleware khỏi route đọc bài công khai** — §3.5 (việc mới phát sinh, ảnh hưởng người dùng cuối trực tiếp, không thể bỏ qua); (f) permission seeder `platform_content_creator` + thu hồi quyền Post khỏi Lớp B; (g) migration `post_categories.organization_id` nullable + bảng `post_category_editors` + sửa `PostArticlePolicy`; (h) viết lại 5 test PHPUnit còn thiếu ở §2 + test mới cho §3/§3.4/§3.5/§4. Con số giả định, chưa breakdown theo giờ — order-of-magnitude, không phải cam kết cứng |
| Phase 2B | §5 Độc giả VIP | Có kế hoạch kinh doanh xác nhận bán gói cá nhân | ⏳ Chưa có lịch | Chưa ước tính — phụ thuộc phạm vi thực tế khi có kế hoạch kinh doanh (đọc lại package `laravel-subscriptions` trước mới ước tính được — xem §5.2) |
| Phase 2C | §6 `post.legal_review` | Có nhu cầu pháp lý cụ thể phát sinh | ⏳ Chưa có lịch | Chưa ước tính — quy mô nhỏ (1 migration + 1 hàng đợi mới), nhưng chưa có yêu cầu nghiệp vụ cụ thể để tính effort thật |
