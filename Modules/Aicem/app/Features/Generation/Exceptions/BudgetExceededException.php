<?php

namespace Modules\Aicem\Features\Generation\Exceptions;

/** Đã vượt ai_settings.monthly_budget_usd tháng hiện tại (mục 13.1). */
class BudgetExceededException extends \RuntimeException
{
}
