<?php

namespace Modules\Aicem\Support;

/**
 * Bảng tra duy nhất cho tính hợp lệ của aicem_knowledge_documents.type — dùng bởi
 * SaveKnowledgeDocumentAction (Phase 2), seeder mặc định, và UI dropdown chọn type, để không
 * lặp lại logic in_array/match rải rác ở nhiều nơi. Xem
 * spec/AICEM_Technical_Specification.md mục 6.3.1.
 */
final class KnowledgeSlotRegistry
{
    public static function isValidKnowledgeType(string $type, ?string $subjectType): bool
    {
        $def = config("aicem_subjects.knowledge_slot_definitions.{$type}");

        if ($def === null) {
            return false;
        }

        if ($def['subject_type_required'] && $subjectType === null) {
            return false;
        }

        if ($def['subject_type_allowed'] === [] && $subjectType !== null) {
            return false;
        }

        if (is_array($def['subject_type_allowed']) && $def['subject_type_allowed'] !== []
            && ! in_array($subjectType, $def['subject_type_allowed'], true)) {
            return false;
        }

        return true;
    }

    /** knowledge_slots dạng "view lọc" từ knowledge_slot_definitions theo đúng subject_type — dùng cho dropdown UI. */
    public static function specializedSlotsFor(string $subjectType): array
    {
        return array_keys(array_filter(
            config('aicem_subjects.knowledge_slot_definitions', []),
            fn (array $def) => $def['tier'] === 'specialized'
                && is_array($def['subject_type_allowed'])
                && in_array($subjectType, $def['subject_type_allowed'], true)
        ));
    }
}
