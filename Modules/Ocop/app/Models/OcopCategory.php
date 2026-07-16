<?php

namespace Modules\Ocop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * spec/danhmuc.html — bảng phân loại sản phẩm OCOP chính thức (nhà nước quy định, thống nhất
 * toàn quốc), 3 cấp: Nhóm lớn (I–VI) → Nhóm → Phân nhóm, "authority" (Cơ quan chủ trì quản lý)
 * chỉ có ở cấp sâu nhất của mỗi nhánh. Đã chuẩn hóa qua OcopCategorySeeder — KHÔNG còn CRUD ở
 * dashboard/ocop/categories (chỉ đọc), cùng lý do bảng đã cố định theo quy định pháp luật.
 *
 * parent_id/depth cùng pattern Modules/Menu (MenuItem) — depth cache lại (0/1/2), không tính
 * đệ quy mỗi lần; tối đa 3 cấp enforce ở OcopCategorySeeder, không phải CHECK constraint DB.
 */
class OcopCategory extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'ocop_categories';

    protected $fillable = [
        'uuid', 'parent_id', 'depth', 'name', 'slug', 'code', 'icon', 'authority',
        'sort_order', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
        'depth'      => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ── Relationships ────────────────────────────────────────────────

    public function products(): HasMany
    {
        return $this->hasMany(OcopProduct::class, 'category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Cây đầy đủ (nhóm lớn → nhóm → phân nhóm) — eager-load tới hết depth=2 (phân nhóm) CỘNG 1
     * cấp rỗng nữa (children của phân nhóm luôn rỗng, taxonomy tối đa 3 cấp) để tránh N+1 VÀ
     * tránh lazy-load exception khi view đệ quy truy cập $item->children ở cấp lá cuối cùng.
     * Dùng cho hiển thị dashboard/ocop/categories (đọc, không CRUD).
     */
    public static function tree(): \Illuminate\Database\Eloquent\Collection
    {
        return static::root()
            ->with(['children' => fn ($q) => $q->with(['children' => fn ($q2) => $q2->with('children')])])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Cây chuẩn hóa dạng PHẲNG (pre-order, giữ đúng thứ tự chính thức I→1→a→b→2...) — dùng cho
     * <select> đơn (không lồng được HTML như bảng/cây thật), mỗi phần tử kèm 'depth' để hiển thị
     * thụt lề. Dùng ở dashboard/ocop/products/create|edit (chọn category_id cho sản phẩm).
     *
     * @return array<int, array{category: self, depth: int}>
     */
    public static function flatTree(): array
    {
        $flat = [];

        $walk = function (\Illuminate\Support\Collection $nodes, int $depth) use (&$walk, &$flat): void {
            foreach ($nodes as $node) {
                $flat[] = ['category' => $node, 'depth' => $depth];
                $walk($node->children, $depth + 1);
            }
        };

        $walk(static::tree(), 0);

        return $flat;
    }
}
