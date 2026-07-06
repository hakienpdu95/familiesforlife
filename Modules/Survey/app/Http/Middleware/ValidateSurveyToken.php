<?php

namespace Modules\Survey\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Survey\Jobs\UpdateTokenLastUsedJob;
use Modules\Survey\Models\SurveyToken;
use Symfony\Component\HttpFoundation\Response;

class ValidateSurveyToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        if (! $plain) {
            return response()->json(['error' => 'API token is required. Use Authorization: Bearer <token>.'], 401);
        }

        $hashed = hash('sha256', $plain);

        // Eager-load survey để tránh query thứ 2 riêng biệt
        $token = SurveyToken::with('survey')->where('token', $hashed)->first();

        if (! $token) {
            return response()->json(['error' => 'Invalid API token.'], 401);
        }

        if (! $token->isValid()) {
            return response()->json(['error' => 'Token is inactive or has expired.'], 401);
        }

        if ($token->hasReachedUsageLimit()) {
            return response()->json(['error' => 'Token đã đạt giới hạn sử dụng.'], 429);
        }

        // Verify token belongs to the survey being accessed — dùng relation đã eager-load
        $slug = $request->route('slug');

        if (! $token->survey || $token->survey->slug !== $slug) {
            return response()->json(['error' => 'Token is not authorized for this survey.'], 403);
        }

        // Dispatch job async — không block response, không ghi activity log
        dispatch(new UpdateTokenLastUsedJob($token->id));

        $request->attributes->set('surveyToken', $token);
        $request->attributes->set('survey', $token->survey);

        return $next($request);
    }
}
