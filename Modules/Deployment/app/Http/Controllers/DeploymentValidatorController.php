<?php

namespace Modules\Deployment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Deployment\Actions\RunValidatorAction;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Deployment\Notifications\ValidatorFoundIssuesNotification;
use Modules\Deployment\Services\DataQualityScoreService;

class DeploymentValidatorController extends Controller
{
    public function run(Request $request, DeploymentTarget $target, RunValidatorAction $action): JsonResponse
    {
        $this->authorize('update', $target);

        $result = $action->handle($target);

        if ($result['new_issues'] > 0) {
            auth()->user()?->notify(
                new ValidatorFoundIssuesNotification($target, $result['new_issues'], $result['score']['score'])
            );
        }

        return response()->json([
            'new_issues' => $result['new_issues'],
            'score'      => $result['score'],
            'message'    => $result['new_issues'] > 0
                ? "Tìm thấy {$result['new_issues']} vấn đề mới."
                : 'Không phát hiện vấn đề mới.',
        ]);
    }

    public function score(DeploymentTarget $target): JsonResponse
    {
        $this->authorize('view', $target);

        $score = (new DataQualityScoreService)->score($target);

        return response()->json($score);
    }
}
