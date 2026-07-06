<?php

namespace Modules\Survey\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Survey\Models\SurveyWebhook;

class SurveyWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];
    public int $timeout = 30;

    public function __construct(
        public readonly int    $webhookId,
        public readonly string $event,
        public readonly array  $payload,
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        $webhook = SurveyWebhook::find($this->webhookId);

        if (! $webhook?->is_active) {
            return;
        }

        $body      = json_encode($this->payload);
        $signature = $webhook->secret
            ? hash_hmac('sha256', $body, $webhook->secret)
            : null;

        $headers = array_filter([
            'Content-Type'       => 'application/json',
            'X-Survey-Event'     => $this->event,
            'X-Survey-Signature' => $signature,
        ]);

        Http::withHeaders($headers)
            ->timeout(15)
            ->post($webhook->url, $this->payload);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('survey.webhook.failed', [
            'webhook_id' => $this->webhookId,
            'event'      => $this->event,
            'error'      => $e->getMessage(),
        ]);
    }
}
