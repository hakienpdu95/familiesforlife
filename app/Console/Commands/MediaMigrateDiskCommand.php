<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\Media\MediaMigrateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Migrate media files from one disk to another in batches.
 *
 * Usage:
 *   php artisan media:migrate-disk --from=public --to=s3
 *   php artisan media:migrate-disk --from=public --to=s3 --collection=avatar --batch=50
 *   php artisan media:migrate-disk --from=public --to=s3 --org=5 --dry-run
 *
 * Safety:
 *  - Verifies MD5 checksum before marking each record as migrated.
 *  - Halts on first checksum failure; already-migrated records are NOT rolled back.
 *  - Re-run safely: skips records already on the target disk.
 */
class MediaMigrateDiskCommand extends Command
{
    protected $signature = 'media:migrate-disk
                            {--from=      : Source disk name (required)}
                            {--to=        : Target disk name (required)}
                            {--collection= : Migrate only this collection}
                            {--org=        : Migrate only this organization_id}
                            {--batch=100   : Records per batch}
                            {--dry-run     : List what would be migrated without copying}';

    protected $description = 'Migrate media files between storage disks (e.g. public → s3)';

    public function __construct(private readonly MediaMigrateService $service) {
        parent::__construct();
    }

    public function handle(): int
    {
        $from       = $this->option('from');
        $to         = $this->option('to');
        $collection = $this->option('collection') ?: null;
        $org        = $this->option('org') ? (int) $this->option('org') : null;
        $batch      = (int) ($this->option('batch') ?? 100);
        $isDryRun   = (bool) $this->option('dry-run');

        if (! $from || ! $to) {
            $this->error('Both --from and --to are required.');
            return self::FAILURE;
        }

        $label = $isDryRun ? ' [DRY RUN]' : '';
        $this->info("Migrating disk: {$from} → {$to}{$label}");

        if ($collection) $this->line("  Collection : {$collection}");
        if ($org)        $this->line("  Org ID     : {$org}");
        $this->line("  Batch size : {$batch}");

        $total    = $this->service->buildMigrateQuery($from, $collection, $org)->count();
        $this->info("  Total records eligible: {$total}");

        if ($total === 0) {
            $this->info('Nothing to migrate.');
            return self::SUCCESS;
        }

        $migrated = 0;
        $skipped  = 0;
        $failed   = 0;
        $offset   = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        while (true) {
            $records = $this->service->buildMigrateQuery($from, $collection, $org)
                ->orderBy('id')
                ->offset($offset)
                ->limit($batch)
                ->get();

            if ($records->isEmpty()) {
                break;
            }

            foreach ($records as $media) {
                /** @var Media $media */
                try {
                    $result = $this->service->migrateToDisk($media, $to, $isDryRun);

                    match ($result['status']) {
                        'migrated', 'dry_run' => $migrated++,
                        'skipped'             => $skipped++,
                        default               => $skipped++,
                    };
                } catch (\Throwable $e) {
                    $failed++;

                    Log::error('media:migrate-disk failed', [
                        'media_id' => $media->id,
                        'uuid'     => $media->uuid,
                        'error'    => $e->getMessage(),
                    ]);

                    $bar->finish();
                    $this->newLine();
                    $this->error("HALT — checksum failure on uuid={$media->uuid}: {$e->getMessage()}");
                    $this->warn('Already-migrated records were NOT rolled back. Fix the error and re-run.');

                    return self::FAILURE;
                }

                $bar->advance();
            }

            // In dry-run mode offset advances normally since records don't change disk
            // In live mode, migrated records are no longer on $from → next query naturally excludes them
            if ($isDryRun) {
                $offset += $batch;
            }
            // Non-dry: offset stays 0 — migrated records drop off naturally from query
        }

        $bar->finish();
        $this->newLine(2);

        if ($isDryRun) {
            $this->info("DRY RUN — would migrate: {$migrated}, skip: {$skipped}");
        } else {
            $this->info("Done — migrated: {$migrated}, skipped: {$skipped}, failed: {$failed}");
        }

        return self::SUCCESS;
    }
}
