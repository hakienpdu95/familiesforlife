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
            self::CEO    => ['ceo_dashboard','sales_ai','sop','workflow','users','activity_log'],
            self::SALES  => ['sales_ai','sop'],
            self::OPS    => ['ceo_dashboard','sop','workflow'],
            self::MARKETING => ['sales_ai','sop'],
            self::HR     => ['sop','users'],
            self::AI_OP  => ['ceo_dashboard','sop','workflow'],
            self::ADMIN  => ['ceo_dashboard','sales_ai','sop','workflow','users','roles','integrations','activity_log'],
            self::VIEWER => ['ceo_dashboard','sop'],
        };
    }
}