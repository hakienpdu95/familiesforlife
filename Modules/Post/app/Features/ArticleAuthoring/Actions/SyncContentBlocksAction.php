<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\ButtonUrlType;
use Modules\Post\Enums\ContentBlockType;
use Modules\Post\Enums\ProductBlockTemplate;
use Modules\Post\Features\ArticleAuthoring\Exceptions\ProductBlockValidationException;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostContentBlock;
use Modules\Post\Models\PostProductBlock;
use Modules\Post\Models\PostProductBlockButton;
use Modules\Post\Models\PostProductBlockItem;
use Modules\Post\Support\ArticleContentRenderer;
use Modules\Product\Contracts\ProductCatalogContract;
use Modules\Product\Enums\ProductLinkType;
use Modules\Product\Models\Product;

/**
 * Sync-on-save cho block-composer: nhận dãy block **đã có cấu trúc sẵn** (JSON từ form,
 * không phải HTML cần parse lại) — mỗi block hoặc `type=text` (HTML từ Jodit mini-editor)
 * hoặc `type=product` (chọn sản phẩm + template). Validate bắt buộc cho block sản phẩm
 * (docs/post-module-spec.md §9.8.1/§9.8.2), upsert-by-key vào `post_product_blocks`/*_items/
 * *_buttons (bảo toàn click_count), rồi ghi lại toàn bộ `post_content_blocks` theo đúng thứ tự.
 */
class SyncContentBlocksAction
{
    use AsAction;

    private const MAX_PRODUCT_BLOCKS_PER_ARTICLE = 3;
    private const MAX_BUTTONS_PER_ITEM           = 5;
    private const MAX_TOTAL_BLOCKS               = 100; // chặn nhồi rác, không giới hạn nghiệp vụ thật

    public function __construct(
        private readonly ArticleContentRenderer $renderer,
        private readonly ProductCatalogContract $productCatalog,
    ) {}

    /** @param array<int, array> $blocks Dãy block theo đúng thứ tự hiển thị. */
    public function handle(PostArticleTranslation $translation, array $blocks): void
    {
        if (count($blocks) > self::MAX_TOTAL_BLOCKS) {
            throw new ProductBlockValidationException([
                sprintf('Bài viết chỉ được tối đa %d block (hiện có %d).', self::MAX_TOTAL_BLOCKS, count($blocks)),
            ]);
        }

        $productBlocksData = array_values(array_filter($blocks, fn ($b) => ($b['type'] ?? null) === 'product'));

        $this->validateProductBlocks($translation, $productBlocksData);

        $productIdsBefore = $this->currentProductIds($translation);

        // ── 1. Upsert-by-key post_product_blocks/*_items/*_buttons (bảo toàn click_count) ──
        $seenUuids = [];
        $productBlockIdByUuid = [];

        foreach ($productBlocksData as $sortOrder => $blockData) {
            $seenUuids[] = $blockData['block_uuid'];
            $block = $this->upsertProductBlock($translation, $blockData, $sortOrder);
            $productBlockIdByUuid[$blockData['block_uuid']] = $block->id;
        }

        // Khối sản phẩm không còn xuất hiện trong bài → xoá (cascade items/buttons)
        $translation->productBlocks()->whereNotIn('uuid', $seenUuids)->get()->each->delete();

        // ── 2. Ghi lại post_content_blocks — chỉ là con trỏ sắp xếp, không mang trạng thái
        // (click_count nằm ở product_blocks/items/buttons đã upsert-by-key ở trên), nên
        // xoá-tạo-lại toàn bộ ở đây an toàn và đơn giản hơn upsert-by-key thêm 1 lần nữa. ──
        $translation->contentBlocks()->delete();

        foreach ($blocks as $sortOrder => $blockData) {
            $type = $blockData['type'] ?? null;

            if ($type === 'text') {
                PostContentBlock::create([
                    'organization_id' => $translation->organization_id,
                    'translation_id'  => $translation->id,
                    'type'            => ContentBlockType::Text,
                    'sort_order'      => $sortOrder,
                    'text_html'       => $this->renderer->sanitizeTextHtml($blockData['html'] ?? ''),
                ]);
            } elseif ($type === 'product') {
                $productBlockId = $productBlockIdByUuid[$blockData['block_uuid']] ?? null;

                if ($productBlockId) {
                    PostContentBlock::create([
                        'organization_id'  => $translation->organization_id,
                        'translation_id'   => $translation->id,
                        'type'             => ContentBlockType::Product,
                        'sort_order'       => $sortOrder,
                        'product_block_id' => $productBlockId,
                    ]);
                }
            }
        }

        // ── 3. Diff usage-count sang Modules\Product ──
        $productIdsAfter = $this->currentProductIds($translation);

        foreach ($productIdsAfter->diff($productIdsBefore) as $productId) {
            $this->productCatalog->incrementArticleUsageCount($productId);
        }

        foreach ($productIdsBefore->diff($productIdsAfter) as $productId) {
            $this->productCatalog->decrementArticleUsageCount($productId);
        }
    }

    private function currentProductIds(PostArticleTranslation $translation): Collection
    {
        return $translation->productBlocks()->with('items')->get()
            ->pluck('items')->flatten()->pluck('product_id')->unique();
    }

    /** @param array<int, array> $blocksData @throws ProductBlockValidationException */
    private function validateProductBlocks(PostArticleTranslation $translation, array $blocksData): void
    {
        $errors = [];

        if (count($blocksData) > self::MAX_PRODUCT_BLOCKS_PER_ARTICLE) {
            $errors[] = sprintf('Bài viết chỉ được tối đa %d khối sản phẩm (hiện có %d).', self::MAX_PRODUCT_BLOCKS_PER_ARTICLE, count($blocksData));
        }

        foreach ($blocksData as $i => $block) {
            $label = 'Khối sản phẩm #' . ($i + 1);

            // Chặn hijack: 1 block_uuid hợp lệ nhưng đang thuộc bản dịch KHÁC (vd tự chỉnh
            // request) — không cho reassign, vì `uuid` có unique constraint toàn cục.
            if (PostProductBlock::where('uuid', $block['block_uuid'] ?? '')->where('translation_id', '!=', $translation->id)->exists()) {
                $errors[] = "{$label}: block_uuid đã thuộc về 1 bản dịch khác, không thể dùng lại.";
                continue;
            }

            $template = ProductBlockTemplate::tryFrom($block['template'] ?? '');

            if (! $template) {
                $errors[] = "{$label}: template \"{$block['template']}\" không hợp lệ.";
                continue;
            }

            $items     = $block['items'] ?? [];
            $itemCount = count($items);

            if ($itemCount < $template->minItems() || $itemCount > $template->maxItems()) {
                $errors[] = "{$label} ({$template->label()}): cần {$template->minItems()}-{$template->maxItems()} sản phẩm, hiện có {$itemCount}.";
            }

            foreach ($items as $item) {
                $product = Product::find($item['product_id'] ?? null); // tenant-scoped qua global scope

                if (! $product) {
                    $errors[] = "{$label}: sản phẩm #{$item['product_id']} không tồn tại hoặc không thuộc tổ chức của bạn.";
                }

                $buttons = $item['buttons'] ?? [];
                if (count($buttons) > self::MAX_BUTTONS_PER_ITEM) {
                    $errors[] = "{$label}: sản phẩm #{$item['product_id']} có " . count($buttons)
                        . ' nút, vượt tối đa ' . self::MAX_BUTTONS_PER_ITEM . '.';
                }

                foreach ($buttons as $button) {
                    $this->validateButton($button, $label, $errors);
                }
            }

            foreach ($block['block_buttons'] ?? [] as $button) {
                $this->validateButton($button, $label, $errors);
            }
        }

        if ($errors) {
            throw new ProductBlockValidationException($errors);
        }
    }

    private function validateButton(array $button, string $label, array &$errors): void
    {
        $urlType = ButtonUrlType::tryFrom($button['url_type'] ?? '');

        if (! $urlType) {
            $errors[] = "{$label}: url_type \"{$button['url_type']}\" không hợp lệ.";
            return;
        }

        match ($urlType) {
            ButtonUrlType::UseProductLink => (function () use ($button, $label, &$errors) {
                if (! ProductLinkType::tryFrom((string) ($button['product_link_type'] ?? ''))) {
                    $errors[] = "{$label}: product_link_type \"{$button['product_link_type']}\" không hợp lệ.";
                }
            })(),
            ButtonUrlType::CustomUrl => (function () use ($button, $label, &$errors) {
                $url = $button['url'] ?? '';
                if (! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $url)) {
                    $errors[] = "{$label}: URL tuỳ chỉnh không hợp lệ (\"{$url}\").";
                }
            })(),
            ButtonUrlType::Phone => (function () use ($button, $label, &$errors) {
                if (! preg_match('/^[0-9+][0-9.\-\s]{6,20}$/', (string) ($button['url'] ?? ''))) {
                    $errors[] = "{$label}: số điện thoại không hợp lệ.";
                }
            })(),
            ButtonUrlType::Email => (function () use ($button, $label, &$errors) {
                if (! filter_var($button['url'] ?? '', FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "{$label}: email không hợp lệ.";
                }
            })(),
            ButtonUrlType::Zalo => (function () use ($button, $label, &$errors) {
                $url       = (string) ($button['url'] ?? '');
                $isZaloUrl = preg_match('#^https://zalo\.me/#i', $url);
                $isPhone   = preg_match('/^[0-9+][0-9.\-\s]{6,20}$/', $url);
                if (! $isZaloUrl && ! $isPhone) {
                    $errors[] = "{$label}: Zalo phải là link https://zalo.me/... hoặc số điện thoại.";
                }
            })(),
        };
    }

    private function upsertProductBlock(PostArticleTranslation $translation, array $blockData, int $sortOrder): PostProductBlock
    {
        /** @var PostProductBlock $block */
        $block = $translation->productBlocks()->firstOrNew(['uuid' => $blockData['block_uuid']]);
        $block->fill([
            'organization_id' => $translation->organization_id,
            'template'        => $blockData['template'],
            'heading'         => $blockData['heading'] ?? null,
            'sort_order'      => $sortOrder,
        ]);
        $block->save();

        $seenItemKeys = [];
        foreach (($blockData['items'] ?? []) as $itemSort => $itemData) {
            $seenItemKeys[] = $itemData['item_key'];
            $item = $this->upsertItem($block, $itemData, $itemSort);
            $this->upsertButtons($block, $itemData['buttons'] ?? [], $item->id);
        }
        $block->items()->whereNotIn('item_key', $seenItemKeys)->get()->each->delete();

        $blockButtonKeys = array_column($blockData['block_buttons'] ?? [], 'button_key');
        $this->upsertButtons($block, $blockData['block_buttons'] ?? [], null);
        $block->buttons()->whereNotIn('button_key', $blockButtonKeys)->get()->each->delete();

        return $block;
    }

    private function upsertItem(PostProductBlock $block, array $itemData, int $sortOrder): PostProductBlockItem
    {
        /** @var PostProductBlockItem $item */
        $item = $block->items()->firstOrNew(['item_key' => $itemData['item_key']]);

        $attributes = [
            'product_id'           => $itemData['product_id'],
            'title_override'       => $itemData['title_override'] ?? null,
            'price_label_override' => $itemData['price_label_override'] ?? null,
            'description_override' => $itemData['description_override'] ?? null,
            'image_url_override'   => $itemData['image_url_override'] ?? null,
            'sort_order'           => $sortOrder,
        ];

        // Dirty-check — chỉ ghi DB nếu có giá trị thực sự đổi (docs/post-module-spec.md §9.8.5).
        if ($item->exists && ! $this->attributesDiffer($item, $attributes)) {
            return $item;
        }

        $item->fill($attributes);
        $item->save();

        return $item;
    }

    private function upsertButtons(PostProductBlock $block, array $buttonsData, ?int $blockItemId): void
    {
        foreach ($buttonsData as $sortOrder => $buttonData) {
            /** @var PostProductBlockButton $button */
            $button = PostProductBlockButton::firstOrNew([
                'block_id'   => $block->id,
                'button_key' => $buttonData['button_key'],
            ]);

            $urlType = ButtonUrlType::from($buttonData['url_type']);

            $attributes = [
                'block_item_id'     => $blockItemId,
                // use_product_link: KHÔNG bao giờ tin url/label client gửi lên — luôn null,
                // resolve động lúc render/click (docs/post-module-spec.md §9.8.1).
                'label'             => $urlType === ButtonUrlType::UseProductLink ? null : ($buttonData['label'] ?? null),
                'url_type'          => $buttonData['url_type'],
                'url'               => $urlType === ButtonUrlType::UseProductLink ? null : ($buttonData['url'] ?? null),
                'product_link_type' => $urlType === ButtonUrlType::UseProductLink ? $buttonData['product_link_type'] : null,
                'target'            => $buttonData['target'] ?? '_blank',
                'style'             => $buttonData['style'] ?? 'primary',
                'sort_order'        => $sortOrder,
            ];

            if ($button->exists && ! $this->attributesDiffer($button, $attributes)) {
                continue;
            }

            $button->fill($attributes);
            $button->save();
        }
    }

    /**
     * So sánh an toàn với cột cast enum (url_type/target/style) — so từng key thủ công và
     * unwrap `->value` nếu cần trước khi so sánh (docs/post-module-spec.md §9.8.5).
     */
    private function attributesDiffer(\Illuminate\Database\Eloquent\Model $model, array $newAttributes): bool
    {
        foreach ($newAttributes as $key => $newValue) {
            $current = $model->getAttribute($key);
            $current = $current instanceof \BackedEnum ? $current->value : $current;

            if ($current !== $newValue) {
                return true;
            }
        }

        return false;
    }
}
