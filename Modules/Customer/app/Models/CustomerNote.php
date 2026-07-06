<?php

namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerNote extends Model
{
    use SoftDeletes;

    protected $table = 'customer_notes';

    protected $fillable = [
        'customer_id', 'organization_id', 'content',
        'is_pinned', 'author_id', 'author_name',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
