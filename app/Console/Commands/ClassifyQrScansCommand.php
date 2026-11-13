<?php

namespace App\Console\Commands;

use App\Shared\Traceability\Models\QrScan;
use App\Shared\Traceability\Support\QrScanUserAgent;
use Illuminate\Console\Command;

class ClassifyQrScansCommand extends Command
{
    protected $signature = 'qr-scans:classify {--all : Phân loại lại toàn bộ, kể cả dòng đã có scan_app}';

    protected $description = 'Phân loại ứng dụng quét, hệ điều hành, hãng máy cho qr_scans từ User-Agent đã lưu';

    public function handle(): int
    {
        $query = QrScan::query()->when(! $this->option('all'), fn ($q) => $q->whereNull('scan_app'));
        $total = (clone $query)->count();
        $bar = $this->output->createProgressBar($total);
        $updated = 0;

        $query->select(['id', 'user_agent'])->chunkById(1000, function ($scans) use ($bar, &$updated) {
            foreach ($scans as $scan) {
                QrScan::query()->whereKey($scan->id)->update(QrScanUserAgent::parse($scan->user_agent));
                $updated++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Đã phân loại {$updated} lượt quét.");

        return self::SUCCESS;
    }
}
