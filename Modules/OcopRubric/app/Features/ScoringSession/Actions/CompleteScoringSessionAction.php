<?php

namespace Modules\OcopRubric\Features\ScoringSession\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Enums\ScoringSessionStatus;
use Modules\OcopRubric\Features\ScoringSession\Events\ScoringSessionCompleted;
use Modules\OcopRubric\Features\ScoringSession\Events\StarBandImproved;
use Modules\OcopRubric\Models\OcopScoringSession;

class CompleteScoringSessionAction
{
    use AsAction;

    public function handle(OcopScoringSession $session): OcopScoringSession
    {
        if ($session->status !== ScoringSessionStatus::InProgress->value) {
            throw new \DomainException('Phiên này đã hoàn thành hoặc đã bị huỷ trước đó.');
        }

        // Chặn hoàn thành khi còn câu trả lời map chéo rubric_version chưa được xác
        // nhận lại (chỉ phát sinh từ DuplicateScoringSessionAction Trường hợp 2 —
        // Phase 4b, hiện tại no-op vì Trường hợp 2 chưa được hỗ trợ ở Phase 4).
        if ($session->answers()->where('needs_review', true)->exists()) {
            throw new \DomainException(
                'Còn tiêu chí được nhân bản chéo phiên bản chưa xác nhận lại — '
                . 'vui lòng xem lại từng tiêu chí được đánh dấu trước khi hoàn thành.'
            );
        }

        return DB::transaction(function () use ($session) {
            $session->update([
                'status'           => ScoringSessionStatus::Completed->value,
                'is_locked'        => true,
                'completed_at'     => now(),
                'duration_seconds' => $session->started_at->diffInSeconds(now()),
            ]);

            if ($session->ocop_product_id) {
                $product = $session->product;

                if ($session->mode === 'practice') {
                    // "practice": theo dõi KỶ LỤC cao nhất — mục tiêu luyện tập là cải
                    // thiện dần, không ghi đè xuống thấp hơn nếu lần sau tệ hơn lần trước.
                    $isNewBest = $product->best_practice_score === null
                        || $session->total_score > $product->best_practice_score;

                    if ($isNewBest) {
                        $previousBest = $product->best_practice_star_rank;
                        $product->update([
                            'best_practice_score'     => $session->total_score,
                            'best_practice_star_rank' => $session->star_rank,
                            'status'                  => $product->status === 'self_assessed' ? 'self_assessed' : 'practicing',
                        ]);

                        if ($previousBest !== null && $session->star_rank > $previousBest) {
                            StarBandImproved::dispatch($product, $previousBest, $session->star_rank);
                        }
                    }
                } else {
                    // "self_assessment": luôn GHI ĐÈ bằng lần MỚI NHẤT — phản ánh đúng
                    // hiện trạng thật tại thời điểm này (đúng tinh thần Mẫu 02).
                    $product->update([
                        'latest_self_assessment_score'      => $session->total_score,
                        'latest_self_assessment_star_rank'  => $session->star_rank,
                        'latest_self_assessment_session_id' => $session->id,
                        'status'                             => 'self_assessed',
                    ]);
                }
            }

            ScoringSessionCompleted::dispatch($session);

            return $session->fresh();
        });
    }
}
