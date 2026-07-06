<?php

namespace Modules\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSetting extends Model
{
    protected $table = 'organization_settings';

    protected $fillable = [
        'organization_id',
        'key',
        'value',
        'type',
    ];

    // ── Relationships ────────────────────────────────────────────────

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    // ── Cast helper ──────────────────────────────────────────────────

    /**
     * Return the value cast to the correct PHP type based on the 'type' field.
     */
    public function getCastedValue(): mixed
    {
        return match ($this->type) {
            'integer' => (int) $this->value,
            'float'   => (float) $this->value,
            'boolean' => (bool) $this->value && $this->value !== '0',
            'json'    => json_decode($this->value, true),
            default   => $this->value, // 'string'
        };
    }
}
