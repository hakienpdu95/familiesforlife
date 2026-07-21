<?php

namespace Modules\Post\Features\TagManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Features\TagManagement\Data\TagData;
use Modules\Post\Models\PostTag;

/**
 * Không đụng tới `slug` khi đổi tên — theo đúng tiền lệ `UpdateCategoryAction`
 * (spec/PostTag_Management_Technical_Specification.md §3.6). Hiển thị tag dùng `name`, không
 * dùng `slug`, nên đổi tên vẫn hiển thị đúng ngay lập tức dù slug giữ nguyên từ lúc tạo.
 */
class UpdateTagAction
{
    use AsAction;

    public function handle(PostTag $tag, TagData $data): PostTag
    {
        $tag->update([
            'name' => $data->name,
        ]);

        return $tag;
    }
}
