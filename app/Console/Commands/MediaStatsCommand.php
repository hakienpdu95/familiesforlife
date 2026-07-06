<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Display media disk usage grouped by organization and collection.
 *
 * Usage:
 *   php artisan media:stats
 *   php artisan media:stats --org=5
 */
class MediaStatsCommand extends Command
{
    protected $signature = 'media:stats
                            {--org= : Show stats for a specific organization_id only}';

    protected $description = 'Show media disk usage per organization and collection';

    public function handle(): int
    {
        $orgFilter = $this->option('org') ? (int) $this->option('org') : null;

        $query = DB::table('media')
            ->select([
                'organization_id',
                'collection_name',
                'disk',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(size) as total_bytes'),
            ])
            ->groupBy('organization_id', 'collection_name', 'disk')
            ->orderBy('organization_id')
            ->orderBy('collection_name');

        if ($orgFilter !== null) {
            $query->where('organization_id', $orgFilter);
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            $this->info('No media records found.');
            return self::SUCCESS;
        }

        // Group by organization for display
        $byOrg = $rows->groupBy('organization_id');

        $grandTotal = ['count' => 0, 'bytes' => 0];

        foreach ($byOrg as $orgId => $orgRows) {
            $orgName  = $this->resolveOrgName($orgId);
            $orgTotal = $orgRows->sum('total_bytes');

            $this->info("Org #{$orgId}" . ($orgName ? " — {$orgName}" : ''));

            $this->table(
                ['Collection', 'Disk', 'Files', 'Size'],
                $orgRows->map(fn ($r) => [
                    $r->collection_name,
                    $r->disk,
                    number_format($r->count),
                    $this->formatBytes($r->total_bytes),
                ])->toArray()
            );

            $this->line("  Subtotal: " . number_format($orgRows->sum('count')) . " files, " . $this->formatBytes($orgTotal));
            $this->newLine();

            $grandTotal['count'] += $orgRows->sum('count');
            $grandTotal['bytes'] += $orgTotal;
        }

        $this->info('─────────────────────────────────────────────────────');
        $this->info('Grand total: ' . number_format($grandTotal['count']) . ' files, ' . $this->formatBytes($grandTotal['bytes']));

        // Per-disk summary
        $perDisk = $rows->groupBy('disk');
        $this->newLine();
        $this->info('Per-disk summary:');
        foreach ($perDisk as $disk => $diskRows) {
            $this->line("  {$disk}: " . number_format($diskRows->sum('count')) . ' files, ' . $this->formatBytes($diskRows->sum('total_bytes')));
        }

        return self::SUCCESS;
    }

    private function resolveOrgName(?int $orgId): ?string
    {
        if ($orgId === null) {
            return '(system)';
        }

        return DB::table('organizations')->where('id', $orgId)->value('name');
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
