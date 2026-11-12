<?php

namespace Modules\Post\Features\Traceability;

use App\Shared\Traceability\Concerns\BuildsRelatedCards;
use App\Shared\Traceability\Contracts\TracePresenter;
use App\Shared\Traceability\TraceabilityService as T;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Post\Enums\ContentBlockType;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Support\ArticleContentRenderer;

class PostArticleTracePresenter implements TracePresenter
{
    use BuildsRelatedCards;

    private const SUMMARY_WORDS = 200;

    private const WORDS_PER_MINUTE = 200;

    public function __construct(
        private readonly ArticleContentRenderer $renderer,
    ) {}

    public function view(): string
    {
        return 'post::traceability.article';
    }

    public function constrain(Builder $query): void
    {
        $query->withTrashed()->with([
            'translations',
            'primaryCategory:post_categories.id,name',
            'tags:id,name',
            'media',
        ]);
    }

    /** @param  PostArticle  $target */
    public function isPublic(Model $target): bool
    {
        return $this->publicTranslation($target) !== null;
    }

    /** @param  PostArticle  $target */
    public function snapshot(Model $target): array
    {
        $t = $this->publicTranslation($target);
        $body = $t ? $this->bodyText($t) : '';
        $sponsored = $target->isCurrentlySponsored();

        return [
            'title' => $t?->title,
            'summary' => $t ? $this->summary($t, $body) : null,
            'category' => $target->primaryCategory->first()?->name,
            'tags' => $target->tags->pluck('name')->take(8)->all(),
            'published_at' => $t?->published_at?->toDateString(),
            'updated_at' => $t?->updated_at?->toDateString(),
            'reading_minutes' => $body !== '' ? max(1, (int) ceil(str_word_count(Str::ascii($body)) / self::WORDS_PER_MINUTE)) : null,
            'faq_count' => $t ? $t->faqBlocks()->withCount('items')->get()->sum('items_count') : 0,
            'howto_count' => $t ? $t->howtoBlocks()->withCount('steps')->get()->sum('steps_count') : 0,
            'sponsor' => $sponsored ? [
                'name' => $target->sponsor_name,
                'label' => $target->sponsor_label?->label(),
                'logo' => $target->sponsor_logo_url,
                'disclosure' => $t?->disclosure_text,
            ] : null,
            'citations' => $t ? $t->contentBlocks()->where('type', ContentBlockType::Citation)->get()
                ->map(fn ($b) => ['text' => $b->citation_text, 'source' => $b->citation_source_name, 'url' => $b->citation_source_url])
                ->values()->all() : [],
            'image' => T::imageRef($target, 'cover', 'medium'),
        ];
    }

    /** @param  PostArticle|null  $live */
    public function page(array $payload, ?Model $live): array
    {
        $t = $live ? $this->publicTranslation($live) : null;

        $related = [];
        if ($live) {
            $live->loadMissing(['ocopProducts.media', 'ocopProducts.qrIdentity']);
            $related = [
                ['title' => __('Sản phẩm OCOP trong bài'), 'icon' => 'fa-certificate', 'items' => $this->ocopCards($live->ocopProducts, true)],
            ];
        }

        $published = ! empty($payload['published_at']) ? Carbon::parse($payload['published_at']) : null;
        $updated = ! empty($payload['updated_at']) ? Carbon::parse($payload['updated_at']) : null;

        return $this->base([
            'title' => $payload['title'] ?? __('Bài viết'),
            'images' => array_filter([$payload['image']['url'] ?? null]),
            'image_srcsets' => $live ? array_slice($this->mediaSrcsets($live, 'cover'), 0, 1) : [],
            'badges' => array_values(array_filter([
                ! empty($payload['category']) ? ['label' => $payload['category'], 'icon' => 'fa-layer-group', 'class' => 'badge-primary'] : null,
                ! empty($payload['sponsor']) ? ['label' => $payload['sponsor']['label'] ?: __('Nội dung tài trợ'), 'icon' => 'fa-handshake', 'class' => 'badge-warning'] : null,
            ])),
            'facts' => [
                T::fact('fa-calendar-check', __('Ngày xuất bản'), format_date($published)),
                T::fact('fa-pen-to-square', __('Cập nhật'), format_date($updated)),
                T::fact('fa-clock', __('Thời gian đọc'), ! empty($payload['reading_minutes']) ? __(':count phút', ['count' => $payload['reading_minutes']]) : null),
                T::fact('fa-layer-group', __('Chuyên mục'), $payload['category'] ?? null),
                T::fact('fa-circle-info', __('Hỏi đáp'), ! empty($payload['faq_count']) ? __(':count câu hỏi', ['count' => $payload['faq_count']]) : null),
                T::fact('fa-list-check', __('Hướng dẫn'), ! empty($payload['howto_count']) ? __(':count bước', ['count' => $payload['howto_count']]) : null),
            ],
            'category' => $payload['category'] ?? null,
            'published_label' => format_date($published),
            'updated_label' => $updated && $published && $updated->gt($published) ? format_date($updated) : null,
            'reading_minutes' => $payload['reading_minutes'] ?? null,
            'summary' => $t ? (filled($t->excerpt) ? trim(strip_tags($t->excerpt)) : (filled($t->direct_answer) ? trim(strip_tags($t->direct_answer)) : null)) : ($payload['summary'] ?? null),
            'content_html' => $t ? $this->renderer->render($t, [ContentBlockType::Citation]) : null,
            'sponsor' => $payload['sponsor'] ?? null,
            'citations' => array_values(array_filter($payload['citations'] ?? [], fn ($c) => filled($c['text'] ?? null))),
            'tags' => $payload['tags'] ?? [],
            'related' => $related,
            'cta' => $t
                ? ['url' => route('post.public.article', ['slug' => $t->slug, 'id' => $t->id]), 'label' => __('Đọc bài trên website')]
                : null,
        ]);
    }

    /** @param  PostArticle  $target */
    public function pending(Model $target): array
    {
        return $this->base([
            'title' => __('Bài viết'),
            'notice' => __('Bài viết hiện chưa được xuất bản.'),
        ]);
    }

    private function base(array $page): array
    {
        return T::page([
            'heading' => __('Thông tin bài viết'),
            'icon' => 'fa-newspaper',
            'kind' => __('Bài viết'),
            'category' => null,
            'published_label' => null,
            'updated_label' => null,
            'reading_minutes' => null,
            'cta_sticky' => false,
            'summary' => null,
            'content_html' => null,
            'sponsor' => null,
            'citations' => [],
            'tags' => [],
            'cta' => null,
            ...$page,
        ]);
    }

    private function bodyText(PostArticleTranslation $t): string
    {
        return $t->contentBlocks()
            ->where('type', ContentBlockType::Text)
            ->pluck('text_html')
            ->map(fn ($html) => trim(preg_replace('/\s+/u', ' ', strip_tags((string) $html))))
            ->filter()
            ->implode(' ');
    }

    private function summary(PostArticleTranslation $t, string $body): ?string
    {
        foreach ([$t->excerpt, $t->direct_answer] as $text) {
            if (filled($text)) {
                return trim(strip_tags($text));
            }
        }

        return $body !== '' ? Str::words($body, self::SUMMARY_WORDS) : null;
    }

    private function publicTranslation(PostArticle $article): ?PostArticleTranslation
    {
        return $article->translations
            ->where('locale', config('post.default_locale'))
            ->firstWhere('status', TranslationStatus::Published);
    }
}
