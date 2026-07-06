<?php

namespace Modules\BusinessSolution\Features\SolutionCatalogManagement\Exceptions;

use RuntimeException;

/**
 * Ném khi publish 1 Business Solution chưa có bất kỳ Blueprint nào ở status=published
 * (OR-006 tương đương ở tầng Solution — không cho publish Solution rỗng).
 */
class BusinessSolutionNotPublishableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Không thể phát hành Business Solution: phải có ít nhất 1 Blueprint đã phát hành (published) thuộc solution này.');
    }
}
