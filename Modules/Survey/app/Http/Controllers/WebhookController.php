<?php

namespace Modules\Survey\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyWebhook;

class WebhookController extends Controller
{
    private const VALID_EVENTS = ['response.created', 'result.calculated'];

    public function index(Survey $survey): JsonResponse
    {
        $this->authorize('survey.update');

        $webhooks = SurveyWebhook::where('survey_id', $survey->id)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'url', 'events', 'is_active', 'created_at']);

        return response()->json(['webhooks' => $webhooks]);
    }

    public function store(Request $request, Survey $survey): JsonResponse
    {
        $this->authorize('survey.update');

        $data = $request->validate([
            'url'      => 'required|url|max:500',
            'secret'   => 'nullable|string|max:255',
            'events'   => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        if (! empty($data['events'])) {
            $events = array_filter(array_map('trim', explode(',', $data['events'])));
            $invalid = array_diff($events, self::VALID_EVENTS);
            if (! empty($invalid)) {
                return response()->json([
                    'message' => 'Events không hợp lệ: ' . implode(', ', $invalid)
                        . '. Cho phép: ' . implode(', ', self::VALID_EVENTS),
                ], 422);
            }
        }

        $webhook = SurveyWebhook::create([
            'survey_id' => $survey->id,
            'url'       => $data['url'],
            'secret'    => $data['secret'] ?? null,
            'events'    => $data['events'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json(['webhook' => $webhook->only(['id', 'url', 'events', 'is_active', 'created_at'])], 201);
    }

    public function update(Request $request, Survey $survey, SurveyWebhook $webhook): JsonResponse
    {
        $this->authorize('survey.update');
        $this->checkOwnership($webhook, $survey);

        $data = $request->validate([
            'url'       => 'sometimes|url|max:500',
            'secret'    => 'nullable|string|max:255',
            'events'    => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        if (isset($data['events']) && ! empty($data['events'])) {
            $events = array_filter(array_map('trim', explode(',', $data['events'])));
            $invalid = array_diff($events, self::VALID_EVENTS);
            if (! empty($invalid)) {
                return response()->json(['message' => 'Events không hợp lệ: ' . implode(', ', $invalid)], 422);
            }
        }

        $webhook->update($data);

        return response()->json(['webhook' => $webhook->only(['id', 'url', 'events', 'is_active'])]);
    }

    public function destroy(Survey $survey, SurveyWebhook $webhook): JsonResponse
    {
        $this->authorize('survey.update');
        $this->checkOwnership($webhook, $survey);

        $webhook->delete();

        return response()->json(['message' => 'Webhook đã được xóa.']);
    }

    private function checkOwnership(SurveyWebhook $webhook, Survey $survey): void
    {
        if ($webhook->survey_id !== $survey->id) {
            abort(404);
        }
    }
}
