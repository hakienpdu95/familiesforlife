<?php

namespace Modules\Assessment\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Assessment\Models\AssessmentResult;

class ValidateAssessmentResultToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->route('token');

        $result = AssessmentResult::where('public_token', $token)->first();

        if (! $result) {
            abort(404);
        }

        $request->attributes->set('assessment_result', $result);

        return $next($request);
    }
}
