<?php

namespace Modules\Newsletter\Features\BroadcastSending\Data;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class BroadcastData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $subject,

        #[Required]
        public readonly string $body_html,

        public readonly ?string $scheduled_at = null,
    ) {}
}
