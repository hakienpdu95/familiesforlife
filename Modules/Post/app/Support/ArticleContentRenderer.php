<?php

namespace Modules\Post\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use League\HTMLToMarkdown\HtmlConverter;
use Modules\Post\Enums\ContentBlockType;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostComparisonBlock;
use Modules\Post\Models\PostComparisonColumn;
use Modules\Post\Models\PostComparisonRow;
use Modules\Post\Models\PostContentBlock;
use Modules\Post\Models\PostFaqBlock;
use Modules\Post\Models\PostFaqItem;
use Modules\Post\Models\PostHowtoBlock;
use Modules\Post\Models\PostHowtoStep;
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
            ->with([
                // publicEmbed() — trang public không có TenantContext (khách vãng lai), OrganizationScope
                // mặc định trả rỗng cho Product tenant-scoped (xem Product::scopePublicEmbed()).
                'productBlock.items.product' => fn ($q) => $q->publicEmbed(),
                'productBlock.items.buttons', 'productBlock.buttons', 'faqBlock.items', 'howtoBlock.steps',
                'comparisonBlock.columns', 'comparisonBlock.rows',
            ])
            ->get();

        return $blocks->map(function ($block) {
            if ($block->type === ContentBlockType::Text) {
                return (string) $block->text_html;
            }

            if ($block->type === ContentBlockType::Faq) {
                return $block->faqBlock
                    ? view('post::components.faq-block.default', ['block' => $block->faqBlock])->render()
                    : '';
            }

            if ($block->type === ContentBlockType::Citation) {
                return view('post::components.citation-block.default', ['block' => $block])->render();
            }

            if ($block->type === ContentBlockType::Howto) {
                return $block->howtoBlock
                    ? view('post::components.howto-block.default', ['block' => $block->howtoBlock])->render()
                    : '';
            }

            if ($block->type === ContentBlockType::Comparison) {
                return $block->comparisonBlock
                    ? view('post::components.comparison-block.default', ['block' => $block->comparisonBlock])->render()
                    : '';
            }

            if ($block->type === ContentBlockType::Testimonial) {
                return view('post::components.testimonial-block.default', ['block' => $block])->render();
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
     * spec/Markdown_Content_Negotiation_Technical_Specification.md §4 — bản Markdown ĐẦY ĐỦ
     * (kèm tiêu đề/mô tả/nguồn/ngày cập nhật) mà content negotiation (Accept: text/markdown)
     * trả về nguyên văn cho `PublicArticleController::showMarkdown()`. Tách khỏi
     * `renderMarkdown()` (chỉ render các content-block, không có header) để trang preview admin
     * (Modules\Post\Features\MarkdownPreview) tái dùng được nguyên vẹn, không lặp lại logic
     * dựng header ở 2 nơi.
     */
    public function renderMarkdownDocument(PostArticleTranslation $translation): string
    {
        $canonicalUrl = route('post.public.article', ['slug' => $translation->slug, 'id' => $translation->id]);
        $description = $translation->seo_description ?: $translation->direct_answer ?: $translation->excerpt;

        $header = "# {$translation->title}\n\n";
        if ($description) {
            $header .= "> {$description}\n\n";
        }
        $header .= "Nguồn: {$canonicalUrl}\n";
        if ($translation->updated_at) {
            $header .= 'Cập nhật: '.$translation->updated_at->format('d/m/Y')."\n";
        }
        $header .= "\n---\n\n";

        return $header.$this->renderMarkdown($translation);
    }

    /**
     * spec/Markdown_Content_Negotiation_Technical_Specification.md §4 — Markdown thuần của các
     * content-block (KHÔNG kèm tiêu đề bài viết — Controller tự thêm `# {title}` để tránh 2 H1
     * trong 1 response, xem `demoteH1()`). Cache theo `updated_at` — tự hết hạn khi bài được lưu
     * lại (`UpdateTranslationAction`/`RestoreArticleVersionAction` luôn `update()` translation
     * trước khi đổi content-block trong cùng transaction).
     */
    public function renderMarkdown(PostArticleTranslation $translation): string
    {
        return Cache::remember(
            "post:{$translation->id}:markdown:v1:{$translation->updated_at?->timestamp}",
            now()->addDay(),
            function () use ($translation) {
                $blocks = $translation->contentBlocks()
                    ->with([
                        'productBlock.items.product' => fn ($q) => $q->publicEmbed(),
                        'productBlock.items.buttons', 'productBlock.buttons', 'faqBlock.items', 'howtoBlock.steps',
                        'comparisonBlock.columns', 'comparisonBlock.rows',
                    ])
                    ->get();

                return $blocks
                    ->map(fn (PostContentBlock $block) => $this->blockToMarkdown($block))
                    ->filter(fn (string $md) => trim($md) !== '')
                    ->implode("\n\n");
            }
        );
    }

    /**
     * `match` liệt kê đủ mọi `ContentBlockType` hiện có (§4) — nhánh `default` chỉ tồn tại để
     * không fallback ngầm định nếu 1 `ContentBlockType` MỚI được thêm sau này mà quên cập nhật
     * chỗ này (ghi log warning + trả ghi chú thấy được thay vì crash `UnhandledMatchError` giữa
     * request công khai).
     */
    private function blockToMarkdown(PostContentBlock $block): string
    {
        return match ($block->type) {
            ContentBlockType::Text => $this->textBlockToMarkdown($block),
            ContentBlockType::Product => $this->productBlockToMarkdown($block),
            ContentBlockType::Faq => $this->faqBlockToMarkdown($block),
            ContentBlockType::Citation => $this->citationBlockToMarkdown($block),
            ContentBlockType::Howto => $this->howtoBlockToMarkdown($block),
            ContentBlockType::Comparison => $this->comparisonBlockToMarkdown($block),
            ContentBlockType::Testimonial => $this->testimonialBlockToMarkdown($block),
            default => $this->unknownBlockToMarkdown($block),
        };
    }

    private function unknownBlockToMarkdown(PostContentBlock $block): string
    {
        Log::warning('ArticleContentRenderer::renderMarkdown — loại content-block chưa hỗ trợ Markdown.', [
            'block_id' => $block->id,
            'type' => $block->type?->value,
        ]);

        return "<!-- loại nội dung '{$block->type?->value}' chưa hỗ trợ bản Markdown -->";
    }

    /** `league/html-to-markdown` sau khi `absolutizeUrls()` + `demoteH1()` (§4). */
    private function textBlockToMarkdown(PostContentBlock $block): string
    {
        $html = $this->demoteH1($this->absolutizeUrls((string) $block->text_html));

        // header_style: 'atx' ('## Tiêu đề') — mặc định league/html-to-markdown dùng Setext
        // ('Tiêu đề\n-------') cho riêng h1/h2, không nhất quán với các heading '###' mà các
        // block khác (Comparison/Faq/Howto/Product) tự tạo ở dưới.
        return trim((new HtmlConverter(['strip_tags' => true, 'header_style' => 'atx']))->convert($html));
    }

    /** Product → tên + giá + mô tả ngắn (`display_description`), không tên/ảnh override rỗng. */
    private function productBlockToMarkdown(PostContentBlock $block): string
    {
        $pb = $block->productBlock;
        if (! $pb) {
            return '';
        }

        $lines = [];
        if ($pb->heading) {
            $lines[] = "### {$pb->heading}";
        }

        foreach ($pb->items as $item) {
            $title = $item->display_title ?: 'Sản phẩm';
            $line = "- **{$title}**";
            if ($item->display_price_label) {
                $line .= " — {$item->display_price_label}";
            }
            $lines[] = $line;
            if ($item->display_description) {
                $lines[] = "  {$item->display_description}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Faq/Howto — không nêu chi tiết ở spec §4 (chỉ liệt kê Text/Comparison/Testimonial-Citation/
     * Product), nhưng spec cũng đòi "match liệt kê đủ mọi ContentBlockType" (không fallback ngầm
     * định) — dùng quy ước Markdown thông thường (Q/A đậm, numbered list) nhất quán với các
     * block khác, không phải nhánh `default`.
     */
    private function faqBlockToMarkdown(PostContentBlock $block): string
    {
        $fb = $block->faqBlock;
        if (! $fb) {
            return '';
        }

        $lines = [];
        if ($fb->heading) {
            $lines[] = "### {$fb->heading}";
        }

        foreach ($fb->items as $item) {
            $lines[] = "**{$item->question}**";
            $lines[] = (string) $item->answer;
            $lines[] = '';
        }

        return trim(implode("\n", $lines));
    }

    private function howtoBlockToMarkdown(PostContentBlock $block): string
    {
        $hb = $block->howtoBlock;
        if (! $hb) {
            return '';
        }

        $lines = [];
        if ($hb->name) {
            $lines[] = "### {$hb->name}";
        }
        if ($hb->description) {
            $lines[] = (string) $hb->description;
            $lines[] = '';
        }

        foreach ($hb->steps as $index => $step) {
            $lines[] = ($index + 1).". **{$step->name}** — {$step->text}";
        }

        return trim(implode("\n", $lines));
    }

    /** Bảng GFM thật — `array_pad`/`array_slice` phòng thủ lớp 2 dù đã validate số cột lúc ghi (§4). */
    private function comparisonBlockToMarkdown(PostContentBlock $block): string
    {
        $cb = $block->comparisonBlock;
        if (! $cb) {
            return '';
        }

        $columnCount = $cb->columns->count();
        if ($columnCount === 0) {
            return $cb->name ? "### {$cb->name}" : '';
        }

        $lines = [];
        if ($cb->name) {
            $lines[] = "### {$cb->name}";
        }
        if ($cb->description) {
            $lines[] = (string) $cb->description;
            $lines[] = '';
        }

        $header = array_merge([''], $cb->columns->pluck('label')->all());
        $lines[] = '| '.implode(' | ', array_map($this->escapeTableCell(...), $header)).' |';
        $lines[] = '| '.implode(' | ', array_fill(0, count($header), '---')).' |';

        foreach ($cb->rows as $row) {
            $values = array_slice(array_pad($row->values ?? [], $columnCount, ''), 0, $columnCount);
            $cells = array_merge([$row->label], $values);
            $lines[] = '| '.implode(' | ', array_map($this->escapeTableCell(...), $cells)).' |';
        }

        return implode("\n", $lines);
    }

    private function escapeTableCell(mixed $value): string
    {
        return str_replace(['|', "\n"], ['\\|', ' '], (string) $value);
    }

    /** Testimonial/Citation → blockquote (§4). */
    private function testimonialBlockToMarkdown(PostContentBlock $block): string
    {
        $quote = trim((string) $block->testimonial_quote);
        if ($quote === '') {
            return '';
        }

        $lines = array_map(fn ($line) => "> {$line}", explode("\n", $quote));

        $attribution = trim(implode(', ', array_filter([
            $block->testimonial_person_name,
            $block->testimonial_person_title,
            $block->testimonial_company_name,
        ])));

        if ($attribution !== '') {
            $lines[] = '>';
            $lines[] = "> — {$attribution}";
        }

        return implode("\n", $lines);
    }

    private function citationBlockToMarkdown(PostContentBlock $block): string
    {
        $text = trim((string) $block->citation_text);
        if ($text === '') {
            return '';
        }

        $lines = array_map(fn ($line) => "> {$line}", explode("\n", $text));

        if ($block->citation_source_name) {
            $source = $block->citation_source_url
                ? "[{$block->citation_source_name}]({$block->citation_source_url})"
                : $block->citation_source_name;
            $lines[] = '>';
            $lines[] = "> — {$source}";
        }

        return implode("\n", $lines);
    }

    /** URL root-relative (href="/..."/src="/...") → tuyệt đối — text-block lưu HTML gốc tương đối theo domain hiện có (§4). */
    private function absolutizeUrls(string $html): string
    {
        $base = rtrim(config('app.url'), '/');

        return preg_replace_callback(
            '/\b(href|src)="\/(?!\/)([^"]*)"/i',
            fn ($m) => $m[1].'="'.$base.'/'.$m[2].'"',
            $html
        ) ?? $html;
    }

    /** Tránh 2 H1 trong 1 response Markdown — Controller đã tự thêm `# {title}` (§4). */
    private function demoteH1(string $html): string
    {
        $html = preg_replace('/<\/h1>/i', '</h2>', $html) ?? $html;

        return preg_replace('/<h1(\s[^>]*)?>/i', '<h2$1>', $html) ?? $html;
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
        $dom->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_NOERROR | LIBXML_NOWARNING);
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
                $name = strtolower($attr->nodeName);
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
        $out = '';
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
            ->with([
                'productBlock.items.product', 'productBlock.items.buttons', 'productBlock.buttons',
                'faqBlock.items', 'howtoBlock.steps', 'comparisonBlock.columns', 'comparisonBlock.rows',
            ])
            ->get();

        return $blocks->map(function (PostContentBlock $block) {
            if ($block->type === ContentBlockType::Text) {
                return ['type' => 'text', 'html' => $block->text_html];
            }

            if ($block->type === ContentBlockType::Faq) {
                $fb = $block->faqBlock;
                if (! $fb) {
                    return null;
                }

                return [
                    'type' => 'faq',
                    'block_uuid' => $fb->uuid,
                    'heading' => $fb->heading,
                    'items' => $fb->items->map(fn ($item) => [
                        'question' => $item->question,
                        'answer' => $item->answer,
                    ])->values(),
                ];
            }

            if ($block->type === ContentBlockType::Citation) {
                return [
                    'type' => 'citation',
                    'citation_text' => $block->citation_text,
                    'citation_source_name' => $block->citation_source_name,
                    'citation_source_url' => $block->citation_source_url,
                ];
            }

            if ($block->type === ContentBlockType::Testimonial) {
                return [
                    'type' => 'testimonial',
                    'quote' => $block->testimonial_quote,
                    'person_name' => $block->testimonial_person_name,
                    'person_title' => $block->testimonial_person_title,
                    'company_name' => $block->testimonial_company_name,
                    'avatar_url' => $block->testimonial_avatar_url,
                    'result_metric' => $block->testimonial_result_metric,
                ];
            }

            if ($block->type === ContentBlockType::Howto) {
                $hb = $block->howtoBlock;
                if (! $hb) {
                    return null;
                }

                return [
                    'type' => 'howto',
                    'block_uuid' => $hb->uuid,
                    'name' => $hb->name,
                    'description' => $hb->description,
                    'steps' => $hb->steps->map(fn ($step) => [
                        'name' => $step->name,
                        'text' => $step->text,
                    ])->values(),
                ];
            }

            if ($block->type === ContentBlockType::Comparison) {
                $cb = $block->comparisonBlock;
                if (! $cb) {
                    return null;
                }

                return [
                    'type' => 'comparison',
                    'block_uuid' => $cb->uuid,
                    'name' => $cb->name,
                    'description' => $cb->description,
                    'columns' => $cb->columns->map(fn ($col) => ['label' => $col->label])->values(),
                    'rows' => $cb->rows->map(fn ($row) => [
                        'label' => $row->label,
                        'values' => $row->values,
                    ])->values(),
                ];
            }

            $pb = $block->productBlock;
            if (! $pb) {
                return null;
            }

            return [
                'type' => 'product',
                'block_uuid' => $pb->uuid,
                'template' => $pb->template->value,
                'heading' => $pb->heading,
                'items' => $pb->items->map(fn ($item) => [
                    'item_key' => $item->item_key,
                    'product_id' => $item->product_id,
                    'title_override' => $item->title_override,
                    'price_label_override' => $item->price_label_override,
                    'description_override' => $item->description_override,
                    'image_url_override' => $item->image_url_override,
                    'cached_name' => $item->product?->name,
                    'cached_image' => $item->product?->cover_image_url,
                    'cached_price' => $item->product?->display_price,
                    'cached_links' => collect(ProductLinkType::cases())
                        ->filter(fn ($type) => filled($item->product?->{$type->urlColumn()}))
                        ->map(fn ($type) => ['type' => $type->value, 'label' => $type->label()])
                        ->values(),
                    'buttons' => $item->buttons->map(fn ($btn) => [
                        'button_key' => $btn->button_key,
                        'label' => $btn->label,
                        'url_type' => $btn->url_type->value,
                        'url' => $btn->url,
                        'product_link_type' => $btn->product_link_type,
                        'target' => $btn->target->value,
                        'style' => $btn->style->value,
                    ]),
                ]),
                'block_buttons' => $pb->buttons->map(fn ($btn) => [
                    'button_key' => $btn->button_key,
                    'label' => $btn->label,
                    'url_type' => $btn->url_type->value,
                    'url' => $btn->url,
                    'product_link_type' => $btn->product_link_type,
                    'target' => $btn->target->value,
                    'style' => $btn->style->value,
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
     * @param  array<int, array>  $blocks
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

            if (($blockData['type'] ?? null) === 'faq') {
                return view('post::components.faq-block.default', [
                    'block' => $this->hydrateFaqBlockSnapshot($blockData),
                    'preview' => true,
                ])->render();
            }

            if (($blockData['type'] ?? null) === 'citation') {
                return view('post::components.citation-block.default', [
                    'block' => (object) [
                        'citation_text' => $blockData['citation_text'] ?? '',
                        'citation_source_name' => $blockData['citation_source_name'] ?? '',
                        'citation_source_url' => $blockData['citation_source_url'] ?? null,
                    ],
                    'preview' => true,
                ])->render();
            }

            if (($blockData['type'] ?? null) === 'howto') {
                return view('post::components.howto-block.default', [
                    'block' => $this->hydrateHowtoBlockSnapshot($blockData),
                    'preview' => true,
                ])->render();
            }

            if (($blockData['type'] ?? null) === 'comparison') {
                return view('post::components.comparison-block.default', [
                    'block' => $this->hydrateComparisonBlockSnapshot($blockData),
                    'preview' => true,
                ])->render();
            }

            if (($blockData['type'] ?? null) === 'testimonial') {
                return view('post::components.testimonial-block.default', [
                    'block' => (object) [
                        'testimonial_quote' => $blockData['quote'] ?? '',
                        'testimonial_person_name' => $blockData['person_name'] ?? '',
                        'testimonial_person_title' => $blockData['person_title'] ?? null,
                        'testimonial_company_name' => $blockData['company_name'] ?? null,
                        'testimonial_avatar_url' => $blockData['avatar_url'] ?? null,
                        'testimonial_result_metric' => $blockData['result_metric'] ?? null,
                    ],
                    'preview' => true,
                ])->render();
            }

            $block = $this->hydrateProductBlockSnapshot($blockData, $existingProducts, $missingProductIds);

            return view(
                "post::components.product-block.{$block->template->value}",
                ['block' => $block, 'preview' => true]
            )->render();
        })->implode('');

        return ['html' => $html, 'missing_products' => $missingProducts];
    }

    /** FAQ không tham chiếu entity ngoài (Product) — không có khái niệm "đã xoá"/fallback như product, đơn giản hơn nhiều. */
    private function hydrateFaqBlockSnapshot(array $blockData): PostFaqBlock
    {
        $block = new PostFaqBlock;
        $block->exists = false;
        $block->forceFill([
            'id' => 0,
            'uuid' => $blockData['block_uuid'] ?? null,
            'heading' => $blockData['heading'] ?? null,
        ]);

        $items = collect($blockData['items'] ?? [])->map(function ($itemData) {
            $item = new PostFaqItem;
            $item->exists = false;
            $item->forceFill([
                'id' => 0,
                'question' => $itemData['question'] ?? '',
                'answer' => $itemData['answer'] ?? '',
            ]);

            return $item;
        });

        $block->setRelation('items', $items);

        return $block;
    }

    /** HowTo không tham chiếu entity ngoài — không cần fallback như product, đơn giản hơn nhiều. */
    private function hydrateHowtoBlockSnapshot(array $blockData): PostHowtoBlock
    {
        $block = new PostHowtoBlock;
        $block->exists = false;
        $block->forceFill([
            'id' => 0,
            'uuid' => $blockData['block_uuid'] ?? null,
            'name' => $blockData['name'] ?? null,
            'description' => $blockData['description'] ?? null,
        ]);

        $steps = collect($blockData['steps'] ?? [])->map(function ($stepData) {
            $step = new PostHowtoStep;
            $step->exists = false;
            $step->forceFill([
                'id' => 0,
                'name' => $stepData['name'] ?? '',
                'text' => $stepData['text'] ?? '',
            ]);

            return $step;
        });

        $block->setRelation('steps', $steps);

        return $block;
    }

    /** Comparison không tham chiếu entity ngoài — không cần fallback như product, đơn giản hơn nhiều. */
    private function hydrateComparisonBlockSnapshot(array $blockData): PostComparisonBlock
    {
        $block = new PostComparisonBlock;
        $block->exists = false;
        $block->forceFill([
            'id' => 0,
            'uuid' => $blockData['block_uuid'] ?? null,
            'name' => $blockData['name'] ?? null,
            'description' => $blockData['description'] ?? null,
        ]);

        $columns = collect($blockData['columns'] ?? [])->map(function ($columnData) {
            $column = new PostComparisonColumn;
            $column->exists = false;
            $column->forceFill(['id' => 0, 'label' => $columnData['label'] ?? '']);

            return $column;
        });

        $rows = collect($blockData['rows'] ?? [])->map(function ($rowData) {
            $row = new PostComparisonRow;
            $row->exists = false;
            $row->forceFill(['id' => 0, 'label' => $rowData['label'] ?? '', 'values' => $rowData['values'] ?? []]);

            return $row;
        });

        $block->setRelation('columns', $columns);
        $block->setRelation('rows', $rows);

        return $block;
    }

    private function hydrateProductBlockSnapshot(array $blockData, Collection $existingProducts, Collection $missingProductIds): PostProductBlock
    {
        $block = new PostProductBlock;
        $block->exists = false;
        $block->forceFill([
            'id' => 0,
            'uuid' => $blockData['block_uuid'] ?? null,
            'template' => $blockData['template'],
            'heading' => $blockData['heading'] ?? null,
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

        $item = new PostProductBlockItem;
        $item->exists = false;
        $item->forceFill([
            'id' => 0,
            'item_key' => $itemData['item_key'] ?? null,
            'product_id' => $productId,
            'title_override' => $itemData['title_override'] ?? null,
            'price_label_override' => $itemData['price_label_override'] ?? null,
            'description_override' => $itemData['description_override'] ?? null,
            'image_url_override' => $itemData['image_url_override'] ?? null,
        ]);

        if ($isMissing) {
            // Không được lazy-load Product cho id đã biết chắc không còn tồn tại (§13.3) —
            // set relation = null NGAY để accessor display_* không bao giờ kích hoạt query.
            $item->setRelation('product', null);
            $item->title_override = $item->title_override ?? 'Sản phẩm không còn tồn tại';
            $item->image_url_override = $item->image_url_override ?? self::MISSING_PRODUCT_PLACEHOLDER_IMAGE;
        } else {
            $item->setRelation('product', $productId ? $existingProducts->get($productId) : null);
        }

        $item->setRelation('buttons', collect($itemData['buttons'] ?? [])->map(fn ($b) => $this->hydrateButtonSnapshot($b)));

        return $item;
    }

    private function hydrateButtonSnapshot(array $buttonData): PostProductBlockButton
    {
        $button = new PostProductBlockButton;
        $button->exists = false;
        $button->forceFill([
            'id' => 0, // route('post.cta.redirect', $button) cần 1 route-key hợp lệ để không ném UrlGenerationException (§13.3) — bấm vào là dead-link chấp nhận được cho preview lịch sử.
            'button_key' => $buttonData['button_key'] ?? null,
            'label' => $buttonData['label'] ?? null,
            'url_type' => $buttonData['url_type'],
            'url' => $buttonData['url'] ?? null,
            'product_link_type' => $buttonData['product_link_type'] ?? null,
            'target' => $buttonData['target'] ?? '_blank',
            'style' => $buttonData['style'] ?? 'primary',
        ]);

        return $button;
    }

    private const MISSING_PRODUCT_PLACEHOLDER_IMAGE = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200' viewBox='0 0 200 200'%3E%3Crect width='200' height='200' fill='%23e5e7eb'/%3E%3Ctext x='50%25' y='50%25' font-size='14' fill='%239ca3af' text-anchor='middle' dominant-baseline='middle' font-family='sans-serif'%3ESản phẩm đã xoá%3C/text%3E%3C/svg%3E";
}
