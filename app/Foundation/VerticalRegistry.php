<?php

namespace App\Foundation;

use App\Foundation\Vertical\DatabaseVertical;
use App\Foundation\Vertical\VerticalTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\BusinessBlueprint\Enums\BlueprintVersionStatus;
use Modules\BusinessBlueprint\Foundation\BlueprintToVerticalDefinitionAdapter;
use Modules\BusinessBlueprint\Models\Blueprint;
use Modules\OrganizationSolution\Enums\OrganizationSolutionStatus;
use Modules\OrganizationSolution\Models\OrganizationSolution;

class VerticalRegistry
{
    /** Bản mẫu thư viện dùng chung (organization_id IS NULL) theo code. */
    public static function resolve(string $code): ?VerticalDefinition
    {
        return static::resolveForOrganization(null, $code);
    }

    /**
     * Bản instance của 1 tổ chức cụ thể theo code — $organizationId null = thư viện dùng chung.
     *
     * Ưu tiên `vertical_templates` (hệ cũ) trước; nếu không có mới fallback sang Business
     * Blueprint (BlueprintToVerticalDefinitionAdapter) — đúng hướng đã ghi trong docblock của
     * adapter đó: dần chuyển các vertical mới sang Blueprint mà không phải sửa lại mọi nơi
     * đang gọi VerticalRegistry::resolve()/resolveForOrganization().
     */
    public static function resolveForOrganization(?int $organizationId, string $code): ?VerticalDefinition
    {
        return static::resolveFromTemplate($organizationId, $code)
            ?? static::resolveFromBlueprint($organizationId, $code);
    }

    private static function resolveFromTemplate(?int $organizationId, string $code): ?VerticalDefinition
    {
        // Cache array of attributes — Eloquent model không serialize tốt qua cache
        $attributes = Cache::remember(
            static::cacheKey($organizationId, $code),
            now()->addHour(),
            function () use ($organizationId, $code) {
                $query = VerticalTemplate::where('code', $code)->where('is_active', true);
                $query = $organizationId === null
                    ? $query->whereNull('organization_id')
                    : $query->where('organization_id', $organizationId);

                return $query->first()?->toArray();
            }
        );

        if (! $attributes) return null;

        $template = (new VerticalTemplate())->forceFill($attributes);
        return new DatabaseVertical($template);
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
     * resolveForOrganization(null, ...) vốn chỉ trả về bản "thư viện dùng chung"
     * (organization_id IS NULL), sẽ bỏ sót vertical_template/Blueprint chỉ đang active cho
     * riêng 1 tổ chức cụ thể (VD "truy-xuat-nguon-goc" của org=2, hay "BP-TXNG" của HTX).
     */
    public static function resolveForSuperAdmin(string $code): ?VerticalDefinition
    {
        $template = VerticalTemplate::where('code', $code)->where('is_active', true)->first();
        if ($template) {
            return new DatabaseVertical($template);
        }

        $blueprint = Blueprint::where('code', $code)->first();
        $version   = $blueprint?->currentVersion;

        if (! $version || $version->status !== BlueprintVersionStatus::Published->value) {
            return null;
        }

        return new BlueprintToVerticalDefinitionAdapter($version);
    }

    /**
     * Danh sách Vertical được deploy từ Business Blueprint hiện đang active — dùng cho
     * "Hub triển khai"/sidebar (song song `vertical_templates`, 2 nguồn chưa hợp nhất).
     * $organizationId null → tất cả tổ chức (super-admin); có giá trị → chỉ tổ chức đó.
     */
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

    /** Danh mục bản mẫu thư viện dùng chung (organization_id IS NULL, active) — cho UI System Admin. */
    public static function libraryTemplates(): Collection
    {
        return VerticalTemplate::whereNull('organization_id')
            ->where('is_active', true)
            ->orderBy('label')
            ->with('phases')
            ->get();
    }

    public static function all(): array
    {
        $rows = Cache::remember(
            'vertical_templates_all',
            now()->addHour(),
            fn () => VerticalTemplate::whereNull('organization_id')->where('is_active', true)->get()->map->toArray()->values()->all()
        );

        return collect($rows)
            ->mapWithKeys(fn ($attrs) => [
                $attrs['code'] => new DatabaseVertical((new VerticalTemplate())->forceFill($attrs))
            ])
            ->all();
    }

    public static function exists(string $code): bool
    {
        return static::resolve($code) !== null;
    }

    public static function clearCache(?int $organizationId = null, ?string $code = null): void
    {
        if ($code) {
            Cache::forget(static::cacheKey($organizationId, $code));
        }
        Cache::forget('vertical_templates_all');
    }

    private static function cacheKey(?int $organizationId, string $code): string
    {
        return 'vertical_template:' . ($organizationId ?? 'lib') . ':' . $code;
    }
}
