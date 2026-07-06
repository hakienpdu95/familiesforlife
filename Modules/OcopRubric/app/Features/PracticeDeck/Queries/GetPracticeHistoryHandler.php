<?php

namespace Modules\OcopRubric\Features\PracticeDeck\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\OcopRubric\Enums\ScoringSessionStatus;
use Modules\OcopRubric\Models\OcopScoringSession;

/**
 * Lịch sử các phiên đã kết thúc (completed/abandoned) — chỉ đọc, không có
 * logic nghiệp vụ trong Controller (đúng tinh thần CQRS-lite).
 */
class GetPracticeHistoryHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var GetPracticeHistoryQuery $query */
        return OcopScoringSession::with(['product', 'user'])
            ->whereIn('status', [ScoringSessionStatus::Completed->value, ScoringSessionStatus::Abandoned->value])
            ->when($query->productId, fn ($q) => $q->where('ocop_product_id', $query->productId))
            ->when($query->userId, fn ($q) => $q->where('user_id', $query->userId))
            ->when($query->mode, fn ($q) => $q->where('mode', $query->mode))
            ->orderByDesc('completed_at')
            ->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
