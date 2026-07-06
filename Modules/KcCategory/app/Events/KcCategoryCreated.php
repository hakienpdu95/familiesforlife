<?php

namespace Modules\KcCategory\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\KcCategory\Models\KcCategory;

class KcCategoryCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly KcCategory $kcCategory,
    ) {}
}
