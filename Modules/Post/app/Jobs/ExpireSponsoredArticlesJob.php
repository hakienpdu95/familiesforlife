<?php

namespace Modules\Post\Jobs;

use App\Models\User;
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
 * Chạy daily (không cần everyMinute như publish-due) — hết hạn tài trợ tính theo date, không
 * theo giờ. Post không còn tenant-scoped (spec/Platform_RBAC_Phase2_Specification.md §3.3
 * v3.0) nên job này đọc/ghi PostArticle bình thường, không cần withoutTenant()/TenantContext
 * nào — người nhận thông báo cũng không còn theo tổ chức, xem notifyExpired().
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
        PostArticle::where('is_sponsored', true)
            ->whereNotNull('sponsored_end_date')
            ->where('sponsored_end_date', '<', now()->toDateString())
            ->chunkById(100, function ($articles) {
                foreach ($articles as $article) {
                    // Cô lập lỗi TỪNG bài — 1 bài lỗi (vd notification gửi thất bại...) không
                    // được làm hỏng cả chunk/job, các bài còn lại vẫn phải được xử lý.
                    try {
                        // §0 — CHỈ tắt is_sponsored, KHÔNG đổi TranslationStatus/unpublish.
                        $article->update(['is_sponsored' => false]);

                        $this->notifyExpired($article);
                    } catch (\Throwable $e) {
                        Log::error('ExpireSponsoredArticlesJob: lỗi xử lý article', [
                            'article_id' => $article->id,
                            'exception'  => $e->getMessage(),
                        ]);
                        // Không rethrow — tiếp tục với bài tiếp theo trong chunk. Bài lỗi vẫn còn
                        // is_sponsored=true + sponsored_end_date cũ nên lần chạy job KẾ TIẾP (ngày
                        // mai) sẽ tự động thử lại, không cần cơ chế retry riêng.
                    }
                }
            });
    }

    /**
     * spec/Platform_RBAC_Phase2_Specification.md §3.3 mục 3 (v3.0) — bài viết không còn gắn
     * với Organization nào (kể cả tổ chức tài trợ — không có UI để chọn, xem lý do đầy đủ ở
     * spec) nên không thể tự động tìm đúng user của doanh nghiệp tài trợ để báo. Báo cho nhân
     * sự nền tảng (platform_content_head/platform_ops) kèm sponsor_name để họ tự liên hệ lại
     * đúng doanh nghiệp qua kênh sales/CRM riêng, ngoài phạm vi hệ thống này.
     */
    private function notifyExpired(PostArticle $article): void
    {
        $recipients = User::withGlobalRole(['platform_content_head', 'platform_ops']);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new SponsorshipExpiredNotification($article));
        }
    }
}
