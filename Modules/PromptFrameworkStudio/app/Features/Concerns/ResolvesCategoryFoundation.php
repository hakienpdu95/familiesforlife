<?php

namespace Modules\PromptFrameworkStudio\Features\Concerns;

use Modules\ContentFoundation\Models\CategoryContentFoundation;

/**
 * spec/PromptFrameworkStudio_Technical_Specification.md §4.4 (v2.7) — tra ngữ cảnh biên tập của
 * chuyên mục NGAY TRƯỚC khi gọi RenderPromptFromFrameworkAction (Action đó không tự query, để test
 * được mà không cần seed DB). Cùng khuôn
 * `ContentOutlines\Features\Concerns\ResolvesCategoryContext::resolveFoundation()`.
 *
 * Truy vấn trực tiếp Model thay vì gọi qua HTTP API của ContentFoundation là CHỦ Ý: API đó gác bởi
 * permission RIÊNG `content_foundation.use`, không phải `prompt_framework_studio.use`. Gọi thẳng
 * Model ở tầng PHP thì quyền đã được route của chính module này gác rồi — đúng cách ContentOutlines
 * làm, và tránh việc người có quyền dùng Prompt Studio bị 403 chỉ vì thiếu 1 permission khác.
 *
 * Vì sao không có `ListCategoryFoundationsAction` tương ứng ở đây: Action đó đã có sẵn bên
 * ContentFoundation và được inject thẳng ở Controller (cùng cách 3 module consumer kia dùng).
 */
trait ResolvesCategoryFoundation
{
    private function resolveFoundation(?int $postCategoryId): ?CategoryContentFoundation
    {
        if ($postCategoryId === null) {
            return null;
        }

        return CategoryContentFoundation::query()
            ->whereHas('categories', fn ($q) => $q->where('post_categories.id', $postCategoryId))
            ->first();
    }
}
