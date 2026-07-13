<?php

namespace Modules\Post\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Features\ArticleAuthoring\Actions\PublishArticleAction;
use Modules\Post\Models\PostArticleTranslation;

/**
 * Phase 14 (spec/PublishingEngine_Technical_Specification.md §7.3) — chạy mỗi phút
 * (Schedule::job, xem PostServiceProvider::boot()), tự động chuyển translation đã tới hạn
 * scheduled_at sang published. Đây là gap có sẵn từ trước §1: ScheduleArticleAction chỉ set
 * status=scheduled, không có gì tự publish khi tới giờ.
 *
 * Post không còn tenant-scoped (spec/Platform_RBAC_Phase2_Specification.md §3.3 v3.0) —
 * trước đây job này phải withoutTenant() + Organization::find() + TenantContext::runForOrganization()
 * để bypass rồi restore đúng OrganizationScope cho từng dòng; giờ không còn scope nào để
 * bypass/restore nữa, đọc/ghi PostArticleTranslation bình thường, bớt hẳn 1 query tra cứu
 * Organization cho mỗi dòng mỗi phút.
 */
class PublishDueTranslationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        PostArticleTranslation::where('status', TranslationStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->chunkById(100, function ($translations) {
                foreach ($translations as $translation) {
                    app(PublishArticleAction::class)->handle($translation);
                }
            });
    }
}
