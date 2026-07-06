<?php

namespace Modules\Survey\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Modules\Survey\Actions\DeleteSurveyTokenAction;
use Modules\Survey\Actions\GenerateSurveyTokenAction;
use Modules\Survey\Actions\RevokeSurveyTokenAction;
use Modules\Survey\Data\TokenFormData;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyToken;

class TokenController extends Controller
{
    public function index(Survey $survey)
    {
        $this->authorize('survey.manage_tokens');

        $tokens = SurveyToken::forSurvey($survey->id)
            ->orderByDesc('created_at')
            ->get();

        return view('survey::tokens.index', compact('survey', 'tokens'));
    }

    public function store(Survey $survey, GenerateSurveyTokenAction $action): JsonResponse
    {
        $this->authorize('survey.manage_tokens');

        $data = TokenFormData::from(request());

        ['token' => $token, 'plain' => $plain] = $action->handle($survey, $data);

        return response()->json([
            'success' => true,
            'plain'   => $plain,
            'token'   => $this->tokenPayload($token),
            'message' => "Token \"{$token->name}\" đã được tạo.",
        ]);
    }

    public function reveal(Survey $survey, SurveyToken $token): JsonResponse
    {
        $this->authorize('survey.manage_tokens');

        $this->checkOwnership($token, $survey);

        if (! $token->token_encrypted) {
            return response()->json([
                'error' => 'Token này được tạo trước khi hỗ trợ xem lại — không thể hiển thị.',
            ], 422);
        }

        return response()->json([
            'plain' => Crypt::decryptString($token->token_encrypted),
        ]);
    }

    public function revoke(Survey $survey, SurveyToken $token, RevokeSurveyTokenAction $action): JsonResponse
    {
        $this->authorize('survey.manage_tokens');

        $this->checkOwnership($token, $survey);

        $action->handle($token);

        return response()->json([
            'success' => true,
            'message' => "Token \"{$token->name}\" đã bị thu hồi.",
        ]);
    }

    public function destroy(Survey $survey, SurveyToken $token, DeleteSurveyTokenAction $action): JsonResponse
    {
        $this->authorize('survey.manage_tokens');

        $this->checkOwnership($token, $survey);

        $action->handle($token);

        return response()->json([
            'success' => true,
            'message' => "Token \"{$token->name}\" đã bị xóa.",
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function checkOwnership(SurveyToken $token, Survey $survey): void
    {
        if ($token->survey_id !== $survey->id) {
            abort(404);
        }
    }

    private function tokenPayload(SurveyToken $token): array
    {
        return [
            'id'           => $token->id,
            'name'         => $token->name,
            'is_active'    => $token->is_active,
            'can_reveal'   => ! is_null($token->token_encrypted),
            'is_expired'   => $token->isExpired(),
            'last_used_at' => $token->last_used_at?->diffForHumans(),
            'expires_at'   => $token->expires_at?->format('d/m/Y H:i'),
            'created_at'   => $token->created_at->format('d/m/Y H:i'),
        ];
    }
}
