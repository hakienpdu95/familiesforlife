<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;

class PassFailConfig extends Model
{
    protected $table = 'pass_fail_configs';

    protected $fillable = [
        'assessment_code',
        'passing_score',
        'label_pass',
        'label_fail',
    ];

    protected function casts(): array
    {
        return [
            'passing_score' => 'float',
        ];
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('assessment_code', $code)->first();
    }
}
