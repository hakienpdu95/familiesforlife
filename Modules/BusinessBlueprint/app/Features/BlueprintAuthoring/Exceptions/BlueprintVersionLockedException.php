<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Exceptions;

use RuntimeException;

/**
 * Ném khi cố sửa nội dung 1 blueprint_version đang ở trạng thái immutable
 * (published/deprecated/archived — BR-004 A04.1, Principle 01 A04.3).
 * Chỉ được phép Clone, không được sửa trực tiếp.
 */
class BlueprintVersionLockedException extends RuntimeException
{
    public function __construct(string $status)
    {
        parent::__construct("Không thể sửa Blueprint Version đang ở trạng thái '{$status}' — chỉ có thể Clone sang version mới.");
    }
}
