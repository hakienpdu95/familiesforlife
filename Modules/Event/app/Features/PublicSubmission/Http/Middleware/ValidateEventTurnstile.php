<?php

namespace Modules\Event\Features\PublicSubmission\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile;
use Symfony\Component\HttpFoundation\Response;

/**
 * spec/Event_Management_Technical_Specification.md §10.1 — bản đơn giản (1 site key toàn cục
 * qua config('services.turnstile.*')), mirror Modules\Auth\Fortify\ValidateTurnstile (login) —
 * KHÔNG dùng bản multi-site của Modules\Survey\Http\Middleware\ValidateSurveyTurnstile vì Event
 * chỉ có đúng 1 form, không cần nhúng nhiều domain khác nhau.
 *
 * Khác ValidateTurnstile (Fortify pipeline step, `handle($request, callable $next)`) — đây là
 * middleware route chuẩn (`handle($request, Closure $next): Response`).
 */
class ValidateEventTurnstile
{
    public function handle(Request $request, Closure $next): Response
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

    /** Dùng trong Blade (submit-form.blade.php) để quyết định có render <x-turnstile />/script hay không. */
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
                Log::warning('Turnstile: keys chưa cấu hình — form nộp sự kiện không có bot protection.');
            }
            return true;
        }

        return false;
    }
}
