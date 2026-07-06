<?php
namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CustomerTag extends Model
{
    protected $table = 'customer_tags';

    protected $fillable = ['organization_id', 'name', 'color'];

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_tag_map', 'tag_id', 'customer_id');
    }
}
