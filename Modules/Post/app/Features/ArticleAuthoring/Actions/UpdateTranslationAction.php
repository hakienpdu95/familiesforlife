<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use App\Services\Media\MediaUploadService;
use App\Services\Media\MediaUrlService;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\ContentBlockType;
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
        private readonly MediaUploadService $mediaUpload,
        private readonly MediaUrlService $mediaUrl,
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
                'direct_answer'    => $data->direct_answer,
                // spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §7 — field per-locale của sponsorship.
                'disclosure_text'  => $data->disclosure_text,
                'cta_text'         => $data->cta_text,
                'cta_url'          => $data->cta_url,
            ]);

            $this->syncContentBlocks->handle($translation, $data->blocks);

            // spec/Media_Library_Technical_Specification.md §5.2/§7.2 — ảnh chèn qua Jodit vào
            // content block sống tạm ở JoditDraft (đính kèm UUID `data-media-uuid` trong
            // text_html) cho tới khi bài được lưu — touch-point này "nhận" ảnh vào chính
            // translation thật, đồng thời gỡ ảnh không còn được nhắc tới trong nội dung mới.
            $this->mediaUpload->reassociateOrphans($translation, $this->mediaUuidsIn($translation));
            $this->refreshEmbeddedImageUrls($translation);

            // spec/Post_VersionHistory_Technical_Specification.md §9.4 — snapshot đóng gói
            // đồng bộ ngay đây (đọc lại dữ liệu vừa ghi), ghi DB thật sự bất đồng bộ qua queue.
            $this->createVersion->handle($translation, VersionTrigger::Save, auth()->id());

            return $translation;
        });
    }

    /**
     * Sau reassociateOrphans() file ảnh đã chuyển từ thư mục JoditDraft sang thư mục của
     * translation — cập nhật lại `src` nhúng trong text_html (vẫn trỏ path draft cũ, 403).
     */
    private function refreshEmbeddedImageUrls(PostArticleTranslation $translation): void
    {
        foreach ($translation->contentBlocks()->where('type', ContentBlockType::Text)->get(['id', 'text_html']) as $block) {
            $html = $this->mediaUrl->refreshEmbeddedImageUrls($block->text_html);

            if ($html !== $block->text_html) {
                $translation->contentBlocks()->whereKey($block->id)->update(['text_html' => $html]);
            }
        }
    }

    /**
     * Quét `data-media-uuid="..."` trong text_html của mọi content block TEXT vừa lưu (đã qua
     * `ArticleContentRenderer::sanitizeTextHtml()` — attribute này không nằm trong blocklist nên
     * còn nguyên). Query lại DB (không dùng property đã cache) — cùng lý do
     * `PostArticleTranslation::toSearchableArray()` đã ghi chú: `syncContentBlocks` vừa
     * xoá-tạo-lại toàn bộ `post_content_blocks` trong cùng transaction này.
     *
     * @return string[]
     */
    private function mediaUuidsIn(PostArticleTranslation $translation): array
    {
        $uuids = [];

        foreach ($translation->contentBlocks()->where('type', ContentBlockType::Text)->pluck('text_html') as $html) {
            if (preg_match_all('/data-media-uuid="([^"]+)"/', (string) $html, $matches)) {
                $uuids = array_merge($uuids, $matches[1]);
            }
        }

        return array_values(array_unique($uuids));
    }
}
