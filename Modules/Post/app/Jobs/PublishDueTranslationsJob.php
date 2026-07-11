<?php

namespace Modules\Post\Jobs;

use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
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
 * Job này xử lý CẢ hệ thống (mọi tổ chức), không thuộc 1 tenant cụ thể tại thời điểm chạy
 * (khác job dispatch từ 1 request trong TenantAwareJob) — nên đọc bằng withoutTenant() để
 * bypass OrganizationScope, rồi restore đúng TenantContext của từng translation trước khi
 * gọi PublishArticleAction, để log/event listener chạy đúng ngữ cảnh tổ chức (cùng pattern
 * Modules\Subscription\...\ProcessExpiringSubscriptionsCommand).
 */
class PublishDueTranslationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        PostArticleTranslation::withoutTenant()
            ->where('status', TranslationStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->chunkById(100, function ($translations) {
                foreach ($translations as $translation) {
                    $org = Organization::withoutGlobalScopes()->find($translation->organization_id);

                    if (! $org) {
                        continue;
                    }

                    TenantContext::runForOrganization($org, function () use ($translation) {
                        app(PublishArticleAction::class)->handle($translation);
                    });
                }
            });
    }
}
