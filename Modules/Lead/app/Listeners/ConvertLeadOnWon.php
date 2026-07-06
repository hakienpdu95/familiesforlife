<?php
namespace Modules\Lead\Listeners;

use Modules\Customer\Actions\Conversion\ConvertLeadToCustomerAction;
use Modules\Lead\Enums\LeadStatus;
use Modules\Lead\Events\LeadStageChanged;

class ConvertLeadOnWon
{
    public function handle(LeadStageChanged $event): void
    {
        $lead = $event->lead->fresh(['contact', 'tags']);

        // Only trigger on Converted status, and only if not already linked
        if ($lead->status !== LeadStatus::Converted) return;
        if ($lead->customer_id !== null) return;

        ConvertLeadToCustomerAction::run($lead);
    }
}
