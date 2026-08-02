<?php

namespace Modules\ContentFoundation\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Post\Models\PostCategory;
use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * spec/CoreIdeaExtractor.md §12 — tách từ CoreIdeaExtractorServiceProvider (module dùng chung cho
 * mọi công cụ nghiên cứu ý tưởng nội dung theo category — CoreIdeaExtractor, VideoIdeaExtractor...).
 * KHÔNG đăng ký Gate::policy() cho PostCategory ở đây: Modules\Post\Providers\PostServiceProvider
 * đã đăng ký PostCategoryPolicy cho model đó rồi, đăng ký policy thứ 2 cho CÙNG 1 model sẽ ghi đè
 * lẫn nhau. Dùng Gate::define() với 1 ability RIÊNG, nhận thêm PostCategory làm tham số.
 */
class ContentFoundationServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'ContentFoundation';
    protected string $nameLower = 'contentfoundation';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        // nwidart đăng ký config module dưới key lồng `contentfoundation.content_foundation` —
        // expose lại ở key top-level `content_foundation` để code đọc gọn
        // (`config('content_foundation.family_values...')`).
        $this->mergeConfigFrom(
            __DIR__.'/../../config/content_foundation.php',
            'content_foundation'
        );
    }

    public function boot(): void
    {
        parent::boot();

        // Cùng pattern role-check với Modules\Post\Policies\PostArticlePolicy::approve() —
        // content_editor/content_head sửa được MỌI category; section_editor chỉ sửa được category
        // mình được gán qua post_category_editors (postCategoryEditorships()).
        Gate::define('content_foundation.manage_category_foundation', function (User $user, PostCategory $category): bool {
            if ($user->isPlatformContentEditor() || $user->isPlatformContentHead()) {
                return true;
            }

            if ($user->isPlatformSectionEditor()) {
                return $user->postCategoryEditorships()
                    ->where('post_categories.id', $category->id)
                    ->exists();
            }

            return false;
        });
    }
}
