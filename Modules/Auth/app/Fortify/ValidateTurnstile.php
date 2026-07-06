<?php

namespace Modules\Auth\Fortify;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile;

/**
 * Fortify pipeline step: Cloudflare Turnstile bot protection.
 *
 * Keys-as-switch: có TURNSTILE_SITE_KEY + TURNSTILE_SECRET_KEY → tự động bật.
 * Không cần flag TURNSTILE_ENABLED nữa.
 *
 * Luôn skip trên local / testing.
 * Thiếu keys trên production → log warning + skip (không làm hỏng login flow).
 */
class ValidateTurnstile
{
    public function handle(Request $request, callable $next): mixed
    {
        if ($this->shouldSkip()) {
            return $next($request);
        }

        $request->validate(
            ['cf-turnstile-response' => ['required', new Turnstile()]],
            [
                'cf-turnstile-response.required' => 'Vui lòng hoàn thành xác minh bảo mật.',
                'cf-turnstile-response.*'        => 'Xác thực bảo mật thất bại, vui lòng thử lại.',
            ]
        );

        return $next($request);
    }

    public static function isActive(): bool
    {
        return ! (new self)->shouldSkip();
    }

    private function shouldSkip(): bool
    {
        if (app()->isLocal() || app()->environment('testing')) {
            return true;
        }

        if (blank(config('services.turnstile.key')) || blank(config('services.turnstile.secret'))) {
            if (app()->isProduction()) {
                Log::warning('Turnstile keys chưa cấu hình — login không có bot protection.');
            }
            return true;
        }

        return false;
    }
}
