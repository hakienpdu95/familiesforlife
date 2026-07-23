<?php

namespace Modules\Post\Features\RelatedPosts\Actions;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostArticleViewEvent;

/**
 * spec/Related_Posts_Engine_Technical_Specification.md §6.1 — ghi 1 dòng / lượt đọc bài viết
 * công khai (KHÔNG gọi cho nhánh format=redirect, xem PublicArticleController::show()), dùng
 * cho thuật toán tính "đồng-xem" (co-occurrence) ở GetRelatedArticlesHandler.
 *
 * §10.1 — KHÔNG có dedup khi ghi (cùng visitor_hash refresh liên tục vẫn tạo nhiều dòng), kế
 * thừa đúng hành vi IncrementArticleViewCountAction đã có sẵn — chấp nhận được vì
 * COUNT(DISTINCT visitor_hash) ở GetRelatedArticlesHandler đã trung hoà tác động lên điểm số,
 * chỉ ảnh hưởng dung lượng bảng (xem spec §10.1 để biết ngưỡng cần theo dõi).
 */
class RecordArticleViewEventAction
{
    use AsAction;

    public function handle(int $articleId): void
    {
        PostArticleViewEvent::create([
            'article_id'   => $articleId,
            'visitor_hash' => $this->resolveVisitorHash(),
            'viewed_at'    => now(),
        ]);
    }

    /**
     * Cookie ẩn danh first-party (§0 "Định danh người xem") — KHÔNG dùng session Laravel
     * (rotate khi hết hạn/đăng nhập, không hợp cho theo dõi dài hạn ẩn danh qua nhiều lượt ghé
     * site khác ngày). Cookie::queue() không cần Response object — AddQueuedCookiesToResponse
     * (middleware mặc định nhóm 'web') tự đính vào response cuối cùng.
     */
    private function resolveVisitorHash(): string
    {
        $cookieName = config('post.related_posts.visitor_cookie_name', 'rp_vid');
        $existing   = request()->cookie($cookieName);

        if ($existing) {
            return $existing;
        }

        $hash = Str::random(64);
        $days = (int) config('post.related_posts.visitor_cookie_days', 365);

        Cookie::queue(Cookie::make($cookieName, $hash, $days * 1440));

        return $hash;
    }
}
