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
use Modules\Post\Models\PostFaqBlock;
use Modules\Post\Models\PostHowtoBlock;
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
    private const MAX_FAQ_BLOCKS_PER_ARTICLE     = 3;
    private const MAX_FAQ_ITEMS_PER_BLOCK        = 15;
    private const MAX_CITATION_BLOCKS_PER_ARTICLE = 10;
    private const MAX_HOWTO_BLOCKS_PER_ARTICLE    = 3;
    private const MAX_HOWTO_STEPS_PER_BLOCK       = 20;

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

        $productBlocksData  = array_values(array_filter($blocks, fn ($b) => ($b['type'] ?? null) === 'product'));
        $faqBlocksData      = array_values(array_filter($blocks, fn ($b) => ($b['type'] ?? null) === 'faq'));
        $citationBlocksData = array_values(array_filter($blocks, fn ($b) => ($b['type'] ?? null) === 'citation'));
        $howtoBlocksData    = array_values(array_filter($blocks, fn ($b) => ($b['type'] ?? null) === 'howto'));

        $this->validateProductBlocks($translation, $productBlocksData);
        $this->validateFaqBlocks($faqBlocksData);
        $this->validateCitationBlocks($citationBlocksData);
        $this->validateHowtoBlocks($howtoBlocksData);

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

        // ── 1b. Upsert-by-uuid post_faq_blocks (giữ block ổn định qua các lần sửa) — items bên
        // trong xoá-tạo-lại toàn bộ mỗi lần lưu, KHÔNG cần upsert-by-key như product item vì FAQ
        // không có click_count/số liệu gì cần bảo toàn (câu hỏi/trả lời là dữ liệu tĩnh nhập tay). ──
        $seenFaqUuids = [];
        $faqBlockIdByUuid = [];

        foreach ($faqBlocksData as $sortOrder => $blockData) {
            $seenFaqUuids[] = $blockData['block_uuid'];
            $block = $this->upsertFaqBlock($translation, $blockData, $sortOrder);
            $faqBlockIdByUuid[$blockData['block_uuid']] = $block->id;
        }

        $translation->faqBlocks()->whereNotIn('uuid', $seenFaqUuids)->get()->each->delete();

        // ── 1c. Upsert-by-uuid post_howto_blocks — cùng lý do 1b (không click_count cần bảo toàn). ──
        $seenHowtoUuids = [];
        $howtoBlockIdByUuid = [];

        foreach ($howtoBlocksData as $sortOrder => $blockData) {
            $seenHowtoUuids[] = $blockData['block_uuid'];
            $block = $this->upsertHowtoBlock($translation, $blockData, $sortOrder);
            $howtoBlockIdByUuid[$blockData['block_uuid']] = $block->id;
        }

        $translation->howtoBlocks()->whereNotIn('uuid', $seenHowtoUuids)->get()->each->delete();

        // ── 2. Ghi lại post_content_blocks — chỉ là con trỏ sắp xếp, không mang trạng thái
        // (click_count nằm ở product_blocks/items/buttons đã upsert-by-key ở trên), nên
        // xoá-tạo-lại toàn bộ ở đây an toàn và đơn giản hơn upsert-by-key thêm 1 lần nữa. ──
        $translation->contentBlocks()->delete();

        foreach ($blocks as $sortOrder => $blockData) {
            $type = $blockData['type'] ?? null;

            if ($type === 'text') {
                PostContentBlock::create([
                    'translation_id'  => $translation->id,
                    'type'            => ContentBlockType::Text,
                    'sort_order'      => $sortOrder,
                    'text_html'       => $this->renderer->sanitizeTextHtml($blockData['html'] ?? ''),
                ]);
            } elseif ($type === 'product') {
                $productBlockId = $productBlockIdByUuid[$blockData['block_uuid']] ?? null;

                if ($productBlockId) {
                    PostContentBlock::create([
                        'translation_id'   => $translation->id,
                        'type'             => ContentBlockType::Product,
                        'sort_order'       => $sortOrder,
                        'product_block_id' => $productBlockId,
                    ]);
                }
            } elseif ($type === 'faq') {
                $faqBlockId = $faqBlockIdByUuid[$blockData['block_uuid']] ?? null;

                if ($faqBlockId) {
                    PostContentBlock::create([
                        'translation_id' => $translation->id,
                        'type'           => ContentBlockType::Faq,
                        'sort_order'     => $sortOrder,
                        'faq_block_id'   => $faqBlockId,
                    ]);
                }
            } elseif ($type === 'citation') {
                // Không có bảng con — khác Product/Faq/Howto, Citation là 1 câu trích dẫn ĐƠN duy
                // nhất, không phải danh sách item lặp lại, nên lưu thẳng 3 cột trên chính
                // post_content_blocks (giống cách type=text lưu thẳng text_html).
                PostContentBlock::create([
                    'translation_id'       => $translation->id,
                    'type'                 => ContentBlockType::Citation,
                    'sort_order'           => $sortOrder,
                    'citation_text'        => trim((string) ($blockData['citation_text'] ?? '')),
                    'citation_source_name' => trim((string) ($blockData['citation_source_name'] ?? '')),
                    'citation_source_url'  => $blockData['citation_source_url'] ?: null,
                ]);
            } elseif ($type === 'howto') {
                $howtoBlockId = $howtoBlockIdByUuid[$blockData['block_uuid']] ?? null;

                if ($howtoBlockId) {
                    PostContentBlock::create([
                        'translation_id' => $translation->id,
                        'type'           => ContentBlockType::Howto,
                        'sort_order'     => $sortOrder,
                        'howto_block_id' => $howtoBlockId,
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

    /** @param array<int, array> $blocksData @throws ProductBlockValidationException */
    private function validateFaqBlocks(array $blocksData): void
    {
        $errors = [];

        if (count($blocksData) > self::MAX_FAQ_BLOCKS_PER_ARTICLE) {
            $errors[] = sprintf('Bài viết chỉ được tối đa %d khối FAQ (hiện có %d).', self::MAX_FAQ_BLOCKS_PER_ARTICLE, count($blocksData));
        }

        foreach ($blocksData as $i => $block) {
            $label = 'Khối FAQ #' . ($i + 1);
            $items = $block['items'] ?? [];

            if (count($items) === 0) {
                $errors[] = "{$label}: cần ít nhất 1 câu hỏi.";
            }

            if (count($items) > self::MAX_FAQ_ITEMS_PER_BLOCK) {
                $errors[] = "{$label}: tối đa " . self::MAX_FAQ_ITEMS_PER_BLOCK . ' câu hỏi (hiện có ' . count($items) . ').';
            }

            foreach ($items as $j => $item) {
                if (trim((string) ($item['question'] ?? '')) === '') {
                    $errors[] = "{$label}, câu hỏi #" . ($j + 1) . ': không được để trống câu hỏi.';
                }

                if (trim((string) ($item['answer'] ?? '')) === '') {
                    $errors[] = "{$label}, câu hỏi #" . ($j + 1) . ': không được để trống câu trả lời.';
                }
            }
        }

        if ($errors) {
            throw new ProductBlockValidationException($errors);
        }
    }

    /**
     * @param array<int, array> $blocksData @throws ProductBlockValidationException
     *
     * `citation_source_name` BẮT BUỘC — 1 trích dẫn không rõ nguồn thì mất hết giá trị "citation
     * engineering" (nghiên cứu Princeton/KDD 2024 dẫn trong trao đổi GEO đợt 4: thêm nguồn tăng
     * tới +115% khả năng được AI trích dẫn, NHƯNG phải là nguồn ĐẶT TÊN, không phải trích dẫn mù).
     */
    private function validateCitationBlocks(array $blocksData): void
    {
        $errors = [];

        if (count($blocksData) > self::MAX_CITATION_BLOCKS_PER_ARTICLE) {
            $errors[] = sprintf('Bài viết chỉ được tối đa %d khối trích dẫn (hiện có %d).', self::MAX_CITATION_BLOCKS_PER_ARTICLE, count($blocksData));
        }

        foreach ($blocksData as $i => $block) {
            $label = 'Trích dẫn #' . ($i + 1);

            if (trim((string) ($block['citation_text'] ?? '')) === '') {
                $errors[] = "{$label}: không được để trống nội dung trích dẫn.";
            }

            if (trim((string) ($block['citation_source_name'] ?? '')) === '') {
                $errors[] = "{$label}: cần ghi rõ tên nguồn (VD: \"Bộ Y tế, 2026\") — trích dẫn không nêu nguồn không có giá trị.";
            }

            $url = $block['citation_source_url'] ?? null;
            if ($url && (! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $url))) {
                $errors[] = "{$label}: link nguồn không hợp lệ (\"{$url}\").";
            }
        }

        if ($errors) {
            throw new ProductBlockValidationException($errors);
        }
    }

    /** @param array<int, array> $blocksData @throws ProductBlockValidationException */
    private function validateHowtoBlocks(array $blocksData): void
    {
        $errors = [];

        if (count($blocksData) > self::MAX_HOWTO_BLOCKS_PER_ARTICLE) {
            $errors[] = sprintf('Bài viết chỉ được tối đa %d khối hướng dẫn từng bước (hiện có %d).', self::MAX_HOWTO_BLOCKS_PER_ARTICLE, count($blocksData));
        }

        foreach ($blocksData as $i => $block) {
            $label = 'Khối hướng dẫn #' . ($i + 1);
            $steps = $block['steps'] ?? [];

            if (count($steps) < 2) {
                $errors[] = "{$label}: cần ít nhất 2 bước (dưới 2 bước không phải \"hướng dẫn từng bước\").";
            }

            if (count($steps) > self::MAX_HOWTO_STEPS_PER_BLOCK) {
                $errors[] = "{$label}: tối đa " . self::MAX_HOWTO_STEPS_PER_BLOCK . ' bước (hiện có ' . count($steps) . ').';
            }

            foreach ($steps as $j => $step) {
                if (trim((string) ($step['name'] ?? '')) === '') {
                    $errors[] = "{$label}, bước #" . ($j + 1) . ': không được để trống tên bước.';
                }

                if (trim((string) ($step['text'] ?? '')) === '') {
                    $errors[] = "{$label}, bước #" . ($j + 1) . ': không được để trống nội dung bước.';
                }
            }
        }

        if ($errors) {
            throw new ProductBlockValidationException($errors);
        }
    }

    private function upsertHowtoBlock(PostArticleTranslation $translation, array $blockData, int $sortOrder): PostHowtoBlock
    {
        /** @var PostHowtoBlock $block */
        $block = $translation->howtoBlocks()->firstOrNew(['uuid' => $blockData['block_uuid']]);
        $block->fill([
            'name'        => $blockData['name'] ?? null,
            'description' => $blockData['description'] ?? null,
            'sort_order'  => $sortOrder,
        ]);
        $block->save();

        $block->steps()->delete();

        foreach (($blockData['steps'] ?? []) as $stepSort => $stepData) {
            $block->steps()->create([
                'name'       => trim((string) $stepData['name']),
                'text'       => trim((string) $stepData['text']),
                'sort_order' => $stepSort,
            ]);
        }

        return $block;
    }

    private function upsertFaqBlock(PostArticleTranslation $translation, array $blockData, int $sortOrder): PostFaqBlock
    {
        /** @var PostFaqBlock $block */
        $block = $translation->faqBlocks()->firstOrNew(['uuid' => $blockData['block_uuid']]);
        $block->fill([
            'heading'    => $blockData['heading'] ?? null,
            'sort_order' => $sortOrder,
        ]);
        $block->save();

        // Không có click_count/số liệu nào cần bảo toàn ở item — xoá-tạo-lại đơn giản hơn
        // upsert-by-key (khác hẳn post_product_block_items, xem docblock class).
        $block->items()->delete();

        foreach (($blockData['items'] ?? []) as $itemSort => $itemData) {
            $block->items()->create([
                'question'   => trim((string) $itemData['question']),
                'answer'     => trim((string) $itemData['answer']),
                'sort_order' => $itemSort,
            ]);
        }

        return $block;
    }

    private function upsertProductBlock(PostArticleTranslation $translation, array $blockData, int $sortOrder): PostProductBlock
    {
        /** @var PostProductBlock $block */
        $block = $translation->productBlocks()->firstOrNew(['uuid' => $blockData['block_uuid']]);
        $block->fill([
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
