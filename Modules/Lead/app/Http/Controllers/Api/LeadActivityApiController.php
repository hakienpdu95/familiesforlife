<?php

namespace Modules\Lead\Http\Controllers\Api;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Lead\Actions\LogLeadActivityAction;
use Modules\Lead\Data\LeadActivityData;
use Modules\Lead\Data\Requests\StoreActivityData;
use Modules\Lead\Enums\LeadActivityType;
use Modules\Lead\Models\Lead;

class LeadActivityApiController extends Controller
{
    public function store(
        Request $request,
        Lead $lead,
        LogLeadActivityAction $action,
    ): JsonResponse {
        $this->authorize('view', $lead);
        abort_unless($request->user()->can(PermissionEnum::LEADS_EDIT->value), 403);

        // Validate that the type is a valid enum value
        $typeValues = collect(LeadActivityType::cases())->map(fn ($c) => $c->value)->all();
        $request->merge(['type' => (int) $request->input('type')]);
        $request->validate([
            'type' => 'required|integer|in:' . implode(',', $typeValues),
        ]);

        $data = StoreActivityData::validateAndCreate($request->all());

        $user = $request->user();

        $activity = $action->handle(new LeadActivityData(
            leadId:          $lead->id,
            orgId:           $lead->organization_id,
            type:            $data->type,
            title:           $data->title,
            description:     $data->description,
            outcome:         $data->outcome,
            scheduledAt:     $data->scheduled_at,
            completedAt:     $data->completed_at ?? now()->toDateTimeString(),
            durationMinutes: $data->duration_minutes,
            attendeeCount:   $data->attendee_count,
            actorId:         $user->id,
            actorName:       $user->name,
        ));

        return response()->json(['ok' => true, 'activity' => $activity], 201);
    }
}
