<?php

namespace Modules\Auth\Http\Controllers;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Auth\Actions\Auth\VerifyEmailAction;

/**
 * Xác minh email qua link ký (signed URL) — KHÔNG yêu cầu trình duyệt phải
 * đang đăng nhập đúng tài khoản đó.
 *
 * Lý do: chữ ký URL (signed) + hash email đã CHÍNH LÀ bằng chứng sở hữu email
 * (chỉ người nhận được email mới có link hợp lệ, chưa hết hạn, chưa bị sửa).
 * Yêu cầu "phải đang đăng nhập đúng user_id" thêm vào là dư thừa và gây lỗi
 * 403 khi người dùng mở link trên thiết bị/trình duyệt khác — đây là hành vi
 * mặc định của Fortify nhưng không phù hợp với UX chuyên nghiệp.
 *
 * Route 'verification.notice' / 'verification.verify' / 'verification.send'
 * được đăng ký thủ công ở Modules/Auth/routes/web.php, thay thế route mặc
 * định của Fortify (đã tắt qua config/fortify.php để tránh đăng ký trùng).
 */
class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(config('fortify.home', '/home'));
        }

        return view('auth.verify-email');
    }

    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::find($id);
        abort_unless($user, 403);
        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        VerifyEmailAction::run($user);

        // Đăng nhập tự động nếu trình duyệt chưa đăng nhập, hoặc đang đăng
        // nhập nhầm tài khoản khác — link đã được xác thực đủ mạnh (signed +
        // hash email) nên không cần bắt người dùng đăng nhập lại thủ công.
        if (! Auth::check() || Auth::id() !== $user->id) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        return redirect()->intended(config('fortify.home', '/home'))
            ->with('status', 'Email đã được xác minh thành công!');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(config('fortify.home', '/home'));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
