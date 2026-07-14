<?php

namespace Modules\Event\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Event\Enums\EventStatus;
use Modules\Event\Models\Event;

/**
 * spec/Event_Management_Technical_Specification.md §11.1 — chạy daily (hết hạn tính theo ngày,
 * không theo giờ). §11.2: KHÔNG gửi thông báo nào khi Expired — độc giả không có tài khoản để
 * "thấy", và đây là chuyển trạng thái tự động thường lệ, không phải kết quả duyệt cần phản hồi.
 */
class ExpirePastEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Event::where('status', EventStatus::Published)
            ->where('end_date', '<', now()->toDateString())
            ->chunkById(100, function ($events) {
                foreach ($events as $event) {
                    try {
                        $event->update(['status' => EventStatus::Expired]);
                    } catch (\Throwable $e) {
                        Log::error('ExpirePastEventsJob: lỗi xử lý event', [
                            'event_id'  => $event->id,
                            'exception' => $e->getMessage(),
                        ]);
                        // Không rethrow — event lỗi vẫn còn end_date cũ, job ngày mai tự thử lại.
                    }
                }
            });
    }
}
