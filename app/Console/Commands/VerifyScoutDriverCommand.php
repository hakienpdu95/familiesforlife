<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * spec/SiteSearch_Activation_Expansion_Technical_Specification.md §4.5 — fail-fast khi
 * SCOUT_DRIVER chưa cấu hình đúng ở production/staging. Dùng làm bước chặn deploy trong
 * CI/CD (`php artisan scout:verify-driver`, exit code khác 0 nếu sai) khi repo có pipeline —
 * hiện `.github/` chưa có workflow nào, nên lệnh này để sẵn cho lần đầu thiết lập CI/CD, và
 * `AppServiceProvider::boot()` đã có kiểm tra tương đương (log critical) làm giải pháp tạm.
 */
class VerifyScoutDriverCommand extends Command
{
    protected $signature = 'scout:verify-driver';

    protected $description = 'Chặn deploy nếu SCOUT_DRIVER khác meilisearch ở production/staging (§4.5)';

    public function handle(): int
    {
        if (! app()->environment('production', 'staging')) {
            $this->info('Môi trường '.app()->environment().' — không áp dụng kiểm tra này (§4.5).');

            return self::SUCCESS;
        }

        $driver = config('scout.driver');

        if ($driver !== 'meilisearch') {
            $this->error("SCOUT_DRIVER='{$driver}' — sai cho môi trường ".app()->environment().
                ", cần 'meilisearch'. Kiểm tra .env — xem spec/SiteSearch_Activation_Expansion_Technical_Specification.md §4.5.");

            return self::FAILURE;
        }

        $this->info("SCOUT_DRIVER='meilisearch' — đúng cho môi trường ".app()->environment().'.');

        return self::SUCCESS;
    }
}
