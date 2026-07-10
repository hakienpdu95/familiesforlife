<?php

namespace Modules\Aicem\Features\KnowledgeBase\Support;

use Modules\Aicem\Features\KnowledgeBase\Exceptions\InvalidKnowledgeDocumentException;
use Modules\Aicem\Support\KnowledgeSlotRegistry;

/**
 * Validate 1 bộ (type, subject_type, scope) trước khi lưu — nguồn kiểm tra duy nhất dùng bởi
 * cả CreateKnowledgeDocumentAction và UpdateKnowledgeDocumentAction, tránh lặp logic
 * (spec/AICEM_Technical_Specification.md mục 6.3.1).
 */
final class KnowledgeDocumentSlotGuard
{
    public static function assertValid(string $type, ?string $subjectType, ?array $scope): void
    {
        if (! KnowledgeSlotRegistry::isValidKnowledgeType($type, $subjectType)) {
            throw new InvalidKnowledgeDocumentException(
                "Loại tri thức \"{$type}\" không hợp lệ với subject_type "
                . ($subjectType ?? '(DNA chung — không gắn subject)') . '.'
            );
        }

        // Tầng DNA (mục 5.1) — subject_type null luôn áp dụng chung, không được kèm scope.
        if ($subjectType === null && $scope !== null) {
            throw new InvalidKnowledgeDocumentException(
                'Tri thức DNA chung toàn tổ chức (không gắn subject_type) không được đặt scope — '
                . 'scope chỉ áp dụng cho tri thức gắn với 1 subject_type cụ thể.'
            );
        }

        if ($subjectType !== null && $scope !== null) {
            $allowedKeys = config("aicem_subjects.{$subjectType}.taxonomy_keys", []);
            $invalidKeys = array_diff(array_keys($scope), $allowedKeys);

            if (! empty($invalidKeys)) {
                throw new InvalidKnowledgeDocumentException(
                    'scope chứa key không hợp lệ cho subject_type ' . $subjectType . ': '
                    . implode(', ', $invalidKeys) . '. Key hợp lệ: ' . implode(', ', $allowedKeys) . '.'
                );
            }
        }
    }
}
