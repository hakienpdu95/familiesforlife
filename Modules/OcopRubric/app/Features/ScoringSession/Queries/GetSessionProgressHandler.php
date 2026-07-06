<?php

namespace Modules\OcopRubric\Features\ScoringSession\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\OcopRubric\Models\OcopScoringSession;
use Modules\OcopRubric\Models\OcopStarBand;

/**
 * Trạng thái tiến độ hiện tại của 1 phiên — dùng cho thanh tiến độ trên deck:
 * "Đang 3★ (62đ) — cần thêm 8đ để chạm 4★ (70đ)".
 */
class GetSessionProgressHandler implements QueryHandlerInterface
{
    /** @return array{total_score:float,score_section_a:float,score_section_b:float,score_section_c:float,star_rank:?int,criteria_answered:int,criteria_total:int,current_band:?OcopStarBand,next_band:?OcopStarBand,points_to_next:?float} */
    public function handle(QueryInterface $query): array
    {
        /** @var GetSessionProgressQuery $query */
        $session = OcopScoringSession::findOrFail($query->sessionId);

        $currentBand = OcopStarBand::where('legal_version', 'QD26-2026')
            ->where('min_score', '<=', $session->total_score)
            ->where('max_score', '>=', $session->total_score)
            ->orderByDesc('star_rank')
            ->first();

        $nextBand = ($currentBand && $currentBand->star_rank < 5)
            ? OcopStarBand::where('legal_version', 'QD26-2026')->where('star_rank', $currentBand->star_rank + 1)->first()
            : null;

        return [
            'total_score'       => (float) $session->total_score,
            'score_section_a'   => (float) $session->score_section_a,
            'score_section_b'   => (float) $session->score_section_b,
            'score_section_c'   => (float) $session->score_section_c,
            'star_rank'         => $session->star_rank,
            'criteria_answered' => $session->criteria_answered,
            'criteria_total'    => $session->criteria_total,
            'current_band'      => $currentBand,
            'next_band'         => $nextBand,
            'points_to_next'    => $nextBand ? round((float) $nextBand->min_score - (float) $session->total_score, 2) : null,
        ];
    }
}
