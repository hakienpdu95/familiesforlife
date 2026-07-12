<?php

namespace Modules\Approval\Actions\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Approval\Enums\ApprovalStatus;
use Modules\Approval\Exceptions\InvalidTransitionException;
use Modules\Approval\Models\ApprovalLog;
use Modules\Approval\Models\ApprovalSubject;

/**
 * spec/Workflow_Approval_Technical_Specification.md §8.1.
 */
trait LogsApprovalActions
{
    /**
     * Khoá row ApprovalSubject (SELECT ... FOR UPDATE) trong 1 transaction để tránh race
     * condition: 2 request đồng thời (vd double-click "Duyệt") có thể cùng đọc status hiện tại
     * là 'pending', cùng pass canTransitionTo(), rồi cùng ghi log — ra 2 dòng ApprovalLog cho
     * cùng 1 transition thật. lockForUpdate() đảm bảo request thứ 2 phải chờ request 1 commit
     * xong, đọc lại status MỚI, và tự fail đúng InvalidTransitionException thay vì log trùng.
     *
     * $parent (entity dùng HasApproval, vd Product) BẮT BUỘC truyền vào để đồng bộ lại cache
     * quan hệ `approvalSubject` trên chính nó sau transition — bug thật phát hiện khi seed demo
     * data nhiều bước liên tiếp trên CÙNG 1 object: `transition()` luôn thao tác trên 1 bản
     * ApprovalSubject fetch RIÊNG (`$locked`, để lockForUpdate() đúng), KHÔNG phải object được
     * truyền vào; nếu không gọi $parent->setRelation() ở đây, `$parent->approvalSubject` (đã
     * cache từ HasApproval::bootHasApproval() lúc entity vừa tạo) sẽ MÃI MÃI là bản cũ trong
     * suốt vòng đời của object đó — khiến ensureApprovalSubject()/ReviseContentAction đọc nhầm
     * status cũ ở transition kế tiếp trên cùng object (vd sau Publish, sửa nội dung ngay trên
     * cùng $product sẽ không tự chuyển Pending vì đọc nhầm status 'draft' cache từ lúc tạo).
     */
    private function transition(
        Model $parent,
        ApprovalSubject $subject,
        ApprovalStatus $to,
        string $action,
        ?string $reason = null,
        array $extraAttributes = [],
    ): ApprovalSubject {
        return DB::transaction(function () use ($parent, $subject, $to, $action, $reason, $extraAttributes) {
            /** @var ApprovalSubject $locked */
            $locked = ApprovalSubject::whereKey($subject->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo($to)) {
                throw new InvalidTransitionException($locked->status->value, $to->value);
            }

            $from = $locked->status;
            $locked->update(array_merge(['status' => $to], $extraAttributes));

            ApprovalLog::create([
                'organization_id'     => $locked->organization_id,
                'approval_subject_id' => $locked->id,
                'action'              => $action,
                'from_status'         => $from->value,
                'to_status'           => $to->value,
                'reason'              => $reason,
                'performed_by'        => auth()->id(), // null nếu chạy từ job/command hệ thống
            ]);

            $parent->setRelation('approvalSubject', $locked);

            return $locked;
        });
    }
}
