<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Exceptions;

/** Tổ chức đã/sắp vượt organizations.ai_monthly_budget_usd tháng hiện tại khi chạy Layer 2. */
class AiBudgetExceededException extends \RuntimeException
{
}
