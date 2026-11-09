<?php

namespace Modules\Product\Features\CatalogManagement\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Approval\Actions\ApproveAction;
use Modules\Approval\Actions\PublishAction;
use Modules\Approval\Actions\SubmitForApprovalAction;
use Modules\Approval\Enums\ApprovalStatus;
use Modules\Approval\Models\ApprovalSubject;
use Modules\Product\Models\Product;

class FastTrackPublishProductAction
{
    use AsAction;

    public function handle(Product $product): ApprovalSubject
    {
        return DB::transaction(function () use ($product) {
            $approval = $product->ensureApprovalSubject();

            if ($approval->status === ApprovalStatus::Draft) {
                $approval = app(SubmitForApprovalAction::class)->handle($product);
            }

            if ($approval->status === ApprovalStatus::Pending) {
                $product->setRelation('approvalSubject', $approval);
                $approval = app(ApproveAction::class)->handle($product);
            }

            if ($approval->status === ApprovalStatus::Approved) {
                $product->setRelation('approvalSubject', $approval);
                $approval = app(PublishAction::class)->handle($product);
            }

            return $approval;
        });
    }
}
