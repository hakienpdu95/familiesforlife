<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\VersionTrigger;
use Modules\Post\Features\ArticleAuthoring\Data\TranslationData;
use Modules\Post\Features\VersionHistory\Actions\CreateArticleVersionAction;
use Modules\Post\Models\PostArticleTranslation;

class UpdateTranslationAction
{
    use AsAction;

    public function __construct(
        private readonly SyncContentBlocksAction $syncContentBlocks,
        private readonly CreateArticleVersionAction $createVersion,
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
                // spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §7 — field per-locale của sponsorship.
                'disclosure_text'  => $data->disclosure_text,
                'cta_text'         => $data->cta_text,
                'cta_url'          => $data->cta_url,
            ]);

            $this->syncContentBlocks->handle($translation, $data->blocks);

            // spec/Post_VersionHistory_Technical_Specification.md §9.4 — snapshot đóng gói
            // đồng bộ ngay đây (đọc lại dữ liệu vừa ghi), ghi DB thật sự bất đồng bộ qua queue.
            $this->createVersion->handle($translation, VersionTrigger::Save, auth()->id());

            return $translation;
        });
    }
}
