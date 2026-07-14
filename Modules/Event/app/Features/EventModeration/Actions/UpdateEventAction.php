<?php

namespace Modules\Event\Features\EventModeration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Event\Enums\EventStatus;
use Modules\Event\Features\EventModeration\Data\EventData;
use Modules\Event\Models\Event;

/**
 * UpdateEventAction — sửa nội dung, tách biệt approve()/publish() (spec §6.1). Cho phép khi
 * status ∈ {Submitted, Approved} — sự kiện đã Published không sửa qua đây (đã công khai, cần
 * quy trình khác ngoài phạm vi đặc tả hiện tại).
 */
class UpdateEventAction
{
    use AsAction;

    public function __construct(private readonly BuildEventAttributesAction $buildAttributes) {}

    public function handle(Event $event, EventData $data): Event
    {
        abort_if(
            ! in_array($event->status, [EventStatus::Submitted, EventStatus::Approved], true),
            422,
            'Không thể sửa sự kiện đã xuất bản.'
        );

        $attributes = $this->buildAttributes->handle($data);

        // Chỉ ghi đè poster khi có ảnh mới — giữ nguyên ảnh cũ nếu form sửa không đổi ảnh.
        if ($data->poster_path) {
            $attributes['poster_path'] = $data->poster_path;
        }

        $attributes['updated_by'] = auth()->id();

        $event->update($attributes);

        return $event;
    }
}
