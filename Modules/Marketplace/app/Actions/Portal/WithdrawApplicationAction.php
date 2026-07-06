<?php
namespace Modules\Marketplace\Actions\Portal;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Marketplace\Enums\ApplicationStatus;
use Modules\Marketplace\Models\MktApplication;

class WithdrawApplicationAction
{
    use AsAction;

    public function handle(MktApplication $application): void
    {
        $application->update(['status' => ApplicationStatus::Withdrawn->value]);
        $application->listing()->withoutGlobalScope('tenant')->decrement('application_count');
    }
}
