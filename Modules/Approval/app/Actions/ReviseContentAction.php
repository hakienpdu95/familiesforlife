<?php

namespace Modules\Approval\Actions;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Approval\Actions\Concerns\LogsApprovalActions;
use Modules\Approval\Enums\ApprovalStatus;
use Modules\Approval\Exceptions\InvalidTransitionException;
use Modules\Approval\Models\ApprovalSubject;

/**
 * Chạy tự động (HasApproval::bootHasApproval(), spec §7.1) mỗi khi 1 trường trong
 * approvalWatchedAttributes() đổi. KHÔNG map trực tiếp tới 1 nút bấm nào trên UI — đây là
 * phản ứng hệ thống, không phải hành động người dùng chủ động chọn (khác 5 Action còn lại).
 */
class ReviseContentAction
{
    use AsAction;
    use LogsApprovalActions;

    public function handle(Model $subject): ApprovalSubject
    {
        $approval = $subject->ensureApprovalSubject();

        return match ($approval->status) {
            // Nội dung đã qua duyệt (Approved) hoặc đã live (Published) mà bị sửa → đánh dấu
            // "đang có bản chờ duyệt" (status=Pending) để hiện lên dashboard/nút "Duyệt lại".
            // CỐ Ý không đụng tới public_snapshot ở đây — cổng thông tin vẫn tiếp tục hiển thị
            // đúng bản đã duyệt trước đó (isPubliclyVisible() không phụ thuộc status), chỉ khi
            // PublishAction chạy lại thì snapshot mới đổi sang bản mới.
            ApprovalStatus::Approved, ApprovalStatus::Published
                => $this->transition($subject, $approval, ApprovalStatus::Pending, 'revise'),

            // Archived coi là read-only — sửa nội dung khi đã lưu trữ là bug ở tầng Policy của
            // module tiêu thụ (đáng lẽ phải chặn từ trước); ném exception ở đây như 1 lớp
            // phòng vệ thứ 2 (defense-in-depth), KHÔNG âm thầm bỏ qua.
            ApprovalStatus::Archived
                => throw new InvalidTransitionException($approval->status->value, ApprovalStatus::Pending->value),

            // Draft: chưa từng submit, sửa thoải mái, không cần transition.
            // Pending: đang chờ duyệt sẵn rồi, sửa thêm không cần transition (không tạo log
            // 'revise' trùng lặp vô nghĩa).
            //
            // Lưu ý: CHỈ nhánh Approved/Published ở trên gọi transition() — đây là nơi DUY
            // NHẤT trong hàm này tạo ApprovalLog (action=revise). Nhánh `default` (Draft/
            // Pending) trả thẳng $approval không đổi gì; nhánh Archived throw exception TRƯỚC
            // khi có cơ hội gọi transition(). Vì vậy log 'revise' chỉ xuất hiện đúng lúc có 1
            // bản đã duyệt/đã live thật sự bị sửa — không bao giờ có log rác cho Draft/Pending.
            default => $approval,
        };
    }
}
