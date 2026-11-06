<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Display media disk usage grouped by collection.
 *
 * Usage:
 *   php artisan media:stats
 */
class MediaStatsCommand extends Command
{
    protected $signature = 'media:stats';

    protected $description = 'Show media disk usage per collection';

    public function handle(): int
    {
        $rows = DB::table('media')
            ->select([
                'collection_name',
                'disk',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(size) as total_bytes'),
            ])
            ->groupBy('collection_name', 'disk')
            ->orderBy('collection_name')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No media records found.');
            return self::SUCCESS;
        }

        $this->table(
            ['Collection', 'Disk', 'Files', 'Size'],
            $rows->map(fn ($r) => [
                $r->collection_name,
                $r->disk,
                number_format($r->count),
                $this->formatBytes($r->total_bytes),
            ])->toArray()
        );

        $this->info('Grand total: ' . number_format($rows->sum('count')) . ' files, ' . $this->formatBytes($rows->sum('total_bytes')));

        $perDisk = $rows->groupBy('disk');
        $this->newLine();
        $this->info('Per-disk summary:');
        foreach ($perDisk as $disk => $diskRows) {
            $this->line("  {$disk}: " . number_format($diskRows->sum('count')) . ' files, ' . $this->formatBytes($diskRows->sum('total_bytes')));
        }

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1_073_741_824) {
            return round($bytes / 1_073_741_824, 2) . ' GB';
        }
        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
