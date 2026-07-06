<?php

namespace Modules\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadNote extends Model
{
    use SoftDeletes;

    protected $table = 'lead_notes';

    protected $fillable = [
        'lead_id', 'organization_id', 'content',
        'is_pinned', 'author_id', 'author_name',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
