<?php

namespace App\Foundation\Vertical;

use Illuminate\Support\Collection;

class VerticalConfigService
{
    public static function activityTypes(DatabaseVertical $vertical): array
    {
        $rows = VerticalConfigItem::where('vertical_template_id', $vertical->template()->id)
            ->where('config_group', 'activity_type')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('label', 'code')
            ->toArray();

        return $rows ?: ($vertical->defaultActivityTypes() ?? []);
    }

    public static function legalDocTypes(DatabaseVertical $vertical): array
    {
        $rows = VerticalConfigItem::where('vertical_template_id', $vertical->template()->id)
            ->where('config_group', 'doc_type')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('label', 'code')
            ->toArray();

        return $rows ?: ($vertical->defaultLegalDocTypes() ?? []);
    }

    /** Danh mục loại issue — hoàn toàn do từng tổ chức tự định nghĩa qua UI, không có default cứng. */
    public static function issueTypes(DatabaseVertical $vertical): array
    {
        return VerticalConfigItem::where('vertical_template_id', $vertical->template()->id)
            ->where('config_group', 'issue_type')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('label', 'code')
            ->toArray();
    }

    public static function configItems(DatabaseVertical $vertical, string $configGroup): Collection
    {
        return VerticalConfigItem::where('vertical_template_id', $vertical->template()->id)
            ->where('config_group', $configGroup)
            ->orderBy('sort_order')
            ->get();
    }

    public static function hierarchyLabels(DatabaseVertical $vertical): array
    {
        $db = VerticalConfigItem::where('vertical_template_id', $vertical->template()->id)
            ->where('config_group', 'hierarchy')
            ->where('is_active', true)
            ->pluck('label', 'code')
            ->toArray();

        $itemDefault = $vertical->itemLabel() ?? 'Đơn vị';

        return [
            'site'        => $db['site_label']        ?? $vertical->defaultSiteLabel()       ?? 'Vùng sản xuất',
            'area'        => $db['area_label']         ?? $vertical->areaLabel()              ?? 'Khu',
            'lot'         => $db['lot_label']          ?? $vertical->lotLabel()               ?? 'Lô',
            'item'        => $db['item_label']         ?? $itemDefault,
            'item_plural' => $db['item_label_plural']  ?? $itemDefault,
            'item_prefix' => $db['item_code_prefix']   ?? $vertical->defaultItemCodePrefix()  ?? 'I',
        ];
    }
}
