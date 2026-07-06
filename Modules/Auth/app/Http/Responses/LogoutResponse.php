<?php

namespace Modules\Auth\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): mixed
    {
        return redirect()->route('login');
    }
}
