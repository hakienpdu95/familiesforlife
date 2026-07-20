<?php

namespace Modules\Post\Support;

use Illuminate\Support\Collection;
use Modules\Post\Enums\ContentBlockType;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostContentBlock;
use Modules\Post\Models\PostProductBlock;
use Modules\Post\Models\PostProductBlockButton;
use Modules\Post\Models\PostProductBlockItem;
use Modules\Product\Enums\ProductLinkType;
use Modules\Product\Models\Product;

/**
 * Render bài viết từ dãy `post_content_blocks` (block-composer) — nguồn sự thật là các
 * dòng quan hệ thật, KHÔNG phải HTML nhúng placeholder cần parse lại (khác thiết kế v1
 * dựa trên DOMDocument). Mỗi block hiển thị theo đúng type + sort_order. Vận hành trên
 * PostArticleTranslation (per-locale) — không phải PostArticle.
 */
class ArticleContentRenderer
{
    /** Render HTML cuối cùng để hiển thị (admin preview / trang công khai). */
    public function render(PostArticleTranslation $translation): string
    {
        $blocks = $translation->contentBlocks()
            ->with(['productBlock.items.product', 'productBlock.items.buttons', 'productBlock.buttons'])
            ->get();

        return $blocks->map(function ($block) {
            if ($block->type === ContentBlockType::Text) {
                return (string) $block->text_html;
            }

            if (! $block->productBlock) {
                return '';
            }

            return view(
                "post::components.product-block.{$block->productBlock->template->value}",
                ['block' => $block->productBlock]
            )->render();
        })->implode('');
    }

    /**
     * Sanitize HTML của 1 text-block trước khi lưu DB (docs/post-module-spec.md §9.8.1) —
     * strip `<script>`, mọi thuộc tính `on*=` (onerror, onclick...), và trung hoà
     * `javascript:`/`data:` scheme trong href/src. Không phải allowlist thẻ/thuộc tính đầy
     * đủ, nhưng chặn đúng 3 vector XSS cụ thể nêu trong acceptance criteria (§18).
     */
    public function sanitizeTextHtml(?string $html): string
    {
        if (! $html) {
            return '';
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        foreach (iterator_to_array($dom->getElementsByTagName('script')) as $script) {
            $script->parentNode->removeChild($script);
        }

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//*') as $el) {
            if (! $el instanceof \DOMElement) {
                continue;
            }

            foreach (iterator_to_array($el->attributes ?? []) as $attr) {
                $name  = strtolower($attr->nodeName);
                $value = trim($attr->nodeValue);

                if (str_starts_with($name, 'on')) {
                    $el->removeAttribute($attr->nodeName);
                    continue;
                }

                if (in_array($name, ['href', 'src'], true) && preg_match('#^\s*(javascript|data):#i', $value)) {
                    $el->removeAttribute($attr->nodeName);
                }
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $out  = '';
        foreach ($body->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out;
    }

    /**
     * Dựng lại dãy block theo đúng shape mà block-composer JS hiểu (giống hệt state nó tự
     * build khi soạn) — dùng để hydrate composer khi mở trang sửa bài (ArticleAdminController)
     * và để AicemSubjectResolver dựng lại đủ `ArticleData::blocks` khi chỉ 1 block/field được
     * accept, tránh SyncContentBlocksAction xoá mất các block không liên quan (nó ghi đè toàn bộ
     * theo dãy được truyền, không phải partial — xem Modules/Aicem PostArticleSubjectResolver).
     *
     * @return array<int, array>
     */
    public function toComposerPayload(PostArticleTranslation $translation): array
    {
        $blocks = $translation->contentBlocks()
            ->with(['productBlock.items.product', 'productBlock.items.buttons', 'productBlock.buttons'])
            ->get();

        return $blocks->map(function (PostContentBlock $block) {
            if ($block->type === ContentBlockType::Text) {
                return ['type' => 'text', 'html' => $block->text_html];
            }

            $pb = $block->productBlock;
            if (! $pb) {
                return null;
            }

            return [
                'type'          => 'product',
                'block_uuid'    => $pb->uuid,
                'template'      => $pb->template->value,
                'heading'       => $pb->heading,
                'items'         => $pb->items->map(fn ($item) => [
                    'item_key'              => $item->item_key,
                    'product_id'            => $item->product_id,
                    'title_override'        => $item->title_override,
                    'price_label_override'  => $item->price_label_override,
                    'description_override'  => $item->description_override,
                    'image_url_override'    => $item->image_url_override,
                    'cached_name'           => $item->product?->name,
                    'cached_image'          => $item->product?->cover_image_url,
                    'cached_price'          => $item->product?->display_price,
                    'cached_links'          => collect(ProductLinkType::cases())
                        ->filter(fn ($type) => filled($item->product?->{$type->urlColumn()}))
                        ->map(fn ($type) => ['type' => $type->value, 'label' => $type->label()])
                        ->values(),
                    'buttons'               => $item->buttons->map(fn ($btn) => [
                        'button_key'         => $btn->button_key,
                        'label'              => $btn->label,
                        'url_type'           => $btn->url_type->value,
                        'url'                => $btn->url,
                        'product_link_type'  => $btn->product_link_type,
                        'target'             => $btn->target->value,
                        'style'              => $btn->style->value,
                    ]),
                ]),
                'block_buttons' => $pb->buttons->map(fn ($btn) => [
                    'button_key'         => $btn->button_key,
                    'label'              => $btn->label,
                    'url_type'           => $btn->url_type->value,
                    'url'                => $btn->url,
                    'product_link_type'  => $btn->product_link_type,
                    'target'             => $btn->target->value,
                    'style'              => $btn->style->value,
                ]),
            ];
        })->filter()->values()->all();
    }

    /**
     * spec/Post_VersionHistory_Technical_Specification.md §13.3 — render preview 1
     * `PostArticleVersion::snapshot` (đọc, không ghi DB). Quy tắc bắt buộc: item có
     * `*_override` không null dùng thẳng override đã lưu; item override null MÀ product_id đã
     * bị xoá dùng placeholder cố định (KHÔNG bao giờ query `Product` cho id đó); item override
     * null mà sản phẩm còn tồn tại được phép đọc `Product` hiện hành (1 lượt `whereIn` gộp,
     * không N+1) — lý do: nếu lỡ query sản phẩm đã xoá sẽ ra null/exception thay vì fallback.
     *
     * @param array<int, array> $blocks
     * @return array{html: string, missing_products: array<int, array{product_id: int, referenced_in_block: ?string}>}
     */
    public function renderSnapshot(array $blocks): array
    {
        $productIds = collect($blocks)
            ->where('type', 'product')
            ->flatMap(fn ($b) => collect($b['items'] ?? [])->pluck('product_id'))
            ->filter()
            ->unique()
            ->values();

        $existingProducts = Product::whereIn('id', $productIds)->get()->keyBy('id');

        // Tính trước TOÀN BỘ danh sách "đã xoá" bằng phép diff thuần (không cần tích luỹ qua
        // tham chiếu khi hydrate — tránh bẫy arrow function `fn() =>` luôn capture BY VALUE dù
        // biến ngoài là tham chiếu, khiến mutation không thoát ra ngoài closure).
        $missingProducts = collect($blocks)
            ->where('type', 'product')
            ->flatMap(fn ($b) => collect($b['items'] ?? [])
                ->pluck('product_id')
                ->filter()
                ->reject(fn ($id) => $existingProducts->has($id))
                ->map(fn ($id) => ['product_id' => $id, 'referenced_in_block' => $b['block_uuid'] ?? null]))
            ->values()
            ->all();

        $missingProductIds = collect($missingProducts)->pluck('product_id')->unique();

        $html = collect($blocks)->map(function ($blockData) use ($existingProducts, $missingProductIds) {
            if (($blockData['type'] ?? null) === 'text') {
                return (string) ($blockData['text_html'] ?? '');
            }

            $block = $this->hydrateProductBlockSnapshot($blockData, $existingProducts, $missingProductIds);

            return view(
                "post::components.product-block.{$block->template->value}",
                ['block' => $block, 'preview' => true]
            )->render();
        })->implode('');

        return ['html' => $html, 'missing_products' => $missingProducts];
    }

    private function hydrateProductBlockSnapshot(array $blockData, Collection $existingProducts, Collection $missingProductIds): PostProductBlock
    {
        $block = new PostProductBlock();
        $block->exists = false;
        $block->forceFill([
            'id'       => 0,
            'uuid'     => $blockData['block_uuid'] ?? null,
            'template' => $blockData['template'],
            'heading'  => $blockData['heading'] ?? null,
        ]);

        $items = collect($blockData['items'] ?? [])->map(
            fn ($itemData) => $this->hydrateItemSnapshot($itemData, $existingProducts, $missingProductIds)
        );

        $block->setRelation('items', $items);
        $block->setRelation('buttons', collect($blockData['block_buttons'] ?? [])->map(fn ($b) => $this->hydrateButtonSnapshot($b)));

        return $block;
    }

    private function hydrateItemSnapshot(array $itemData, Collection $existingProducts, Collection $missingProductIds): PostProductBlockItem
    {
        $productId = $itemData['product_id'] ?? null;
        $isMissing = $productId && $missingProductIds->contains($productId);

        $item = new PostProductBlockItem();
        $item->exists = false;
        $item->forceFill([
            'id'                    => 0,
            'item_key'              => $itemData['item_key'] ?? null,
            'product_id'            => $productId,
            'title_override'        => $itemData['title_override'] ?? null,
            'price_label_override'  => $itemData['price_label_override'] ?? null,
            'description_override'  => $itemData['description_override'] ?? null,
            'image_url_override'    => $itemData['image_url_override'] ?? null,
        ]);

        if ($isMissing) {
            // Không được lazy-load Product cho id đã biết chắc không còn tồn tại (§13.3) —
            // set relation = null NGAY để accessor display_* không bao giờ kích hoạt query.
            $item->setRelation('product', null);
            $item->title_override      = $item->title_override ?? 'Sản phẩm không còn tồn tại';
            $item->image_url_override  = $item->image_url_override ?? self::MISSING_PRODUCT_PLACEHOLDER_IMAGE;
        } else {
            $item->setRelation('product', $productId ? $existingProducts->get($productId) : null);
        }

        $item->setRelation('buttons', collect($itemData['buttons'] ?? [])->map(fn ($b) => $this->hydrateButtonSnapshot($b)));

        return $item;
    }

    private function hydrateButtonSnapshot(array $buttonData): PostProductBlockButton
    {
        $button = new PostProductBlockButton();
        $button->exists = false;
        $button->forceFill([
            'id'                => 0, // route('post.cta.redirect', $button) cần 1 route-key hợp lệ để không ném UrlGenerationException (§13.3) — bấm vào là dead-link chấp nhận được cho preview lịch sử.
            'button_key'        => $buttonData['button_key'] ?? null,
            'label'             => $buttonData['label'] ?? null,
            'url_type'          => $buttonData['url_type'],
            'url'               => $buttonData['url'] ?? null,
            'product_link_type' => $buttonData['product_link_type'] ?? null,
            'target'            => $buttonData['target'] ?? '_blank',
            'style'             => $buttonData['style'] ?? 'primary',
        ]);

        return $button;
    }

    private const MISSING_PRODUCT_PLACEHOLDER_IMAGE = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200' viewBox='0 0 200 200'%3E%3Crect width='200' height='200' fill='%23e5e7eb'/%3E%3Ctext x='50%25' y='50%25' font-size='14' fill='%239ca3af' text-anchor='middle' dominant-baseline='middle' font-family='sans-serif'%3ESản phẩm đã xoá%3C/text%3E%3C/svg%3E";
}
