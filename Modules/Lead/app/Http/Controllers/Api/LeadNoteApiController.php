<?php

namespace Modules\Lead\Http\Controllers\Api;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Lead\Actions\Notes\DestroyNoteAction;
use Modules\Lead\Actions\Notes\StoreNoteAction;
use Modules\Lead\Actions\Notes\TogglePinNoteAction;
use Modules\Lead\Actions\Notes\UpdateNoteAction;
use Modules\Lead\Data\Requests\StoreNoteData;
use Modules\Lead\Models\Lead;
use Modules\Lead\Models\LeadNote;

class LeadNoteApiController extends Controller
{
    public function store(
        Request $request,
        Lead $lead,
        StoreNoteAction $action,
    ): JsonResponse {
        $this->authorize('view', $lead);

        $data = StoreNoteData::validateAndCreate($request->all());
        $note = $action->handle($lead, $data);

        return response()->json(['ok' => true, 'note' => $note], 201);
    }

    public function update(
        Request $request,
        Lead $lead,
        LeadNote $note,
        UpdateNoteAction $action,
    ): JsonResponse {
        $this->assertNoteOwnership($lead, $note);

        $data        = StoreNoteData::validateAndCreate($request->all());
        $updatedNote = $action->handle($note, $data);

        return response()->json(['ok' => true, 'note' => $updatedNote]);
    }

    public function destroy(
        Request $request,
        Lead $lead,
        LeadNote $note,
        DestroyNoteAction $action,
    ): JsonResponse {
        $this->assertNoteOwnership($lead, $note);

        $action->handle($note);

        return response()->json(['ok' => true]);
    }

    public function togglePin(
        Request $request,
        Lead $lead,
        LeadNote $note,
        TogglePinNoteAction $action,
    ): JsonResponse {
        $this->authorize('view', $lead);
        abort_unless($note->lead_id === $lead->id, 404);

        $updatedNote = $action->handle($note);

        return response()->json(['ok' => true, 'is_pinned' => $updatedNote->is_pinned]);
    }

    // ── Private helpers ───────────────────────────────────────────────

    private function assertNoteOwnership(Lead $lead, LeadNote $note): void
    {
        abort_unless($note->lead_id === $lead->id, 404);

        $user     = auth()->user();
        $isAuthor = (int) $note->author_id === $user->id;
        $canEdit  = $user->can(PermissionEnum::LEADS_EDIT->value);

        abort_unless($isAuthor || $canEdit, 403);
    }
}
