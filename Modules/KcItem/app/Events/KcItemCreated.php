<?php

namespace Modules\KcItem\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\KcItem\Models\KcItem;

class KcItemCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly KcItem $kcItem) {}
}
