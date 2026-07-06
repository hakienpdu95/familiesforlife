<?php

namespace Modules\OcopRubric\Features\ScoringSession\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\OcopRubric\Models\OcopScoringSession;

/**
 * Sự kiện quan trọng nhất module này — vertical chứng nhận tương lai
 * (OcopCertification, Phase 7) sẽ lắng nghe sự kiện này (mode=self_assessment)
 * để tự tạo hồ sơ nháp. Không có listener bắt buộc ở module này.
 */
class ScoringSessionCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly OcopScoringSession $session) {}
}
