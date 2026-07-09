<?php

namespace App\Foundation;

use Illuminate\Support\Collection;
use Modules\BusinessBlueprint\Enums\BlueprintVersionStatus;
use Modules\BusinessBlueprint\Foundation\BlueprintToVerticalDefinitionAdapter;
use Modules\BusinessBlueprint\Models\Blueprint;
use Modules\OrganizationSolution\Enums\OrganizationSolutionStatus;
use Modules\OrganizationSolution\Models\OrganizationSolution;

class VerticalRegistry
{
    /** Bản mẫu thư viện dùng chung theo code. */
    public static function resolve(string $code): ?VerticalDefinition
    {
        return static::resolveForOrganization(null, $code);
    }

    /** Bản instance của 1 tổ chức cụ thể theo code — $organizationId null = thư viện dùng chung. */
    public static function resolveForOrganization(?int $organizationId, string $code): ?VerticalDefinition
    {
        return static::resolveFromBlueprint($organizationId, $code);
    }

    /**
     * $organizationId null → trả về bản Published mới nhất bất kể tổ chức nào đã deploy
     * (dùng cho thư viện dùng chung). $organizationId cụ thể → chỉ trả về nếu tổ chức đó
     * thực sự đã deploy đúng blueprint version này (OrganizationSolution running/ready) —
     * không phải cứ Blueprint tồn tại là mọi tổ chức đều thấy được.
     */
    private static function resolveFromBlueprint(?int $organizationId, string $code): ?VerticalDefinition
    {
        $blueprint = Blueprint::where('code', $code)->first();
        $version   = $blueprint?->currentVersion;

        if (! $version || $version->status !== BlueprintVersionStatus::Published->value) {
            return null;
        }

        if ($organizationId !== null) {
            $deployed = OrganizationSolution::withoutTenant()
                ->where('organization_id', $organizationId)
                ->where('blueprint_version_id', $version->id)
                ->whereIn('status', [
                    OrganizationSolutionStatus::Running->value,
                    OrganizationSolutionStatus::Ready->value,
                ])
                ->exists();

            if (! $deployed) return null;
        }

        return new BlueprintToVerticalDefinitionAdapter($version);
    }

    /**
     * super-admin xem được BẤT KỲ vertical nào đang active, ở BẤT KỲ tổ chức nào — khác
     * resolveForOrganization(null, ...) vốn chỉ trả về bản "thư viện dùng chung", sẽ bỏ sót
     * Blueprint chỉ đang active cho riêng 1 tổ chức cụ thể.
     */
    public static function resolveForSuperAdmin(string $code): ?VerticalDefinition
    {
        $blueprint = Blueprint::where('code', $code)->first();
        $version   = $blueprint?->currentVersion;

        if (! $version || $version->status !== BlueprintVersionStatus::Published->value) {
            return null;
        }

        return new BlueprintToVerticalDefinitionAdapter($version);
    }

    /** Danh sách Vertical được deploy từ Business Blueprint hiện đang active — dùng cho "Hub triển khai"/sidebar.
     *  $organizationId null → tất cả tổ chức (super-admin); có giá trị → chỉ tổ chức đó. */
    public static function activeBlueprintVerticals(?int $organizationId): Collection
    {
        $query = OrganizationSolution::withoutTenant()
            ->whereIn('status', [OrganizationSolutionStatus::Running->value, OrganizationSolutionStatus::Ready->value])
            ->with('blueprintVersion.blueprint');

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        return $query->get()
            ->filter(fn (OrganizationSolution $os) => $os->blueprintVersion?->status === BlueprintVersionStatus::Published->value)
            ->map(fn (OrganizationSolution $os) => new BlueprintToVerticalDefinitionAdapter($os->blueprintVersion))
            ->unique(fn (VerticalDefinition $v) => $v->code())
            ->values();
    }

    public static function exists(string $code): bool
    {
        return static::resolve($code) !== null;
    }
}
