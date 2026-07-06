<?php

namespace Modules\OcopRubric\Features\ScoringSession\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Enums\ScoringSessionStatus;
use Modules\OcopRubric\Models\OcopProduct;
use Modules\OcopRubric\Models\OcopScoringSession;

class StartScoringSessionAction
{
    use AsAction;

    public function handle(OcopProduct $product, string $mode, int $userId): OcopScoringSession
    {
        // Chống trùng phiên: 2 tab/2 nhân viên cùng bấm "bắt đầu luyện tập" cho CÙNG
        // sản phẩm + CÙNG mode trong khi đã có 1 phiên in_progress → resume phiên cũ,
        // KHÔNG tạo phiên mới (tránh 2 phiên chấm song song gây nhầm lẫn).
        $existing = OcopScoringSession::where('ocop_product_id', $product->id)
            ->where('mode', $mode)
            ->where('status', ScoringSessionStatus::InProgress->value)
            ->first();

        if ($existing) {
            return $existing;
        }

        $rubricVersion = $product->activeRubricVersion();
        if (!$rubricVersion) {
            throw new \DomainException(
                'Bộ tiêu chí cho nhóm sản phẩm này chưa được cấu hình — vui lòng liên hệ quản trị hệ thống.'
            );
        }

        $scorableCount = $rubricVersion->sections()
            ->withCount(['criteria' => fn ($q) => $q->where('is_scorable', true)])
            ->get()->sum('criteria_count');

        return OcopScoringSession::create([
            'organization_id'   => $product->organization_id,
            'ocop_product_id'   => $product->id,
            'rubric_version_id' => $rubricVersion->id,
            'user_id'           => $userId,
            'mode'              => $mode,
            'status'            => ScoringSessionStatus::InProgress->value,
            'criteria_total'    => $scorableCount,
            // Đặt tường minh bằng PHP now() thay vì để cột useCurrent() của DB tự
            // điền — tránh lệch múi giờ giữa DB server (SYSTEM tz) và app.timezone
            // (UTC), vốn làm duration_seconds tính sai (âm) khi so với completed_at.
            'started_at'        => now(),
        ]);
    }
}
