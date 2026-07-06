# Đặc tả Kỹ thuật: Social Login — Google · Facebook · LinkedIn

**Module:** `Modules/Auth`
**Version:** 1.1.0
**Ngày:** 2026-06-15
**Phụ thuộc:** `DacTa_CompetencyPassport_Platform.md` (spec gốc)
**Architecture:** AVSA + CQRS-lite (nhất quán với module Auth hiện tại)

---

## Mục lục

1. [Bối cảnh & Mục tiêu](#1-bối-cảnh--mục-tiêu)
2. [Ràng buộc từ Spec gốc](#2-ràng-buộc-từ-spec-gốc)
3. [Kiến trúc tổng thể](#3-kiến-trúc-tổng-thể)
4. [Schema: Bảng `social_accounts`](#4-schema-bảng-social_accounts)
5. [Tích hợp Trust Level](#5-tích-hợp-trust-level)
6. [Phase 0 — Nền tảng (Foundation)](#6-phase-0--nền-tảng-foundation)
7. [Phase 1 — Google OAuth (MVP)](#7-phase-1--google-oauth-mvp)
8. [Phase 2 — Facebook & LinkedIn](#8-phase-2--facebook--linkedin)
9. [Phase 3 — Profile: Link / Unlink](#9-phase-3--profile-link--unlink)
10. [Phase 4 — Security & Hardening](#10-phase-4--security--hardening)
11. [Acceptance Criteria tổng hợp](#11-acceptance-criteria-tổng-hợp)
12. [Rollback Plan](#12-rollback-plan)

---

## 1. Bối cảnh & Mục tiêu

Social Login cho phép người dùng đăng nhập / đăng ký bằng tài khoản bên thứ ba (Google, Facebook, LinkedIn) thay vì email + mật khẩu. Tính năng này:

- **Không thay thế** luồng email/password hiện tại — là phương thức **song song**
- **Không phải** phương thức xác minh danh tính (không tăng trust_level lên ≥ 2)
- **Phù hợp** nguyên tắc "email cá nhân là identity anchor" của spec gốc (Google/Facebook trả về email cá nhân)
- **Cần xử lý đặc biệt** với LinkedIn (có thể trả về email công việc)

### Phạm vi tài liệu này

| Trong scope | Ngoài scope |
|-------------|-------------|
| Đăng nhập / Đăng ký qua Google, Facebook, LinkedIn | OAuth cho API bên thứ ba (Phase 5 spec gốc) |
| Link/Unlink tài khoản social từ Profile | VNeID SSO |
| Audit trail mọi hành động OAuth | Zalo login |
| Trust level tự động từ OAuth | 2FA qua social provider |

---

## 2. Ràng buộc từ Spec gốc

### 2.1 Nguyên tắc bất biến phải tuân theo

```
users.email LUÔN LUÔN là email cá nhân.
Không bao giờ là email tổ chức.
```

**Ảnh hưởng trực tiếp đến social login:**

| Provider | Email thường trả về | Đánh giá | Xử lý |
|---------|---------------------|----------|-------|
| Google | Gmail / email cá nhân | ✅ An toàn | Dùng trực tiếp |
| Facebook | Email đăng ký Facebook (cá nhân) | ✅ Thường an toàn | Validate + fallback nếu trống |
| LinkedIn | Email đăng ký LinkedIn — **thường là email công việc** | ⚠️ Rủi ro | Bắt buộc chạy `NotOrgDomainEmail` rule |

### 2.2 Trust Level — mapping với OAuth

Spec gốc §9 định nghĩa:

| Level | Phương thức | Ý nghĩa |
|-------|------------|---------|
| 0 | Chưa xác minh | Mới tạo tài khoản |
| 1 | Email verified | Click link xác minh |
| 2 | Phone OTP | |
| 3 | CCCD OCR/Chip | |
| 4 | VNeID / Biometrics | |

**Quy tắc OAuth → trust_level:**

> OAuth provider (Google, Facebook, LinkedIn) đã xác minh email thay hệ thống.
> → Khi tạo tài khoản mới qua OAuth: **`trust_level = 1`** + **`email_verified_at = now()`** ngay lập tức.
> → KHÔNG cần gửi email verification link.

### 2.3 AccountType lifecycle không thay đổi

Tài khoản tạo qua social vẫn đi theo đúng state machine của spec gốc:

```
Đăng ký qua Google → account_type = 'free' → (HR invite) → account_type = 'org_member' → ...
```

---

## 3. Kiến trúc tổng thể

### 3.1 Cấu trúc thư mục bổ sung (AVSA)

```
Modules/Auth/
├── app/
│   ├── Actions/Auth/
│   │   └── SocialLoginAction.php          [MỚI] Find-or-create user từ OAuth profile
│   ├── Http/Controllers/
│   │   └── SocialAuthController.php       [MỚI] Redirect + Callback handler
│   └── Models/
│       └── SocialAccount.php              [MỚI] Pivot user ↔ OAuth provider
├── routes/
│   └── web.php                            [SỬA] +2 routes social
└── resources/views/
    ├── login.blade.php                    [SỬA] Thêm nút social login
    └── profile.blade.php                  [SỬA] Hiển thị + quản lý linked accounts

app/
└── Models/
    └── User.php                           [SỬA] +socialAccounts() relationship

config/
└── services.php                           [SỬA] +google, facebook, linkedin credentials

database/migrations/
└── ..._create_social_accounts_table.php   [MỚI]
```

### 3.2 Luồng tổng quát

```
[Login view]
    │
    ├─ Nút "Đăng nhập với Google"
    │       │
    │       ▼
    │   GET /auth/social/google           → SocialAuthController::redirect()
    │       │                               → Socialite::driver('google')->redirect()
    │       ▼
    │   [Google OAuth consent screen]
    │       │
    │       ▼
    │   GET /auth/social/google/callback  → SocialAuthController::callback()
    │       │
    │       ▼
    │   SocialLoginAction::run($provider, $socialUser)
    │       │
    │       ├─ Tìm social_accounts WHERE provider=google AND provider_user_id=X
    │       │       └─ Tìm thấy → Auth::login($user) → redirect dashboard
    │       │
    │       ├─ Không thấy → Validate email (NotOrgDomainEmail nếu LinkedIn)
    │       │
    │       ├─ Tìm users WHERE email = provider_email
    │       │       └─ Tìm thấy → Link social_account → Auth::login($user) → redirect
    │       │
    │       └─ Không thấy → Tạo user mới (trust_level=1) → Link → Auth::login → redirect
    │
    └─ Nút "Đăng nhập với Facebook" / "Đăng nhập với LinkedIn"
            └─ Tương tự, provider thay đổi
```

### 3.3 Dependency

```
composer.json          laravel/socialite ^5.x
config/services.php    credentials 3 providers
SocialAccount model    pivot table
SocialLoginAction      core business logic
SocialAuthController   HTTP layer (redirect + callback)
AuthServiceProvider    đăng ký routes + middleware
```

---

## 4. Schema: Bảng `social_accounts`

```sql
CREATE TABLE social_accounts (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Liên kết với user
    user_id             BIGINT UNSIGNED NOT NULL,

    -- Provider
    provider            VARCHAR(20) NOT NULL
                        COMMENT 'google | facebook | linkedin',
    provider_user_id    VARCHAR(255) NOT NULL
                        COMMENT 'ID từ provider — dùng để lookup, không thay đổi',

    -- Thông tin từ provider (snapshot tại thời điểm link, cập nhật mỗi lần login)
    provider_email      VARCHAR(255) NULL
                        COMMENT 'Email provider trả về — audit trail, KHÔNG phải users.email',
    provider_name       VARCHAR(255) NULL,
    provider_avatar     VARCHAR(500) NULL,

    -- Token (lưu để có thể revoke / refresh sau này — Phase 4)
    access_token        TEXT NULL,
    refresh_token       TEXT NULL,
    token_expires_at    TIMESTAMP NULL,

    -- Audit
    linked_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                        COMMENT 'Lần đầu link tài khoản này',
    last_used_at        TIMESTAMP NULL
                        COMMENT 'Lần cuối dùng social account này để đăng nhập',

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_provider_user    (provider, provider_user_id),
    INDEX      idx_sa_user_id      (user_id),
    INDEX      idx_sa_provider     (provider),

    CONSTRAINT fk_sa_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Ghi chú thiết kế:**

- `provider_email` ≠ `users.email` — chỉ để audit, không dùng để tìm user
- `provider_user_id` là khóa lookup chính (stable ID từ OAuth provider)
- Một `users` row có thể có nhiều social_accounts (Google + Facebook + LinkedIn)
- Khi user bị xóa → cascade delete social_accounts

---

## 5. Tích hợp Trust Level

### 5.1 Khi TẠO tài khoản mới qua OAuth

```php
// SocialLoginAction — luồng tạo user mới
$user = User::create([
    'name'               => $socialUser->getName(),
    'email'              => $email,                 // đã validate
    'password'           => null,                   // không có password
    'account_type'       => AccountType::Free,
    'trust_level'        => 1,                      // provider đã verify email
    'email_verified_at'  => now(),                  // đánh dấu đã verify
]);
```

### 5.2 Khi LINK tài khoản social vào user HIỆN CÓ

```php
// KHÔNG thay đổi trust_level của user đã có
// trust_level chỉ tăng, không giảm
// Nếu user hiện tại trust_level = 0 (chưa verify email):
//   → cập nhật trust_level = 1, email_verified_at = now()
// Nếu trust_level >= 1 → không thay đổi
```

### 5.3 Trust level KHÔNG tăng quá 1 từ social

Social login chỉ chứng minh ownership email (trust_level 1).
Phone OTP, CCCD, VNeID vẫn cần quy trình riêng theo spec gốc §9.

---

## 6. Phase 0 — Nền tảng (Foundation)

**Mục tiêu:** Cài đặt package, cấu hình, migration, model — không có UI.
**Thời gian ước tính:** 1 ngày
**Phụ thuộc:** Không có

### 6.1 Cài đặt package

```bash
composer require laravel/socialite
```

**Yêu cầu:** `laravel/socialite ^5.x` — tương thích Laravel 13.

### 6.2 Cấu hình `config/services.php`

Thêm vào cuối file, sau block `turnstile`:

```php
/*
|--------------------------------------------------------------------------
| Social OAuth Providers
|--------------------------------------------------------------------------
| Credentials lấy từ:
|   Google:   https://console.cloud.google.com → Credentials → OAuth 2.0
|   Facebook: https://developers.facebook.com → My Apps → Settings
|   LinkedIn: https://www.linkedin.com/developers/apps → Auth tab
|
| Callback URL pattern: {APP_URL}/auth/social/{provider}/callback
*/
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('GOOGLE_REDIRECT_URI', '/auth/social/google/callback'),
],

'facebook' => [
    'client_id'     => env('FACEBOOK_CLIENT_ID'),
    'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
    'redirect'      => env('FACEBOOK_REDIRECT_URI', '/auth/social/facebook/callback'),
],

'linkedin-openid' => [
    'client_id'     => env('LINKEDIN_CLIENT_ID'),
    'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
    'redirect'      => env('LINKEDIN_REDIRECT_URI', '/auth/social/linkedin/callback'),
],
```

> **Lưu ý:** LinkedIn dùng driver `linkedin-openid` (OpenID Connect) từ Socialite v5+,
> không dùng `linkedin` (OAuth 2.0 cũ đã deprecated từ LinkedIn API v2).

### 6.3 Biến môi trường — `.env.example`

```dotenv
# ── Social OAuth ─────────────────────────────────────────────────────────────
# Google: https://console.cloud.google.com → Credentials → OAuth 2.0 Client ID
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
# GOOGLE_REDIRECT_URI=/auth/social/google/callback

# Facebook: https://developers.facebook.com → App → Settings → Basic
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
# FACEBOOK_REDIRECT_URI=/auth/social/facebook/callback

# LinkedIn (OpenID Connect): https://www.linkedin.com/developers/apps
LINKEDIN_CLIENT_ID=
LINKEDIN_CLIENT_SECRET=
# LINKEDIN_REDIRECT_URI=/auth/social/linkedin/callback
```

### 6.4 Migration

**File:** `database/migrations/generated/YYYY_MM_DD_HHMMSS_create_social_accounts_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('provider', 20);
            $table->string('provider_user_id', 255);

            $table->string('provider_email', 255)->nullable();
            $table->string('provider_name', 255)->nullable();
            $table->string('provider_avatar', 500)->nullable();

            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            $table->timestamp('linked_at')->useCurrent();
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->unique(['provider', 'provider_user_id'], 'uq_provider_user');
            $table->index('user_id', 'idx_sa_user_id');
            $table->index('provider', 'idx_sa_provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
```

### 6.5 Model `SocialAccount`

**File:** `Modules/Auth/app/Models/SocialAccount.php`

```php
<?php

namespace Modules\Auth\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'provider_email',
        'provider_name',
        'provider_avatar',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'linked_at',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'linked_at'        => 'datetime',
            'last_used_at'     => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### 6.6 Cập nhật `User` model

**File:** `app/Models/User.php` — thêm relationship:

```php
use Modules\Auth\Models\SocialAccount;

// Thêm vào phần Relationships:
public function socialAccounts(): HasMany
{
    return $this->hasMany(SocialAccount::class);
}
```

### 6.7 Acceptance Criteria Phase 0

- [ ] `social_accounts` table tồn tại sau `php artisan migrate`
- [ ] `SocialAccount` model quan hệ đúng với `User`
- [ ] `config/services.php` có 3 provider keys
- [ ] `.env.example` có 6 env vars mới
- [ ] `composer show laravel/socialite` cho kết quả

---

## 7. Phase 1 — Google OAuth (MVP)

**Mục tiêu:** Đăng nhập / Đăng ký qua Google hoạt động end-to-end.
**Thời gian ước tính:** 1-2 ngày
**Phụ thuộc:** Phase 0 hoàn thành

### 7.1 `SocialLoginResult` — Value Object

Controller cần biết **tại sao** user được login để flash đúng message. Dùng value object nhỏ thay vì trả thẳng `User`:

**File:** `Modules/Auth/app/Actions/Auth/SocialLoginResult.php`

```php
<?php

namespace Modules\Auth\Actions\Auth;

use App\Models\User;

final readonly class SocialLoginResult
{
    public function __construct(
        public User $user,
        public bool $isNewUser,   // true: vừa tạo tài khoản mới qua OAuth
        public bool $isNewLink,   // true: vừa link lần đầu vào tài khoản đã có
                                  // false: đã link từ trước → login bình thường
    ) {}
}
```

**Ba trạng thái và message tương ứng:**

| `isNewUser` | `isNewLink` | Ý nghĩa | Flash message |
|-------------|-------------|---------|---------------|
| `true` | `true` | Tài khoản mới tạo qua OAuth | "Chào mừng! Tài khoản đã được tạo." |
| `false` | `true` | Tài khoản cũ, lần đầu dùng Google | "Đã liên kết tài khoản Google. Lần sau bạn có thể đăng nhập bằng Google." |
| `false` | `false` | Đã link từ trước, login lại | Không cần thông báo |

---

### 7.2 `SocialLoginAction`

**File:** `Modules/Auth/app/Actions/Auth/SocialLoginAction.php`

```php
<?php

namespace Modules\Auth\Actions\Auth;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialUser;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Models\SocialAccount;

class SocialLoginAction
{
    use AsAction;

    // Các provider được phép — thêm 'facebook', 'linkedin' khi Phase 2 xong
    private const ALLOWED_PROVIDERS = ['google'];

    public function handle(string $provider, SocialUser $socialUser): SocialLoginResult
    {
        $this->assertProviderAllowed($provider);

        // 1. Tìm theo provider_user_id (fastest path — stable ID từ provider)
        $social = SocialAccount::where('provider', $provider)
            ->where('provider_user_id', $socialUser->getId())
            ->with('user')
            ->first();

        if ($social) {
            $social->update([
                'access_token'     => $socialUser->token,
                'refresh_token'    => $socialUser->refreshToken,
                'token_expires_at' => $socialUser->expiresIn
                    ? now()->addSeconds($socialUser->expiresIn) : null,
                'last_used_at'     => now(),
                'provider_name'    => $socialUser->getName(),
                'provider_avatar'  => $socialUser->getAvatar(),
            ]);

            // Đã link từ trước → login thông thường, không cần thông báo
            return new SocialLoginResult($social->user, isNewUser: false, isNewLink: false);
        }

        // 2. Validate email từ provider
        $email = $this->resolveEmail($provider, $socialUser);

        // 3. Tìm user hiện có theo email → link thêm social account
        $user = User::where('email', $email)->first();

        if ($user) {
            $this->linkSocialAccount($user, $provider, $socialUser, $email);

            // Nâng trust_level nếu user chưa verify email
            // (Google đã xác minh email thay hệ thống → upgrade trust_level lên 1)
            if (! $user->email_verified_at) {
                $user->update([
                    'email_verified_at' => now(),
                    'trust_level'       => max($user->trust_level, 1),
                ]);
            }

            // Tài khoản cũ, link lần đầu → thông báo để user biết
            return new SocialLoginResult($user, isNewUser: false, isNewLink: true);
        }

        // 4. Tạo user mới
        $newUser = $this->createUserFromSocial($provider, $socialUser, $email);

        return new SocialLoginResult($newUser, isNewUser: true, isNewLink: true);
    }

    private function assertProviderAllowed(string $provider): void
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS)) {
            throw ValidationException::withMessages([
                'provider' => "Provider '{$provider}' chưa được hỗ trợ.",
            ]);
        }
    }

    private function resolveEmail(string $provider, SocialUser $socialUser): string
    {
        $email = $socialUser->getEmail();

        if (blank($email)) {
            throw ValidationException::withMessages([
                'email' => 'Tài khoản ' . ucfirst($provider) . ' không có địa chỉ email. '
                         . 'Vui lòng đăng nhập bằng email và mật khẩu.',
            ]);
        }

        return Str::lower($email);
    }

    private function linkSocialAccount(
        User $user,
        string $provider,
        SocialUser $socialUser,
        string $email,
    ): void {
        SocialAccount::create([
            'user_id'          => $user->id,
            'provider'         => $provider,
            'provider_user_id' => $socialUser->getId(),
            'provider_email'   => $email,
            'provider_name'    => $socialUser->getName(),
            'provider_avatar'  => $socialUser->getAvatar(),
            'access_token'     => $socialUser->token,
            'refresh_token'    => $socialUser->refreshToken,
            'token_expires_at' => $socialUser->expiresIn
                ? now()->addSeconds($socialUser->expiresIn) : null,
            'linked_at'        => now(),
            'last_used_at'     => now(),
        ]);
    }

    private function createUserFromSocial(
        string $provider,
        SocialUser $socialUser,
        string $email,
    ): User {
        $user = User::create([
            'name'              => $socialUser->getName() ?? Str::before($email, '@'),
            'email'             => $email,
            'password'          => null,        // không có password — chỉ login qua social
            'account_type'      => AccountType::Free,
            'trust_level'       => 1,           // provider đã xác minh email
            'email_verified_at' => now(),
        ]);

        $this->linkSocialAccount($user, $provider, $socialUser, $email);

        return $user;
    }
}
```

### 7.3 `SocialAuthController`

**File:** `Modules/Auth/app/Http/Controllers/SocialAuthController.php`

```php
<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Modules\Auth\Actions\Auth\SocialLoginAction;
use Throwable;

class SocialAuthController
{
    // Driver map: route param → Socialite driver name
    private const DRIVER_MAP = [
        'google'   => 'google',
        'facebook' => 'facebook',
        'linkedin' => 'linkedin-openid',
    ];

    // Flash messages theo kết quả từ SocialLoginResult
    private const LOGIN_MESSAGES = [
        'new_user' => 'Chào mừng! Tài khoản đã được tạo thành công.',
        'new_link' => 'Tài khoản :provider đã được liên kết. Lần sau bạn có thể đăng nhập bằng :provider.',
    ];

    public function redirect(string $provider): RedirectResponse
    {
        $driver = $this->resolveDriver($provider);

        return Socialite::driver($driver)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $driver = $this->resolveDriver($provider);

        try {
            $socialUser = Socialite::driver($driver)->user();
        } catch (Throwable) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Xác thực ' . ucfirst($provider) . ' thất bại. Vui lòng thử lại.']);
        }

        try {
            $result = SocialLoginAction::run($provider, $socialUser);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('login')
                ->withErrors($e->errors());
        }

        $user = $result->user;

        // Kiểm tra trạng thái tài khoản — phòng trường hợp tài khoản bị khóa/vô hiệu hóa
        // (SocialLoginAction chỉ xử lý identity, không xử lý authorization)
        if (! $user->is_active) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Tài khoản đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.']);
        }

        if (! $user->account_type->canLogin()) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.']);
        }

        Auth::login($user, remember: true);

        // Flash message dựa trên SocialLoginResult — không thông báo khi login lại bình thường
        $message = match (true) {
            $result->isNewUser => self::LOGIN_MESSAGES['new_user'],
            $result->isNewLink => str_replace(
                ':provider',
                ucfirst($provider),
                self::LOGIN_MESSAGES['new_link']
            ),
            default            => null,
        };

        return redirect()
            ->intended(route('backend.dashboard'))
            ->with('success', $message);
    }

    private function resolveDriver(string $provider): string
    {
        $driver = self::DRIVER_MAP[$provider] ?? null;

        if (! $driver) {
            abort(404);
        }

        return $driver;
    }
}
```

### 7.4 Routes

**File:** `Modules/Auth/routes/web.php` — thêm vào group `auth.`:

```php
use Modules\Auth\Http\Controllers\SocialAuthController;

// Ngoài group middleware(['auth']), thêm routes public:
Route::prefix('auth/social')->name('auth.social.')->group(function () {
    Route::get('{provider}',          [SocialAuthController::class, 'redirect'])->name('redirect');
    Route::get('{provider}/callback', [SocialAuthController::class, 'callback'])->name('callback');
});
```

**Lưu ý:**
- Route này KHÔNG dùng middleware `auth` (user chưa login)
- `{provider}` sẽ validate trong controller (abort 404 nếu không hợp lệ)

### 7.5 Cập nhật Login View

**File:** `Modules/Auth/resources/views/login.blade.php`

Thêm vào trước thẻ `</div>` cuối của `card-body`, sau form đăng nhập:

```blade
{{-- Social Login --}}
@if (config('services.google.client_id'))
    <div class="divider text-xs text-base-content/40 my-0">Hoặc tiếp tục với</div>

    <div class="flex flex-col gap-2">
        <a href="{{ route('auth.social.redirect', 'google') }}"
           class="btn btn-outline gap-2 w-full">
            {{-- Google icon SVG --}}
            <svg class="w-5 h-5" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Đăng nhập với Google
        </a>
    </div>
@endif
```

### 7.6 Acceptance Criteria Phase 1

**Luồng cơ bản:**
- [ ] Click "Đăng nhập với Google" → redirect đến Google consent screen
- [ ] Sau khi approve → redirect về dashboard
- [ ] Từ chối quyền ở Google → redirect về `/login` với error message tiếng Việt

**Trạng thái tài khoản:**
- [ ] User mới tạo qua Google: `trust_level = 1`, `email_verified_at != null`, `password = null`
- [ ] `social_accounts` bảng có row đúng sau mỗi lần login
- [ ] `last_used_at` cập nhật mỗi lần login lại

**Email collision — tài khoản đã tồn tại:**
- [ ] User có tài khoản email/password `abc@gmail.com` → login Google cùng email → vào đúng tài khoản cũ
- [ ] Sau login đó: `social_accounts` có thêm row mới cho `google`
- [ ] Flash message: "Tài khoản Google đã được liên kết. Lần sau bạn có thể đăng nhập bằng Google."
- [ ] Lần login Google tiếp theo: không có flash message (đã link rồi)
- [ ] User có `email_verified_at = null` → sau khi link Google: `email_verified_at` được set, `trust_level` nâng lên 1

**Tài khoản mới qua Google:**
- [ ] Flash message: "Chào mừng! Tài khoản đã được tạo thành công."

**Tài khoản bị khóa:**
- [ ] Tài khoản `Suspended` login qua Google → redirect `/login` + error "Tài khoản đã bị khóa"
- [ ] Tài khoản `is_active = false` login qua Google → redirect `/login` + error "Tài khoản đã bị vô hiệu hóa"

---

## 8. Phase 2 — Facebook & LinkedIn

**Mục tiêu:** Mở rộng thêm Facebook và LinkedIn với xử lý edge case.
**Thời gian ước tính:** 1-2 ngày
**Phụ thuộc:** Phase 1 hoàn thành và ổn định

### 8.1 Facebook — Edge case: không có email

Facebook cho phép đăng ký bằng số điện thoại, không bắt buộc email.
Khi `$socialUser->getEmail()` trả về `null`:

```php
// Trong SocialLoginAction::resolveEmail() — đã xử lý:
if (blank($email)) {
    throw ValidationException::withMessages([
        'email' => 'Tài khoản Facebook không có địa chỉ email. '
                 . 'Vui lòng đăng nhập bằng email và mật khẩu.',
    ]);
}
```

**Yêu cầu thêm khi cấu hình Facebook App:**
- Permissions cần request: `email` (không phải default)
- Trong `SocialAuthController::redirect()` — với Facebook cần scope:

```php
// SocialAuthController::redirect() — cập nhật:
return match ($provider) {
    'facebook' => Socialite::driver($driver)->scopes(['email'])->redirect(),
    default    => Socialite::driver($driver)->redirect(),
};
```

### 8.2 LinkedIn — Rule: `NotOrgDomainEmail`

LinkedIn thường bind với email công việc → cần validate trước khi tạo/link user.

**Tạo Rule:** `Modules/Auth/app/Rules/NotOrgDomainEmail.php`

```php
<?php

namespace Modules\Auth\Rules;

use App\Shared\Tenancy\Models\Organization;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotOrgDomainEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domain = Str::after($value, '@');

        $isOrgDomain = Organization::where('email_domain', $domain)
            ->where('status', 'active')
            ->exists();

        if ($isOrgDomain) {
            $fail(
                "Email @{$domain} là email tổ chức. " .
                "Vui lòng dùng email cá nhân (Gmail, Yahoo, Outlook cá nhân...) " .
                "hoặc đổi email chính trong cài đặt LinkedIn."
            );
        }
    }
}
```

**Tích hợp vào `SocialLoginAction`:**

```php
private function resolveEmail(string $provider, SocialUser $socialUser): string
{
    $email = $socialUser->getEmail();

    if (blank($email)) {
        throw ValidationException::withMessages([
            'email' => 'Tài khoản ' . ucfirst($provider) . ' không có địa chỉ email. '
                     . 'Vui lòng đăng nhập bằng email và mật khẩu.',
        ]);
    }

    $email = Str::lower($email);

    // LinkedIn: bắt buộc kiểm tra domain tổ chức
    if ($provider === 'linkedin') {
        $rule = new NotOrgDomainEmail();
        $failed = false;
        $rule->validate('email', $email, function (string $msg) use (&$failed) {
            $failed = $msg;
        });

        if ($failed) {
            throw ValidationException::withMessages(['email' => $failed]);
        }
    }

    return $email;
}
```

### 8.3 Mở rộng `ALLOWED_PROVIDERS`

```php
// SocialLoginAction.php
private const ALLOWED_PROVIDERS = ['google', 'facebook', 'linkedin'];
```

### 8.4 Cập nhật Login View

Thêm nút Facebook và LinkedIn vào phần social login:

```blade
<div class="flex flex-col gap-2">
    @if (config('services.google.client_id'))
    <a href="{{ route('auth.social.redirect', 'google') }}" class="btn btn-outline gap-2 w-full">
        {{-- Google SVG --}} Đăng nhập với Google
    </a>
    @endif

    @if (config('services.facebook.client_id'))
    <a href="{{ route('auth.social.redirect', 'facebook') }}" class="btn btn-outline gap-2 w-full">
        {{-- Facebook SVG --}} Đăng nhập với Facebook
    </a>
    @endif

    @if (config('services.linkedin-openid.client_id'))
    <a href="{{ route('auth.social.redirect', 'linkedin') }}" class="btn btn-outline gap-2 w-full">
        {{-- LinkedIn SVG --}} Đăng nhập với LinkedIn
    </a>
    @endif
</div>
```

> Nút chỉ hiện khi `client_id` được cấu hình — keys-as-switch, nhất quán với Turnstile.

### 8.5 Acceptance Criteria Phase 2

- [ ] Facebook login hoạt động khi tài khoản có email
- [ ] Facebook không có email → redirect về login với thông báo rõ ràng tiếng Việt
- [ ] LinkedIn login hoạt động với email cá nhân (non-org domain)
- [ ] LinkedIn email có domain trùng org đang active → block với message giải thích
- [ ] Nút social chỉ hiện khi provider đã được cấu hình credentials

---

## 9. Phase 3 — Profile: Link / Unlink

**Mục tiêu:** User quản lý các social account đã link từ trang Profile.
**Thời gian ước tính:** 1 ngày
**Phụ thuộc:** Phase 1 + 2 hoàn thành

### 9.1 Luồng Link thêm account

User đã đăng nhập (bằng email/password) muốn link thêm Google:

```
Profile page → Nút "Kết nối Google"
    → GET /auth/social/google (với user đã authenticated)
    → SocialAuthController::redirect() (không thay đổi)
    → callback() → SocialLoginAction::run()
        → Tìm thấy users.email = google_email → link social_account
        → Auth::login() lại (refresh session) → redirect Profile với success
```

**Cần xử lý trong callback:** Nếu user đã đăng nhập, không login lại mà chỉ link:

```php
// SocialAuthController::callback() — cập nhật
$user = SocialLoginAction::run($provider, $socialUser);

if (Auth::check() && Auth::id() !== $user->id) {
    // User đã login nhưng social email thuộc user khác → conflict
    return redirect()->route('auth.profile')
        ->withErrors(['email' => 'Email này đã được liên kết với tài khoản khác.']);
}

if (! Auth::check()) {
    Auth::login($user, remember: true);
}

$redirectTo = Auth::check() ? route('auth.profile') : route('backend.dashboard');
return redirect()->intended($redirectTo)->with('success', 'Liên kết tài khoản thành công.');
```

### 9.2 Luồng Unlink

**Rule bảo vệ:** Không cho unlink nếu:
- Đây là phương thức đăng nhập DUY NHẤT (`password = null` và chỉ có 1 social account)

```php
// Thêm vào SocialAuthController:
public function unlink(string $provider): RedirectResponse
{
    $user = Auth::user();

    $social = $user->socialAccounts()->where('provider', $provider)->firstOrFail();

    // Guard: không được unlink nếu không có cách đăng nhập khác
    $hasPassword      = ! is_null($user->password);
    $otherSocialCount = $user->socialAccounts()
        ->where('provider', '!=', $provider)
        ->count();

    if (! $hasPassword && $otherSocialCount === 0) {
        return back()->withErrors([
            'social' => 'Không thể bỏ liên kết — đây là phương thức đăng nhập duy nhất. '
                      . 'Hãy đặt mật khẩu trước.',
        ]);
    }

    $social->delete();

    return back()->with('success', 'Đã bỏ liên kết tài khoản ' . ucfirst($provider) . '.');
}
```

**Route bổ sung:**

```php
Route::middleware('auth')->delete('auth/social/{provider}', [SocialAuthController::class, 'unlink'])
    ->name('auth.social.unlink');
```

### 9.3 Cập nhật Profile View

**File:** `Modules/Auth/resources/views/profile.blade.php`

Thêm section "Tài khoản liên kết":

```blade
{{-- Linked Social Accounts --}}
<div class="card bg-base-100 shadow">
    <div class="card-body">
        <h2 class="card-title text-base">Tài khoản liên kết</h2>

        @foreach (['google' => 'Google', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn'] as $provider => $label)
            @php $linked = $user->socialAccounts->firstWhere('provider', $provider) @endphp

            <div class="flex items-center justify-between py-2 border-b last:border-0">
                <span class="font-medium text-sm">{{ $label }}</span>

                @if ($linked)
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-base-content/60">
                            {{ $linked->provider_email }}
                        </span>
                        <form method="POST"
                              action="{{ route('auth.social.unlink', $provider) }}"
                              onsubmit="return confirm('Bỏ liên kết {{ $label }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-ghost text-error">Bỏ liên kết</button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('auth.social.redirect', $provider) }}"
                       class="btn btn-xs btn-outline">
                        Kết nối
                    </a>
                @endif
            </div>
        @endforeach
    </div>
</div>
```

### 9.4 Acceptance Criteria Phase 3

- [ ] User có password → có thể link thêm Google/Facebook/LinkedIn từ Profile
- [ ] Link thành công → hiện trong danh sách với email provider
- [ ] Unlink khi còn password → thành công
- [ ] Unlink social DUY NHẤT khi `password = null` → bị block với message rõ ràng
- [ ] Conflict email (social email thuộc user khác) → error message rõ ràng

---

## 10. Phase 4 — Security & Hardening

**Mục tiêu:** Tăng cường bảo mật, audit trail, rate limiting.
**Thời gian ước tính:** 1 ngày
**Phụ thuộc:** Phase 1-3 hoàn thành

### 10.1 Audit Logging

Tích hợp với `LogSuccessfulLogin` listener hiện có — thêm `provider` vào metadata:

```php
// Cập nhật LogSuccessfulLogin listener:
ActivityLogger::info('Auth', 'login', $event->user, [
    'ip'         => request()->ip(),
    'user_agent' => request()->userAgent(),
    'remember'   => $event->remember,
    'method'     => session('auth.method', 'password'), // 'google' | 'facebook' | 'linkedin' | 'password'
]);
```

Set session trước khi `Auth::login()` trong callback:

```php
// SocialAuthController::callback()
session(['auth.method' => $provider]);
Auth::login($user, remember: true);
```

### 10.2 Rate Limiting cho OAuth callback

Ngăn flood callback endpoint (CSRF state đã có nhưng thêm rate limit để an toàn hơn):

```php
// app/Providers/FortifyServiceProvider.php — thêm:
RateLimiter::for('social-auth', function (Request $request) {
    return Limit::perMinute(10)->by($request->ip());
});
```

```php
// SocialAuthController — thêm middleware vào constructor:
public function __construct()
{
    $this->middleware('throttle:social-auth')->only('callback');
}
```

### 10.3 Bảo vệ token lưu trữ

**Hiện tại:** `access_token` lưu plaintext trong DB.

**Cải tiến:** Encrypt token trước khi lưu.

```php
// SocialAccount model — thêm cast:
protected function casts(): array
{
    return [
        'access_token'    => 'encrypted',
        'refresh_token'   => 'encrypted',
        'token_expires_at'=> 'datetime',
        'linked_at'       => 'datetime',
        'last_used_at'    => 'datetime',
    ];
}
```

> Dùng Laravel's built-in `encrypted` cast — tự động encrypt/decrypt qua `APP_KEY`.

### 10.4 Cleanup tokens cũ (optional scheduled job)

```php
// Thêm vào routes/console.php:
Schedule::call(function () {
    SocialAccount::where('token_expires_at', '<', now()->subDays(30))->update([
        'access_token'  => null,
        'refresh_token' => null,
    ]);
})->weekly();
```

### 10.5 Acceptance Criteria Phase 4

- [ ] Mỗi lần login qua social → ActivityLog có `method = provider_name`
- [ ] Flood callback endpoint (>10 req/phút từ 1 IP) → 429 Too Many Requests
- [ ] `access_token` trong DB ở dạng encrypted (không đọc được plaintext)
- [ ] Token hết hạn > 30 ngày → bị cleanup tự động

---

## 11. Acceptance Criteria Tổng hợp

### Happy Path

| # | Scenario | Expected |
|---|---------|---------|
| 1 | User mới → Login Google lần đầu | Tạo user (trust_level=1), social_account row, vào dashboard |
| 2 | User có email/password → Login Google cùng email | Không tạo user mới, link social_account, vào dashboard |
| 3 | User đã link Google → Login Google lần 2 | Login trực tiếp, `last_used_at` updated |
| 4 | User link Google từ Profile | social_account row mới, hiện trong danh sách |
| 5 | User unlink Google (còn password) | social_account xóa, không còn nút "Bỏ liên kết" |

### Edge Cases & Security

| # | Scenario | Expected |
|---|---------|---------|
| 6 | Facebook không có email | Error: "Tài khoản Facebook không có địa chỉ email" |
| 7 | LinkedIn email là org domain | Error: "Email @domain là email tổ chức..." |
| 8 | Google email đã dùng bởi user khác | Error: "Email này đã được liên kết với tài khoản khác" |
| 9 | Unlink social DUY NHẤT, password=null | Block: "Hãy đặt mật khẩu trước" |
| 10 | OAuth callback bị tamper / state mismatch | Socialite throw → redirect login + error |
| 11 | Tài khoản `Suspended` login qua Google | Error: "Tài khoản đã bị khóa" (từ LoginUserAction) |

> **Note #11:** Khi user đã có account và bị Suspended, `SocialLoginAction` trả về user đó,
> sau đó `SocialAuthController` gọi `Auth::login()`. Cần thêm check:

```php
// SocialAuthController::callback() — thêm sau SocialLoginAction::run():
if (! $user->account_type->canLogin() || ! $user->is_active) {
    return redirect()->route('login')
        ->withErrors(['email' => $user->isSuspended()
            ? 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.'
            : 'Tài khoản đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.'
        ]);
}
```

---

## 12. Rollback Plan

### Rollback từng Phase

| Phase | Rollback |
|-------|---------|
| 0 | `php artisan migrate:rollback` → drop `social_accounts`, xóa config keys |
| 1 | Xóa routes social khỏi web.php, ẩn nút Google khỏi login view |
| 2 | Xóa nút Facebook/LinkedIn, revert `ALLOWED_PROVIDERS` về `['google']` |
| 3 | Xóa section profile, xóa unlink route |
| 4 | Revert encrypted cast (cần migration decrypt data trước) |

### Feature Flag (nếu cần toggle nhanh)

```php
// config/services.php — keys-as-switch:
// Nếu muốn tắt social login hoàn toàn → bỏ trống tất cả GOOGLE/FACEBOOK/LINKEDIN client_id
// Nút trên UI sẽ tự ẩn (điều kiện @if config('services.google.client_id'))
```

---

## Appendix: Thứ tự file cần tạo/sửa theo Phase

```
Phase 0:
  [MỚI]  database/migrations/..._create_social_accounts_table.php
  [MỚI]  Modules/Auth/app/Models/SocialAccount.php
  [SỬA]  app/Models/User.php                      (+socialAccounts relationship)
  [SỬA]  config/services.php                       (+3 provider blocks)
  [SỬA]  .env.example                              (+6 env vars)
  [CMD]  composer require laravel/socialite

Phase 1:
  [MỚI]  Modules/Auth/app/Actions/Auth/SocialLoginResult.php  (value object: isNewUser, isNewLink)
  [MỚI]  Modules/Auth/app/Actions/Auth/SocialLoginAction.php  (trả SocialLoginResult)
  [MỚI]  Modules/Auth/app/Http/Controllers/SocialAuthController.php
  [SỬA]  Modules/Auth/routes/web.php               (+2 routes)
  [SỬA]  Modules/Auth/resources/views/login.blade.php (+Google button)

Phase 2:
  [MỚI]  Modules/Auth/app/Rules/NotOrgDomainEmail.php
  [SỬA]  Modules/Auth/app/Actions/Auth/SocialLoginAction.php (LinkedIn validate + ALLOWED_PROVIDERS)
  [SỬA]  Modules/Auth/app/Http/Controllers/SocialAuthController.php (Facebook scope)
  [SỬA]  Modules/Auth/resources/views/login.blade.php (+Facebook, LinkedIn buttons)

Phase 3:
  [SỬA]  Modules/Auth/app/Http/Controllers/SocialAuthController.php (+unlink method, callback guard)
  [SỬA]  Modules/Auth/routes/web.php               (+unlink route)
  [SỬA]  Modules/Auth/resources/views/profile.blade.php (+linked accounts section)

Phase 4:
  [SỬA]  Modules/Auth/app/Models/SocialAccount.php (+encrypted cast)
  [SỬA]  Modules/Auth/app/Listeners/LogSuccessfulLogin.php (+method field)
  [SỬA]  Modules/Auth/app/Http/Controllers/SocialAuthController.php (+session method, account_status check)
  [SỬA]  app/Providers/FortifyServiceProvider.php   (+social-auth rate limiter)
  [SỬA]  routes/console.php                         (+weekly token cleanup)
```
