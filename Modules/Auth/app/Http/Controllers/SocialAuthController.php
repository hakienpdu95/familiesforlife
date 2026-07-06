<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Modules\Auth\Actions\Auth\SocialLoginAction;
use Throwable;

class SocialAuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:social-auth')->only('redirect', 'callback');
    }

    private const LOGIN_MESSAGES = [
        'new_user' => 'Chào mừng! Tài khoản đã được tạo thành công.',
        'new_link' => 'Tài khoản :provider đã được liên kết. Lần sau bạn có thể đăng nhập bằng :provider.',
    ];

    public function redirect(string $provider): RedirectResponse
    {
        $driver = $this->resolveDriver($provider);

        // Facebook không request email by default — phải khai báo scope rõ ràng
        return match ($provider) {
            'facebook' => Socialite::driver($driver)->scopes(['email'])->redirect(),
            default    => Socialite::driver($driver)->redirect(),
        };
    }

    public function callback(string $provider): RedirectResponse
    {
        $driver       = $this->resolveDriver($provider);
        $wasLoggedIn  = Auth::check();

        try {
            $socialUser = Socialite::driver($driver)->user();
        } catch (Throwable) {
            return redirect()->route($wasLoggedIn ? 'auth.profile' : 'login')
                ->withErrors(['social' => 'Xác thực ' . ucfirst($provider) . ' thất bại. Vui lòng thử lại.']);
        }

        try {
            // Truyền Auth::id() để Action có thể abort trước khi write DB nếu conflict
            $result = SocialLoginAction::run($provider, $socialUser, Auth::id());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route($wasLoggedIn ? 'auth.profile' : 'login')
                ->withErrors($e->errors());
        } catch (Throwable $e) {
            report($e);
            return redirect()->route($wasLoggedIn ? 'auth.profile' : 'login')
                ->withErrors(['social' => 'Đã xảy ra lỗi khi xác thực. Vui lòng thử lại.']);
        }

        $user = $result->user;

        // Kiểm tra trạng thái tài khoản — áp dụng cho cả login lẫn linking.
        // Nếu user đang có session mà bị vô hiệu hóa/khóa sau khi login:
        // invalidate session ngay thay vì để họ tiếp tục hoạt động.
        if (! $user->is_active || ! $user->account_type->canLogin()) {
            if ($wasLoggedIn) {
                Auth::logout();
                request()->session()->invalidate();
                request()->session()->regenerateToken();
            }

            $errorMessage = ! $user->is_active
                ? 'Tài khoản đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.'
                : 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.';

            return redirect()->route('login')
                ->withErrors(['email' => $errorMessage]);
        }

        // Chỉ login khi user chưa authenticated
        if (! $wasLoggedIn) {
            session(['auth.method' => $provider]);
            Auth::login($user, remember: true);
        }

        $message = match (true) {
            $result->isNewUser => self::LOGIN_MESSAGES['new_user'],
            $result->isNewLink => str_replace(
                ':provider',
                ucfirst($provider),
                self::LOGIN_MESSAGES['new_link']
            ),
            default            => null,
        };

        // Khi redirect về profile (linking flow), dùng key riêng 'social_success'
        // để profile card hiển thị đúng mà không trigger thêm Toast của backend layout.
        // Khi redirect về dashboard (login flow), giữ 'success' để Toast hoạt động bình thường.
        $flashKey = $wasLoggedIn ? 'social_success' : 'success';

        return redirect()
            ->intended($wasLoggedIn ? route('auth.profile') : route('backend.dashboard'))
            ->with($flashKey, $message);
    }

    public function unlink(string $provider): RedirectResponse
    {
        $this->resolveDriver($provider); // 404 nếu provider không hợp lệ

        $user = Auth::user();

        try {
            DB::transaction(function () use ($user, $provider) {
                // lockForUpdate() giữ row-level lock trên toàn bộ social_accounts của user
                // trong suốt transaction — hai request đồng thời không thể đều qua guard
                $accounts = $user->socialAccounts()->lockForUpdate()->get();

                $social = $accounts->firstWhere('provider', $provider);

                abort_unless($social, 404);

                $hasPassword      = ! is_null($user->password);
                $otherSocialCount = $accounts->where('provider', '!=', $provider)->count();

                if (! $hasPassword && $otherSocialCount === 0) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'social' => 'Không thể bỏ liên kết — đây là phương thức đăng nhập duy nhất. '
                                  . 'Hãy đặt mật khẩu trước.',
                    ]);
                }

                $social->delete();
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('social_success', 'Đã bỏ liên kết tài khoản ' . ucfirst($provider) . '.');
    }

    private function resolveDriver(string $provider): string
    {
        $driver = config('services.social_providers.' . $provider);

        if (! $driver) {
            abort(404);
        }

        return $driver;
    }
}
