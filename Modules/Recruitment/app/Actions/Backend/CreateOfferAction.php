<?php

namespace Modules\Recruitment\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Recruitment\Models\RcApplication;
use Modules\Recruitment\Models\RcOffer;

class CreateOfferAction
{
    use AsAction;

    public function handle(RcApplication $application, array $data): RcOffer
    {
        // BR-RC-005: 1 offer active per application
        $hasActive = $application->offers()
            ->whereNotIn('status', ['rejected', 'expired', 'revoked'])
            ->exists();

        abort_if($hasActive, 422, 'Đơn ứng tuyển này đã có offer đang hoạt động');

        return RcOffer::create([
            'application_id' => $application->id,
            'salary_offered' => $data['salary_offered'],
            'currency'       => $data['currency'] ?? 'VND',
            'start_date'     => $data['start_date'],
            'probation_days' => $data['probation_days'] ?? 60,
            'benefits_note'  => $data['benefits_note'] ?? null,
            'expire_at'      => $data['expire_at'] ?? null,
            'status'         => 'draft',
        ]);
    }
}
