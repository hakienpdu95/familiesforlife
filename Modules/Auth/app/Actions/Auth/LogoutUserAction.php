<?php

namespace Modules\Auth\Actions\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;

class LogoutUserAction
{
    use AsAction;

    public function handle(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
