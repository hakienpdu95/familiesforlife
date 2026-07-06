<?php

namespace Modules\BusinessSolution\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessSolutionVersion extends Model
{
    protected $table = 'business_solution_versions';

    protected $fillable = [
        'business_solution_id', 'version', 'status', 'release_note',
        'published_at', 'published_by', 'metadata',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'metadata'     => 'array',
    ];

    public function businessSolution(): BelongsTo
    {
        return $this->belongsTo(BusinessSolution::class);
    }
}
