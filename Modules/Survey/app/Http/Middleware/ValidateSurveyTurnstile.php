<?php

namespace Modules\Survey\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cloudflare Turnstile verification cho survey submit endpoint.
 *
 * Mỗi survey có thể gán 1 SurveyTurnstileSite (widget Turnstile của 1 domain
 * bên ngoài, vd thuchocvn.vn) — nhiều survey dùng chung 1 site, không cần tạo
 * key riêng cho từng survey. KHÔNG dùng chung secret với widget login admin
 * CRM (Modules/Auth/.../ValidateTurnstile) — Cloudflare khoá site key theo
 * domain, domain khác nhau bắt buộc phải là widget khác nhau.
 *
 * Survey chưa gán site (hoặc site bị tắt) → skip, log warning trên production.
 * ValidateSurveyToken (chạy trước middleware này trong route group) đã eager-load
 * survey kèm theo, lưu vào $request->attributes — dùng lại, không query thêm.
 */
class ValidateSurveyTurnstile
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isLocal() || app()->environment('testing')) {
            return $next($request);
        }

        $survey = $request->attributes->get('survey');
        $site   = $survey?->turnstileSite;

        if (! $site || ! $site->is_active) {
            if (app()->isProduction()) {
                Log::warning('Turnstile: survey chưa gán Turnstile site — submit không có bot protection.', [
                    'survey_id' => $survey?->id,
                ]);
            }
            return $next($request);
        }

        $token = $request->input('cf-turnstile-response');

        if (blank($token)) {
            return response()->json([
                'message' => 'Vui lòng hoàn thành xác minh bảo mật.',
                'errors'  => ['cf-turnstile-response' => ['Vui lòng hoàn thành xác minh bảo mật.']],
            ], 422);
        }

        $secret = $site->secretKey();

        if ($secret === null) {
            Log::error('Turnstile: không giải mã được secret key — kiểm tra lại Turnstile Site đã gán cho survey.', [
                'survey_id' => $survey->id,
                'site_id'   => $site->id,
            ]);
            return response()->json([
                'message' => 'Xác thực bảo mật thất bại, vui lòng thử lại.',
                'errors'  => ['cf-turnstile-response' => ['Xác thực bảo mật thất bại, vui lòng thử lại.']],
            ], 422);
        }

        $cfResponse = Http::asForm()->acceptJson()->retry(3, 100)
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

        if (! $cfResponse->json('success', false)) {
            // error-codes phổ biến: invalid-input-secret (secret key sai/không khớp site key
            // đang render ở frontend), timeout-or-duplicate (token hết hạn/dùng lại).
            Log::warning('Turnstile: Cloudflare từ chối token submit.', [
                'survey_id'   => $survey->id,
                'site_id'     => $site->id,
                'error_codes' => $cfResponse->json('error-codes', []),
            ]);
            return response()->json([
                'message' => 'Xác thực bảo mật thất bại, vui lòng thử lại.',
                'errors'  => ['cf-turnstile-response' => ['Xác thực bảo mật thất bại, vui lòng thử lại.']],
            ], 422);
        }

        return $next($request);
    }
}
