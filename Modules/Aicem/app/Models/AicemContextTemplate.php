<?php

namespace Modules\Aicem\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AicemContextTemplate extends TenantAwareModel
{
    protected $table = 'aicem_context_templates';

    protected $fillable = [
        'subject_type',
        'name',
        'slug',
        'version',
        'is_default',
        'schema',
    ];

    protected function casts(): array
    {
        return [
            'schema'     => 'array',
            'version'    => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(AicemWorkflow::class, 'context_template_id');
    }
}
