<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\Concerns\AdvancesToConfiguring;
use Modules\OrganizationSolution\Models\OrganizationSolution;

/**
 * Bước 7 (spec §3.6): cấu hình Dashboard — ghi vào organization_dashboard_widgets
 * (thay JSON config_key='dashboard'). Xoá hết widget cũ rồi tạo lại theo thứ tự mới
 * (đơn giản hơn so khớp từng phần tử, vì thứ tự/sort_order là toàn bộ ý nghĩa của bước này).
 */
class ConfigureDashboardAction
{
    use AsAction;
    use AdvancesToConfiguring;

    /** @param array<int, array{blueprint_analytic_id?:?int, widget_type?:string, title:string, enabled?:bool}> $items */
    public function handle(OrganizationSolution $orgSolution, array $items): void
    {
        DB::transaction(function () use ($orgSolution, $items) {
            $this->advanceToConfiguring($orgSolution);

            $orgSolution->dashboardWidgets()->delete();

            foreach (array_values($items) as $index => $item) {
                $orgSolution->dashboardWidgets()->create([
                    'blueprint_analytic_id' => $item['blueprint_analytic_id'] ?? null,
                    'widget_type'           => $item['widget_type'] ?? 'metric',
                    'title'                 => $item['title'],
                    'enabled'               => $item['enabled'] ?? true,
                    'sort_order'            => $index,
                ]);
            }
        });
    }
}
