<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PassportDomainScore extends Model
{
    protected $table = 'passport_domain_scores';

    public $timestamps = false;

    protected $fillable = [
        'passport_entry_id',
        'domain_code',
        'domain_name',
        'score',
        'required_score',
        'gap',
        'is_critical',
    ];

    protected function casts(): array
    {
        return [
            'score'          => 'float',
            'required_score' => 'float',
            'gap'            => 'float',
            'is_critical'    => 'boolean',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(PassportEntry::class, 'passport_entry_id');
    }
}
