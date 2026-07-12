<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\ContentBlockType;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Features\ArticleAuthoring\Data\TranslationData;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostContentBlock;
use Modules\Post\Models\PostProductBlock;
use Modules\Post\Models\PostProductBlockButton;

/**
 * Tạo bản dịch mới cho 1 article ở locale chưa có — luôn status=draft (kể cả nguồn copy
 * đang published, vì bản dịch mới chưa qua duyệt riêng). Slug bắt buộc auto-generate từ
 * title nếu không truyền, unique theo (organization_id, locale, slug). Content/product
 * blocks copy làm bản nháp khởi điểm từ mainTranslation() (hoặc translation khác nếu
 * main_locale cũng chưa có nội dung) — deep-copy độc lập, sửa bản copy không ảnh hưởng gốc.
 */
class CreateTranslationAction
{
    use AsAction;

    public function handle(PostArticle $article, string $locale, TranslationData $data): PostArticleTranslation
    {
        return DB::transaction(function () use ($article, $locale, $data) {
            $translation = PostArticleTranslation::create([
                'article_id'       => $article->id,
                'organization_id' => $article->organization_id,
                'locale'          => $locale,
                'title'           => $data->title,
                'slug'            => $data->slug ?: $this->uniqueSlug($article->organization_id, $locale, $data->title),
                'excerpt'         => $data->excerpt,
                'seo_title'       => $data->seo_title,
                'seo_description' => $data->seo_description,
                'status'          => TranslationStatus::Draft,
                // spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §7 — field per-locale của sponsorship,
                // đúng như disclosure_text/cta_text/cta_url đã validate ở §6.2.
                'disclosure_text' => $data->disclosure_text,
                'cta_text'        => $data->cta_text,
                'cta_url'         => $data->cta_url,
            ]);

            $source = $article->mainTranslation() ?? $article->translations()->first();

            if ($source) {
                $this->copyBlocks($source, $translation);
            }

            return $translation;
        });
    }

    private function uniqueSlug(int $organizationId, string $locale, string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 2;

        while (
            PostArticleTranslation::where('organization_id', $organizationId)
                ->where('locale', $locale)
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    private function copyBlocks(PostArticleTranslation $source, PostArticleTranslation $target): void
    {
        foreach ($source->contentBlocks()->get() as $block) {
            if ($block->type === ContentBlockType::Text) {
                PostContentBlock::create([
                    'organization_id' => $target->organization_id,
                    'translation_id'  => $target->id,
                    'type'            => ContentBlockType::Text,
                    'sort_order'      => $block->sort_order,
                    // Media/ảnh nhúng trong text_html giữ nguyên URL tuyệt đối — không tải
                    // lại/nhân bản file vật lý, 2 bản dịch cùng trỏ 1 file là bình thường.
                    'text_html'       => $block->text_html,
                ]);

                continue;
            }

            if ($block->type === ContentBlockType::Product && $block->productBlock) {
                $newProductBlock = $this->copyProductBlock($block->productBlock, $target);

                PostContentBlock::create([
                    'organization_id'  => $target->organization_id,
                    'translation_id'   => $target->id,
                    'type'             => ContentBlockType::Product,
                    'sort_order'       => $block->sort_order,
                    'product_block_id' => $newProductBlock->id,
                ]);
            }
        }
    }

    private function copyProductBlock(PostProductBlock $source, PostArticleTranslation $target): PostProductBlock
    {
        $new = PostProductBlock::create([
            'uuid'            => (string) Str::uuid(),
            'organization_id' => $target->organization_id,
            'translation_id'  => $target->id,
            'template'        => $source->template,
            'heading'         => $source->heading,
            'sort_order'      => $source->sort_order,
        ]);

        foreach ($source->items as $item) {
            // id/item_key mới — không tái dùng khoá gốc, tránh đụng unique(block_id,item_key)
            // khi 2 bản dịch tồn tại song song. product_id giữ nguyên (cùng 1 sản phẩm).
            $newItem = $new->items()->create([
                'item_key'             => Str::random(16),
                'product_id'           => $item->product_id,
                'title_override'       => $item->title_override,
                'price_label_override' => $item->price_label_override,
                'description_override' => $item->description_override,
                'image_url_override'   => $item->image_url_override,
                'sort_order'           => $item->sort_order,
            ]);

            foreach ($item->buttons as $button) {
                $this->copyButton($new, $button, $newItem->id);
            }
        }

        // Nút cấp-khối (không gắn item nào)
        foreach ($source->buttons as $button) {
            $this->copyButton($new, $button, null);
        }

        return $new;
    }

    private function copyButton(PostProductBlock $newBlock, PostProductBlockButton $source, ?int $newBlockItemId): void
    {
        PostProductBlockButton::create([
            'block_id'          => $newBlock->id,
            'block_item_id'     => $newBlockItemId,
            'button_key'        => Str::random(16),
            'label'             => $source->label,
            'url_type'          => $source->url_type,
            'url'               => $source->url,
            'product_link_type' => $source->product_link_type,
            'target'            => $source->target,
            'style'             => $source->style,
            'sort_order'        => $source->sort_order,
            // click_count là số liệu đo hiệu quả CTA của riêng bản dịch mới — reset về 0,
            // không kế thừa lịch sử click của bản nguồn.
            'click_count'       => 0,
        ]);
    }
}
