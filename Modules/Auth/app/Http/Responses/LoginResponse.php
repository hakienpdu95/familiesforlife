<?php

namespace Modules\Auth\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): mixed
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $redirect = redirect()->intended(route('backend.dashboard'));

        // Lưu preference "ghi nhớ đăng nhập" vào cookie riêng (1 năm).
        // Cookie này CHỈ nhớ ý định của user (để pre-check checkbox sau logout),
        // khác với remember token của Laravel dùng để tự động đăng nhập lại.
        if ($request->boolean('remember')) {
            $redirect->withCookie(cookie('pref_remember', '1', 60 * 24 * 365));
        } else {
            $redirect->withCookie(cookie()->forget('pref_remember'));
        }

        return $redirect;
    }
}
