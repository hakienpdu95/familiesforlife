<?php

namespace Modules\Post\Features\Traceability;

use App\Shared\Traceability\Contracts\TracePresenter;
use App\Shared\Traceability\TraceabilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Post\Enums\ContentBlockType;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Support\ArticleContentRenderer;

class PostArticleTracePresenter implements TracePresenter
{
    private const SUMMARY_WORDS = 200;

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
            'translations' => fn ($q) => $q->select('id', 'article_id', 'locale', 'title', 'slug', 'excerpt', 'direct_answer', 'status', 'published_at'),
            'primaryCategory:post_categories.id,name',
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
        $translation = $this->publicTranslation($target);

        return [
            'title' => $translation?->title,
            'summary' => $translation ? $this->summary($translation) : null,
            'category' => $target->primaryCategory->first()?->name,
            'published_at' => $translation?->published_at?->format('d/m/Y'),
            'image' => TraceabilityService::imageRef($target, 'cover', 'medium'),
        ];
    }

    /** @param  PostArticle|null  $live */
    public function page(array $payload, ?Model $live): array
    {
        $translation = $live ? $this->publicTranslation($live) : null;

        return $this->base([
            'title' => $payload['title'] ?? 'Bài viết',
            'image' => $payload['image']['url'] ?? null,
            'category' => $payload['category'] ?? null,
            'published_at' => $payload['published_at'] ?? null,
            'excerpt' => $translation ? (filled($translation->excerpt) ? trim(strip_tags($translation->excerpt)) : null) : ($payload['summary'] ?? null),
            'content_html' => $translation ? $this->renderer->render($translation) : null,
            'cta' => $translation
                ? ['url' => route('post.public.article', ['slug' => $translation->slug, 'id' => $translation->id]), 'label' => 'Đọc toàn bộ bài viết']
                : ['url' => route('post.public.home'), 'label' => 'Xem tin tức khác'],
        ]);
    }

    /** @param  PostArticle  $target */
    public function pending(Model $target): array
    {
        return $this->base([
            'title' => 'Bài viết',
            'notice' => 'Bài viết hiện chưa được xuất bản.',
        ]);
    }

    private function base(array $page): array
    {
        return TraceabilityService::page([
            'heading' => 'Thông tin bài viết',
            'icon' => 'fa-newspaper',
            'category' => null,
            'published_at' => null,
            'excerpt' => null,
            'content_html' => null,
            'archive_text' => 'Cảnh báo: Thông tin dưới đây là bản lưu trữ. Bài viết này hiện đã bị gỡ khỏi hệ thống.',
            'cta' => ['url' => route('post.public.home'), 'label' => 'Xem tin tức khác'],
            ...$page,
        ]);
    }

    private function summary(PostArticleTranslation $translation): ?string
    {
        foreach ([$translation->excerpt, $translation->direct_answer] as $text) {
            if (filled($text)) {
                return trim(strip_tags($text));
            }
        }

        $body = $translation->contentBlocks()
            ->where('type', ContentBlockType::Text)
            ->orderBy('sort_order')
            ->pluck('text_html')
            ->map(fn ($html) => trim(preg_replace('/\s+/u', ' ', strip_tags((string) $html))))
            ->filter()
            ->implode(' ');

        return $body !== '' ? Str::words($body, self::SUMMARY_WORDS) : null;
    }

    private function publicTranslation(PostArticle $article): ?PostArticleTranslation
    {
        return $article->translations
            ->where('locale', config('post.default_locale'))
            ->firstWhere('status', TranslationStatus::Published);
    }
}
