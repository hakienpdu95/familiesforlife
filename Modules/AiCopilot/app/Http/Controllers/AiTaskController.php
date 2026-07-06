<?php

namespace Modules\AiCopilot\Http\Controllers;

use App\Enums\PermissionEnum as P;
use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Modules\AiCopilot\Actions\ExecuteAiTaskAction;
use Modules\AiCopilot\Data\Requests\AiTaskData;
use Modules\AiCopilot\Exceptions\FeatureNotAvailableException;
use Modules\AiCopilot\Exceptions\QuotaExceededException;
use Modules\AiCopilot\Models\AiRequest;

class AiTaskController extends Controller
{
    public function execute(): JsonResponse
    {
        $this->authorize(P::AI_COPILOT_USE->value);

        try {
            $data      = AiTaskData::validateAndCreate(request()->all());
            $subject   = $this->resolveSubject($data->subject_type, $data->subject_id);
            $aiRequest = ExecuteAiTaskAction::run(
                agentSlug: $data->agent_slug,
                variables: $data->variables,
                subject:   $subject,
                forceSync: false,
            );

            return response()->json([
                'uuid'   => $aiRequest->uuid,
                'status' => $aiRequest->status,
                'output' => $aiRequest->isDone() ? $aiRequest->ai_output : null,
            ], $aiRequest->isDone() ? 200 : 202);

        } catch (FeatureNotAvailableException $e) {
            return response()->json(['error' => $e->getMessage()], 402);
        } catch (QuotaExceededException $e) {
            return response()->json(['error' => $e->getMessage()], 429);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['error' => 'Agent not found.'], 404);
        }
    }

    public function poll(string $uuid): JsonResponse
    {
        $this->authorize(P::AI_COPILOT_USE->value);

        $aiRequest = AiRequest::where('uuid', $uuid)
            ->where('organization_id', TenantContext::getOrganizationId())
            ->firstOrFail();

        return response()->json([
            'uuid'         => $aiRequest->uuid,
            'status'       => $aiRequest->status,
            'output'       => $aiRequest->ai_output,
            'total_tokens' => $aiRequest->total_tokens,
            'duration_ms'  => $aiRequest->duration_ms,
            'error'        => $aiRequest->error_message,
        ]);
    }

    private function resolveSubject(?string $type, ?int $id): ?object
    {
        if (!$type || !$id) {
            return null;
        }
        if (!class_exists($type)) {
            return null;
        }
        return app($type)->find($id);
    }
}
