<?php

namespace Modules\Post\Enums;

enum ButtonStyle: string
{
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Outline = 'outline';
    case Ghost = 'ghost';

    public function label(): string
    {
        return match ($this) {
            self::Primary => __('Chính (primary)'),
            self::Secondary => __('Phụ (secondary)'),
            self::Outline => __('Viền (outline)'),
            self::Ghost => __('Trong suốt (ghost)'),
        };
    }

    /** Map sang DaisyUI btn-* class. */
    public function btnClass(): string
    {
        return match ($this) {
            self::Primary => 'btn-primary',
            self::Secondary => 'btn-secondary',
            self::Outline => 'btn-outline',
            self::Ghost => 'btn-ghost',
        };
    }
}
