<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\PromptFrameworkStudio\Models\GeneratedPrompt;
use Modules\PromptFrameworkStudio\Models\TopicClusterResult;

/**
 * (2026-08-28, phản hồi review spec/TopicClusterGenerator.md) — lưu (hoặc GHI ĐÈ, không versioning
 * — cùng quy ước "Sinh lại" của `GeneratedPrompt`) kết quả AI đã dán lại cho 1 prompt `topiccluster`.
 * GHI ĐÈ hoàn toàn `structured` mỗi lần dán lại — kể cả các mục đã từng đẩy sang ContentOutlines
 * (content_outline_uuid) sẽ mất liên kết đó nếu dán lại; chấp nhận được vì đây là công cụ nội bộ,
 * dán lại thường vì đã sửa/AI sinh lại — không nên giữ liên kết cũ có thể không còn khớp tiêu đề mới.
 */
class SaveTopicClusterAiResultAction
{
    use AsAction;

    public function __construct(private readonly ParseTopicClusterAiResultAction $parse) {}

    public function handle(GeneratedPrompt $prompt, string $rawResult, int $userId): TopicClusterResult
    {
        $parsed = $this->parse->handle($rawResult);

        $result = TopicClusterResult::firstOrNew(['generated_prompt_id' => $prompt->id]);

        $result->fill([
            'ai_result_raw' => $rawResult,
            'structured' => [
                'pillar' => $parsed['pillar'],
                'clusters' => $parsed['clusters'],
            ],
            'updated_by' => $userId,
        ]);

        if (! $result->exists) {
            $result->created_by = $userId;
        }

        $result->save();

        return $result;
    }
}
