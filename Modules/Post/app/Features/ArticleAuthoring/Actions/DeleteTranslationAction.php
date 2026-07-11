<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Features\ArticleAuthoring\Exceptions\CannotDeleteMainLocaleException;
use Modules\Post\Models\PostArticleTranslation;

class DeleteTranslationAction
{
    use AsAction;

    public function __construct(
        private readonly DeleteArticleAction $deleteArticle,
    ) {}

    public function handle(PostArticleTranslation $translation): void
    {
        // Chỉ còn đúng 1 bản dịch (luôn là main_locale) — không thể tồn tại "vỏ" PostArticle
        // không có translation nào, kéo theo xoá cả article. Đặt TRƯỚC điều kiện main_locale
        // bên dưới vì trường hợp này luôn thoả main_locale (không có bản nào khác để so sánh).
        if ($this->isLastTranslation($translation)) {
            $this->deleteArticle->handle($translation->article);

            return;
        }

        // main_locale phải chuyển sang locale khác trước khi xoá — không cho xoá bản dịch
        // đang là main_locale trong khi vẫn còn >1 bản khác (sẽ không còn "bản dịch chính").
        if ($translation->locale === $translation->article->main_locale) {
            throw new CannotDeleteMainLocaleException($translation->locale);
        }

        $translation->delete();
    }

    private function isLastTranslation(PostArticleTranslation $translation): bool
    {
        return $translation->article->translations()->count() === 1;
    }
}
