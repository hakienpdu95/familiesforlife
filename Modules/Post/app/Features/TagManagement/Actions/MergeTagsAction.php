<?php

namespace Modules\Post\Features\TagManagement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostTag;

/**
 * Gộp tag nguồn vào tag đích — không có tiền lệ trong repo, thiết kế mới
 * (spec/PostTag_Management_Technical_Specification.md §3.2/§3.9). `post_article_tag.tag_id` có
 * `->cascadeOnDelete()` nên xoá tag nguồn tự động dọn sạch pivot cũ ở tầng DB, không cần code
 * thêm bước xoá pivot thủ công.
 */
class MergeTagsAction
{
    use AsAction;

    public function handle(PostTag $sourceTag, PostTag $targetTag): PostTag
    {
        if ($sourceTag->id === $targetTag->id) {
            throw ValidationException::withMessages([
                'target_tag_id' => 'Không thể gộp tag vào chính nó.',
            ]);
        }

        DB::transaction(function () use ($sourceTag, $targetTag): void {
            $existingArticleIds = $targetTag->articles()->pluck('post_articles.id');

            $articleIdsToAttach = $sourceTag->articles()
                ->pluck('post_articles.id')
                ->diff($existingArticleIds);

            if ($articleIdsToAttach->isNotEmpty()) {
                $targetTag->articles()->attach($articleIdsToAttach);
            }

            $sourceTag->delete();
        });

        Log::info('post_tag.merge', [
            'source_tag_id' => $sourceTag->id,
            'source_name'   => $sourceTag->name,
            'target_tag_id' => $targetTag->id,
            'target_name'   => $targetTag->name,
            'actor_id'      => auth()->id(),
        ]);

        return $targetTag;
    }
}
