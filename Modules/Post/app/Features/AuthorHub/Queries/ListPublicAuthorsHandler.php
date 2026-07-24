<?php

namespace Modules\Post\Features\AuthorHub\Queries;

use App\Enums\AccountType;
use App\Models\User;
use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Models\PostArticleTranslation;

/**
 * spec/Author_Contributor_Hub_Technical_Specification.md §5.1/§7.2 — điều kiện xuất hiện ở
 * `/tac-gia`: (a) `account_type=platform` (§0 v1.2 — loại `marketing`/Lớp B), (b) hồ sơ
 * `is_public=true`, (c) ≥1 bài published. KHÔNG dùng quan hệ `User::articlesCreated()` (không
 * tồn tại — §7.4 chỉ thêm đúng 1 quan hệ đọc `authorProfile()` vào User) — điều kiện (c) và số
 * bài hiển thị đều tính qua subquery trực tiếp trên `post_articles`/`post_article_translations`.
 */
class ListPublicAuthorsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListPublicAuthorsQuery $query */
        $publishedCountSub = PostArticleTranslation::query()
            ->selectRaw('COUNT(*)')
            ->join('post_articles', 'post_articles.id', '=', 'post_article_translations.article_id')
            ->whereColumn('post_articles.created_by', 'users.id')
            ->where('post_article_translations.status', TranslationStatus::Published->value);

        return User::query()
            ->where('account_type', AccountType::Platform->value)
            ->whereHas('authorProfile', fn ($q) => $q->where('is_public', true))
            ->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('post_articles')
                    ->join('post_article_translations', 'post_article_translations.article_id', '=', 'post_articles.id')
                    ->whereColumn('post_articles.created_by', 'users.id')
                    ->where('post_article_translations.status', TranslationStatus::Published->value);
            })
            ->with('authorProfile')
            ->addSelect(['published_articles_count' => $publishedCountSub])
            ->withCasts(['published_articles_count' => 'integer'])
            ->orderBy('name')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
