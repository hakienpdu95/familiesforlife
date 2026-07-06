<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PassportCertification extends Model
{
    protected $table = 'passport_certifications';

    public $timestamps = false;

    protected $fillable = [
        'passport_entry_id',
        'cert_definition_id',
        'cert_code',
        'cert_name',
        'cert_type_code',
        'level_code',
        'level_order',
        'issued_at',
        'expires_at',
        'certificate_number',
        'composite_score_at_issue',
    ];

    protected function casts(): array
    {
        return [
            'issued_at'              => 'date',
            'expires_at'             => 'date',
            'composite_score_at_issue' => 'float',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(PassportEntry::class, 'passport_entry_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(CertificationDefinition::class, 'cert_definition_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
