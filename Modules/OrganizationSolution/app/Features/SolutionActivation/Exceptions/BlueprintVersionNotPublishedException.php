<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Exceptions;

use RuntimeException;

/**
 * Ném khi cố activate 1 Business Solution với blueprint_version chưa published
 * (spec §3.3 "PIN cứng — bắt buộc published").
 */
class BlueprintVersionNotPublishedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Không thể kích hoạt Solution: Blueprint Version phải ở trạng thái published.');
    }
}
