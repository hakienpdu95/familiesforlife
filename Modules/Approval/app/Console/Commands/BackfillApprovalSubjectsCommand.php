<?php

namespace Modules\Approval\Console\Commands;

use Illuminate\Console\Command;
use Modules\Approval\Enums\ApprovalStatus;
use Modules\Approval\Models\ApprovalSubject;

/**
 * spec/Workflow_Approval_Technical_Specification.md §4.1 — theo đúng mẫu
 * Modules/Post/app/Console/Commands/BackfillPostTranslationsCommand.php: idempotent (bỏ qua
 * entity đã có ApprovalSubject), chunkById, --dry-run.
 *
 * Chạy `php artisan approval:backfill-subjects {type} --dry-run` trước để soát, chạy thật rồi
 * chạy LẠI đúng lệnh đó (không --dry-run) một lần nữa để xác nhận idempotent — lần 2 phải in
 * ra "Đã xử lý 0 bản ghi".
 */
class BackfillApprovalSubjectsCommand extends Command
{
    protected $signature = 'approval:backfill-subjects {type} {--dry-run}';

    protected $description = 'Tạo ApprovalSubject còn thiếu cho 1 subject_type khai báo trong config/approval.php';

    public function handle(): int
    {
        $type = $this->argument('type');
        $config = config("approval.subjects.{$type}");

        if (! $config) {
            $this->error("Subject type \"{$type}\" chưa khai báo trong config/approval.php");

            return self::FAILURE;
        }

        $modelClass = $config['model'];
        $resolver   = $config['initial_status_resolver'] ?? null;
        $dryRun     = (bool) $this->option('dry-run');
        $totalCount = 0;

        $modelClass::query()->whereDoesntHave('approvalSubject')
            ->chunkById(200, function ($entities) use (&$totalCount, $dryRun, $type, $resolver) {
                foreach ($entities as $entity) {
                    $status = $resolver ? $resolver::resolve($entity) : ApprovalStatus::Draft;

                    // Nếu coi entity cũ là "đã publish", PHẢI set luôn public_snapshot ngay
                    // bằng nội dung hiện tại — thiếu bước này, isPubliclyVisible()/
                    // scopePubliclyVisible() sẽ loại toàn bộ dữ liệu cũ khỏi cổng thông
                    // tin dù status=published, vì tiêu chí hiển thị dựa vào
                    // public_snapshot chứ không phải status.
                    $snapshot = $status === ApprovalStatus::Published
                        ? collect($entity->approvalWatchedAttributes())->mapWithKeys(fn ($a) => [$a => $entity->getAttribute($a)])->all()
                        : null;

                    if ($dryRun) {
                        $this->line("[dry-run] {$type} #{$entity->id} → status={$status->value}, snapshot=" . ($snapshot ? 'có' : 'không'));
                        $totalCount++;

                        continue;
                    }

                    ApprovalSubject::create([
                        'subject_type'    => $entity->getMorphClass(),
                        'subject_id'      => $entity->id,
                        'status'          => $status,
                        'public_snapshot' => $snapshot,
                    ]);
                    $totalCount++;
                }
            });

        $this->info(($dryRun ? '[dry-run] ' : '') . "Đã xử lý {$totalCount} bản ghi \"{$type}\".");

        return self::SUCCESS;
    }
}
