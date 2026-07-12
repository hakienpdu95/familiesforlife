<?php

namespace Modules\Approval\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Approval\Actions\Concerns\LogsApprovalActions;
use Modules\Approval\Enums\ApprovalStatus;
use Modules\Approval\Models\ApprovalSubject;

class RejectAction
{
    use AsAction;
    use LogsApprovalActions;

    /**
     * $reason bắt buộc — Controller của module tiêu thụ nên validate lại qua
     * $request->validate(['reason' => ['required','string','min:10']]) TRƯỚC khi gọi Action
     * (đúng như TranslationController::unpublish/takedown của Modules/Post), nhưng Action vẫn
     * tự validate lại ở đây (defense-in-depth — Action cũng có thể được gọi từ artisan
     * tinker/queue job không qua Controller).
     */
    public function handle(Model $subject, string $reason): ApprovalSubject
    {
        Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'min:10']])->validate();

        return $this->transition($subject, $subject->approvalSubject, ApprovalStatus::Draft, 'reject', $reason);
    }
}
