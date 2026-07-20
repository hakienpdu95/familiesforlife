<?php

namespace Modules\Post\Features\VersionHistory\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\VersionTrigger;
use Modules\Post\Features\ArticleAuthoring\Actions\SyncContentBlocksAction;
use Modules\Post\Features\VersionHistory\Exceptions\VersionRestoreException;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostArticleVersion;
use Modules\Product\Models\Product;

/**
 * spec/Post_VersionHistory_Technical_Specification.md §9.5 — tái dùng nguyên
 * SyncContentBlocksAction (đúng shape §4), thừa hưởng upsert-by-key (bảo toàn click_count).
 * Restore tạo version MỚI (không ghi đè version cũ), KHÔNG đụng status/published_at (§0).
 */
class RestoreArticleVersionAction
{
    use AsAction;

    public function __construct(
        private readonly SyncContentBlocksAction $syncContentBlocks,
        private readonly CreateArticleVersionAction $createVersion,
    ) {}

    public function handle(PostArticleVersion $version, int $userId): PostArticleTranslation
    {
        $translation = $version->translation;
        $snapshot    = $version->snapshot;

        $this->assertProductsStillExist($snapshot['blocks']); // §11 — fail fast, không restore 1 phần

        return DB::transaction(function () use ($translation, $version, $snapshot, $userId) {
            $translation->update($snapshot['translation']); // KHÔNG đụng status/published_at (§0)
            $this->syncContentBlocks->handle($translation, $this->toSyncInput($snapshot['blocks']));
            $this->createVersion->handle(
                $translation->fresh(['contentBlocks', 'productBlocks']),
                VersionTrigger::Restore,
                $userId,
                $version->id, // restored_from_version_id — lineage (§6.1, §18.1)
            );

            return $translation;
        });
    }

    /**
     * BUGFIX — `snapshot['blocks']` lưu block text dạng `{"type":"text","text_html":"..."}`
     * (đúng shape tài liệu hoá ở §4), nhưng `SyncContentBlocksAction::handle()` (dùng chung cho
     * cả save lẫn restore) lại đọc key `html` cho block text (khớp với payload thật từ
     * `post-block-composer.js`/`ArticleContentRenderer::toComposerPayload()`, cả hai đều dùng
     * `html`, không phải `text_html`) — 2 tên key khác nhau cho cùng 1 khái niệm. Không dịch lại
     * ở đây khiến `$blockData['html'] ?? ''` luôn rỗng khi restore, xoá sạch nội dung text dù
     * snapshot vẫn còn nguyên (đã xác nhận bằng test thật). Block `product` giữ nguyên vì shape
     * đã khớp 2 chiều.
     */
    private function toSyncInput(array $blocks): array
    {
        return array_map(
            fn (array $block) => $block['type'] === 'text'
                ? ['type' => 'text', 'html' => $block['text_html'] ?? '']
                : $block,
            $blocks
        );
    }

    private function assertProductsStillExist(array $blocks): void
    {
        $productIds = collect($blocks)
            ->where('type', 'product')
            ->flatMap(fn ($b) => collect($b['items'])->pluck('product_id'))
            ->unique();

        $missing = $productIds->diff(Product::whereIn('id', $productIds)->pluck('id'));

        if ($missing->isNotEmpty()) {
            throw new VersionRestoreException(
                "Không thể khôi phục: sản phẩm #{$missing->implode(', #')} trong phiên bản này không còn tồn tại."
            );
        }
    }
}
