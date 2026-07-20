<?php

namespace Modules\Newsletter\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Resend\Laravel\Facades\Resend;

/**
 * spec/Newsletter_Technical_Specification.md §9.5 — cùng cấu trúc retry với
 * SyncSubscriberToResendJob, tách file riêng vì mục đích khác (unsubscribe, không phải tạo/cập
 * nhật). KHÔNG gọi Resend::contacts()->remove() (xoá cứng) — xem §9.5/§16 mục 4.
 */
class UnsubscribeSubscriberFromResendJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 300, 900];

    public function __construct(private readonly string $resendContactId) {}

    public function handle(): void
    {
        Resend::contacts()->update($this->resendContactId, ['unsubscribed' => true]);
    }
}
