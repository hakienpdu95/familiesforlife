<?php

namespace Modules\Recruitment\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Recruitment\Actions\Backend\StoreCandidateNoteAction;
use Modules\Recruitment\Enums\NoteType;
use Modules\Recruitment\Models\RcCandidate;
use Modules\Recruitment\Models\RcCandidateNote;

class CandidateNoteController extends Controller
{
    public function store(Request $request, RcCandidate $candidate, StoreCandidateNoteAction $action): JsonResponse
    {
        $this->authorize('update', $candidate);

        $validated = $request->validate([
            'content'        => ['required', 'string', 'max:5000'],
            'note_type'      => ['required', 'string', 'in:' . implode(',', array_column(NoteType::cases(), 'value'))],
            'is_private'     => ['boolean'],
            'application_id' => ['nullable', 'integer', 'exists:rc_applications,id'],
        ]);

        $note = $action->handle($candidate, $validated);
        $note->load('createdBy');

        return response()->json([
            'message' => 'Đã thêm ghi chú',
            'note'    => [
                'id'         => $note->id,
                'content'    => $note->content,
                'note_type'  => $note->note_type?->value,
                'note_label' => $note->note_type?->label(),
                'is_private' => $note->is_private,
                'created_by' => $note->createdBy?->name,
                'created_at' => $note->created_at?->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function destroy(RcCandidate $candidate, RcCandidateNote $note): JsonResponse
    {
        $this->authorize('update', $candidate);

        if ($note->candidate_id !== $candidate->id) {
            abort(404);
        }

        // Chỉ cho phép xóa note của chính mình hoặc HR Admin
        if ($note->created_by !== auth()->id() && !auth()->user()->hasRole('HR_Admin')) {
            abort(403, 'Bạn không có quyền xóa ghi chú này');
        }

        $note->delete();

        return response()->json(['message' => 'Đã xóa ghi chú']);
    }
}
