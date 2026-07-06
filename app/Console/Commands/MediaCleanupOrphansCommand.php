<?php

namespace App\Console\Commands;

use App\Models\FilePondDraft;
use App\Models\JoditDraft;
use App\Models\Media;
use App\Services\Media\MediaUploadService;
use App\Shared\Tenancy\OrganizationScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Cleanup orphan media for both Jodit and FilePond — completely independent.
 *
 * Jodit orphans:    collection='jodit_content', model_type=JoditDraft, TTL from last_touched_at
 * FilePond orphans: any FilePond collection,    model_type=FilePondDraft, TTL from created_at
 *
 * Usage:
 *   php artisan media:cleanup-orphans
 *   php artisan media:cleanup-orphans --dry-run
 */
class MediaCleanupOrphansCommand extends Command
{
    protected $signature = 'media:cleanup-orphans
                            {--dry-run : Print what would be deleted without actually deleting}';

    protected $description = 'Delete Jodit and FilePond orphan media older than TTL, then prune empty draft records';

    public function __construct(private readonly MediaUploadService $uploadService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->cleanupJoditOrphans($isDryRun);
        $this->line('');
        $this->cleanupFilePondOrphans($isDryRun);

        return self::SUCCESS;
    }

    // ── Jodit ──────────────────────────────────────────────────────────────

    private function cleanupJoditOrphans(bool $isDryRun): void
    {
        $ttlHours = (int) config('media.jodit_orphan_ttl_hours', 72);
        $cutoff   = now()->subHours($ttlHours);

        $this->info("[Jodit] Checking orphans older than {$ttlHours}h" . ($isDryRun ? ' [DRY RUN]' : '') . '...');

        $orphans = Media::withoutTenant()
            ->where('collection_name', 'jodit_content')
            ->where('model_type', JoditDraft::class)
            ->where(function ($q) use ($cutoff) {
                $q->where('last_touched_at', '<', $cutoff)
                  ->orWhereNull('last_touched_at');
            })
            ->get();

        if ($orphans->isEmpty()) {
            $this->line('[Jodit] No orphan media found.');
        } else {
            $this->info("[Jodit] Found {$orphans->count()} orphan media.");
        }

        $deleted = 0;
        foreach ($orphans as $media) {
            if ($isDryRun) {
                $this->line("  [Jodit] Would delete: {$media->uuid} — {$media->file_name}");
                continue;
            }
            try {
                $this->uploadService->delete($media);
                $deleted++;
            } catch (\Throwable $e) {
                Log::error('media:cleanup-orphans [jodit] failed', [
                    'media_id' => $media->id,
                    'uuid'     => $media->uuid,
                    'error'    => $e->getMessage(),
                ]);
                $this->warn("  [Jodit] Failed {$media->uuid}: {$e->getMessage()}");
            }
        }

        if (! $isDryRun && $deleted > 0) {
            $this->info("[Jodit] Deleted {$deleted} orphan media file(s).");
        }

        // Prune empty JoditDraft records
        $emptyQuery = JoditDraft::withoutGlobalScope(OrganizationScope::class)
            ->where('created_at', '<', $cutoff)
            ->whereDoesntHave('media', fn ($q) => $q->where('collection_name', 'jodit_content'));

        if ($isDryRun) {
            $count = $emptyQuery->count();
            if ($count > 0) $this->line("  [Jodit] Would prune {$count} empty JoditDraft record(s).");
        } else {
            $pruned = $emptyQuery->delete();
            if ($pruned > 0) $this->info("[Jodit] Pruned {$pruned} empty JoditDraft record(s).");
        }
    }

    // ── FilePond ───────────────────────────────────────────────────────────

    private function cleanupFilePondOrphans(bool $isDryRun): void
    {
        $ttlHours = (int) config('media.filepond_orphan_ttl_hours', 72);
        $cutoff   = now()->subHours($ttlHours);

        $this->info("[FilePond] Checking orphans older than {$ttlHours}h" . ($isDryRun ? ' [DRY RUN]' : '') . '...');

        // FilePond has no touch mechanism — use media.created_at as TTL basis
        $orphans = Media::withoutTenant()
            ->where('model_type', FilePondDraft::class)
            ->where(function ($q) use ($cutoff) {
                $q->where('created_at', '<', $cutoff)
                  ->orWhereNull('created_at');
            })
            ->get();

        if ($orphans->isEmpty()) {
            $this->line('[FilePond] No orphan media found.');
        } else {
            $this->info("[FilePond] Found {$orphans->count()} orphan media.");
        }

        $deleted = 0;
        foreach ($orphans as $media) {
            if ($isDryRun) {
                $this->line("  [FilePond] Would delete: {$media->uuid} — {$media->file_name} (collection: {$media->collection_name})");
                continue;
            }
            try {
                $this->uploadService->delete($media);
                $deleted++;
            } catch (\Throwable $e) {
                Log::error('media:cleanup-orphans [filepond] failed', [
                    'media_id' => $media->id,
                    'uuid'     => $media->uuid,
                    'error'    => $e->getMessage(),
                ]);
                $this->warn("  [FilePond] Failed {$media->uuid}: {$e->getMessage()}");
            }
        }

        if (! $isDryRun && $deleted > 0) {
            $this->info("[FilePond] Deleted {$deleted} orphan media file(s).");
        }

        // Prune empty FilePondDraft records
        $emptyQuery = FilePondDraft::withoutGlobalScope(OrganizationScope::class)
            ->where('created_at', '<', $cutoff)
            ->doesntHave('media');

        if ($isDryRun) {
            $count = $emptyQuery->count();
            if ($count > 0) $this->line("  [FilePond] Would prune {$count} empty FilePondDraft record(s).");
        } else {
            $pruned = $emptyQuery->delete();
            if ($pruned > 0) $this->info("[FilePond] Pruned {$pruned} empty FilePondDraft record(s).");
        }
    }
}
