<?php
namespace Modules\Marketplace\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Marketplace\Enums\ApplicationStatus;
use Modules\Marketplace\Models\MktApplication;

class ShortlistApplicationAction
{
    use AsAction;

    public function handle(MktApplication $application): void
    {
        $application->update(['status' => ApplicationStatus::Shortlisted->value]);
    }
}
