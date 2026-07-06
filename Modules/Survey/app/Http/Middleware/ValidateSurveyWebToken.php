<?php

namespace Modules\Survey\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Survey\Models\SurveyToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Web variant of ValidateSurveyToken — reads token from ?token= query param
 * instead of Authorization: Bearer header (browsers can't set headers on GET).
 */
class ValidateSurveyWebToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->query('token');

        if (! $plain) {
            abort(403, 'Token truy cập là bắt buộc. Vui lòng dùng đường dẫn được cấp.');
        }

        $token = SurveyToken::with('survey')
            ->where('token', hash('sha256', $plain))
            ->first();

        if (! $token) {
            abort(403, 'Token không hợp lệ.');
        }

        if (! $token->isValid()) {
            abort(403, 'Token đã hết hạn hoặc bị vô hiệu hóa.');
        }

        if (! $token->survey || $token->survey->slug !== $request->route('slug')) {
            abort(403, 'Token không có quyền truy cập khảo sát này.');
        }

        $request->attributes->set('survey', $token->survey);

        return $next($request);
    }
}
