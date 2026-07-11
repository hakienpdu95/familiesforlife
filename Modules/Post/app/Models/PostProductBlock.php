<?php

namespace Modules\Post\Models;

use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Post\Enums\ProductBlockTemplate;
use Illuminate\Support\Str;

/** Không soft-delete — vòng đời gắn chặt bản dịch bài viết, xoá cứng theo cascade khi bản dịch/khối bị xoá. */
class PostProductBlock extends Model
{
    use BelongsToOrganization;

    protected $table = 'post_product_blocks';

    protected $fillable = [
        'uuid',
        'organization_id',
        'translation_id',
        'template',
        'heading',
        'sort_order',
    ];

    protected $casts = [
        'template'   => ProductBlockTemplate::class,
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function translation(): BelongsTo
    {
        return $this->belongsTo(PostArticleTranslation::class, 'translation_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PostProductBlockItem::class, 'block_id')->orderBy('sort_order');
    }

    /** Nút cấp-khối (không gắn với item nào), vd "Xem tất cả sản phẩm". */
    public function buttons(): HasMany
    {
        return $this->hasMany(PostProductBlockButton::class, 'block_id')->whereNull('block_item_id')->orderBy('sort_order');
    }
}
