<?php

namespace Modules\Approval\Console\Commands;

use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\OrganizationScope;
use App\Shared\Tenancy\TenantContext;
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
 *
 * BẮT BUỘC loop qua từng Organization và set TenantContext trước khi query — Product (và mọi
 * model dùng BelongsToOrganization) có global scope `OrganizationScope`, mà khi TenantContext
 * CHƯA được set (đúng trạng thái mặc định của 1 tiến trình artisan console, không đi qua
 * middleware IdentifyOrganization) sẽ tự áp `whereRaw('0=1')` — failsafe chống rò rỉ dữ liệu
 * chéo tổ chức (xem OrganizationScope::apply()). Thiếu bước set TenantContext, lệnh này LUÔN
 * báo "Đã xử lý 0 bản ghi" bất kể có bao nhiêu Product thật trong DB — bug thật phát hiện khi
 * kiểm tra tại sao 2 sản phẩm demo có sẵn trong tổ chức "demo" chưa từng được backfill dù đã
 * chạy lệnh này "thành công" nhiều lần trước đó.
 *
 * `whereDoesntHave('approvalSubject', fn ($q) => $q->withoutGlobalScope(...))` — bắt buộc bỏ
 * OrganizationScope NGAY TRONG subquery kiểm tra "đã có ApprovalSubject chưa", KHÔNG được để
 * subquery này tự động thừa hưởng ambient TenantContext của vòng lặp org hiện tại. Bug thật
 * thứ hai phát hiện khi backfill subject_type="organization" (Organization KHÔNG tự
 * tenant-scoped — nó LÀ tenant, khác Product): ở vòng lặp org #2, subquery
 * `whereDoesntHave('approvalSubject')` vô tình bị lọc theo `organization_id = 2` (TenantContext
 * đang set), nên kết luận SAI rằng org #1 (đã có ApprovalSubject với organization_id = 1 từ
 * vòng lặp org #1) "chưa có subject" — cố insert lại, vỡ unique constraint
 * `uq_approval_subject`. Không phải entity nào cũng tự tenant-scoped như Product; subquery
 * kiểm tra tồn tại phải LUÔN nhìn thấy dữ liệu global, bất kể vòng lặp org nào đang chạy.
 */
class BackfillApprovalSubjectsCommand extends Command
{
    protected $signature = 'approval:backfill-subjects {type} {--dry-run}';

    protected $description = 'Tạo ApprovalSubject còn thiếu cho 1 subject_type khai báo trong config/approval.php, trên TẤT CẢ organization';

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

        foreach (Organization::all() as $organization) {
            TenantContext::set($organization);

            $orgCount = 0;

            $modelClass::query()->whereDoesntHave('approvalSubject', fn ($q) => $q->withoutGlobalScope(OrganizationScope::class))
                ->chunkById(200, function ($entities) use (&$orgCount, $dryRun, $type, $resolver, $organization) {
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
                            $this->line("[dry-run] org #{$organization->id} {$type} #{$entity->id} → status={$status->value}, snapshot=" . ($snapshot ? 'có' : 'không'));
                            $orgCount++;

                            continue;
                        }

                        ApprovalSubject::create([
                            'subject_type'    => $entity->getMorphClass(),
                            'subject_id'      => $entity->id,
                            'organization_id' => $entity->approvalOrganizationId(),
                            'status'          => $status,
                            'public_snapshot' => $snapshot,
                        ]);
                        $orgCount++;
                    }
                });

            $totalCount += $orgCount;
        }

        TenantContext::flush();

        $this->info(($dryRun ? '[dry-run] ' : '') . "Đã xử lý {$totalCount} bản ghi \"{$type}\" trên tất cả organization.");

        return self::SUCCESS;
    }
}
