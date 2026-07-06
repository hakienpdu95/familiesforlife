<?php

namespace App\Foundation\Vertical;

use App\Foundation\VerticalRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Organization\Models\Organization;

class VerticalTemplate extends Model
{
    protected $fillable = [
        'code', 'label', 'target_label', 'target_org_category',
        'has_physical_assets', 'export_config',
        'readiness_template_slug', 'data_collection_template_slug',
        'default_roles', 'sidebar_config', 'is_active',
        'organization_id', 'source_template_id', 'status', 'activated_at', 'activated_by',
    ];

    protected $casts = [
        'default_roles'       => 'array',
        'sidebar_config'      => 'array',
        'export_config'       => 'array',
        'has_physical_assets' => 'boolean',
        'is_active'           => 'boolean',
        'activated_at'        => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saved(fn ($t)   => VerticalRegistry::clearCache($t->organization_id, $t->code));
        static::deleted(fn ($t) => VerticalRegistry::clearCache($t->organization_id, $t->code));
    }

    /** Các phase (giai đoạn) triển khai, đã sắp xếp theo sort_order. */
    public function phases(): HasMany
    {
        return $this->hasMany(VerticalPhase::class)->orderBy('sort_order')->with('checklistItems');
    }

    /** Danh mục cấu hình (hierarchy/activity_type/doc_type) của template này. */
    public function configItems(): HasMany
    {
        return $this->hasMany(VerticalConfigItem::class);
    }

    /** Bản mẫu gốc khi template này được nhân bản — null nếu tự tạo từ đầu. */
    public function sourceTemplate(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_template_id');
    }

    /** Các bản đã nhân bản từ template này. */
    public function clones(): HasMany
    {
        return $this->hasMany(self::class, 'source_template_id');
    }

    /** Tổ chức sở hữu bản instance này — null nếu là bản mẫu thư viện dùng chung. */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** Shape dữ liệu phases/checklist cho Alpine builder (resources/views/backend/vertical-templates/builder.blade.php). */
    public function toBuilderPhasesData(): array
    {
        $this->loadMissing('phases.checklistItems');

        return $this->phases->map(fn ($phase) => [
            'id'                          => $phase->id,
            'vertical_template_id'        => $phase->vertical_template_id,
            'key'                         => $phase->key,
            'label'                       => $phase->label,
            'sort_order'                  => $phase->sort_order,
            'is_initial'                  => $phase->is_initial,
            'auto_assign_data_collection' => $phase->auto_assign_data_collection,
            'checklist_items'             => $phase->checklistItems->map(fn ($item) => [
                'id'                => $item->id,
                'vertical_phase_id' => $item->vertical_phase_id,
                'key'               => $item->key,
                'label'             => $item->label,
                'is_required'       => $item->is_required,
                'sort_order'        => $item->sort_order,
            ])->all(),
        ])->all();
    }
}
