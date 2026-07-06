<?php

namespace App\Foundation\Vertical;

/**
 * Tạo 1 vertical hoàn toàn mới cho tổ chức, không nhân bản từ thư viện — instance trống,
 * tự thêm phase/checklist/config qua builder (Phase 7-8, chưa làm). Dùng khi tổ chức cần
 * quy trình triển khai đặc thù, không có sẵn trong thư viện chuẩn.
 */
class CreateVerticalFromScratchAction
{
    public function execute(int $organizationId, string $code, string $label, array $attributes = []): VerticalTemplate
    {
        if (VerticalTemplate::where('organization_id', $organizationId)->where('code', $code)->exists()) {
            throw new \InvalidArgumentException("Tổ chức đã có vertical '{$code}'.");
        }

        return VerticalTemplate::create(array_merge([
            'code'                 => $code,
            'label'                => $label,
            'target_label'         => 'Tổ chức',
            'target_org_category'  => 'organization',
            'has_physical_assets'  => true,
            'default_roles'        => [],
            'sidebar_config'       => [],
            'is_active'            => true,
            'organization_id'      => $organizationId,
            'source_template_id'   => null,
            'status'               => 'active',
            'activated_at'         => now(),
            'activated_by'         => auth()->id(),
        ], $attributes));
    }
}
