<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\HeadingData;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Data\SourceStructureData;

/**
 * Layer 1 — spec/CoreIdeaExtractor.md §5.2/§5.3. Parse HTML bằng DOMDocument/DOMXPath built-in
 * (repo không có package parse HTML chuyên dụng) — cùng convention load an toàn đã dùng ở
 * Modules/Post/app/Support/ArticleContentRenderer.php::sanitizeTextHtml() (libxml_use_internal_errors
 * + LIBXML_NOERROR|LIBXML_NOWARNING), áp dụng cho parse HTML ĐẦY ĐỦ (không phải fragment).
 */
class ExtractRawContentAction
{
    use AsAction;

    /**
     * class/id chứa 1 trong các từ khoá này bị coi là noise (menu/sidebar/quảng cáo...).
     * v1.15 — phản hồi thực tế: `sections`/`main_content` vẫn lẫn noise "trong thân bài" (không
     * phải widget/sidebar mà `stripNoise()` đã xử lý trước đó) — ngày đăng/lượt xem/"bởi Team"
     * (thường nằm trong khối byline/post-meta ngay đầu bài) và link "Mua ngay"/"Thêm vào giỏ"
     * (trang sản phẩm) — thêm từ khoá cho 2 nhóm này, cùng cơ chế/ngưỡng an toàn (tỉ lệ chữ tối đa
     * so với `<body>`) như các từ khoá đã có, không phải rule riêng.
     */
    private const NOISE_KEYWORDS = [
        'menu', 'nav', 'sidebar', 'comment', 'share', 'related',
        'advertisement', 'ads', 'breadcrumb', 'pagination', 'cookie', 'popup', 'modal', 'footer',
        // Viết cách nhau bằng KHOẢNG TRẮNG (không phải dấu gạch nối) — stripNoise() đã translate()
        // '-'/'_' trong @class/@id thành khoảng trắng TRƯỚC khi so khớp (xem $conditions bên
        // dưới), nên "post-meta"/"post_meta" trong DOM thực tế đều thành "post meta" ở chuỗi so
        // khớp; viết keyword bằng gạch nối ở đây sẽ KHÔNG BAO GIỜ khớp được gì.
        'byline', 'post meta', 'entry meta', 'view count', 'reading time',
        'add to cart', 'buy now', 'cta button',
    ];

    /** Heading text khớp 1 trong các cụm này (sau khi lowercase) coi là trang trí, không "có ý nghĩa" (§6.1.4 v1.3). */
    private const DECORATIVE_HEADING_PHRASES = [
        'xem thêm', 'chia sẻ', 'bình luận', 'liên quan', 'share', 'related', 'comments', 'đọc thêm',
        'từ khoá', 'từ khóa', 'tags', 'xem nhiều', 'cùng chuyên mục', 'tin liên quan',
    ];

    /**
     * Dò substring thô trên @id/@class (KHÔNG word-boundary) — cố ý rộng hơn ví dụ literal của
     * spec §5.3 ("content, post, entry, body, article-body") vì thực tế CMS đặt tên rất đa dạng
     * (VD Wikipedia dùng #mw-content-text/#bodyContent, không khớp bất kỳ literal nào trong
     * spec) — đánh đổi: có thể khớp nhầm 1 vài container không liên quan, nhưng đã lọc bớt qua
     * stripNoise() trước đó (menu/nav/sidebar/footer...) nên rủi ro chấp nhận được.
     */
    private const MAIN_CONTENT_SELECTORS = [
        "//*[contains(@id, 'content')]", "//*[contains(@class, 'content')]",
        "//*[contains(@id, 'post')]", "//*[contains(@class, 'post')]",
        "//*[contains(@id, 'entry')]", "//*[contains(@class, 'entry')]",
        "//*[contains(@id, 'article')]", "//*[contains(@class, 'article')]",
        "//*[contains(@id, 'body')]", "//*[contains(@class, 'body')]",
        '//main',
    ];

    private const PAYWALL_KEYWORDS = ['paywall', 'premium-content', 'subscriber-only', 'đăng nhập để đọc tiếp', 'subscribe to continue'];

    /** Title chứa 1 trong các cụm này (sau khi lowercase) → tín hiệu bài dạng hướng dẫn từng bước. */
    private const HOWTO_TITLE_KEYWORDS = [
        'cách ', 'hướng dẫn', 'làm thế nào', 'làm sao để', 'bí quyết',
        'phòng ngừa', 'phòng tránh', 'biện pháp', 'lưu ý khi',
    ];

    /** Title chứa 1 trong các cụm này → tín hiệu bài dạng so sánh/đánh giá. */
    private const REVIEW_TITLE_KEYWORDS = ['so sánh', ' vs ', 'đánh giá', 'review'];

    /** question_heading_ratio (đã có sẵn ở SourceStructureData) từ ngưỡng này trở lên → tín hiệu bài dạng FAQ. */
    private const FAQ_QUESTION_RATIO_THRESHOLD = 0.5;

    /** Tỉ lệ heading khớp pattern "số + khoảng trắng + chữ" (VD "1. Nguyên nhân") từ ngưỡng này trở lên → tín hiệu listicle/các-bước-đánh-số. */
    private const LISTICLE_HEADING_RATIO_THRESHOLD = 0.5;

    /** Số heading có ý nghĩa TỐI THIỂU để coi bài là "educational" khi không khớp rule cụ thể nào khác (xem classifyContentTypeSignal()). */
    private const EDUCATIONAL_MIN_HEADINGS = 3;

    /**
     * Thẻ block-level cần chèn ranh giới đoạn khi nối text — DOMNode::textContent tự nó KHÔNG
     * chèn khoảng trắng/newline giữa các thẻ (chỉ nối chuỗi text node liền nhau), nên với HTML
     * đã minify (không có whitespace thật giữa các tag trong response server trả về), 2 đoạn văn
     * liên tiếp bị dính chữ liền nhau không có ranh giới (đã gặp thật, VD ".significant.”Why
     * diastasis..." khi test thật với site minify HTML).
     */
    private const BLOCK_TAGS = [
        'p', 'div', 'section', 'article', 'header', 'footer',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'li', 'ul', 'ol', 'blockquote', 'figure', 'figcaption',
        'table', 'tr', 'td', 'th', 'pre',
    ];

    /**
     * @param string|null $mainContentSelector Selector đơn giản (id/class, kiểu ".detail-content",
     * "#main-content", có thể kèm tag như "div.detail-content") do người dùng chỉ định để khoanh
     * vùng main_content thay cho thuật toán tự động resolveContentRoot(). Null/rỗng → tự động.
     * @param string|null $languageOverride Ngôn ngữ nguồn do người dùng tự chọn trên form (vi/en/
     * th/id) — GHI ĐÈ hoàn toàn `<html lang>` khai báo lẫn kết quả đối chiếu ký tự script ở
     * resolveLanguage(), vì tự detect nhiều khi không chính xác (xem docblock resolveLanguage()).
     * Null → giữ nguyên hành vi tự động cũ.
     * @return array{title:?string, meta_description:?string, canonical_url:?string, content_category:?string, declared_content_type:?string, content_type_signal:?string, keywords:string[], headings:HeadingData[], sections:array<int, array{heading: ?string, text: string}>, main_content:string, publish_date:?string, date_modified:?string, author:?string, publisher_name:?string, language:string, language_mismatch_suspected:bool, word_count:int, meaningful_heading_count:int, paywall_suspected:bool, custom_selector_matched:?bool, source_structure:SourceStructureData}
     */
    public function handle(string $html, ?string $mainContentSelector = null, ?string $languageOverride = null): array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        // libxml's HTML parser thường KHÔNG nhận diện được `<meta charset="utf-8">` kiểu HTML5
        // (nhất là khi charset không phải attribute đầu tiên của thẻ meta, VD trang SSR/Nuxt.js:
        // `<meta data-n-head="ssr" charset="utf-8">`) và mặc định coi input là ISO-8859-1, làm vỡ
        // MỌI ký tự tiếng Việt có dấu dù $html (từ FetchArticleHtmlAction) đã đúng là UTF-8 —
        // (VD gặp thật: "Lịch ăn dặm" bị đọc thành "Lá»ch Än dáº·m"). Prefix pseudo-declaration
        // `<?xml encoding="UTF-8">` ép libxml parse đúng theo UTF-8 mà KHÔNG chèn node thật vào
        // DOM (libxml có xử lý đặc biệt cho khai báo này khi dùng với loadHTML).
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        $declaredLanguage = $this->extractLanguage($xpath);
        $metaDescription = $this->extractMetaDescription($xpath);
        $publishDate     = $this->extractPublishDate($xpath, $html);
        $author          = $this->extractAuthor($xpath, $html);
        $paywallSuspected = $this->looksLikePaywalled($xpath);

        // Tín hiệu do chính site khai báo qua OpenGraph/JSON-LD — đọc TRƯỚC stripNoise() cùng
        // nhóm với publishDate/author ở trên (đều nằm trong <head>/thẻ script, không phụ thuộc
        // cấu trúc <body>, và fromJsonLd() vốn đọc trên $html thô nên không bị ảnh hưởng bởi
        // việc DOM có bị mutate hay không — chỉ đặt cùng chỗ cho nhất quán quy ước đọc field).
        $canonicalUrl         = $this->extractCanonicalUrl($xpath);
        $contentCategory      = $this->extractContentCategory($xpath, $html);
        $declaredContentType  = $this->extractDeclaredContentType($xpath, $html);
        $dateModified         = $this->extractDateModified($xpath, $html);
        $publisherName        = $this->extractPublisherName($xpath, $html);

        // Title lấy TRƯỚC khi xoá noise (nằm trong <head>, không bị ảnh hưởng), nhưng fallback
        // h1 phải lấy SAU khi xoá noise (tránh h1 trang trí trong header/nav).
        $rawTitle = $this->extractTitleFromHead($xpath);

        $this->stripNoise($xpath);

        $title = $rawTitle ?: $this->firstHeadingText($xpath, 'h1');

        // Nếu người dùng chỉ định selector riêng (id/class), ưu tiên dùng khối đó làm root thay
        // vì thuật toán tự động — cho phép người dùng "sửa tay" khi thuật toán chọn sai container
        // (thường gặp với site có bố cục lạ/hiếm). Không khớp được (selector sai/không tồn tại
        // trên trang) → coi như không chỉ định, rơi về thuật toán tự động như cũ, không throw lỗi.
        $customSelectorMatched = null;
        $contentRoot = null;

        if ($mainContentSelector !== null && trim($mainContentSelector) !== '') {
            $contentRoot = $this->resolveContentRootFromSelector($xpath, $mainContentSelector);
            $customSelectorMatched = $contentRoot !== null;
        }

        // Xác định 1 node GỐC duy nhất cho nội dung chính, rồi soi headings TRONG PHẠM VI node
        // đó (không quét cả document) — nhiều site (VD báo Việt Nam) dùng <article> cho MỌI thẻ
        // "story card" (bài liên quan/xem nhiều), không chỉ bài chính; quét headings toàn trang
        // sẽ lẫn cả tiêu đề các bài KHÁC không liên quan (đã gặp thật: "Xem nhiều"/"Cùng chuyên
        // mục" kèm tiêu đề 10 bài linh tinh). Cùng root cho main_content luôn đảm bảo 2 field
        // nhất quán với nhau (headings chắc chắn nằm trong main_content, không lệch phạm vi).
        if ($contentRoot === null) {
            $contentRoot = $this->resolveContentRoot($xpath);
        }

        $mainContent      = $contentRoot ? $this->cleanText($this->extractBlockText($contentRoot)) : '';
        $headings         = $this->extractHeadings($xpath, $contentRoot);
        $keywords         = $this->extractKeywords($xpath, $html, $headings, $title !== '' ? $title : null);
        $languageResolution = $languageOverride !== null
            ? ['language' => $languageOverride, 'mismatch_suspected' => false]
            : $this->resolveLanguage($declaredLanguage, $mainContent);
        $language         = $languageResolution['language'];
        $wordCount        = $this->countWords($mainContent, $language);
        $sourceStructure  = $this->analyzeStructure($xpath, $contentRoot, $headings);
        $contentTypeSignal = $this->classifyContentTypeSignal($title !== '' ? $title : null, $headings, $sourceStructure, $declaredContentType);
        $sections         = $this->buildSections($mainContent, $headings);

        return [
            'title'                       => $title !== '' ? $title : null,
            'meta_description'           => $metaDescription,
            'canonical_url'               => $canonicalUrl,
            'content_category'            => $contentCategory,
            'declared_content_type'       => $declaredContentType,
            'content_type_signal'         => $contentTypeSignal,
            'keywords'                    => $keywords,
            'headings'                    => $headings,
            'sections'                    => $sections,
            'main_content'                => $mainContent,
            'publish_date'                => $publishDate,
            'date_modified'               => $dateModified,
            'author'                      => $author,
            'publisher_name'              => $publisherName,
            'language'                    => $language,
            'language_mismatch_suspected' => $languageResolution['mismatch_suspected'],
            'word_count'                  => $wordCount,
            'meaningful_heading_count'    => count($headings),
            'paywall_suspected'           => $paywallSuspected,
            'custom_selector_matched'     => $customSelectorMatched,
            'source_structure'            => $sourceStructure,
        ];
    }

    /** Ngưỡng "chiếm ưu thế" khi đối chiếu script ký tự — CÙNG giá trị 0.3 đã dùng ở countWords() (§6.1.4), giữ nhất quán 1 quyết định "chiếm ưu thế" cho cả 2 mục đích (đếm từ + đối chiếu ngôn ngữ). */
    private const NON_LATIN_SCRIPT_DOMINANCE_RATIO = 0.3;

    /**
     * `<html lang>` do site khai báo (đọc ở extractLanguage()) CÓ THỂ SAI/lỗi thời so với ngôn ngữ
     * THẬT của main_content — đã gặp thật: site khai `lang="en-US"` (nhiều khả năng do site dùng
     * chung 1 template mặc định tiếng Anh, chỉ đổi nội dung mà quên đổi thẻ <html lang>) nhưng
     * main_content thực tế viết bằng tiếng Thái. Trường `language` sai sẽ khiến người đọc JSON (và
     * chỉ dẫn ngôn ngữ output ở copyPromptForAi() — xem index.blade.php) hiểu nhầm nguồn viết bằng
     * ngôn ngữ khác hẳn thực tế.
     *
     * Chỉ đối chiếu được CHẮC CHẮN với các script KHÔNG dùng chữ Latin (Thái/Trung/Nhật/Hàn — cùng
     * dải Unicode đã dùng ở countWords()) vì đây là tín hiệu ký tự học rõ ràng, không cần mô hình
     * ngôn ngữ học đầy đủ (repo không có package NLP/language-detection chuyên dụng, cùng tinh thần
     * "không thêm dependency mới" đã áp dụng cho việc parse HTML ở đầu file). KHÔNG cố phân biệt
     * các ngôn ngữ cùng dùng chữ Latin (VD "vi" vs "en" vs "fr") — không có tín hiệu ký tự nào đủ
     * tin cậy để làm việc đó bằng rule đơn giản, nên các trường hợp này vẫn tin nguyên `<html lang>`
     * như hành vi cũ (an toàn hơn đoán sai).
     *
     * @return array{language: string, mismatch_suspected: bool}
     */
    private function resolveLanguage(string $declaredLanguage, string $mainContent): array
    {
        $detectedScript = $this->detectNonLatinScript($mainContent);

        if ($detectedScript === null || $detectedScript === $declaredLanguage) {
            return ['language' => $declaredLanguage, 'mismatch_suspected' => false];
        }

        return ['language' => $detectedScript, 'mismatch_suspected' => true];
    }

    /**
     * Trả về mã ngôn ngữ ('th'/'ja'/'ko'/'zh') nếu 1 script không dùng chữ Latin CHIẾM ƯU THẾ
     * trong $text, ngược lại null (không đủ tín hiệu để kết luận — giữ nguyên declared lang).
     * Hiragana/Katakana xét TRƯỚC và tách riêng khỏi Kanji (dải 'zh') vì Kanji dùng CHUNG giữa
     * tiếng Trung và tiếng Nhật — báo tiếng Nhật luôn trộn Kanji + Hiragana/Katakana, nên hễ có
     * đáng kể Hiragana/Katakana là đủ chắc chắn kết luận 'ja' mà không cần so tỉ lệ với Kanji.
     */
    private function detectNonLatinScript(string $text): ?string
    {
        $totalChars = mb_strlen($text);

        if ($totalChars === 0) {
            return null;
        }

        preg_match_all('/[\x{3040}-\x{30FF}]/u', $text, $hiraganaKatakana);
        if (count($hiraganaKatakana[0]) / $totalChars > self::NON_LATIN_SCRIPT_DOMINANCE_RATIO) {
            return 'ja';
        }

        $counts = [];
        foreach (['th' => '\x{0E00}-\x{0E7F}', 'ko' => '\x{AC00}-\x{D7A3}', 'zh' => '\x{4E00}-\x{9FFF}'] as $code => $range) {
            preg_match_all("/[{$range}]/u", $text, $m);
            $counts[$code] = count($m[0]);
        }

        arsort($counts);
        $topCode  = array_key_first($counts);
        $topCount = $counts[$topCode];

        return ($topCount / $totalChars) > self::NON_LATIN_SCRIPT_DOMINANCE_RATIO ? $topCode : null;
    }

    /**
     * spec/CoreIdeaExtractor.md §5.2/§7 (v1.5) — tín hiệu cấu trúc THÔ của nguồn, tham khảo
     * https://kime.ai/blog/structure-content-for-llm-extraction (bảng/danh sách số/heading dạng
     * câu hỏi được AI answer engine trích dẫn nhiều hơn văn xuôi). Quét bảng/danh sách TRONG
     * PHẠM VI $contentRoot (không quét toàn trang) — cùng lý do đã áp dụng cho headings/
     * main_content (§ resolveContentRoot() docblock): tránh đếm nhầm bảng/danh sách nằm trong
     * sidebar/widget "bài liên quan" không thuộc nội dung chính.
     */
    private function analyzeStructure(\DOMXPath $xpath, ?\DOMNode $contentRoot, array $headings): SourceStructureData
    {
        $hasTables        = false;
        $hasNumberedLists = false;
        $hasBulletLists   = false;

        if ($contentRoot !== null) {
            $hasTables        = $xpath->query('.//table', $contentRoot)->length > 0;
            $hasNumberedLists = $xpath->query('.//ol', $contentRoot)->length > 0;
            $hasBulletLists   = $xpath->query('.//ul', $contentRoot)->length > 0;
        }

        $questionHeadings = array_filter(
            $headings,
            static fn (HeadingData $h) => str_ends_with(trim($h->text), '?')
        );

        return new SourceStructureData(
            has_tables: $hasTables,
            has_numbered_lists: $hasNumberedLists,
            has_bullet_lists: $hasBulletLists,
            question_heading_ratio: $headings === [] ? 0.0 : round(count($questionHeadings) / count($headings), 2),
        );
    }

    /** `declared_content_type` khớp 1 trong các cụm này (sau khi lowercase) coi là trang sản phẩm. */
    private const PRODUCT_CONTENT_TYPE_MARKERS = ['product', 'sản phẩm'];

    /**
     * Nhãn phân loại content type suy ra bằng RULE THUẦN (pattern heading/title) — KHÔNG phải AI
     * phân tích ngữ nghĩa, chỉ là tín hiệu tham khảo THÔ (có thể sai), khác hẳn
     * `declared_content_type` (do chính site tự khai qua og:type/JSON-LD, đáng tin hơn nhưng
     * thường chỉ "article"/"website"/"product", không chi tiết tới mức listicle/how-to).
     *
     * `product`/`product_faq` xét TRƯỚC listicle/how_to/review/faq — dựa trên `declaredContentType`
     * (dữ liệu publisher TỰ khai báo, đáng tin hơn hẳn heuristic heading/title đoán mò ở dưới), nên
     * ưu tiên cao hơn: 1 trang sản phẩm có mục "1. Công dụng, 2. Đối tượng..." dễ bị heuristic
     * listicle bên dưới khớp nhầm trước nếu không xét product trước.`product_faq` (thay vì gắn 2
     * nhãn) khi trang sản phẩm ĐỒNG THỜI có tỉ lệ heading dạng câu hỏi cao — vẫn giữ field đơn giản
     * (string, không phải mảng) như các nhãn khác, chỉ thêm 1 giá trị enum mới cho tổ hợp này.
     *
     * Các nhãn còn lại ưu tiên theo thứ tự listicle > how_to > review_comparison > faq — 1 bài có
     * thể mang nhiều đặc điểm cùng lúc (VD "10 cách..." vừa là listicle vừa là how-to), chỉ trả về
     * nhãn ĐẦU TIÊN khớp thay vì gắn nhiều nhãn.
     *
     * Trả về null nếu không khớp rule nào rõ ràng — KHÔNG suy đoán ép buộc (an toàn hơn 1 nhãn
     * sai chắc chắn gây hiểu lầm cho người đọc JSON này).
     */
    private function classifyContentTypeSignal(?string $title, array $headings, SourceStructureData $sourceStructure, ?string $declaredContentType): ?string
    {
        $declaredContentTypeLower = mb_strtolower(trim((string) $declaredContentType));
        $isFaqHeavy = $headings !== [] && $sourceStructure->question_heading_ratio >= self::FAQ_QUESTION_RATIO_THRESHOLD;

        foreach (self::PRODUCT_CONTENT_TYPE_MARKERS as $marker) {
            if (str_contains($declaredContentTypeLower, $marker)) {
                return $isFaqHeavy ? 'product_faq' : 'product';
            }
        }

        $titleLower = mb_strtolower(trim((string) $title));

        // Listicle: title bắt đầu bằng số (VD "10 cách trị ho cho bé...") HOẶC đa số heading tự
        // đánh số (VD "1. Nguyên nhân", "2. Triệu chứng") HOẶC trang dùng danh sách đánh số thật
        // (`<ol>`, xem `has_numbered_lists`) — bắt thêm trường hợp bài liệt kê N mục (VD "5 dưỡng
        // chất thiết yếu") nhưng KHÔNG đánh số ở text heading/title (mỗi mục 1 heading tên riêng,
        // VD "DHA", "Omega 3"...) mà đánh số bằng <ol> thật trong main_content — heading-ratio/
        // title-number đơn thuần bỏ sót trường hợp này.
        $titleStartsWithNumber = preg_match('/^\d+\s+\S/u', $titleLower) === 1;

        $numberedHeadings = array_filter(
            $headings,
            static fn (HeadingData $h) => preg_match('/^\d+[.\)]?\s+\S/u', trim($h->text)) === 1
        );
        $numberedHeadingRatio = $headings === [] ? 0.0 : count($numberedHeadings) / count($headings);

        if ($titleStartsWithNumber || $numberedHeadingRatio >= self::LISTICLE_HEADING_RATIO_THRESHOLD || $sourceStructure->has_numbered_lists) {
            return 'listicle';
        }

        foreach (self::HOWTO_TITLE_KEYWORDS as $keyword) {
            if (str_contains($titleLower, $keyword)) {
                return 'how_to';
            }
        }

        foreach (self::REVIEW_TITLE_KEYWORDS as $keyword) {
            if (str_contains($titleLower, $keyword)) {
                return 'review_comparison';
            }
        }

        if ($isFaqHeavy) {
            return 'faq';
        }

        // Fallback THẤP ĐỘ TIN CẬY (khác các nhãn trên — đều có tín hiệu cụ thể): bài có NHIỀU
        // heading có ý nghĩa (đã nhiều mục/phần rõ ràng) nhưng không khớp rule cụ thể nào ở trên
        // (không phải sản phẩm/listicle/how-to/review/faq) — VD bài kiến thức/y tế/nuôi dạy con
        // nhiều phần (dinh dưỡng, dấu hiệu, cách chăm sóc...) không rơi gọn vào 1 khuôn cụ thể nào.
        // Vẫn hơn `null` (không gợi ý gì) dù độ chắc chắn thấp hơn các nhãn trên.
        if (count($headings) >= self::EDUCATIONAL_MIN_HEADINGS) {
            return 'educational';
        }

        return null;
    }

    /** Heading dài hơn ngưỡng này (số TỪ, tách bằng khoảng trắng) coi là 1 câu mô tả, KHÔNG phải tên 1 chủ đề/thực thể cụ thể — không đáng làm keyword. */
    private const HEADING_KEYWORD_MAX_WORDS = 4;

    /**
     * Heading NGẮN (VD "DHA", "Omega 3", "Sắt") tự nó CHÍNH LÀ 1 chủ đề/thực thể cụ thể của bài —
     * đáng tin hơn hẳn meta[name=keywords]/JSON-LD (thường chỉ có tên shop/brand chung chung,
     * xem phản hồi thực tế: bài về dinh dưỡng trẻ em nhưng keywords toàn tên brand sữa như "Hi-Q"/
     * "S-Mom Club" — không phản ánh ĐÚNG chủ đề bài đang nói về gì). Bỏ qua: heading dạng câu hỏi
     * (kết thúc bằng "?", đã là FAQ — 1 câu hỏi không phải "tên chủ đề"), heading trùng chính
     * `$title` (không phải thông tin mới), và heading DÀI hơn HEADING_KEYWORD_MAX_WORDS từ (câu
     * mô tả, không phải nhãn chủ đề ngắn gọn). Strip số thứ tự đầu (VD "1. DHA" → "DHA", xem
     * extractPseudoHeadings()) vì số thứ tự không phải 1 phần ý nghĩa của keyword.
     *
     * @param HeadingData[] $headings
     * @return string[]
     */
    private function extractHeadingKeywords(array $headings, ?string $title): array
    {
        $titleNormalized = mb_strtolower(trim((string) $title));
        $keywords        = [];

        foreach ($headings as $heading) {
            $text = trim(preg_replace('/^\d+[.\)]\s*/u', '', $heading->text) ?? $heading->text);

            if ($text === '' || str_ends_with($text, '?') || mb_strtolower($text) === $titleNormalized) {
                continue;
            }

            $wordCount = count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY));

            if ($wordCount > self::HEADING_KEYWORD_MAX_WORDS) {
                continue;
            }

            $keywords[] = $text;
        }

        return $keywords;
    }

    /**
     * meta[name="keywords"] chuẩn HTML là 1 chuỗi các từ khoá phân tách bởi dấu phẩy — tách ra
     * mảng string, bỏ khoảng trắng thừa và phần tử rỗng (VD content="a, b,, c" → ["a","b","c"]).
     * Nhiều site hiện đại đã bỏ hẳn meta keywords (deprecated cho SEO) nhưng vẫn khai qua
     * `article:tag` (OpenGraph, có thể lặp lại nhiều thẻ) hoặc `keywords` trong JSON-LD — gộp cả
     * 3 nguồn, loại trùng lặp (case-insensitive) để không mất tín hiệu chỉ vì site không dùng
     * đúng tag "chuẩn" mà spec ban đầu nhắm tới.
     *
     * @param HeadingData[] $headings
     * @return string[]
     */
    private function extractKeywords(\DOMXPath $xpath, string $html, array $headings, ?string $title): array
    {
        $metaKeywords = [];
        $content      = $this->metaContent($xpath, "//meta[@name='keywords']");

        if ($content !== null) {
            $parts        = array_map('trim', explode(',', $content));
            $metaKeywords = array_values(array_filter($parts, static fn (string $p) => $p !== ''));
        }

        // Thứ tự ưu tiên: (1) heading NGẮN — chủ đề THẬT của CHÍNH bài này (xem
        // extractHeadingKeywords()); (2) tên sản phẩm/thương hiệu (JSON-LD Product) — vẫn cụ thể,
        // do site tự khai; (3)-(4) meta keywords/article:tag/JSON-LD keywords chung — phản hồi
        // thực tế cho thấy nhóm này thường chỉ là tên shop/brand marketing, ít phản ánh ĐÚNG chủ
        // đề nội dung, nên xếp SAU cùng thay vì đứng đầu như trước.
        $merged = array_merge(
            $this->extractHeadingKeywords($headings, $title),
            $this->extractJsonLdProductKeywords($html),
            $metaKeywords,
            $this->extractArticleTags($xpath),
            $this->extractJsonLdKeywords($html),
        );

        $seen   = [];
        $unique = [];
        foreach ($merged as $keyword) {
            $normalized = mb_strtolower($keyword);
            if (isset($seen[$normalized])) {
                continue;
            }
            $seen[$normalized] = true;
            $unique[]          = $keyword;
        }

        return $unique;
    }

    /**
     * Tên sản phẩm + thương hiệu do CHÍNH site khai báo qua JSON-LD Product schema — chỉ đọc khi
     * `@type` của block đó khớp "product" (xem docblock `fromJsonLd()`), tránh lẫn `name` của
     * block Organization/BreadcrumbList khác nằm cùng trang.
     *
     * @return string[]
     */
    private function extractJsonLdProductKeywords(string $html): array
    {
        $name  = $this->fromJsonLd($html, 'name', 'product');
        $brand = $this->fromJsonLd($html, 'brand', 'product');

        $brandName = is_array($brand) ? ($brand['name'] ?? null) : $brand;

        return array_values(array_filter(
            [is_string($name) ? trim($name) : null, is_string($brandName) ? trim($brandName) : null],
            static fn (?string $v) => $v !== null && $v !== '',
        ));
    }

    /** @return string[] */
    private function extractArticleTags(\DOMXPath $xpath): array
    {
        $tags = [];

        foreach ($xpath->query("//meta[@property='article:tag']") as $node) {
            if (! $node instanceof \DOMElement) {
                continue;
            }

            $value = trim($node->getAttribute('content'));

            if ($value !== '') {
                $tags[] = $value;
            }
        }

        return $tags;
    }

    /** @return string[] */
    private function extractJsonLdKeywords(string $html): array
    {
        $keywords = $this->fromJsonLd($html, 'keywords');

        if (is_array($keywords)) {
            $trimmed = array_map(static fn ($k) => trim((string) $k), $keywords);

            return array_values(array_filter($trimmed, static fn (string $k) => $k !== ''));
        }

        if (is_string($keywords) && trim($keywords) !== '') {
            $parts = array_map('trim', explode(',', $keywords));

            return array_values(array_filter($parts, static fn (string $p) => $p !== ''));
        }

        return [];
    }

    /** Do chính trang khai báo qua `<link rel="canonical">` — bản gốc/chuẩn của URL, có thể khác `final_url` (redirect) khi trang là bản syndicate/AMP trỏ về bài gốc. */
    private function extractCanonicalUrl(\DOMXPath $xpath): ?string
    {
        $node = $xpath->query("//link[@rel='canonical']")->item(0);

        if (! $node instanceof \DOMElement) {
            return null;
        }

        $href = trim($node->getAttribute('href'));

        return $href !== '' ? $href : null;
    }

    /** Chủ đề/chuyên mục do chính site khai báo (`article:section`/`articleSection`) — KHÔNG phải suy đoán, đối chiếu trực tiếp được với Category Content Foundation. */
    private function extractContentCategory(\DOMXPath $xpath, string $html): ?string
    {
        $meta = $this->metaContent($xpath, "//meta[@property='article:section']");

        if ($meta) {
            return $meta;
        }

        $value = $this->fromJsonLd($html, 'articleSection');

        if (is_array($value)) {
            $first = $value[0] ?? null;

            return is_string($first) && trim($first) !== '' ? trim($first) : null;
        }

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * Loại nội dung do chính site khai báo (VD "article", "website", "product") — an toàn hơn tự
     * đoán bằng heuristic vì là dữ liệu publisher tự công bố. Ưu tiên `og:type` (chuẩn OpenGraph,
     * đã dùng từ trước); nhiều trang sản phẩm (VD WooCommerce/Shopify) chỉ khai schema.org qua
     * JSON-LD (`@type: "Product"`) mà bỏ hẳn og:type, nên fallback đọc `@type` JSON-LD khi
     * og:type vắng mặt — cùng nguồn tự khai báo, chỉ khác vị trí trong HTML.
     */
    private function extractDeclaredContentType(\DOMXPath $xpath, string $html): ?string
    {
        $ogType = $this->metaContent($xpath, "//meta[@property='og:type']");

        if ($ogType !== null) {
            return $ogType;
        }

        $jsonLdType = $this->fromJsonLd($html, '@type');
        $first      = is_array($jsonLdType) ? ($jsonLdType[0] ?? null) : $jsonLdType;

        return is_string($first) && trim($first) !== '' ? trim($first) : null;
    }

    /** Thời điểm cập nhật GẦN NHẤT (`article:modified_time`/`dateModified`) — khác `publish_date` (thời điểm đăng lần đầu), tín hiệu độ mới khi so sánh nhiều nguồn. */
    private function extractDateModified(\DOMXPath $xpath, string $html): ?string
    {
        $meta = $this->metaContent($xpath, "//meta[@property='article:modified_time']");

        if ($meta) {
            return $meta;
        }

        $value = $this->fromJsonLd($html, 'dateModified');

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /** Tên đơn vị xuất bản do chính site khai (`og:site_name`/`publisher.name`) — khác `domain` (hostname thô), giúp đánh giá độ uy tín nguồn. */
    private function extractPublisherName(\DOMXPath $xpath, string $html): ?string
    {
        $meta = $this->metaContent($xpath, "//meta[@property='og:site_name']");

        if ($meta) {
            return $meta;
        }

        $publisher = $this->fromJsonLd($html, 'publisher');

        if (is_array($publisher)) {
            $name = $publisher['name'] ?? null;

            return is_string($name) && trim($name) !== '' ? trim($name) : null;
        }

        return is_string($publisher) && trim($publisher) !== '' ? trim($publisher) : null;
    }

    /**
     * Chuyển selector đơn giản do người dùng nhập (id/class, có thể kèm tag) thành XPath rồi tìm
     * node đầu tiên khớp. Chỉ hỗ trợ cú pháp CSS selector cơ bản nhất — ĐÚNG PHẠM VI yêu cầu
     * (chọn khối theo id/class, kiểu "class=\"detail-content\""), không phải 1 CSS-selector-engine
     * đầy đủ (không hỗ trợ tổ hợp combinator/pseudo-class — nếu cần phức tạp hơn, thuật toán tự
     * động resolveContentRoot() vẫn là fallback an toàn).
     *
     * Cú pháp hỗ trợ (có thể liệt kê nhiều, phân tách bởi dấu phẩy, thử lần lượt theo thứ tự,
     * dùng khối đầu tiên khớp): ".class", "#id", "tag.class", "tag#id", chỉ "tag", hoặc GHÉP NHIỀU
     * class/id liền nhau (VD ".col-md-12.content-full", "div#main.content-full") — khớp phần tử có
     * ĐỦ TẤT CẢ class/id liệt kê (điều kiện AND, giống CSS thật), không phải chỉ khớp 1 trong số
     * đó — cần thiết khi 1 class riêng lẻ (VD ".content-full") khớp NHẦM nhiều khối khác trên trang
     * (VD widget/sidebar cũng gắn class đó), còn ghép thêm class layout đi kèm (VD ".col-md-12") mới
     * xác định ĐÚNG DUY NHẤT 1 khối.
     */
    private function resolveContentRootFromSelector(\DOMXPath $xpath, string $selector): ?\DOMNode
    {
        foreach (explode(',', $selector) as $part) {
            $query = $this->simpleSelectorToXPath(trim($part));

            if ($query === null) {
                continue;
            }

            $node = $xpath->query($query)?->item(0);

            if ($node instanceof \DOMNode) {
                return $node;
            }
        }

        return null;
    }

    private function simpleSelectorToXPath(string $part): ?string
    {
        if ($part === '') {
            return null;
        }

        // Tên tag thuần, không class/id (VD "article") — không đi qua nhánh ghép qualifier bên dưới.
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $part) === 1) {
            return "//{$part}";
        }

        // "tag" (tùy chọn) + 1 hoặc nhiều ".class"/"#id" GHÉP LIỀN NHAU, không dấu cách (đúng ngữ
        // nghĩa CSS compound selector — mọi qualifier liệt kê đều phải khớp CÙNG 1 phần tử).
        if (! preg_match('/^([a-zA-Z][a-zA-Z0-9]*)?((?:[.#][a-zA-Z0-9_-]+)+)$/', $part, $m)) {
            return null;
        }

        [, $tag, $qualifiers] = $m;
        $tag = $tag !== '' ? $tag : '*';

        preg_match_all('/([.#])([a-zA-Z0-9_-]+)/', $qualifiers, $matches, PREG_SET_ORDER);

        $conditions = array_map(
            static fn (array $q) => $q[1] === '#'
                ? "@id='{$q[2]}'"
                : "contains(concat(' ', normalize-space(@class), ' '), ' {$q[2]} ')",
            $matches,
        );

        return "//{$tag}[".implode(' and ', $conditions).']';
    }

    private function extractTitleFromHead(\DOMXPath $xpath): string
    {
        $titleNode = $xpath->query('//title')->item(0);
        if ($titleNode && trim($titleNode->textContent) !== '') {
            return trim($titleNode->textContent);
        }

        $ogTitle = $this->metaContent($xpath, "//meta[@property='og:title']");
        if ($ogTitle) {
            return $ogTitle;
        }

        return '';
    }

    private function firstHeadingText(\DOMXPath $xpath, string $tag): string
    {
        $node = $xpath->query("//{$tag}")->item(0);

        return $node ? trim($node->textContent) : '';
    }

    private function extractMetaDescription(\DOMXPath $xpath): ?string
    {
        return $this->metaContent($xpath, "//meta[@name='description']")
            ?? $this->metaContent($xpath, "//meta[@property='og:description']");
    }

    private function metaContent(\DOMXPath $xpath, string $query): ?string
    {
        $node = $xpath->query($query)->item(0);

        if (! $node instanceof \DOMElement) {
            return null;
        }

        $content = trim($node->getAttribute('content'));

        return $content !== '' ? $content : null;
    }

    private function extractLanguage(\DOMXPath $xpath): string
    {
        $html = $xpath->query('//html')->item(0);

        if (! $html instanceof \DOMElement) {
            return 'unknown';
        }

        $lang = trim($html->getAttribute('lang'));

        if ($lang === '') {
            return 'unknown';
        }

        return strtolower(explode('-', $lang)[0]);
    }

    /**
     * Xoá node script/style/nav/footer/aside + node có class/id khớp NOISE_KEYWORDS.
     *
     * KHÔNG xoá `<header>` blanket — HTML5 `<header>` cũng hợp lệ dùng làm header CỦA 1
     * section/article (VD Wikipedia bọc chính <h1> tiêu đề bài trong <header>), xoá blanket sẽ
     * mất luôn tiêu đề bài viết (đã gặp thật khi test với trang Wikipedia thật — main_content/
     * headings ra rỗng vì h1 title nằm trong <header>).
     *
     * KHÔNG áp NOISE_KEYWORDS lên <html>/<body> — nhiều site gắn rất nhiều class feature-flag/
     * utility lên 2 tag này (VD Wikipedia: class="... vector-feature-navigation-update-disabled
     * ... vector-feature-language-in-main-menu-disabled ...") — bare-substring 'nav'/'menu' khớp
     * NHẦM các class không liên quan gì đến menu/nav thật, và vì <html>/<body> là node GỐC, xoá
     * nhầm nó sẽ xoá theo CẢ TOÀN BỘ document (đã gặp thật: 100% nội dung biến mất). Chỉ xoá
     * DESCENDANT của <body>, không bao giờ xoá chính <html>/<body>.
     */
    /**
     * Ngưỡng an toàn khi xoá noise theo NOISE_KEYWORDS (substring match trên class/id, không
     * phải nghĩa ngữ nghĩa) — nếu node khớp lại chiếm từ tỉ lệ này trở lên tổng chữ của <body>,
     * KHÔNG xoá (xem lý do trong docblock stripNoise()).
     */
    private const NOISE_MAX_BODY_TEXT_RATIO = 0.4;

    /**
     * Xoá node script/style/nav/footer/aside + node có class/id khớp NOISE_KEYWORDS.
     *
     * Với NOISE_KEYWORDS: chỉ xoá khi node đó chiếm PHẦN NHỎ tổng chữ của <body> — đã gặp thật
     * với vnexpress.net: toàn bộ khối nội dung chính (<main><article class="fck_detail">...)
     * nằm trong wrapper layout <div class="sidebar-1"> (tên class do site tự đặt cho cột bố cục,
     * KHÔNG phải sidebar/menu phụ thật) — class match substring "sidebar" (sau khi translate '-'
     * thành khoảng trắng: "sidebar-1" → "sidebar 1") nên bị NOISE_KEYWORDS bắt nhầm và xoá mất
     * toàn bộ nội dung bài viết thật (3857/4365 ký tự = 88% tổng <body>). Sidebar/nav/footer THẬT
     * hầu như luôn chỉ chiếm phần nhỏ trang, nên dùng tỉ lệ làm van an toàn: node càng LỚN so với
     * tổng trang càng khó là noise thật, bất kể tên class trùng khớp thế nào.
     */
    private function stripNoise(\DOMXPath $xpath): void
    {
        foreach (['script', 'style', 'nav', 'footer', 'aside'] as $tag) {
            foreach (iterator_to_array($xpath->query("//{$tag}")) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        $bodyNode = $xpath->query('//body')->item(0);
        $totalLen = $bodyNode ? mb_strlen($this->cleanText($bodyNode->textContent)) : 0;
        $maxNoiseLen = (int) round($totalLen * self::NOISE_MAX_BODY_TEXT_RATIO);

        $conditions = array_map(
            fn (string $kw) => "contains(translate(concat(' ', normalize-space(@class), ' ', normalize-space(@id), ' '), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ-_', 'abcdefghijklmnopqrstuvwxyz  '), ' {$kw} ')",
            self::NOISE_KEYWORDS
        );

        // Loại chính h1/h2/h3 khỏi diện bị xoá theo NOISE_KEYWORDS — đã gặp thật với
        // orami.co.id: heading thật <h2 id="ide-menu-makanan-bayi-1-tahun"> (id tự sinh từ chính
        // text heading, kiểu slug cho anchor/TOC) chứa substring "menu" ("ide-MENU-makanan...")
        // nên bị bắt nhầm là menu điều hướng và xoá mất cả câu heading. id/class kiểu slug-từ-
        // chính-nội-dung rất dễ trùng ngẫu nhiên với NOISE_KEYWORDS (menu, modal, cookie... đều là
        // từ thường gặp trong văn bản thường, không riêng gì UI điều hướng) — trong khi noise
        // THẬT (nav/menu/popup...) hầu như luôn là 1 khối container (div/ul), không phải bản thân
        // 1 thẻ heading. Container bọc ngoài heading (nếu đúng là noise thật) vẫn bị xoá bình
        // thường qua chính node cha của nó.
        $query = '//*[not(self::html) and not(self::body) and not(self::h1) and not(self::h2) and not(self::h3) and ('.implode(' or ', $conditions).')]';

        foreach (iterator_to_array($xpath->query($query)) as $node) {
            if ($totalLen > 0 && mb_strlen($this->cleanText($node->textContent)) > $maxNoiseLen) {
                continue;
            }

            $node->parentNode?->removeChild($node);
        }
    }

    /** @return HeadingData[] */
    private function extractHeadings(\DOMXPath $xpath, ?\DOMNode $contextNode): array
    {
        $query    = './/h1 | .//h2 | .//h3';
        $nodes    = $contextNode ? $xpath->query($query, $contextNode) : $xpath->query('//h1 | //h2 | //h3');
        $headings = [];

        foreach ($nodes as $node) {
            $text = trim(preg_replace('/\s+/', ' ', $node->textContent) ?? '');

            if ($text === '' || $this->isDecorativeHeading($text)) {
                continue;
            }

            $headings[] = new HeadingData(level: (int) substr($node->nodeName, 1), text: $text);
        }

        // Fallback CHỈ khi không có <h1>-<h3> thật nào — nhiều trang (đặc biệt trang sản phẩm)
        // dùng đoạn/danh sách đánh số hoặc bôi đậm làm "tiêu đề mục" thay vì thẻ heading chuẩn
        // (VD "<p><strong>1. CÔNG DỤNG</strong></p>"), khiến headings:[] dù nội dung có cấu trúc
        // rõ ràng — xem extractPseudoHeadings(). Không chạy song song với heading thật để không
        // đổi hành vi bài viết bình thường đã có <h1>-<h3> chuẩn.
        if ($headings === [] && $contextNode !== null) {
            $headings = $this->extractPseudoHeadings($xpath, $contextNode);
        }

        return $headings;
    }

    /** Nhãn mục giả định (numbered/bold-as-heading) không được dài hơn ngưỡng này — phân biệt với câu văn dài vô tình đánh số/bôi đậm. */
    private const PSEUDO_HEADING_MAX_CHARS = 80;

    /**
     * Fallback khi trang không dùng <h1>-<h3> thật cho các mục con (đã gặp thật qua phản hồi
     * người dùng: trang sản phẩm cấu trúc rõ ràng "1. CÔNG DỤNG", "2. ĐỐI TƯỢNG SỬ DỤNG"... bằng
     * <p><strong>...</strong></p> thay vì <h2>, khiến headings:[] dù nội dung không hề thiếu cấu
     * trúc — 1 lỗi extraction thật, không phải nội dung nguồn thiếu tổ chức).
     *
     * 2 dạng nhận diện, cả 2 giới hạn PSEUDO_HEADING_MAX_CHARS (nhãn mục ngắn, không phải câu văn
     * dài), quét theo ĐÚNG thứ tự xuất hiện trong tài liệu (union XPath trả node-set theo document
     * order):
     * 1. `<p>`/`<li>` mà TOÀN BỘ nội dung khớp pattern đánh số (VD "1. Công dụng") — không phải
     *    câu văn thường chỉ TÌNH CỜ bắt đầu bằng số.
     * 2. `<strong>`/`<b>` mà text trùng khớp HOÀN TOÀN với text của thẻ cha (nghĩa là bôi đậm
     *    chiếm TRỌN cả dòng/đoạn) — phân biệt với bôi đậm 1 cụm từ để nhấn mạnh GIỮA câu văn dài
     *    (trường hợp đó `parentNode->textContent` sẽ dài hơn hẳn text của riêng thẻ bôi đậm).
     *
     * Gán level 2 cho mọi pseudo-heading (không phân biệt được cấp bậc thật như heading chuẩn).
     */
    private function extractPseudoHeadings(\DOMXPath $xpath, \DOMNode $contextNode): array
    {
        $headings  = [];
        $seenTexts = [];

        foreach ($xpath->query('.//p | .//li | .//strong | .//b', $contextNode) as $node) {
            $text = trim(preg_replace('/\s+/', ' ', $node->textContent) ?? '');

            if ($text === '' || mb_strlen($text) > self::PSEUDO_HEADING_MAX_CHARS
                || $this->isDecorativeHeading($text) || isset($seenTexts[$text])) {
                continue;
            }

            $isNumbered  = preg_match('/^\d+[.\)]\s*\S/u', $text) === 1;
            $isBoldLabel = in_array($node->nodeName, ['strong', 'b'], true)
                && $node->parentNode
                && trim(preg_replace('/\s+/', ' ', $node->parentNode->textContent) ?? '') === $text;

            if (! $isNumbered && ! $isBoldLabel) {
                continue;
            }

            $seenTexts[$text] = true;
            $headings[]       = new HeadingData(level: 2, text: $text);
        }

        return $headings;
    }

    private function isDecorativeHeading(string $text): bool
    {
        if (mb_strlen($text) <= 2) {
            return true;
        }

        $lower = mb_strtolower($text);

        foreach (self::DECORATIVE_HEADING_PHRASES as $phrase) {
            if ($lower === $phrase || mb_strlen($text) <= mb_strlen($phrase) + 3 && str_contains($lower, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /** Ứng viên match quá vụn vặt (VD 1 <div class="post-count">Bình luận</div>) không đáng xét. */
    private const MIN_CANDIDATE_CHARS = 100;

    /**
     * Nếu 1 ứng viên con-cháu đã "gói gọn" từ tỉ lệ này trở lên độ dài của ứng viên cha, coi cha
     * là wrapper thừa (loại cha, giữ con cụ thể hơn). Nếu con chỉ là 1 mảnh nhỏ bên trong cha
     * (VD embed/widget lồng trong khối nội dung to hơn nhiều), KHÔNG loại cha vì con không phản
     * ánh đủ nội dung — xem lý do chi tiết ở docblock resolveContentRoot().
     */
    private const ANCESTOR_REDUNDANCY_RATIO = 0.6;

    /**
     * Chọn 1 node GỐC duy nhất cho main_content + headings — dùng chung, không xử lý riêng lẻ
     * (tránh 2 field lệch phạm vi nhau).
     *
     * Thuật toán: gộp TẤT CẢ ứng viên (mọi <article> + mọi node khớp MAIN_CONTENT_SELECTORS) vào
     * 1 pool, loại các ứng viên là "wrapper thừa" (xem ANCESTOR_REDUNDANCY_RATIO), rồi lấy ứng
     * viên còn lại dài nhất.
     *
     * KHÔNG được chọn theo kiểu "ứng viên dài nhất trong TOÀN pool" (kể cả không short-circuit ở
     * <article>) — đã gặp thật với nhandan.vn: <div class="site-body"> (bọc CẢ bài viết LẪN
     * widget "Có thể bạn quan tâm" gồm hàng chục tiêu đề bài khác) luôn dài hơn <div
     * class="article__body"> (nội dung bài viết thật) — chọn theo độ dài thô sẽ lấy nhầm
     * site-body, kéo theo hàng chục heading của các bài không liên quan bị lẫn vào.
     *
     * NHƯNG cũng KHÔNG được loại bỏ TRIỆT ĐỂ mọi ứng viên là tổ tiên của bất kỳ ứng viên nào khác
     * (cách làm ở bản trước) — đã gặp thật với vietnamnet.vn: khối nội dung thật
     * (`content-detail`, 5647 ký tự) chứa vài đoạn embed/widget nhỏ lồng bên trong (các
     * `<article class="vnn-bg-sample...">`, chỉ ~200-460 ký tự — kiểu block nội dung nhúng của
     * CMS, không phải "câu chuyện liên quan"). Loại triệt để mọi tổ tiên sẽ loại luôn
     * `content-detail`, cuối cùng chỉ còn sót lại đúng 1 embed bé xíu (460 ký tự) làm
     * main_content — mất gần hết bài viết thật. Nên chỉ loại ứng viên cha khi có 1 ứng viên con
     * chiếm ĐA SỐ nội dung của cha (tỉ lệ >= ANCESTOR_REDUNDANCY_RATIO, nghĩa là cha chỉ là
     * wrapper mỏng bọc ngoài con) — còn con chỉ là mảnh nhỏ lồng bên trong (như trường hợp
     * vietnamnet) thì giữ nguyên cha vì cha mới là nội dung đầy đủ.
     */
    private function resolveContentRoot(\DOMXPath $xpath): ?\DOMNode
    {
        $candidates = [];

        foreach ($xpath->query('//article') as $node) {
            $candidates[] = [$node, mb_strlen($this->cleanText($node->textContent))];
        }

        foreach (self::MAIN_CONTENT_SELECTORS as $selector) {
            foreach ($xpath->query($selector) as $node) {
                $candidates[] = [$node, mb_strlen($this->cleanText($node->textContent))];
            }
        }

        if ($candidates === []) {
            // Fallback cuối cùng — toàn bộ <body> sau khi đã loại noise.
            return $xpath->query('//body')->item(0);
        }

        $substantial = array_values(array_filter($candidates, fn (array $c) => $c[1] >= self::MIN_CANDIDATE_CHARS));
        $pool        = $substantial !== [] ? $substantial : $candidates;

        $notRedundant = array_values(array_filter(
            $pool,
            fn (array $c) => ! $this->isRedundantAncestor($c[0], $c[1], $pool)
        ));

        $final = $notRedundant !== [] ? $notRedundant : $pool;

        usort($final, fn (array $a, array $b) => $b[1] <=> $a[1]);

        return $final[0][0];
    }

    /** @param array<array{0: \DOMNode, 1: int}> $pool */
    private function isRedundantAncestor(\DOMNode $node, int $len, array $pool): bool
    {
        foreach ($pool as [$candidateNode, $candidateLen]) {
            if ($candidateNode !== $node
                && $this->isAncestorOf($node, $candidateNode)
                && $len > 0
                && ($candidateLen / $len) >= self::ANCESTOR_REDUNDANCY_RATIO
            ) {
                return true;
            }
        }

        return false;
    }

    private function isAncestorOf(\DOMNode $ancestor, \DOMNode $node): bool
    {
        for ($current = $node->parentNode; $current !== null; $current = $current->parentNode) {
            if ($current === $ancestor) {
                return true;
            }
        }

        return false;
    }

    /**
     * Nối text theo cấu trúc DOM, tự chèn ranh giới đoạn cho thẻ block (xem BLOCK_TAGS) và
     * newline đơn cho `<br>` — thay cho `$node->textContent` (không phân biệt ranh giới thẻ,
     * gây dính chữ ở HTML minify).
     */
    private function extractBlockText(\DOMNode $node): string
    {
        if ($node instanceof \DOMText) {
            return $node->nodeValue ?? '';
        }

        if ($node->nodeName === 'br') {
            return "\n";
        }

        if (! $node instanceof \DOMElement && ! $node instanceof \DOMDocument) {
            return '';
        }

        $text = '';
        foreach ($node->childNodes as $child) {
            $text .= $this->extractBlockText($child);
        }

        return in_array($node->nodeName, self::BLOCK_TAGS, true) ? "\n\n{$text}\n\n" : $text;
    }

    private function cleanText(string $text): string
    {
        // "\r\n"/"\r" đơn lẻ (line ending kiểu Windows, đã gặp thật khi site nguồn dùng text node
        // CRLF) — chuẩn hoá về "\n" TRƯỚC các bước dưới, nếu không "\r" sót lại sẽ hiện ra thành
        // ký tự lạ/dòng trống bất thường trong main_content/sections đã trích.
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]*\n[ \t]*/', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * v1.14 — phản hồi thực tế: module tên "CoreIdeaExtractor" nhưng vẫn đẩy gần như nguyên văn
     * `main_content` cho AI tự tách đoạn theo heading, tốn công AI + tốn token. Cân nhắc phương
     * án ĐẦY ĐỦ hơn (`key_points[]`/`claims[]`/`nutrients_benefits[]`... tự sinh bằng rule PHP)
     * nhưng đã KHÔNG chọn — về bản chất là tự động hoá Layer 2 (diễn giải Ý NGHĨA nội dung) bằng
     * heuristic thay vì AI, đi ngược §12.3 ("không tự động hoá Layer 2"), rủi ro suy diễn sai cao
     * hơn hẳn lợi ích với nội dung sức khoẻ/dinh dưỡng. `sections[]` là phương án AN TOÀN: THUẦN
     * TỔ CHỨC LẠI `main_content` đã có theo ranh giới heading (giữ NGUYÊN VĂN, không diễn giải/gắn
     * nhãn ý nghĩa nào) — giúp AI khỏi phải tự tách đoạn từ 1 khối main_content phẳng, không thêm
     * rủi ro hallucinate vì không suy luận gì cả, chỉ là REORGANIZE dữ liệu đã trích được.
     *
     * Cách khớp: `$headings` (thật hoặc pseudo, xem extractHeadings()/extractPseudoHeadings()) đều
     * là các NODE BLOCK-LEVEL đã nằm sẵn trong `$mainContent` dưới dạng 1 "đoạn" riêng (mọi
     * h1-h6/p/li đều thuộc BLOCK_TAGS, luôn được bọc `\n\n...\n\n` khi extractBlockText() nối chuỗi)
     * — nên tách `$mainContent` thành các đoạn (ranh giới dòng trống), rồi so khớp TUẦN TỰ với
     * `$headingTexts` theo đúng thứ tự: đoạn nào trùng khớp y hệt text heading TIẾP THEO đang chờ
     * → bắt đầu section mới, còn lại gộp vào `text` của section hiện tại. Không có heading nào
     * (`$headings === []`) → không có ranh giới để tách, trả về mảng rỗng (dùng thẳng
     * `main_content`, tránh trùng lặp dữ liệu vô nghĩa).
     *
     * Giới hạn CHẤP NHẬN ĐƯỢC (best-effort, không phải parser chính xác tuyệt đối): nếu 1 đoạn văn
     * THƯỜNG (không phải heading) tình cờ trùng y hệt text heading đang chờ khớp, sẽ bị hiểu nhầm
     * thành ranh giới mới — hiếm gặp trong thực tế, và hậu quả chỉ là 1 section bị tách hơi lệch,
     * không phải lỗi/crash.
     *
     * Public (không phải private như các helper khác cùng file) — CoreIdeaExtractorController
     * cần gọi lại hàm này ở batch mode SAU KHI `main_content` đã bị cắt/chọn lọc theo ngân sách ký
     * tự (`truncateBatchMainContent()`/`selectRelevantContent()`), để `sections[]` phản ánh ĐÚNG
     * phần nội dung THẬT SỰ được trả về (không rò rỉ nội dung đã bị cắt bỏ ra ngoài `sections`,
     * làm mất tác dụng giới hạn ngân sách ký tự/nguồn của batch — xem CoreIdeaExtractorController::
     * extractBatch()/extract()).
     *
     * @param HeadingData[] $headings
     * @return array<int, array{heading: ?string, text: string}>
     */
    public function buildSections(string $mainContent, array $headings): array
    {
        if ($headings === []) {
            return [];
        }

        $paragraphs   = preg_split('/\n{2,}/', trim($mainContent)) ?: [];
        $headingTexts = array_map(static fn (HeadingData $h) => $h->text, $headings);
        $nextHeading  = 0;

        $sections = [];
        $current  = ['heading' => null, 'text' => []];

        foreach ($paragraphs as $paragraph) {
            $trimmed = trim($paragraph);

            if ($nextHeading < count($headingTexts) && $trimmed === $headingTexts[$nextHeading]) {
                if ($current['heading'] !== null || $current['text'] !== []) {
                    $sections[] = ['heading' => $current['heading'], 'text' => trim(implode("\n\n", $current['text']))];
                }

                $current = ['heading' => $trimmed, 'text' => []];
                $nextHeading++;

                continue;
            }

            $current['text'][] = $paragraph;
        }

        if ($current['heading'] !== null || $current['text'] !== []) {
            $sections[] = ['heading' => $current['heading'], 'text' => trim(implode("\n\n", $current['text']))];
        }

        // Loại section HOÀN TOÀN rỗng (không heading lẫn không text) — không có tình huống thật
        // nào tạo ra dạng này (luôn ít nhất có heading hoặc text), chỉ là an toàn phòng hờ.
        return array_values(array_filter(
            $sections,
            static fn (array $s) => $s['heading'] !== null || $s['text'] !== '',
        ));
    }

    private function extractPublishDate(\DOMXPath $xpath, string $html): ?string
    {
        $meta = $this->metaContent($xpath, "//meta[@property='article:published_time']");
        if ($meta) {
            return $meta;
        }

        return $this->fromJsonLd($html, 'datePublished');
    }

    private function extractAuthor(\DOMXPath $xpath, string $html): ?string
    {
        $meta = $this->metaContent($xpath, "//meta[@name='author']");
        if ($meta) {
            return $meta;
        }

        $author = $this->fromJsonLd($html, 'author');

        if (is_array($author)) {
            return $author['name'] ?? null;
        }

        return $author;
    }

    /**
     * Best-effort đọc JSON-LD (<script type="application/ld+json">) — không throw nếu JSON sai
     * định dạng. `$typeSubstring` (tùy chọn, case-insensitive) giới hạn chỉ đọc từ block có
     * `@type` khớp — cần thiết khi trang có NHIỀU JSON-LD block cùng lúc (VD Organization +
     * BreadcrumbList + Product): nếu chỉ tra theo `$key` bất kể `@type` (hành vi mặc định khi
     * không truyền `$typeSubstring`), dễ đọc NHẦM field cùng tên ở block KHÁC (VD `name` của
     * Organization thay vì của Product).
     */
    private function fromJsonLd(string $html, string $key, ?string $typeSubstring = null): mixed
    {
        if (! preg_match_all('#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $matches)) {
            return null;
        }

        foreach ($matches[1] as $json) {
            $decoded = json_decode(trim($json), true);

            if (! is_array($decoded)) {
                continue;
            }

            $candidates = isset($decoded[0]) && is_array($decoded[0]) ? $decoded : [$decoded];

            foreach ($candidates as $item) {
                if ($typeSubstring !== null) {
                    $type      = $item['@type'] ?? null;
                    $typeValue = is_array($type) ? ($type[0] ?? '') : (string) $type;

                    if (! str_contains(mb_strtolower($typeValue), $typeSubstring)) {
                        continue;
                    }
                }

                if (isset($item[$key])) {
                    return $item[$key];
                }
            }
        }

        return null;
    }

    /**
     * Quét cả HTML thô (không chỉ text hiển thị) để bắt được cả trường hợp paywall chỉ thể hiện
     * qua class/id (VD icon khoá không có text) — nhưng PHẢI loại nội dung <style>/<script>
     * trước khi quét: đã gặp thật với vnexpress.net — CSS boilerplate dùng chung cho MỌI bài
     * (kể cả bài miễn phí) định nghĩa sẵn class ".paywall-paragraph-overlay"/"paywall-readmore"
     * trong <style>, khớp nhầm từ khoá "paywall" dù bài viết hoàn toàn đọc được đầy đủ, không hề
     * bị khoá.
     */
    private function looksLikePaywalled(\DOMXPath $xpath): bool
    {
        $bodyText = mb_strtolower($xpath->query('//body')->item(0)?->textContent ?? '');
        $bodyHtml = mb_strtolower($xpath->document->saveHTML() ?: '');
        $bodyHtml = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $bodyHtml) ?? $bodyHtml;

        foreach (self::PAYWALL_KEYWORDS as $kw) {
            if (str_contains($bodyText, $kw) || str_contains($bodyHtml, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * §6.1.4 (v1.3) — với ngôn ngữ không phân từ bằng khoảng trắng (Thái/Trung/Nhật...), đếm
     * theo str_word_count sẽ sai hoàn toàn (VD tiếng Thái không có khoảng trắng giữa từ) — dò
     * tỉ lệ ký tự thuộc dải Unicode CJK/Thai/Hiragana/Katakana, nếu chiếm ưu thế thì ước lượng
     * word_count = mb_strlen/2 (xấp xỉ trung bình 2 ký tự/từ, chấp nhận được vì đây chỉ dùng để
     * so ngưỡng high/medium/low, không cần chính xác tuyệt đối — spec §6.1.4 cũng chỉ yêu cầu
     * "ước lượng theo cảm nhận ngữ nghĩa", ở đây code hoá bằng heuristic gần nhất có thể).
     */
    private function countWords(string $text, string $language): int
    {
        $text = trim($text);

        if ($text === '') {
            return 0;
        }

        $totalChars = mb_strlen($text);
        preg_match_all('/[\x{0E00}-\x{0E7F}\x{4E00}-\x{9FFF}\x{3040}-\x{30FF}\x{AC00}-\x{D7A3}]/u', $text, $m);
        $cjkChars = count($m[0]);

        if ($totalChars > 0 && $cjkChars / $totalChars > 0.3) {
            return (int) round(mb_strlen(preg_replace('/\s+/', '', $text) ?? $text) / 2);
        }

        return str_word_count($text);
    }
}
