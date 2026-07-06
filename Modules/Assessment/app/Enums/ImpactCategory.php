<?php

namespace Modules\Assessment\Enums;

/**
 * 5 impact categories with their AII weights (from spec §5.3)
 *
 * AII = Productivity×40% + Quality×30% + Time Saving×30%
 * Note: learning, ai_adoption, business contribute to the overall profile
 * but are not part of the core AII formula — they feed DWMI via ai_adoption.
 */
enum ImpactCategory: string
{
    case Learning    = 'learning';
    case Productivity = 'productivity';
    case Quality      = 'quality';
    case AiAdoption   = 'ai_adoption';
    case Business     = 'business';

    public function label(): string
    {
        return match ($this) {
            self::Learning     => 'Learning Impact — Tác động học tập',
            self::Productivity => 'Productivity Impact — Tác động năng suất',
            self::Quality      => 'Quality Impact — Tác động chất lượng',
            self::AiAdoption   => 'AI Adoption Impact — Tác động ứng dụng AI',
            self::Business     => 'Business Impact — Tác động tổ chức',
        };
    }

    /** Weight in overall impact profile (not AII formula) */
    public function weight(): float
    {
        return match ($this) {
            self::Learning     => 0.20,
            self::Productivity => 0.25,
            self::Quality      => 0.20,
            self::AiAdoption   => 0.15,
            self::Business     => 0.20,
        };
    }

    /** Valid impact_type values per category */
    public function impactTypes(): array
    {
        return match ($this) {
            self::Learning    => ['skill_gain', 'course_completion', 'knowledge_score'],
            self::Productivity => ['productivity_gain', 'time_saving', 'output_increase', 'task_automation'],
            self::Quality      => ['error_rate_reduction', 'quality_score', 'rework_reduction'],
            self::AiAdoption   => ['ai_tool_usage', 'ai_task_ratio', 'process_digitalization'],
            self::Business     => ['cost_reduction', 'revenue_impact', 'customer_satisfaction'],
        };
    }
}
