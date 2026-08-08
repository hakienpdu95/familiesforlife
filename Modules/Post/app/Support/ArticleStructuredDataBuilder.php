<?php

namespace Modules\Post\Support;

use Modules\Post\Enums\ContentBlockType;
use Modules\Post\Features\AuthorHub\Support\AuthorRoleResolver;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostProductBlockItem;

/**
 * AEO/GEO (2026-07-28) — trang bài viết public trước đây KHÔNG render bất kỳ structured data
 * nào (chỉ có SiteNavigationElement ở layout dùng chung, xem layouts/frontend.blade.php), khiến
 * Google/AI answer engine (AI Overview, ChatGPT, Perplexity...) thiếu tín hiệu rõ ràng để
 * hiểu/trích dẫn bài viết. Article + BreadcrumbList + Person (tác giả, embed trong `author`) +
 * FAQPage (nếu bài có ít nhất 1 khối FAQ) + HowTo (1 node/khối, xem PostHowtoBlock) — text ở
 * đây LUÔN khớp với faq-block/howto-block/default.blade.php vì cùng đọc từ
 * `$translation->faqBlocks`/`howtoBlocks`, không cần đồng bộ tay. `Article.citation` (đợt 4) lấy
 * từ khối Citation trong content-block — "citation engineering" theo nghiên cứu Princeton/KDD 2024.
 * `Article.mentions` (đợt 5, 2026-08-08) — thực thể/chủ đề bài viết lấy từ PostTag sẵn có, phục
 * vụ "entity fan-out" (spec/giadinh.md — Moz "A Guide to Web Guide"), xem `buildMentions()`.
 * Đợt 6 (2026-08-08) — khối "So sánh" (ContentBlockType::Comparison) CỐ Ý KHÔNG có
 * buildComparisons() ở đây — xem lý do ở comparison-block/default.blade.php (không có @type
 * schema.org nào phù hợp cho bảng so sánh tuỳ ý; semantic HTML <table> là tín hiệu GEO đủ tốt).
 *
 * HIỆU CHỈNH KỲ VỌNG (2026-08-08, đối chiếu spec/giadinh.md — Moz "5 Takeaways from Google's GEO
 * Guidelines", dẫn nguyên văn tài liệu chính thức của Google): "Structured data isn't required
 * for generative AI search, and there's no special schema.org markup you need to add" —
 * "obsessively optimizing your markup... isn't going to produce additional benefits." Class này
 * ĐÃ Ở MỨC ĐỦ (Article/BreadcrumbList/FAQPage/HowTo/Product/mentions) — KHÔNG cần chủ động thêm
 * loại schema mới "phòng hờ" nếu chưa có nhu cầu nội dung cụ thể phát sinh (đúng lý do đã từ chối
 * buildComparisons() ở trên) — Google xác nhận đây không phải đòn bẩy chính cho khả năng hiện
 * diện trong GenAI search, nội dung "phi hàng hoá" (§4.25 ContentOutlines) mới là trọng tâm.
 *
 * Đợt 7 (2026-08-08) — khối "Lời chứng thực khách hàng" (ContentBlockType::Testimonial) CŨNG CỐ
 * Ý KHÔNG có buildTestimonials()/JSON-LD Review — xem lý do đầy đủ ở testimonial-block/default.
 * blade.php (schema.org Review yêu cầu `itemReviewed` trỏ 1 entity cụ thể mà 1 testimonial nhúng
 * trong bài viết không có; ép vào rủi ro bị Google coi là dùng sai Review/Rating markup).
 */
class ArticleStructuredDataBuilder
{
    /** @return array<int, array<string, mixed>> danh sách JSON-LD node độc lập (Article, BreadcrumbList, FAQPage, HowTo...) */
    public function build(PostArticle $article, PostArticleTranslation $translation, string $canonicalUrl): array
    {
        $nodes = array_filter([
            $this->buildArticle($article, $translation, $canonicalUrl),
            $this->buildBreadcrumbList($article, $translation, $canonicalUrl),
            $this->buildFaqPage($translation),
        ]);

        return array_values(array_merge($nodes, $this->buildHowTos($translation), $this->buildProducts($translation)));
    }

    private function buildArticle(PostArticle $article, PostArticleTranslation $translation, string $canonicalUrl): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $translation->title,
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonicalUrl],
            'publisher' => $this->buildPublisher(),
        ];

        if ($translation->published_at) {
            $schema['datePublished'] = $translation->published_at->toIso8601String();
        }

        // updated_at của translation phản ánh đúng lần sửa NỘI DUNG gần nhất (title/excerpt/SEO) —
        // sát nghĩa "dateModified" hơn updated_at của PostArticle (chỉ đổi khi field không-theo-
        // ngôn-ngữ như sponsor/is_featured thay đổi).
        $dateModified = $translation->updated_at ?? $translation->published_at;
        if ($dateModified) {
            $schema['dateModified'] = $dateModified->toIso8601String();
        }

        // seo_description ưu tiên trước vì được biên tập viên viết RIÊNG cho mục đích SEO meta;
        // direct_answer (AEO) đứng sau — dù là câu trả lời trực tiếp, mục đích chính của nó là
        // hiển thị NỔI BẬT đầu bài (xem article.blade.php), không phải thay thế hẳn meta description.
        $description = $translation->seo_description ?: $translation->direct_answer ?: $translation->excerpt;
        if ($description) {
            $schema['description'] = $description;
        }

        if ($article->cover_image_url) {
            $schema['image'] = [$article->cover_image_url];
        }

        $author = $this->buildAuthor($article);
        if ($author) {
            $schema['author'] = $author;
        }

        $citations = $this->buildCitations($translation);
        if ($citations) {
            $schema['citation'] = $citations;
        }

        $mentions = $this->buildMentions($article);
        if ($mentions) {
            $schema['mentions'] = $mentions;
        }

        return $schema;
    }

    /**
     * "Entity fan-out" (spec/giadinh.md — Moz "A Guide to Web Guide: Our Hybrid Search Future")
     * — khai báo rõ các thực thể/chủ đề bài viết đề cập để hỗ trợ answer engine mở rộng câu hỏi
     * theo thực thể (vd "X có tốt cho Y không?"). Tái dùng `PostTag` sẵn có làm nguồn — biên tập
     * viên đã gắn tag = đã xác định "thực thể/chủ đề" của bài từ trước, không cần trường nhập
     * liệu mới. `Thing` là @type tổng quát nhất hợp lý vì không biết chắc 1 tag là Place/
     * Organization/Product cụ thể nào — tránh khai sai loại thực thể còn tệ hơn không khai.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildMentions(PostArticle $article): array
    {
        return $article->tags
            ->map(fn ($tag) => ['@type' => 'Thing', 'name' => $tag->name])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function buildCitations(PostArticleTranslation $translation): array
    {
        return $translation->contentBlocks
            ->filter(fn ($block) => $block->type === ContentBlockType::Citation && trim((string) $block->citation_source_name) !== '')
            ->map(function ($block) {
                $citation = ['@type' => 'CreativeWork', 'name' => $block->citation_source_name];

                if ($block->citation_source_url) {
                    $citation['url'] = $block->citation_source_url;
                }

                return $citation;
            })
            ->values()
            ->all();
    }

    /** Person schema — chỉ thêm `url`/`sameAs` khi hồ sơ thật sự công khai, tránh lộ link tới hồ sơ is_public=false. */
    private function buildAuthor(PostArticle $article): ?array
    {
        $user = $article->createdBy;

        if (! $user) {
            return null;
        }

        $profile = $user->authorProfile;
        $name = $profile?->displayName() ?: $user->name;

        if (! $name) {
            return null;
        }

        $person = ['@type' => 'Person', 'name' => $name];

        if (! $profile) {
            return $person;
        }

        if ($profile->job_title) {
            $person['jobTitle'] = $profile->job_title;
        }

        if ($profile->credentials) {
            $person['description'] = $profile->credentials;
        }

        if ($profile->is_public && AuthorRoleResolver::isEligible($user)) {
            $person['url'] = route('post.public.author-hub.show', $profile);
        }

        $sameAs = array_values(array_filter($profile->social_links ?? []));
        if ($sameAs) {
            $person['sameAs'] = $sameAs;
        }

        return $person;
    }

    private function buildPublisher(): array
    {
        return [
            '@type' => 'Organization',
            'name' => config('app.site_name'),
            'url' => route('post.public.home'),
        ];
    }

    /** Home > danh mục chính (nếu có) > bài viết — khớp đúng breadcrumb HIỂN THỊ ở article.blade.php, không thêm cấp nào khác để tránh lệch với UI. */
    private function buildBreadcrumbList(PostArticle $article, PostArticleTranslation $translation, string $canonicalUrl): array
    {
        $items = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang Chủ', 'item' => route('post.public.home')],
        ];

        $category = $article->categories->first();
        if ($category) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $category->name,
                'item' => route('post.public.category', ['category' => $category->slug]),
            ];
        }

        $items[] = [
            '@type' => 'ListItem',
            'position' => count($items) + 1,
            'name' => $translation->title,
            'item' => $canonicalUrl,
        ];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /** Gộp câu hỏi từ MỌI khối FAQ trong bài vào 1 FAQPage duy nhất — Google chỉ cần 1 node/trang. */
    private function buildFaqPage(PostArticleTranslation $translation): ?array
    {
        $questions = $translation->faqBlocks
            ->flatMap(fn ($block) => $block->items)
            ->filter(fn ($item) => trim($item->question) !== '' && trim($item->answer) !== '')
            ->values();

        if ($questions->isEmpty()) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $questions->map(fn ($item) => [
                '@type' => 'Question',
                'name' => $item->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item->answer,
                ],
            ])->all(),
        ];
    }

    /**
     * 1 node HowTo RIÊNG cho MỖI khối (khác FAQPage — gộp chung 1 node) vì mỗi khối HowTo là 1
     * QUY TRÌNH khác nhau (VD "Cách pha sữa" và "Cách vệ sinh bình sữa" trong cùng 1 bài không nên
     * gộp chung step vào 1 HowTo, sẽ sai ngữ nghĩa thứ tự bước).
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildHowTos(PostArticleTranslation $translation): array
    {
        return $translation->howtoBlocks
            ->filter(fn ($block) => $block->steps->isNotEmpty())
            ->map(fn ($block) => array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'HowTo',
                'name' => $block->name ?: $translation->title,
                'description' => $block->description,
                'step' => $block->steps->map(fn ($step, $i) => [
                    '@type' => 'HowToStep',
                    'position' => $i + 1,
                    'name' => $step->name,
                    'text' => $step->text,
                ])->values()->all(),
            ]))
            ->values()
            ->all();
    }

    /**
     * GEO (2026-08-01, nguồn tham khảo aithinkerlab.com/generative-engine-optimization-2026) —
     * "Khối sản phẩm" trong bài viết trước đây không phát sinh schema Product/Offer nào, dù
     * đã hiển thị đầy đủ tên/giá/ảnh/link cho người đọc (xem product-block/*.blade.php). 1 node
     * Product/item — không gộp theo block vì mỗi item là 1 sản phẩm riêng biệt (giống lý do
     * buildHowTos() không gộp theo bài).
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildProducts(PostArticleTranslation $translation): array
    {
        return $translation->contentBlocks
            ->filter(fn ($block) => $block->type === ContentBlockType::Product && $block->productBlock)
            ->flatMap(fn ($block) => $block->productBlock->items)
            ->filter(fn (PostProductBlockItem $item) => trim((string) $item->display_title) !== '')
            ->map(function (PostProductBlockItem $item) {
                $schema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'Product',
                    'name' => $item->display_title,
                ];

                if ($item->display_image_url) {
                    $schema['image'] = [$item->display_image_url];
                }

                if ($item->display_description) {
                    $schema['description'] = $item->display_description;
                }

                $offer = $this->buildOffer($item);
                if ($offer) {
                    $schema['offers'] = $offer;
                }

                return $schema;
            })
            ->values()
            ->all();
    }

    /**
     * CHỈ phát sinh `offers` khi có giá SỐ THẬT từ Product liên kết (`price` decimal) — bỏ qua
     * `price_label_override`/`display_price_label` vì đó là chuỗi hiển thị tự do (VD "Liên hệ",
     * "Giá tốt nhất thị trường"), không đảm bảo đúng định dạng số Offer.price yêu cầu; phát sinh
     * sai định dạng sẽ khiến Google/AI bỏ qua luôn cả node Product (thà thiếu `offers` còn hơn sai).
     */
    private function buildOffer(PostProductBlockItem $item): ?array
    {
        $product = $item->product;

        if (! $product || $product->price === null) {
            return null;
        }

        $offer = [
            '@type' => 'Offer',
            'price' => (string) $product->price,
            'priceCurrency' => $product->currency ?: 'VND',
        ];

        // Chỉ nhận URL http(s) thật — resolveTargetUrl() có thể trả về tel:/mailto: cho nút liên
        // hệ, không hợp lệ cho Offer.url (schema.org yêu cầu URL trang mua hàng).
        $url = $item->buttons->first(fn ($button) => str_starts_with((string) $button->resolveTargetUrl(), 'http'))
            ?->resolveTargetUrl();

        if ($url) {
            $offer['url'] = $url;
        }

        return $offer;
    }
}
