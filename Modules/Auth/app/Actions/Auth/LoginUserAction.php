<?php

namespace Modules\Auth\Actions\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class LoginUserAction
{
    use AsAction;

    /**
     * Xác thực credentials và trả về User nếu hợp lệ, null nếu thất bại.
     * Được gọi bởi Fortify::authenticateUsing() — Fortify tự xử lý remember-me
     * và session sau khi callback trả về user.
     */
    public function handle(Request $request): ?Authenticatable
    {
        /** @var User|null $user */
        $user = User::where('email', Str::lower($request->string('email')))->first();

        // Sai email hoặc sai mật khẩu → trả null để Fortify xử lý
        // (tăng rate limiter + dùng trans('auth.failed'))
        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            return null;
        }

        // Chỉ tiết lộ lý do cụ thể SAU KHI đã xác minh đúng credentials.
        // Nếu kiểm tra trước bước này, attacker có thể dò trạng thái account
        // mà không cần biết mật khẩu.
        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Tài khoản đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.',
            ]);
        }

        if (! $user->account_type->canLogin()) {
            throw ValidationException::withMessages([
                'email' => 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.',
            ]);
        }

        return $user;
    }
}
