<?php
namespace App\Enums;

enum RoleEnum: string
{
    case CEO       = 'ceo';
    case SALES     = 'sales';
    case OPS       = 'ops';
    case MARKETING = 'marketing';
    case HR        = 'hr';
    case AI_OP     = 'ai_operator';
    case ADMIN     = 'system_admin';
    case VIEWER    = 'viewer';

    public function label(): string
    {
        return match($this) {
            self::CEO       => 'CEO / Founder',
            self::SALES     => 'Sales Team',
            self::OPS       => 'Operations',
            self::MARKETING => 'Marketing',
            self::HR        => 'HR / Admin Staff',
            self::AI_OP     => 'AI Operator',
            self::ADMIN     => 'System Admin',
            self::VIEWER    => 'Viewer / Partner',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::CEO       => 'badge-primary',
            self::ADMIN     => 'badge-error',
            self::OPS       => 'badge-warning',
            self::SALES     => 'badge-success',
            self::HR        => 'badge-info',
            self::AI_OP     => 'badge-secondary',
            self::MARKETING => 'badge-accent',
            self::VIEWER    => 'badge-ghost',
        };
    }

    public function visibleModules(): array
    {
        return match($this) {
            self::CEO    => ['ceo_dashboard','crm','sales_ai','sop','workflow','users','reports','activity_log'],
            self::SALES  => ['crm','sales_ai','sop','reports'],
            self::OPS    => ['ceo_dashboard','crm','sop','workflow','reports'],
            self::MARKETING => ['crm','sales_ai','sop','reports'],
            self::HR     => ['sop','users','reports'],
            self::AI_OP  => ['ceo_dashboard','crm','sop','workflow','reports'],
            self::ADMIN  => ['ceo_dashboard','crm','sales_ai','sop','workflow','users','roles','reports','integrations','activity_log'],
            self::VIEWER => ['ceo_dashboard','sop','reports'],
        };
    }
}