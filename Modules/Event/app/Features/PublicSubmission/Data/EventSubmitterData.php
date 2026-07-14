<?php

namespace Modules\Event\Features\PublicSubmission\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/**
 * Thông tin người nộp (form public — spec §5.3). Tách riêng khỏi EventData (nội dung sự
 * kiện) vì thuộc 2 bảng khác nhau (events vs event_submissions) — SubmitEventAction nhận cả
 * 2 Data object thay vì 1 Data khổng lồ gộp chung nội dung + PII.
 */
class EventSubmitterData extends Data
{
    public function __construct(
        #[Required, Max(100)]
        public readonly string $first_name,

        #[Required, Max(100)]
        public readonly string $last_name,

        #[Required, Email, Max(255)]
        public readonly string $email,

        public readonly bool $newsletter_consent = false,
    ) {}
}
