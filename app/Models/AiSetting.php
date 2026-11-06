<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    protected $fillable = ['provider_config', 'monthly_budget_usd', 'rate_limit_override'];

    protected function casts(): array
    {
        return [
            'provider_config' => 'encrypted:array',
            'monthly_budget_usd' => 'float',
            'rate_limit_override' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::query()->first() ?? new static;
    }

    public static function providerConfig(): array
    {
        return static::current()->provider_config ?? config('ai.default');
    }
}
