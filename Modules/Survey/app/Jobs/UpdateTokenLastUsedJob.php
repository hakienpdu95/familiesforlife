<?php

namespace Modules\Survey\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Survey\Models\SurveyToken;

class UpdateTokenLastUsedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(private readonly int $tokenId)
    {
        $this->onQueue('low');
    }

    public function handle(): void
    {
        SurveyToken::whereKey($this->tokenId)->update(['last_used_at' => now()]);
    }
}
