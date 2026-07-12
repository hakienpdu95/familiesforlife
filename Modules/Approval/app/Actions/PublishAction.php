<?php

namespace Modules\Approval\Actions;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Approval\Actions\Concerns\LogsApprovalActions;
use Modules\Approval\Enums\ApprovalStatus;
use Modules\Approval\Models\ApprovalSubject;

class PublishAction
{
    use AsAction;
    use LogsApprovalActions;

    /**
     * Đây là NƠI DUY NHẤT ghi public_snapshot — chụp lại đúng giá trị hiện tại của các trường
     * approvalWatchedAttributes() và đóng băng vào ApprovalSubject. Từ thời điểm này, cổng
     * thông tin công khai hiển thị đúng bản vừa chụp cho tới lần PublishAction tiếp theo (dù
     * entity có bị sửa tiếp — ReviseContentAction không đụng vào snapshot này).
     */
    public function handle(Model $subject): ApprovalSubject
    {
        $snapshot = collect($subject->approvalWatchedAttributes())
            ->mapWithKeys(fn (string $attribute) => [$attribute => $subject->getAttribute($attribute)])
            ->all();

        return $this->transition(
            $subject,
            $subject->approvalSubject,
            ApprovalStatus::Published,
            'publish',
            extraAttributes: ['public_snapshot' => $snapshot],
        );
    }
}
