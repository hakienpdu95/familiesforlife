<?php

namespace Modules\KpiGoal\Enums;

enum KpiDirection: string
{
    case HigherBetter = 'higher_better';
    case LowerBetter  = 'lower_better';

    public function label(): string
    {
        return match($this) {
            self::HigherBetter => 'Cao hơn tốt hơn (↑)',
            self::LowerBetter  => 'Thấp hơn tốt hơn (↓)',
        };
    }

    public function calcAchievement(float $current, float $target): float
    {
        if ($target == 0) {
            return 0;
        }

        return match($this) {
            self::HigherBetter => min($current / $target * 100, 150),
            self::LowerBetter  => max((2 - $current / $target) * 100, 0),
        };
    }
}
