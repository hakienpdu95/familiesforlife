<?php

namespace Modules\Post\Support;

use Modules\Post\Enums\ContentBlockType;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostContentBlock;
use Modules\Product\Enums\ProductLinkType;

/**
 * Render bài viết từ dãy `post_content_blocks` (block-composer) — nguồn sự thật là các
 * dòng quan hệ thật, KHÔNG phải HTML nhúng placeholder cần parse lại (khác thiết kế v1
 * dựa trên DOMDocument). Mỗi block hiển thị theo đúng type + sort_order.
 */
class ArticleContentRenderer
{
    /** Render HTML cuối cùng để hiển thị (admin preview / trang công khai). */
    public function render(PostArticle $article): string
    {
        $blocks = $article->contentBlocks()
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
    public function toComposerPayload(PostArticle $article): array
    {
        $blocks = $article->contentBlocks()
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
}
