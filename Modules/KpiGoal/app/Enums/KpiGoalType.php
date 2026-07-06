<?php

namespace Modules\KpiGoal\Enums;

enum KpiGoalType: string
{
    case Manual       = 'manual';
    case LinkedSource = 'linked_source'; // Phase 3B

    public function label(): string
    {
        return match($this) {
            self::Manual       => 'Thủ công',
            self::LinkedSource => 'Liên kết nguồn',
        };
    }
}
