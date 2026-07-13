<?php

namespace Modules\Post\Policies;

use App\Models\User;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Models\PostArticleTranslation;

/**
 * Mọi method thao tác dữ liệu nhận PostArticleTranslation (không phải PostArticle) — xem
 * spec/PublishingEngine_Technical_Specification.md §8. Đăng ký cho CẢ 2 model class trong
 * PostServiceProvider (PostArticle::class cho viewAny/create; PostArticleTranslation::class
 * cho phần còn lại).
 */
class PostArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('post_article.view') || $user->isPlatformContentEditor() || $user->isPlatformContentHead();
    }

    /** Chỉ bài `published` nếu user không có quyền edit. content_editor/content_head luôn xem
     *  được (cần inspect nội dung trước khi duyệt). */
    public function view(User $user, PostArticleTranslation $translation): bool
    {
        if ($user->isPlatformContentEditor() || $user->isPlatformContentHead()) {
            return true;
        }

        if (! $user->can('post_article.view')) {
            return false;
        }

        return $translation->status === TranslationStatus::Published || $user->can('post_article.edit');
    }

    public function create(User $user): bool
    {
        return $user->can('post_article.create');
    }

    public function update(User $user, PostArticleTranslation $translation): bool
    {
        // loadMissing (không phải truy cập trực tiếp $translation->article) — $translation có
        // thể tới đây từ 1 collection đã eager-load (vd $article->translation($locale) qua
        // $article->translations đã load()) mà KHÔNG có inverse "article" được set, khiến truy
        // cập trực tiếp lazy-load và ném LazyLoadingViolationException thật khi
        // Model::shouldBeStrict() bật (môi trường non-production) — đã gặp lỗi thật này khi
        // test @can('submitForReview', $translation) trên trang edit với bài ≥2 bản dịch.
        $translation->loadMissing('article');

        return $user->can('post_article.edit')
            && ($translation->article->created_by === $user->id || $user->can('post_article.publish'));
    }

    public function delete(User $user, PostArticleTranslation $translation): bool
    {
        $translation->loadMissing('article');

        return $user->can('post_article.delete')
            && ($translation->article->created_by === $user->id || $user->can('post_article.publish'));
    }

    public function submitForReview(User $user, PostArticleTranslation $translation): bool
    {
        return $this->update($user, $translation);
    }

    // ── Approval workflow — Platform Approval Gateway, PHÂN CẤP 2 TẦNG cho bài viết ────
    // (Hà Kiên nội bộ — "biên tập viên duyệt sơ bộ, trưởng phòng nội dung duyệt cuối trước
    // khi hiển thị ra cổng thông tin", đúng luồng toà soạn tin tức thật). Doanh nghiệp/đội
    // marketing (cộng tác viên) chỉ tự submitForReview (gửi duyệt) — KHÔNG tự duyệt/publish
    // bài của chính mình nữa, dù có post_article.publish/unpublish hay không (permission đó
    // vẫn giữ lại, chỉ còn ý nghĩa "quyền sửa bài của người khác trong cùng tổ chức" ở
    // update()/delete(), không còn liên quan gì tới publish).
    //
    // 2 tầng ánh xạ ĐÚNG 2 bước đã có sẵn trong TranslationStatus (không cần đổi state
    // machine): content_editor (biên tập viên) duyệt Submitted → Approved; content_head
    // (trưởng phòng nội dung) duyệt CUỐI Approved → Published/Scheduled + archive/unpublish
    // (thu hồi nội dung là quyết định cấp cao hơn). content_head làm được CẢ việc của
    // content_editor (cấp trên thay thế được cấp dưới, không ngược lại) — giống phân cấp thật
    // trong tổ chức, KHÔNG áp dụng ngược (content_editor không tự publish được).

    public function approve(User $user, PostArticleTranslation $translation): bool
    {
        return $user->isPlatformContentEditor() || $user->isPlatformContentHead();
    }

    public function publish(User $user, PostArticleTranslation $translation): bool
    {
        return $user->isPlatformContentHead();
    }

    public function schedule(User $user, PostArticleTranslation $translation): bool
    {
        return $user->isPlatformContentHead();
    }

    public function archive(User $user, PostArticleTranslation $translation): bool
    {
        return $user->isPlatformContentHead();
    }

    public function unpublish(User $user, PostArticleTranslation $translation): bool
    {
        return $user->isPlatformContentHead();
    }

    /**
     * Dùng translation làm tham số cho nhất quán với các method khác trong Policy, dù field
     * sponsorship thực chất nằm ở PostArticle (spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §9) —
     * check qua $translation->article nếu cần mở rộng logic sau này. Dùng ở Blade
     * (@can('manageSponsorship', $translation)) với $translation luôn có sẵn ở trang edit;
     * controller (ArticleAdminController::removeSponsor) check permission trực tiếp thay vì
     * qua đây vì $article->mainTranslation() có thể null.
     */
    public function manageSponsorship(User $user, PostArticleTranslation $translation): bool
    {
        return $user->can('post_article.manage_sponsorship');
    }
}
