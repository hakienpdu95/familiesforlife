<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

/**
 * Danh sách version theo blueprint_id, kèm đếm số organization_solutions đang dùng
 * mỗi version (phục vụ Version Manager, A04.3 §12). Bảng `organization_solutions`
 * thuộc module OrganizationSolution (chưa tồn tại) — dùng Schema::hasTable để
 * không vỡ khi module đó chưa được cài đặt.
 */
class ListBlueprintVersionsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListBlueprintVersionsQuery $query */
        $versions = BlueprintVersion::where('blueprint_id', $query->blueprintId)
            ->orderByDesc('created_at')
            ->get();

        if (\Illuminate\Support\Facades\Schema::hasTable('organization_solutions')) {
            $usageCounts = \Illuminate\Support\Facades\DB::table('organization_solutions')
                ->whereIn('blueprint_version_id', $versions->pluck('id'))
                ->selectRaw('blueprint_version_id, count(*) as total')
                ->groupBy('blueprint_version_id')
                ->pluck('total', 'blueprint_version_id');

            $versions->each(function (BlueprintVersion $version) use ($usageCounts) {
                $version->setAttribute('organization_solutions_count', $usageCounts->get($version->id, 0));
            });
        } else {
            $versions->each(fn (BlueprintVersion $version) => $version->setAttribute('organization_solutions_count', 0));
        }

        return $versions;
    }
}
