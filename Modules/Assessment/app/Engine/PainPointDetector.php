<?php

namespace Modules\Assessment\Engine;

class PainPointDetector
{
    /**
     * Parse required_flags (comma-separated, prefix ! = NOT, tất cả AND)
     * → trả về danh sách pain_point_code được kích hoạt.
     *
     * @param  array<string, bool>  $signalFlags
     * @return string[]
     */
    public function detect(ScoringConfig $config, array $signalFlags): array
    {
        $detected = [];

        foreach ($config->painPointRules as $rule) {
            if ($this->matches($rule->required_flags, $signalFlags)) {
                $detected[] = $rule->pain_point_code;
            }
        }

        return $detected;
    }

    private function matches(string $requiredFlags, array $signalFlags): bool
    {
        $conditions = array_map('trim', explode(',', $requiredFlags));

        foreach ($conditions as $condition) {
            if ($condition === '') {
                continue;
            }

            if (str_starts_with($condition, '!')) {
                // NOT: flag phải là false hoặc không tồn tại
                $flag   = substr($condition, 1);
                $value  = $signalFlags[$flag] ?? false;
                if ($value === true) {
                    return false;
                }
            } else {
                // AND: flag phải là true
                $value = $signalFlags[$condition] ?? false;
                if ($value !== true) {
                    return false;
                }
            }
        }

        return true;
    }
}
