<?php

namespace Modules\Newsletter\Features\BroadcastSending\Actions;

use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Newsletter\Models\NewsletterBroadcastLog;
use Resend\Laravel\Facades\Resend;

/**
 * spec/Newsletter_Technical_Specification.md §9.4 — soạn & gửi bản tin qua Resend Broadcast
 * API. KHÔNG tự xây Bus::batch/queue riêng (§0 mục 4) — Resend tự lo gửi hàng loạt.
 */
class SendBroadcastAction
{
    use AsAction;

    public function handle(string $subject, string $bodyHtml, ?string $scheduledAt = null): NewsletterBroadcastLog
    {
        if (! str_contains($bodyHtml, '{{{RESEND_UNSUBSCRIBE_URL}}}')) {
            throw new InvalidArgumentException(
                'Nội dung bản tin phải chứa merge tag {{{RESEND_UNSUBSCRIBE_URL}}} (bắt buộc để tuân thủ unsubscribe).'
            );
        }

        $broadcast = Resend::broadcasts()->create([
            'segment_id'   => config('newsletter.resend_segment_id'),
            'from'         => config('newsletter.from_address'),
            'subject'      => $subject,
            'html'         => $bodyHtml,
            'scheduled_at' => $scheduledAt, // null = không lên lịch, gửi theo bước sau
        ]);

        Resend::broadcasts()->send($broadcast->id, $scheduledAt ? ['scheduled_at' => $scheduledAt] : []);

        // §0 mục 13 — ghi log NGAY SAU khi cả 2 lệnh gọi Resend thành công (không ghi trước —
        // nếu create()/send() ném exception, không để lại log "đã gửi" sai sự thật).
        return NewsletterBroadcastLog::create([
            'resend_broadcast_id' => $broadcast->id,
            'subject'             => $subject,
            'scheduled_at'        => $scheduledAt,
            'sent_by'             => auth()->id(),
        ]);
    }
}
