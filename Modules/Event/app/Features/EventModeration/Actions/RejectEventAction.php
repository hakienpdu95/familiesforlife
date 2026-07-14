<?php

namespace Modules\Event\Features\EventModeration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Event\Enums\EventStatus;
use Modules\Event\Models\Event;

/**
 * platform_content_editor/head — từ chối: Submitted|Approved → Rejected (terminal, spec §6).
 * Độc giả nộp qua form public không có tài khoản để sửa & nộp lại — bắt buộc kèm lý do rõ ràng
 * để gửi email cho họ (§11.2), không phải chỉ 1 nút "Từ chối" trần trụi.
 */
class RejectEventAction
{
    use AsAction;

    public function handle(Event $event, string $reason): Event
    {
        abort_unless(
            $event->status->canTransitionTo(EventStatus::Rejected),
            422,
            "Không thể từ chối sự kiện đang ở trạng thái \"{$event->status->label()}\"."
        );

        $event->update([
            'status'          => EventStatus::Rejected,
            'rejected_by'     => auth()->id(),
            'rejected_at'     => now(),
            'rejected_reason' => $reason,
        ]);

        return $event;
    }
}
