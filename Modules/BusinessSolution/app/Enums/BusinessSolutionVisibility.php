<?php

namespace Modules\BusinessSolution\Enums;

enum BusinessSolutionVisibility: string
{
    case Private     = 'private';
    case Public      = 'public';
    case Marketplace = 'marketplace';

    public function label(): string
    {
        return match ($this) {
            self::Private     => 'Riêng tư',
            self::Public      => 'Công khai',
            self::Marketplace => 'Marketplace',
        };
    }
}
