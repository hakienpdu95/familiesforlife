<?php

namespace Modules\Auth\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): mixed
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đăng ký thành công.'], 201);
        }

        return redirect()->intended(route('backend.dashboard'));
    }
}
