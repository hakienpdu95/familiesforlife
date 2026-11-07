<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostArticleTranslation;

class ResolveArticleReviewersAction
{
    use AsAction;

    public function approvers(PostArticleTranslation $translation, ?User $except = null): Collection
    {
        $translation->loadMissing('article.categories');

        $categoryIds = $translation->article->categories->pluck('id');

        $users = User::query()
            ->where('is_active', true)
            ->where(function ($q) use ($categoryIds) {
                $q->whereHas('roles', fn ($r) => $r->where('name', RoleEnum::CONTENT_EDITOR->value))
                    ->orWhere(function ($q) use ($categoryIds) {
                        $q->whereHas('roles', fn ($r) => $r->where('name', RoleEnum::SECTION_EDITOR->value))
                            ->whereHas('postCategoryEditorships', fn ($c) => $c->whereIn('post_categories.id', $categoryIds));
                    });
            })
            ->get();

        $users = $this->without($users, $except);

        return $users->isNotEmpty() ? $users : $this->publishers($except);
    }

    public function publishers(?User $except = null): Collection
    {
        $users = $this->activeWithRole([RoleEnum::CONTENT_HEAD->value]);

        if ($this->without($users, $except)->isEmpty()) {
            $users = $this->activeWithRole([RoleEnum::SUPER_ADMIN->value]);
        }

        return $this->without($users, $except);
    }

    private function activeWithRole(array $roles): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $roles))
            ->get();
    }

    private function without(Collection $users, ?User $except): Collection
    {
        return $except ? $users->reject(fn (User $u) => $u->is($except))->values() : $users;
    }
}
