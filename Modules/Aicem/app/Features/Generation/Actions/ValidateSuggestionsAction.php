<?php

namespace Modules\Aicem\Features\Generation\Actions;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Contracts\AicemSubjectResolver;

/**
 * Lọc suggestion thô từ AI trước khi ghi aicem_suggestions — spec/AICEM_Technical_Specification.md
 * mục 6.9.2. original_text ghi bằng giá trị THẬT đọc từ resolver (không tin model), vừa để so
 * "khác original" ở đây, vừa làm mốc phát hiện staleness ở AcceptSuggestionAction (mục 9.1).
 */
class ValidateSuggestionsAction
{
    use AsAction;

    /**
     * @param array<int, array> $rawSuggestions
     * @return array<int, array{field: ?string, block_id: ?int, original_text: string, suggested_text: string, reason: string}>
     */
    public function handle(string $subjectType, Model $subject, array $rawSuggestions): array
    {
        $registry = config("aicem_subjects.{$subjectType}", []);

        /** @var AicemSubjectResolver $resolver */
        $resolver = app($registry['resolver']);

        $allowedFields      = $registry['fields'] ?? [];
        $hasBlocks          = $registry['has_blocks'] ?? false;
        $editableBlockTypes = $registry['block_editable_types'] ?? [];
        $fieldConstraints   = $registry['field_constraints'] ?? [];

        $currentFields = $resolver->fields($subject);
        $currentBlocks = collect($hasBlocks ? $resolver->blocks($subject) : [])->keyBy('block_id');

        $valid = [];

        foreach ($rawSuggestions as $item) {
            if (! is_array($item)) {
                continue;
            }

            $field         = $item['field'] ?? null;
            $blockId       = $item['block_id'] ?? null;
            $suggestedText = trim((string) ($item['suggested_text'] ?? ''));
            $reason        = (string) ($item['reason'] ?? '');

            // Rule 1: đúng 1 trong 2 (field set + block_id null) hoặc (field null + block_id set).
            if (($field !== null) === ($blockId !== null)) {
                continue;
            }

            if ($suggestedText === '') {
                continue;
            }

            if ($field !== null) {
                // Rule 2: field phải thuộc registry.
                if (! in_array($field, $allowedFields, true)) {
                    continue;
                }

                $originalText = (string) ($currentFields[$field] ?? '');

                // Rule 4: khác original.
                if ($suggestedText === $originalText) {
                    continue;
                }

                $constraint = $fieldConstraints[$field] ?? null;
                if ($constraint && isset($constraint['max']) && mb_strlen($suggestedText) > $constraint['max']) {
                    continue;
                }

                $valid[] = [
                    'field'          => $field,
                    'block_id'       => null,
                    'original_text'  => $originalText,
                    'suggested_text' => $suggestedText,
                    'reason'         => $reason,
                ];

                continue;
            }

            // Rule 3: block_id chỉ hợp lệ nếu has_blocks=true, block tồn tại và type editable.
            if (! $hasBlocks) {
                continue;
            }

            $block = $currentBlocks->get($blockId);
            if (! $block || ! in_array($block['type'], $editableBlockTypes, true)) {
                continue;
            }

            $originalText = (string) $block['body'];

            if ($suggestedText === $originalText) {
                continue;
            }

            $valid[] = [
                'field'          => null,
                'block_id'       => $blockId,
                'original_text'  => $originalText,
                'suggested_text' => $suggestedText,
                'reason'         => $reason,
            ];
        }

        return $valid;
    }
}
