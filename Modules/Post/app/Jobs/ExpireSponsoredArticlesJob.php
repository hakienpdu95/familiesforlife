<?php

namespace Modules\Post\Jobs;

use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Post\Features\ArticleAuthoring\Notifications\SponsorshipExpiredNotification;
use Modules\Post\Models\PostArticle;

/**
 * spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §8 — đúng pattern PublishDueTranslationsJob (Phase 14):
 * job hệ thống, không thuộc 1 tenant cụ thể, withoutTenant() khi đọc + restore đúng TenantContext
 * từng dòng trước khi ghi/gửi notification. Chạy daily (không cần everyMinute như publish-due) —
 * hết hạn tài trợ tính theo date, không theo giờ (§13).
 */
class ExpireSponsoredArticlesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Queue 'low' được set khi ĐĂNG KÝ lịch (PostServiceProvider::boot(), tham số thứ 2 của
    // Schedule::job()), KHÔNG khai property $queue ở đây — trait Queueable đã khai
    // `public $queue;` (không type, không default); khai lại property cùng tên với default khác
    // vi phạm quy tắc composition property của PHP trait (fatal error thật gặp khi lint: "define
    // the same property... definition differs and is considered incompatible").
    public function handle(): void
    {
        PostArticle::withoutTenant()
            ->where('is_sponsored', true)
            ->whereNotNull('sponsored_end_date')
            ->where('sponsored_end_date', '<', now()->toDateString())
            ->chunkById(100, function ($articles) {
                foreach ($articles as $article) {
                    // Cô lập lỗi TỪNG bài — 1 tổ chức/bài lỗi (vd Organization vừa bị xoá cứng
                    // giữa lúc job chạy, notification gửi thất bại...) không được làm hỏng cả
                    // chunk/job, các bài còn lại vẫn phải được xử lý.
                    try {
                        $org = Organization::withoutGlobalScopes()->find($article->organization_id);

                        if (! $org) {
                            continue;
                        }

                        TenantContext::runForOrganization($org, function () use ($article) {
                            // §0 — CHỈ tắt is_sponsored, KHÔNG đổi TranslationStatus/unpublish.
                            $article->update(['is_sponsored' => false]);

                            $this->notifyExpired($article);
                        });
                    } catch (\Throwable $e) {
                        Log::error('ExpireSponsoredArticlesJob: lỗi xử lý article', [
                            'article_id'      => $article->id,
                            'organization_id' => $article->organization_id,
                            'exception'       => $e->getMessage(),
                        ]);
                        // Không rethrow — tiếp tục với bài tiếp theo trong chunk. Bài lỗi vẫn còn
                        // is_sponsored=true + sponsored_end_date cũ nên lần chạy job KẾ TIẾP (ngày
                        // mai) sẽ tự động thử lại, không cần cơ chế retry riêng.
                    }
                }
            });
    }

    private function notifyExpired(PostArticle $article): void
    {
        $editors = User::where('organization_id', $article->organization_id)
            ->role(['marketing', 'ceo'])
            ->get();

        if ($editors->isNotEmpty()) {
            Notification::send($editors, new SponsorshipExpiredNotification($article));
        }
    }
}
