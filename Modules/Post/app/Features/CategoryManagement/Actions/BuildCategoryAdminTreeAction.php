<?php

namespace Modules\Post\Features\CategoryManagement\Actions;

use App\Models\User;
use App\Support\I18n\PortalLocale;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostCategory;

class BuildCategoryAdminTreeAction
{
    use AsAction;

    public const STATUSES = ['active', 'inactive', 'untranslated'];

    private Collection $byParent;

    private array $subtreeTotals = [];

    private array $locales;

    private bool $canCreate = false;

    public function handle(User $user, ?string $search = null, ?string $status = null): array
    {
        $this->locales = PortalLocale::prefixed();
        $this->canCreate = $user->can('create', PostCategory::class);

        $all = PostCategory::query()
            ->withCount('articles')
            ->with(['translations' => fn ($q) => $q->select(['id', 'post_category_id', 'locale', 'name'])->whereIn('locale', $this->locales)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $this->byParent = $all->whereNotNull('parent_id')->groupBy('parent_id');
        $needle = $search !== null && trim($search) !== '' ? Str::lower(Str::ascii(trim($search))) : null;
        $status = in_array($status, self::STATUSES, true) ? $status : null;
        $filtering = $needle !== null || $status !== null;

        $matched = 0;
        $build = function (PostCategory $node, int $depth) use (&$build, $user, $needle, $status, $filtering, &$matched): ?array {
            $children = $this->byParent->get($node->id, collect());
            $childRows = $children->map(fn (PostCategory $child) => $build($child, $depth + 1))->filter()->values()->all();
            $isMatch = $this->matches($node, $needle, $status);

            if ($filtering && ! $isMatch && $childRows === []) {
                return null;
            }

            $matched += $isMatch ? 1 : 0;

            return [
                ...$this->fields($node, $user, $depth, $children->count()),
                'matched' => ! $filtering || $isMatch,
                ...($childRows === [] ? [] : ['children' => $childRows]),
            ];
        };

        $rows = $all->whereNull('parent_id')->map(fn (PostCategory $root) => $build($root, 0))->filter()->values()->all();

        return [
            'data' => $rows,
            'matched' => $matched,
            'filtering' => $filtering,
            'stats' => [
                'total' => $all->count(),
                'roots' => $all->whereNull('parent_id')->count(),
                'inactive' => $all->where('is_active', false)->count(),
                'untranslated' => $all->reject(fn (PostCategory $c) => $this->isTranslated($c))->count(),
                'translated' => $all->filter(fn (PostCategory $c) => $this->isTranslated($c))->count(),
                'max_depth' => $this->maxDepth($all->whereNull('parent_id')->values(), 1),
            ],
        ];
    }

    private function fields(PostCategory $category, User $user, int $depth, int $childrenCount): array
    {
        $translations = [];
        foreach ($this->locales as $locale) {
            $name = $category->translations->firstWhere('locale', $locale)?->name;
            $translations[$locale] = filled($name) ? $name : null;
        }

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'depth' => $depth,
            'children_count' => $childrenCount,
            'articles_count' => (int) $category->articles_count,
            'subtree_articles' => $this->subtreeTotal($category),
            'is_active' => (bool) $category->is_active,
            'translations' => $translations,
            'is_translated' => ! in_array(null, $translations, true),
            'search_text' => Str::lower(Str::ascii(implode(' ', array_filter([$category->name, $category->slug, ...array_values($translations)])))),
            'edit_url' => route('backend.post.categories.edit', $category),
            'destroy_url' => route('backend.post.categories.destroy', $category),
            'create_child_url' => $this->canCreate ? route('backend.post.categories.create', ['parent_id' => $category->id]) : null,
            'public_url' => $category->is_active ? route('post.public.category', ['category' => $category->slug]) : null,
            'can_update' => $user->can('update', $category),
            'can_delete' => $user->can('delete', $category),
            'delete_blocker' => match (true) {
                $childrenCount > 0 => __('Còn :count danh mục con', ['count' => $childrenCount]),
                $category->articles_count > 0 => __('Còn :count bài viết', ['count' => $category->articles_count]),
                default => null,
            },
        ];
    }

    private function matches(PostCategory $category, ?string $needle, ?string $status): bool
    {
        if ($needle !== null) {
            $haystack = Str::lower(Str::ascii($category->name.' '.$category->slug.' '.$category->translations->pluck('name')->implode(' ')));
            if (! str_contains($haystack, $needle)) {
                return false;
            }
        }

        return match ($status) {
            'active' => (bool) $category->is_active,
            'inactive' => ! $category->is_active,
            'untranslated' => ! $this->isTranslated($category),
            default => true,
        };
    }

    private function isTranslated(PostCategory $category): bool
    {
        foreach ($this->locales as $locale) {
            if (blank($category->translations->firstWhere('locale', $locale)?->name)) {
                return false;
            }
        }

        return true;
    }

    private function subtreeTotal(PostCategory $category): int
    {
        return $this->subtreeTotals[$category->id] ??= (int) $category->articles_count
            + $this->byParent->get($category->id, collect())->sum(fn (PostCategory $child) => $this->subtreeTotal($child));
    }

    private function maxDepth(Collection $nodes, int $level): int
    {
        if ($nodes->isEmpty()) {
            return $level - 1;
        }

        return $nodes->max(fn (PostCategory $node) => $this->maxDepth($this->byParent->get($node->id, collect()), $level + 1));
    }
}
