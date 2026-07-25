<?php

namespace Modules\CoreIdeaExtractor\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Post\Models\PostCategory;
use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * spec/CoreIdeaExtractor.md §12 (v1.4) — module giờ có 1 Eloquent Model
 * (CategoryContentFoundation, xem app/Models/), nhưng KHÔNG đăng ký Gate::policy() cho
 * PostCategory ở đây: Modules\Post\Providers\PostServiceProvider đã đăng ký PostCategoryPolicy
 * cho model đó rồi, đăng ký policy thứ 2 cho CÙNG 1 model sẽ ghi đè lẫn nhau. Thay vào đó dùng
 * Gate::define() với 1 ability RIÊNG (không đụng namespace ability của Post) — vẫn theo đúng
 * style "gate bằng permission string" hiện có của module (xem routes/web.php
 * 'can:core_idea_extractor.use'), chỉ khác là ability này nhận thêm PostCategory làm tham số.
 */
class CoreIdeaExtractorServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'CoreIdeaExtractor';
    protected string $nameLower = 'coreideaextractor';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        // nwidart đăng ký config module dưới key lồng `coreideaextractor.core_idea_extractor`
        // (xem Modules/WorkflowAutomation/app/Providers/WorkflowAutomationServiceProvider.php
        // — cùng pattern) — expose lại ở key top-level `core_idea_extractor` để code đọc gọn
        // (`config('core_idea_extractor.confidence...')`).
        $this->mergeConfigFrom(
            __DIR__.'/../../config/core_idea_extractor.php',
            'core_idea_extractor'
        );
    }

    public function boot(): void
    {
        parent::boot();

        // Cùng pattern role-check với Modules\Post\Policies\PostArticlePolicy::approve() —
        // content_editor/content_head sửa được MỌI category (đã có core_idea_extractor.use
        // không giới hạn từ trước, không nên siết lại); section_editor chỉ sửa được category
        // mình được gán qua post_category_editors (postCategoryEditorships()).
        Gate::define('core_idea_extractor.manage_category_foundation', function (User $user, PostCategory $category): bool {
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
