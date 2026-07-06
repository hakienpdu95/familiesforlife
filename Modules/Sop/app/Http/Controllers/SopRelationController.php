<?php

namespace Modules\Sop\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Sop\Actions\Backend\StoreSopRelationAction;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Models\SopRelation;

class SopRelationController extends Controller
{
    public function store(Request $request, SopProcess $sop, StoreSopRelationAction $action): JsonResponse
    {
        $this->authorize('update', $sop);

        $data = $request->validate([
            'related_sop_id' => 'required|integer|min:1',
            'relation_type'  => 'required|in:prerequisite,related,replaces,replaced_by',
            'note'           => 'nullable|string|max:500',
        ]);

        // BR-FC-005: sop_id !== related_sop_id
        if ((int) $data['related_sop_id'] === $sop->id) {
            return response()->json(['message' => 'SOP không thể tự tham chiếu chính nó.'], 422);
        }

        // Check related SOP exists and belongs to same org
        $related = SopProcess::find($data['related_sop_id']);
        if (! $related || $related->organization_id !== TenantContext::getOrganizationId()) {
            return response()->json(['message' => 'SOP liên quan không tồn tại hoặc không thuộc tổ chức.'], 422);
        }

        // No duplicate
        $exists = SopRelation::where('sop_id', $sop->id)
            ->where('related_sop_id', $data['related_sop_id'])
            ->where('relation_type', $data['relation_type'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Quan hệ này đã tồn tại.'], 422);
        }

        $relation = $action->handle($sop, $data);
        $relation->load('relatedSop:id,uuid,code,title,status');

        return response()->json([
            'uuid'          => $relation->uuid,
            'relation_type' => $relation->relation_type,
            'note'          => $relation->note,
            'related_sop'   => [
                'uuid'   => $relation->relatedSop->uuid,
                'code'   => $relation->relatedSop->code,
                'title'  => $relation->relatedSop->title,
                'status' => $relation->relatedSop->status?->value,
            ],
        ], 201);
    }

    public function destroy(SopProcess $sop, SopRelation $relation): JsonResponse
    {
        $this->authorize('update', $sop);

        abort_if($relation->sop_id !== $sop->id, 404);

        $relation->delete();

        return response()->json(['message' => 'OK']);
    }
}
