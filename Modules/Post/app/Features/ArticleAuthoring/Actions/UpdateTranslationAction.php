<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Features\ArticleAuthoring\Data\TranslationData;
use Modules\Post\Models\PostArticleTranslation;

class UpdateTranslationAction
{
    use AsAction;

    public function __construct(
        private readonly SyncContentBlocksAction $syncContentBlocks,
    ) {}

    /** Slug giữ nguyên sau khi tạo (tránh vỡ link đã chia sẻ) nếu không truyền slug mới tường minh. */
    public function handle(PostArticleTranslation $translation, TranslationData $data): PostArticleTranslation
    {
        return DB::transaction(function () use ($translation, $data) {
            $translation->update([
                'title'            => $data->title,
                'slug'             => $data->slug ?: $translation->slug,
                'excerpt'          => $data->excerpt,
                'seo_title'        => $data->seo_title,
                'seo_description'  => $data->seo_description,
            ]);

            $this->syncContentBlocks->handle($translation, $data->blocks);

            return $translation;
        });
    }
}
