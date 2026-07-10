<?php

namespace Modules\Aicem\Features\Generation\Exceptions;

/** Organization đã vượt organizations.ai_monthly_budget_usd tháng hiện tại (mục 13.1). */
class BudgetExceededException extends \RuntimeException
{
}
