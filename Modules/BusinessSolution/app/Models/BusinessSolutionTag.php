<?php

namespace Modules\BusinessSolution\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessSolutionTag extends Model
{
    protected $table = 'business_solution_tags';

    protected $fillable = [
        'business_solution_id', 'tag',
    ];

    public function businessSolution(): BelongsTo
    {
        return $this->belongsTo(BusinessSolution::class);
    }
}
