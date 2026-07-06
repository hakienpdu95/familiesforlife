<?php

namespace Modules\KcItem\Models;

use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KcTag extends Model
{
    use BelongsToOrganization;

    protected $table = 'kc_tags';

    protected $fillable = [
        'uuid',
        'organization_id',
        'name',
        'slug',
        'color_hex',
    ];

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(KcItem::class, 'kc_item_tags', 'tag_id', 'item_id');
    }
}
