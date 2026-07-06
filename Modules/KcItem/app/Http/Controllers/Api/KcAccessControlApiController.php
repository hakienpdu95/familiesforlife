<?php

namespace Modules\KcItem\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\KcItem\Actions\Backend\DestroyKcAccessControlAction;
use Modules\KcItem\Actions\Backend\StoreKcAccessControlAction;
use Modules\KcItem\Models\KcAccessControl;
use Modules\KcItem\Models\KcItem;

class KcAccessControlApiController extends Controller
{
    public function index(KcItem $kcItem): JsonResponse
    {
        $this->authorize('update', $kcItem);

        $controls = $kcItem->accessControls()
            ->with('grantedBy:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($ac) => [
                'id'          => $ac->id,
                'uuid'        => $ac->uuid,
                'target_type' => $ac->target_type,
                'target_id'   => $ac->target_id,
                'permission'  => $ac->permission,
                'granted_at'  => $ac->granted_at?->format('d/m/Y H:i'),
                'expired_at'  => $ac->expired_at?->format('d/m/Y'),
                'granted_by'  => $ac->grantedBy?->name,
            ]);

        return response()->json(['data' => $controls]);
    }

    public function store(Request $request, KcItem $kcItem, StoreKcAccessControlAction $action): JsonResponse
    {
        $this->authorize('update', $kcItem);

        $validated = $request->validate([
            'target_type' => 'required|in:user,role,dept',
            'target_id'   => 'required|integer|min:1',
            'permission'  => 'required|in:view,edit,manage',
            'expired_at'  => 'nullable|date|after:today',
        ]);

        // Không cho trùng target_type + target_id trong cùng item
        $exists = KcAccessControl::where('item_id', $kcItem->id)
            ->where('target_type', $validated['target_type'])
            ->where('target_id', $validated['target_id'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Quyền truy cập này đã tồn tại.'], 422);
        }

        $ac = $action->handle($kcItem, $validated);

        return response()->json([
            'message' => 'Đã cấp quyền thành công.',
            'data'    => [
                'id'          => $ac->id,
                'uuid'        => $ac->uuid,
                'target_type' => $ac->target_type,
                'target_id'   => $ac->target_id,
                'permission'  => $ac->permission,
                'granted_at'  => $ac->granted_at?->format('d/m/Y H:i'),
                'expired_at'  => $ac->expired_at?->format('d/m/Y'),
                'granted_by'  => auth()->user()->name,
            ],
        ], 201);
    }

    public function destroy(KcItem $kcItem, KcAccessControl $accessControl, DestroyKcAccessControlAction $action): JsonResponse
    {
        $this->authorize('update', $kcItem);

        if ($accessControl->item_id !== $kcItem->id) {
            abort(404);
        }

        $action->handle($accessControl);

        return response()->json(['message' => 'Đã thu hồi quyền.']);
    }
}
