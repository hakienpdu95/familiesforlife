<?php

namespace App\Foundation\Vertical;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * "Kích hoạt" 1 vertical cho tổ chức — nhân bản bản mẫu thư viện (organization_id IS NULL)
 * thành 1 bản instance độc lập của tổ chức đó (phases/checklist/config items copy nguyên,
 * sửa gì sau này trên bản instance không ảnh hưởng bản mẫu gốc).
 *
 * Idempotent: nếu tổ chức đã từng có bản instance của vertical này (đã Tắt trước đó) —
 * chỉ reactivate lại, không nhân bản chồng lần 2 (tránh trùng lặp phases/checklist/config).
 */
class CloneVerticalFromTemplateAction
{
    public function execute(int $organizationId, string $code): VerticalTemplate
    {
        $existing = VerticalTemplate::where('organization_id', $organizationId)
            ->where('code', $code)
            ->first();

        if ($existing) {
            $existing->update([
                'status'       => 'active',
                'activated_at' => now(),
                'activated_by' => auth()->id(),
            ]);

            return $existing;
        }

        $library = VerticalTemplate::whereNull('organization_id')
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $library) {
            throw new \InvalidArgumentException("Vertical '{$code}' không tồn tại trong thư viện.");
        }

        return DB::transaction(function () use ($library, $organizationId) {
            $clone = VerticalTemplate::create([
                'code'                          => $library->code,
                'label'                         => $library->label,
                'target_label'                  => $library->target_label,
                'target_org_category'           => $library->target_org_category,
                'has_physical_assets'           => $library->has_physical_assets,
                'export_config'                 => $library->export_config,
                'readiness_template_slug'       => $library->readiness_template_slug,
                'data_collection_template_slug' => $library->data_collection_template_slug,
                'default_roles'                 => $library->default_roles,
                'sidebar_config'                => $library->sidebar_config,
                'is_active'                     => true,
                'organization_id'               => $organizationId,
                'source_template_id'            => $library->id,
                'status'                        => 'active',
                'activated_at'                  => now(),
                'activated_by'                  => auth()->id(),
            ]);

            foreach ($library->phases as $phase) {
                $newPhase = VerticalPhase::create([
                    'vertical_template_id'        => $clone->id,
                    'key'                          => $phase->key,
                    'label'                        => $phase->label,
                    'sort_order'                   => $phase->sort_order,
                    'is_initial'                   => $phase->is_initial,
                    'auto_assign_data_collection'  => $phase->auto_assign_data_collection,
                ]);

                foreach ($phase->checklistItems as $item) {
                    VerticalChecklistItem::create([
                        'vertical_phase_id' => $newPhase->id,
                        'key'               => $item->key,
                        'label'             => $item->label,
                        'is_required'       => $item->is_required,
                        'sort_order'        => $item->sort_order,
                    ]);
                }
            }

            foreach ($library->configItems as $configItem) {
                VerticalConfigItem::create([
                    'vertical_template_id' => $clone->id,
                    'config_group'         => $configItem->config_group,
                    'code'                 => $configItem->code,
                    'label'                => $configItem->label,
                    'is_required'          => $configItem->is_required,
                    'is_active'            => $configItem->is_active,
                    'sort_order'           => $configItem->sort_order,
                ]);
            }

            // Tạo Spatie roles động: {vertical_code}_{suffix}
            foreach ($clone->default_roles ?? [] as $suffix) {
                Role::firstOrCreate(['name' => $clone->code . '_' . $suffix, 'guard_name' => 'web']);
            }

            return $clone;
        });
    }
}
