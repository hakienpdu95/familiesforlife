<?php

namespace Modules\Aicem\Features\Generation\Actions;

use Illuminate\Database\Eloquent\Model;
use Modules\Aicem\Contracts\AicemSubjectResolver;
use Modules\Aicem\Models\AicemWorkflow;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Ghép aicem_context_templates.schema (sections) + knowledge base + field/block hiện tại của
 * subject thành `array $messages` chuẩn OpenAI, kèm cảnh báo nếu bị input-bounding cắt bớt —
 * spec/AICEM_Technical_Specification.md mục 6.6/6.9.1.
 *
 * Lưu ý bổ sung so với mục 6.6 gốc: mục đó chỉ nhận (AicemContextTemplate, Model) và không nói
 * rõ `aicem_workflows.prompt_template` (nhiệm vụ cụ thể của workflow, VD "tối ưu tiêu đề") được
 * ghép vào đâu — action này nhận thẳng AicemWorkflow (có quan hệ contextTemplate) để tự ghép
 * prompt_template vào cuối khối user, thay {{field}} bằng giá trị field hiện tại của subject.
 */
class BuildPromptAction
{
    use AsAction;

    public function __construct(
        private readonly ResolveApplicableKnowledgeAction $resolveKnowledge,
    ) {}

    /** @return array{messages: array<int, array{role: string, content: string}>, warnings: string[]} */
    public function handle(AicemWorkflow $workflow, Model $subject): array
    {
        $template    = $workflow->contextTemplate;
        $subjectType = $template->subject_type;

        /** @var AicemSubjectResolver $resolver */
        $resolver = app(config("aicem_subjects.{$subjectType}.resolver"));

        $taxonomy = $resolver->taxonomy($subject);
        $fields   = $resolver->fields($subject);

        $warnings        = [];
        $knowledgeChunks = [];
        $totalChars      = 0;
        $maxChars        = config('aicem.prompt_bounds.max_knowledge_chars', 40_000);
        $maxBlocks       = config('aicem.prompt_bounds.max_blocks', 40);
        $chunksTruncatedByChars = false;

        $taxonomyBlock = null;
        $fieldsBlock   = null;
        $blocksBlock   = null;

        foreach (($template->schema['sections'] ?? []) as $section) {
            $source = $section['source'] ?? null;

            if ($source === 'knowledge_document') {
                if ($chunksTruncatedByChars) {
                    continue; // đã đạt trần ký tự — không thêm khối knowledge nào nữa
                }

                $result = $this->resolveKnowledge->handle(
                    $subject->organization_id,
                    $section['type'],
                    $section['subject_type'] ?? $subjectType,
                    $taxonomy,
                );

                if ($result['truncated']) {
                    $warnings[] = "Loại tri thức \"{$section['type']}\" vượt trần max_docs_per_type, đã bỏ bớt document priority thấp nhất.";
                }

                $contents = ! empty($section['limit'])
                    ? array_slice($result['content'], 0, (int) $section['limit'])
                    : $result['content'];

                foreach ($contents as $content) {
                    $len = mb_strlen($content);

                    if ($totalChars + $len > $maxChars) {
                        $warnings[] = "Đã đạt trần max_knowledge_chars ({$maxChars} ký tự), bỏ bớt phần knowledge base còn lại.";
                        $chunksTruncatedByChars = true;
                        continue 2;
                    }

                    $totalChars       += $len;
                    $knowledgeChunks[] = "### {$section['type']}\n{$content}";
                }
            } elseif ($source === 'subject_taxonomy') {
                $taxonomyBlock = $this->renderTaxonomy($taxonomy);
            } elseif ($source === 'subject_fields') {
                $fieldsBlock = $this->renderFields($fields, $section['fields'] ?? []);
            } elseif ($source === 'subject_blocks') {
                $blocks = array_values(array_filter(
                    $resolver->blocks($subject),
                    fn (array $b) => in_array($b['type'], $section['block_types'] ?? [], true)
                ));

                if (count($blocks) > $maxBlocks) {
                    $warnings[] = 'Bài viết có ' . count($blocks) . " block, vượt trần max_blocks ({$maxBlocks}), chỉ lấy {$maxBlocks} block đầu.";
                    $blocks     = array_slice($blocks, 0, $maxBlocks);
                }

                $blocksBlock = $this->renderBlocks($blocks, $section['instruction'] ?? null);
            }
        }

        $systemParts = [
            'Bạn là trợ lý AI hỗ trợ biên tập nội dung — CHỈ đề xuất, không tự quyết định thay '
            . 'biên tập viên. Biên tập viên sẽ xem từng đề xuất và chấp nhận/từ chối riêng lẻ.',
        ];

        if ($knowledgeChunks) {
            $systemParts[] = 'Các đoạn hướng dẫn dưới đây được sắp từ tổng quát đến cụ thể theo bài '
                . "viết/sản phẩm này. Nếu có mâu thuẫn, tuân theo đoạn xuất hiện SAU.\n\n"
                . implode("\n\n", $knowledgeChunks);
        }

        $userParts   = array_values(array_filter([$taxonomyBlock, $fieldsBlock, $blocksBlock]));
        $userParts[] = $this->renderWorkflowInstruction($workflow, $fields);

        $messages = [
            // cacheable=true (Phase 6, mục 8.7/15) — khối DNA/knowledge_document nằm trong message
            // này lặp lại gần như nguyên văn qua mọi lần chạy AI của cùng 1 Organization+workflow,
            // ứng viên tốt cho prompt caching (AnthropicProvider đọc cờ này để gắn cache_control;
            // OpenAIProvider bỏ qua vì caching của OpenAI tự động, không cần đánh dấu).
            ['role' => 'system', 'content' => implode("\n\n", $systemParts), 'cacheable' => true],
            ['role' => 'user', 'content' => implode("\n\n", $userParts)],
        ];

        return ['messages' => $messages, 'warnings' => $warnings];
    }

    private function renderTaxonomy(array $taxonomy): string
    {
        $lines = [];
        foreach ($taxonomy as $key => $values) {
            $lines[] = "{$key}: " . implode(', ', (array) $values);
        }

        return "### Bối cảnh phân loại\n" . implode("\n", $lines);
    }

    /** @param array<string, mixed> $allFields @param string[] $allowedFields */
    private function renderFields(array $allFields, array $allowedFields): string
    {
        $lines = [];
        foreach ($allowedFields as $field) {
            $value   = $allFields[$field] ?? null;
            $lines[] = "{$field}: " . ($value !== null && $value !== '' ? $value : '(trống)');
        }

        return "### Các field hiện tại\n" . implode("\n", $lines);
    }

    /** @param array<int, array{block_id: int, type: string, body: string}> $blocks */
    private function renderBlocks(array $blocks, ?string $instruction): string
    {
        $lines = [];
        if ($instruction) {
            $lines[] = $instruction;
        }

        foreach ($blocks as $block) {
            $lines[] = "[block_id={$block['block_id']}] {$block['body']}";
        }

        return "### Nội dung bài viết (theo block)\n" . implode("\n\n", $lines);
    }

    private function renderWorkflowInstruction(AicemWorkflow $workflow, array $fields): string
    {
        $text = $workflow->prompt_template;

        foreach ($fields as $key => $value) {
            $text = str_replace('{{' . $key . '}}', (string) ($value ?? ''), $text);
        }

        return "### Nhiệm vụ\n{$text}";
    }
}
