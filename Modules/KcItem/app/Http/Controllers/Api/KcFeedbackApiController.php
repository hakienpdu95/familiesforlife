<?php

namespace Modules\KcItem\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\KcItem\Actions\Backend\UpsertKcFeedbackAction;
use Modules\KcItem\Enums\KcItemStatus;
use Modules\KcItem\Models\KcFeedback;
use Modules\KcItem\Models\KcItem;

class KcFeedbackApiController extends Controller
{
    public function upsert(Request $request, KcItem $kcItem, UpsertKcFeedbackAction $action): JsonResponse
    {
        // BR-KC-005: không feedback tài liệu draft/rejected
        if (in_array($kcItem->status, [KcItemStatus::Draft, KcItemStatus::Rejected])) {
            return response()->json(['message' => 'Không thể đánh giá tài liệu ở trạng thái này.'], 422);
        }

        $validated = $request->validate([
            'rating'     => 'nullable|integer|min:1|max:5',
            'comment'    => 'nullable|string|max:1000',
            'is_helpful' => 'nullable|boolean',
        ]);

        // Ít nhất một trường phải có giá trị
        if (empty($validated['rating']) && ! isset($validated['is_helpful']) && empty($validated['comment'])) {
            return response()->json(['message' => 'Vui lòng nhập ít nhất một trường đánh giá.'], 422);
        }

        $feedback = $action->handle($kcItem, $validated);

        return response()->json([
            'message' => 'Đánh giá đã được lưu.',
            'data'    => [
                'rating'     => $feedback->rating,
                'comment'    => $feedback->comment,
                'is_helpful' => $feedback->is_helpful,
            ],
        ]);
    }

    public function summary(KcItem $kcItem): JsonResponse
    {
        $stats = KcFeedback::where('item_id', $kcItem->id)
            ->selectRaw('
                COUNT(*) as total,
                AVG(rating) as avg_rating,
                SUM(CASE WHEN is_helpful = 1 THEN 1 ELSE 0 END) as helpful_count,
                SUM(CASE WHEN is_helpful IS NOT NULL THEN 1 ELSE 0 END) as helpful_total
            ')
            ->first();

        $helpfulPercent = $stats->helpful_total > 0
            ? round(($stats->helpful_count / $stats->helpful_total) * 100)
            : null;

        return response()->json([
            'total'           => (int) $stats->total,
            'avg_rating'      => $stats->avg_rating ? round($stats->avg_rating, 1) : null,
            'helpful_percent' => $helpfulPercent,
        ]);
    }

    public function myFeedback(KcItem $kcItem): JsonResponse
    {
        $feedback = KcFeedback::where('item_id', $kcItem->id)
            ->where('user_id', auth()->id())
            ->first();

        return response()->json([
            'rating'     => $feedback?->rating,
            'comment'    => $feedback?->comment,
            'is_helpful' => $feedback?->is_helpful,
        ]);
    }
}
