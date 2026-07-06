<?php

namespace Modules\LeadSource\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\LeadSource\Events\SourceUpdated;
use Modules\LeadSource\Models\LeadSource;

class ToggleSourceAction
{
    use AsAction;

    public function handle(LeadSource $source): LeadSource
    {
        $source->update(['is_active' => ! $source->is_active]);
        $source->refresh();

        event(new SourceUpdated($source));

        return $source;
    }
}
