<?php

namespace Modules\Auth\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Modules\Auth\Actions\Auth\LoginUserAction;
use Modules\Auth\Actions\Auth\RegisterUserAction;
use Modules\Auth\Actions\Auth\ResetPasswordAction;
use Modules\Auth\Actions\Auth\UpdateProfileAction;
use Modules\Auth\Fortify\ValidateTurnstile;
use Modules\Auth\Http\Responses\LoginResponse;
use Modules\Auth\Http\Responses\LogoutResponse;
use Modules\Auth\Http\Responses\RegisterResponse;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AuthServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Auth';
    protected string $nameLower = 'auth';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register(); // đăng ký EventServiceProvider + RouteServiceProvider

        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->bootFortifyActions();
        $this->bootFortifyViews();
        $this->bootFortifyPipeline();
    }

    // ── Override Fortify actions với module Actions ────────────────────
    // Dùng app()->booted() (Application::booted) thay vì $this->booted() (ServiceProvider::booted)
    // vì ServiceProvider::booted() chỉ chạy ngay sau provider đó, không phải sau TẤT CẢ providers.
    // App\Providers\FortifyServiceProvider boot sau module này nên phải override bằng Application::booted().
    private function bootFortifyActions(): void
    {
        $this->app->booted(function () {
            Fortify::authenticateUsing(fn (Request $request) => LoginUserAction::run($request));
            Fortify::createUsersUsing(RegisterUserAction::class);
            Fortify::resetUserPasswordsUsing(ResetPasswordAction::class);
            Fortify::updateUserProfileInformationUsing(UpdateProfileAction::class);
        });
    }

    // ── Fortify views → module Auth views ─────────────────────────────
    private function bootFortifyViews(): void
    {
        Fortify::loginView(fn () => view('auth::login'));
        Fortify::registerView(fn () => view('auth::register'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth::passwords.email'));
        Fortify::resetPasswordView(
            fn ($request) => view('auth::passwords.reset', ['request' => $request])
        );
        // verification.notice view được trả trực tiếp bởi EmailVerificationController
        // (Features::emailVerification() đã tắt trong config/fortify.php).

        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Xác minh email của bạn — ' . config('app.name'))
                ->greeting('Xin chào ' . ($notifiable->name ?? 'bạn') . ' 👋')
                ->line('Cảm ơn bạn đã đăng ký tài khoản tại **' . config('app.name') . '**.')
                ->line('Nhấn vào nút **"Xác minh địa chỉ email"** bên dưới để hoàn tất xác minh và kích hoạt tài khoản của bạn.')
                ->action('Xác minh địa chỉ email', $url)
                ->line('⏱️ Đường liên kết này có hiệu lực trong **60 phút** kể từ khi email được gửi.')
                ->line('Nếu bạn không tạo tài khoản này, bạn có thể bỏ qua email — không cần thực hiện thêm hành động nào.')
                ->salutation('Trân trọng,  ' . "\n" . config('app.name'));
        });
    }

    // ── Fortify authentication pipeline ───────────────────────────────
    // Thứ tự: throttle → turnstile → 2FA check → attempt → session
    private function bootFortifyPipeline(): void
    {
        Fortify::authenticateThrough(function () {
            return array_filter([
                config('fortify.limiters.login') ? EnsureLoginIsNotThrottled::class : null,
                ValidateTurnstile::class,
                Features::enabled(Features::twoFactorAuthentication())
                    ? RedirectIfTwoFactorAuthenticatable::class
                    : null,
                AttemptToAuthenticate::class,
                PrepareAuthenticatedSession::class,
            ]);
        });
    }
}
