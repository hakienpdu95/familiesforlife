<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentOutlines\Features\OutlineGeneration\Actions\CreateContentOutlineAction;
use Modules\ContentOutlines\Features\OutlineGeneration\Data\ContentOutlineInputData;
use Modules\PromptFrameworkStudio\Models\TopicClusterResult;

/**
 * (2026-08-28, phản hồi review spec/TopicClusterGenerator.md) — "nút Tạo Dàn ý": đẩy các mục Pillar/
 * Cluster ĐÃ DUYỆT (chọn checkbox ở màn hình review) từ 1 `TopicClusterResult` sang
 * `Modules\ContentOutlines` — mỗi mục thành 1 `ContentOutline` nháp (`content_role` = pillar/cluster,
 * `topic`/`target_keyword` lấy từ tiêu đề/từ khóa đã parse), để biên tập viên vào đó sinh tiếp prompt
 * dàn ý chi tiết — KHÔNG tự động gọi AI sinh dàn ý (`CreateContentOutlineAction` cũng chỉ dựng prompt
 * để copy-paste, đúng nguyên tắc chung "không gọi AI Provider trong app" của cả 2 module).
 *
 * PHỤ THUỘC TRỰC TIẾP `Modules\ContentOutlines` — ĐÂY LÀ NGOẠI LỆ so với quy ước "modules phụ thuộc
 * 1 CHIỀU vào ContentFoundation, KHÔNG phụ thuộc chéo lẫn nhau" (xem docblock
 * `CategoryContentFoundation`): quan hệ này CHỦ Ý 1 chiều PromptFrameworkStudio → ContentOutlines
 * (ContentOutlines không biết/không cần biết gì về PromptFrameworkStudio), và là hành vi người dùng
 * yêu cầu rõ ràng (nút "Tạo Dàn ý" đẩy thẳng sang module đó) — không phải phụ thuộc chéo qua lại.
 *
 * KHÔNG chèn lại mục đã có `content_outline_uuid` (đã đẩy trước đó) — tránh tạo trùng ContentOutline
 * nếu người dùng bấm đẩy 2 lần hoặc chọn lại mục đã đẩy.
 */
class PushTopicClusterItemsToContentOutlinesAction
{
    use AsAction;

    public function __construct(private readonly CreateContentOutlineAction $createOutline) {}

    /**
     * @param  string[]  $selectedKeys  'pillar' hoặc 'cluster:<index>' — xem field-form phía view.
     */
    public function handle(TopicClusterResult $result, array $selectedKeys, int $userId): TopicClusterResult
    {
        $structured = $result->structured;
        $postCategoryId = $result->generatedPrompt->post_category_id;

        if (in_array('pillar', $selectedKeys, true) && $structured['pillar'] && ! $structured['pillar']['content_outline_uuid']) {
            $structured['pillar']['content_outline_uuid'] = $this->push($structured['pillar'], 'pillar', $postCategoryId, $userId);
        }

        foreach ($structured['clusters'] as $index => $cluster) {
            $key = "cluster:{$index}";

            if (in_array($key, $selectedKeys, true) && ! $cluster['content_outline_uuid']) {
                $structured['clusters'][$index]['content_outline_uuid'] = $this->push($cluster, 'cluster', $postCategoryId, $userId);
            }
        }

        $result->update(['structured' => $structured, 'updated_by' => $userId]);

        return $result->fresh();
    }

    /** @param array{title: string, target_keyword: string} $item */
    private function push(array $item, string $contentRole, ?int $postCategoryId, int $userId): string
    {
        $input = new ContentOutlineInputData(
            label: $item['title'],
            topic: $item['title'],
            target_keyword: $item['target_keyword'] !== '' ? $item['target_keyword'] : $item['title'],
            post_category_id: $postCategoryId,
            content_role: $contentRole,
        );

        return $this->createOutline->handle($input, $userId)->uuid;
    }
}
